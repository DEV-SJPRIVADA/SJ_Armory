<?php

namespace App\Services;

use App\Models\Client;
use App\Models\IncidentType;
use App\Models\Post;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponDocument;
use App\Models\WeaponIncident;
use App\Models\WeaponTransfer;
use App\Models\Worker;
use App\Support\WeaponDocumentAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    private const INCIDENT_ORDER = [
        'Hurtada',
        'Perdida',
        'Incautada',
        'En Mantenimiento',
        'Para Mantenimiento',
        'En Armerillo',
        'Dar de Baja',
    ];

    public function forUser(User $user, ?int $renewalYear = null): array
    {
        $weapons = $this->weaponsQuery($user)
            ->with([
                'activeClientAssignment.client',
                'activeClientAssignment.responsible',
                'activePostAssignment',
                'activeWorkerAssignment',
                'operationalBlockingIncidents',
            ])
            ->get();

        $weaponIds = $weapons->pluck('id');

        // Crear un mapa de armas con incidentes bloqueantes
        $weaponsWithBlockingIncidents = $weapons
            ->filter(fn (Weapon $weapon) => $weapon->operationalBlockingIncidents->isNotEmpty())
            ->pluck('id')
            ->toArray();

        $renewalDocuments = $this->renewalDocumentsQuery($user, $weaponIds)
            ->orderByDesc('id')
            ->get()
            ->unique('weapon_id')
            ->values();

        $riskCounts = [
            'Vencidas' => 0,
            'Por vencer' => 0,
            'Preventivas' => 0,
            'Vigentes' => 0,
        ];

        foreach ($renewalDocuments as $document) {
            $alert = WeaponDocumentAlert::forComplianceDocument($document);

            if ($alert['state'] === 'Vencido') {
                $riskCounts['Vencidas']++;
                continue;
            }

            if ($alert['state'] === 'Próximo a vencer') {
                $riskCounts['Por vencer']++;
                continue;
            }

            if ($alert['state'] === 'Alerta preventiva') {
                $riskCounts['Preventivas']++;
                continue;
            }

            $riskCounts['Vigentes']++;
        }

        $responsibleCounts = $weapons
            ->filter(fn (Weapon $weapon) => $weapon->activeClientAssignment?->responsible?->name)
            ->groupBy(fn (Weapon $weapon) => $weapon->activeClientAssignment->responsible->name)
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->take(8);

        $availableRenewalYears = $renewalDocuments
            ->filter(fn (WeaponDocument $document) => $document->valid_until !== null)
            ->map(fn (WeaponDocument $document) => (int) $document->valid_until->format('Y'))
            ->unique()
            ->sort()
            ->values();

        $currentYear = (int) now()->format('Y');
        $selectedRenewalYear = $availableRenewalYears->contains($renewalYear)
            ? $renewalYear
            : ($availableRenewalYears->contains($currentYear)
                ? $currentYear
                : $availableRenewalYears->first());

        $renewalMonths = $renewalDocuments
            ->filter(fn (WeaponDocument $document) => $document->valid_until !== null)
            ->when($selectedRenewalYear, fn (Collection $items) => $items->filter(
                fn (WeaponDocument $document) => (int) $document->valid_until->format('Y') === (int) $selectedRenewalYear
            ))
            ->groupBy(fn (WeaponDocument $document) => $document->valid_until->format('Y-m'))
            ->sortKeys()
            ->map(function (Collection $group, string $monthKey) use ($weaponsWithBlockingIncidents) {
                $month = Carbon::createFromFormat('Y-m-d', $monthKey . '-01')
                    ->locale(app()->getLocale());

                // Contar documentos con y sin novedad
                $sinNovedad = $group->filter(fn (WeaponDocument $document) => 
                    !in_array($document->weapon_id, $weaponsWithBlockingIncidents)
                )->count();
                
                $conNovedad = $group->filter(fn (WeaponDocument $document) => 
                    in_array($document->weapon_id, $weaponsWithBlockingIncidents)
                )->count();

                return [
                    'key' => $monthKey,
                    'label' => ucfirst($month->translatedFormat('M y')),
                    'value' => $group->count(),
                    'sin_novedad' => $sinNovedad,
                    'con_novedad' => $conNovedad,
                ];
            })
            ->values();

        $openIncidents = WeaponIncident::query()
            ->with('type')
            ->whereIn('weapon_id', $weaponIds->all())
            ->whereIn('status', [WeaponIncident::STATUS_OPEN, WeaponIncident::STATUS_IN_PROGRESS])
            ->get();

        $incidentTypeMap = IncidentType::query()
            ->whereIn('name', self::INCIDENT_ORDER)
            ->get()
            ->keyBy('name');

        $incidentCounts = collect(self::INCIDENT_ORDER)
            ->map(function (string $label) use ($incidentTypeMap, $openIncidents) {
                $type = $incidentTypeMap->get($label);

                return [
                    'label' => $label,
                    'type' => $type,
                    'value' => $type ? $openIncidents->where('incident_type_id', $type->id)->count() : 0,
                ];
            });

        $transferCounts = $this->transferStatusCounts($user);

        $activeDestinationWeapons = $weapons
            ->filter(fn (Weapon $weapon) => $weapon->activeClientAssignment)
            ->values();

        $operationalWeaponsCount = $weapons
            ->filter(fn (Weapon $weapon) => $weapon->operationalBlockingIncidents->isEmpty())
            ->count();

        $nonOperationalWeaponsCount = $weapons->count() - $operationalWeaponsCount;

        $operationalDistribution = [
            'Asignadas a puesto' => $activeDestinationWeapons
                ->filter(fn (Weapon $weapon) => $weapon->activePostAssignment)
                ->count(),
            'Asignadas a trabajador' => $activeDestinationWeapons
                ->filter(fn (Weapon $weapon) => $weapon->activeWorkerAssignment)
                ->count(),
            'Sin asignación interna' => $activeDestinationWeapons
                ->filter(fn (Weapon $weapon) => ! $weapon->activePostAssignment && ! $weapon->activeWorkerAssignment)
                ->count(),
        ];

        $clientCount = $this->clientsQuery($user)->count();
        $postCount = $this->postsQuery($user)->count();
        $workerCount = $this->workersQuery($user)->count();

        $riskItems = [
            ['label' => 'Vencidas', 'value' => $riskCounts['Vencidas'], 'color' => '#dc2626'],
            ['label' => 'Por vencer', 'value' => $riskCounts['Por vencer'], 'color' => '#ea580c'],
            ['label' => 'Preventivas', 'value' => $riskCounts['Preventivas'], 'color' => '#d97706'],
            ['label' => 'Vigentes', 'value' => $riskCounts['Vigentes'], 'color' => '#15803d'],
        ];

        return [
            'scope_label' => $this->scopeLabel($user),
            'as_of' => now(),
            'kpis' => [
                [
                    'label' => 'Total de armas',
                    'value' => $weapons->count(),
                    'tone' => 'slate',
                    'helper' => 'Inventario visible para tu rol',
                ],
                [
                    'label' => 'Armas operativas',
                    'value' => $operationalWeaponsCount,
                    'tone' => 'green',
                    'helper' => 'Disponibles para operación',
                ],
                [
                    'label' => 'Armas con novedad',
                    'value' => $nonOperationalWeaponsCount,
                    'tone' => 'red',
                    'helper' => 'Fuera de operación por novedad bloqueante',
                ],
                [
                    'label' => 'Con destino activo',
                    'value' => $activeDestinationWeapons->count(),
                    'tone' => 'blue',
                    'helper' => 'Armas con cliente asignado',
                ],
                [
                    'label' => 'Sin destino',
                    'value' => $weapons->filter(fn (Weapon $weapon) => ! $weapon->activeClientAssignment)->count(),
                    'tone' => 'amber',
                    'helper' => 'Pendientes de asignación operativa',
                ],
                [
                    'label' => 'Documentos vencidos',
                    'value' => $riskCounts['Vencidas'],
                    'tone' => 'red',
                    'helper' => 'Requieren atención inmediata',
                ],
                [
                    'label' => 'Por vencer',
                    'value' => $riskCounts['Por vencer'] + $riskCounts['Preventivas'],
                    'tone' => 'orange',
                    'helper' => 'Dentro de la ventana de 120 días',
                ],
                [
                    'label' => 'Transferencias pendientes',
                    'value' => $transferCounts['Pendientes'],
                    'tone' => 'indigo',
                    'helper' => 'Solicitudes aún sin resolver',
                ],
            ],
            'meta' => [
                ['label' => 'Clientes', 'value' => $clientCount],
                ['label' => 'Puestos', 'value' => $postCount],
                ['label' => 'Trabajadores', 'value' => $workerCount],
            ],
            'responsible_chart' => [
                'items' => $responsibleCounts->map(fn (int $value, string $label) => [
                    'label' => $label,
                    'value' => $value,
                ])->values()->all(),
                'max' => max(1, (int) $responsibleCounts->max()),
            ],
            'renewal_chart' => [
                'items' => $renewalMonths->all(),
                'max' => max(1, (int) $renewalMonths->max('value')),
                'years' => $availableRenewalYears->all(),
                'selected_year' => $selectedRenewalYear,
            ],
            'risk_chart' => [
                'items' => $riskItems,
                'total' => array_sum($riskCounts),
                'donut_style' => $this->buildDonutStyle($riskItems),
            ],
            'incident_chart' => [
                'items' => $incidentCounts->map(function (array $item) {
                    $colors = [
                        'Hurtada' => '#be123c',
                        'Perdida' => '#b91c1c',
                        'Incautada' => '#7c2d12',
                        'En Mantenimiento' => '#0f766e',
                        'Para Mantenimiento' => '#0ea5e9',
                        'En Armerillo' => '#7c3aed',
                        'Dar de Baja' => '#4b5563',
                    ];

                    return [
                        'label' => $item['label'],
                        'value' => (int) $item['value'],
                        'color' => $colors[$item['label']] ?? '#475569',
                        'url' => $item['type']
                            ? route('reports.weapon-incidents.show', ['incidentType' => $item['type']->code])
                            : route('reports.weapon-incidents.index'),
                    ];
                })->all(),
                'total' => (int) $incidentCounts->sum('value'),
                'max' => max(1, (int) $incidentCounts->max('value')),
            ],
            'transfer_chart' => [
                'items' => collect($transferCounts)->map(function (int $value, string $label) {
                    $colors = [
                        'Pendientes' => '#2563eb',
                        'Aceptadas' => '#15803d',
                        'Rechazadas' => '#dc2626',
                        'Canceladas' => '#64748b',
                    ];

                    return [
                        'label' => $label,
                        'value' => $value,
                        'color' => $colors[$label] ?? '#475569',
                    ];
                })->values()->all(),
                'max' => max(1, max($transferCounts)),
            ],
            'operational_chart' => [
                'items' => collect($operationalDistribution)->map(function (int $value, string $label) {
                    $colors = [
                        'Asignadas a puesto' => '#0891b2',
                        'Asignadas a trabajador' => '#7c3aed',
                        'Sin asignación interna' => '#f59e0b',
                    ];

                    return [
                        'label' => $label,
                        'value' => $value,
                        'color' => $colors[$label] ?? '#475569',
                    ];
                })->values()->all(),
                'total' => array_sum($operationalDistribution),
                'max' => max(1, max($operationalDistribution)),
            ],
        ];
    }

    private function weaponsQuery(User $user): Builder
    {
        $query = Weapon::query();

        if ($this->limitsToResponsibleScope($user)) {
            $query->whereHas('activeClientAssignment', function (Builder $builder) use ($user) {
                $builder->where('responsible_user_id', $user->id);
            });
        }

        return $query;
    }

    private function clientsQuery(User $user): Builder
    {
        $query = Client::query();

        if ($this->limitsToResponsibleScope($user)) {
            $query->whereIn('id', $user->clients()->pluck('clients.id'));
        }

        return $query;
    }

    private function postsQuery(User $user): Builder
    {
        $query = Post::query();

        if ($this->limitsToResponsibleScope($user)) {
            $query->whereIn('client_id', $user->clients()->pluck('clients.id'));
        }

        return $query;
    }

    private function workersQuery(User $user): Builder
    {
        $query = Worker::query();

        if ($this->limitsToResponsibleScope($user)) {
            $query->whereIn('client_id', $user->clients()->pluck('clients.id'));
        }

        return $query;
    }

    private function renewalDocumentsQuery(User $user, Collection $weaponIds): Builder
    {
        $query = WeaponDocument::query()
            ->where('is_renewal', true)
            ->whereNotNull('valid_until');

        if ($this->limitsToResponsibleScope($user)) {
            $query->whereIn('weapon_id', $weaponIds->all());
        }

        return $query;
    }

    private function transferStatusCounts(User $user): array
    {
        $query = WeaponTransfer::query();

        if ($this->limitsToResponsibleScope($user)) {
            $query->where(function (Builder $builder) use ($user) {
                $builder->where('requested_by', $user->id)
                    ->orWhere('to_user_id', $user->id);
            });
        }

        $rows = $query
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'Pendientes' => (int) ($rows[WeaponTransfer::STATUS_PENDING] ?? 0),
            'Aceptadas' => (int) ($rows[WeaponTransfer::STATUS_ACCEPTED] ?? 0),
            'Rechazadas' => (int) ($rows[WeaponTransfer::STATUS_REJECTED] ?? 0),
            'Canceladas' => (int) ($rows[WeaponTransfer::STATUS_CANCELLED] ?? 0),
        ];
    }

    private function buildDonutStyle(array $items): string
    {
        $total = max(1, array_sum(array_column($items, 'value')));
        $offset = 0.0;
        $segments = [];

        foreach ($items as $item) {
            $value = (int) ($item['value'] ?? 0);
            if ($value <= 0) {
                continue;
            }

            $start = round(($offset / $total) * 360, 2);
            $offset += $value;
            $end = round(($offset / $total) * 360, 2);
            $segments[] = sprintf('%s %sdeg %sdeg', $item['color'], $start, $end);
        }

        if ($segments === []) {
            $segments[] = '#e2e8f0 0deg 360deg';
        }

        return 'background: conic-gradient(' . implode(', ', $segments) . ');';
    }

    private function scopeLabel(User $user): string
    {
        if ($user->isResponsible()) {
            return 'Vista filtrada a tu operación';
        }

        if ($user->isAuditor()) {
            return 'Vista global de consulta';
        }

        return 'Vista global del sistema';
    }

    private function limitsToResponsibleScope(User $user): bool
    {
        return $user->isResponsible() && ! $user->isAdmin() && ! $user->isAuditor();
    }
}

