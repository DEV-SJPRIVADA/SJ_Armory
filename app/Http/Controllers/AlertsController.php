<?php

namespace App\Http\Controllers;

use App\Models\Weapon;
use App\Models\WeaponDocument;
use App\Services\WeaponDocumentService;
use App\Support\AlertDocumentPeriod;
use Illuminate\Http\Request;
use RuntimeException;

class AlertsController extends Controller
{
    public function documents(Request $request, WeaponDocumentService $documentService)
    {
        $this->authorizeAdmin();

        $selectedMonths = AlertDocumentPeriod::normalizeFromRequest($request);
        $hasMonthFilter = $selectedMonths !== [];
        $monthLabel = AlertDocumentPeriod::label($selectedMonths);

        $today = now()->startOfDay();
        $alertWindowEnd = $today->copy()->addDays(120)->endOfDay();

        $documentsQuery = WeaponDocument::with([
            'weapon.activeClientAssignment.client',
            'weapon.operationalBlockingIncidents',
            'file',
        ])
            ->where('is_renewal', true)
            ->whereNotNull('valid_until');

        AlertDocumentPeriod::applyMonthFilter($documentsQuery, $selectedMonths);

        $documents = $documentsQuery
            ->orderBy('valid_until')
            ->orderBy('weapon_id')
            ->get();

        $expired = $documents
            ->filter(fn (WeaponDocument $document) => $document->valid_until?->copy()->startOfDay()->lte($today))
            ->values();

        $expiring = $documents
            ->filter(fn (WeaponDocument $document) => $document->valid_until?->copy()->startOfDay()->gt($today)
                && $document->valid_until?->copy()->endOfDay()->lte($alertWindowEnd))
            ->values();

        $noAlerts = $documents
            ->filter(fn (WeaponDocument $document) => $document->valid_until?->copy()->endOfDay()->gt($alertWindowEnd))
            ->values();

        $summaryCards = [
            'expired' => [
                'count' => $expired->pluck('weapon_id')->filter()->unique()->count(),
                'label' => 'Documentos vencidos',
                'subtitle' => $hasMonthFilter
                    ? 'Armas vencidas en ' . $monthLabel
                    : 'Armas vencidas registradas en el sistema',
                'empty' => $hasMonthFilter
                    ? 'No hay armas vencidas para los meses seleccionados.'
                    : 'No hay armas vencidas registradas.',
            ],
            'expiring' => [
                'count' => $expiring->pluck('weapon_id')->filter()->unique()->count(),
                'label' => 'Documentos por vencer',
                'subtitle' => $hasMonthFilter
                    ? 'Alertas activas dentro de 120 días en ' . $monthLabel
                    : 'Alertas activas dentro de 120 días en el sistema',
                'empty' => $hasMonthFilter
                    ? 'No hay armas por vencer dentro de la ventana de 120 días para los meses seleccionados.'
                    : 'No hay armas por vencer dentro de la ventana de 120 días.',
            ],
            'no_alerts' => [
                'count' => $noAlerts->pluck('weapon_id')->filter()->unique()->count(),
                'label' => 'Armas sin alertas',
                'subtitle' => $hasMonthFilter
                    ? 'Armas de los meses seleccionados fuera de la ventana de alerta'
                    : 'Armas del sistema fuera de la ventana de alerta',
                'empty' => $hasMonthFilter
                    ? 'No hay armas fuera de alerta para los meses seleccionados.'
                    : 'No hay armas fuera de alerta.',
            ],
        ];

        $previewAvailable = $documentService->hasPdfPreviewSupport();

        return view('alerts.documents', compact(
            'expired',
            'expiring',
            'noAlerts',
            'selectedMonths',
            'monthLabel',
            'summaryCards',
            'hasMonthFilter',
            'previewAvailable',
        ));
    }

    public function downloadBatch(Request $request, WeaponDocumentService $documentService)
    {
        $this->authorizeAdmin();

        $weapons = $this->selectedWeapons($request);
        $downloadBaseName = $this->resolveDownloadBaseName($request);

        $batch = $documentService->buildBatchDocument($weapons, $downloadBaseName);

        return response()->download($batch['path'], $batch['file_name'])->deleteFileAfterSend(true);
    }

    public function previewBatch(Request $request, WeaponDocumentService $documentService)
    {
        $this->authorizeAdmin();

        $weapons = $this->selectedWeapons($request);
        $downloadBaseName = $this->resolveDownloadBaseName($request);

        try {
            $preview = $documentService->buildBatchPreviewPdf($weapons, $downloadBaseName);
        } catch (RuntimeException $exception) {
            abort(503, $exception->getMessage());
        }

        return response()->file($preview['path'], [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $preview['file_name'] . '"',
        ])->deleteFileAfterSend(true);
    }

    private function selectedWeapons(Request $request)
    {
        $data = $request->validate([
            'weapon_ids' => ['required', 'array', 'min:1'],
            'weapon_ids.*' => ['integer', 'distinct', 'exists:weapons,id'],
        ]);

        $weapons = Weapon::with([
            'photos.file',
            'permitFile',
            'documents.file',
        ])
            ->whereIn('id', $data['weapon_ids'])
            ->orderBy('internal_code')
            ->get();

        abort_if($weapons->isEmpty(), 422, 'Debe seleccionar al menos un arma.');

        return $weapons;
    }

    private function resolveDownloadBaseName(Request $request): string
    {
        $months = AlertDocumentPeriod::normalizeFromRequest($request);

        return AlertDocumentPeriod::downloadBaseName($months);
    }

    private function authorizeAdmin(): void
    {
        if (!request()->user()?->isAdmin() && !request()->user()?->isAuditor()) {
            abort(403);
        }
    }
}
