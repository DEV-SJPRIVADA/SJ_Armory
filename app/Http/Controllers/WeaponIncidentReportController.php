<?php

namespace App\Http\Controllers;

use App\Models\IncidentType;
use App\Models\WeaponIncident;
use App\Services\WeaponIncidentReportService;
use Illuminate\Http\Request;

class WeaponIncidentReportController extends Controller
{
    public function __construct(private readonly WeaponIncidentReportService $reports)
    {
        $this->middleware(function ($request, $next) {
            $this->authorize('viewAny', WeaponIncident::class);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        return $this->renderPage($request);
    }

    public function show(Request $request, IncidentType $incidentType)
    {
        if (! $incidentType->is_reportable) {
            abort(404);
        }

        return $this->renderPage($request, $incidentType);
    }

    public function searchWeapons(Request $request)
    {
        $results = $this->reports->searchWeapons(
            $request->user(),
            $request->string('q')->toString(),
            (int) $request->input('limit', 8)
        );

        return response()->json([
            'items' => $results->values()->all(),
        ]);
    }

    private function renderPage(Request $request, ?IncidentType $selectedType = null)
    {
        $filters = $this->reports->filtersFromRequest($request->all());
        $dashboard = $this->reports->dashboard($request->user(), $filters, $selectedType);
        $incidents = $this->reports->incidentsForReportTable($request->user(), $filters, $selectedType);
        $types = IncidentType::query()
            ->with(['modalities' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->reportable()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $modalityMap = $types->mapWithKeys(fn (IncidentType $type) => [
            $type->id => $type->modalities->map(fn ($modality) => [
                'id' => $modality->id,
                'name' => $modality->name,
            ])->values()->all(),
        ])->all();

        $selectedWeapon = old('weapon_id')
            ? $this->reports->findSearchWeapon($request->user(), (int) old('weapon_id'))
            : null;
        $years = $this->reports->availableYears($request->user(), $selectedType);
        $statusOptions = WeaponIncident::statusOptions();

        return view('reports.weapon-incidents.index', compact(
            'dashboard',
            'incidents',
            'types',
            'modalityMap',
            'years',
            'selectedWeapon',
            'filters',
            'selectedType',
            'statusOptions',
        ));
    }
}
