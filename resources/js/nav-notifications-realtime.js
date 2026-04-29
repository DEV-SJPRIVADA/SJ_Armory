/**
 * Actualiza contador y lista del modal vía Pusher (canal privado del usuario).
 */
function initNavNotificationsRealtime() {
    if (!window.Echo) {
        return;
    }

    const uid = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    if (!uid) {
        return;
    }

    try {
        window.Echo.private(`App.Models.User.${uid}`)
            .listen('.InboxUpdated', (payload) => {
                const count = typeof payload?.unread_count === 'number' ? payload.unread_count : 0;
                window.dispatchEvent(new CustomEvent('inbox-updated', { detail: { unread_count: count } }));
            });
    } catch (error) {
        console.warn('[Inbox] Echo private user channel', error);
    }
}

initNavNotificationsRealtime();
