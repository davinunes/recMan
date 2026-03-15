<?php
// /var/www/html/api_notificacao.php

// SEGURANÇA: Só aceita se a chamada vier do próprio servidor
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Acesso negado');
}

$id_usuario = $_POST['user_id'] ?? null;
$id_recurso = $_POST['id_rec'] ?? null;

if ($id_usuario && $id_recurso) {
    require_once "push_helper.php";
    require_once "database.php";

    $usuario = getUsuariosById($id_usuario);
    @sendPushNotification(
        "Sistema de Recursos do Conselho",
        "O conselheiro {$usuario['nome']} acabou de votar no recurso $id_recurso"
    );
}