<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header">
            <div class="sj-section-header__main">
                <p class="sj-section-header__eyebrow">{{ __('Documentos operativos') }}</p>
                <h2 class="sj-section-header__title">{{ __('Formatos') }}</h2>
                <p class="sj-section-header__subtitle">
                    {{ __('Descarga plantillas oficiales listas para imprimir en carta horizontal.') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">FO-OP-03</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ __('Revista mensual de armamento') }}</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                {{ __('Formato oficial con 20 filas por hoja. Seleccione las armas que desea incluir en el Excel.') }}
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <a href="{{ route('formatos.revista-mensual.vacio') }}"
                               class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                {{ __('Descargar vacío') }}
                            </a>
                            <button type="button"
                                    x-data=""
                                    x-on:click.prevent="$dispatch('open-modal', 'monthly-review-filters')"
                                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                {{ __('Con relación de armas') }}
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <x-modal name="monthly-review-filters" maxWidth="6xl" focusable :bodyScroll="false">
        <div id="monthly-review-picker" class="flex h-full min-h-0 flex-col overflow-hidden">
            <div class="flex-shrink-0 px-6 pt-6 pb-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Relación de armas para revista mensual') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('Seleccione las armas a exportar. Cada hoja del Excel conserva 20 filas para impresión en carta horizontal.') }}
                        </p>
                    </div>
                    <button type="button" x-data="" x-on:click.prevent="$dispatch('close-modal', 'monthly-review-filters')" class="rounded-md px-2 py-1 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                        {{ __('Cerrar') }}
                    </button>
                </div>
            </div>

            <div class="flex-shrink-0 space-y-4 px-6">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <div class="xl:col-span-2">
                        <x-input-label for="monthly-review-q" :value="__('Búsqueda general')" />
                        <x-text-input id="monthly-review-q" type="text" class="mt-1 block w-full" placeholder="Serie, cliente, responsable..." />
                    </div>
                    <div>
                        <x-input-label for="monthly-review-inventory" :value="__('Inventario')" />
                        <select id="monthly-review-inventory" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($formOptions['inventory_scopes'] as $value => $label)
                                <option value="{{ $value }}" @selected($value === 'operational')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="monthly-review-destination" :value="__('Destino')" />
                        <select id="monthly-review-destination" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($formOptions['destinations'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <x-input-label for="monthly-review-from" :value="__('Vence desde')" />
                            <x-text-input id="monthly-review-from" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="monthly-review-to" :value="__('Vence hasta')" />
                            <x-text-input id="monthly-review-to" type="date" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="monthly-review-select-visible" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            {{ __('Seleccionar visibles') }}
                        </button>
                        <button type="button" id="monthly-review-clear-selection" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            {{ __('Limpiar selección') }}
                        </button>
                        <button type="button" id="monthly-review-clear-column-filters" class="hidden rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            {{ __('Limpiar filtros de columna') }}
                        </button>
                    </div>
                    <div class="text-sm text-slate-600">
                        <span id="monthly-review-results-count">0 {{ __('resultados') }}</span>
                        <span class="mx-2 text-slate-300">|</span>
                        <span id="monthly-review-selected-count" class="font-semibold text-indigo-700">0 {{ __('seleccionadas') }}</span>
                        <span class="mx-2 text-slate-300">|</span>
                        <span id="monthly-review-preview">0 {{ __('hoja(s)') }}</span>
                    </div>
                </div>
            </div>

            <div class="mx-6 mt-4 flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-200">
                <div class="flex-shrink-0 overflow-x-auto bg-[#162457] text-white">
                    <table class="min-w-full table-fixed text-sm">
                        <colgroup>
                            <col style="width: 2.75rem">
                            <col style="width: 32%">
                            <col style="width: 16%">
                            <col style="width: 26%">
                            <col style="width: 22%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left">
                                    <input id="monthly-review-select-page" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" aria-label="{{ __('Seleccionar página') }}">
                                </th>
                                @foreach ([
                                    'cliente' => __('Cliente'),
                                    'puesto' => __('Puesto'),
                                    'responsable' => __('Responsable'),
                                    'serie' => __('Serie'),
                                ] as $key => $label)
                                    <th class="px-3 py-2 text-left">
                                        <div class="flex items-center justify-between gap-2">
                                            <span>{{ $label }}</span>
                                            <button type="button"
                                                    class="monthly-review-col-filter-trigger inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border border-white/30 bg-white/10 text-white hover:bg-white/20"
                                                    data-col-filter="{{ $key }}"
                                                    aria-label="{{ __('Filtrar') }} {{ $label }}">
                                                ▾
                                            </button>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                    </table>
                </div>

                <div id="monthly-review-table-scroll" class="min-h-0 flex-1 overflow-y-auto overflow-x-auto overscroll-contain bg-white">
                    <table class="min-w-full table-fixed divide-y divide-slate-100 text-sm">
                        <colgroup>
                            <col style="width: 2.75rem">
                            <col style="width: 32%">
                            <col style="width: 16%">
                            <col style="width: 26%">
                            <col style="width: 22%">
                        </colgroup>
                        <tbody id="monthly-review-tbody"></tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex-shrink-0 border-t border-slate-200 bg-white px-6 pb-6 pt-4 shadow-[0_-8px_16px_-12px_rgba(15,23,42,0.35)]">
                <div id="monthly-review-pagination" class="mb-4 flex items-center justify-between text-sm text-slate-600"></div>

                <form id="monthly-review-form" method="POST" action="{{ route('formatos.revista-mensual.descargar') }}" class="flex items-center justify-end gap-3">
                    @csrf
                    <div id="monthly-review-form-inputs"></div>
                    <x-primary-button id="monthly-review-submit" disabled>{{ __('Generar Excel') }}</x-primary-button>
                </form>
            </div>
        </div>
    </x-modal>

    <div id="monthly-review-col-popover" class="fixed z-[80] hidden w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
        <input type="search" data-col-filter-search class="mb-2 w-full rounded-md border-slate-300 text-sm" placeholder="{{ __('Buscar...') }}">
        <div class="mb-2 flex items-center justify-between border-b border-slate-200 pb-2 text-xs">
            <button type="button" data-col-filter-select-all class="font-semibold text-indigo-700">{{ __('Seleccionar todo') }}</button>
            <button type="button" data-col-filter-clear class="font-semibold text-slate-600">{{ __('Limpiar') }}</button>
        </div>
        <div data-col-filter-list class="max-h-44 space-y-1 overflow-auto"></div>
        <button type="button" data-col-filter-apply class="mt-3 w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            {{ __('Aplicar') }}
        </button>
    </div>

    @push('scripts')
    <script>
        (() => {
            const picker = document.getElementById('monthly-review-picker');
            if (!picker) return;

            const COLUMN_KEYS = @json($columnKeys);
            const weaponsUrl = @json(route('formatos.revista-mensual.armas'));
            const columnOptionsUrl = @json(route('formatos.revista-mensual.column-options'));
            const previewUrl = @json(route('formatos.revista-mensual.vista-previa'));
            const csrf = @json(csrf_token());

            const qInput = document.getElementById('monthly-review-q');
            const inventorySelect = document.getElementById('monthly-review-inventory');
            const destinationSelect = document.getElementById('monthly-review-destination');
            const fromInput = document.getElementById('monthly-review-from');
            const toInput = document.getElementById('monthly-review-to');
            const tbody = document.getElementById('monthly-review-tbody');
            const tableScroll = document.getElementById('monthly-review-table-scroll');
            const tableHeadScroll = tableScroll?.previousElementSibling;
            const pagination = document.getElementById('monthly-review-pagination');
            const resultsCount = document.getElementById('monthly-review-results-count');
            const selectedCount = document.getElementById('monthly-review-selected-count');
            const previewLabel = document.getElementById('monthly-review-preview');
            const selectVisibleBtn = document.getElementById('monthly-review-select-visible');
            const clearSelectionBtn = document.getElementById('monthly-review-clear-selection');
            const clearColumnFiltersBtn = document.getElementById('monthly-review-clear-column-filters');
            const selectPageCheckbox = document.getElementById('monthly-review-select-page');
            const submitBtn = document.getElementById('monthly-review-submit');
            const form = document.getElementById('monthly-review-form');
            const formInputs = document.getElementById('monthly-review-form-inputs');
            const popover = document.getElementById('monthly-review-col-popover');
            const popoverSearch = popover?.querySelector('[data-col-filter-search]');
            const popoverList = popover?.querySelector('[data-col-filter-list]');
            const popoverSelectAll = popover?.querySelector('[data-col-filter-select-all]');
            const popoverClear = popover?.querySelector('[data-col-filter-clear]');
            const popoverApply = popover?.querySelector('[data-col-filter-apply]');

            const columnFilters = Object.fromEntries(COLUMN_KEYS.map((key) => [key, new Set()]));
            const selectedIds = new Set();
            let currentPage = 1;
            let openFilterKey = null;
            let draftSelection = new Set();
            let visibleRows = [];
            let debounceTimer = null;
            let popoverOptionsCache = [];

            const resetTableScroll = () => {
                if (tableScroll) {
                    tableScroll.scrollTop = 0;
                    tableScroll.scrollLeft = 0;
                }
                if (tableHeadScroll) {
                    tableHeadScroll.scrollLeft = 0;
                }
            };

            tableScroll?.addEventListener('scroll', () => {
                if (tableHeadScroll) {
                    tableHeadScroll.scrollLeft = tableScroll.scrollLeft;
                }
            }, { passive: true });

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            const countActiveColumnFilters = () => COLUMN_KEYS.reduce((count, key) => count + (columnFilters[key].size > 0 ? 1 : 0), 0);

            const appendStateToParams = (params) => {
                const q = qInput.value.trim();
                if (q !== '') params.set('q', q);
                params.set('inventory_scope', inventorySelect.value || 'operational');
                const destination = destinationSelect.value;
                if (destination !== '') params.set('destination', destination);
                if (fromInput.value) params.set('permit_expires_from', fromInput.value);
                if (toInput.value) params.set('permit_expires_to', toInput.value);
                COLUMN_KEYS.forEach((key) => {
                    columnFilters[key].forEach((value) => params.append(`col[${key}][]`, value));
                });
            };

            const updateColumnFilterTriggers = () => {
                document.querySelectorAll('.monthly-review-col-filter-trigger').forEach((trigger) => {
                    const key = trigger.getAttribute('data-col-filter');
                    const active = key && columnFilters[key]?.size > 0;
                    trigger.classList.toggle('bg-white', Boolean(active));
                    trigger.classList.toggle('text-indigo-700', Boolean(active));
                    trigger.classList.toggle('border-indigo-300', Boolean(active));
                });
                clearColumnFiltersBtn?.classList.toggle('hidden', countActiveColumnFilters() === 0);
            };

            const syncFormInputs = () => {
                formInputs.innerHTML = '';
                selectedIds.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'weapon_ids[]';
                    input.value = String(id);
                    formInputs.appendChild(input);
                });
                submitBtn.disabled = selectedIds.size === 0;
            };

            const updateSelectionUi = async () => {
                const count = selectedIds.size;
                selectedCount.textContent = `${count} {{ __('seleccionadas') }}`;
                syncFormInputs();

                if (count === 0) {
                    previewLabel.textContent = `0 {{ __('hoja(s)') }}`;
                    return;
                }

                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ weapon_ids: Array.from(selectedIds) }),
                    });
                    if (!response.ok) throw new Error('preview_failed');
                    const data = await response.json();
                    previewLabel.textContent = `${data.pages} {{ __('hoja(s)') }} (${data.count} {{ __('armas') }})`;
                } catch (error) {
                    previewLabel.textContent = `${Math.ceil(count / 20)} {{ __('hoja(s)') }}`;
                }
            };

            const renderRows = () => {
                if (visibleRows.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No hay armas con los filtros actuales.') }}</td></tr>`;
                    selectPageCheckbox.checked = false;
                    selectPageCheckbox.indeterminate = false;
                    return;
                }

                tbody.innerHTML = visibleRows.map((row) => {
                    const checked = selectedIds.has(row.id) ? 'checked' : '';
                    return `<tr class="hover:bg-slate-50">
                        <td class="px-3 py-2"><input type="checkbox" class="monthly-review-row-check rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" data-weapon-id="${row.id}" ${checked}></td>
                        <td class="px-3 py-2">${escapeHtml(row.cliente)}</td>
                        <td class="px-3 py-2">${escapeHtml(row.puesto)}</td>
                        <td class="px-3 py-2">${escapeHtml(row.responsable)}</td>
                        <td class="px-3 py-2 font-medium">${escapeHtml(row.serie)}</td>
                    </tr>`;
                }).join('');

                const visibleIds = visibleRows.map((row) => row.id);
                const selectedVisible = visibleIds.filter((id) => selectedIds.has(id)).length;
                selectPageCheckbox.checked = visibleIds.length > 0 && selectedVisible === visibleIds.length;
                selectPageCheckbox.indeterminate = selectedVisible > 0 && selectedVisible < visibleIds.length;
            };

            const renderPagination = (meta) => {
                if (!meta || meta.last_page <= 1) {
                    pagination.innerHTML = '';
                    return;
                }

                pagination.innerHTML = `
                    <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm ${meta.current_page <= 1 ? 'opacity-40 pointer-events-none' : ''}" data-page="${meta.current_page - 1}">{{ __('Anterior') }}</button>
                    <span>{{ __('Página') }} ${meta.current_page} {{ __('de') }} ${meta.last_page}</span>
                    <button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm ${meta.current_page >= meta.last_page ? 'opacity-40 pointer-events-none' : ''}" data-page="${meta.current_page + 1}">{{ __('Siguiente') }}</button>
                `;

                pagination.querySelectorAll('[data-page]').forEach((button) => {
                    button.addEventListener('click', () => {
                        currentPage = Number(button.getAttribute('data-page'));
                        loadRows();
                    });
                });
            };

            const loadRows = async () => {
                const params = new URLSearchParams();
                params.set('page', String(currentPage));
                appendStateToParams(params);

                tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('Cargando...') }}</td></tr>`;
                resetTableScroll();

                try {
                    const response = await fetch(`${weaponsUrl}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error('load_failed');
                    const data = await response.json();
                    visibleRows = data.rows || [];
                    resultsCount.textContent = `${data.meta.total} {{ __('resultados') }}`;
                    renderRows();
                    renderPagination(data.meta);
                } catch (error) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-rose-600">{{ __('No se pudo cargar la tabla.') }}</td></tr>`;
                }
            };

            const closePopover = () => {
                openFilterKey = null;
                popover.classList.add('hidden');
            };

            const renderPopoverOptions = (values) => {
                const term = popoverSearch.value.trim().toLowerCase();
                const filtered = values.filter((value) => value.toLowerCase().includes(term));
                if (filtered.length === 0) {
                    popoverList.innerHTML = `<p class="px-2 py-3 text-center text-sm text-slate-500">{{ __('Sin opciones') }}</p>`;
                    return;
                }

                popoverList.innerHTML = filtered.map((value) => {
                    const checked = draftSelection.has(value) ? 'checked' : '';
                    return `<label class="flex items-start gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50">
                        <input type="checkbox" value="${escapeHtml(value)}" ${checked} class="mt-0.5 rounded border-slate-300 text-indigo-600">
                        <span>${escapeHtml(value)}</span>
                    </label>`;
                }).join('');
            };

            const openPopover = async (trigger, key) => {
                openFilterKey = key;
                draftSelection = new Set(columnFilters[key]);
                popoverSearch.value = '';

                const rect = trigger.getBoundingClientRect();
                popover.style.top = `${Math.min(window.innerHeight - 320, rect.bottom + 6)}px`;
                popover.style.left = `${Math.min(window.innerWidth - 300, rect.left)}px`;
                popover.classList.remove('hidden');

                popoverList.innerHTML = `<p class="px-2 py-3 text-center text-sm text-slate-500">{{ __('Cargando...') }}</p>`;

                const params = new URLSearchParams();
                params.set('target', key);
                appendStateToParams(params);

                try {
                    const response = await fetch(`${columnOptionsUrl}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error('options_failed');
                    const data = await response.json();
                    popoverOptionsCache = data.values || [];
                    renderPopoverOptions(popoverOptionsCache);
                } catch (error) {
                    popoverList.innerHTML = `<p class="px-2 py-3 text-center text-sm text-rose-600">{{ __('Error al cargar opciones.') }}</p>`;
                }
            };

            const scheduleReload = (resetPage = true) => {
                if (resetPage) currentPage = 1;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(loadRows, 250);
            };

            document.querySelectorAll('.monthly-review-col-filter-trigger').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const key = trigger.getAttribute('data-col-filter');
                    if (!key) return;
                    if (openFilterKey === key && !popover.classList.contains('hidden')) {
                        closePopover();
                        return;
                    }
                    openPopover(trigger, key);
                });
            });

            popoverSearch?.addEventListener('input', () => {
                if (!openFilterKey) return;
                renderPopoverOptions(popoverOptionsCache);
            });

            popoverList?.addEventListener('change', (event) => {
                const input = event.target.closest('input[type="checkbox"]');
                if (!input) return;
                if (input.checked) draftSelection.add(input.value);
                else draftSelection.delete(input.value);
            });

            popoverSelectAll?.addEventListener('click', () => {
                popoverList.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                    input.checked = true;
                    draftSelection.add(input.value);
                });
            });

            popoverClear?.addEventListener('click', () => {
                draftSelection.clear();
                popoverList.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                    input.checked = false;
                });
            });

            popoverApply?.addEventListener('click', () => {
                if (!openFilterKey) return;
                columnFilters[openFilterKey] = new Set(draftSelection);
                updateColumnFilterTriggers();
                closePopover();
                scheduleReload(true);
            });

            document.addEventListener('click', (event) => {
                if (!popover.contains(event.target) && !event.target.closest('.monthly-review-col-filter-trigger')) {
                    closePopover();
                }
            });

            [qInput, inventorySelect, destinationSelect, fromInput, toInput].forEach((element) => {
                element?.addEventListener('change', () => scheduleReload(true));
                element?.addEventListener('input', () => scheduleReload(true));
            });

            tbody.addEventListener('change', (event) => {
                const checkbox = event.target.closest('.monthly-review-row-check');
                if (!checkbox) return;
                const id = Number(checkbox.dataset.weaponId);
                if (checkbox.checked) selectedIds.add(id);
                else selectedIds.delete(id);
                updateSelectionUi();
                renderRows();
            });

            selectPageCheckbox?.addEventListener('change', () => {
                visibleRows.forEach((row) => {
                    if (selectPageCheckbox.checked) selectedIds.add(row.id);
                    else selectedIds.delete(row.id);
                });
                updateSelectionUi();
                renderRows();
            });

            selectVisibleBtn?.addEventListener('click', () => {
                visibleRows.forEach((row) => selectedIds.add(row.id));
                updateSelectionUi();
                renderRows();
            });

            clearSelectionBtn?.addEventListener('click', () => {
                selectedIds.clear();
                updateSelectionUi();
                renderRows();
            });

            clearColumnFiltersBtn?.addEventListener('click', () => {
                COLUMN_KEYS.forEach((key) => columnFilters[key].clear());
                updateColumnFilterTriggers();
                scheduleReload(true);
            });

            form?.addEventListener('submit', (event) => {
                if (selectedIds.size === 0) {
                    event.preventDefault();
                }
            });

            window.addEventListener('open-modal', (event) => {
                if (event.detail !== 'monthly-review-filters') return;
                currentPage = 1;
                loadRows();
            });

            updateColumnFilterTriggers();
            syncFormInputs();
        })();
    </script>
    @endpush
</x-app-layout>
