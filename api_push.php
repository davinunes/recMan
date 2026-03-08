<?php
session_start();
require_once "classes/repositorio.php";

$action = $_GET['action'] ?? '';

// Retorna a Chave Pública pro Navegador do Conselheiro solicitar permissão
if ($action == 'get_vapid') {
    $vapidPath = __DIR__ . '/classes/vapid.json';
    if (file_exists($vapidPath)) {
        $vapid = json_decode(file_get_contents($vapidPath), true);
        echo json_encode(['publicKey' => $vapid['publicKey']]);
    } else {
        echo json_encode(['error' => 'Chaves VAPID não configuradas']);
    }
    exit;
}

// Salva a permissão criptografada (Endpoint do Chrome/Safari/Firebase) no Banco de Dados atrelado ao Conselheiro Logado
if ($action == 'subscribe') {
    header('Content-Type: application/json');

    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }

    // O JS vai mandar o corpo JSON bruto contendo p256dh e Auth 
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data) || empty($data['endpoint'])) {
        echo json_encode(['success' => false, 'error' => 'Faltam dados do Endpoint']);
        exit;
    }

    $userId = (int) $_SESSION['user_id'];
    $endpoint = $data['endpoint'];
    $p256dh = $data['keys']['p256dh'] ?? '';
    $auth = $data['keys']['auth'] ?? '';

    // Evita duplicação (o navegador renova as chaves as vezes em background)
    $chk = DBExecute("SELECT id FROM push_subscriptions WHERE endpoint = '" . DBEscape($endpoint) . "'");

    if ($chk && mysqli_num_rows($chk) == 0) {
        $sql = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) 
                VALUES ($userId, '" . DBEscape($endpoint) . "', '" . DBEscape($p256dh) . "', '" . DBEscape($auth) . "')";
        DBExecute($sql);
    } else {
        $sql = "UPDATE push_subscriptions 
                SET user_id = $userId, p256dh = '" . DBEscape($p256dh) . "', auth = '" . DBEscape($auth) . "' 
                WHERE endpoint = '" . DBEscape($endpoint) . "'";
        DBExecute($sql);
    }

    echo json_encode(['success' => true]);
    exit;
}
