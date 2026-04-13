function normalizePath(pathname) {
    const p = pathname || '';
    if (p === '/' || p === '') {
        return '/';
    }

    return p.replace(/\/+$/, '') || '/';
}

function isWeaponsListPath(pathname) {
    return normalizePath(pathname) === '/weapons';
}

function initRealtimeWeaponsSync() {
    if (!window.Echo || !isWeaponsListPath(window.location.pathname)) {
        return;
    }

    const tbody = document.getElementById('weapons-tbody');
    const pagination = document.getElementById('weapons-pagination');

    if (!tbody || !pagination) {
        return;
    }

    const refreshWeaponsList = async () => {
        const response = await fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        if (typeof data?.tbody !== 'string' || typeof data?.pagination !== 'string') {
            return;
        }

        tbody.innerHTML = data.tbody;
        pagination.innerHTML = data.pagination;
        window.syncWeaponsHorizontalScrollbar?.();
    };

    let debounceTimer = null;
    let pendingRefresh = false;
    const scheduleRefresh = () => {
        if (document.visibilityState === 'hidden') {
            pendingRefresh = true;
            return;
        }

        pendingRefresh = false;

        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }

        debounceTimer = window.setTimeout(() => {
            debounceTimer = null;
            refreshWeaponsList();
        }, 350);
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && pendingRefresh) {
            scheduleRefresh();
        }
    });

    try {
        const weaponsChannel = window.Echo.private('weapons.updates');
        weaponsChannel.listen('.WeaponChanged', scheduleRefresh);
        weaponsChannel.listen('WeaponChanged', scheduleRefresh);
    } catch (error) {
        console.warn('Echo weapons.updates (WeaponChanged)', error);
    }

    try {
        const assignmentsChannel = window.Echo.private('assignments.updates');
        assignmentsChannel.listen('.AssignmentChanged', scheduleRefresh);
        assignmentsChannel.listen('AssignmentChanged', scheduleRefresh);
    } catch (error) {
        console.warn('Echo assignments.updates (AssignmentChanged)', error);
    }
}

initRealtimeWeaponsSync();
