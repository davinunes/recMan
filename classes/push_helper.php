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
 * @param string|null $icon URL ou caminho do ícone.
 */
function sendPushNotification($titulo, $mensagem, $url = '/', $userIds = null, $icon = null)
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

        // Icone padrão se não for passado
        if (!$icon) {
            $icon = 'https://mini.davinunes.eti.br/storage/icons/conselho-toon.webp';
        }

        $sql = "SELECT id, user_id, endpoint, p256dh, auth, base_url FROM push_subscriptions";
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

            // Se a URL for relativa, tenta usar o base_url do dispositivo do conselheiro
            // Se o base_url for por exemplo https://mini.davinunes.eti.br
            // E a URL for /index.php, o link final será https://mini.davinunes.eti.br/index.php
            $finalUrl = $url;
            if (substr($url, 0, 4) !== 'http') {
                $base = $row['base_url'] ?: 'https://mini.davinunes.eti.br';
                $finalUrl = rtrim($base, '/') . '/' . ltrim($url, '/');
            }

            $subscription = Subscription::create([
                'endpoint' => $row['endpoint'],
                'publicKey' => $row['p256dh'],
                'authToken' => $row['auth']
            ]);

            $devicePayload = json_encode([
                'title' => $titulo,
                'body' => $mensagem,
                'url' => $finalUrl,
                'icon' => $icon
            ]);

            $webPush->sendOneNotification($subscription, $devicePayload);
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
    } catch (\Throwable $e) {
        $msg = $e->getMessage();
        error_log("Erro de Push: " . $msg);
        return false;
    }
}

/**
 * Dispara o envio de Push em background via chamada interna (CLI)
 * Resolve problemas de lentidão no request do usuário e centraliza a lógica de Host/URL/Backgrounding
 * 
 * @param string $titulo
 * @param string $mensagem
 * @param string $url
 * @param array|null $userIds
 */
function sendPushBackground($titulo, $mensagem, $url = '/', $userIds = null)
{
    // Domínio oficial para garantir que o curl interno sempre funcione
    // Mesmo se o request vier do portal (outro dominio), o curl interno deve usar o dominio que tem a pasta /classes/
    $domain = "mini.davinunes.eti.br";

    $postData = [
        'titulo' => $titulo,
        'mensagem' => $mensagem,
        'url' => $url
    ];

    if ($userIds) {
        $postData['user_ids'] = is_array($userIds) ? implode(',', $userIds) : $userIds;
    }

    $postFields = http_build_query($postData);

    // Tenta detectar se estamos em um subdiretório (ex: /recMan/)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = (strpos($scriptName, '/recMan/') !== false) ? '/recMan/' : '/';
    
    $api_url = "http://127.0.0.1" . $basePath . "classes/api_push_cli.php";

    // --- LOG PARA DEBUG INTERNO ---
    $logMsg = date('Y-m-d H:i:s') . " | Calling: $api_url | Host: $domain | Title: $titulo\n";
    @file_put_contents(__DIR__ . '/push_internal.log', $logMsg, FILE_APPEND);
    // ------------------------------

    // Silencia saída e joga para background
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $comando = "start /B curl -s -k -H \"Host: $domain\" -d \"$postFields\" \"$api_url\" > NUL 2>&1";
    } else {
        $comando = "curl -s -k -H \"Host: $domain\" -d \"$postFields\" \"$api_url\" > /dev/null 2>&1 &";
    }

    exec($comando);
}
