<?php

namespace App\Services\Formats;

use App\Models\User;
use App\Models\Weapon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MonthlyWeaponReviewQueryService
{
    public const ROWS_PER_PAGE = 20;

    public const TABLE_PAGE_SIZE = 50;

    /**
     * @return list<string>
     */
    public function columnKeys(): array
    {
        return ['cliente', 'puesto', 'responsable', 'serie'];
    }

    /**
     * @return array{
     *     inventory_scope: string,
     *     q: ?string,
     *     permit_expires_from: ?string,
     *     permit_expires_to: ?string,
     *     destination: ?string,
     * }
     */
    public function globalFiltersFromRequest(Request $request): array
    {
        $inventoryScope = trim((string) $request->input('inventory_scope', 'operational')) ?: 'operational';
        if (! in_array($inventoryScope, ['operational', 'all', 'non_operational'], true)) {
            $inventoryScope = 'operational';
        }

        return [
            'inventory_scope' => $inventoryScope,
            'q' => trim((string) $request->input('q', '')) ?: null,
            'permit_expires_from' => trim((string) $request->input('permit_expires_from', '')) ?: null,
            'permit_expires_to' => trim((string) $request->input('permit_expires_to', '')) ?: null,
            'destination' => trim((string) $request->input('destination', '')) ?: null,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function columnFiltersFromRequest(Request $request): array
    {
        $raw = $request->input('col', []);
        if (! is_array($raw)) {
            $raw = [];
        }

        $normalized = [];
        foreach ($this->columnKeys() as $key) {
            $values = $raw[$key] ?? [];
            if (! is_array($values)) {
                $values = [$values];
            }

            $normalized[$key] = collect($values)
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn (string $value) => $value !== '')
                ->unique()
                ->values()
                ->all();
        }

        return $normalized;
    }

    /**
     * @param  array<string, list<string>>  $columnFilters
     */
    public function paginatedTableRows(User $user, array $globalFilters, array $columnFilters, int $page = 1): LengthAwarePaginator
    {
        $query = $this->buildQuery($user, $globalFilters);
        $this->applyColumnFilters($query, $columnFilters, $user, $globalFilters);

        return $query->paginate(self::TABLE_PAGE_SIZE, ['*'], 'page', max(1, $page));
    }

    /**
     * @param  array<string, list<string>>  $columnFilters
     * @return list<string>
     */
    public function columnFilterOptions(User $user, array $globalFilters, array $columnFilters, string $target): array
    {
        if (! in_array($target, $this->columnKeys(), true)) {
            return [];
        }

        $scopedFilters = $columnFilters;
        $scopedFilters[$target] = [];

        $query = $this->buildQuery($user, $globalFilters);
        $this->applyColumnFilters($query, $scopedFilters, $user, $globalFilters);

        return $query
            ->get()
            ->map(fn (Weapon $weapon) => $this->columnValues($weapon)[$target] ?? '')
            ->filter(fn (string $value) => $value !== '')
            ->unique()
            ->sort(fn (string $a, string $b) => strcasecmp($a, $b))
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $weaponIds
     * @return Collection<int, Weapon>
     */
    public function weaponsByIds(User $user, array $weaponIds): Collection
    {
        $weaponIds = collect($weaponIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($weaponIds === []) {
            return collect();
        }

        $query = Weapon::query()->whereIn('weapons.id', $weaponIds);
        $this->applyRoleScope($query, $user);

        $weapons = $query
            ->with([
                'activeClientAssignment.client',
                'activeClientAssignment.responsible',
                'activePendingTransfer.fromClient',
                'activePendingTransfer.fromUser',
                'activePostAssignment.post',
                'activeWorkerAssignment.worker',
            ])
            ->get()
            ->keyBy('id');

        return collect($weaponIds)
            ->map(fn (int $id) => $weapons->get($id))
            ->filter()
            ->values();
    }

    public function pageCount(int $weaponCount): int
    {
        if ($weaponCount <= 0) {
            return 0;
        }

        return (int) ceil($weaponCount / self::ROWS_PER_PAGE);
    }

    /**
     * @return array{
     *     destinations: array<string, string>,
     *     inventory_scopes: array<string, string>,
     * }
     */
    public function formOptions(): array
    {
        return [
            'destinations' => [
                '' => 'Todos los destinos',
                'with_destination' => 'Con destino',
                'without_destination' => 'Sin destino',
                'post' => 'Asignadas a puesto',
                'worker' => 'Asignadas a trabajador',
            ],
            'inventory_scopes' => [
                'operational' => 'Inventario operativo',
                'all' => 'Todas las armas',
                'non_operational' => 'No operativas',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnValues(Weapon $weapon): array
    {
        return [
            'cliente' => (string) ($weapon->operationalDisplayClient()?->name ?? __('Sin destino')),
            'puesto' => (string) ($weapon->activePostAssignment?->post?->name ?? '-'),
            'responsable' => (string) ($weapon->operationalDisplayResponsible()?->name ?? '-'),
            'serie' => (string) $weapon->serial_number,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function tableRow(Weapon $weapon): array
    {
        return array_merge(
            ['id' => $weapon->id],
            $this->columnValues($weapon),
        );
    }

    public function buildQuery(User $user, array $globalFilters): Builder
    {
        $query = Weapon::query();

        $this->applyInventoryScope($query, $globalFilters['inventory_scope']);
        $this->applyRoleScope($query, $user);
        $this->applySearch($query, (string) ($globalFilters['q'] ?? ''));
        $this->applyGlobalFilters($query, $globalFilters);

        return $query
            ->with([
                'activeClientAssignment.client',
                'activeClientAssignment.responsible',
                'activePendingTransfer.fromClient',
                'activePendingTransfer.fromUser',
                'activePostAssignment.post',
                'activeWorkerAssignment.worker',
            ])
            ->orderBy('weapons.serial_number');
    }

    /**
     * @param  array<string, list<string>>  $columnFilters
     */
    private function applyColumnFilters(Builder $query, array $columnFilters, User $user, array $globalFilters): void
    {
        $activeFilters = array_filter($columnFilters, fn (array $values) => $values !== []);
        if ($activeFilters === []) {
            return;
        }

        $matchingIds = $this->buildQuery($user, $globalFilters)
            ->get()
            ->filter(function (Weapon $weapon) use ($activeFilters) {
                $values = $this->columnValues($weapon);
                foreach ($activeFilters as $key => $selected) {
                    if (! in_array((string) ($values[$key] ?? ''), $selected, true)) {
                        return false;
                    }
                }

                return true;
            })
            ->pluck('id')
            ->all();

        if ($matchingIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('weapons.id', $matchingIds);
    }

    private function applyInventoryScope(Builder $query, string $scope): void
    {
        switch ($scope) {
            case 'all':
                return;
            case 'non_operational':
                $query->outsideInventory();

                return;
            case 'operational':
            default:
                $query->inInventory();

                return;
        }
    }

    private function applyRoleScope(Builder $query, User $user): void
    {
        if ($user->isResponsible() && ! $user->isAdmin()) {
            $query->where(function (Builder $outer) use ($user) {
                $outer
                    ->whereHas('clientAssignments', function (Builder $assignmentQuery) use ($user) {
                        $assignmentQuery
                            ->where('responsible_user_id', $user->id)
                            ->where('is_active', true);
                    })
                    ->orWhereHas('activePendingTransfer', function (Builder $transferQuery) use ($user) {
                        $transferQuery->where(function (Builder $inner) use ($user) {
                            $inner
                                ->where('from_user_id', $user->id)
                                ->orWhere('to_user_id', $user->id);
                        });
                    });
            });
        }
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search) {
            $builder->where('serial_number', 'like', '%'.$search.'%')
                ->orWhere('weapon_type', 'like', '%'.$search.'%')
                ->orWhere('permit_number', 'like', '%'.$search.'%')
                ->orWhere('caliber', 'like', '%'.$search.'%')
                ->orWhere('brand', 'like', '%'.$search.'%')
                ->orWhereHas('activeClientAssignment.client', function (Builder $clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', '%'.$search.'%');
                })
                ->orWhereHas('activeClientAssignment.responsible', function (Builder $userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%'.$search.'%');
                })
                ->orWhereHas('activePostAssignment.post', function (Builder $postQuery) use ($search) {
                    $postQuery->where('name', 'like', '%'.$search.'%');
                })
                ->orWhereHas('activeWorkerAssignment.worker', function (Builder $workerQuery) use ($search) {
                    $workerQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('document', 'like', '%'.$search.'%');
                });
        });
    }

    /**
     * @param  array{
     *     inventory_scope: string,
     *     q: ?string,
     *     permit_expires_from: ?string,
     *     permit_expires_to: ?string,
     *     destination: ?string,
     * }  $globalFilters
     */
    private function applyGlobalFilters(Builder $query, array $globalFilters): void
    {
        if ($globalFilters['permit_expires_from']) {
            $query->whereDate('permit_expires_at', '>=', $globalFilters['permit_expires_from']);
        }

        if ($globalFilters['permit_expires_to']) {
            $query->whereDate('permit_expires_at', '<=', $globalFilters['permit_expires_to']);
        }

        switch ($globalFilters['destination']) {
            case 'with_destination':
                $query->where(function (Builder $builder) {
                    $builder->whereHas('activeClientAssignment')
                        ->orWhereHas('activePostAssignment')
                        ->orWhereHas('activeWorkerAssignment')
                        ->orWhereHas('activePendingTransfer', function (Builder $tq) {
                            $tq->whereNotNull('from_client_id');
                        });
                });
                break;
            case 'without_destination':
                $query->whereDoesntHave('activeClientAssignment')
                    ->whereDoesntHave('activePostAssignment')
                    ->whereDoesntHave('activeWorkerAssignment')
                    ->where(function (Builder $destQuery) {
                        $destQuery->whereDoesntHave('activePendingTransfer')
                            ->orWhereHas('activePendingTransfer', function (Builder $tq) {
                                $tq->whereNull('from_client_id');
                            });
                    });
                break;
            case 'post':
                $query->whereHas('activePostAssignment');
                break;
            case 'worker':
                $query->whereHas('activeWorkerAssignment');
                break;
        }
    }
}
