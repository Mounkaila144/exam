import './bootstrap';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from(raw, (c) => c.charCodeAt(0));
}

export async function subscribePush(vapidPublicKey, storeUrl) {
    if (! ('serviceWorker' in navigator) || ! ('PushManager' in window)) {
        throw new Error('Notifications non supportées par ce navigateur.');
    }

    const reg = await navigator.serviceWorker.register('/sw.js');
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        throw new Error('Permission refusée.');
    }

    const subscription = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    await window.axios.post(storeUrl, subscription.toJSON());
    return subscription;
}

window.examGuardPush = { subscribePush };
