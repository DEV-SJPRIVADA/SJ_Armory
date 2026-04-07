<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use App\Models\WeaponImportBatch;
use App\Models\WeaponImportRow;
use App\Services\Imports\ClientImportProcessor;
use App\Services\Imports\Contracts\ImportBatchProcessor;
use App\Services\Imports\WeaponImportProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class WeaponImportService
{
    public function __construct(
        private readonly WeaponImportSpreadsheetReader $reader,
        private readonly WeaponImportProcessor $weaponProcessor,
        private readonly ClientImportProcessor $clientProcessor,
    ) {
    }

    public function createPreviewBatch(
        UploadedFile $uploadedFile,
        User $user,
        string $type = WeaponImportBatch::TYPE_WEAPON
    ): WeaponImportBatch {
        $processor = $this->processorForType($type);
        $storedPath = $uploadedFile->store('weapon-imports/' . $processor->type(), 'local');
        $absolutePath = Storage::disk('local')->path($storedPath);

        try {
            $sheet = $this->reader->read($absolutePath, $uploadedFile->getClientOriginalExtension());
            [$preparedRows, $counts] = $processor->prepareRows($sheet['headers'], $sheet['rows']);
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
            return DB::transaction(function () use ($uploadedFile, $user, $storedPath, $checksum, $preparedRows, $counts, $processor) {
                $this->cleanupDraftBatches($user, $processor->type());

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
                    'type' => $processor->type(),
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

                return $this->freshBatch($batch->id);
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
            return $this->freshBatch($batch->id);
        }

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

            return $this->freshBatch($batch->id);
        });
    }

    public function processBatchChunk(WeaponImportBatch $batch, User $user, int $chunkSize = 25): WeaponImportBatch
    {
        $lock = Cache::lock('weapon-import-batch:' . $batch->id, 15);

        if (! $lock->get()) {
            return $this->freshBatch($batch->id);
        }

        try {
            $batch->refresh();

            if ($batch->isExecuted() || $batch->isFailed()) {
                return $this->freshBatch($batch->id);
            }

            if (! $batch->isProcessing()) {
                $batch = $this->startBatchExecution($batch, $user);
            }

            $processor = $this->processorForBatch($batch);

            $pendingRows = WeaponImportRow::query()
                ->with(['weapon', 'client'])
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

                return $this->freshBatch($batch->id);
            }

            foreach ($pendingRows as $row) {
                try {
                    $processor->executeRow($row, $user);

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

                    return $this->freshBatch($batch->id);
                }
            }

            $batch->refresh();

            if ((int) $batch->processed_rows >= (int) $batch->total_rows && ! $batch->isFailed()) {
                $batch->update([
                    'status' => 'executed',
                    'executed_at' => now(),
                    'finished_at' => now(),
                ]);
            }

            return $this->freshBatch($batch->id);
        } finally {
            optional($lock)->release();
        }
    }

    public function executeBatch(WeaponImportBatch $batch, User $user): WeaponImportBatch
    {
        if (! $batch->isDraft()) {
            throw ValidationException::withMessages([
                'batch' => 'Este lote ya fue ejecutado.',
            ]);
        }

        if ($batch->hasErrors()) {
            throw ValidationException::withMessages([
                'batch' => 'No se puede ejecutar mientras existan filas con error.',
            ]);
        }

        $batch = $this->startBatchExecution($batch, $user);
        $chunkSize = max(25, (int) $batch->total_rows);

        while ($batch->isProcessing()) {
            $batch = $this->processBatchChunk($batch, $user, $chunkSize);
        }

        if ($batch->isFailed()) {
            throw ValidationException::withMessages([
                'batch' => $batch->last_error ?: 'No se pudieron ejecutar los cambios del lote.',
            ]);
        }

        return $batch;
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
            'type' => $batch->type ?: WeaponImportBatch::TYPE_WEAPON,
        ];
    }

    public function discardDraftBatch(WeaponImportBatch $batch): void
    {
        if (! $batch->isDraft()) {
            throw ValidationException::withMessages([
                'batch' => 'Solo puedes cancelar lotes pendientes.',
            ]);
        }

        DB::transaction(function () use ($batch) {
            $batch->loadMissing('file');
            $this->deleteDraftBatch($batch);
        });
    }

    private function cleanupDraftBatches(User $user, string $type): void
    {
        $drafts = WeaponImportBatch::query()
            ->with('file')
            ->where('uploaded_by', $user->id)
            ->where('status', 'draft')
            ->where('type', $type)
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

    private function processorForBatch(WeaponImportBatch $batch): ImportBatchProcessor
    {
        return $this->processorForType($batch->type ?: WeaponImportBatch::TYPE_WEAPON);
    }

    private function processorForType(string $type): ImportBatchProcessor
    {
        return match ($type) {
            WeaponImportBatch::TYPE_WEAPON => $this->weaponProcessor,
            WeaponImportBatch::TYPE_CLIENT => $this->clientProcessor,
            default => throw new RuntimeException('Tipo de importacion no soportado.'),
        };
    }

    private function freshBatch(int $id): WeaponImportBatch
    {
        return WeaponImportBatch::query()
            ->with($this->batchRelations())
            ->findOrFail($id);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function batchRelations(): array
    {
        return [
            'file',
            'uploadedBy',
            'executedBy',
            'rows' => fn ($query) => $query
                ->orderByRaw($this->actionOrderSql())
                ->orderBy('row_number'),
            'rows.weapon',
            'rows.client',
        ];
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


