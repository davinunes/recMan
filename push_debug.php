<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "classes/repositorio.php";
require_once "classes/push_helper.php";

echo "<h2>Raio-X do Motor de Push Notifications</h2>";

// 1. Quantas pessoas deram permissão no navegador?
$res = DBExecute('SELECT * FROM push_subscriptions');
$subs = mysqli_fetch_all($res, MYSQLI_ASSOC);

echo "<h3>Dispositivos Cadastrados Agora (" . count($subs) . "):</h3>";
echo "<pre>";
print_r($subs);
echo "</pre><hr>";

// 2. Testando Disparo Forçado
echo "<h3>Teste Brutal de Disparo:</h3>";

try {
    echo "<ul>";
    if (!class_exists('Minishlink\WebPush\WebPush')) {
        echo "<li>ERRO: A classe Minishlink\WebPush\WebPush não foi encontrada.</li>";
    } else {
        echo "<li>OK: Classe WebPush encontrada.</li>";
    }

    $vapidPath = __DIR__ . '/classes/vapid.json';
    if (!file_exists($vapidPath)) {
        echo "<li>ERRO: Arquivo $vapidPath não existe.</li>";
    } else {
        echo "<li>OK: Arquivo VAPID existe.</li>";
    }

    $resTest = DBExecute('SELECT id FROM push_subscriptions');
    if (!$resTest || mysqli_num_rows($resTest) == 0) {
        echo "<li>ERRO: Nenhuma assinatura no banco (retornado 0 linhas).</li>";
    } else {
        echo "<li>OK: " . mysqli_num_rows($resTest) . " assinaturas no DB.</li>";
    }
    echo "</ul>";

    echo "Disparando agora...<br>";

    // Configurando WebPush direto aqui pra debugar o que o Google responde
    $vapid = json_decode(file_get_contents(__DIR__ . '/classes/vapid.json'), true);
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:admin@recman.miami',
            'publicKey' => $vapid['publicKey'],
            'privateKey' => $vapid['privateKey'],
        ],
    ];
    $webPush = new \Minishlink\WebPush\WebPush($auth);
    $payload = json_encode(['title' => 'Teste', 'body' => 'Corpo', 'url' => '/']);

    foreach ($subs as $sub) {
        $subscription = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['p256dh'],
            'authToken' => $sub['auth']
        ]);
        $webPush->sendOneNotification($subscription, $payload);
    }

    $reports = $webPush->flush();
    echo "<h4>Resultados do Envio ao provedor (Google/Apple):</h4><ul>";
    $successCount = 0;
    foreach ($reports as $report) {
        if ($report->isSuccess()) {
            echo "<li style='color:green;'>[OK] Notificação enviada. Seu celular TEVE que apitar.</li>";
            $successCount++;
        } else {
            echo "<li style='color:red;'>[FALHA] Motivo: " . $report->getReason() . " (Código HTTP: " . $report->getResponse()->getStatusCode() . ")</li>";
        }
    }
    echo "</ul>";
} catch (\Throwable $e) {
    echo "<h4 style='color:red;'>ALERTA CRÍTICO:</h4>";
    echo "<p><b>O Push morreu com a seguinte mensagem de erro do servidor:</b></p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
