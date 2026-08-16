<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está autenticado no sistema
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Sessão expirada ou não autenticada.']);
    exit;
}

// Iniciar buffer para evitar saídas acidentais de warnings/notices
ob_start();

require_once __DIR__ . "/../classes/vds_acesso_service.php";

// Limpar buffer antes da saída JSON
ob_clean();

header('Content-Type: application/json; charset=utf-8');

$busca = $_GET['busca'] ?? $_POST['busca'] ?? '';
$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? 'ALL';
$usuarioId = (int)$_SESSION['user_id'];

if (empty(trim($busca))) {
    echo json_encode([
        'success' => true,
        'data' => [],
        'count' => 0,
        'tipo' => strtoupper($tipo),
        'message' => 'Termo de busca não fornecido.'
    ]);
    exit;
}

try {
    $res = vds_busca_generica($busca, $tipo, $usuarioId);
    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno ao realizar a busca: ' . $e->getMessage()
    ]);
}
