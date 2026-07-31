<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/classes/database.php";
require_once __DIR__ . "/classes/vds_auth_service.php";
require_once __DIR__ . "/classes/vds_ocorrencia_service.php";

$usuarioIdConselho = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 1);

// 1. Executar sincronização de fundo com a VDS
$resSync = vds_sync_ocorrencias(null);
$newItemsCount = (int)($resSync['inserted'] ?? 0);

// 2. Descarregar confirmações de leitura pendentes do conselheiro
$flushedReads = vds_flush_pending_reads($usuarioIdConselho);

// 3. Registrar a data da última sincronização e sincronizar status de leituras legadas do dados_json
$now = date('Y-m-d H:i:s');
$link = DBConnect();
vds_ensure_leitura_table_exists($link);

// Sincronizar leitura relacional para ocorrências onde o JSON da VDS indica que já foram lidas
$uIdInt = (int)$usuarioIdConselho;
@mysqli_query($link, "INSERT IGNORE INTO ocorrencia_leitura_conselheiro (conselheiro_id, ocorrencia_id, uuid_remoto, lido, sincronizado_remoto)
SELECT {$uIdInt}, id, uuid_remoto, 1, 1
FROM ocorrencias
WHERE (JSON_UNQUOTE(JSON_EXTRACT(dados_json, '$.lida')) = 'true' OR JSON_UNQUOTE(JSON_EXTRACT(dados_json, '$.isLida')) = 'true')");

$stmtUp = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto, dados_extras_json) VALUES ('controle', 'ultima_sincronizacao_ocorrencias', ?, ?) ON DUPLICATE KEY UPDATE uuid_remoto = VALUES(uuid_remoto), dados_extras_json = VALUES(dados_extras_json)");
if ($stmtUp) {
    $jsonInfo = json_encode(['last_sync' => $now, 'by_user' => $usuarioIdConselho], JSON_UNESCAPED_UNICODE);
    mysqli_stmt_bind_param($stmtUp, "ss", $now, $jsonInfo);
    mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);
}
DBClose($link);

// 4. Obter a lista atualizada de chamados não lidos para o conselheiro localmente
$resPratico = vds_get_ocorrencias_pratico($usuarioIdConselho, 50);
$items = $resPratico['items'] ?? [];

$message = null;
if ($newItemsCount > 0) {
    $message = "{$newItemsCount} novo(s) chamado(s) sincronizado(s) da VDS!";
} elseif ($flushedReads > 0) {
    $message = "{$flushedReads} confirmação(ões) de leitura enviada(s) à VDS.";
}

echo json_encode([
    'success' => $resSync['success'] ?? true,
    'count' => count($items),
    'newCount' => $newItemsCount,
    'lastSync' => $now,
    'flushedReads' => $flushedReads,
    'message' => $message,
    'items' => $items
], JSON_UNESCAPED_UNICODE);
