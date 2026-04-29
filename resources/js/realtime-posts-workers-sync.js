function normalizePath(pathname) {
    const p = pathname || '';
    if (p === '/' || p === '') {
        return '/';
    }

    return p.replace(/\/+$/, '') || '/';
}

function initRealtimePostsWorkersSync() {
    if (!window.Echo) {
        return;
    }

    const path = normalizePath(window.location.pathname);
    const setupPosts = path === '/posts';
    const setupWorkers = path === '/workers';

    if (!setupPosts && !setupWorkers) {
        return;
    }

    const refreshPosts = async () => {
        const tbody = document.getElementById('posts-tbody');
        const pagination = document.getElementById('posts-pagination');
        if (!tbody || !pagination) {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('_rt', String(Date.now()));

        const response = await fetch(url.toString(), {
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'Cache-Control': 'no-cache',
                Pragma: 'no-cache',
            },
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
        window.Alpine?.initTree?.(tbody);
    };

    const refreshWorkers = async () => {
        const tbody = document.getElementById('workers-tbody');
        const pagination = document.getElementById('workers-pagination');
        if (!tbody || !pagination) {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('_rt', String(Date.now()));

        const response = await fetch(url.toString(), {
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'Cache-Control': 'no-cache',
                Pragma: 'no-cache',
            },
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
        window.Alpine?.initTree?.(tbody);
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
            if (setupPosts) {
                refreshPosts();
            } else if (setupWorkers) {
                refreshWorkers();
            }
        }, 350);
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && pendingRefresh) {
            scheduleRefresh();
        }
    });

    if (setupPosts) {
        try {
            const channel = window.Echo.private('posts.updates');
            channel.listen('.PostChanged', scheduleRefresh);
            channel.listen('PostChanged', scheduleRefresh);
        } catch (error) {
            console.warn('Echo posts.updates (PostChanged)', error);
        }
    }

    if (setupWorkers) {
        try {
            const channel = window.Echo.private('workers.updates');
            channel.listen('.WorkerChanged', scheduleRefresh);
            channel.listen('WorkerChanged', scheduleRefresh);
        } catch (error) {
            console.warn('Echo workers.updates (WorkerChanged)', error);
        }
    }
}

initRealtimePostsWorkersSync();
