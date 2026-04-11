import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Laravel Echo — Reverb WebSocket Bağlantısı
 *
 * Frontend'i Laravel Reverb'e bağlar.
 * PriceUpdated event'lerini gerçek zamanlı dinlemek için kullanılır.
 *
 * @see TASKS.md — Görev M-17
 */

// Pusher'ı window objesine ekle (Echo gereksinimi)
(window as any).Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

export default echo;
