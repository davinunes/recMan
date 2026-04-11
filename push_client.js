document.addEventListener('DOMContentLoaded', async () => {
    // Escuta se o navegador é compatível com Push
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('Web Push não suportado por este navegador.');
        return;
    }

    try {
        // Registra o tratador de notificações que criamos na raiz
        const registration = await navigator.serviceWorker.register('sw.js');

        let subscription = await registration.pushManager.getSubscription();

        // Se ainda não se inscreveu neste aparelho, pergunta e se cadastra no servidor
        if (!subscription) {
            console.log('Solicitando permissão para enviar Push...');
            const response = await fetch('api_push.php?action=get_vapid');
            const data = await response.json();

            if (data.publicKey) {
                const convertedVapidKey = urlBase64ToUint8Array(data.publicKey);
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                });
            }
        }

        // Sempre sincroniza com o servidor (para atualizar ID do usuário ou base_url se mudou)
        if (subscription) {
            const subscriptionData = JSON.parse(JSON.stringify(subscription));
            subscriptionData.base_url = window.location.origin;

            await fetch('api_push.php?action=subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(subscriptionData)
            });

            console.log('Push sincronizado com o servidor!');
        }
    } catch (e) {
        console.error('Falha no Web Push Registration:', e);
    }
});

// Converter a Hash Publica string para int puro
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
