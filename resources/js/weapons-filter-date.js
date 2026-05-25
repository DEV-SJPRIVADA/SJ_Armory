import Litepicker from 'litepicker';
import 'litepicker/dist/css/litepicker.css';

const ISO_FORMAT = 'YYYY-MM-DD';

/**
 * @returns {{ clear: () => void, close: () => void } | null}
 */
export function initWeaponsFilterDatePicker() {
    const trigger = document.getElementById('filter-permit-range-trigger');
    const triggerText = document.getElementById('filter-permit-range-trigger-text');
    const fromHidden = document.getElementById('filter-permit-from');
    const toHidden = document.getElementById('filter-permit-to');
    const popover = document.getElementById('weapons-filter-date-popover');
    const anchor = document.getElementById('filter-permit-picker-anchor');
    const mount = document.getElementById('filter-permit-picker-mount');
    const hintEl = document.getElementById('weapons-filter-date-hint');
    const doneButton = document.getElementById('weapons-filter-date-done');
    const clearButton = document.getElementById('weapons-filter-date-clear');
    const errorEl = document.getElementById('weapons-filter-date-error');

    if (!trigger || !triggerText || !fromHidden || !toHidden || !popover || !anchor || !mount) {
        return null;
    }

    const placeholder = trigger.dataset.placeholder || 'Seleccione rango';
    const hintDefault = hintEl?.dataset.defaultHint
        || 'Seleccione la fecha de inicio (calendario izquierdo) y la de fin (derecho).';
    const hintStartOnly = hintEl?.dataset.startOnlyHint
        || 'Inicio elegido. Elija la fecha final (igual o posterior; puede ser otro mes o año).';
    const hintComplete = hintEl?.dataset.completeHint || 'Rango listo. Pulse Listo para confirmar.';

    let committedFrom = fromHidden.value.trim();
    let committedTo = toHidden.value.trim();
    let isOpen = false;

    const picker = new Litepicker({
        element: anchor,
        parentEl: mount,
        inlineMode: true,
        singleMode: false,
        numberOfMonths: 2,
        numberOfColumns: 2,
        splitView: true,
        selectForward: true,
        allowRepick: true,
        scrollToDate: true,
        format: ISO_FORMAT,
        lang: 'es',
        autoApply: true,
        showTooltip: true,
        firstDay: 1,
        dropdowns: {
            months: true,
            years: true,
            minYear: 2015,
            maxYear: new Date().getFullYear() + 15,
        },
        buttonText: {
            apply: 'Listo',
            cancel: 'Cancelar',
            previousMonth: '‹',
            nextMonth: '›',
            reset: 'Limpiar',
        },
        setup: (instance) => {
            instance.on('selected', (start, end) => {
                updateSelectionHint(start, end);
            });

            instance.on('clear:selection', () => {
                updateSelectionHint(null, null);
            });
        },
    });

    const hideError = () => {
        if (!errorEl) {
            return;
        }

        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    };

    const showError = (message) => {
        if (!errorEl) {
            return;
        }

        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
    };

    const setHint = (message) => {
        if (hintEl) {
            hintEl.textContent = message;
        }
    };

    const updateSelectionHint = (start, end) => {
        if (!start && !end) {
            setHint(hintDefault);
            return;
        }

        if (start && !end) {
            setHint(hintStartOnly);
            return;
        }

        setHint(hintComplete);
    };

    const formatIsoForDisplay = (iso) => {
        if (!iso) {
            return '';
        }

        const parts = iso.split('-').map((part) => Number.parseInt(part, 10));
        if (parts.length !== 3 || parts.some((part) => Number.isNaN(part))) {
            return iso;
        }

        const [year, month, day] = parts;
        const date = new Date(year, month - 1, day);

        return date.toLocaleDateString('es-CO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    const syncTrigger = () => {
        const from = fromHidden.value.trim();
        const to = toHidden.value.trim();

        if (from === '' && to === '') {
            triggerText.textContent = placeholder;
            trigger.classList.remove('is-active');
            return;
        }

        const fromLabel = from !== '' ? formatIsoForDisplay(from) : '…';
        const toLabel = to !== '' ? formatIsoForDisplay(to) : '…';
        triggerText.textContent = `${fromLabel} – ${toLabel}`;
        trigger.classList.add('is-active');
    };

    const focusPickerOnDate = (iso) => {
        const target = iso || new Date().toISOString().slice(0, 10);
        picker.gotoDate(target);
    };

    const applyPickerSelection = (from, to) => {
        hideError();

        if (from && to) {
            picker.setDateRange(from, to);
            focusPickerOnDate(from);
            updateSelectionHint(picker.getStartDate(), picker.getEndDate());
            return;
        }

        picker.clearSelection();

        if (from) {
            picker.setStartDate(from);
            focusPickerOnDate(from);
            updateSelectionHint(picker.getStartDate(), picker.getEndDate());
            return;
        }

        focusPickerOnDate(null);
        updateSelectionHint(null, null);
    };

    const openPopover = () => {
        committedFrom = fromHidden.value.trim();
        committedTo = toHidden.value.trim();
        applyPickerSelection(committedFrom, committedTo);
        popover.classList.remove('hidden');
        popover.setAttribute('aria-hidden', 'false');
        trigger.setAttribute('aria-expanded', 'true');
        isOpen = true;
    };

    const closePopover = ({ revert = true } = {}) => {
        if (revert) {
            applyPickerSelection(committedFrom, committedTo);
        }

        hideError();
        setHint(hintDefault);
        popover.classList.add('hidden');
        popover.setAttribute('aria-hidden', 'true');
        trigger.setAttribute('aria-expanded', 'false');
        isOpen = false;
    };

    const commitSelection = () => {
        const start = picker.getStartDate();
        const end = picker.getEndDate();

        if (!start || !end) {
            showError('Seleccione la fecha de inicio y la de fin del rango.');
            return false;
        }

        if (start.isAfter(end)) {
            showError('La fecha «hasta» debe ser igual o posterior a «desde».');
            return false;
        }

        const from = start.format(ISO_FORMAT);
        const to = end.format(ISO_FORMAT);

        fromHidden.value = from;
        toHidden.value = to;
        committedFrom = from;
        committedTo = to;
        syncTrigger();
        hideError();
        closePopover({ revert: false });

        return true;
    };

    const clear = () => {
        fromHidden.value = '';
        toHidden.value = '';
        committedFrom = '';
        committedTo = '';
        picker.clearSelection();
        setHint(hintDefault);
        syncTrigger();
        hideError();

        if (isOpen) {
            closePopover({ revert: false });
        }
    };

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (isOpen) {
            closePopover({ revert: true });
            return;
        }

        openPopover();
    });

    doneButton?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        commitSelection();
    });

    clearButton?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        picker.clearSelection();
        setHint(hintDefault);
        hideError();
    });

    popover.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    mount.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    document.addEventListener('click', (event) => {
        if (!isOpen) {
            return;
        }

        const target = event.target;
        if (!(target instanceof Node)) {
            return;
        }

        if (popover.contains(target) || trigger.contains(target)) {
            return;
        }

        closePopover({ revert: true });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            closePopover({ revert: true });
        }
    });

    setHint(hintDefault);
    syncTrigger();

    return {
        clear,
        close: () => closePopover({ revert: true }),
    };
}
