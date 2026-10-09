self.addEventListener('push', function (event) {
    if (event.data) {
        let data = {};
        try {
            data = event.data.json();
        } catch (e) {
            // Se o Payload não for JSON (como clicar no botão de Teste do Chrome)
            data = {
                title: 'Aviso',
                body: event.data.text(),
                url: '/'
            };
        }

        let options = {
            body: data.body,
            icon: data.icon || 'https://mini.davinunes.eti.br/storage/icons/logo-conselho.png',
            badge: data.badge || 'https://mini.davinunes.eti.br/favicon/logo-conselho/96-96.png',
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
