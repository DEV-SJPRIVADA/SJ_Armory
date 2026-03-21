@push('styles')
    <style>
        .sj-page-header { position: sticky; top: 4rem; z-index: 1100; }
        .alerts-toolbar-shell { max-width: 68rem; margin: 0 auto; width: 100%; }
        .alerts-toolbar { display: flex; flex-direction: column; gap: 1rem; width: 100%; }
        .alerts-toolbar__top { display: grid; align-items: center; column-gap: 2.5rem; grid-template-columns: minmax(18rem, 1fr) auto minmax(7rem, 1fr); }
        .alerts-toolbar__title { margin: 0; color: #111827; font-size: 1.12rem; font-weight: 800; letter-spacing: -0.01em; line-height: 1; white-space: nowrap; }
        .alerts-toolbar__center, .alerts-toolbar__bottom { display: flex; justify-content: center; min-width: 0; }
        .alerts-toolbar__filters, .alerts-toolbar__bottom-group { display: flex; align-items: center; justify-content: center; gap: 1rem; min-width: 0; }
        .alerts-toolbar__filters { gap: 0.75rem; width: max-content; margin: 0 !important; }
        .alerts-toolbar__filters label, .alerts-toolbar__back { color: #374151; font-size: 0.95rem; font-weight: 600; line-height: 1; white-space: nowrap; }
        .alerts-toolbar__back { color: #6b7280; justify-self: end; }
        .alerts-toolbar__filters input, .alerts-toolbar__filters button, .alerts-toolbar__filters a, .alerts-toolbar__download, .alerts-toolbar__preview { height: 2.55rem; border-radius: 0.55rem; font-size: 0.95rem; margin-top: 0 !important; box-sizing: border-box; }
        .alerts-toolbar__month { min-width: 12.75rem; padding: 0 0.9rem; border: 1px solid #cbd5e1; background: #fff; color: #111827; }
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
        @media (max-width: 1180px) {
            .alerts-toolbar__top { grid-template-columns: 1fr; justify-items: center; row-gap: .9rem; }
            .alerts-toolbar__title, .alerts-toolbar__back { justify-self: center; }
            .alerts-overview { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .alerts-toolbar__filters, .alerts-toolbar__bottom-group { flex-direction: column; align-items: stretch; width: 100%; }
            .alerts-toolbar__center, .alerts-toolbar__search { width: 100%; }
            .alerts-toolbar__download, .alerts-toolbar__month, .alerts-toolbar__filters button, .alerts-toolbar__filters a, .alerts-toolbar__filters label { width: 100%; justify-content: center; }
            .alerts-modal-wrap { padding-inline: .65rem; }
        }
    </style>
@endpush

<x-app-layout>
    <x-slot name="header">
        <div id="alerts-toolbar-shell" class="alerts-toolbar-shell">
            <div class="alerts-toolbar">
                <div class="alerts-toolbar__top">
                    <h2 class="alerts-toolbar__title">{{ __('Alertas Documentales') }}</h2>
                    <div class="alerts-toolbar__center">
                        <form method="GET" class="alerts-toolbar__filters">
                            <label for="alerts-month">{{ __('Mes') }}</label>
                            <input id="alerts-month" type="month" name="month" value="{{ $selectedMonth }}" class="alerts-toolbar__month">
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
                            <button id="alerts-download-button" type="submit" form="alerts-download-form" class="alerts-toolbar__download" disabled>{{ __('Descargar relacion') }}</button>
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
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
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
                <div id="alerts-modal-layer" class="alerts-modal-layer hidden" aria-hidden="true">
                    <button type="button" class="alerts-modal-backdrop" data-close-modal aria-label="{{ __('Cerrar') }}"></button>
                    <div class="alerts-modal-wrap">
                        <section class="alerts-modal-panel hidden" data-alerts-modal="expired" role="dialog" aria-modal="true" aria-labelledby="alerts-modal-title-expired">
                            <div class="alerts-modal-panel__header">
                                <div>
                                    <h3 id="alerts-modal-title-expired" class="alerts-modal-panel__title">{{ $summaryCards['expired']['label'] }}</h3>
                                    <div class="alerts-modal-panel__subtitle">{{ $summaryCards['expired']['subtitle'] }}</div>
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
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Tipo') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Serie') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Vence') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Estado') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Observación') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="expired-alerts-body" class="divide-y divide-gray-200">
                                            @forelse ($expired as $doc)
                                                @php($alert = \App\Support\WeaponDocumentAlert::forComplianceDocument($doc))
                                                <tr class="alert-document-row {{ $alert['row_class'] }}" data-alert-search="{{ strtolower(trim(($doc->weapon?->activeClientAssignment?->client?->name ?? 'Sin cliente') . ' ' . ($doc->weapon?->weapon_type ?? '') . ' ' . ($doc->weapon?->serial_number ?? '') . ' ' . ($doc->valid_until?->format('Y-m-d') ?? '') . ' ' . ($alert['state'] ?? '') . ' ' . ($alert['observation'] ?? ''))) }}">
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
                                            <tr id="expired-alerts-no-results" class="hidden"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No hay resultados para la busqueda actual.') }}</td></tr>
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
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Tipo') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Serie') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Vence') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Estado') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Observación') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="expiring-alerts-body" class="divide-y divide-gray-200">
                                            @forelse ($expiring as $doc)
                                                @php($alert = \App\Support\WeaponDocumentAlert::forComplianceDocument($doc))
                                                <tr class="alert-document-row {{ $alert['row_class'] }}" data-alert-search="{{ strtolower(trim(($doc->weapon?->activeClientAssignment?->client?->name ?? 'Sin cliente') . ' ' . ($doc->weapon?->weapon_type ?? '') . ' ' . ($doc->weapon?->serial_number ?? '') . ' ' . ($doc->valid_until?->format('Y-m-d') ?? '') . ' ' . ($alert['state'] ?? '') . ' ' . ($alert['observation'] ?? ''))) }}">
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
                                            <tr id="expiring-alerts-no-results" class="hidden"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No hay resultados para la busqueda actual.') }}</td></tr>
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
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Tipo') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Serie') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Vence') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Estado') }}</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Observación') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="no-alerts-body" class="divide-y divide-gray-200">
                                            @forelse ($noAlerts as $doc)
                                                @php($searchText = strtolower(trim(($doc->weapon?->activeClientAssignment?->client?->name ?? 'Sin cliente') . ' ' . ($doc->weapon?->weapon_type ?? '') . ' ' . ($doc->weapon?->serial_number ?? '') . ' ' . ($doc->valid_until?->format('Y-m-d') ?? '') . ' sin alerta fuera de la ventana de 120 dias')))
                                                <tr class="alert-document-row" data-alert-search="{{ $searchText }}">
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
                                                    <td class="px-3 py-2 text-gray-700">{{ __('Fuera de la ventana de 120 dias') }}</td>
                                                </tr>
                                            @empty
                                                <tr class="alerts-empty-row"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ $summaryCards['no_alerts']['empty'] }}</td></tr>
                                            @endforelse
                                            <tr id="no-alerts-no-results" class="hidden"><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No hay resultados para la busqueda actual.') }}</td></tr>
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
        const searchInput = document.getElementById('alerts-search');
        const countBadge = document.getElementById('alerts-selected-count');
        const downloadButton = document.getElementById('alerts-download-button');
        const previewButton = document.getElementById('alerts-preview-button');
        const modalLayer = document.getElementById('alerts-modal-layer');
        const modalPanels = Array.from(document.querySelectorAll('[data-alerts-modal]'));
        const openButtons = Array.from(document.querySelectorAll('[data-open-modal]'));
        const closeButtons = Array.from(document.querySelectorAll('[data-close-modal]'));
        let activeModal = null;

        const sections = [
            { bodyId: 'expired-alerts-body', rows: () => Array.from(document.querySelectorAll('#expired-alerts-body .alert-document-row')), noResults: document.getElementById('expired-alerts-no-results'), emptyRows: () => Array.from(document.querySelectorAll('#expired-alerts-body .alerts-empty-row')), selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="expired-alerts-body"]') },
            { bodyId: 'expiring-alerts-body', rows: () => Array.from(document.querySelectorAll('#expiring-alerts-body .alert-document-row')), noResults: document.getElementById('expiring-alerts-no-results'), emptyRows: () => Array.from(document.querySelectorAll('#expiring-alerts-body .alerts-empty-row')), selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="expiring-alerts-body"]') },
            { bodyId: 'no-alerts-body', rows: () => Array.from(document.querySelectorAll('#no-alerts-body .alert-document-row')), noResults: document.getElementById('no-alerts-no-results'), emptyRows: () => Array.from(document.querySelectorAll('#no-alerts-body .alerts-empty-row')), selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="no-alerts-body"]') },
        ];

        const checkboxes = () => Array.from(document.querySelectorAll('.alert-weapon-checkbox'));
        const visibleRows = (section) => section.rows().filter((row) => !row.classList.contains('hidden'));
        const visibleCheckboxes = (section) => visibleRows(section)
            .map((row) => row.querySelector('.alert-weapon-checkbox'))
            .filter(Boolean);

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
            const selected = checkboxes().filter((checkbox) => checkbox.checked).length;
            if (countBadge) countBadge.textContent = `${selected} seleccionadas`;
            if (downloadButton) {
                downloadButton.disabled = selected === 0;
                downloadButton.textContent = selected > 0 ? `Descargar relacion (${selected})` : 'Descargar relacion';
                downloadButton.classList.toggle('is-ready', selected > 0);
            }
            if (previewButton) {
                const canPreview = @json($previewAvailable) && selected > 0;
                previewButton.disabled = !canPreview;
                previewButton.classList.toggle('is-ready', canPreview);
            }

            updateAllSelectAllStates();
        };

        const updateSearch = () => {
            const term = (searchInput?.value || '').trim().toLowerCase();
            sections.forEach((section) => {
                const rows = section.rows();
                let visibleCount = 0;
                rows.forEach((row) => {
                    const haystack = row.dataset.alertSearch || row.textContent.toLowerCase();
                    const matches = term === '' || haystack.includes(term);
                    row.classList.toggle('hidden', !matches);
                    if (matches) visibleCount += 1;
                });
                section.emptyRows().forEach((row) => row.classList.toggle('hidden', visibleCount > 0 || term !== ''));
                if (section.noResults) section.noResults.classList.toggle('hidden', term === '' || visibleCount > 0 || rows.length === 0);
                updateSelectAllState(section);
            });
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
        searchInput?.addEventListener('input', updateSearch);
        document.addEventListener('change', (event) => {
            if (event.target.closest('.alert-weapon-checkbox')) {
                updateSelectionCount();
                return;
            }

            if (event.target.closest('.alert-select-all-toggle')) {
                toggleSectionSelection(event.target.dataset.targetBody, event.target.checked);
            }
        });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && activeModal) closeModal(); });
        window.addEventListener('resize', syncModalOffset);
        window.addEventListener('scroll', () => { if (activeModal) syncModalOffset(); }, { passive: true });

            syncModalOffset();
            updateSearch();
            updateSelectionCount();
        })();
        </script>
    @endpush
</x-app-layout>
