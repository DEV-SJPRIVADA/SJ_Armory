<?php

namespace App\Services;

use App\Models\IncidentType;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponIncident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WeaponIncidentReportService
{
    public function filtersFromRequest(array $input): array
    {
        return [
            'year' => $this->normalizeYear($input['year'] ?? null),
        ];
    }

    public function availableYears(User $user, ?IncidentType $selectedType = null): Collection
    {
        $query = WeaponIncident::query()
            ->whereNotNull('event_at');

        if ($selectedType) {
            $query->where('incident_type_id', $selectedType->id);
        }

        if ($user->isResponsible() && ! $user->isAdmin()) {
            $query->whereHas('weapon.activeClientAssignment', function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery->where('responsible_user_id', $user->id);
            });
        }

        return $query
            ->get(['event_at'])
            ->map(fn (WeaponIncident $incident) => (int) optional($incident->event_at)->format('Y'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }

    public function paginated(User $user, array $filters, ?IncidentType $selectedType = null): LengthAwarePaginator
    {
        return $this->baseQuery($user, $filters, $selectedType)
            ->with($this->relationships())
            ->orderByDesc('event_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function dashboard(User $user, array $filters, ?IncidentType $selectedType = null): array
    {
        $incidents = $this->baseQuery($user, $filters, $selectedType)
            ->with($this->relationships())
            ->get();

        $selectedTypeId = $selectedType?->id;
        $selectedYear = $filters['year'];
        $periodLabel = $selectedYear ? (string) $selectedYear : 'todos los años';

        $typePalette = [
            'hurtada' => '#dc2626',
            'perdida' => '#f59e0b',
            'incautada' => '#7c3aed',
            'en_mantenimiento' => '#0f766e',
            'para_mantenimiento' => '#2563eb',
            'en_armerillo' => '#8b5cf6',
            'dar_de_baja' => '#475569',
        ];

        $allTypes = IncidentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $typeItems = $allTypes->map(function (IncidentType $type) use ($incidents, $selectedTypeId, $typePalette) {
            return [
                'id' => $type->id,
                'code' => $type->code,
                'label' => $type->name,
                'value' => $incidents->where('incident_type_id', $type->id)->count(),
                'color' => $typePalette[$type->code] ?? $type->color ?? '#475569',
                'selected' => $selectedTypeId === $type->id,
            ];
        })->values();

        $modalityItems = $incidents
            ->filter(fn (WeaponIncident $incident) => $selectedTypeId ? $incident->incident_type_id === $selectedTypeId : true)
            ->groupBy(fn (WeaponIncident $incident) => $incident->modality?->name ?? 'Sin modalidad')
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'value' => $group->count(),
            ])
            ->sortByDesc('value')
            ->values();

        $recentItems = $incidents
            ->sortByDesc(fn (WeaponIncident $incident) => $incident->latestActivityAt()?->timestamp ?? 0)
            ->take(5)
            ->map(function (WeaponIncident $incident) {
                $latestNote = $incident->latestUpdate?->note ?: ($incident->note ?: $incident->observation);

                return [
                    'id' => $incident->id,
                    'label' => $incident->type?->name ?? 'Novedad',
                    'weapon' => trim(($incident->weapon?->internal_code ?? '-') . ' / ' . ($incident->weapon?->serial_number ?? '-')),
                    'status' => WeaponIncident::statusOptions()[$incident->status] ?? $incident->status,
                    'event_at' => $incident->latestActivityAt(),
                    'note' => $latestNote ?: '-',
                ];
            })
            ->values()
            ->all();

        $typeChart = $this->buildDonutChart(
            $typeItems->all(),
            'Total de reportes',
            $incidents->count()
        );

        $modalityChart = $this->buildDonutChart(
            $modalityItems->all(),
            $selectedType ? 'Modalidades de ' . $selectedType->name : 'Modalidades vigentes',
            $modalityItems->sum('value')
        );

        $timelineChart = $this->buildTimelineChart($incidents, $selectedYear);
        $openStatuses = [WeaponIncident::STATUS_OPEN, WeaponIncident::STATUS_IN_PROGRESS];
        $topModality = $modalityItems->sortByDesc('value')->first();

        return [
            'as_of' => now(),
            'selected_year' => $selectedYear,
            'selected_type' => $selectedType?->only(['id', 'code', 'name']),
            'kpis' => [
                [
                    'label' => 'Novedades abiertas',
                    'value' => $incidents->whereIn('status', $openStatuses)->count(),
                    'tone' => 'red',
                    'helper' => 'Casos activos en el alcance actual',
                ],
                [
                    'label' => 'Armas afectadas',
                    'value' => $incidents->pluck('weapon_id')->unique()->count(),
                    'tone' => 'blue',
                    'helper' => 'Armas con al menos una novedad',
                ],
                [
                    'label' => 'Modalidad principal',
                    'value' => $topModality['value'] ?? 0,
                    'tone' => 'orange',
                    'helper' => $topModality['label'] ?? 'Sin modalidad dominante',
                ],
                [
                    'label' => 'En el periodo',
                    'value' => $incidents->count(),
                    'tone' => 'slate',
                    'helper' => 'Novedades registradas en ' . $periodLabel,
                ],
            ],
            'type_chart' => $typeChart,
            'modality_chart' => $modalityChart,
            'timeline_chart' => $timelineChart,
            'recent_items' => $recentItems,
        ];
    }

    public function searchWeapons(User $user, ?string $term = null, int $limit = 8): Collection
    {
        $query = Weapon::query()
            ->select(['id', 'brand', 'serial_number', 'permit_expires_at'])
            ->with('activeClientAssignment.client')
            ->orderBy('serial_number');

        $this->applyRoleScopeToWeapons($query, $user);

        $term = trim((string) $term);

        if ($term !== '') {
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('serial_number', 'like', '%' . $term . '%')
                    ->orWhere('brand', 'like', '%' . $term . '%')
                    ->orWhereHas('activeClientAssignment.client', function (Builder $clientQuery) use ($term) {
                        $clientQuery->where('name', 'like', '%' . $term . '%');
                    });
            });
        }

        return $query
            ->limit(max(1, min($limit, 12)))
            ->get()
            ->map(fn (Weapon $weapon) => $this->mapWeaponSearchResult($weapon));
    }

    public function findSearchWeapon(User $user, int $weaponId): ?array
    {
        $query = Weapon::query()
            ->select(['id', 'brand', 'serial_number', 'permit_expires_at'])
            ->with('activeClientAssignment.client')
            ->whereKey($weaponId);

        $this->applyRoleScopeToWeapons($query, $user);

        $weapon = $query->first();

        return $weapon ? $this->mapWeaponSearchResult($weapon) : null;
    }

    private function baseQuery(User $user, array $filters, ?IncidentType $selectedType = null): Builder
    {
        $query = WeaponIncident::query();

        if ($selectedType) {
            $query->where('incident_type_id', $selectedType->id);
        }

        if (! empty($filters['year'])) {
            $year = (int) $filters['year'];
            $query->whereBetween('event_at', [
                now()->setYear($year)->startOfYear(),
                now()->setYear($year)->endOfYear(),
            ]);
        }

        if ($user->isResponsible() && ! $user->isAdmin()) {
            $query->whereHas('weapon.activeClientAssignment', function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery->where('responsible_user_id', $user->id);
            });
        }

        return $query;
    }

    private function relationships(): array
    {
        return [
            'weapon.activeClientAssignment.client',
            'weapon.activeClientAssignment.responsible',
            'type',
            'modality',
            'reporter',
            'attachmentFile',
            'updates.creator',
            'updates.attachmentFile',
            'latestUpdate.creator',
            'latestUpdate.attachmentFile',
        ];
    }

    private function applyRoleScopeToWeapons(Builder $query, User $user): void
    {
        if ($user->isResponsible() && ! $user->isAdmin()) {
            $query->whereHas('activeClientAssignment', function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery->where('responsible_user_id', $user->id)
                    ->where('is_active', true);
            });
        }
    }

    private function mapWeaponSearchResult(Weapon $weapon): array
    {
        $expiresAt = $weapon->permit_expires_at instanceof Carbon
            ? $weapon->permit_expires_at
            : ($weapon->permit_expires_at ? Carbon::parse($weapon->permit_expires_at) : null);

        return [
            'id' => $weapon->id,
            'client' => $weapon->activeClientAssignment?->client?->name ?? 'Sin destino',
            'brand' => $weapon->brand ?? '-',
            'serial' => $weapon->serial_number ?? '-',
            'permit_expires_at' => $expiresAt?->format('Y-m-d'),
            'permit_expires_label' => $expiresAt?->format('Y-m-d') ?? 'Sin vencimiento',
            'summary' => trim(($weapon->brand ?? '-') . ' / ' . ($weapon->serial_number ?? '-')),
        ];
    }

    private function buildDonutChart(array $items, string $centerLabel, int $centerValue): array
    {
        $palette = ['#0b6fb6', '#dc2626', '#f59e0b', '#7c3aed', '#0f766e', '#334155', '#1d4ed8', '#be123c'];

        $normalized = collect($items)
            ->values()
            ->map(function (array $item, int $index) use ($palette) {
                return [
                    'label' => $item['label'],
                    'value' => (int) ($item['value'] ?? 0),
                    'color' => $item['color'] ?? $palette[$index % count($palette)],
                    'code' => $item['code'] ?? null,
                    'selected' => (bool) ($item['selected'] ?? false),
                ];
            })
            ->sortBy([
                ['value', 'desc'],
                ['label', 'asc'],
            ])
            ->values();

        $total = max(0, (int) $normalized->sum('value'));
        $maxValue = (int) $normalized->max('value');
        $offset = 0.0;
        $centerX = 50.0;
        $centerY = 50.0;
        $labelRadius = 38.0;
        $anchorRadius = 43.0;

        $itemsWithShare = $normalized->map(function (array $item) use (
            $total,
            $maxValue,
            &$offset,
            $centerX,
            $centerY,
            $labelRadius,
            $anchorRadius
        ) {
            $share = $total > 0 ? round(($item['value'] / $total) * 100, 1) : 0.0;
            $from = $offset;
            $offset += $share;
            $to = $offset;
            $mid = $from + ($share / 2);
            $radians = deg2rad($mid * 3.6);
            $side = sin($radians) >= 0 ? 'right' : 'left';

            $item['share'] = $share;
            $item['share_label'] = number_format($share, 1) . '%';
            $item['from'] = $from;
            $item['to'] = $to;
            $item['mid_angle'] = $mid;
            $item['arc_x'] = round($centerX + (sin($radians) * $labelRadius), 2);
            $item['arc_y'] = round($centerY - (cos($radians) * $labelRadius), 2);
            $item['show_arc_label'] = $item['value'] > 0 && ($share >= 9.5 || ($item['value'] === $maxValue && $share >= 6));
            $item['callout_visible'] = $item['value'] > 0 && ! $item['show_arc_label'];
            $item['callout_side'] = $side;
            $item['callout_anchor_x'] = round($centerX + (sin($radians) * $anchorRadius), 2);
            $item['callout_anchor_y'] = round($centerY - (cos($radians) * $anchorRadius), 2);
            $item['callout_y'] = round($centerY - (cos($radians) * 49.5), 2);
            $item['callout_bend_x'] = $side === 'right' ? 76.5 : 23.5;
            $item['callout_end_x'] = $side === 'right' ? 83.5 : 16.5;
            $item['callout_badge_x'] = $side === 'right' ? 86.5 : 13.5;
            $item['callout_text_anchor'] = $side === 'right' ? 'start' : 'end';

            return $item;
        });

        $itemsWithShare = collect($this->arrangeDonutCallouts($itemsWithShare));

        return [
            'center_label' => $centerLabel,
            'center_value' => $centerValue,
            'gradient' => $this->buildConicGradient($itemsWithShare),
            'has_data' => $total > 0,
            'items' => $itemsWithShare->all(),
        ];
    }

    private function arrangeDonutCallouts(Collection $items): array
    {
        $itemsArray = $items->values()->all();
        $sideIndexes = [
            'left' => [],
            'right' => [],
        ];

        foreach ($itemsArray as $index => $item) {
            if (! ($item['callout_visible'] ?? false)) {
                continue;
            }

            $sideIndexes[$item['callout_side'] ?? 'right'][] = $index;
        }

        foreach ($sideIndexes as $side => $indexes) {
            if ($indexes === []) {
                continue;
            }

            usort($indexes, function (int $first, int $second) use ($itemsArray) {
                return ($itemsArray[$first]['callout_anchor_y'] ?? 0) <=> ($itemsArray[$second]['callout_anchor_y'] ?? 0);
            });

            $count = count($indexes);
            $top = 24.0;
            $bottom = 76.0;
            $slots = [];

            if ($count === 1) {
                $slots[] = min($bottom, max($top, (float) ($itemsArray[$indexes[0]]['callout_anchor_y'] ?? 50.0)));
            } else {
                $step = ($bottom - $top) / max(1, $count - 1);
                for ($position = 0; $position < $count; $position++) {
                    $slots[] = round($top + ($step * $position), 2);
                }
            }

            foreach ($indexes as $position => $index) {
                $y = $slots[$position] ?? end($slots);
                $itemsArray[$index]['callout_y'] = round((float) $y, 2);
                $itemsArray[$index]['callout_bend_x'] = $side === 'right' ? 76.5 : 23.5;
                $itemsArray[$index]['callout_end_x'] = $side === 'right' ? 83.5 : 16.5;
                $itemsArray[$index]['callout_badge_x'] = $side === 'right' ? 86.5 : 13.5;
                $itemsArray[$index]['callout_text_anchor'] = $side === 'right' ? 'start' : 'end';
            }
        }

        return $itemsArray;
    }

    private function buildTimelineChart(Collection $incidents, ?int $selectedYear): array
    {
        if ($selectedYear) {
            $items = collect(range(1, 12))
                ->map(function (int $monthNumber) use ($incidents, $selectedYear) {
                    $month = now()->setYear($selectedYear)->setMonth($monthNumber)->startOfMonth();
                    $key = $month->format('Y-m');

                    return [
                        'label' => ucfirst($month->translatedFormat('M')),
                        'value' => $incidents->filter(fn (WeaponIncident $incident) => $incident->event_at?->format('Y-m') === $key)->count(),
                    ];
                })
                ->values();

            return [
                'title' => 'Evolucion mensual',
                'items' => $items->all(),
                'max' => max(1, (int) $items->max('value')),
            ];
        }

        $years = $incidents
            ->map(fn (WeaponIncident $incident) => (int) optional($incident->event_at)->format('Y'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([(int) now()->year]);
        }

        $items = $years->map(function (int $year) use ($incidents) {
            return [
                'label' => (string) $year,
                'value' => $incidents->filter(fn (WeaponIncident $incident) => (int) $incident->event_at?->format('Y') === $year)->count(),
            ];
        })->values();

        return [
            'title' => 'Evolucion anual',
            'items' => $items->all(),
            'max' => max(1, (int) $items->max('value')),
        ];
    }

    private function buildConicGradient(Collection $items): string
    {
        $segments = $items
            ->filter(fn (array $item) => $item['value'] > 0)
            ->values();

        if ($segments->isEmpty()) {
            return 'conic-gradient(#dbe4ef 0deg 360deg)';
        }

        $stops = [];
        $offset = 0.0;

        foreach ($segments as $item) {
            $degrees = max(0.0, ($item['share'] / 100) * 360);
            $nextOffset = min(360.0, $offset + $degrees);
            $stops[] = sprintf('%s %.2fdeg %.2fdeg', $item['color'], $offset, $nextOffset);
            $offset = $nextOffset;
        }

        if ($offset < 360.0) {
            $stops[] = sprintf('%s %.2fdeg 360deg', $segments->last()['color'], $offset);
        }

        return 'conic-gradient(' . implode(', ', $stops) . ')';
    }

    private function normalizeYear(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $yearText = trim((string) $value);

        if ($yearText === '' || strtolower($yearText) === 'all') {
            return null;
        }

        $year = (int) $yearText;
        $maxYear = (int) now()->year + 1;

        return $year >= 2000 && $year <= $maxYear
            ? $year
            : null;
    }
}

