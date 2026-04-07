<?php

namespace App\Services\Imports;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use App\Models\WeaponImportBatch;
use App\Models\WeaponImportRow;
use App\Services\Imports\Contracts\ImportBatchProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ClientImportProcessor implements ImportBatchProcessor
{
    private const REQUIRED_COLUMNS = [
        'nit',
        'name',
        'legal_representative',
        'address',
        'city',
    ];

    private const FIELD_LABELS = [
        'nit' => 'NIT./CC',
        'name' => 'Razón social',
        'legal_representative' => 'Representante legal',
        'address' => 'Dirección principal',
        'city' => 'Ciudad',
    ];

    /**
     * @var array<string, string>
     */
    private const HEADER_ALIASES = [
        'nit cc' => 'nit',
        'nit' => 'nit',
        'cc' => 'nit',
        'razon social' => 'name',
        'nombre rep legal' => 'legal_representative',
        'nombre representante legal' => 'legal_representative',
        'representante legal' => 'legal_representative',
        'direccion principal' => 'address',
        'direccion' => 'address',
        'ciudad' => 'city',
    ];

    public function type(): string
    {
        return WeaponImportBatch::TYPE_CLIENT;
    }

    public function prepareRows(array $headers, array $rows): array
    {
        $columnMap = $this->resolveColumnMap($headers);
        $nitFrequency = [];

        foreach ($rows as $row) {
            $nitValue = $this->extractCell($row['cells'], $columnMap, 'nit');
            $normalizedNit = $this->normalizeNitCompareValue($nitValue);

            if ($normalizedNit !== '') {
                $nitFrequency[$normalizedNit] = ($nitFrequency[$normalizedNit] ?? 0) + 1;
            }
        }

        $existingClientsByNit = Client::query()
            ->whereNotNull('nit')
            ->get()
            ->groupBy(fn (Client $client) => $this->normalizeNitCompareValue($client->nit));

        $preparedRows = [];
        $counts = [
            WeaponImportRow::ACTION_CREATE => 0,
            WeaponImportRow::ACTION_UPDATE => 0,
            WeaponImportRow::ACTION_NO_CHANGE => 0,
            WeaponImportRow::ACTION_ERROR => 0,
        ];

        foreach ($rows as $row) {
            $rawPayload = $this->buildRawPayload($row['cells'], $columnMap);
            $normalizedPayload = [
                'nit' => $this->normalizeRequiredString($rawPayload['nit'] ?? ''),
                'name' => $this->normalizeRequiredString($rawPayload['name'] ?? ''),
                'legal_representative' => $this->normalizeOptionalString($rawPayload['legal_representative'] ?? ''),
                'address' => $this->normalizeOptionalString($rawPayload['address'] ?? ''),
                'city' => $this->normalizeOptionalString($rawPayload['city'] ?? ''),
            ];
            $errors = [];

            if (($normalizedPayload['nit'] ?? null) === null) {
                $errors[] = 'El NIT./CC es obligatorio.';
            }

            if (($normalizedPayload['name'] ?? null) === null) {
                $errors[] = 'La razón social es obligatoria.';
            }

            $normalizedNit = $this->normalizeNitCompareValue((string) ($normalizedPayload['nit'] ?? ''));
            if ($normalizedNit !== '' && ($nitFrequency[$normalizedNit] ?? 0) > 1) {
                $errors[] = 'El NIT./CC está repetido dentro del archivo.';
            }

            $matchingClients = $normalizedNit !== '' ? ($existingClientsByNit->get($normalizedNit) ?? collect()) : collect();
            $client = $matchingClients->count() === 1 ? $matchingClients->first() : null;
            $beforePayload = $client ? $this->clientSnapshot($client) : null;

            if ($matchingClients->count() > 1) {
                $errors[] = 'Ya existen varios clientes con el mismo NIT./CC en el sistema.';
            }

            if ($errors !== []) {
                $action = WeaponImportRow::ACTION_ERROR;
                $summary = implode(' ', array_unique($errors));
            } elseif (! $client) {
                $action = WeaponImportRow::ACTION_CREATE;
                $summary = 'NIT nuevo. Se creará el cliente base.';
            } else {
                $changedFields = $this->detectChangedFields($client, $normalizedPayload);

                if ($changedFields === []) {
                    $action = WeaponImportRow::ACTION_NO_CHANGE;
                    $summary = 'La información importable ya coincide con el sistema.';
                } else {
                    $action = WeaponImportRow::ACTION_UPDATE;
                    $summary = 'Actualiza: ' . implode(', ', array_map(
                        fn (string $field) => self::FIELD_LABELS[$field] ?? $field,
                        $changedFields
                    ));
                }
            }

            $counts[$action]++;

            $preparedRows[] = [
                'client_id' => $client?->id,
                'row_number' => $row['row_number'],
                'action' => $action,
                'summary' => $summary,
                'raw_payload' => $rawPayload,
                'normalized_payload' => $normalizedPayload,
                'before_payload' => $beforePayload,
                'after_payload' => null,
                'errors' => array_values(array_unique($errors)),
            ];
        }

        return [$preparedRows, $counts];
    }

    public function executeRow(WeaponImportRow $row, User $user): void
    {
        DB::transaction(function () use ($row, $user) {
            $row->refresh();
            $row->loadMissing('client');

            if ($row->execution_status === WeaponImportRow::EXECUTION_COMPLETED) {
                return;
            }

            $row->update([
                'execution_status' => WeaponImportRow::EXECUTION_PROCESSING,
                'execution_error' => null,
            ]);

            if ($row->action === WeaponImportRow::ACTION_NO_CHANGE) {
                $client = $row->client ?: Client::query()
                    ->where('nit', $row->normalized_payload['nit'] ?? null)
                    ->first();

                $row->update([
                    'client_id' => $client?->id,
                    'after_payload' => $client ? $this->clientSnapshot($client) : null,
                    'execution_status' => WeaponImportRow::EXECUTION_COMPLETED,
                    'processed_at' => now(),
                ]);

                return;
            }

            $payload = $row->normalized_payload ?? [];

            if ($row->action === WeaponImportRow::ACTION_CREATE) {
                $client = Client::create($this->buildCreatePayload($payload));
                $after = $this->clientSnapshot($client->fresh());

                $row->update([
                    'client_id' => $client->id,
                    'after_payload' => $after,
                    'execution_status' => WeaponImportRow::EXECUTION_COMPLETED,
                    'processed_at' => now(),
                ]);

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'client_import_created',
                    'auditable_type' => Client::class,
                    'auditable_id' => $client->id,
                    'before' => null,
                    'after' => $after,
                ]);

                return;
            }

            $client = $row->client ?: Client::query()
                ->where('nit', $payload['nit'] ?? null)
                ->first();

            if (! $client) {
                throw new RuntimeException(sprintf(
                    'No se encontró el cliente de la fila %d al momento de ejecutar el lote.',
                    $row->row_number
                ));
            }

            $before = $this->clientSnapshot($client);
            $client->fill($this->buildUpdatePayload($payload));
            $client->save();

            $after = $this->clientSnapshot($client->fresh());

            $row->update([
                'client_id' => $client->id,
                'before_payload' => $before,
                'after_payload' => $after,
                'execution_status' => WeaponImportRow::EXECUTION_COMPLETED,
                'processed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'client_import_updated',
                'auditable_type' => Client::class,
                'auditable_id' => $client->id,
                'before' => $before,
                'after' => $after,
            ]);
        });
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, int>
     */
    private function resolveColumnMap(array $headers): array
    {
        $columnMap = [];

        foreach ($headers as $index => $header) {
            $normalizedHeader = $this->normalizeHeader($header);
            if ($normalizedHeader === '') {
                continue;
            }

            $field = self::HEADER_ALIASES[$normalizedHeader] ?? null;
            if (! $field || isset($columnMap[$field])) {
                continue;
            }

            $columnMap[$field] = $index;
        }

        $missingColumns = array_values(array_diff(self::REQUIRED_COLUMNS, array_keys($columnMap)));
        if ($missingColumns !== []) {
            throw ValidationException::withMessages([
                'document' => 'Faltan columnas obligatorias: ' . implode(', ', array_map(
                    fn (string $field) => self::FIELD_LABELS[$field] ?? $field,
                    $missingColumns
                )) . '.',
            ]);
        }

        return $columnMap;
    }

    /**
     * @param  array<int, string>  $cells
     * @param  array<string, int>  $columnMap
     * @return array<string, string>
     */
    private function buildRawPayload(array $cells, array $columnMap): array
    {
        $payload = [];

        foreach (self::REQUIRED_COLUMNS as $field) {
            $payload[$field] = $this->extractCell($cells, $columnMap, $field);
        }

        return $payload;
    }

    /**
     * @param  array<int, string>  $cells
     * @param  array<string, int>  $columnMap
     */
    private function extractCell(array $cells, array $columnMap, string $field): string
    {
        $index = $columnMap[$field] ?? null;
        if ($index === null) {
            return '';
        }

        return trim((string) ($cells[$index] ?? ''));
    }

    private function normalizeRequiredString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeOptionalString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, string>
     */
    private function detectChangedFields(Client $client, array $normalizedPayload): array
    {
        $changedFields = [];

        if (! $this->normalizedNitMatches($client->nit, $normalizedPayload['nit'] ?? null)) {
            $changedFields[] = 'nit';
        }

        if (! $this->valuesMatch($client->name, $normalizedPayload['name'] ?? null)) {
            $changedFields[] = 'name';
        }

        foreach (['legal_representative', 'address', 'city'] as $field) {
            $incomingValue = $normalizedPayload[$field] ?? null;

            if ($incomingValue === null) {
                continue;
            }

            if (! $this->valuesMatch($client->{$field}, $incomingValue)) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    /**
     * @return array<string, mixed>
     */
    private function clientSnapshot(Client $client): array
    {
        return [
            'nit' => $client->nit,
            'name' => $client->name,
            'legal_representative' => $client->legal_representative,
            'address' => $client->address,
            'city' => $client->city,
            'contact_name' => $client->contact_name,
            'email' => $client->email,
            'neighborhood' => $client->neighborhood,
            'department' => $client->department,
            'latitude' => $client->latitude,
            'longitude' => $client->longitude,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildCreatePayload(array $payload): array
    {
        return [
            'nit' => $payload['nit'] ?? null,
            'name' => $payload['name'] ?? null,
            'legal_representative' => $payload['legal_representative'] ?? null,
            'address' => $payload['address'] ?? null,
            'city' => $payload['city'] ?? null,
            'contact_name' => null,
            'email' => null,
            'neighborhood' => null,
            'department' => null,
            'latitude' => null,
            'longitude' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(array $payload): array
    {
        $updatePayload = [
            'name' => $payload['name'] ?? null,
        ];

        foreach (['legal_representative', 'address', 'city'] as $field) {
            if (($payload[$field] ?? null) !== null) {
                $updatePayload[$field] = $payload[$field];
            }
        }

        return $updatePayload;
    }

    private function normalizedNitMatches(?string $currentValue, mixed $incomingValue): bool
    {
        return $this->normalizeNitCompareValue($currentValue) === $this->normalizeNitCompareValue((string) ($incomingValue ?? ''));
    }

    private function valuesMatch(mixed $currentValue, mixed $incomingValue): bool
    {
        if ($currentValue === null || $currentValue === '') {
            return $incomingValue === null || $incomingValue === '';
        }

        return trim((string) $currentValue) === trim((string) $incomingValue);
    }

    private function normalizeHeader(string $value): string
    {
        $value = Str::ascii($value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function normalizeNitCompareValue(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtoupper($value);

        return preg_replace('/[^A-Z0-9]+/', '', $value) ?? '';
    }
}

