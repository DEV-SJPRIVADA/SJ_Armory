/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the `XSRF` token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo + broadcasting: `pusher` (Pusher Cloud / Soketi) or `reverb` (self-hosted).
 * Configure with VITE_BROADCAST_CONNECTION in .env (must match BROADCAST_CONNECTION).
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echoAuth = {
    authEndpoint: `${window.location.origin}/broadcasting/auth`,
    auth: {
        headers: {
            'X-CSRF-TOKEN':
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ??
                '',
            Accept: 'application/json',
        },
    },
};

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

function buildEcho() {
    const driver = import.meta.env.VITE_BROADCAST_CONNECTION ?? 'reverb';

    if (driver === 'pusher') {
        const key = import.meta.env.VITE_PUSHER_APP_KEY;
        if (!key) {
            console.warn('[Echo] VITE_BROADCAST_CONNECTION=pusher pero falta VITE_PUSHER_APP_KEY.');
            return null;
        }

        const customHost = import.meta.env.VITE_PUSHER_HOST?.trim();
        if (customHost) {
            const scheme = import.meta.env.VITE_PUSHER_SCHEME ?? 'http';
            const port = Number(import.meta.env.VITE_PUSHER_PORT ?? 6001);

            return new Echo({
                broadcaster: 'pusher',
                key,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
                wsHost: customHost,
                wsPort: scheme === 'https' ? 443 : port,
                wssPort: port,
                forceTLS: scheme === 'https',
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
                ...echoAuth,
            });
        }

        return new Echo({
            broadcaster: 'pusher',
            key,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
            disableStats: true,
            ...echoAuth,
        });
    }

    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
    const reverbPort = import.meta.env.VITE_REVERB_PORT ?? '6001';
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
    if (!reverbKey) {
        console.warn('[Echo] Falta VITE_REVERB_APP_KEY (modo reverb).');
        return null;
    }

    return new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: resolveReverbWsHost(),
        wsPort: reverbScheme === 'https' ? 443 : Number(reverbPort),
        wssPort: Number(reverbPort),
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        ...echoAuth,
    });
}

window.Echo = buildEcho();
