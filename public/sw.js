/* Autoluz Web Push service worker */
self.addEventListener('push', (event) => {
    let data = {
        title: 'Autoluz',
        body: 'Ada update baru',
        url: '/',
        icon: '/favicon.ico',
        tag: 'autoluz',
    };

    try {
        if (event.data) {
            data = { ...data, ...event.data.json() };
        }
    } catch (_) {
        // ignore malformed payload
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'Autoluz', {
            body: data.body || '',
            icon: data.icon || '/favicon.ico',
            badge: '/favicon.ico',
            tag: data.tag || 'autoluz',
            data: { url: data.url || '/' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification?.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.navigate?.(target);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(target);
            }
            return undefined;
        }),
    );
});
