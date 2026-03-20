<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\File;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponImportBatch;
use App\Models\WeaponImportRow;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class WeaponImportService
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
        'permit_type' => 'Tipo permiso',
        'permit_number' => 'No. permiso',
        'permit_expires_at' => 'Fecha vencimiento',
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

    public function __construct(
        private readonly WeaponImportSpreadsheetReader $reader,
        private readonly WeaponDocumentService $documentService,
    ) {
    }

    public function createPreviewBatch(UploadedFile $uploadedFile, User $user): WeaponImportBatch
    {
        $storedPath = $uploadedFile->store('weapon-imports', 'local');
        $absolutePath = Storage::disk('local')->path($storedPath);

        try {
            $sheet = $this->reader->read($absolutePath, $uploadedFile->getClientOriginalExtension());
            [$preparedRows, $counts] = $this->prepareRows($sheet['headers'], $sheet['rows']);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }

        if ($preparedRows === []) {
            Storage::disk('local')->delete($storedPath);
            throw ValidationException::withMessages([
                'document' => 'El archivo no contiene filas de datos para importar.',
            ]);
        }

        $checksum = hash_file('sha256', $absolutePath) ?: null;

        try {
            return DB::transaction(function () use ($uploadedFile, $user, $storedPath, $checksum, $preparedRows, $counts) {
                $this->cleanupDraftBatches($user);

                $file = File::create([
                    'disk' => 'local',
                    'path' => $storedPath,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getClientMimeType() ?: 'application/octet-stream',
                    'size' => $uploadedFile->getSize(),
                    'checksum' => $checksum,
                    'uploaded_by' => $user->id,
                ]);

                $batch = WeaponImportBatch::create([
                    'file_id' => $file->id,
                    'uploaded_by' => $user->id,
                    'status' => 'draft',
                    'source_name' => $uploadedFile->getClientOriginalName(),
                    'total_rows' => count($preparedRows),
                    'create_count' => $counts[WeaponImportRow::ACTION_CREATE] ?? 0,
                    'update_count' => $counts[WeaponImportRow::ACTION_UPDATE] ?? 0,
                    'no_change_count' => $counts[WeaponImportRow::ACTION_NO_CHANGE] ?? 0,
                    'error_count' => $counts[WeaponImportRow::ACTION_ERROR] ?? 0,
                ]);

                foreach ($preparedRows as $row) {
                    $batch->rows()->create($row);
                }

                return $batch->fresh([
                    'file',
                    'uploadedBy',
                    'rows' => fn ($query) => $query->orderByRaw($this->actionOrderSql())->orderBy('row_number'),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }
    }

    public function startBatchExecution(WeaponImportBatch $batch, User $user): WeaponImportBatch
    {
        if ($batch->isExecuted()) {
            throw ValidationException::withMessages([
                'batch' => 'Este lote ya fue ejecutado.',
            ]);
        }

        if ($batch->isFailed()) {
            throw ValidationException::withMessages([
                'batch' => 'Este lote fallo y no puede reanudarse.',
            ]);
        }

        if ($batch->hasErrors()) {
            throw ValidationException::withMessages([
                'batch' => 'No se puede ejecutar mientras existan filas con error.',
            ]);
        }

        if ($batch->isProcessing()) {
            return $batch->fresh(['rows']);
        }

        $batch->loadMissing('rows');

        return DB::transaction(function () use ($batch, $user) {
            $batch->rows()
                ->where('action', '!=', WeaponImportRow::ACTION_ERROR)
                ->update([
                    'execution_status' => WeaponImportRow::EXECUTION_PENDING,
                    'processed_at' => null,
                    'execution_error' => null,
                    'after_payload' => null,
                ]);

            $batch->update([
                'status' => 'processing',
                'executed_by' => $user->id,
                'executed_at' => null,
                'started_at' => now(),
                'finished_at' => null,
                'processed_rows' => 0,
                'successful_rows' => 0,
                'failed_rows' => 0,
                'last_error' => null,
            ]);

            return $batch->fresh([
                'file',
                'uploadedBy',
                'executedBy',
                'rows' => fn ($query) => $query->orderByRaw($this->actionOrderSql())->orderBy('row_number'),
            ]);
        });
    }

    public function processBatchChunk(WeaponImportBatch $batch, User $user, int $chunkSize = 25): WeaponImportBatch
    {
        $lock = Cache::lock('weapon-import-batch:' . $batch->id, 15);

        if (!$lock->get()) {
            return $batch->fresh([
                'file',
                'uploadedBy',
                'executedBy',
                'rows' => fn ($query) => $query->orderByRaw($this->actionOrderSql())->orderBy('row_number'),
            ]);
        }

        try {
            $batch->refresh();

            if ($batch->isExecuted() || $batch->isFailed()) {
                return $batch->fresh([
                    'file',
                    'uploadedBy',
                    'executedBy',
                    'rows' => fn ($query) => $query->orderByRaw($this->actionOrderSql())->orderBy('row_number'),
                ]);
            }

            if (!$batch->isProcessing()) {
                $batch = $this->startBatchExecution($batch, $user);
            }

            $pendingRows = WeaponImportRow::query()
                ->with('weapon')
                ->where('batch_id', $batch->id)
                ->where('execution_status', WeaponImportRow::EXECUTION_PENDING)
                ->orderBy('row_number')
                ->limit($chunkSize)
                ->get();

            if ($pendingRows->isEmpty()) {
                $batch->update([
                    'status' => 'executed',
                    'executed_at' => now(),
                    'finished_at' => now(),
                ]);

                return $batch->fresh([
                    'file',
                    'uploadedBy',
                    'executedBy',
                    'rows' => fn ($query) => $query->orderByRaw($this->actionOrderSql())->orderBy('row_number'),
                ]);
            }

            foreach ($pendingRows as $row) {
                try {
                    $this->processExecutionRow($row, $user);

                    $batch->increment('processed_rows');
                    $batch->increment('successful_rows');
                } catch (Throwable $exception) {
                    $message = $exception instanceof ValidationException
                        ? collect($exception->errors())->flatten()->implode(' ')
                        : 'No se pudo procesar una fila del lote.';

                    WeaponImportRow::query()
                        ->whereKey($row->id)
                        ->update([
                            'execution_status' => WeaponImportRow::EXECUTION_FAILED,
                            'processed_at' => now(),
                            'execution_error' => $message,
                        ]);

                    $batch->increment('processed_rows');
                    $batch->increment('failed_rows');

                    $batch->update([
                        'status' => 'failed',
                        'finished_at' => now(),
                        'last_error' => $message,
                    ]);

                    return $batch->fresh([
                        'file',
                        'uploadedBy',
                        'executedBy',
                        'rows' => fn ($query) => $query->orderByRaw($this->actionOrderSql())->orderBy('row_number'),
                    ]);
                }
            }

            $batch->refresh();

            if ((int) $batch->processed_rows >= (int) $batch->total_rows && !$batch->isFailed()) {
                $batch->update([
                    'status' => 'executed',
                    'executed_at' => now(),
                    'finished_at' => now(),
                ]);
            }

            return $batch->fresh([
                'file',
                'uploadedBy',
                'executedBy',
                'rows' => fn ($query) => $query->orderByRaw($this->actionOrderSql())->orderBy('row_number'),
            ]);
        } finally {
            optional($lock)->release();
        }
    }

    public function executeBatch(WeaponImportBatch $batch, User $user): WeaponImportBatch
    {
        if (!$batch->isDraft()) {
            throw ValidationException::withMessages([
                'batch' => 'Este lote ya fue ejecutado.',
            ]);
        }

        if ($batch->hasErrors()) {
            throw ValidationException::withMessages([
                'batch' => 'No se puede ejecutar mientras existan filas con error.',
            ]);
        }

        return DB::transaction(function () use ($batch, $user) {
            $batch->update([
                'started_at' => now(),
                'finished_at' => null,
                'processed_rows' => 0,
                'successful_rows' => 0,
                'failed_rows' => 0,
                'last_error' => null,
            ]);

            $batch->load([
                'rows' => fn ($query) => $query->orderBy('row_number'),
                'rows.weapon',
            ]);

            $nextInternalCode = $this->nextInternalCodeNumber();

            foreach ($batch->rows as $row) {
                if ($row->action === WeaponImportRow::ACTION_ERROR) {
                    continue;
                }

                if ($row->action === WeaponImportRow::ACTION_NO_CHANGE) {
                    $weapon = $row->weapon ?: Weapon::query()
                        ->where('serial_number', $row->normalized_payload['serial_number'] ?? null)
                        ->first();

                    $row->update([
                        'weapon_id' => $weapon?->id,
                        'after_payload' => $weapon ? $this->weaponSnapshot($weapon) : null,
                    ]);

                    $batch->processed_rows++;
                    $batch->successful_rows++;

                    continue;
                }

                $payload = $row->normalized_payload ?? [];

                if ($row->action === WeaponImportRow::ACTION_CREATE) {
                    $payload['internal_code'] = sprintf('SJ-%04d', $nextInternalCode++);
                    $payload['ownership_type'] = 'company_owned';

                    $weapon = Weapon::create($payload);

                    $this->documentService->syncPermitDocument($weapon);
                    $this->documentService->syncRenewalDocument($weapon);

                    $after = $this->weaponSnapshot($weapon->fresh());

                    $row->update([
                        'weapon_id' => $weapon->id,
                        'after_payload' => $after,
                    ]);

                    AuditLog::create([
                        'user_id' => $user->id,
                        'action' => 'weapon_import_created',
                        'auditable_type' => Weapon::class,
                        'auditable_id' => $weapon->id,
                        'before' => null,
                        'after' => $after,
                    ]);

                    $batch->processed_rows++;
                    $batch->successful_rows++;

                    continue;
                }

                $weapon = $row->weapon ?: Weapon::query()
                    ->where('serial_number', $payload['serial_number'] ?? null)
                    ->first();

                if (!$weapon) {
                    throw new RuntimeException(sprintf(
                        'No se encontro el arma de la fila %d al momento de ejecutar el lote.',
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
                ]);

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'weapon_import_updated',
                    'auditable_type' => Weapon::class,
                    'auditable_id' => $weapon->id,
                    'before' => $before,
                    'after' => $after,
                ]);

                $batch->processed_rows++;
                $batch->successful_rows++;
            }

            $batch->update([
                'status' => 'executed',
                'executed_by' => $user->id,
                'executed_at' => now(),
                'finished_at' => now(),
                'processed_rows' => $batch->processed_rows ?: $batch->total_rows,
                'successful_rows' => $batch->successful_rows ?: $batch->total_rows,
            ]);

            return $batch->fresh([
                'file',
                'uploadedBy',
                'executedBy',
                'rows' => fn ($query) => $query->orderByRaw($this->actionOrderSql())->orderBy('row_number'),
            ]);
        });
    }

    public function progressData(WeaponImportBatch $batch): array
    {
        $batch->refresh();

        $elapsedSeconds = $batch->started_at ? max(1, now()->diffInSeconds($batch->started_at)) : 0;
        $processedRows = (int) $batch->processed_rows;
        $totalRows = max(0, (int) $batch->total_rows);
        $remainingRows = max(0, $totalRows - $processedRows);
        $etaSeconds = null;

        if ($batch->isProcessing() && $processedRows > 0 && $remainingRows > 0) {
            $etaSeconds = (int) ceil(($elapsedSeconds / $processedRows) * $remainingRows);
        }

        return [
            'status' => $batch->status,
            'processed_rows' => $processedRows,
            'successful_rows' => (int) $batch->successful_rows,
            'failed_rows' => (int) $batch->failed_rows,
            'total_rows' => $totalRows,
            'remaining_rows' => $remainingRows,
            'percentage' => $batch->progressPercentage(),
            'elapsed_seconds' => $batch->started_at ? $elapsedSeconds : 0,
            'eta_seconds' => $etaSeconds,
            'last_error' => $batch->last_error,
            'source_name' => $batch->source_name,
        ];
    }

    private function processExecutionRow(WeaponImportRow $row, User $user): void
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

            if (!$weapon) {
                throw new RuntimeException(sprintf(
                    'No se encontro el arma de la fila %d al momento de ejecutar el lote.',
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
     * @param  array<int, array{row_number:int, cells: array<int, string>}>  $rows
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, int>}
     */
    private function prepareRows(array $headers, array $rows): array
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
                $errors[] = 'La serie esta repetida dentro del archivo.';
            }

            $weapon = $normalizedSerial !== '' ? $existingWeapons->get($normalizedSerial) : null;
            $beforePayload = $weapon ? $this->weaponSnapshot($weapon) : null;

            if ($errors !== []) {
                $action = WeaponImportRow::ACTION_ERROR;
                $summary = implode(' ', array_unique($errors));
            } elseif (!$weapon) {
                $action = WeaponImportRow::ACTION_CREATE;
                $summary = 'Serie nueva. Se creara el arma.';
            } else {
                $changedFields = $this->detectChangedFields($weapon, $normalizedPayload);
                if ($changedFields === []) {
                    $action = WeaponImportRow::ACTION_NO_CHANGE;
                    $summary = 'La informacion ya coincide con el sistema.';
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
            if (!$field || isset($columnMap[$field])) {
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

        if (!$mapped) {
            return [null, ['Tipo de arma no valido.']];
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

        if (!$mapped) {
            return [null, ['Tipo de permiso no valido.']];
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
            return [null, ['Fecha de vencimiento invalida.']];
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

            if (!$this->valuesMatch($currentValue, $incomingValue)) {
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
            'internal_code' => $weapon->internal_code,
            'weapon_type' => $weapon->weapon_type,
            'brand' => $weapon->brand,
            'serial_number' => $weapon->serial_number,
            'caliber' => $weapon->caliber,
            'capacity' => $weapon->capacity,
            'permit_type' => $weapon->permit_type,
            'permit_number' => $weapon->permit_number,
            'permit_expires_at' => $weapon->permit_expires_at?->format('Y-m-d'),
        ];
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

    public function discardDraftBatch(WeaponImportBatch $batch): void
    {
        if (!$batch->isDraft()) {
            throw ValidationException::withMessages([
                'batch' => 'Solo puedes cancelar lotes pendientes.',
            ]);
        }

        DB::transaction(function () use ($batch) {
            $batch->loadMissing('file');
            $this->deleteDraftBatch($batch);
        });
    }

    private function cleanupDraftBatches(User $user): void
    {
        $drafts = WeaponImportBatch::query()
            ->with('file')
            ->where('uploaded_by', $user->id)
            ->where('status', 'draft')
            ->get();

        foreach ($drafts as $draft) {
            $this->deleteDraftBatch($draft);
        }
    }

    private function deleteDraftBatch(WeaponImportBatch $batch): void
    {
        $file = $batch->file;

        $batch->delete();

        if ($file) {
            Storage::disk($file->disk)->delete($file->path);
            $file->delete();
        }
    }

    private function normalizeCompareValue(?string $value): string
    {
        return $this->normalizeHeader((string) $value);
    }

    private function actionOrderSql(): string
    {
        return sprintf(
            "CASE action WHEN '%s' THEN 0 WHEN '%s' THEN 1 WHEN '%s' THEN 2 WHEN '%s' THEN 3 ELSE 4 END",
            WeaponImportRow::ACTION_ERROR,
            WeaponImportRow::ACTION_CREATE,
            WeaponImportRow::ACTION_UPDATE,
            WeaponImportRow::ACTION_NO_CHANGE,
        );
    }
}

