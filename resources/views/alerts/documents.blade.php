@push('styles')
    <style>
        .sj-page-header { position: sticky; top: 4rem; z-index: 1100; }
        .alerts-toolbar-shell { margin: 0 auto; max-width: none; width: 100%; }
        .alerts-toolbar { display: flex; flex-direction: column; gap: 1rem; width: 100%; }
        .alerts-toolbar__top { display: grid; align-items: center; column-gap: 2.5rem; grid-template-columns: minmax(18rem, 1fr) auto minmax(7rem, 1fr); }
        .alerts-toolbar__title { margin: 0; color: #111827; font-size: 1.12rem; font-weight: 800; letter-spacing: -0.01em; line-height: 1; white-space: nowrap; }
        .alerts-toolbar__center, .alerts-toolbar__bottom { display: flex; justify-content: center; min-width: 0; }
        .alerts-toolbar__filters, .alerts-toolbar__bottom-group { display: flex; align-items: center; justify-content: center; gap: 1rem; min-width: 0; }
        .alerts-toolbar__filters { gap: 0.75rem; width: max-content; margin: 0 !important; }
        .alerts-toolbar__filters label, .alerts-period-picker__label, .alerts-toolbar__back { color: #374151; font-size: 0.95rem; font-weight: 600; line-height: 1; white-space: nowrap; }
        .alerts-toolbar__back { color: #6b7280; justify-self: end; }
        .alerts-toolbar__filters input, .alerts-toolbar__filters button, .alerts-toolbar__filters a, .alerts-toolbar__download, .alerts-toolbar__preview { height: 2.55rem; border-radius: 0.55rem; font-size: 0.95rem; margin-top: 0 !important; box-sizing: border-box; }
        .alerts-period-picker { position: relative; z-index: 1200; }
        .alerts-period-picker__toggle { display: inline-flex; align-items: center; justify-content: space-between; gap: 0.65rem; min-width: 14.5rem; max-width: 20rem; padding: 0 0.9rem; border: 1px solid #cbd5e1; background: #fff; color: #111827; font-weight: 600; cursor: pointer; text-align: left; }
        .alerts-period-picker__toggle:hover { border-color: #0b6fb6; }
        .alerts-period-picker__toggle[aria-expanded="true"] { border-color: #0b6fb6; box-shadow: 0 0 0 3px rgba(11, 111, 182, 0.15); }
        .alerts-period-picker__toggle.has-selection { border-color: #93c5fd; background: #f8fbff; }
        .alerts-period-picker__summary { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.92rem; }
        .alerts-period-picker__chevron { flex-shrink: 0; width: 1rem; height: 1rem; color: #64748b; transition: transform .18s ease; }
        .alerts-period-picker__toggle[aria-expanded="true"] .alerts-period-picker__chevron { transform: rotate(180deg); color: #0b6fb6; }
        .alerts-period-panel { position: absolute; top: calc(100% + 0.45rem); left: 0; width: min(22rem, calc(100vw - 2rem)); padding: 0.85rem; border: 1px solid #dbe5f1; border-radius: 0.75rem; background: #fff; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14); }
        .alerts-period-panel.hidden { display: none; }
        .alerts-period-panel__header { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem; }
        .alerts-period-panel__year { flex: 1; text-align: center; color: #0f172a; font-size: 1rem; font-weight: 800; letter-spacing: -0.02em; }
        .alerts-period-panel__nav { display: inline-flex; align-items: center; justify-content: center; width: 2.1rem; height: 2.1rem; padding: 0; border: 1px solid #cbd5e1; border-radius: 0.45rem; background: #fff; color: #334155; font-size: 1rem; font-weight: 700; cursor: pointer; }
        .alerts-period-panel__nav:hover { border-color: #0b6fb6; color: #0b6fb6; }
        .alerts-period-panel__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.45rem; }
        .alerts-period-month { display: flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc; color: #334155; font-size: 0.82rem; font-weight: 600; cursor: pointer; user-select: none; transition: border-color .15s ease, background .15s ease, color .15s ease; }
        .alerts-period-month:hover { border-color: #93c5fd; background: #eff6ff; }
        .alerts-period-month.is-checked { border-color: #0b6fb6; background: #eff6ff; color: #0b6fb6; }
        .alerts-period-month input { width: 0.95rem; height: 0.95rem; margin: 0; accent-color: #0b6fb6; cursor: pointer; }
        .alerts-period-panel__footer { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; }
        .alerts-period-panel__hint { color: #64748b; font-size: 0.78rem; font-weight: 600; line-height: 1.3; }
        .alerts-period-panel__clear { padding: 0.35rem 0.65rem; border: none; border-radius: 0.45rem; background: transparent; color: #0b6fb6; font-size: 0.82rem; font-weight: 700; cursor: pointer; }
        .alerts-period-panel__clear:hover { background: #eff6ff; }
        .alerts-toolbar__filters button, .alerts-toolbar__filters a { display: inline-flex; align-items: center; justify-content: center; padding: 0 1rem; border: 1px solid #cbd5e1; background: #fff; color: #374151; font-weight: 600; text-decoration: none; }
        .alerts-toolbar__download { display: inline-flex; align-items: center; justify-content: center; min-width: 11rem; padding: 0 1.15rem; border: none; background: #cbd5e1; color: #fff; font-weight: 700; white-space: nowrap; transition: background .18s ease, box-shadow .18s ease, transform .18s ease; }
        .alerts-toolbar__download.is-ready { background: #0b6fb6; box-shadow: 0 10px 22px rgba(11, 111, 182, 0.24); }
        .alerts-toolbar__download.is-ready:hover { background: #085a93; transform: translateY(-1px); }
        .alerts-toolbar__download:hover:not(:disabled) { background: #94a3b8; }
        .alerts-toolbar__download:disabled { cursor: not-allowed; opacity: 1; }
        .alerts-toolbar__preview { display: inline-flex; align-items: center; justify-content: center; width: 3.1rem; min-width: 3.1rem; padding: 0; overflow: hidden; border: 1px solid #cbd5e1; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); color: #94a3b8; transition: background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease, box-shadow .18s ease; }
        .alerts-toolbar__preview img { width: 2.7rem; height: 2.7rem; object-fit: contain; display: block; transform: scale(1.42); transform-origin: center; transition: transform .18s ease, opacity .18s ease; opacity: .86; }
        .alerts-toolbar__preview.is-ready { border-color: #0b6fb6; background: linear-gradient(180deg, #18a3db 0%, #0b6fb6 100%); color: #ffffff; box-shadow: 0 10px 22px rgba(11, 111, 182, 0.28); }
        .alerts-toolbar__preview.is-ready img { opacity: 1; transform: scale(1.55); }
        .alerts-toolbar__preview.is-ready:hover { background: linear-gradient(180deg, #1393c6 0%, #085a93 100%); color: #ffffff; transform: translateY(-1px); }
        .alerts-toolbar__preview:disabled { cursor: not-allowed; color: #b6c1d1; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); opacity: 1; }
        .alerts-toolbar__preview:disabled img { opacity: .4; transform: scale(1.35); }
        .alerts-toolbar__bottom-group { width: min(100%, 43rem); }
        .alerts-toolbar__search { flex: 1 1 31rem; min-width: 0; }
        .alerts-toolbar__search input { width: 100%; height: 2.45rem; padding: 0 0.9rem; border: 1px solid #cbd5e1; border-radius: 0.55rem; font-size: 0.95rem; color: #374151; }
        .alerts-toolbar__count { min-width: 9.5rem; color: #111827; font-size: 0.95rem; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; }
        .alerts-overview { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .alerts-card { display: flex; flex-direction: column; gap: 0.8rem; padding: 1.2rem; border: 1px solid #dbe5f1; border-radius: 1rem; background: #fff; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08); text-align: left; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .alerts-card:hover { transform: translateY(-1px); box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12); }
        .alerts-card--expired:hover { border-color: #fca5a5; }
        .alerts-card--expiring:hover { border-color: #fcd34d; }
        .alerts-card--safe:hover { border-color: #86efac; }
        .alerts-card__eyebrow { color: #64748b; font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .alerts-card__count { color: #0f172a; font-size: 2rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; }
        .alerts-card__title { color: #111827; font-size: 1.05rem; font-weight: 700; line-height: 1.2; }
        .alerts-card__subtitle { min-height: 2.6rem; color: #64748b; font-size: .92rem; line-height: 1.4; }
        .alerts-card__action { color: #0b6fb6; font-size: .92rem; font-weight: 700; }
        .alerts-modal-layer { position: fixed; inset: var(--alerts-modal-top, 12rem) 0 0; z-index: 1050; }
        .alerts-modal-layer.hidden, .alerts-modal-panel.hidden { display: none; }
        .alerts-modal-backdrop { position: absolute; inset: 0; width: 100%; border: none; background: rgba(15, 23, 42, 0.22); }
        .alerts-modal-wrap { position: relative; height: 100%; max-width: 77rem; margin: 0 auto; padding: 1rem 1rem 1.25rem; }
        .alerts-modal-panel { display: flex; flex-direction: column; height: 100%; overflow: hidden; border: 1px solid #dbe5f1; border-radius: 1rem; background: #fff; box-shadow: 0 22px 55px rgba(15, 23, 42, 0.16); }
        .alerts-modal-panel__header { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; padding: 1rem 1.15rem; border-bottom: 1px solid #e2e8f0; }
        .alerts-modal-panel__title { margin: 0; color: #0f172a; font-size: 1.2rem; font-weight: 800; line-height: 1.1; }
        .alerts-modal-panel__subtitle { margin-top: .4rem; color: #64748b; font-size: .92rem; }
        .alerts-modal-panel__header-actions { display: flex; align-items: center; gap: .9rem; }
        .alerts-modal-panel__close { display: inline-flex; align-items: center; justify-content: center; min-width: 2.35rem; height: 2.35rem; padding: 0 .85rem; border: 1px solid #cbd5e1; border-radius: 999px; background: #fff; color: #475569; font-weight: 700; }
        .alerts-modal-panel__body { flex: 1 1 auto; overflow: auto; padding: 1rem 1.15rem 1.2rem; }
        .alerts-modal-panel__toggle { display: inline-flex; align-items: center; gap: .55rem; color: #334155; font-size: .92rem; font-weight: 600; }
        .alerts-modal-panel__toggle input { width: 1rem; height: 1rem; border-radius: .25rem; }
        .alerts-modal-panel__toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.25rem; margin-top: .5rem; }
        .alerts-modal-panel__count { font-size: .92rem; font-weight: 700; color: #334155; }
        @media (max-width: 1180px) {
            .alerts-toolbar__top { grid-template-columns: 1fr; justify-items: center; row-gap: .9rem; }
            .alerts-toolbar__title, .alerts-toolbar__back { justify-self: center; }
            .alerts-overview { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .alerts-toolbar__filters, .alerts-toolbar__bottom-group { flex-direction: column; align-items: stretch; width: 100%; }
            .alerts-toolbar__center, .alerts-toolbar__search { width: 100%; }
            .alerts-toolbar__download, .alerts-period-picker, .alerts-period-picker__toggle, .alerts-toolbar__filters button, .alerts-toolbar__filters a, .alerts-toolbar__filters label, .alerts-period-picker__label { width: 100%; justify-content: center; }
            .alerts-period-picker { width: 100%; }
            .alerts-period-panel { position: fixed; left: 50%; right: auto; top: auto; bottom: 1rem; width: min(22rem, calc(100vw - 1.25rem)); transform: translateX(-50%); }
            .alerts-modal-wrap { padding-inline: .65rem; }
        }
    </style>
@endpush

@php
    $alertMonthShortNames = [
        __('Ene'), __('Feb'), __('Mar'), __('Abr'),
        __('May'), __('Jun'), __('Jul'), __('Ago'),
        __('Sep'), __('Oct'), __('Nov'), __('Dic'),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div id="alerts-toolbar-shell" class="alerts-toolbar-shell">
            <div class="alerts-toolbar">
                <div class="alerts-toolbar__top">
                    <h2 class="alerts-toolbar__title">{{ __('Alertas Documentales') }}</h2>
                    <div class="alerts-toolbar__center">
                        <form id="alerts-filter-form" method="GET" action="{{ route('alerts.documents') }}" class="alerts-toolbar__filters">
                            <span class="alerts-period-picker__label">{{ __('Meses') }}</span>
                            <div class="alerts-period-picker">
                                <button
                                    type="button"
                                    id="alerts-period-toggle"
                                    class="alerts-period-picker__toggle @if (count($selectedMonths) > 0) has-selection @endif"
                                    aria-expanded="false"
                                    aria-controls="alerts-period-panel"
                                    aria-haspopup="dialog"
                                >
                                    <span id="alerts-period-summary" class="alerts-period-picker__summary">{{ __('Seleccionar meses') }}</span>
                                    <svg class="alerts-period-picker__chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div
                                    id="alerts-period-panel"
                                    class="alerts-period-panel hidden"
                                    role="dialog"
                                    aria-modal="true"
                                    aria-labelledby="alerts-period-year"
                                    hidden
                                >
                                    <div class="alerts-period-panel__header">
                                        <button type="button" class="alerts-period-panel__nav" data-period-year-step="-1" aria-label="{{ __('Año anterior') }}">&#8249;</button>
                                        <span id="alerts-period-year" class="alerts-period-panel__year">{{ now()->year }}</span>
                                        <button type="button" class="alerts-period-panel__nav" data-period-year-step="1" aria-label="{{ __('Año siguiente') }}">&#8250;</button>
                                    </div>
                                    <div id="alerts-period-month-grid" class="alerts-period-panel__grid"></div>
                                    <div class="alerts-period-panel__footer">
                                        <span id="alerts-period-hint" class="alerts-period-panel__hint">{{ __('Marque uno o varios meses y pulse Filtrar.') }}</span>
                                        <button type="button" id="alerts-period-clear" class="alerts-period-panel__clear">{{ __('Limpiar') }}</button>
                                    </div>
                                </div>
                            </div>
                            <div id="alerts-month-hidden-inputs" hidden>
                                @foreach ($selectedMonths as $monthValue)
                                    <input type="hidden" name="months[]" value="{{ $monthValue }}">
                                @endforeach
                            </div>
                            <button type="submit">{{ __('Filtrar') }}</button>
                            @if ($hasMonthFilter)
                                <a href="{{ route('alerts.documents') }}">{{ __('Todos') }}</a>
                            @endif
                            <button
                                id="alerts-preview-button"
                                type="submit"
                                form="alerts-download-form"
                                formaction="{{ route('alerts.documents.preview') }}"
                                formtarget="_blank"
                                class="alerts-toolbar__preview"
                                @disabled(!$previewAvailable)
                                title="{{ $previewAvailable ? __('Ver relación') : __('La vista previa PDF no está disponible') }}"
                                aria-label="{{ __('Ver relación') }}"
                            >
                                <img src="{{ asset('images/Ojo.webp') }}" alt="" aria-hidden="true">
                            </button>
                            <button id="alerts-download-button" type="submit" form="alerts-download-form" class="alerts-toolbar__download" disabled>{{ __('Descargar relación') }}</button>
                        </form>
                    </div>
                    <a href="{{ route('reports.index') }}" class="alerts-toolbar__back">{{ __('Volver') }}</a>
                </div>
                <div class="alerts-toolbar__bottom">
                    <div class="alerts-toolbar__bottom-group">
                        <div class="alerts-toolbar__search">
                            <input id="alerts-search" type="search" placeholder="{{ __('Buscar en todas las columnas...') }}">
                        </div>
                        <div id="alerts-selected-count" class="alerts-toolbar__count">0 {{ __('seleccionadas') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8" data-alerts-page>
        <div class="sj-page-shell sj-page-shell--wide space-y-6">
            <section class="alerts-overview">
                <button type="button" class="alerts-card alerts-card--expired" data-open-modal="expired">
                    <span class="alerts-card__eyebrow">{{ $monthLabel }}</span>
                    <span class="alerts-card__count">{{ $summaryCards['expired']['count'] }}</span>
                    <span class="alerts-card__title">{{ $summaryCards['expired']['label'] }}</span>
                    <span class="alerts-card__subtitle">{{ $summaryCards['expired']['subtitle'] }}</span>
                    <span class="alerts-card__action">{{ __('Abrir detalle') }}</span>
                </button>
                <button type="button" class="alerts-card alerts-card--expiring" data-open-modal="expiring">
                    <span class="alerts-card__eyebrow">{{ $monthLabel }}</span>
                    <span class="alerts-card__count">{{ $summaryCards['expiring']['count'] }}</span>
                    <span class="alerts-card__title">{{ $summaryCards['expiring']['label'] }}</span>
                    <span class="alerts-card__subtitle">{{ $summaryCards['expiring']['subtitle'] }}</span>
                    <span class="alerts-card__action">{{ __('Abrir detalle') }}</span>
                </button>
                <button type="button" class="alerts-card alerts-card--safe" data-open-modal="no_alerts">
                    <span class="alerts-card__eyebrow">{{ $monthLabel }}</span>
                    <span class="alerts-card__count">{{ $summaryCards['no_alerts']['count'] }}</span>
                    <span class="alerts-card__title">{{ $summaryCards['no_alerts']['label'] }}</span>
                    <span class="alerts-card__subtitle">{{ $summaryCards['no_alerts']['subtitle'] }}</span>
                    <span class="alerts-card__action">{{ __('Abrir detalle') }}</span>
                </button>
            </section>

            <form id="alerts-download-form" method="POST" action="{{ route('alerts.documents.download') }}">
                @csrf
                <div id="alerts-download-month-inputs">
                    @foreach ($selectedMonths as $monthValue)
                        <input type="hidden" name="months[]" value="{{ $monthValue }}">
                    @endforeach
                </div>
                <div id="alerts-modal-layer" class="alerts-modal-layer hidden" aria-hidden="true">
                    <button type="button" class="alerts-modal-backdrop" data-close-modal aria-label="{{ __('Cerrar') }}"></button>
                    <div class="alerts-modal-wrap">
                        <section class="alerts-modal-panel hidden" data-alerts-modal="expired" role="dialog" aria-modal="true" aria-labelledby="alerts-modal-title-expired">
                            <div class="alerts-modal-panel__header">
                                <div>
                                    <h3 id="alerts-modal-title-expired" class="alerts-modal-panel__title">{{ $summaryCards['expired']['label'] }}</h3>
                                    <div class="alerts-modal-panel__subtitle">{{ $summaryCards['expired']['subtitle'] }}</div>
                                    <div class="alerts-modal-panel__toolbar">
                                        <span id="expired-visible-count" class="alerts-modal-panel__count" data-alerts-visible-count data-target-body="expired-alerts-body">0 {{ __('armas en la lista') }}</span>
                                        <label class="alerts-modal-panel__toggle">
                                            <input type="checkbox" class="alerts-exclude-novedades" data-target-body="expired-alerts-body">
                                            <span>{{ __('Excluir armas no revalidables') }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="alerts-modal-panel__header-actions">
                                    <label class="alerts-modal-panel__toggle">
                                        <input type="checkbox" class="alert-select-all-toggle" data-target-body="expired-alerts-body">
                                        <span>{{ __('Seleccionar todo') }}</span>
                                    </label>
                                    <button type="button" class="alerts-modal-panel__close" data-close-modal>{{ __('Cerrar') }}</button>
                                </div>
                            </div>
                            <div class="alerts-modal-panel__body">
                                <div class="overflow-x-auto sj-table-wrap">
                                    <table class="sj-table sj-table--align-left min-w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Cliente') }}</th>
                                                <th>{{ __('Tipo') }}</th>
                                                <th>{{ __('Serie') }}</th>
                                                <th>{{ __('Vence') }}</th>
                                                <th>{{ __('Estado') }}</th>
                                                <th>{{ __('Observación') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="expired-alerts-body">
                                            @forelse ($expired as $doc)
                                                @php($alert = \App\Support\WeaponDocumentAlert::forComplianceDocument($doc))
                                                @php($excludedFromRevalidation = $doc->weapon?->isExcludedFromRevalidationDocuments() ?? false)
                                                <tr class="alert-document-row {{ $alert['row_class'] }}" data-blocking-novedad="{{ $excludedFromRevalidation ? '1' : '0' }}" data-alert-search="{{ strtolower(trim(($doc->weapon?->activeClientAssignment?->client?->name ?? 'Sin cliente') . ' ' . ($doc->weapon?->weapon_type ?? '') . ' ' . ($doc->weapon?->serial_number ?? '') . ' ' . ($doc->valid_until?->format('Y-m-d') ?? '') . ' ' . ($alert['state'] ?? '') . ' ' . ($alert['observation'] ?? ''))) }}">
                                                    <td class="px-3 py-2">
                                                        <label class="inline-flex items-center gap-2">
                                                            <input type="checkbox" name="weapon_ids[]" value="{{ $doc->weapon_id }}" class="alert-weapon-checkbox rounded border-gray-300 text-indigo-600">
                                                            <span>{{ $doc->weapon?->activeClientAssignment?->client?->name ?? __('Sin cliente') }}</span>
                                                        </label>
                                                    </td>
                                                    <td class="px-3 py-2">{{ $doc->weapon?->weapon_type ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $doc->weapon?->serial_number ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $doc->valid_until?->format('Y-m-d') }}</td>
                                                    <td class="px-3 py-2 {{ $alert['text_class'] }}">{{ $alert['state'] }}</td>
                                                    <td class="px-3 py-2 {{ $alert['text_class'] }}">{{ $alert['observation'] }}</td>
                                                </tr>
                                            @empty
                                                <tr class="alerts-empty-row"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ $summaryCards['expired']['empty'] }}</td></tr>
                                            @endforelse
                                            <tr id="expired-alerts-no-results" class="hidden"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No hay resultados con los filtros actuales.') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>

                        <section class="alerts-modal-panel hidden" data-alerts-modal="expiring" role="dialog" aria-modal="true" aria-labelledby="alerts-modal-title-expiring">
                            <div class="alerts-modal-panel__header">
                                <div>
                                    <h3 id="alerts-modal-title-expiring" class="alerts-modal-panel__title">{{ $summaryCards['expiring']['label'] }}</h3>
                                    <div class="alerts-modal-panel__subtitle">{{ $summaryCards['expiring']['subtitle'] }}</div>
                                    <div class="alerts-modal-panel__toolbar">
                                        <span id="expiring-visible-count" class="alerts-modal-panel__count" data-alerts-visible-count data-target-body="expiring-alerts-body">0 {{ __('armas en la lista') }}</span>
                                        <label class="alerts-modal-panel__toggle">
                                            <input type="checkbox" class="alerts-exclude-novedades" data-target-body="expiring-alerts-body">
                                            <span>{{ __('Excluir armas no revalidables') }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="alerts-modal-panel__header-actions">
                                    <label class="alerts-modal-panel__toggle">
                                        <input type="checkbox" class="alert-select-all-toggle" data-target-body="expiring-alerts-body">
                                        <span>{{ __('Seleccionar todo') }}</span>
                                    </label>
                                    <button type="button" class="alerts-modal-panel__close" data-close-modal>{{ __('Cerrar') }}</button>
                                </div>
                            </div>
                            <div class="alerts-modal-panel__body">
                                <div class="overflow-x-auto sj-table-wrap">
                                    <table class="sj-table sj-table--align-left min-w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Cliente') }}</th>
                                                <th>{{ __('Tipo') }}</th>
                                                <th>{{ __('Serie') }}</th>
                                                <th>{{ __('Vence') }}</th>
                                                <th>{{ __('Estado') }}</th>
                                                <th>{{ __('Observación') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="expiring-alerts-body">
                                            @forelse ($expiring as $doc)
                                                @php($alert = \App\Support\WeaponDocumentAlert::forComplianceDocument($doc))
                                                @php($excludedFromRevalidation = $doc->weapon?->isExcludedFromRevalidationDocuments() ?? false)
                                                <tr class="alert-document-row {{ $alert['row_class'] }}" data-blocking-novedad="{{ $excludedFromRevalidation ? '1' : '0' }}" data-alert-search="{{ strtolower(trim(($doc->weapon?->activeClientAssignment?->client?->name ?? 'Sin cliente') . ' ' . ($doc->weapon?->weapon_type ?? '') . ' ' . ($doc->weapon?->serial_number ?? '') . ' ' . ($doc->valid_until?->format('Y-m-d') ?? '') . ' ' . ($alert['state'] ?? '') . ' ' . ($alert['observation'] ?? ''))) }}">
                                                    <td class="px-3 py-2">
                                                        <label class="inline-flex items-center gap-2">
                                                            <input type="checkbox" name="weapon_ids[]" value="{{ $doc->weapon_id }}" class="alert-weapon-checkbox rounded border-gray-300 text-indigo-600">
                                                            <span>{{ $doc->weapon?->activeClientAssignment?->client?->name ?? __('Sin cliente') }}</span>
                                                        </label>
                                                    </td>
                                                    <td class="px-3 py-2">{{ $doc->weapon?->weapon_type ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $doc->weapon?->serial_number ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $doc->valid_until?->format('Y-m-d') }}</td>
                                                    <td class="px-3 py-2 {{ $alert['text_class'] }}">{{ $alert['state'] }}</td>
                                                    <td class="px-3 py-2 {{ $alert['text_class'] }}">{{ $alert['observation'] }}</td>
                                                </tr>
                                            @empty
                                                <tr class="alerts-empty-row"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ $summaryCards['expiring']['empty'] }}</td></tr>
                                            @endforelse
                                            <tr id="expiring-alerts-no-results" class="hidden"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No hay resultados con los filtros actuales.') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>

                        <section class="alerts-modal-panel hidden" data-alerts-modal="no_alerts" role="dialog" aria-modal="true" aria-labelledby="alerts-modal-title-no-alerts">
                            <div class="alerts-modal-panel__header">
                                <div>
                                    <h3 id="alerts-modal-title-no-alerts" class="alerts-modal-panel__title">{{ $summaryCards['no_alerts']['label'] }}</h3>
                                    <div class="alerts-modal-panel__subtitle">{{ $summaryCards['no_alerts']['subtitle'] }}</div>
                                    <div class="alerts-modal-panel__toolbar">
                                        <span id="no-alerts-visible-count" class="alerts-modal-panel__count" data-alerts-visible-count data-target-body="no-alerts-body">0 {{ __('armas en la lista') }}</span>
                                        <label class="alerts-modal-panel__toggle">
                                            <input type="checkbox" class="alerts-exclude-novedades" data-target-body="no-alerts-body">
                                            <span>{{ __('Excluir armas no revalidables') }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="alerts-modal-panel__header-actions">
                                    <label class="alerts-modal-panel__toggle">
                                        <input type="checkbox" class="alert-select-all-toggle" data-target-body="no-alerts-body">
                                        <span>{{ __('Seleccionar todo') }}</span>
                                    </label>
                                    <button type="button" class="alerts-modal-panel__close" data-close-modal>{{ __('Cerrar') }}</button>
                                </div>
                            </div>
                            <div class="alerts-modal-panel__body">
                                <div class="overflow-x-auto sj-table-wrap">
                                    <table class="sj-table sj-table--align-left min-w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Cliente') }}</th>
                                                <th>{{ __('Tipo') }}</th>
                                                <th>{{ __('Serie') }}</th>
                                                <th>{{ __('Vence') }}</th>
                                                <th>{{ __('Estado') }}</th>
                                                <th>{{ __('Observación') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="no-alerts-body">
                                            @forelse ($noAlerts as $doc)
                                                @php($excludedFromRevalidation = $doc->weapon?->isExcludedFromRevalidationDocuments() ?? false)
                                                @php($searchText = strtolower(trim(($doc->weapon?->activeClientAssignment?->client?->name ?? 'Sin cliente') . ' ' . ($doc->weapon?->weapon_type ?? '') . ' ' . ($doc->weapon?->serial_number ?? '') . ' ' . ($doc->valid_until?->format('Y-m-d') ?? '') . ' sin alerta fuera de la ventana de 120 días')))
                                                <tr class="alert-document-row" data-blocking-novedad="{{ $excludedFromRevalidation ? '1' : '0' }}" data-alert-search="{{ $searchText }}">
                                                    <td class="px-3 py-2">
                                                        <label class="inline-flex items-center gap-2">
                                                            <input type="checkbox" name="weapon_ids[]" value="{{ $doc->weapon_id }}" class="alert-weapon-checkbox rounded border-gray-300 text-indigo-600">
                                                            <span>{{ $doc->weapon?->activeClientAssignment?->client?->name ?? __('Sin cliente') }}</span>
                                                        </label>
                                                    </td>
                                                    <td class="px-3 py-2">{{ $doc->weapon?->weapon_type ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $doc->weapon?->serial_number ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $doc->valid_until?->format('Y-m-d') }}</td>
                                                    <td class="px-3 py-2 text-green-700">{{ __('Sin alerta') }}</td>
                                                    <td class="px-3 py-2 text-gray-700">{{ __('Fuera de la ventana de 120 días') }}</td>
                                                </tr>
                                            @empty
                                                <tr class="alerts-empty-row"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ $summaryCards['no_alerts']['empty'] }}</td></tr>
                                            @endforelse
                                            <tr id="no-alerts-no-results" class="hidden"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No hay resultados con los filtros actuales.') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @push('scripts')
        <script>
            (() => {
        const periodToggle = document.getElementById('alerts-period-toggle');
        const periodPanel = document.getElementById('alerts-period-panel');
        const periodSummary = document.getElementById('alerts-period-summary');
        const periodYearLabel = document.getElementById('alerts-period-year');
        const periodMonthGrid = document.getElementById('alerts-period-month-grid');
        const periodClearButton = document.getElementById('alerts-period-clear');
        const periodHint = document.getElementById('alerts-period-hint');
        const filterForm = document.getElementById('alerts-filter-form');
        const monthHiddenInputs = document.getElementById('alerts-month-hidden-inputs');
        const downloadMonthInputs = document.getElementById('alerts-download-month-inputs');
        const locale = document.documentElement.lang || 'es';

        const monthShortNames = @json($alertMonthShortNames);
        const txtSelectMonths = @json(__('Seleccionar meses'));
        const txtAllMonths = @json(__('Todos los meses'));
        const txtPeriodsSelected = @json(__('períodos seleccionados'));
        const txtPeriodHintEmpty = @json(__('Marque uno o varios meses y pulse Filtrar.'));
        const txtPeriodHintCount = @json(__('seleccionado(s). Pulse Filtrar para aplicar.'));

        const selectedMonths = new Set(@json($selectedMonths));
        let panelYear = (() => {
            if (selectedMonths.size === 0) return new Date().getFullYear();
            const sorted = [...selectedMonths].sort();
            return Number.parseInt(sorted[sorted.length - 1].split('-')[0], 10);
        })();
        let panelOpen = false;

        const formatMonthLabel = (monthValue) => {
            const [year, month] = monthValue.split('-').map((part) => Number.parseInt(part, 10));
            const date = new Date(year, month - 1, 1);
            return date.toLocaleDateString(locale, { month: 'short', year: 'numeric' });
        };

        const syncMonthHiddenInputs = (container) => {
            if (!container) return;
            container.innerHTML = '';
            [...selectedMonths].sort().forEach((monthValue) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'months[]';
                input.value = monthValue;
                container.appendChild(input);
            });
        };

        const syncDownloadMonthInputs = () => {
            syncMonthHiddenInputs(downloadMonthInputs);
        };

        const updatePeriodSummary = () => {
            if (!periodSummary || !periodToggle) return;

            const count = selectedMonths.size;
            periodToggle.classList.toggle('has-selection', count > 0);

            if (count === 0) {
                periodSummary.textContent = txtSelectMonths;
                if (periodHint) periodHint.textContent = txtPeriodHintEmpty;
                return;
            }

            if (count === 1) {
                periodSummary.textContent = formatMonthLabel([...selectedMonths][0]);
            } else if (count <= 3) {
                periodSummary.textContent = [...selectedMonths].sort().map(formatMonthLabel).join(', ');
            } else {
                periodSummary.textContent = `${count} ${txtPeriodsSelected}`;
            }

            if (periodHint) {
                periodHint.textContent = `${count} ${txtPeriodHintCount}`;
            }
        };

        const renderPeriodMonthGrid = () => {
            if (!periodMonthGrid || !periodYearLabel) return;

            periodYearLabel.textContent = String(panelYear);
            periodMonthGrid.innerHTML = '';

            for (let month = 1; month <= 12; month += 1) {
                const monthValue = `${panelYear}-${String(month).padStart(2, '0')}`;
                const label = document.createElement('label');
                label.className = 'alerts-period-month';
                if (selectedMonths.has(monthValue)) {
                    label.classList.add('is-checked');
                }

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = monthValue;
                checkbox.checked = selectedMonths.has(monthValue);
                checkbox.setAttribute('aria-label', formatMonthLabel(monthValue));

                const text = document.createElement('span');
                text.textContent = monthShortNames[month - 1] || String(month);

                label.append(checkbox, text);
                periodMonthGrid.append(label);
            }
        };

        const syncPeriodSelection = () => {
            syncMonthHiddenInputs(monthHiddenInputs);
            syncDownloadMonthInputs();
            updatePeriodSummary();
            renderPeriodMonthGrid();
        };

        const setPanelOpen = (open) => {
            panelOpen = open;
            if (!periodPanel || !periodToggle) return;

            periodPanel.classList.toggle('hidden', !open);
            periodPanel.hidden = !open;
            periodToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        periodToggle?.addEventListener('click', () => {
            setPanelOpen(!panelOpen);
        });

        periodMonthGrid?.addEventListener('change', (event) => {
            const checkbox = event.target;
            if (!(checkbox instanceof HTMLInputElement) || checkbox.type !== 'checkbox') return;

            const monthValue = checkbox.value;
            if (!/^\d{4}-\d{2}$/.test(monthValue)) return;

            if (checkbox.checked) {
                selectedMonths.add(monthValue);
            } else {
                selectedMonths.delete(monthValue);
            }

            syncPeriodSelection();
        });

        document.querySelectorAll('[data-period-year-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const step = Number.parseInt(button.getAttribute('data-period-year-step') || '0', 10);
                if (!step) return;
                panelYear += step;
                renderPeriodMonthGrid();
            });
        });

        periodClearButton?.addEventListener('click', () => {
            selectedMonths.clear();
            syncPeriodSelection();
        });

        filterForm?.addEventListener('submit', () => {
            syncMonthHiddenInputs(monthHiddenInputs);
            setPanelOpen(false);
        });

        document.addEventListener('click', (event) => {
            if (!panelOpen) return;
            const target = event.target;
            if (!(target instanceof Node)) return;
            if (periodToggle?.contains(target) || periodPanel?.contains(target)) return;
            setPanelOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && panelOpen) {
                setPanelOpen(false);
            }
        });

        syncPeriodSelection();

        const searchInput = document.getElementById('alerts-search');
        const countBadge = document.getElementById('alerts-selected-count');
        const downloadButton = document.getElementById('alerts-download-button');
        const previewButton = document.getElementById('alerts-preview-button');
        const modalLayer = document.getElementById('alerts-modal-layer');
        const modalPanels = Array.from(document.querySelectorAll('[data-alerts-modal]'));
        const openButtons = Array.from(document.querySelectorAll('[data-open-modal]'));
        const closeButtons = Array.from(document.querySelectorAll('[data-close-modal]'));
        let activeModal = null;

        const txtArmaEnLista = @json(__('arma en la lista'));
        const txtArmasEnLista = @json(__('armas en la lista'));
        const txtDescargarRelacion = @json(__('Descargar relación'));
        const txtSeleccionadas = @json(__('seleccionadas'));

        const sections = [
            { bodyId: 'expired-alerts-body', rows: () => Array.from(document.querySelectorAll('#expired-alerts-body .alert-document-row')), noResults: document.getElementById('expired-alerts-no-results'), emptyRows: () => Array.from(document.querySelectorAll('#expired-alerts-body .alerts-empty-row')), selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="expired-alerts-body"]'), excludeToggle: document.querySelector('.alerts-exclude-novedades[data-target-body="expired-alerts-body"]'), visibleCountEl: document.getElementById('expired-visible-count') },
            { bodyId: 'expiring-alerts-body', rows: () => Array.from(document.querySelectorAll('#expiring-alerts-body .alert-document-row')), noResults: document.getElementById('expiring-alerts-no-results'), emptyRows: () => Array.from(document.querySelectorAll('#expiring-alerts-body .alerts-empty-row')), selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="expiring-alerts-body"]'), excludeToggle: document.querySelector('.alerts-exclude-novedades[data-target-body="expiring-alerts-body"]'), visibleCountEl: document.getElementById('expiring-visible-count') },
            { bodyId: 'no-alerts-body', rows: () => Array.from(document.querySelectorAll('#no-alerts-body .alert-document-row')), noResults: document.getElementById('no-alerts-no-results'), emptyRows: () => Array.from(document.querySelectorAll('#no-alerts-body .alerts-empty-row')), selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="no-alerts-body"]'), excludeToggle: document.querySelector('.alerts-exclude-novedades[data-target-body="no-alerts-body"]'), visibleCountEl: document.getElementById('no-alerts-visible-count') },
        ];

        const checkboxes = () => Array.from(document.querySelectorAll('.alert-weapon-checkbox'));
        const visibleRows = (section) => section.rows().filter((row) => !row.classList.contains('hidden'));
        const visibleCheckboxes = (section) => visibleRows(section)
            .map((row) => row.querySelector('.alert-weapon-checkbox'))
            .filter((checkbox) => checkbox && !checkbox.disabled);

        const updateSelectAllState = (section) => {
            if (!section?.selectAll) return;

            const visible = visibleCheckboxes(section);
            const checked = visible.filter((checkbox) => checkbox.checked).length;

            section.selectAll.checked = visible.length > 0 && checked === visible.length;
            section.selectAll.indeterminate = checked > 0 && checked < visible.length;
            section.selectAll.disabled = visible.length === 0;
        };

        const updateAllSelectAllStates = () => {
            sections.forEach(updateSelectAllState);
        };

        const syncModalOffset = () => {
            const header = document.querySelector('.sj-page-header');
            const offset = header ? Math.ceil(header.getBoundingClientRect().bottom) : 180;
            document.documentElement.style.setProperty('--alerts-modal-top', `${offset}px`);
        };

        const updateSelectionCount = () => {
            const selected = checkboxes().filter((checkbox) => checkbox.checked && !checkbox.disabled).length;
            if (countBadge) countBadge.textContent = `${selected} ${txtSeleccionadas}`;
            if (downloadButton) {
                downloadButton.disabled = selected === 0;
                downloadButton.textContent = selected > 0 ? `${txtDescargarRelacion} (${selected})` : txtDescargarRelacion;
                downloadButton.classList.toggle('is-ready', selected > 0);
            }
            if (previewButton) {
                const canPreview = @json($previewAvailable) && selected > 0;
                previewButton.disabled = !canPreview;
                previewButton.classList.toggle('is-ready', canPreview);
            }

            updateAllSelectAllStates();
        };

        const applyFilters = () => {
            const term = (searchInput?.value || '').trim().toLowerCase();
            sections.forEach((section) => {
                const excludeNovedad = section.excludeToggle?.checked ?? false;
                const rows = section.rows();
                let visibleCount = 0;
                const hasActiveFilter = term !== '' || excludeNovedad;

                rows.forEach((row) => {
                    const haystack = row.dataset.alertSearch || row.textContent.toLowerCase();
                    const matchesSearch = term === '' || haystack.includes(term);
                    const blocking = row.dataset.blockingNovedad === '1';
                    const matchesNovedad = !excludeNovedad || !blocking;
                    const visible = matchesSearch && matchesNovedad;

                    row.classList.toggle('hidden', !visible);
                    if (visible) visibleCount += 1;

                    const checkbox = row.querySelector('.alert-weapon-checkbox');
                    if (checkbox) {
                        if (!visible) {
                            checkbox.checked = false;
                            checkbox.disabled = true;
                        } else {
                            checkbox.disabled = false;
                        }
                    }
                });

                section.emptyRows().forEach((row) => row.classList.toggle('hidden', visibleCount > 0 || term !== ''));
                if (section.noResults) {
                    const showNoResults = rows.length > 0 && visibleCount === 0 && hasActiveFilter;
                    section.noResults.classList.toggle('hidden', !showNoResults);
                }
                if (section.visibleCountEl) {
                    section.visibleCountEl.textContent = `${visibleCount} ${visibleCount === 1 ? txtArmaEnLista : txtArmasEnLista}`;
                }
                updateSelectAllState(section);
            });
            updateSelectionCount();
        };

        const openModal = (key) => {
            activeModal = key;
            syncModalOffset();
            modalLayer?.classList.remove('hidden');
            modalLayer?.setAttribute('aria-hidden', 'false');
            modalPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.alertsModal !== key));
        };

        const closeModal = () => {
            activeModal = null;
            modalLayer?.classList.add('hidden');
            modalLayer?.setAttribute('aria-hidden', 'true');
            modalPanels.forEach((panel) => panel.classList.add('hidden'));
        };

        const toggleSectionSelection = (bodyId, checked) => {
            const section = sections.find((item) => item.bodyId === bodyId);
            if (!section) return;

            visibleCheckboxes(section).forEach((checkbox) => {
                checkbox.checked = checked;
            });

            updateSelectionCount();
        };

        openButtons.forEach((button) => button.addEventListener('click', () => openModal(button.dataset.openModal)));
        closeButtons.forEach((button) => button.addEventListener('click', closeModal));
        searchInput?.addEventListener('input', applyFilters);
        document.addEventListener('change', (event) => {
            if (event.target.closest('.alert-weapon-checkbox')) {
                updateSelectionCount();
                return;
            }

            if (event.target.closest('.alert-select-all-toggle')) {
                toggleSectionSelection(event.target.dataset.targetBody, event.target.checked);
            }

            if (event.target.classList?.contains('alerts-exclude-novedades')) {
                applyFilters();
            }
        });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && activeModal) closeModal(); });
        window.addEventListener('resize', syncModalOffset);
        window.addEventListener('scroll', () => { if (activeModal) syncModalOffset(); }, { passive: true });

            syncModalOffset();
            applyFilters();
        })();
        </script>
    @endpush
</x-app-layout>
