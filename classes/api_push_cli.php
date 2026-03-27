<?php
// Central de envio de push via CLI
// Acesso restrito ao servidor (curl de 127.0.0.1)

// SEGURANÇA: Só aceita se a chamada vier do próprio servidor
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Acesso negado');
}

$titulo = $_POST['titulo'] ?? 'Sistema de Recursos';
$mensagem = $_POST['mensagem'] ?? null;
$url = $_POST['url'] ?? '/';
$user_ids = $_POST['user_ids'] ?? null; // Serializado como string se vier via POST

// --- DEBUG LOG ---
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'titulo' => $titulo,
    'mensagem' => $mensagem,
    'user_ids' => $user_ids
];
file_put_contents(__DIR__ . '/push_debug.log', json_encode($logData) . PHP_EOL, FILE_APPEND);
// -----------------

if ($mensagem) {
    require_once "push_helper.php";
    require_once "database.php";

    if ($user_ids && is_string($user_ids)) {
        $user_ids = explode(',', $user_ids);
    }

    sendPushNotification($titulo, $mensagem, $url, $user_ids);
}