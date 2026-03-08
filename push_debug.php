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
    $sucesso = sendPushNotification("Push de Teste", "Seu servidor atirou com sucesso!", "https://" . $_SERVER['HTTP_HOST'] . "/recMan/index.php");
    if ($sucesso) {
        echo "<h4 style='color:green;'>Push disparado aos " . count($subs) . " conselheiros com sucesso!</h4>";
    } else {
        echo "<h4 style='color:orange;'>Função retornou FALSE. (Sem erro detectado, possivelmente nenhum dispositivo salvo no banco apto para envio).</h4>";
    }
} catch (\Throwable $e) {
    echo "<h4 style='color:red;'>ALERTA CRÍTICO:</h4>";
    echo "<p><b>O Push morreu com a seguinte mensagem de erro do servidor:</b></p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
