// Service Worker recMan - v3.0 (2026-11-17) - Suporte a focusWindow, tag (agrupamento) e actions
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
            icon: data.icon || 'https://mini.davinunes.eti.br/storage/icons/logo-conselho.png?v=20261116',
            badge: data.badge || 'https://mini.davinunes.eti.br/favicon/logo-conselho/96-96.png?v=20261116',
            vibrate: [200, 100, 200, 100, 200, 100, 200], // vibração de alerta
            data: {
                url: data.url || '/',
                actions: data.actions || []
            }
        };

        // Agrupamento Inteligente no Android/Chrome por recurso
        if (data.tag) {
            options.tag = data.tag;
            options.renotify = (data.renotify !== undefined) ? data.renotify : true;
        }

        // Botões de Ação Rápida (Actions)
        if (data.actions && Array.isArray(data.actions)) {
            options.actions = data.actions.map(function (act) {
                return {
                    action: act.action,
                    title: act.title,
                    icon: act.icon || undefined
                };
            });
        }

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close(); // Fecha o popup no celular

    let targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/';

    // Se o conselheiro tocou em um botão de ação específico (ex: 'ver_parecer' ou 'abrir_recurso')
    if (event.action && event.notification.data && event.notification.data.actions) {
        const clickedAction = event.notification.data.actions.find(function (a) {
            return a.action === event.action;
        });
        if (clickedAction && clickedAction.url) {
            targetUrl = clickedAction.url;
        }
    }

    const absoluteTargetUrl = new URL(targetUrl, self.location.origin).href;

    // focusWindow: Foca na aba já existente sem abrir abas duplicadas no celular
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i++) {
                let client = clientList[i];
                if ('focus' in client) {
                    return client.focus().then(function (focusedClient) {
                        if (focusedClient && 'navigate' in focusedClient) {
                            return focusedClient.navigate(absoluteTargetUrl);
                        }
                    });
                }
            }
            // Se nenhuma aba estava aberta, abre uma nova janela/aba
            if (clients.openWindow) {
                return clients.openWindow(absoluteTargetUrl);
            }
        })
    );
});
