<?php

namespace App\Http\Controllers;

use App\Models\WeaponIncident;
use App\Services\WeaponCustodyReportService;
use App\Support\PostCustodyRole;

class WeaponCustodyReportController extends Controller
{
    public function __construct(private readonly WeaponCustodyReportService $reports)
    {
        $this->middleware(function ($request, $next) {
            $this->authorize('viewAny', WeaponIncident::class);

            return $next($request);
        });
    }

    public function index()
    {
        $dashboard = $this->reports->dashboard(request()->user());

        return view('reports.weapon-custody.index', [
            'rows' => $dashboard['rows'],
            'counts' => $dashboard['counts'],
            'roleLabels' => [
                PostCustodyRole::ARMERILLO => PostCustodyRole::label(PostCustodyRole::ARMERILLO),
                PostCustodyRole::ARMERILLO_PARA_MANTENIMIENTO => PostCustodyRole::label(PostCustodyRole::ARMERILLO_PARA_MANTENIMIENTO),
                PostCustodyRole::ARMERO => PostCustodyRole::label(PostCustodyRole::ARMERO),
            ],
        ]);
    }
}
