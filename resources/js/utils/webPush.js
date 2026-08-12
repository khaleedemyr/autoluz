function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
    }
    return output;
}

export function pushSupported() {
    return typeof window !== 'undefined'
        && 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window;
}

export async function ensureServiceWorker() {
    if (!pushSupported()) return null;
    const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
    await navigator.serviceWorker.ready;
    return registration;
}

/**
 * Ask permission (if needed), subscribe, and sync to backend.
 * @param {{ vapidPublicKey?: string, email?: string|null, brandIds?: number[], wantsNewsletter?: boolean }} options
 */
export async function enableWebPush(options = {}) {
    if (!pushSupported()) {
        return { ok: false, reason: 'unsupported' };
    }

    const vapidPublicKey = options.vapidPublicKey;
    if (!vapidPublicKey) {
        return { ok: false, reason: 'missing_vapid' };
    }

    let permission = Notification.permission;
    if (permission === 'default') {
        permission = await Notification.requestPermission();
    }
    if (permission !== 'granted') {
        return { ok: false, reason: 'denied' };
    }

    const registration = await ensureServiceWorker();
    if (!registration) {
        return { ok: false, reason: 'sw_failed' };
    }

    let subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });
    }

    const json = subscription.toJSON();
    await window.axios.post('/push/subscribe', {
        endpoint: json.endpoint,
        keys: json.keys,
        contentEncoding: subscription.options?.applicationServerKey ? 'aes128gcm' : 'aesgcm',
        email: options.email || null,
        brand_ids: options.brandIds || [],
        wants_newsletter: !!options.wantsNewsletter,
    });

    return { ok: true, subscription };
}

/**
 * Sync followed brands (and optional newsletter flag) to the current push subscription.
 */
export async function syncPushPreferences({ brandIds = [], email = null, wantsNewsletter = undefined, vapidPublicKey = null } = {}) {
    if (!pushSupported() || Notification.permission !== 'granted') {
        return { ok: false, reason: 'not_enabled' };
    }

    const registration = await ensureServiceWorker();
    let subscription = await registration?.pushManager.getSubscription();

    if (!subscription && vapidPublicKey) {
        const enabled = await enableWebPush({
            vapidPublicKey,
            email,
            brandIds,
            wantsNewsletter: !!wantsNewsletter,
        });
        return enabled;
    }

    if (!subscription) {
        return { ok: false, reason: 'no_subscription' };
    }

    const json = subscription.toJSON();
    const payload = {
        endpoint: json.endpoint,
        keys: json.keys,
        contentEncoding: 'aes128gcm',
        brand_ids: brandIds,
    };
    if (email) payload.email = email;
    if (typeof wantsNewsletter === 'boolean') payload.wants_newsletter = wantsNewsletter;

    await window.axios.post('/push/subscribe', payload);
    return { ok: true };
}
