<?php

namespace App\Http\Controllers;

use App\Models\WeaponImportBatch;
use App\Services\WeaponImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class WeaponImportController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless($request->user()?->isAdmin(), 403);

            return $next($request);
        });
    }

    public function index()
    {
        $batches = WeaponImportBatch::query()
            ->with(['file', 'uploadedBy', 'executedBy'])
            ->where('status', 'executed')
            ->latest()
            ->get();

        return view('weapon-imports.center', [
            'batches' => $batches,
        ]);
    }

    public function show(Request $request, WeaponImportBatch $weaponImportBatch)
    {
        $selectedBatch = $this->loadBatch($weaponImportBatch->id);

        return view('weapon-imports.batch', [
            'selectedBatch' => $selectedBatch,
            'openPreview' => $request->boolean('preview') && ($selectedBatch->isDraft() || $selectedBatch->isProcessing()),
        ]);
    }

    public function preview(Request $request, WeaponImportService $importService)
    {
        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
            'type' => ['nullable', 'string'],
        ]);

        $type = $this->resolveImportType($data['type'] ?? null);

        try {
            $batch = $importService->createPreviewBatch($data['document'], $request->user(), $type);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Import preview failed.', [
                'user_id' => $request->user()?->id,
                'file_name' => $data['document']->getClientOriginalName(),
                'exception' => $exception,
            ]);

            throw ValidationException::withMessages([
                'document' => 'No se pudo procesar el archivo seleccionado.',
            ]);
        }

        $redirectUrl = route('weapon-imports.show', [
            'weaponImportBatch' => $batch->id,
            'preview' => 1,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect_url' => $redirectUrl,
                'batch_id' => $batch->id,
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    public function startExecution(Request $request, WeaponImportBatch $weaponImportBatch, WeaponImportService $importService)
    {
        try {
            $batch = $importService->startBatchExecution($weaponImportBatch, $request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'batch' => 'No se pudo iniciar la ejecución del lote.',
            ]);
        }

        return response()->json([
            'progress' => $importService->progressData($batch),
            'status_url' => route('weapon-imports.status', $batch),
            'process_url' => route('weapon-imports.process', $batch),
            'redirect_url' => route('weapon-imports.show', $batch),
        ]);
    }

    public function processExecution(Request $request, WeaponImportBatch $weaponImportBatch, WeaponImportService $importService)
    {
        try {
            $batch = $importService->processBatchChunk($weaponImportBatch, $request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'batch' => 'No se pudo continuar la ejecución del lote.',
            ]);
        }

        return response()->json([
            'progress' => $importService->progressData($batch),
            'redirect_url' => route('weapon-imports.show', $batch),
        ]);
    }

    public function executionStatus(Request $request, WeaponImportBatch $weaponImportBatch, WeaponImportService $importService)
    {
        abort_unless(
            $weaponImportBatch->uploaded_by === null
            || $weaponImportBatch->uploaded_by === $request->user()?->id
            || $request->user()?->isAdmin(),
            403
        );

        $batch = $this->loadBatch($weaponImportBatch->id);

        return response()->json([
            'progress' => $importService->progressData($batch),
            'redirect_url' => route('weapon-imports.show', $batch),
        ]);
    }

    public function execute(Request $request, WeaponImportBatch $weaponImportBatch, WeaponImportService $importService)
    {
        try {
            $batch = $importService->executeBatch($weaponImportBatch, $request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'batch' => 'No se pudieron ejecutar los cambios del lote.',
            ]);
        }

        return redirect()
            ->route('weapon-imports.show', $batch)
            ->with('status', 'Carga ejecutada correctamente.');
    }

    public function discard(Request $request, WeaponImportBatch $weaponImportBatch, WeaponImportService $importService)
    {
        try {
            $importService->discardDraftBatch($weaponImportBatch);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'batch' => 'No se pudo cancelar la carga pendiente.',
            ]);
        }

        return redirect()
            ->route('weapon-imports.index')
            ->with('status', 'Carga cancelada. Puedes subir un nuevo documento.');
    }

    private function loadBatch(int $id): WeaponImportBatch
    {
        return WeaponImportBatch::query()
            ->with([
                'file',
                'uploadedBy',
                'executedBy',
                'rows' => fn ($query) => $query
                    ->orderByRaw("CASE action WHEN 'error' THEN 0 WHEN 'create' THEN 1 WHEN 'update' THEN 2 WHEN 'no_change' THEN 3 ELSE 4 END")
                    ->orderBy('row_number'),
                'rows.weapon',
                'rows.client',
            ])
            ->findOrFail($id);
    }

    private function resolveImportType(?string $type): string
    {
        return in_array($type, [WeaponImportBatch::TYPE_CLIENT, WeaponImportBatch::TYPE_WEAPON], true)
            ? $type
            : WeaponImportBatch::TYPE_WEAPON;
    }
}



