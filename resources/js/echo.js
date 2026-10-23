import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

const key = import.meta.env.VITE_REVERB_APP_KEY;

if (key) {
    const scheme =
        import.meta.env.VITE_REVERB_SCHEME ||
        (window.location.protocol === 'https:' ? 'https' : 'http');
    const secure = scheme === 'https';
    const port = Number(
        import.meta.env.VITE_REVERB_PORT || (secure ? 443 : 8080),
    );

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: port,
        wssPort: port,
        forceTLS: secure,
        enabledTransports: ['ws', 'wss'],
    });
}
