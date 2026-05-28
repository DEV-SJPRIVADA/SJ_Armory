<x-app-layout>
    <x-slot name="header">
        <div class="weapon-header">
            <div class="weapon-header__row">
                <div class="weapon-header__intro">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ Auth::user()->isResponsible() && !Auth::user()->isAdmin() ? __('Mis armas') : __('Armamento') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __('Selecciona una fila para ver o editar. Usa la selección múltiple para exportar relaciones operativas. Las armas con novedad bloqueante se consultan desde historial y novedades.') }}
                    </p>
                </div>

                <div class="weapon-header__actions">
                    <a
                        id="weapon-view-action"
                        href="#"
                        class="weapon-toolbar-action is-disabled"
                        aria-disabled="true"
                    >
                        {{ __('Ver') }}
                    </a>

                    @if (auth()->user()?->isAdmin())
                        <a
                            id="weapon-edit-action"
                            href="#"
                            class="weapon-toolbar-action is-disabled"
                            aria-disabled="true"
                        >
                            {{ __('Editar') }}
                        </a>
                    @endif

                    @can('create', App\Models\Weapon::class)
                        <a href="{{ route('weapons.create') }}" class="weapon-header__primary-action">
                            {{ __('Nueva arma') }}
                        </a>
                    @endcan
                </div>
            </div>

            <div class="weapon-header__row weapon-header__row--bottom">
                <div class="weapon-header__search">
                    <input id="weapons-search" type="search" name="q" value="{{ $search ?? '' }}"
                        class="h-10 w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        placeholder="{{ __('Buscar por cliente, responsable, serie, marca o permiso...') }}">
                </div>

                <div class="weapon-header__tools">
                    <span id="weapons-selected-count" class="weapon-header__counter">
                        {{ __('0 seleccionadas') }}
                    </span>

                    <details id="weapons-export-menu" class="relative">
                        <summary class="weapon-header__utility list-none">
                            {{ __('Exportar') }}
                        </summary>

                        <div class="absolute right-0 z-[120] mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                            <form id="weapons-export-filtered-form" method="GET" action="{{ route('weapons.export') }}">
                                <div id="weapons-export-filtered-inputs"></div>
                                <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                    {{ __('Exportar filtrado') }}
                                </button>
                            </form>

                            <form id="weapons-export-selected-form" method="POST" action="{{ route('weapons.export.selected') }}">
                                @csrf
                                <div id="weapons-export-selected-inputs"></div>
                                <button
                                    type="submit"
                                    id="weapons-export-selected-button"
                                    class="mt-1 block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    disabled
                                >
                                    {{ __('Exportar selección') }}
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8 pb-20">
            @if (session('status'))
                <div class="mb-4 rounded-xl bg-green-50 p-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('weapon'))
                <div class="mb-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                    {{ $errors->first('weapon') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-2xl w-full border border-slate-200">
                <div class="p-6 text-gray-900">
                    @php
                        $weaponTableColumns = [
                            ['key' => 'cliente', 'label' => __('Cliente'), 'class' => 'min-w-[200px]'],
                            ['key' => 'tipo', 'label' => __('Tipo')],
                            ['key' => 'marca', 'label' => __('Marca')],
                            ['key' => 'serie', 'label' => __('Serie')],
                            ['key' => 'calibre', 'label' => __('Calibre')],
                            ['key' => 'capacidad', 'label' => __('Capacidad')],
                            ['key' => 'tipo_permiso', 'label' => __('Tipo de permiso')],
                            ['key' => 'numero_permiso', 'label' => __('N° de permiso')],
                            ['key' => 'vence', 'label' => __('Vence')],
                            ['key' => 'estado', 'label' => __('Estado')],
                            ['key' => 'municion', 'label' => __('Cant. Munición')],
                            ['key' => 'proveedor', 'label' => __('Cant. Proveedor')],
                            ['key' => 'responsable', 'label' => __('Responsable'), 'class' => 'min-w-[200px]'],
                            ['key' => 'destino', 'label' => __('Puesto o trabajador'), 'class' => 'min-w-[220px]'],
                            ['key' => 'cedula', 'label' => __('Cédula')],
                        ];
                    @endphp
                    <div id="weapons-table-scroll" class="sj-table-wrap w-full overflow-auto weapons-table-scroll relative" style="max-height: calc(100vh - 340px);">
                        <table class="sj-table sj-table--sticky-head min-w-full text-sm min-w-[2200px]">
                            <thead>
                                <tr>
                                    <th class="whitespace-nowrap">
                                        <span class="sr-only">{{ __('Seleccionar') }}</span>
                                    </th>
                                    @foreach ($weaponTableColumns as $column)
                                        <th class="whitespace-nowrap {{ $column['class'] ?? '' }}">
                                            <div class="weapon-col-filter">
                                                <span class="weapon-col-filter__label">{{ $column['label'] }}</span>
                                                <button
                                                    type="button"
                                                    class="weapon-col-filter__trigger"
                                                    data-weapon-col-filter-trigger
                                                    data-weapon-col-filter="{{ $column['key'] }}"
                                                    aria-expanded="false"
                                                    aria-haspopup="dialog"
                                                    aria-controls="weapons-column-filter-popover"
                                                    aria-label="{{ __('Filtrar columna :column', ['column' => $column['label']]) }}"
                                                >
                                                    <svg class="weapon-col-filter__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="whitespace-nowrap">{{ __('Impronta') }}</th>
                                </tr>
                            </thead>
                            <tbody id="weapons-tbody">
                                @include('weapons.partials.index_rows', ['weapons' => $weapons])
                            </tbody>
                        </table>
                    </div>

                    <div id="weapons-pagination">
                        @include('weapons.partials.index_pagination', ['weapons' => $weapons])
                    </div>

                    <button type="button" id="weapons-clear-column-filters" class="hidden mt-4 weapon-header__utility">
                        {{ __('Limpiar filtros de columna') }}
                    </button>
                </div>
            </div>

            <div
                id="weapons-column-filter-popover"
                class="weapon-col-filter-popover hidden"
                hidden
                role="dialog"
                aria-hidden="true"
                aria-label="{{ __('Filtrar valores de columna') }}"
            >
                <input type="search" class="weapon-col-filter-popover__search" data-weapon-col-filter-search placeholder="{{ __('Buscar en la lista…') }}">
                <div class="weapon-col-filter-popover__list" data-weapon-col-filter-list></div>
                <div class="weapon-col-filter-popover__actions">
                    <button type="button" class="weapon-col-filter-popover__btn weapon-col-filter-popover__btn--ghost" data-weapon-col-filter-select-all>{{ __('Seleccionar todo') }}</button>
                    <button type="button" class="weapon-col-filter-popover__btn weapon-col-filter-popover__btn--ghost" data-weapon-col-filter-clear>{{ __('Limpiar') }}</button>
                    <button type="button" class="weapon-col-filter-popover__btn weapon-col-filter-popover__btn--primary" data-weapon-col-filter-apply>{{ __('Aplicar') }}</button>
                </div>
            </div>

            <div id="weapons-scrollbar-shell" class="fixed bottom-0 z-40 pb-2 pointer-events-none">
                <div id="weapons-scrollbar" class="pointer-events-auto h-4 w-full overflow-x-scroll overflow-y-hidden">
                    <div id="weapons-scrollbar-spacer" class="h-px w-[2200px]"></div>
                </div>
            </div>

            <div
                id="weapons-export-modal"
                class="weapon-export-modal hidden"
                aria-hidden="true"
            >
                <div class="weapon-export-modal__backdrop"></div>
                <div class="weapon-export-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="weapons-export-modal-title">
                    <div class="weapon-export-modal__header">
                        <div>
                            <h3 id="weapons-export-modal-title" class="weapon-export-modal__title">{{ __('Confirmar exportación') }}</h3>
                            <p id="weapons-export-modal-description" class="weapon-export-modal__description"></p>
                        </div>
                        <button type="button" id="weapons-export-modal-close" class="weapon-export-modal__close" aria-label="{{ __('Cerrar') }}">
                            &times;
                        </button>
                    </div>

                    <div id="weapons-export-modal-warning" class="weapon-export-modal__warning hidden"></div>

                    <div class="weapon-export-modal__format">
                        <span class="weapon-export-modal__format-label">{{ __('Formato de archivo') }}</span>
                        <label class="weapon-export-modal__format-option">
                            <input type="radio" name="weapon_export_format" value="xlsx" checked>
                            <span>{{ __('Excel (.xlsx)') }}</span>
                        </label>
                        <label class="weapon-export-modal__format-option">
                            <input type="radio" name="weapon_export_format" value="csv">
                            <span>{{ __('CSV (.csv)') }}</span>
                        </label>
                    </div>

                    <div id="weapons-export-modal-table-shell" class="weapon-export-modal__table-shell sj-table-wrap hidden">
                        <table class="sj-table sj-table--align-left sj-table--compact weapon-export-modal__table">
                            <thead>
                                <tr>
                                    <th>{{ __('Cliente') }}</th>
                                    <th>{{ __('Tipo') }}</th>
                                    <th>{{ __('Marca') }}</th>
                                    <th>{{ __('Serie') }}</th>
                                    <th>{{ __('Calibre') }}</th>
                                    <th>{{ __('Tipo de permiso') }}</th>
                                    <th>{{ __('Número de permiso') }}</th>
                                    <th>{{ __('Vence') }}</th>
                                </tr>
                            </thead>
                            <tbody id="weapons-export-modal-tbody"></tbody>
                        </table>
                    </div>

                    <div class="weapon-export-modal__footer">
                        <button type="button" id="weapons-export-modal-cancel" class="weapon-export-modal__button weapon-export-modal__button--ghost">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="button" id="weapons-export-modal-edit" class="weapon-export-modal__button weapon-export-modal__button--secondary">
                            {{ __('Editar selección') }}
                        </button>
                        <button type="button" id="weapons-export-modal-confirm" class="weapon-export-modal__button weapon-export-modal__button--primary">
                            {{ __('Aceptar y descargar') }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>

<style>
    #weapons-table-scroll {
        position: relative;
        z-index: 1;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    #weapons-table-scroll::-webkit-scrollbar {
        height: 0;
    }

    #weapons-scrollbar {
        background: transparent;
        border: 0;
        box-shadow: none;
        height: 1rem;
        overflow-x: scroll;
        scrollbar-width: auto;
        -ms-overflow-style: auto;
    }

    #weapons-scrollbar-spacer {
        min-width: 100%;
    }

    .sj-page-header {
        overflow: visible;
    }

    #weapons-export-menu {
        position: relative;
        z-index: 90;
        display: block;
    }

    #weapons-export-menu[open] {
        z-index: 110;
        padding-bottom: 5.5rem;
    }

    #weapons-export-menu > summary::-webkit-details-marker {
        display: none;
    }

    .weapon-header {
        position: relative;
        z-index: 60;
        isolation: isolate;
        overflow: visible;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .weapon-header__row {
        align-items: flex-end;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
    }

    .weapon-header__row--bottom {
        align-items: flex-start;
    }

    .weapon-header__intro,
    .weapon-header__search {
        flex: 1 1 auto;
        min-width: 0;
    }

    .weapon-header__search {
        max-width: 46rem;
    }

    .weapon-header__intro p {
        display: none;
    }

    .weapon-header__actions,
    .weapon-header__tools {
        position: relative;
        z-index: 70;
        overflow: visible;
        align-items: center;
        display: flex;
        flex: 0 0 auto;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .weapon-header__tools {
        align-items: flex-start;
    }

    .weapon-header__primary-action,
    .weapon-header__utility,
    .weapon-header__counter {
        align-items: center;
        border-radius: 0.75rem;
        display: inline-flex;
        font-size: 0.875rem;
        font-weight: 600;
        height: 2.5rem;
    }

    .weapon-header__primary-action {
        background: rgb(37 99 235);
        color: #fff;
        padding: 0 1rem;
        transition: 150ms ease;
    }

    .weapon-header__primary-action:hover {
        background: rgb(29 78 216);
    }

    .weapon-header__utility,
    .weapon-header__counter {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        color: rgb(51 65 85);
        padding: 0 1rem;
    }

    .weapon-header__utility:hover {
        border-color: rgb(148 163 184);
        background: rgb(248 250 252);
    }

    .weapon-toolbar-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        border: 1px solid rgb(203 213 225);
        background: white;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: rgb(30 41 59);
        transition: 150ms ease;
    }

    .weapon-toolbar-action:hover {
        border-color: rgb(148 163 184);
        background: rgb(248 250 252);
    }

    .weapon-toolbar-action-danger {
        color: rgb(185 28 28);
    }

    .weapon-toolbar-action.is-disabled {
        pointer-events: none;
        opacity: 0.45;
    }

    .weapon-row.is-selected {
        outline: 2px solid rgb(37 99 235);
        outline-offset: -2px;
        box-shadow: inset 0 0 0 9999px rgba(219, 234, 254, 0.62);
    }

    .weapon-col-filter {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .weapon-col-filter__label {
        font-weight: 600;
        white-space: nowrap;
    }

    .weapon-col-filter__trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.15rem;
        height: 1.15rem;
        border-radius: 0.35rem;
        color: rgb(148 163 184);
        transition: all 120ms ease;
    }

    .weapon-col-filter__trigger:hover {
        color: rgb(71 85 105);
        background: rgb(226 232 240);
    }

    .weapon-col-filter__trigger.is-active {
        color: rgb(30 64 175);
        background: rgb(219 234 254);
    }

    .weapon-col-filter__icon {
        width: 0.9rem;
        height: 0.9rem;
    }

    .weapon-col-filter-popover {
        position: fixed;
        z-index: 250;
        width: 300px;
        border: 1px solid rgb(203 213 225);
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
        padding: 0.65rem;
    }

    .weapon-col-filter-popover__search {
        width: 100%;
        border: 1px solid rgb(203 213 225);
        border-radius: 0.6rem;
        padding: 0.4rem 0.55rem;
        font-size: 0.85rem;
        margin-bottom: 0.55rem;
    }

    .weapon-col-filter-popover__list {
        max-height: 260px;
        overflow: auto;
        display: grid;
        gap: 0.35rem;
        padding-right: 0.1rem;
    }

    .weapon-col-filter-option {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.85rem;
        color: rgb(51 65 85);
    }

    .weapon-col-filter-popover__empty {
        color: rgb(100 116 139);
        font-size: 0.82rem;
        margin: 0.25rem 0;
    }

    .weapon-col-filter-popover__actions {
        margin-top: 0.6rem;
        display: flex;
        gap: 0.4rem;
        justify-content: flex-end;
    }

    .weapon-col-filter-popover__btn {
        border-radius: 0.55rem;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.35rem 0.55rem;
        border: 1px solid transparent;
    }

    .weapon-col-filter-popover__btn--ghost {
        border-color: rgb(203 213 225);
        color: rgb(51 65 85);
        background: #fff;
    }

    .weapon-col-filter-popover__btn--primary {
        background: rgb(15 23 42);
        color: #fff;
    }

    .weapon-export-modal {
        inset: 0;
        position: fixed;
        z-index: 5000;
    }

    .weapon-export-modal.hidden {
        display: none;
    }

    .weapon-export-modal__backdrop {
        background: rgba(15, 23, 42, 0.48);
        inset: 0;
        position: absolute;
        z-index: 0;
    }

    .weapon-export-modal__dialog {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 1.25rem;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        display: flex;
        flex-direction: column;
        inset: 50% auto auto 50%;
        max-height: min(85vh, 52rem);
        overflow: hidden;
        position: absolute;
        transform: translate(-50%, -50%);
        width: min(92vw, 96rem);
        z-index: 1;
    }

    .weapon-export-modal__header {
        align-items: flex-start;
        border-bottom: 1px solid rgb(226 232 240);
        display: flex;
        flex-shrink: 0;
        gap: 1rem;
        justify-content: space-between;
        padding: 1.25rem 1.5rem 1rem;
    }

    .weapon-export-modal__title {
        color: rgb(15 23 42);
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
    }

    .weapon-export-modal__description {
        color: rgb(71 85 105);
        font-size: 0.95rem;
        margin: 0.35rem 0 0;
    }

    .weapon-export-modal__close {
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 9999px;
        color: rgb(100 116 139);
        cursor: pointer;
        display: inline-flex;
        font-size: 1.75rem;
        height: 2.25rem;
        justify-content: center;
        line-height: 1;
        width: 2.25rem;
    }

    .weapon-export-modal__warning {
        background: rgb(255 247 237);
        border-bottom: 1px solid rgb(254 215 170);
        color: rgb(154 52 18);
        flex-shrink: 0;
        font-size: 0.95rem;
        margin: 0;
        padding: 1rem 1.5rem;
    }

    .weapon-export-modal__format {
        align-items: center;
        display: flex;
        flex-shrink: 0;
        flex-wrap: wrap;
        gap: 0.85rem;
        padding: 1rem 1.5rem 0.25rem;
    }

    .weapon-export-modal__format-label {
        color: rgb(51 65 85);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .weapon-export-modal__format-option {
        align-items: center;
        background: rgb(248 250 252);
        border: 1px solid rgb(203 213 225);
        border-radius: 9999px;
        color: rgb(30 41 59);
        cursor: pointer;
        display: inline-flex;
        font-size: 0.9rem;
        font-weight: 600;
        gap: 0.5rem;
        padding: 0.55rem 0.95rem;
    }

    .weapon-export-modal__format-option input {
        accent-color: rgb(37 99 235);
        margin: 0;
    }

    .weapon-export-modal__table-shell {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
    }

    .weapon-export-modal__table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .weapon-export-modal__table tbody tr:nth-child(even) td {
        background: rgba(255, 255, 255, 0.28);
    }

    .weapon-export-modal__footer {
        align-items: center;
        background: #fff;
        border-top: 1px solid rgb(226 232 240);
        display: flex;
        flex-shrink: 0;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: flex-end;
        padding: 1rem 1.5rem 1.25rem;
    }

    .weapon-export-modal__button {
        align-items: center;
        border-radius: 0.9rem;
        display: inline-flex;
        font-size: 0.95rem;
        font-weight: 600;
        height: 2.75rem;
        justify-content: center;
        padding: 0 1.15rem;
        transition: 160ms ease;
    }

    .weapon-export-modal__button--ghost,
    .weapon-export-modal__button--secondary {
        background: #fff;
        border: 1px solid rgb(203 213 225);
        color: rgb(51 65 85);
    }

    .weapon-export-modal__button--ghost:hover,
    .weapon-export-modal__button--secondary:hover {
        background: rgb(248 250 252);
    }

    .weapon-export-modal__button--primary {
        background: rgb(15 23 42);
        border: 1px solid rgb(15 23 42);
        color: #fff;
    }

    .weapon-export-modal__button--primary:hover {
        background: rgb(30 41 59);
    }

    @media (max-width: 1100px) {
        .weapon-header__row {
            align-items: stretch;
            flex-direction: column;
        }

        .weapon-header__search {
            max-width: none;
        }

        .weapon-header__actions,
        .weapon-header__tools {
            justify-content: flex-start;
        }

        .weapon-export-modal__dialog {
            max-height: 86vh;
            width: min(96vw, 96rem);
        }

        .weapon-export-modal__format {
            align-items: stretch;
            flex-direction: column;
        }

        .weapon-export-modal__footer {
            justify-content: stretch;
        }

        .weapon-export-modal__button {
            width: 100%;
        }
    }
</style>

<script>
    (() => {
        const tableScroll = document.getElementById('weapons-table-scroll');
        const scrollbarShell = document.getElementById('weapons-scrollbar-shell');
        const scrollbar = document.getElementById('weapons-scrollbar');
        const spacer = document.getElementById('weapons-scrollbar-spacer');

        if (!tableScroll || !scrollbarShell || !scrollbar || !spacer) {
            return;
        }

        let syncing = false;
        let resizeObserver = null;

        const syncMetrics = () => {
            const rect = tableScroll.getBoundingClientRect();
            scrollbarShell.style.left = `${Math.max(rect.left, 0)}px`;
            scrollbarShell.style.width = `${tableScroll.clientWidth}px`;
            spacer.style.width = `${tableScroll.scrollWidth}px`;
            scrollbar.scrollLeft = tableScroll.scrollLeft;
        };

        const syncScroll = (from, to) => {
            if (syncing) {
                return;
            }

            syncing = true;
            to.scrollLeft = from.scrollLeft;
            syncing = false;
        };

        scrollbar.addEventListener('scroll', () => syncScroll(scrollbar, tableScroll));
        tableScroll.addEventListener('scroll', () => syncScroll(tableScroll, scrollbar));
        window.addEventListener('resize', syncMetrics);

        if ('ResizeObserver' in window) {
            resizeObserver = new ResizeObserver(() => syncMetrics());
            resizeObserver.observe(tableScroll);

            const table = tableScroll.querySelector('table');
            if (table) {
                resizeObserver.observe(table);
            }
        }

        window.syncWeaponsHorizontalScrollbar = syncMetrics;
        syncMetrics();
    })();
</script>
<script>
    (() => {
        const input = document.getElementById('weapons-search');
        const tbody = document.getElementById('weapons-tbody');
        const pagination = document.getElementById('weapons-pagination');
        const clearColumnFiltersBtn = document.getElementById('weapons-clear-column-filters');
        const columnFilterPopover = document.getElementById('weapons-column-filter-popover');
        const columnFilterSearch = columnFilterPopover?.querySelector('[data-weapon-col-filter-search]');
        const columnFilterList = columnFilterPopover?.querySelector('[data-weapon-col-filter-list]');
        const columnFilterSelectAllBtn = columnFilterPopover?.querySelector('[data-weapon-col-filter-select-all]');
        const columnFilterClearBtn = columnFilterPopover?.querySelector('[data-weapon-col-filter-clear]');
        const columnFilterApplyBtn = columnFilterPopover?.querySelector('[data-weapon-col-filter-apply]');
        const viewAction = document.getElementById('weapon-view-action');
        const editAction = document.getElementById('weapon-edit-action');
        const selectedCount = document.getElementById('weapons-selected-count');
        const exportFilteredForm = document.getElementById('weapons-export-filtered-form');
        const exportFilteredInputs = document.getElementById('weapons-export-filtered-inputs');
        const exportSelectedForm = document.getElementById('weapons-export-selected-form');
        const exportSelectedInputs = document.getElementById('weapons-export-selected-inputs');
        const exportSelectedButton = document.getElementById('weapons-export-selected-button');
        const exportMenu = document.getElementById('weapons-export-menu');
        const exportPreviewUrl = @json(route('weapons.export.preview'));
        const filterOptionsUrl = @json(route('weapons.filter_options'));
        const exportModal = document.getElementById('weapons-export-modal');
        const exportModalTitle = document.getElementById('weapons-export-modal-title');
        const exportModalDescription = document.getElementById('weapons-export-modal-description');
        const exportModalWarning = document.getElementById('weapons-export-modal-warning');
        const exportModalTableShell = document.getElementById('weapons-export-modal-table-shell');
        const exportModalTbody = document.getElementById('weapons-export-modal-tbody');
        const exportModalConfirm = document.getElementById('weapons-export-modal-confirm');
        const exportModalCancel = document.getElementById('weapons-export-modal-cancel');
        const exportModalEdit = document.getElementById('weapons-export-modal-edit');
        const exportModalClose = document.getElementById('weapons-export-modal-close');
        const exportModalBackdrop = exportModal?.querySelector('.weapon-export-modal__backdrop');
        const exportFormatInputs = Array.from(document.querySelectorAll('input[name="weapon_export_format"]'));

        if (!input || !tbody || !pagination || !viewAction || !selectedCount || !exportFilteredForm || !exportFilteredInputs || !exportSelectedForm || !exportSelectedInputs || !exportSelectedButton || !exportMenu || !exportModal || !exportModalTitle || !exportModalDescription || !exportModalWarning || !exportModalTableShell || !exportModalTbody || !exportModalConfirm || !exportModalCancel || !exportModalEdit || !exportModalClose || !exportModalBackdrop || exportFormatInputs.length === 0) {
            return;
        }

        const COLUMN_KEYS = ['cliente', 'tipo', 'marca', 'serie', 'calibre', 'capacidad', 'tipo_permiso', 'numero_permiso', 'vence', 'estado', 'municion', 'proveedor', 'responsable', 'destino', 'cedula'];
        const initialColumnFilters = @json($columnFilters ?? []);
        const columnFilters = Object.fromEntries(COLUMN_KEYS.map((key) => [key, new Set(initialColumnFilters[key] ?? [])]));
        const exportSelection = new Set();
        const exportSelectionData = new Map();
        let selectedWeaponId = null;
        let pendingExportForm = null;
        let openFilterContext = null;
        let draftSelection = new Set();

        const setDisabledState = (element, disabled) => {
            if (!element) return;
            element.classList.toggle('is-disabled', disabled);
            if (element.tagName === 'BUTTON') {
                element.disabled = disabled;
            } else if (disabled) {
                element.setAttribute('aria-disabled', 'true');
                element.setAttribute('tabindex', '-1');
            } else {
                element.removeAttribute('aria-disabled');
                element.removeAttribute('tabindex');
            }
        };

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        const highlight = (term) => {
            const cells = tbody.querySelectorAll('td:not([data-searchable="false"])');
            if (!term) {
                cells.forEach((cell) => {
                    if (cell.dataset.original !== undefined) {
                        cell.innerHTML = cell.dataset.original;
                    }
                });
                return;
            }
            const escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(escaped, 'gi');
            cells.forEach((cell) => {
                if (cell.dataset.original === undefined) {
                    cell.dataset.original = cell.innerHTML;
                }
                cell.innerHTML = cell.dataset.original.replace(regex, (match) => `<mark class="bg-yellow-200">${match}</mark>`);
            });
        };

        const extractWeaponSummary = (row) => ({
            id: row.dataset.weaponId,
            client: row.dataset.exportClient || '{{ __('Sin destino') }}',
            type: row.dataset.exportType || '-',
            brand: row.dataset.exportBrand || '-',
            serial: row.dataset.exportSerial || '-',
            caliber: row.dataset.exportCaliber || '-',
            permit_type: row.dataset.exportPermitType || '-',
            permit_number: row.dataset.exportPermitNumber || '-',
            expires_at: row.dataset.exportExpiresAt || '-',
        });

        const selectedExportFormat = () => exportFormatInputs.find((input) => input.checked)?.value || 'xlsx';
        const countActiveColumnFilters = () => COLUMN_KEYS.reduce((count, key) => count + (columnFilters[key].size > 0 ? 1 : 0), 0);

        const updateColumnFilterTriggerStates = () => {
            document.querySelectorAll('[data-weapon-col-filter-trigger]').forEach((trigger) => {
                const key = trigger.getAttribute('data-weapon-col-filter');
                const active = key && columnFilters[key]?.size > 0;
                trigger.classList.toggle('is-active', Boolean(active));
                trigger.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            clearColumnFiltersBtn?.classList.toggle('hidden', countActiveColumnFilters() === 0);
        };

        const appendCurrentStateToSearchParams = (params, includePage = true) => {
            const q = input.value.trim();
            if (q !== '') {
                params.set('q', q);
            }
            COLUMN_KEYS.forEach((key) => {
                columnFilters[key].forEach((value) => params.append(`col[${key}][]`, value));
            });
            if (includePage) {
                params.set('page', params.get('page') || '1');
            } else {
                params.delete('page');
            }
        };

        const applyStateToUrl = (url, { resetPage = false } = {}) => {
            const next = new URL(url.toString());
            const page = resetPage ? '1' : (next.searchParams.get('page') || '1');
            next.search = '';
            appendCurrentStateToSearchParams(next.searchParams, false);
            next.searchParams.set('page', page);
            return next;
        };

        const syncExportForms = () => {
            exportFilteredInputs.innerHTML = '';
            exportSelectedInputs.innerHTML = '';

            const q = input.value.trim();
            if (q !== '') {
                const qInput = document.createElement('input');
                qInput.type = 'hidden';
                qInput.name = 'q';
                qInput.value = q;
                exportFilteredInputs.appendChild(qInput);
            }

            COLUMN_KEYS.forEach((key) => {
                columnFilters[key].forEach((value) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = `col[${key}][]`;
                    hidden.value = value;
                    exportFilteredInputs.appendChild(hidden);
                });
            });

            const filteredFormatInput = document.createElement('input');
            filteredFormatInput.type = 'hidden';
            filteredFormatInput.name = 'format';
            filteredFormatInput.value = selectedExportFormat();
            exportFilteredInputs.appendChild(filteredFormatInput);

            Array.from(exportSelection).forEach((weaponId) => {
                const selectedInput = document.createElement('input');
                selectedInput.type = 'hidden';
                selectedInput.name = 'weapon_ids[]';
                selectedInput.value = weaponId;
                exportSelectedInputs.appendChild(selectedInput);
            });

            const qSelectedInput = document.createElement('input');
            qSelectedInput.type = 'hidden';
            qSelectedInput.name = 'q';
            qSelectedInput.value = q;
            exportSelectedInputs.appendChild(qSelectedInput);

            COLUMN_KEYS.forEach((key) => {
                columnFilters[key].forEach((value) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = `col[${key}][]`;
                    hidden.value = value;
                    exportSelectedInputs.appendChild(hidden);
                });
            });

            const selectedFormatInput = document.createElement('input');
            selectedFormatInput.type = 'hidden';
            selectedFormatInput.name = 'format';
            selectedFormatInput.value = selectedExportFormat();
            exportSelectedInputs.appendChild(selectedFormatInput);

            const count = exportSelection.size;
            selectedCount.textContent = count === 1 ? '{{ __('1 seleccionada') }}' : `${count} {{ __('seleccionadas') }}`;
            exportSelectedButton.disabled = count === 0;
        };

        const syncSelectionDetailsFromVisibleRows = () => {
            tbody.querySelectorAll('.weapon-row').forEach((row) => {
                if (exportSelection.has(row.dataset.weaponId)) {
                    exportSelectionData.set(row.dataset.weaponId, extractWeaponSummary(row));
                }
            });
        };

        const clearSelectedRow = () => {
            selectedWeaponId = null;
            tbody.querySelectorAll('.weapon-row').forEach((row) => row.classList.remove('is-selected'));
            viewAction.href = '#';
            setDisabledState(viewAction, true);
            if (editAction) {
                editAction.href = '#';
                setDisabledState(editAction, true);
            }
        };

        const setSelectedRow = (row) => {
            clearSelectedRow();
            if (!row) return;
            selectedWeaponId = row.dataset.weaponId;
            row.classList.add('is-selected');
            viewAction.href = row.dataset.showUrl;
            setDisabledState(viewAction, false);
            if (editAction) {
                editAction.href = row.dataset.editUrl;
                setDisabledState(editAction, row.dataset.canEdit !== '1');
            }
        };

        const syncExportCheckboxes = () => {
            tbody.querySelectorAll('.weapon-export-checkbox').forEach((checkbox) => {
                checkbox.checked = exportSelection.has(checkbox.value);
                checkbox.disabled = false;
            });
        };

        const closeColumnFilterPopover = () => {
            if (!columnFilterPopover) return;
            columnFilterPopover.classList.add('hidden');
            columnFilterPopover.hidden = true;
            document.querySelectorAll('[data-weapon-col-filter-trigger][aria-expanded="true"]').forEach((trigger) => {
                trigger.setAttribute('aria-expanded', 'false');
            });
            openFilterContext = null;
            draftSelection = new Set();
            if (columnFilterSearch) columnFilterSearch.value = '';
        };

        const positionColumnFilterPopover = (trigger) => {
            if (!columnFilterPopover) return;
            const rect = trigger.getBoundingClientRect();
            let left = rect.left;
            const maxLeft = window.innerWidth - 312;
            if (left > maxLeft) left = maxLeft;
            if (left < 12) left = 12;
            columnFilterPopover.style.left = `${left}px`;
            columnFilterPopover.style.top = `${rect.bottom + 6}px`;
        };

        const renderColumnFilterList = () => {
            if (!columnFilterList || !openFilterContext) return;
            const term = (columnFilterSearch?.value || '').trim().toLowerCase();
            const values = openFilterContext.values || [];
            const filteredValues = term === '' ? values : values.filter((value) => value.toLowerCase().includes(term));
            columnFilterList.innerHTML = '';

            if (filteredValues.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'weapon-col-filter-popover__empty';
                empty.textContent = '{{ __('Sin valores para mostrar.') }}';
                columnFilterList.appendChild(empty);
                return;
            }

            filteredValues.forEach((value) => {
                const label = document.createElement('label');
                label.className = 'weapon-col-filter-option';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = value;
                checkbox.checked = draftSelection.has(value);
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) draftSelection.add(value);
                    else draftSelection.delete(value);
                });
                const text = document.createElement('span');
                text.textContent = value;
                label.append(checkbox, text);
                columnFilterList.appendChild(label);
            });
        };

        const loadColumnValues = async (columnKey) => {
            const url = new URL(filterOptionsUrl, window.location.origin);
            appendCurrentStateToSearchParams(url.searchParams, false);
            url.searchParams.set('target', columnKey);
            const response = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return [];
            const data = await response.json();
            return Array.isArray(data.values) ? data.values : [];
        };

        const openColumnFilterPopover = async (trigger) => {
            const columnKey = trigger.getAttribute('data-weapon-col-filter');
            if (!columnKey || !columnFilterPopover) return;
            if (openFilterContext?.columnKey === columnKey && trigger.getAttribute('aria-expanded') === 'true') {
                closeColumnFilterPopover();
                return;
            }

            closeColumnFilterPopover();
            openFilterContext = { columnKey, trigger, values: [] };
            draftSelection = new Set(columnFilters[columnKey]);
            document.querySelectorAll('[data-weapon-col-filter-trigger]').forEach((btn) => {
                btn.setAttribute('aria-expanded', btn === trigger ? 'true' : 'false');
            });
            columnFilterPopover.classList.remove('hidden');
            columnFilterPopover.hidden = false;
            positionColumnFilterPopover(trigger);
            renderColumnFilterList();
            columnFilterSearch?.focus();

            const values = await loadColumnValues(columnKey);
            if (!openFilterContext || openFilterContext.columnKey !== columnKey) return;
            openFilterContext.values = values;
            renderColumnFilterList();
        };

        const updateList = async (url) => {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            const data = await response.json();
            tbody.innerHTML = data.tbody;
            pagination.innerHTML = data.pagination;
            clearSelectedRow();
            syncExportCheckboxes();
            syncSelectionDetailsFromVisibleRows();
            highlight(input.value.trim());
            syncExportForms();
            window.syncWeaponsHorizontalScrollbar?.();
        };

        const exportPreviewDescription = (count, type, truncated = false) => {
            const base = type === 'selected'
                ? (count === 1 ? '{{ __('Se exportará 1 arma seleccionada.') }}' : `{{ __('Se exportarán') }} ${count} {{ __('armas seleccionadas.') }}`)
                : (count === 1 ? '{{ __('Se exportará 1 arma filtrada.') }}' : `{{ __('Se exportarán') }} ${count} {{ __('armas filtradas.') }}`);
            return truncated ? `${base} {{ __('Se muestra una vista previa de las primeras 50.') }}` : base;
        };

        const closeExportModal = () => {
            exportModal.classList.add('hidden');
            exportModal.setAttribute('aria-hidden', 'true');
            pendingExportForm = null;
        };

        const renderExportPreviewRows = (items) => {
            exportModalTbody.innerHTML = items.map((item) => `
                <tr>
                    <td>${escapeHtml(item.client)}</td>
                    <td>${escapeHtml(item.type)}</td>
                    <td>${escapeHtml(item.brand)}</td>
                    <td>${escapeHtml(item.serial)}</td>
                    <td>${escapeHtml(item.caliber)}</td>
                    <td>${escapeHtml(item.permit_type)}</td>
                    <td>${escapeHtml(item.permit_number)}</td>
                    <td>${escapeHtml(item.expires_at)}</td>
                </tr>
            `).join('');
        };

        const openExportModal = ({ description, items = [], warning = '', editLabel = '{{ __('Editar selección') }}', showEdit = true, submitForm = null, defaultFormat = 'xlsx' }) => {
            exportModalTitle.textContent = '{{ __('Confirmar exportación') }}';
            exportModalDescription.textContent = description;
            exportModalWarning.textContent = warning;
            exportModalWarning.classList.toggle('hidden', warning === '');
            exportModalTableShell.classList.toggle('hidden', items.length === 0);
            exportModalEdit.classList.toggle('hidden', !showEdit);
            exportModalEdit.textContent = editLabel;
            exportFormatInputs.forEach((radio) => { radio.checked = radio.value === defaultFormat; });
            exportModalTbody.innerHTML = '';
            if (items.length > 0) renderExportPreviewRows(items);
            pendingExportForm = submitForm;
            exportModal.classList.remove('hidden');
            exportModal.setAttribute('aria-hidden', 'false');
        };

        let timer = null;
        input.addEventListener('input', () => {
            const url = applyStateToUrl(new URL(window.location.href), { resetPage: true });
            window.history.replaceState({}, '', url.toString());
            if (timer) clearTimeout(timer);
            timer = setTimeout(() => updateList(url.toString()), 300);
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;
            const trigger = target.closest('[data-weapon-col-filter-trigger]');
            if (trigger) {
                event.preventDefault();
                event.stopPropagation();
                openColumnFilterPopover(trigger);
                return;
            }
            if (columnFilterPopover && !columnFilterPopover.contains(target)) {
                closeColumnFilterPopover();
            }
        });

        columnFilterSearch?.addEventListener('input', renderColumnFilterList);
        columnFilterSelectAllBtn?.addEventListener('click', () => {
            if (!openFilterContext) return;
            const term = (columnFilterSearch?.value || '').trim().toLowerCase();
            const values = openFilterContext.values || [];
            const filteredValues = term === '' ? values : values.filter((value) => value.toLowerCase().includes(term));
            filteredValues.forEach((value) => draftSelection.add(value));
            renderColumnFilterList();
        });
        columnFilterClearBtn?.addEventListener('click', () => {
            draftSelection.clear();
            renderColumnFilterList();
        });
        columnFilterApplyBtn?.addEventListener('click', async () => {
            if (!openFilterContext) return;
            columnFilters[openFilterContext.columnKey] = new Set(draftSelection);
            closeColumnFilterPopover();
            updateColumnFilterTriggerStates();
            syncExportForms();
            const url = applyStateToUrl(new URL(window.location.href), { resetPage: true });
            window.history.replaceState({}, '', url.toString());
            await updateList(url.toString());
        });
        clearColumnFiltersBtn?.addEventListener('click', async () => {
            COLUMN_KEYS.forEach((key) => columnFilters[key].clear());
            closeColumnFilterPopover();
            updateColumnFilterTriggerStates();
            syncExportForms();
            const url = applyStateToUrl(new URL(window.location.href), { resetPage: true });
            window.history.replaceState({}, '', url.toString());
            await updateList(url.toString());
        });

        pagination.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) return;
            event.preventDefault();
            const url = applyStateToUrl(new URL(link.href), { resetPage: false });
            window.history.replaceState({}, '', url.toString());
            updateList(url.toString());
        });

        tbody.addEventListener('click', (event) => {
            const row = event.target.closest('.weapon-row');
            if (!row) return;
            if (event.target.closest('.weapon-export-checkbox, .imprint-checkbox, button, a, label')) return;
            if (selectedWeaponId === row.dataset.weaponId) {
                clearSelectedRow();
                return;
            }
            setSelectedRow(row);
        });

        tbody.addEventListener('change', (event) => {
            const exportCheckbox = event.target.closest('.weapon-export-checkbox');
            if (exportCheckbox) {
                const row = exportCheckbox.closest('.weapon-row');
                if (exportCheckbox.checked) {
                    exportSelection.add(exportCheckbox.value);
                    if (row) exportSelectionData.set(exportCheckbox.value, extractWeaponSummary(row));
                } else {
                    exportSelection.delete(exportCheckbox.value);
                    exportSelectionData.delete(exportCheckbox.value);
                }
                syncExportForms();
                return;
            }

            const imprintCheckbox = event.target.closest('.imprint-checkbox');
            if (!imprintCheckbox) return;
            const form = imprintCheckbox.closest('form');
            if (form) {
                const tableScroll = document.getElementById('weapons-table-scroll');
                sessionStorage.setItem('weaponsScrollTop', String(window.scrollY || 0));
                sessionStorage.setItem('weaponsTableScrollLeft', String(tableScroll?.scrollLeft || 0));
                form.submit();
            }
        });

        exportFilteredForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            syncExportForms();
            exportMenu.removeAttribute('open');
            const previewUrl = new URL(exportPreviewUrl, window.location.origin);
            appendCurrentStateToSearchParams(previewUrl.searchParams, false);
            const response = await fetch(previewUrl.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            const data = await response.json();
            if (data.has_filters) {
                openExportModal({
                    description: exportPreviewDescription(data.count, 'filtered', data.truncated),
                    items: Array.isArray(data.items) ? data.items : [],
                    editLabel: '{{ __('Editar filtros') }}',
                    submitForm: exportFilteredForm,
                });
                return;
            }
            openExportModal({
                description: `{{ __('Se descargarán') }} ${data.count} ${data.count === 1 ? '{{ __('arma') }}' : '{{ __('armas') }}'}.`,
                warning: '{{ __('Vas a exportar todas las armas.') }}',
                showEdit: false,
                submitForm: exportFilteredForm,
            });
        });

        exportSelectedForm.addEventListener('submit', (event) => {
            event.preventDefault();
            syncExportForms();
            if (exportSelection.size === 0) return;
            const items = Array.from(exportSelection).map((id) => exportSelectionData.get(id)).filter(Boolean);
            exportMenu.removeAttribute('open');
            openExportModal({
                description: exportPreviewDescription(exportSelection.size, 'selected'),
                items,
                editLabel: '{{ __('Editar selección') }}',
                submitForm: exportSelectedForm,
            });
        });

        exportModalConfirm.addEventListener('click', () => {
            if (!pendingExportForm) return;
            const formToSubmit = pendingExportForm;
            syncExportForms();
            closeExportModal();
            HTMLFormElement.prototype.submit.call(formToSubmit);
        });

        exportModalCancel.addEventListener('click', closeExportModal);
        exportModalEdit.addEventListener('click', closeExportModal);
        exportModalClose.addEventListener('click', closeExportModal);
        exportModalBackdrop.addEventListener('click', closeExportModal);

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            if (openFilterContext) {
                closeColumnFilterPopover();
                return;
            }
            if (!exportModal.classList.contains('hidden')) {
                closeExportModal();
            }
        });

        if (input.value.trim() !== '') highlight(input.value.trim());
        syncSelectionDetailsFromVisibleRows();
        syncExportForms();
        clearSelectedRow();
        syncExportCheckboxes();
        updateColumnFilterTriggerStates();

        window.addEventListener('resize', () => {
            if (openFilterContext?.trigger) positionColumnFilterPopover(openFilterContext.trigger);
        });
        window.addEventListener('scroll', () => {
            if (openFilterContext?.trigger) positionColumnFilterPopover(openFilterContext.trigger);
        }, { passive: true });
    })();
</script>

<script>
    (() => {
        const tableScroll = document.getElementById('weapons-table-scroll');
        const fakeScrollbar = document.getElementById('weapons-scrollbar');
        const scrollTop = sessionStorage.getItem('weaponsScrollTop');
        const tableLeft = sessionStorage.getItem('weaponsTableScrollLeft');

        if (scrollTop !== null) {
            window.scrollTo(0, Number(scrollTop));
            sessionStorage.removeItem('weaponsScrollTop');
        }
        if (tableScroll && tableLeft !== null) {
            tableScroll.scrollLeft = Number(tableLeft);
            if (fakeScrollbar) {
                fakeScrollbar.scrollLeft = Number(tableLeft);
            }
            sessionStorage.removeItem('weaponsTableScrollLeft');
        }
        window.syncWeaponsHorizontalScrollbar?.();
    })();
</script>
