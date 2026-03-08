self.addEventListener('push', function (event) {
    if (event.data) {
        let data = event.data.json();

        let options = {
            body: data.body,
            icon: data.icon || 'https://cdn-icons-png.flaticon.com/512/3239/3239952.png',
            vibrate: [200, 100, 200, 100, 200, 100, 200], // vibração bacana de notificação
            data: { url: data.url }
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close(); // Fecha o popup no celular assim que a pessoa toca

    if (event.notification.data && event.notification.data.url) {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
});
