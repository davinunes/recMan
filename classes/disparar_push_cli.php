<?php
require_once "push_helper.php";
$id_usuario = $argv[1];
$id_recurso = $argv[2];

$usuario = getUsuariosById($id_usuario);
@sendPushNotification(
    "Sistema de Recursos do Conselho",
    "O conselheiro {$usuario['nome']} acabou de votar no recurso $id_recurso"
);