<?php
require_once "classes/repositorio.php";
require_once "vendor/autoload.php";
use Minishlink\WebPush\VAPID;

echo "<h3>Iniciando Migration para Web Push Notifications</h3>";

$sql = "CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (DBExecute($sql)) {
    echo "<p style='color:green;'>1. Tabela <b>push_subscriptions</b> criada ou ja existente.</p>";
} else {
    echo "<p style='color:red;'>Erro ao criar tabela push_subscriptions.</p>";
}

$vapidPath = __DIR__ . '/classes/vapid.json';
if (!file_exists($vapidPath)) {
    echo "<p>Gerando chaves VAPID (Isso garante que os navegadores considerem o servidor seguro para enviar pushes)...</p>";
    $keys = VAPID::createVapidKeys();
    file_put_contents($vapidPath, json_encode($keys));
    echo "<p style='color:green;'>2. Chaves criadas e salvas em: <b>" . htmlspecialchars($vapidPath) . "</b></p>";
} else {
    echo "<p style='color:orange;'>2. Chaves VAPID já existiam em: <b>" . htmlspecialchars($vapidPath) . "</b>. (O processo pulou a sobrescrita).</p>";
}

echo "<h3>Migrate para Push Notifications concluído com sucesso!</h3>";
?>