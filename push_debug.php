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
    $sucesso = sendPushNotification("Push de Teste", "Seu servidor atirou com sucesso!", "https://" . $_SERVER['HTTP_HOST'] . "/recMan/index.php");
    if ($sucesso) {
        echo "<h4 style='color:green;'>Push disparado aos " . count($subs) . " conselheiros com sucesso!</h4>";
    } else {
        echo "<h4 style='color:orange;'>Função retornou FALSE. Se tudo acima estiver OK, pode ser que a consulta filtrada não encontrou o array de users (embora aqui mandemos pra todos).</h4>";
    }
} catch (\Throwable $e) {
    echo "<h4 style='color:red;'>ALERTA CRÍTICO:</h4>";
    echo "<p><b>O Push morreu com a seguinte mensagem de erro do servidor:</b></p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
