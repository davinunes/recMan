<?php
require_once "classes/repositorio.php";
require_once "vendor/autoload.php";
use Minishlink\WebPush\VAPID;

echo "<h3>Iniciando Migration para Web Push Notifications</h3>";

$sql = file_get_contents("storage/migrations_20260329.sql");

if (DBMultiExecute($sql)) {
    echo "<p style='color:green;'>1. Tabelas criadas ou ja existentes.</p>";
} else {
    echo "<p style='color:red;'>Erro ao criar tabelas.</p>";
}


echo "<h3>Migrate concluído com sucesso!</h3>";
?>