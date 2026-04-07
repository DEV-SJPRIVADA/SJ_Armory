<?php

namespace App\Services\Imports;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponImportBatch;
use App\Models\WeaponImportRow;
use App\Services\Imports\Contracts\ImportBatchProcessor;
use App\Services\WeaponDocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class WeaponImportProcessor implements ImportBatchProcessor
{
    private const REQUIRED_COLUMNS = [
        'weapon_type',
        'brand',
        'serial_number',
        'caliber',
        'capacity',
        'permit_type',
        'permit_number',
        'permit_expires_at',
    ];

    private const IMPORTABLE_FIELDS = [
        'weapon_type',
        'brand',
        'serial_number',
        'caliber',
        'capacity',
        'permit_type',
        'permit_number',
        'permit_expires_at',
    ];

    private const FIELD_LABELS = [
        'weapon_type' => 'Tipo de arma',
        'brand' => 'Marca',
        'serial_number' => 'No. serie',
        'caliber' => 'Calibre',
        'capacity' => 'Capacidad',
        'permit_type' => 'Tipo de permiso',
        'permit_number' => 'No. permiso',
        'permit_expires_at' => 'Fecha de vencimiento',
    ];

    /**
     * @var array<string, string>
     */
    private const HEADER_ALIASES = [
        'tipo de arma' => 'weapon_type',
        'tipo arma' => 'weapon_type',
        'marca arma' => 'brand',
        'marca' => 'brand',
        'no serie' => 'serial_number',
        'n serie' => 'serial_number',
        'numero serie' => 'serial_number',
        'numero de serie' => 'serial_number',
        'serial' => 'serial_number',
        'calibre' => 'caliber',
        'capacidad' => 'capacity',
        'tipo permiso' => 'permit_type',
        'tipo de permiso' => 'permit_type',
        'no permiso' => 'permit_number',
        'n permiso' => 'permit_number',
        'numero permiso' => 'permit_number',
        'numero de permiso' => 'permit_number',
        'fecha vencimiento salvoconducto' => 'permit_expires_at',
        'fecha de vencimiento salvoconducto' => 'permit_expires_at',
        'fecha vencimiento' => 'permit_expires_at',
        'vence' => 'permit_expires_at',
    ];

    /**
     * @var array<string, string>
     */
    private const WEAPON_TYPE_MAP = [
        'escopeta' => 'Escopeta',
        'pistola' => 'Pistola',
        'revolver' => 'Revólver',
        'subametralladora' => 'Subametralladora',
        'uzi' => 'Subametralladora',
    ];

    /**
     * @var array<string, string>
     */
    private const PERMIT_TYPE_MAP = [
        'porte' => 'porte',
        'tenencia' => 'tenencia',
    ];

    public function __construct(private readonly WeaponDocumentService $documentService)
    {
    }

    public function type(): string
    {
        return WeaponImportBatch::TYPE_WEAPON;
    }

    public function prepareRows(array $headers, array $rows): array
    {
        $columnMap = $this->resolveColumnMap($headers);
        $serialFrequency = [];
        $serialCandidates = [];

        foreach ($rows as $row) {
            $serialValue = $this->extractCell($row['cells'], $columnMap, 'serial_number');
            $normalizedSerial = $this->normalizeCompareValue($serialValue);

            if ($normalizedSerial !== '') {
                $serialFrequency[$normalizedSerial] = ($serialFrequency[$normalizedSerial] ?? 0) + 1;
                $serialCandidates[] = $serialValue;
            }
        }

        $existingWeapons = Weapon::query()
            ->whereIn('serial_number', array_values(array_unique($serialCandidates)))
            ->get()
            ->keyBy(fn (Weapon $weapon) => $this->normalizeCompareValue($weapon->serial_number));

        $preparedRows = [];
        $counts = [
            WeaponImportRow::ACTION_CREATE => 0,
            WeaponImportRow::ACTION_UPDATE => 0,
            WeaponImportRow::ACTION_NO_CHANGE => 0,
            WeaponImportRow::ACTION_ERROR => 0,
        ];

        foreach ($rows as $row) {
            $rawPayload = $this->buildRawPayload($row['cells'], $columnMap);
            $normalizedPayload = [];
            $errors = [];

            foreach (self::REQUIRED_COLUMNS as $field) {
                $value = $rawPayload[$field] ?? '';
                [$normalizedValue, $fieldErrors] = $this->normalizeField($field, $value);
                $normalizedPayload[$field] = $normalizedValue;
                $errors = [...$errors, ...$fieldErrors];
            }

            $normalizedSerial = $this->normalizeCompareValue((string) ($normalizedPayload['serial_number'] ?? ''));
            if ($normalizedSerial !== '' && ($serialFrequency[$normalizedSerial] ?? 0) > 1) {
                $errors[] = 'La serie está repetida dentro del archivo.';
            }

            $weapon = $normalizedSerial !== '' ? $existingWeapons->get($normalizedSerial) : null;
            $beforePayload = $weapon ? $this->weaponSnapshot($weapon) : null;

            if ($errors !== []) {
                $action = WeaponImportRow::ACTION_ERROR;
                $summary = implode(' ', array_unique($errors));
            } elseif (! $weapon) {
                $action = WeaponImportRow::ACTION_CREATE;
                $summary = 'Serie nueva. Se creará el arma.';
            } else {
                $changedFields = $this->detectChangedFields($weapon, $normalizedPayload);

                if ($changedFields === []) {
                    $action = WeaponImportRow::ACTION_NO_CHANGE;
                    $summary = 'La información ya coincide con el sistema.';
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
                'weapon_id' => $weapon?->id,
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
            $row->loadMissing('weapon');

            if ($row->execution_status === WeaponImportRow::EXECUTION_COMPLETED) {
                return;
            }

            $row->update([
                'execution_status' => WeaponImportRow::EXECUTION_PROCESSING,
                'execution_error' => null,
            ]);

            if ($row->action === WeaponImportRow::ACTION_NO_CHANGE) {
                $weapon = $row->weapon ?: Weapon::query()
                    ->where('serial_number', $row->normalized_payload['serial_number'] ?? null)
                    ->first();

                $row->update([
                    'weapon_id' => $weapon?->id,
                    'after_payload' => $weapon ? $this->weaponSnapshot($weapon) : null,
                    'execution_status' => WeaponImportRow::EXECUTION_COMPLETED,
                    'processed_at' => now(),
                ]);

                return;
            }

            $payload = $row->normalized_payload ?? [];

            if ($row->action === WeaponImportRow::ACTION_CREATE) {
                $payload['internal_code'] = sprintf('SJ-%04d', $this->nextInternalCodeNumber());
                $payload['ownership_type'] = 'company_owned';

                $weapon = Weapon::create($payload);

                $this->documentService->syncPermitDocument($weapon);
                $this->documentService->syncRenewalDocument($weapon);

                $after = $this->weaponSnapshot($weapon->fresh());

                $row->update([
                    'weapon_id' => $weapon->id,
                    'after_payload' => $after,
                    'execution_status' => WeaponImportRow::EXECUTION_COMPLETED,
                    'processed_at' => now(),
                ]);

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'weapon_import_created',
                    'auditable_type' => Weapon::class,
                    'auditable_id' => $weapon->id,
                    'before' => null,
                    'after' => $after,
                ]);

                return;
            }

            $weapon = $row->weapon ?: Weapon::query()
                ->where('serial_number', $payload['serial_number'] ?? null)
                ->first();

            if (! $weapon) {
                throw new RuntimeException(sprintf(
                    'No se encontró el arma de la fila %d al momento de ejecutar el lote.',
                    $row->row_number
                ));
            }

            $before = $this->weaponSnapshot($weapon);
            $weapon->fill($payload);
            $weapon->save();

            $this->documentService->syncPermitDocument($weapon);
            $this->documentService->syncRenewalDocument($weapon);

            $after = $this->weaponSnapshot($weapon->fresh());

            $row->update([
                'weapon_id' => $weapon->id,
                'before_payload' => $before,
                'after_payload' => $after,
                'execution_status' => WeaponImportRow::EXECUTION_COMPLETED,
                'processed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'weapon_import_updated',
                'auditable_type' => Weapon::class,
                'auditable_id' => $weapon->id,
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

    /**
     * @return array{0: mixed, 1: array<int, string>}
     */
    private function normalizeField(string $field, string $value): array
    {
        $value = trim($value);

        if ($field !== 'permit_expires_at' && $value === '') {
            return [null, [sprintf('La columna %s es obligatoria.', self::FIELD_LABELS[$field] ?? $field)]];
        }

        return match ($field) {
            'weapon_type' => $this->normalizeWeaponType($value),
            'permit_type' => $this->normalizePermitType($value),
            'permit_expires_at' => $this->normalizeDate($value),
            'serial_number' => [$value, $value === '' ? ['La serie es obligatoria.'] : []],
            default => [$value, []],
        };
    }

    /**
     * @return array{0: ?string, 1: array<int, string>}
     */
    private function normalizeWeaponType(string $value): array
    {
        $normalizedKey = $this->normalizeHeader($value);
        $mapped = self::WEAPON_TYPE_MAP[$normalizedKey] ?? null;

        if (! $mapped) {
            return [null, ['Tipo de arma no válido.']];
        }

        return [$mapped, []];
    }

    /**
     * @return array{0: ?string, 1: array<int, string>}
     */
    private function normalizePermitType(string $value): array
    {
        $normalizedKey = $this->normalizeHeader($value);
        $mapped = self::PERMIT_TYPE_MAP[$normalizedKey] ?? null;

        if (! $mapped) {
            return [null, ['Tipo de permiso no válido.']];
        }

        return [$mapped, []];
    }

    /**
     * @return array{0: ?string, 1: array<int, string>}
     */
    private function normalizeDate(string $value): array
    {
        if ($value === '') {
            return [null, []];
        }

        if (is_numeric($value)) {
            $date = Carbon::create(1899, 12, 30)->addDays((int) floor((float) $value));

            return [$date->format('Y-m-d'), []];
        }

        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'Y-m-d', 'Y/m/d', 'd.m.Y'] as $format) {
            try {
                return [Carbon::createFromFormat($format, $value)->format('Y-m-d'), []];
            } catch (Throwable) {
            }
        }

        try {
            return [Carbon::parse($value)->format('Y-m-d'), []];
        } catch (Throwable) {
            return [null, ['Fecha de vencimiento inválida.']];
        }
    }

    /**
     * @return array<int, string>
     */
    private function detectChangedFields(Weapon $weapon, array $normalizedPayload): array
    {
        $changedFields = [];

        foreach (self::IMPORTABLE_FIELDS as $field) {
            $currentValue = $field === 'permit_expires_at'
                ? $weapon->permit_expires_at?->format('Y-m-d')
                : $weapon->{$field};
            $incomingValue = $normalizedPayload[$field] ?? null;

            if (! $this->valuesMatch($currentValue, $incomingValue)) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    private function valuesMatch(mixed $currentValue, mixed $incomingValue): bool
    {
        if ($currentValue === null || $currentValue === '') {
            return $incomingValue === null || $incomingValue === '';
        }

        return trim((string) $currentValue) === trim((string) $incomingValue);
    }

    /**
     * @return array<string, mixed>
     */
    private function weaponSnapshot(Weapon $weapon): array
    {
        return [
            'internal_code' => $this->sanitizeText($weapon->internal_code),
            'weapon_type' => $this->sanitizeText($weapon->weapon_type),
            'brand' => $this->sanitizeText($weapon->brand),
            'serial_number' => $this->sanitizeText($weapon->serial_number),
            'caliber' => $this->sanitizeText($weapon->caliber),
            'capacity' => $this->sanitizeText($weapon->capacity),
            'permit_type' => $this->sanitizeText($weapon->permit_type),
            'permit_number' => $this->sanitizeText($weapon->permit_number),
            'permit_expires_at' => $weapon->permit_expires_at?->format('Y-m-d'),
        ];
    }

    private function sanitizeText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $encoding = mb_detect_encoding($value, ['Windows-1252', 'ISO-8859-1', 'UTF-8'], true) ?: 'Windows-1252';

        return mb_convert_encoding($value, 'UTF-8', $encoding);
    }

    private function nextInternalCodeNumber(): int
    {
        $latestCode = Weapon::query()
            ->lockForUpdate()
            ->where('internal_code', 'like', 'SJ-%')
            ->orderByRaw('CAST(SUBSTRING(internal_code, 4) AS UNSIGNED) DESC')
            ->value('internal_code');

        $lastNumber = $latestCode ? (int) preg_replace('/\D/', '', $latestCode) : 0;

        return $lastNumber + 1;
    }

    private function normalizeHeader(string $value): string
    {
        $value = Str::ascii($value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function normalizeCompareValue(?string $value): string
    {
        return $this->normalizeHeader((string) $value);
    }
}

