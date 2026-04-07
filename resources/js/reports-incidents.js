const initReportsIncidents = () => {
    const moduleRoot = document.querySelector('[data-incident-module]');
    const typeSelects = Array.from(document.querySelectorAll('[data-incident-type-select]'));
    const modalitySelects = Array.from(document.querySelectorAll('[data-incident-modality-select]'));
    const weaponPickers = Array.from(document.querySelectorAll('[data-weapon-picker]'));
    const modals = Array.from(document.querySelectorAll('[data-modal-key]'));
    const modalMap = new Map(modals.map((modal) => [modal.dataset.modalKey, modal]));
    let activeModal = null;

    const syncBodyScroll = () => {
        const hasOpenModal = modals.some((modal) => !modal.classList.contains('hidden'));
        document.body.classList.toggle('overflow-hidden', hasOpenModal);
    };

    const openModal = (modal) => {
        if (!modal) {
            return;
        }

        if (activeModal && activeModal !== modal) {
            activeModal.classList.add('hidden');
            activeModal.setAttribute('aria-hidden', 'true');
        }

        activeModal = modal;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        syncBodyScroll();

        const firstField = modal.querySelector('input, select, textarea, button');
        window.setTimeout(() => firstField?.focus?.(), 20);
    };

    const closeModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');

        if (activeModal === modal) {
            activeModal = null;
        }

        weaponPickers.forEach((picker) => {
            picker.querySelector('[data-weapon-picker-menu]')?.classList.add('hidden');
        });

        syncBodyScroll();
    };

    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            openModal(modalMap.get(button.dataset.openModal));
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(button.closest('[data-modal-key]'));
        });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeModal) {
            closeModal(activeModal);
        }
    });

    const buildOptions = (select) => {
        const map = JSON.parse(select.dataset.modalityMap || '{}');
        const typeSelect = select.closest('form, section, div')?.querySelector('[data-incident-type-select]') || typeSelects[0];
        const selectedTypeId = String(typeSelect?.value || '');
        const selectedValue = String(select.dataset.selectedModality || select.value || '');
        const modalities = selectedTypeId && Array.isArray(map[selectedTypeId]) ? map[selectedTypeId] : [];

        select.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'No aplica';
        select.appendChild(defaultOption);

        modalities.forEach((modality) => {
            const option = document.createElement('option');
            option.value = String(modality.id);
            option.textContent = modality.name;
            option.selected = selectedValue !== '' && selectedValue === String(modality.id);
            select.appendChild(option);
        });

        select.disabled = modalities.length === 0;
    };

    typeSelects.forEach((typeSelect) => {
        typeSelect.addEventListener('change', () => {
            modalitySelects.forEach((modalitySelect) => {
                modalitySelect.dataset.selectedModality = '';
                buildOptions(modalitySelect);
            });
        });
    });

    modalitySelects.forEach(buildOptions);

    const closeStatusSelects = Array.from(document.querySelectorAll('[data-close-status-select]'));

    const syncCloseOutcomeVisibility = (statusSelect) => {
        const targetId = statusSelect.dataset.targetOutcome;
        const outcomeWrap = targetId ? document.getElementById(targetId) : null;

        if (!outcomeWrap) {
            return;
        }

        const outcomeSelect = outcomeWrap.querySelector('[data-close-outcome-select]');
        const isCancelled = statusSelect.value === 'cancelled';

        outcomeWrap.classList.toggle('hidden', isCancelled);

        if (outcomeSelect) {
            outcomeSelect.required = !isCancelled;

            if (isCancelled) {
                outcomeSelect.value = '';
            }
        }
    };

    closeStatusSelects.forEach((statusSelect) => {
        syncCloseOutcomeVisibility(statusSelect);
        statusSelect.addEventListener('change', () => syncCloseOutcomeVisibility(statusSelect));
    });

    const createWeaponResultButton = (item, onSelect) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'sj-weapon-picker__option';
        button.innerHTML = `
            <span>${item.client || 'Sin destino'}</span>
            <span>${item.brand || '-'}</span>
            <span>${item.serial || '-'}</span>
            <span>${item.permit_expires_label || 'Sin vencimiento'}</span>
        `;
        button.addEventListener('click', () => onSelect(item));
        return button;
    };

    weaponPickers.forEach((picker) => {
        const input = picker.querySelector('[data-weapon-picker-input]');
        const hidden = picker.querySelector('[data-weapon-picker-value]');
        const menu = picker.querySelector('[data-weapon-picker-menu]');
        const results = picker.querySelector('[data-weapon-picker-results]');
        const selectedBox = picker.querySelector('[data-weapon-picker-selected]');
        const selectedSummary = picker.querySelector('[data-weapon-picker-selected-summary]');
        const selectedMeta = picker.querySelector('[data-weapon-picker-selected-meta]');
        const clearButton = picker.querySelector('[data-weapon-picker-clear]');
        const searchUrl = picker.dataset.searchUrl;

        if (!input || !hidden || !menu || !results || !selectedBox || !searchUrl) {
            return;
        }

        let debounceTimer = null;
        let abortController = null;

        const syncRequired = () => {
            hidden.required = hidden.value === '';
        };

        const closeMenu = () => {
            menu.classList.add('hidden');
        };

        const openMenu = () => {
            menu.classList.remove('hidden');
        };

        const applySelection = (item) => {
            hidden.value = String(item.id || '');
            input.value = item.summary || '';
            selectedSummary.textContent = item.summary || '';
            selectedMeta.textContent = `${item.client || 'Sin destino'} / vence ${item.permit_expires_label || 'Sin vencimiento'}`;
            selectedBox.classList.remove('hidden');
            closeMenu();
            syncRequired();
        };

        const clearSelection = () => {
            hidden.value = '';
            input.value = '';
            selectedSummary.textContent = '';
            selectedMeta.textContent = '';
            selectedBox.classList.add('hidden');
            results.innerHTML = '';
            syncRequired();
            window.setTimeout(() => input.focus(), 0);
        };

        const renderEmpty = (message) => {
            results.innerHTML = '';
            const empty = document.createElement('div');
            empty.className = 'sj-weapon-picker__empty';
            empty.textContent = message;
            results.appendChild(empty);
        };

        const runSearch = async (term) => {
            if (abortController) {
                abortController.abort();
            }

            if (term.trim().length < 2) {
                renderEmpty('Escribe al menos 2 caracteres.');
                openMenu();
                return;
            }

            abortController = new AbortController();
            renderEmpty('Buscando armas...');
            openMenu();

            try {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: abortController.signal,
                });

                if (!response.ok) {
                    throw new Error('search_failed');
                }

                const payload = await response.json();
                const items = Array.isArray(payload.items) ? payload.items : [];

                results.innerHTML = '';

                if (items.length === 0) {
                    renderEmpty('No se encontraron armas con ese criterio.');
                    return;
                }

                items.forEach((item) => {
                    results.appendChild(createWeaponResultButton(item, applySelection));
                });
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                renderEmpty('No fue posible cargar resultados.');
            }
        };

        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 2) {
                openMenu();
            }
        });

        input.addEventListener('input', () => {
            hidden.value = '';
            selectedBox.classList.add('hidden');
            syncRequired();

            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                runSearch(input.value);
            }, 180);
        });

        clearButton?.addEventListener('click', clearSelection);

        document.addEventListener('click', (event) => {
            if (!picker.contains(event.target)) {
                closeMenu();
            }
        });

        syncRequired();
    });

    if (!moduleRoot) {
        return;
    }

    const focusIncidentId = String(moduleRoot.dataset.openIncidentCase || '').trim();
    const shouldOpenCreateModal = moduleRoot.dataset.openCreateModal === '1';

    if (focusIncidentId !== '') {
        openModal(modalMap.get(`incident-case-${focusIncidentId}`));
    } else if (shouldOpenCreateModal) {
        openModal(modalMap.get('weapon-incident-modal'));
    }
};

document.addEventListener('DOMContentLoaded', initReportsIncidents);
