@php
    $selectedYear = $filters['year'] ?? null;
    $typeChartItems = collect($dashboard['type_chart']['items'])
        ->filter(fn ($item) => ($item['value'] ?? 0) > 0)
        ->values();
    $modalityChartItems = collect($dashboard['modality_chart']['items'])
        ->filter(fn ($item) => ($item['value'] ?? 0) > 0)
        ->values();
@endphp

<section class="sj-dashboard-kpis sj-dashboard-kpis--compact">
    @foreach ($dashboard['kpis'] as $item)
        <article class="sj-kpi-card sj-kpi-card--{{ $item['tone'] }}">
            <div class="sj-kpi-card__label">{{ $item['label'] }}</div>
            <div class="sj-kpi-card__value">{{ number_format($item['value']) }}</div>
            <div class="sj-kpi-card__helper">{{ $item['helper'] }}</div>
        </article>
    @endforeach
</section>

<section class="sj-dashboard-grid sj-dashboard-grid--primary">
    <article class="sj-panel sj-panel--donut">
        <div class="sj-panel__head">
            <div>
                <div class="sj-form-section__title">{{ __('Tipos') }}</div>
                <h2 class="sj-panel__title">{{ __('Novedades por tipo') }}</h2>
            </div>
        </div>

        <div class="sj-donut-card sj-donut-card--stacked">
            <div class="sj-donut-card__chart sj-donut-card__chart--stacked">
                <div class="sj-donut-chart-shell">
                    <div class="sj-donut-chart sj-donut-chart--executive" style="--donut-gradient: {{ $dashboard['type_chart']['gradient'] }};">
                        <div class="sj-donut-chart__center sj-donut-chart__center--executive">
                            <span class="sj-donut-chart__label">{{ $dashboard['type_chart']['center_label'] }}</span>
                            <strong class="sj-donut-chart__value">{{ number_format($dashboard['type_chart']['center_value']) }}</strong>
                        </div>

                        @foreach ($typeChartItems as $item)
                            @if (!empty($item['show_arc_label']))
                                <span class="sj-donut-chart__arc-label" style="left: {{ $item['arc_x'] }}%; top: {{ $item['arc_y'] }}%;">
                                    <span class="sj-donut-chart__arc-share">{{ $item['share_label'] }}</span>
                                    <span class="sj-donut-chart__arc-value">{{ number_format($item['value']) }}</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="sj-donut-card__legend sj-donut-card__legend--stacked">
                @forelse ($typeChartItems as $item)
                    @php
                        $typeRoute = ['incidentType' => $item['code']];
                        if ($selectedYear !== null) {
                            $typeRoute['year'] = $selectedYear;
                        }
                    @endphp
                    <a
                        href="{{ route('reports.weapon-incidents.show', $typeRoute) }}"
                        class="sj-donut-legend__row {{ $item['selected'] ? 'sj-donut-legend__row--active' : '' }}"
                    >
                        <span class="sj-donut-legend__swatch" style="background: {{ $item['color'] }};"></span>
                        <span class="sj-donut-legend__text">{{ $item['label'] }}</span>
                        @if (empty($item['show_arc_label']))
                            <span class="sj-donut-legend__meta">
                                <span class="sj-donut-legend__share">{{ $item['share_label'] }}</span>
                                <span class="sj-donut-legend__count">{{ number_format($item['value']) }}</span>
                            </span>
                        @endif
                    </a>
                @empty
                    <p class="sj-panel__empty sj-panel__empty--soft">{{ __('No hay tipos con datos para el filtro actual.') }}</p>
                @endforelse
            </div>
        </div>
    </article>

    <article class="sj-panel sj-panel--donut">
        <div class="sj-panel__head">
            <div>
                <div class="sj-form-section__title">{{ __('Modalidades') }}</div>
                <h2 class="sj-panel__title">
                    {{ $selectedType ? __('Distribución de modalidades') : __('Distribución actual') }}
                </h2>
            </div>
        </div>

        <div class="sj-donut-card sj-donut-card--stacked">
            <div class="sj-donut-card__chart sj-donut-card__chart--stacked">
                <div class="sj-donut-chart-shell">
                    <div class="sj-donut-chart sj-donut-chart--executive" style="--donut-gradient: {{ $dashboard['modality_chart']['gradient'] }};">
                        <div class="sj-donut-chart__center sj-donut-chart__center--executive">
                            <span class="sj-donut-chart__label">{{ $dashboard['modality_chart']['center_label'] }}</span>
                            <strong class="sj-donut-chart__value">{{ number_format($dashboard['modality_chart']['center_value']) }}</strong>
                        </div>

                        @foreach ($modalityChartItems as $item)
                            @if (!empty($item['show_arc_label']))
                                <span class="sj-donut-chart__arc-label" style="left: {{ $item['arc_x'] }}%; top: {{ $item['arc_y'] }}%;">
                                    <span class="sj-donut-chart__arc-share">{{ $item['share_label'] }}</span>
                                    <span class="sj-donut-chart__arc-value">{{ number_format($item['value']) }}</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="sj-donut-card__legend sj-donut-card__legend--stacked">
                @forelse ($modalityChartItems as $item)
                    <div class="sj-donut-legend__row sj-donut-legend__row--static">
                        <span class="sj-donut-legend__swatch" style="background: {{ $item['color'] }};"></span>
                        <span class="sj-donut-legend__text">{{ $item['label'] }}</span>
                        @if (empty($item['show_arc_label']))
                            <span class="sj-donut-legend__meta">
                                <span class="sj-donut-legend__share">{{ $item['share_label'] }}</span>
                                <span class="sj-donut-legend__count">{{ number_format($item['value']) }}</span>
                            </span>
                        @endif
                    </div>
                @empty
                    <p class="sj-panel__empty sj-panel__empty--soft">{{ __('No hay modalidades para el filtro actual.') }}</p>
                @endforelse
            </div>
        </div>
    </article>
</section>

<section class="sj-dashboard-grid sj-dashboard-grid--secondary">
    <article class="sj-panel">
        <div class="sj-panel__head">
            <div>
                <div class="sj-form-section__title">{{ __('Tendencia') }}</div>
                <h2 class="sj-panel__title">{{ $dashboard['timeline_chart']['title'] }}</h2>
            </div>
        </div>

        <div class="sj-column-chart-wrap">
            <div class="sj-column-chart">
                @foreach ($dashboard['timeline_chart']['items'] as $item)
                    <div class="sj-column-chart__item">
                        <div class="sj-column-chart__value">{{ number_format($item['value']) }}</div>
                        <div class="sj-column-chart__track">
                            <div class="sj-column-chart__bar" style="height: {{ max(1, round(($item['value'] / max(1, $dashboard['timeline_chart']['max'])) * 100)) }}%"></div>
                        </div>
                        <div class="sj-column-chart__label">{{ $item['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </article>

    <article class="sj-panel">
        <div class="sj-panel__head">
            <div>
                <div class="sj-form-section__title">{{ __('Recientes') }}</div>
                <h2 class="sj-panel__title">{{ __('Últimos reportes') }}</h2>
            </div>
        </div>

        <div class="sj-stat-list sj-stat-list--compact">
            @forelse ($dashboard['recent_items'] as $item)
                <div class="sj-stat-list__item">
                    <div class="sj-stat-list__meta">
                        <span class="sj-stat-list__dot" style="background:#0f172a"></span>
                        <span class="sj-stat-list__label">{{ $item['label'] }} / {{ $item['weapon'] }}</span>
                    </div>
                    <span class="sj-stat-list__value">{{ $item['status'] }}</span>
                </div>
            @empty
                <p class="sj-panel__empty">{{ __('No hay novedades recientes.') }}</p>
            @endforelse
        </div>
    </article>
</section>

<section class="sj-panel">
    <div class="sj-panel__head">
        <div>
            <div class="sj-form-section__title">{{ __('Detalle') }}</div>
            <h2 class="sj-panel__title">{{ __('Listado de reportes') }}</h2>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Fecha') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Arma') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Tipo') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Modalidad') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Estado') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Resumen') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Seguimiento') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Adjunto') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Expediente') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($incidents as $incident)
                    @php
                        $updateCount = $incident->updates->reject(
                            fn (App\Models\WeaponIncidentUpdate $update) => $update->event_type === App\Models\WeaponIncidentUpdate::EVENT_REPORTED
                        )->count();
                        $statusTone = match ($incident->status) {
                            App\Models\WeaponIncident::STATUS_OPEN => 'danger',
                            App\Models\WeaponIncident::STATUS_IN_PROGRESS => 'warning',
                            App\Models\WeaponIncident::STATUS_RESOLVED => 'ok',
                            App\Models\WeaponIncident::STATUS_CANCELLED => 'neutral',
                            default => 'notice',
                        };
                    @endphp
                    <tr>
                        <td class="px-3 py-2">{{ $incident->event_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('weapons.show', $incident->weapon) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $incident->weapon?->internal_code ?? '-' }} / {{ $incident->weapon?->serial_number ?? '-' }}
                            </a>
                        </td>
                        <td class="px-3 py-2">{{ $incident->type?->name ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $incident->modality?->name ?? '-' }}</td>
                        <td class="px-3 py-2">
                            <span class="weapon-incident-status">
                                <span class="weapon-incident-status__dot weapon-incident-status__dot--{{ $statusTone }}"></span>
                                <span>{{ $statusOptions[$incident->status] ?? $incident->status }}</span>
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ $incident->weapon?->activeClientAssignment?->client?->name ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $incident->observation ?? ($incident->note ?? '-') }}</td>
                        <td class="px-3 py-2">
                            <div class="text-slate-700">{{ $updateCount }} {{ __('hitos') }}</div>
                            <div class="text-xs text-slate-500">
                                {{ $incident->latestUpdate?->eventTypeLabel() ?? __('Solo reporte inicial') }}
                                @if ($incident->latestActivityAt())
                                    &middot; {{ $incident->latestActivityAt()->format('Y-m-d H:i') }}
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            @if ($incident->attachmentFile)
                                <a href="{{ route('weapon-incidents.attachment', $incident) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ __('Descargar') }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-full border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                                data-open-modal="incident-case-{{ $incident->id }}"
                            >
                                @can('update', $incident)
                                    {{ __('Gestionar') }}
                                @else
                                    {{ __('Ver caso') }}
                                @endcan
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-3 py-6 text-center text-gray-500">
                            {{ __('No hay novedades con los filtros actuales.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $incidents->links() }}
    </div>
</section>

@foreach ($incidents as $incident)
    @include('reports.weapon-incidents.partials.case-modal', ['incident' => $incident, 'statusOptions' => $statusOptions])
@endforeach

