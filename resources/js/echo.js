import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export function initEcho() {
    if (window.Echo) return window.Echo;

    const cfg = window.examGuardConfig || {};

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: cfg.reverbAppKey,
        wsHost: cfg.reverbHost,
        wsPort: cfg.reverbPort || 80,
        wssPort: cfg.reverbPort || 443,
        forceTLS: (cfg.reverbScheme || 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    return window.Echo;
}

export function subscribeMonitor(examId, handlers = {}) {
    const echo = initEcho();
    const channel = echo.private(`exam.${examId}.monitor`);

    if (handlers.onJoined)    channel.listen('.StudentJoined', handlers.onJoined);
    if (handlers.onSubmitted) channel.listen('.StudentSubmitted', handlers.onSubmitted);
    if (handlers.onLocked)    channel.listen('.StudentLocked', handlers.onLocked);
    if (handlers.onIncident)  channel.listen('.IncidentRecorded', handlers.onIncident);
    if (handlers.onUnlocked)  channel.listen('.ExamUnlocked', handlers.onUnlocked);

    return channel;
}
