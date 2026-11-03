<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueio de autenticação: apenas conselheiro autenticado pode acessar
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    die("Acesso negado. Apenas conselheiros autenticados podem acessar o diagnóstico de push.");
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "classes/repositorio.php";
require_once "classes/push_helper.php";

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Diagnóstico de Push</title>";
echo "<style>body{font-family:sans-serif;max-width:900px;margin:20px auto;padding:0 20px;} pre{background:#f4f4f4;padding:10px;border-radius:4px;overflow-x:auto;} .btn{padding:10px 18px;background:#1976d2;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;} .btn:hover{background:#1565c0;} .box{border:1px solid #ddd;padding:15px;border-radius:6px;margin-bottom:20px;background:#fff;} table{width:100%;border-collapse:collapse;} th,td{padding:8px;border-bottom:1px solid #eee;text-align:left;font-size:13px;} th{background:#f8f9fa;}</style>";
echo "</head><body>";
echo "<h2>Raio-X do Motor de Push Notifications</h2>";

// 1. Quantas pessoas deram permissão no navegador?
$res = DBExecute('SELECT id, user_id, base_url, endpoint, criado_em FROM push_subscriptions');
$subs = mysqli_fetch_all($res, MYSQLI_ASSOC);

echo "<div class='box'>";
echo "<h3>Dispositivos Cadastrados (" . count($subs) . "):</h3>";
if (!empty($subs)) {
    echo "<table><thead><tr><th>ID</th><th>User ID</th><th>Base URL</th><th>Endpoint (Resumo)</th><th>Data</th></tr></thead><tbody>";
    foreach ($subs as $s) {
        $endpointResumo = substr($s['endpoint'], 0, 45) . '...';
        $dataCad = $s['criado_em'] ?? 'N/D';
        echo "<tr><td>{$s['id']}</td><td>{$s['user_id']}</td><td>" . htmlspecialchars($s['base_url'] ?? '') . "</td><td><code>{$endpointResumo}</code></td><td>{$dataCad}</td></tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhum dispositivo cadastrado no banco.</p>";
}
echo "</div>";

// 2. Disparo de teste somente via ação explícita (POST)
$disparar = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disparar_teste']));

echo "<div class='box'>";
echo "<h3>Teste Controlado de Disparo:</h3>";

if (!$disparar) {
    echo "<p>Para evitar disparos acidentais ao recarregar a página ou por indexação, clique no botão abaixo para efetuar um envio de teste manual:</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='disparar_teste' value='1' class='btn'>Disparar Push de Teste Agora</button>";
    echo "</form>";
} else {
    try {
        echo "<ul>";
        if (!class_exists('Minishlink\WebPush\WebPush')) {
            echo "<li style='color:red;'>ERRO: A classe Minishlink\WebPush\WebPush não foi encontrada.</li>";
        } else {
            echo "<li style='color:green;'>OK: Classe WebPush encontrada.</li>";
        }

        $vapidPath = __DIR__ . '/classes/vapid.json';
        if (!file_exists($vapidPath)) {
            echo "<li style='color:red;'>ERRO: Arquivo $vapidPath não existe.</li>";
        } else {
            echo "<li style='color:green;'>OK: Arquivo VAPID existe.</li>";
        }

        $resTest = DBExecute('SELECT id, user_id, endpoint, p256dh, auth FROM push_subscriptions');
        $subsFull = $resTest ? mysqli_fetch_all($resTest, MYSQLI_ASSOC) : [];

        if (empty($subsFull)) {
            echo "<li style='color:red;'>ERRO: Nenhuma assinatura no banco (retornado 0 linhas).</li>";
        } else {
            echo "<li style='color:green;'>OK: " . count($subsFull) . " assinaturas no DB.</li>";
        }
        echo "</ul>";

        echo "<p><strong>Disparando notificação de teste...</strong></p>";

        $vapid = json_decode(file_get_contents($vapidPath), true);
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@recman.miami',
                'publicKey' => $vapid['publicKey'],
                'privateKey' => $vapid['privateKey'],
            ],
        ];
        $webPush = new \Minishlink\WebPush\WebPush($auth);
        $payload = json_encode([
            'title' => 'Teste Manual de Diagnóstico',
            'body' => 'Disparado por conselheiro logado ID: ' . $_SESSION['user_id'],
            'url' => '/'
        ]);

        echo "<h4>Resultados do Envio:</h4><ul>";
        foreach ($subsFull as $sub) {
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $sub['endpoint'],
                'publicKey' => $sub['p256dh'],
                'authToken' => $sub['auth']
            ]);

            $report = $webPush->sendOneNotification($subscription, $payload);

            if ($report) {
                if ($report->isSuccess()) {
                    echo "<li style='color:green;'>[OK] Notificação enviada para Inscrição ID {$sub['id']} (User {$sub['user_id']}).</li>";
                } else {
                    echo "<li style='color:red;'>[FALHA] Inscrição ID {$sub['id']} - Motivo: " . htmlentities($report->getReason()) . " (HTTP " . ($report->getResponse() ? $report->getResponse()->getStatusCode() : 'sem status') . ")</li>";
                }
            } else {
                echo "<li style='color:red;'>[FALHA] Inscrição ID {$sub['id']} - Provedor não retornou resposta válida.</li>";
            }
        }
        echo "</ul>";
        echo "<p><a href='push_debug.php'>Voltar</a></p>";
    } catch (\Throwable $e) {
        echo "<h4 style='color:red;'>ALERTA CRÍTICO:</h4>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}
echo "</div>";
echo "</body></html>";
