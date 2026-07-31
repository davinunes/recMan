<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/classes/database.php";
require_once __DIR__ . "/classes/vds_auth_service.php";
require_once __DIR__ . "/classes/vds_ocorrencia_service.php";

$usuarioIdConselho = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 1);
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 10);

// 1. Descarregar confirmações de leitura pendentes do conselheiro em segundo plano
$flushedReads = vds_flush_pending_reads($usuarioIdConselho);

// 2. Consultar a página especificada de não lidos (Lida=0) diretamente na VDS API
$resPratico = vds_get_ocorrencias_pratico($usuarioIdConselho, $limit, $page);
$items = $resPratico['items'] ?? [];
$totalRegs = (int)($resPratico['totalRegs'] ?? 0);
$hasMore = !empty($resPratico['hasMore']);

// 3. Registrar a data da última sincronização bem-sucedida
$now = date('Y-m-d H:i:s');
$link = DBConnect();
$stmtUp = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto, dados_extras_json) VALUES ('controle', 'ultima_sincronizacao_ocorrencias', ?, ?) ON DUPLICATE KEY UPDATE uuid_remoto = VALUES(uuid_remoto), dados_extras_json = VALUES(dados_extras_json)");
if ($stmtUp) {
    $jsonInfo = json_encode(['last_sync' => $now, 'by_user' => $usuarioIdConselho, 'total_regs' => $totalRegs, 'page' => $page], JSON_UNESCAPED_UNICODE);
    mysqli_stmt_bind_param($stmtUp, "ss", $now, $jsonInfo);
    mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);
}
DBClose($link);

echo json_encode([
    'success' => $resPratico['success'] ?? true,
    'count' => count($items),
    'page' => $page,
    'limit' => $limit,
    'totalRegs' => $totalRegs,
    'hasMore' => $hasMore,
    'lastSync' => $now,
    'flushedReads' => $flushedReads,
    'items' => $items,
    'debug' => $resPratico['debug'] ?? []
], JSON_UNESCAPED_UNICODE);
