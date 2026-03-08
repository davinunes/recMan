<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/repositorio.php";

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Envia um Push Notification via Web (Celular ou Navegador)
 * 
 * @param string $titulo O Título do Pop Up
 * @param string $mensagem O Corpo do Pop Up
 * @param string $url URL para qual o usuário será redirecionado se clicar
 * @param array|null $userIds Array opcional de IDs de usuários. Null envia para todos os conselheiros.
 */
function sendPushNotification($titulo, $mensagem, $url = '/', $userIds = null)
{
    try {
        if (!class_exists('Minishlink\WebPush\WebPush')) {
            error_log("WebPush class does not exist");
            return false;
        }

        $vapidPath = __DIR__ . '/vapid.json';
        if (!file_exists($vapidPath)) {
            error_log("Vapid file does not exist at $vapidPath");
            return false;
        }

        $vapid = json_decode(file_get_contents($vapidPath), true);
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@recman.miami', // Identificação obrigatória do Firebase
                'publicKey' => $vapid['publicKey'],
                'privateKey' => $vapid['privateKey'],
            ],
        ];

        $webPush = new WebPush($auth);

        // Payload que o Service Worker do Chrome vai interpretar:
        $payload = json_encode([
            'title' => $titulo,
            'body' => $mensagem,
            'url' => $url,
            // Ícone customizável (usando um sininho genérico, você pode trocar pelo logo do condomínio em PNG)
            'icon' => 'https://cdn-icons-png.flaticon.com/512/3239/3239952.png'
        ]);

        $sql = "SELECT id, user_id, endpoint, p256dh, auth FROM push_subscriptions";
        if (!empty($userIds) && is_array($userIds)) {
            $ids = implode(',', array_map('intval', $userIds));
            $sql .= " WHERE user_id IN ($ids)";
        }

        $res = DBExecute($sql);
        if (!$res || mysqli_num_rows($res) == 0) {
            error_log("No push_subscriptions rows found");
            return false;
        }

        $hasSubs = false;
        while ($row = mysqli_fetch_assoc($res)) {
            $hasSubs = true;

            $subscription = Subscription::create([
                'endpoint' => $row['endpoint'],
                'publicKey' => $row['p256dh'],
                'authToken' => $row['auth']
            ]);

            $webPush->sendOneNotification($subscription, $payload);
        }

        if ($hasSubs) {
            // Limpa as subscrições velhas (quando a pessoa formata o celular, por exemplo)
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                if (!$report->isSuccess() && in_array($report->getResponse()->getStatusCode(), [404, 410])) {
                    DBExecute("DELETE FROM push_subscriptions WHERE endpoint = '" . DBEscape($endpoint) . "'");
                }
            }
        }
        return true;
        return true;
    } catch (\Throwable $e) {
        $msg = $e->getMessage();
        error_log("Erro de Push: " . $msg);
        throw new \Exception($msg);
    }
}
