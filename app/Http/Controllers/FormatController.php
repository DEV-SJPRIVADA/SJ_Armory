<?php

namespace App\Http\Controllers;

use App\Models\Weapon;
use App\Services\Formats\MonthlyWeaponReviewQueryService;
use App\Services\Formats\MonthlyWeaponReviewRowMapper;
use App\Services\Formats\MonthlyWeaponReviewSpreadsheetExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormatController extends Controller
{
    public function __construct(
        private readonly MonthlyWeaponReviewQueryService $queryService,
        private readonly MonthlyWeaponReviewRowMapper $rowMapper,
        private readonly MonthlyWeaponReviewSpreadsheetExporter $spreadsheetExporter,
    ) {
        $this->middleware(function (Request $request, $next) {
            $this->authorize('viewAny', Weapon::class);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        return view('formats.index', [
            'formOptions' => $this->queryService->formOptions(),
            'columnKeys' => $this->queryService->columnKeys(),
        ]);
    }

    public function downloadEmptyMonthlyReview(): StreamedResponse
    {
        return $this->spreadsheetExporter->stream(
            MonthlyWeaponReviewSpreadsheetExporter::downloadFilename(),
        );
    }

    public function monthlyReviewWeapons(Request $request): JsonResponse
    {
        $globalFilters = $this->queryService->globalFiltersFromRequest($request);
        $columnFilters = $this->queryService->columnFiltersFromRequest($request);
        $page = max(1, $request->integer('page', 1));

        $paginator = $this->queryService->paginatedTableRows(
            $request->user(),
            $globalFilters,
            $columnFilters,
            $page,
        );

        return response()->json([
            'rows' => collect($paginator->items())
                ->map(fn (Weapon $weapon) => $this->queryService->tableRow($weapon))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function monthlyReviewColumnOptions(Request $request): JsonResponse
    {
        $target = trim((string) $request->input('target', ''));
        abort_unless(in_array($target, $this->queryService->columnKeys(), true), 422);

        $values = $this->queryService->columnFilterOptions(
            $request->user(),
            $this->queryService->globalFiltersFromRequest($request),
            $this->queryService->columnFiltersFromRequest($request),
            $target,
        );

        return response()->json(['values' => $values]);
    }

    public function previewMonthlyReview(Request $request): JsonResponse
    {
        $weaponIds = $this->validatedWeaponIds($request);
        $weapons = $this->queryService->weaponsByIds($request->user(), $weaponIds);
        $count = $weapons->count();

        return response()->json([
            'count' => $count,
            'pages' => $this->queryService->pageCount($count),
            'rows_per_page' => MonthlyWeaponReviewQueryService::ROWS_PER_PAGE,
        ]);
    }

    public function downloadMonthlyReview(Request $request): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $weaponIds = $this->validatedWeaponIds($request);

        if ($weaponIds === []) {
            return redirect()
                ->route('formatos.index')
                ->with('status', __('Seleccione al menos un arma para generar el Excel.'));
        }

        $weapons = $this->queryService->weaponsByIds($request->user(), $weaponIds);

        if ($weapons->isEmpty()) {
            return redirect()
                ->route('formatos.index')
                ->with('status', __('No se encontraron armas válidas en su selección.'));
        }

        if ($weapons->count() !== count($weaponIds)) {
            return redirect()
                ->route('formatos.index')
                ->with('status', __('Parte de la selección ya no está disponible. Actualice la tabla e intente de nuevo.'));
        }

        $pages = $this->spreadsheetExporter->pagesFromWeapons($weapons, $this->rowMapper);

        return $this->spreadsheetExporter->stream(
            MonthlyWeaponReviewSpreadsheetExporter::downloadFilename(),
            $pages,
        );
    }

    /**
     * @return list<int>
     */
    private function validatedWeaponIds(Request $request): array
    {
        $request->validate([
            'weapon_ids' => ['required', 'array', 'min:1'],
            'weapon_ids.*' => ['integer', 'min:1'],
        ]);

        return collect($request->input('weapon_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
