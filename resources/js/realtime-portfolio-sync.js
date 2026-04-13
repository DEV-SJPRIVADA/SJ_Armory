/**
 * Recarga vistas cuando cambian asignaciones usuario↔cliente (carteras).
 * Requiere Echo (bootstrap.js) y meta user-id en layout autenticado.
 *
 * Nota: los eventos se emiten en el servidor tras enviar la respuesta (terminating),
 * para que el cliente ya tenga WebSocket suscrito y no se pierda el mensaje en el redirect.
 */

function normalizePath(pathname) {
    const p = pathname || '';
    if (p === '/' || p === '') {
        return '/';
    }
    return p.replace(/\/+$/, '') || '/';
}

function isPortfolioListPath(pathname) {
    return normalizePath(pathname) === '/portfolios';
}

function parseEntityId(payload) {
    if (!payload || typeof payload !== 'object') {
        return null;
    }
    const raw = payload.entity_id ?? payload.data?.entity_id;
    if (raw === undefined || raw === null) {
        return null;
    }
    const n = Number(raw);
    return Number.isNaN(n) ? null : n;
}

function initRealtimePortfolioSync() {
    if (!window.Echo) {
        return;
    }

    const meta = document.querySelector('meta[name="user-id"]');
    if (!meta?.content) {
        return;
    }

    const currentUserId = Number.parseInt(meta.content, 10);
    if (Number.isNaN(currentUserId)) {
        return;
    }

    let debounceTimer = null;
    const scheduleReload = () => {
        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }
        debounceTimer = window.setTimeout(() => {
            debounceTimer = null;
            window.location.reload();
        }, 400);
    };

    const onUserChanged = (payload) => {
        const entityId = parseEntityId(payload);
        if (entityId !== null && entityId === currentUserId) {
            scheduleReload();
        }
    };

    const onPortfolioListChanged = () => {
        if (isPortfolioListPath(window.location.pathname)) {
            scheduleReload();
        }
    };

    try {
        const usersCh = window.Echo.private('users.updates');
        usersCh.listen('.UserChanged', onUserChanged);
        usersCh.listen('UserChanged', onUserChanged);
    } catch (error) {
        console.warn('Echo users.updates (UserChanged)', error);
    }

    try {
        const dashCh = window.Echo.private('dashboard.updates');
        dashCh.listen('.PortfolioAssignmentsChanged', onPortfolioListChanged);
        dashCh.listen('PortfolioAssignmentsChanged', onPortfolioListChanged);
    } catch (error) {
        console.warn('Echo dashboard.updates (PortfolioAssignmentsChanged)', error);
    }
}

initRealtimePortfolioSync();
