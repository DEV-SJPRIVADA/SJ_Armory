/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
const reverbPort = import.meta.env.VITE_REVERB_PORT ?? '6001';

function resolveReverbWsHost() {
    const fromEnv = import.meta.env.VITE_REVERB_HOST;
    if (
        fromEnv &&
        fromEnv !== '127.0.0.1' &&
        fromEnv !== 'localhost' &&
        String(fromEnv).trim() !== ''
    ) {
        return fromEnv;
    }
    if (typeof window !== 'undefined' && window.location?.hostname) {
        return window.location.hostname;
    }

    return fromEnv || '127.0.0.1';
}

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: resolveReverbWsHost(),
    wsPort: reverbScheme === 'https' ? 443 : Number(reverbPort),
    wssPort: Number(reverbPort),
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${window.location.origin}/broadcasting/auth`,
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            Accept: 'application/json',
        },
    },
});
