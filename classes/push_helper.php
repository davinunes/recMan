<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/repositorio.php";

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Obtém a configuração das chaves VAPID do sistema.
 * Se o arquivo vapid.json não existir ou estiver corrompido, utiliza as chaves oficiais e restaura o arquivo físico.
 * 
 * @return array ['publicKey' => string, 'privateKey' => string]
 */
function getVapidConfig()
{
    $vapidPath = __DIR__ . '/vapid.json';
    if (file_exists($vapidPath)) {
        $content = @file_get_contents($vapidPath);
        $data = json_decode($content, true);
        if (is_array($data) && !empty($data['publicKey']) && !empty($data['privateKey']) && strpos($data['publicKey'], '{{') === false) {
            return $data;
        }
    }

    // Chaves oficiais do recMan compatíveis com inscrições já salvas no banco
    $defaultKeys = [
        'publicKey' => 'BGaKShgVE_Z2WPds6j9pUoLjuEBZ14io6fUXWDVBriycLhdvNHKdyHUP0g3LZXDLb9ZXsehEY5dbp1uC-NsdoZY',
        'privateKey' => 'x-Av76CQzKwX3KqGbQ8ZaU81eBO3bAYu45llWTmziWY',
    ];

    // Tenta persistir para leituras rápidas subsequentes
    @file_put_contents($vapidPath, json_encode($defaultKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $defaultKeys;
}

/**
 * Envia um Push Notification via Web (Celular ou Navegador)
 * 
 * @param string $titulo O Título do Pop Up
 * @param string $mensagem O Corpo do Pop Up
 * @param string $url URL para qual o usuário será redirecionado se clicar
 * @param array|null $userIds Array opcional de IDs de usuários. Null envia para todos os conselheiros.
 * @param string|null $icon URL ou caminho do ícone.
 * @param string|null $tag Tag de agrupamento para substituir notificações do mesmo assunto/recurso.
 * @param array|null $actions Ações interativas (botões) na notificação.
 */
function sendPushNotification($titulo, $mensagem, $url = '/', $userIds = null, $icon = null, $tag = null, $actions = null)
{
    try {
        if (!class_exists('Minishlink\WebPush\WebPush')) {
            error_log("WebPush class does not exist");
            return false;
        }

        $vapid = getVapidConfig();
        if (empty($vapid['publicKey']) || empty($vapid['privateKey'])) {
            error_log("Chaves VAPID não configuradas ou inválidas.");
            return false;
        }

        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@recman.miami', // Identificação obrigatória do Firebase
                'publicKey' => $vapid['publicKey'],
                'privateKey' => $vapid['privateKey'],
            ],
        ];

        $webPush = new WebPush($auth);

        // Icone padrão se não for passado (Novo Logo do Conselho) com Cache Busting
        $iconFile = __DIR__ . '/../storage/icons/logo-conselho.png';
        $v = file_exists($iconFile) ? filemtime($iconFile) : '20261116';
        if (!$icon) {
            $icon = 'https://mini.davinunes.eti.br/storage/icons/logo-conselho.png?v=' . $v;
        } elseif (strpos($icon, 'logo-conselho.png') !== false && strpos($icon, '?') === false) {
            $icon .= '?v=' . $v;
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
            $base = $row['base_url'] ?: 'https://mini.davinunes.eti.br';
            $finalUrl = $url;
            if (substr($url, 0, 4) !== 'http') {
                $finalUrl = rtrim($base, '/') . '/' . ltrim($url, '/');
            }

            $subscription = Subscription::create([
                'endpoint' => $row['endpoint'],
                'publicKey' => $row['p256dh'],
                'authToken' => $row['auth']
            ]);

            $payloadData = [
                'title' => $titulo,
                'body' => $mensagem,
                'url' => $finalUrl,
                'icon' => $icon,
                'badge' => 'https://mini.davinunes.eti.br/favicon/logo-conselho/96-96.png?v=' . $v
            ];

            // Agrupamento Inteligente no Android/Chrome
            if (!empty($tag)) {
                $payloadData['tag'] = $tag;
                $payloadData['renotify'] = true;
            }

            // Ações interativas (botões de ação)
            if (!empty($actions) && is_array($actions)) {
                $resolvedActions = [];
                foreach ($actions as $act) {
                    $actUrl = $act['url'] ?? $finalUrl;
                    if (substr($actUrl, 0, 4) !== 'http') {
                        $actUrl = rtrim($base, '/') . '/' . ltrim($actUrl, '/');
                    }
                    $resolvedActions[] = [
                        'action' => $act['action'],
                        'title' => $act['title'],
                        'url' => $actUrl,
                        'icon' => $act['icon'] ?? null
                    ];
                }
                $payloadData['actions'] = $resolvedActions;
            }

            $devicePayload = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
 * @param string|null $icon URL ou caminho do ícone.
 * @param string|null $tag Tag de agrupamento por recurso.
 * @param array|null $actions Ações rápidas na notificação.
 */
function sendPushBackground($titulo, $mensagem, $url = '/', $userIds = null, $icon = null, $tag = null, $actions = null)
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

    if ($icon) {
        $postData['icon'] = $icon;
    }

    if ($tag) {
        $postData['tag'] = $tag;
    }

    if ($actions) {
        $postData['actions'] = json_encode($actions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
