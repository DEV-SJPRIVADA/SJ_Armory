<?php

namespace App\Http\Controllers;

use App\Models\Weapon;
use App\Models\WeaponDocument;
use App\Services\WeaponDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AlertsController extends Controller
{
    public function documents(Request $request)
    {
        $this->authorizeAdmin();

        $selectedMonth = trim((string) $request->input('month', ''));
        $hasMonthFilter = preg_match('/^\d{4}-\d{2}$/', $selectedMonth) === 1;

        $today = now()->startOfDay();
        $alertWindowEnd = $today->copy()->addDays(120)->endOfDay();

        $documentsQuery = WeaponDocument::with(['weapon.activeClientAssignment.client', 'file'])
            ->where('is_renewal', true)
            ->whereNotNull('valid_until');

        if ($hasMonthFilter) {
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $documentsQuery->whereBetween('valid_until', [$monthStart, $monthEnd]);
            $monthLabel = $monthStart->translatedFormat('F \d\e Y');
        } else {
            $selectedMonth = '';
            $monthLabel = 'Todos los meses';
        }

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
                    ? 'No hay armas vencidas para este mes.'
                    : 'No hay armas vencidas registradas.',
            ],
            'expiring' => [
                'count' => $expiring->pluck('weapon_id')->filter()->unique()->count(),
                'label' => 'Documentos por vencer',
                'subtitle' => $hasMonthFilter
                    ? 'Alertas activas dentro de 120 días en ' . $monthLabel
                    : 'Alertas activas dentro de 120 días en el sistema',
                'empty' => $hasMonthFilter
                    ? 'No hay armas por vencer dentro de la ventana de 120 días para este mes.'
                    : 'No hay armas por vencer dentro de la ventana de 120 días.',
            ],
            'no_alerts' => [
                'count' => $noAlerts->pluck('weapon_id')->filter()->unique()->count(),
                'label' => 'Armas sin alertas',
                'subtitle' => $hasMonthFilter
                    ? 'Armas del mes fuera de la ventana de alerta'
                    : 'Armas del sistema fuera de la ventana de alerta',
                'empty' => $hasMonthFilter
                    ? 'No hay armas fuera de alerta para este mes.'
                    : 'No hay armas fuera de alerta.',
            ],
        ];

        return view('alerts.documents', compact(
            'expired',
            'expiring',
            'noAlerts',
            'selectedMonth',
            'monthLabel',
            'summaryCards',
            'hasMonthFilter',
        ));
    }

    public function downloadBatch(Request $request, WeaponDocumentService $documentService)
    {
        $this->authorizeAdmin();

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

        $batch = $documentService->buildBatchDocument($weapons);

        return response()->download($batch['path'], $batch['file_name'])->deleteFileAfterSend(true);
    }

    private function authorizeAdmin(): void
    {
        if (!request()->user()?->isAdmin() && !request()->user()?->isAuditor()) {
            abort(403);
        }
    }
}
