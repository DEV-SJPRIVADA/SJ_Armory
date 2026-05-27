/**
 * Alertas documentales: period picker, modales, búsqueda global y filtros por columna (estilo Excel).
 */

const COLUMN_KEYS = ['cliente', 'tipo', 'serie', 'vence', 'estado', 'observacion'];

/**
 * @param {HTMLElement} root
 */
export function initAlertsDocumentsPage(root) {
    if (!root || root.dataset.alertsInitialized === '1') {
        return;
    }

    root.dataset.alertsInitialized = '1';

    const configEl = document.getElementById('alerts-page-config');
    const config = configEl ? JSON.parse(configEl.textContent || '{}') : {};

    const {
        monthShortNames = [],
        selectedMonths: initialSelectedMonths = [],
        previewAvailable = false,
        locale = 'es',
        labels = {},
    } = config;

    const txt = {
        selectMonths: labels.selectMonths ?? 'Seleccionar meses',
        allMonths: labels.allMonths ?? 'Todos los meses',
        periodsSelected: labels.periodsSelected ?? 'períodos seleccionados',
        periodHintEmpty: labels.periodHintEmpty ?? 'Marque uno o varios meses y pulse Filtrar.',
        periodHintCount: labels.periodHintCount ?? 'seleccionado(s). Pulse Filtrar para aplicar.',
        armaEnLista: labels.armaEnLista ?? 'arma en la lista',
        armasEnLista: labels.armasEnLista ?? 'armas en la lista',
        descargarRelacion: labels.descargarRelacion ?? 'Descargar relación',
        seleccionadas: labels.seleccionadas ?? 'seleccionadas',
        filterSearchPlaceholder: labels.filterSearchPlaceholder ?? 'Buscar en la lista…',
        filterSelectAll: labels.filterSelectAll ?? 'Seleccionar todo',
        filterClear: labels.filterClear ?? 'Limpiar',
        filterApply: labels.filterApply ?? 'Aplicar',
        clearColumnFilters: labels.clearColumnFilters ?? 'Limpiar filtros de columna',
        filterActive: labels.filterActive ?? 'Filtro activo',
        noFilterValues: labels.noFilterValues ?? 'Sin valores para mostrar.',
    };

    // —— Period picker ——
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

    const selectedMonths = new Set(initialSelectedMonths);
    let panelYear = (() => {
        if (selectedMonths.size === 0) {
            return new Date().getFullYear();
        }
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
        if (!container) {
            return;
        }
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
        if (!periodSummary || !periodToggle) {
            return;
        }

        const count = selectedMonths.size;
        periodToggle.classList.toggle('has-selection', count > 0);

        if (count === 0) {
            periodSummary.textContent = txt.selectMonths;
            if (periodHint) {
                periodHint.textContent = txt.periodHintEmpty;
            }
            return;
        }

        if (count === 1) {
            periodSummary.textContent = formatMonthLabel([...selectedMonths][0]);
        } else if (count <= 3) {
            periodSummary.textContent = [...selectedMonths].sort().map(formatMonthLabel).join(', ');
        } else {
            periodSummary.textContent = `${count} ${txt.periodsSelected}`;
        }

        if (periodHint) {
            periodHint.textContent = `${count} ${txt.periodHintCount}`;
        }
    };

    const renderPeriodMonthGrid = () => {
        if (!periodMonthGrid || !periodYearLabel) {
            return;
        }

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
        if (!periodPanel || !periodToggle) {
            return;
        }

        periodPanel.classList.toggle('hidden', !open);
        periodPanel.hidden = !open;
        periodToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    periodToggle?.addEventListener('click', () => {
        setPanelOpen(!panelOpen);
    });

    periodMonthGrid?.addEventListener('change', (event) => {
        const checkbox = event.target;
        if (!(checkbox instanceof HTMLInputElement) || checkbox.type !== 'checkbox') {
            return;
        }

        const monthValue = checkbox.value;
        if (!/^\d{4}-\d{2}$/.test(monthValue)) {
            return;
        }

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
            if (!step) {
                return;
            }
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
        if (!panelOpen) {
            return;
        }
        const target = event.target;
        if (!(target instanceof Node)) {
            return;
        }
        if (periodToggle?.contains(target) || periodPanel?.contains(target)) {
            return;
        }
        setPanelOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && panelOpen) {
            setPanelOpen(false);
        }
    });

    syncPeriodSelection();

    // —— Modals, selection, filters ——
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
        {
            bodyId: 'expired-alerts-body',
            rows: () => Array.from(document.querySelectorAll('#expired-alerts-body .alert-document-row')),
            noResults: document.getElementById('expired-alerts-no-results'),
            emptyRows: () => Array.from(document.querySelectorAll('#expired-alerts-body .alerts-empty-row')),
            selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="expired-alerts-body"]'),
            excludeToggle: document.querySelector('.alerts-exclude-novedades[data-target-body="expired-alerts-body"]'),
            visibleCountEl: document.getElementById('expired-visible-count'),
            clearColumnFiltersBtn: document.querySelector('[data-clear-column-filters="expired-alerts-body"]'),
        },
        {
            bodyId: 'expiring-alerts-body',
            rows: () => Array.from(document.querySelectorAll('#expiring-alerts-body .alert-document-row')),
            noResults: document.getElementById('expiring-alerts-no-results'),
            emptyRows: () => Array.from(document.querySelectorAll('#expiring-alerts-body .alerts-empty-row')),
            selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="expiring-alerts-body"]'),
            excludeToggle: document.querySelector('.alerts-exclude-novedades[data-target-body="expiring-alerts-body"]'),
            visibleCountEl: document.getElementById('expiring-visible-count'),
            clearColumnFiltersBtn: document.querySelector('[data-clear-column-filters="expiring-alerts-body"]'),
        },
        {
            bodyId: 'no-alerts-body',
            rows: () => Array.from(document.querySelectorAll('#no-alerts-body .alert-document-row')),
            noResults: document.getElementById('no-alerts-no-results'),
            emptyRows: () => Array.from(document.querySelectorAll('#no-alerts-body .alerts-empty-row')),
            selectAll: document.querySelector('.alert-select-all-toggle[data-target-body="no-alerts-body"]'),
            excludeToggle: document.querySelector('.alerts-exclude-novedades[data-target-body="no-alerts-body"]'),
            visibleCountEl: document.getElementById('no-alerts-visible-count'),
            clearColumnFiltersBtn: document.querySelector('[data-clear-column-filters="no-alerts-body"]'),
        },
    ];

    /** @type {Map<string, Record<string, Set<string>>>} */
    const columnFiltersByBody = new Map();
    sections.forEach((section) => {
        columnFiltersByBody.set(section.bodyId, Object.fromEntries(COLUMN_KEYS.map((key) => [key, new Set()])));
    });

    const columnFilterPopover = document.getElementById('alerts-column-filter-popover');
    const columnFilterSearch = columnFilterPopover?.querySelector('[data-col-filter-search]');
    const columnFilterList = columnFilterPopover?.querySelector('[data-col-filter-list]');
    const columnFilterSelectAllBtn = columnFilterPopover?.querySelector('[data-col-filter-select-all]');
    const columnFilterClearBtn = columnFilterPopover?.querySelector('[data-col-filter-clear]');
    const columnFilterApplyBtn = columnFilterPopover?.querySelector('[data-col-filter-apply]');

    let openFilterContext = null;
    /** @type {Set<string>} */
    let draftSelection = new Set();

    const getSection = (bodyId) => sections.find((item) => item.bodyId === bodyId);

    const getColumnFilters = (bodyId) => columnFiltersByBody.get(bodyId);

    const getRowColumnValue = (row, columnKey) => row.getAttribute(`data-col-${columnKey}`) ?? '';

    const rowMatchesSearch = (row, term) => {
        if (term === '') {
            return true;
        }
        const haystack = row.dataset.alertSearch || row.textContent.toLowerCase();
        return haystack.includes(term);
    };

    const rowMatchesNovedad = (row, excludeNovedad) => {
        if (!excludeNovedad) {
            return true;
        }
        return row.dataset.blockingNovedad !== '1';
    };

    const rowMatchesColumnFilters = (row, bodyId, exceptColumn = null) => {
        const filters = getColumnFilters(bodyId);
        if (!filters) {
            return true;
        }

        return COLUMN_KEYS.every((key) => {
            if (key === exceptColumn) {
                return true;
            }
            const selected = filters[key];
            if (!selected || selected.size === 0) {
                return true;
            }
            return selected.has(getRowColumnValue(row, key));
        });
    };

    const getBaseFilteredRows = (section, exceptColumn = null) => {
        const term = (searchInput?.value || '').trim().toLowerCase();
        const excludeNovedad = section.excludeToggle?.checked ?? false;

        return section.rows().filter((row) => {
            return rowMatchesSearch(row, term)
                && rowMatchesNovedad(row, excludeNovedad)
                && rowMatchesColumnFilters(row, section.bodyId, exceptColumn);
        });
    };

    const getUniqueColumnValues = (section, columnKey) => {
        const values = new Set();
        getBaseFilteredRows(section, columnKey).forEach((row) => {
            const value = getRowColumnValue(row, columnKey);
            if (value !== '') {
                values.add(value);
            }
        });
        return [...values].sort((a, b) => a.localeCompare(b, locale, { sensitivity: 'base' }));
    };

    const countActiveColumnFilters = (bodyId) => {
        const filters = getColumnFilters(bodyId);
        if (!filters) {
            return 0;
        }
        return COLUMN_KEYS.reduce((count, key) => count + (filters[key].size > 0 ? 1 : 0), 0);
    };

    const updateColumnFilterTriggerStates = (bodyId) => {
        const filters = getColumnFilters(bodyId);
        if (!filters) {
            return;
        }

        document.querySelectorAll(`[data-col-filter-trigger][data-target-body="${bodyId}"]`).forEach((trigger) => {
            const columnKey = trigger.getAttribute('data-col-filter');
            const active = columnKey && filters[columnKey]?.size > 0;
            trigger.classList.toggle('is-active', Boolean(active));
            trigger.setAttribute('aria-pressed', active ? 'true' : 'false');
            trigger.setAttribute('title', active ? txt.filterActive : '');
        });

        const section = getSection(bodyId);
        if (section?.clearColumnFiltersBtn) {
            const activeCount = countActiveColumnFilters(bodyId);
            section.clearColumnFiltersBtn.classList.toggle('hidden', activeCount === 0);
            section.clearColumnFiltersBtn.setAttribute('aria-hidden', activeCount === 0 ? 'true' : 'false');
        }
    };

    const positionColumnFilterPopover = (trigger) => {
        if (!columnFilterPopover || !trigger) {
            return;
        }

        const rect = trigger.getBoundingClientRect();
        const popoverWidth = 300;
        let left = rect.left;
        const maxLeft = window.innerWidth - popoverWidth - 12;
        if (left > maxLeft) {
            left = maxLeft;
        }
        if (left < 12) {
            left = 12;
        }

        columnFilterPopover.style.width = `${popoverWidth}px`;
        columnFilterPopover.style.left = `${left}px`;
        columnFilterPopover.style.top = `${rect.bottom + 6}px`;
    };

    const renderColumnFilterList = () => {
        if (!columnFilterList || !openFilterContext) {
            return;
        }

        const { section, columnKey } = openFilterContext;
        const term = (columnFilterSearch?.value || '').trim().toLowerCase();
        const values = getUniqueColumnValues(section, columnKey);
        const filteredValues = term === ''
            ? values
            : values.filter((value) => value.toLowerCase().includes(term));

        columnFilterList.innerHTML = '';

        if (filteredValues.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'alerts-col-filter-popover__empty';
            empty.textContent = txt.noFilterValues;
            columnFilterList.append(empty);
            return;
        }

        filteredValues.forEach((value) => {
            const label = document.createElement('label');
            label.className = 'alerts-col-filter-option';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = value;
            checkbox.checked = draftSelection.has(value);

            const text = document.createElement('span');
            text.textContent = value;

            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    draftSelection.add(value);
                } else {
                    draftSelection.delete(value);
                }
            });

            label.append(checkbox, text);
            columnFilterList.append(label);
        });
    };

    const closeColumnFilterPopover = () => {
        if (!columnFilterPopover) {
            return;
        }

        columnFilterPopover.classList.add('hidden');
        columnFilterPopover.hidden = true;
        document.querySelectorAll('[data-col-filter-trigger][aria-expanded="true"]').forEach((trigger) => {
            trigger.setAttribute('aria-expanded', 'false');
        });
        openFilterContext = null;
        draftSelection = new Set();
        if (columnFilterSearch) {
            columnFilterSearch.value = '';
        }
    };

    const openColumnFilterPopover = (trigger) => {
        const bodyId = trigger.getAttribute('data-target-body');
        const columnKey = trigger.getAttribute('data-col-filter');
        if (!bodyId || !columnKey || !columnFilterPopover) {
            return;
        }

        const section = getSection(bodyId);
        const filters = getColumnFilters(bodyId);
        if (!section || !filters) {
            return;
        }

        const isSame = openFilterContext
            && openFilterContext.bodyId === bodyId
            && openFilterContext.columnKey === columnKey
            && trigger.getAttribute('aria-expanded') === 'true';

        if (isSame) {
            closeColumnFilterPopover();
            return;
        }

        closeColumnFilterPopover();

        openFilterContext = { bodyId, columnKey, section, trigger };
        draftSelection = new Set(filters[columnKey]);

        document.querySelectorAll('[data-col-filter-trigger]').forEach((btn) => {
            btn.setAttribute('aria-expanded', btn === trigger ? 'true' : 'false');
        });

        columnFilterPopover.dataset.targetBody = bodyId;
        columnFilterPopover.dataset.colFilter = columnKey;
        columnFilterPopover.classList.remove('hidden');
        columnFilterPopover.hidden = false;

        if (columnFilterSearch) {
            columnFilterSearch.placeholder = txt.filterSearchPlaceholder;
        }

        positionColumnFilterPopover(trigger);
        renderColumnFilterList();
        columnFilterSearch?.focus();
    };

    const applyDraftColumnFilter = () => {
        if (!openFilterContext) {
            return;
        }

        const { bodyId, columnKey } = openFilterContext;
        const filters = getColumnFilters(bodyId);
        if (!filters) {
            return;
        }

        filters[columnKey] = new Set(draftSelection);
        closeColumnFilterPopover();
        updateColumnFilterTriggerStates(bodyId);
        applyFilters();
    };

    const clearColumnFiltersForBody = (bodyId) => {
        const filters = getColumnFilters(bodyId);
        if (!filters) {
            return;
        }

        COLUMN_KEYS.forEach((key) => {
            filters[key].clear();
        });
        updateColumnFilterTriggerStates(bodyId);
        applyFilters();
    };

    const checkboxes = () => Array.from(document.querySelectorAll('.alert-weapon-checkbox'));
    const visibleRows = (section) => section.rows().filter((row) => !row.classList.contains('hidden'));
    const visibleCheckboxes = (section) => visibleRows(section)
        .map((row) => row.querySelector('.alert-weapon-checkbox'))
        .filter((checkbox) => checkbox && !checkbox.disabled);

    const updateSelectAllState = (section) => {
        if (!section?.selectAll) {
            return;
        }

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
        if (countBadge) {
            countBadge.textContent = `${selected} ${txt.seleccionadas}`;
        }
        if (downloadButton) {
            downloadButton.disabled = selected === 0;
            downloadButton.textContent = selected > 0 ? `${txt.descargarRelacion} (${selected})` : txt.descargarRelacion;
            downloadButton.classList.toggle('is-ready', selected > 0);
        }
        if (previewButton) {
            const canPreview = previewAvailable && selected > 0;
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
            const hasColumnFilters = countActiveColumnFilters(section.bodyId) > 0;
            const hasActiveFilter = term !== '' || excludeNovedad || hasColumnFilters;

            rows.forEach((row) => {
                const visible = rowMatchesSearch(row, term)
                    && rowMatchesNovedad(row, excludeNovedad)
                    && rowMatchesColumnFilters(row, section.bodyId);

                row.classList.toggle('hidden', !visible);
                if (visible) {
                    visibleCount += 1;
                }

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

            section.emptyRows().forEach((row) => row.classList.toggle('hidden', visibleCount > 0 || term !== '' || hasColumnFilters));
            if (section.noResults) {
                const showNoResults = rows.length > 0 && visibleCount === 0 && hasActiveFilter;
                section.noResults.classList.toggle('hidden', !showNoResults);
            }
            if (section.visibleCountEl) {
                section.visibleCountEl.textContent = `${visibleCount} ${visibleCount === 1 ? txt.armaEnLista : txt.armasEnLista}`;
            }
            updateColumnFilterTriggerStates(section.bodyId);
            updateSelectAllState(section);
        });

        if (openFilterContext) {
            renderColumnFilterList();
        }

        updateSelectionCount();
    };

    const openModal = (key) => {
        activeModal = key;
        closeColumnFilterPopover();
        syncModalOffset();
        modalLayer?.classList.remove('hidden');
        modalLayer?.setAttribute('aria-hidden', 'false');
        modalPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.alertsModal !== key));
    };

    const closeModal = () => {
        activeModal = null;
        closeColumnFilterPopover();
        modalLayer?.classList.add('hidden');
        modalLayer?.setAttribute('aria-hidden', 'true');
        modalPanels.forEach((panel) => panel.classList.add('hidden'));
    };

    const toggleSectionSelection = (bodyId, checked) => {
        const section = getSection(bodyId);
        if (!section) {
            return;
        }

        visibleCheckboxes(section).forEach((checkbox) => {
            checkbox.checked = checked;
        });

        updateSelectionCount();
    };

    openButtons.forEach((button) => button.addEventListener('click', () => openModal(button.dataset.openModal)));
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    searchInput?.addEventListener('input', applyFilters);

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const trigger = target.closest('[data-col-filter-trigger]');
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
        if (!openFilterContext) {
            return;
        }

        const { section, columnKey } = openFilterContext;
        const term = (columnFilterSearch?.value || '').trim().toLowerCase();
        const values = getUniqueColumnValues(section, columnKey);
        const filteredValues = term === ''
            ? values
            : values.filter((value) => value.toLowerCase().includes(term));

        filteredValues.forEach((value) => {
            draftSelection.add(value);
        });
        renderColumnFilterList();
    });

    columnFilterClearBtn?.addEventListener('click', () => {
        draftSelection.clear();
        renderColumnFilterList();
    });

    columnFilterApplyBtn?.addEventListener('click', applyDraftColumnFilter);

    document.querySelectorAll('[data-clear-column-filters]').forEach((button) => {
        button.addEventListener('click', () => {
            const bodyId = button.getAttribute('data-clear-column-filters');
            if (bodyId) {
                clearColumnFiltersForBody(bodyId);
            }
        });
    });

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

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (openFilterContext) {
                closeColumnFilterPopover();
                return;
            }
            if (activeModal) {
                closeModal();
            }
        }
    });

    window.addEventListener('resize', () => {
        syncModalOffset();
        if (openFilterContext?.trigger) {
            positionColumnFilterPopover(openFilterContext.trigger);
        }
    });

    window.addEventListener('scroll', () => {
        if (activeModal) {
            syncModalOffset();
        }
        if (openFilterContext?.trigger) {
            positionColumnFilterPopover(openFilterContext.trigger);
        }
    }, { passive: true });

    syncModalOffset();
    sections.forEach((section) => updateColumnFilterTriggerStates(section.bodyId));
    applyFilters();
}
