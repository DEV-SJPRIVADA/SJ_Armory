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

                    <button
                        type="button"
                        id="weapons-filters-toggle"
                        class="weapon-header__utility"
                    >
                        {{ __('Filtros') }}
                    </button>

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
            @php
                $hasWeaponFilters = collect($filters)->reject(function ($value, $key) {
                    if ($key === 'inventory_scope') {
                        return ($value ?? 'operational') === 'operational';
                    }

                    return empty($value);
                })->isNotEmpty();
            @endphp

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

            <div
                id="weapons-filters-panel"
                class="{{ $hasWeaponFilters ? '' : 'hidden' }} mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('Filtros') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Refina el listado sin cargar el encabezado con controles permanentes.') }}</p>
                    </div>
                </div>

                <form id="weapons-filters-form" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <div>
                        <label for="filter-inventory-scope" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Inventario') }}</label>
                        <select id="filter-inventory-scope" name="inventory_scope" class="w-full rounded-xl border-slate-300 text-sm shadow-sm">
                            <option value="operational" @selected(($filters['inventory_scope'] ?? 'operational') === 'operational')>{{ __('Operativas') }}</option>
                            <option value="all" @selected(($filters['inventory_scope'] ?? null) === 'all')>{{ __('Todas') }}</option>
                            <option value="non_operational" @selected(($filters['inventory_scope'] ?? null) === 'non_operational')>{{ __('No operativas') }}</option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label for="filter-client" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Cliente') }}</label>
                        <select id="filter-client" name="client_id" class="w-full rounded-xl border-slate-300 text-sm shadow-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected(($filters['client_id'] ?? null) === $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label for="filter-responsible" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Responsable') }}</label>
                        <select id="filter-responsible" name="responsible_user_id" class="w-full rounded-xl border-slate-300 text-sm shadow-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($responsibles as $responsible)
                                <option value="{{ $responsible->id }}" @selected(($filters['responsible_user_id'] ?? null) === $responsible->id)>{{ $responsible->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter-weapon-type" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tipo') }}</label>
                        <select id="filter-weapon-type" name="weapon_type" class="w-full rounded-xl border-slate-300 text-sm shadow-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($weaponTypes as $weaponType)
                                <option value="{{ $weaponType }}" @selected(($filters['weapon_type'] ?? null) === $weaponType)>{{ $weaponType }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter-destination" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Destino') }}</label>
                        <select id="filter-destination" name="destination" class="w-full rounded-xl border-slate-300 text-sm shadow-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($destinationOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['destination'] ?? null) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter-permit-from" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Vence desde') }}</label>
                        <input id="filter-permit-from" type="date" name="permit_expires_from" value="{{ $filters['permit_expires_from'] ?? '' }}" class="w-full rounded-xl border-slate-300 text-sm shadow-sm">
                    </div>

                    <div>
                        <label for="filter-permit-to" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Vence hasta') }}</label>
                        <input id="filter-permit-to" type="date" name="permit_expires_to" value="{{ $filters['permit_expires_to'] ?? '' }}" class="w-full rounded-xl border-slate-300 text-sm shadow-sm">
                    </div>

                    <div class="md:col-span-2 xl:col-span-6 flex flex-wrap items-center justify-end gap-2 pt-2">
                        <button type="button" id="weapons-filters-reset" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            {{ __('Limpiar filtros') }}
                        </button>
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                            {{ __('Aplicar filtros') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-2xl w-full border border-slate-200">
                <div class="p-6 text-gray-900">
                    <div id="weapons-table-scroll" class="w-full overflow-auto weapons-table-scroll relative" style="max-height: calc(100vh - 340px);">
                        <table class="min-w-full divide-y divide-gray-200 text-sm min-w-[2200px]">
                            <thead class="bg-gray-50 sticky top-0 z-20">
                                <tr>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">
                                        <span class="sr-only">{{ __('Seleccionar') }}</span>
                                    </th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 min-w-[200px] whitespace-nowrap bg-gray-50">{{ __('Cliente') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Tipo') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Marca') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Serie') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Calibre') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Capacidad') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Tipo de permiso') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('N° de permiso') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Vence') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Estado') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">
                                        <span class="block leading-tight">{{ __('Cant.') }}</span>
                                        <span class="block leading-tight">{{ __('Munición') }}</span>
                                    </th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">
                                        <span class="block leading-tight">{{ __('Cant.') }}</span>
                                        <span class="block leading-tight">{{ __('Proveedor') }}</span>
                                    </th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 min-w-[200px] whitespace-nowrap bg-gray-50">{{ __('Responsable') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 min-w-[220px] whitespace-nowrap bg-gray-50">{{ __('Puesto o trabajador') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Cédula') }}</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-600 whitespace-nowrap bg-gray-50">{{ __('Impronta') }}</th>
                                </tr>
                            </thead>
                            <tbody id="weapons-tbody" class="divide-y divide-gray-200">
                                @include('weapons.partials.index_rows', ['weapons' => $weapons])
                            </tbody>
                        </table>
                    </div>

                    <div id="weapons-pagination">
                        @include('weapons.partials.index_pagination', ['weapons' => $weapons])
                    </div>
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

                    <div id="weapons-export-modal-table-shell" class="weapon-export-modal__table-shell hidden">
                        <table class="weapon-export-modal__table">
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

    .weapon-export-modal {
        inset: 0;
        position: fixed;
        z-index: 180;
    }

    .weapon-export-modal.hidden {
        display: none;
    }

    .weapon-export-modal__backdrop {
        background: rgba(15, 23, 42, 0.48);
        inset: 0;
        position: absolute;
    }

    .weapon-export-modal__dialog {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 1.25rem;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        inset: 50% auto auto 50%;
        max-height: min(80vh, 52rem);
        overflow: hidden;
        position: absolute;
        transform: translate(-50%, -50%);
        width: min(92vw, 96rem);
    }

    .weapon-export-modal__header {
        align-items: flex-start;
        border-bottom: 1px solid rgb(226 232 240);
        display: flex;
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
        font-size: 0.95rem;
        margin: 0;
        padding: 1rem 1.5rem;
    }

    .weapon-export-modal__format {
        align-items: center;
        display: flex;
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
        max-height: 28rem;
        overflow: auto;
    }

    .weapon-export-modal__table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 100%;
        width: 100%;
    }

    .weapon-export-modal__table thead th {
        background: linear-gradient(180deg, #223469 0%, #162457 100%);
        color: rgb(239 246 255);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        padding: 0.85rem 1rem;
        position: sticky;
        text-align: left;
        text-transform: uppercase;
        top: 0;
        white-space: nowrap;
        z-index: 1;
    }

    .weapon-export-modal__table tbody td {
        border-bottom: 1px solid rgb(226 232 240);
        color: rgb(30 41 59);
        font-size: 0.9rem;
        padding: 0.85rem 1rem;
        vertical-align: top;
    }

    .weapon-export-modal__table tbody tr:nth-child(even) td {
        background: rgb(248 250 252);
    }

    .weapon-export-modal__footer {
        align-items: center;
        border-top: 1px solid rgb(226 232 240);
        display: flex;
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
        const filtersForm = document.getElementById('weapons-filters-form');
        const filtersPanel = document.getElementById('weapons-filters-panel');
        const filtersToggle = document.getElementById('weapons-filters-toggle');
        const filtersReset = document.getElementById('weapons-filters-reset');
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

        if (!input || !tbody || !pagination || !filtersForm || !filtersPanel || !filtersToggle || !filtersReset || !viewAction || !selectedCount || !exportFilteredForm || !exportFilteredInputs || !exportSelectedForm || !exportSelectedInputs || !exportSelectedButton || !exportMenu || !exportModal || !exportModalTitle || !exportModalDescription || !exportModalWarning || !exportModalTableShell || !exportModalTbody || !exportModalConfirm || !exportModalCancel || !exportModalEdit || !exportModalClose || !exportModalBackdrop || exportFormatInputs.length === 0) {
            return;
        }

        const filterFieldNames = ['inventory_scope', 'client_id', 'responsible_user_id', 'weapon_type', 'destination', 'permit_expires_from', 'permit_expires_to'];
        const exportSelection = new Set();
        const exportSelectionData = new Map();
        let selectedWeaponId = null;
        let pendingExportForm = null;

        const setDisabledState = (element, disabled) => {
            if (!element) {
                return;
            }

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
                const text = cell.dataset.original;
                cell.innerHTML = text.replace(regex, (match) => `<mark class="bg-yellow-200">${match}</mark>`);
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

        const currentState = () => {
            const data = { q: input.value.trim() };

            filterFieldNames.forEach((name) => {
                const field = filtersForm.elements.namedItem(name);
                data[name] = field ? field.value.trim() : '';
            });

            return data;
        };

        const selectedExportFormat = () => exportFormatInputs.find((input) => input.checked)?.value || 'xlsx';

        const applyStateToUrl = (url, { resetPage = false } = {}) => {
            const state = currentState();
            const page = resetPage ? '1' : (url.searchParams.get('page') || '1');

            url.search = '';

            Object.entries(state).forEach(([key, value]) => {
                if (value !== '') {
                    url.searchParams.set(key, value);
                }
            });

            url.searchParams.set('page', page);
        };

        const syncExportForms = () => {
            const state = currentState();
            exportFilteredInputs.innerHTML = '';
            exportSelectedInputs.innerHTML = '';

            Object.entries(state).forEach(([key, value]) => {
                if (value === '') {
                    return;
                }

                const filteredInput = document.createElement('input');
                filteredInput.type = 'hidden';
                filteredInput.name = key;
                filteredInput.value = value;
                exportFilteredInputs.appendChild(filteredInput);
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

            const selectedFormatInput = document.createElement('input');
            selectedFormatInput.type = 'hidden';
            selectedFormatInput.name = 'format';
            selectedFormatInput.value = selectedExportFormat();
            exportSelectedInputs.appendChild(selectedFormatInput);

            const count = exportSelection.size;
            selectedCount.textContent = count === 1
                ? '{{ __('1 seleccionada') }}'
                : `${count} {{ __('seleccionadas') }}`;
            exportSelectedButton.disabled = count === 0;
        };

        const syncSelectionDetailsFromVisibleRows = () => {
            tbody.querySelectorAll('.weapon-row').forEach((row) => {
                if (exportSelection.has(row.dataset.weaponId)) {
                    exportSelectionData.set(row.dataset.weaponId, extractWeaponSummary(row));
                }
            });
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

        const openExportModal = ({
            description,
            items = [],
            warning = '',
            editLabel = '{{ __('Editar selección') }}',
            showEdit = true,
            submitForm = null,
            defaultFormat = 'xlsx',
        }) => {
            exportModalTitle.textContent = '{{ __('Confirmar exportación') }}';
            exportModalDescription.textContent = description;
            exportModalWarning.textContent = warning;
            exportModalWarning.classList.toggle('hidden', warning === '');
            exportModalTableShell.classList.toggle('hidden', items.length === 0);
            exportModalEdit.classList.toggle('hidden', !showEdit);
            exportModalEdit.textContent = editLabel;
            exportFormatInputs.forEach((input) => {
                input.checked = input.value === defaultFormat;
            });

            if (items.length > 0) {
                renderExportPreviewRows(items);
            } else {
                exportModalTbody.innerHTML = '';
            }

            pendingExportForm = submitForm;
            exportModal.classList.remove('hidden');
            exportModal.setAttribute('aria-hidden', 'false');
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
            if (!row) {
                return;
            }

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
            });
        };

        const exportPreviewDescription = (count, type, truncated = false) => {
            const base = type === 'selected'
                ? (count === 1
                    ? '{{ __('Se exportará 1 arma seleccionada.') }}'
                    : `{{ __('Se exportarán') }} ${count} {{ __('armas seleccionadas.') }}`)
                : (count === 1
                    ? '{{ __('Se exportará 1 arma filtrada.') }}'
                    : `{{ __('Se exportarán') }} ${count} {{ __('armas filtradas.') }}`);

            if (!truncated) {
                return base;
            }

            return `${base} {{ __('Se muestra una vista previa de las primeras 50.') }}`;
        };

        const updateList = async (url) => {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                return;
            }

            const data = await response.json();
            tbody.innerHTML = data.tbody;
            pagination.innerHTML = data.pagination;
            clearSelectedRow();
            syncExportCheckboxes();
            syncSelectionDetailsFromVisibleRows();
            syncExportForms();
            highlight(input.value.trim());
            window.syncWeaponsHorizontalScrollbar?.();
        };

        let timer = null;
        input.addEventListener('input', () => {
            const url = new URL(window.location.href);
            applyStateToUrl(url, { resetPage: true });
            window.history.replaceState({}, '', url.toString());

            if (timer) {
                clearTimeout(timer);
            }

            timer = setTimeout(() => {
                updateList(url.toString());
            }, 300);
        });

        filtersToggle.addEventListener('click', () => {
            filtersPanel.classList.toggle('hidden');
        });

        filtersForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const url = new URL(window.location.href);
            applyStateToUrl(url, { resetPage: true });
            window.history.replaceState({}, '', url.toString());
            updateList(url.toString());
        });

        filtersReset.addEventListener('click', () => {
            filtersForm.reset();
            const url = new URL(window.location.href);
            applyStateToUrl(url, { resetPage: true });
            window.history.replaceState({}, '', url.toString());
            updateList(url.toString());
        });

        pagination.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) {
                return;
            }

            event.preventDefault();
            const url = new URL(link.href);
            applyStateToUrl(url, { resetPage: false });
            window.history.replaceState({}, '', url.toString());
            updateList(url.toString());
        });

        tbody.addEventListener('click', (event) => {
            const row = event.target.closest('.weapon-row');
            if (!row) {
                return;
            }

            if (event.target.closest('.weapon-export-checkbox, .imprint-checkbox, button, a, label')) {
                return;
            }

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
                    if (row) {
                        exportSelectionData.set(exportCheckbox.value, extractWeaponSummary(row));
                    }
                } else {
                    exportSelection.delete(exportCheckbox.value);
                    exportSelectionData.delete(exportCheckbox.value);
                }

                syncExportForms();
                return;
            }

            const imprintCheckbox = event.target.closest('.imprint-checkbox');
            if (!imprintCheckbox) {
                return;
            }

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
            const state = currentState();
            Object.entries(state).forEach(([key, value]) => {
                if (value !== '') {
                    previewUrl.searchParams.set(key, value);
                }
            });

            const response = await fetch(previewUrl.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.has_filters) {
                openExportModal({
                    description: exportPreviewDescription(data.count, 'filtered', data.truncated),
                    items: Array.isArray(data.items) ? data.items : [],
                    editLabel: '{{ __('Editar filtros') }}',
                    submitForm: exportFilteredForm,
                    defaultFormat: 'xlsx',
                });
                return;
            }

            openExportModal({
                description: `{{ __('Se descargarán') }} ${data.count} ${data.count === 1 ? '{{ __('arma') }}' : '{{ __('armas') }}'}.`,
                warning: `{{ __('Vas a exportar todas las armas.') }}`,
                showEdit: false,
                submitForm: exportFilteredForm,
                defaultFormat: 'xlsx',
            });
        });

        exportSelectedForm.addEventListener('submit', (event) => {
            event.preventDefault();
            syncExportForms();
            if (exportSelection.size === 0) {
                return;
            }

            const items = Array.from(exportSelection)
                .map((weaponId) => exportSelectionData.get(weaponId))
                .filter(Boolean);

            exportMenu.removeAttribute('open');
            openExportModal({
                description: exportPreviewDescription(exportSelection.size, 'selected'),
                items,
                editLabel: '{{ __('Editar selección') }}',
                submitForm: exportSelectedForm,
                defaultFormat: 'xlsx',
            });
        });

        exportModalConfirm.addEventListener('click', () => {
            if (!pendingExportForm) {
                return;
            }

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
            if (event.key === 'Escape' && !exportModal.classList.contains('hidden')) {
                closeExportModal();
            }
        });

        if (input.value.trim() !== '') {
            highlight(input.value.trim());
        }

        syncSelectionDetailsFromVisibleRows();
        syncExportForms();
        clearSelectedRow();
        syncExportCheckboxes();
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
