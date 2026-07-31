<?php
// Script de Sincronização Automática & Auto-Refresh de Token para Crontab
// 
// Exemplo via cURL (Recomendado se o PHP CLI do servidor não possuir a extensão mysqli):
// */15 * * * * curl -s "https://mini.davinunes.eti.br/cron_vds_sync.php?secret_key=vds_cron_sync_sec_2026" >> /var/www/html/storage/cron_vds.log 2>&1
//
// Exemplo via PHP CLI (se a extensão mysqli_connect estiver instalada no php-cli):
// */15 * * * * php /var/www/html/cron_vds_sync.php >> /var/www/html/storage/cron_vds.log 2>&1

define('CRON_SECRET_KEY', 'vds_cron_sync_sec_2026');

$isCli = (php_sapi_name() === 'cli');
$providedKey = $_GET['secret_key'] ?? ($_GET['secret_cron_key'] ?? '');

if (!$isCli && $providedKey !== CRON_SECRET_KEY) {
    header('HTTP/1.1 403 Forbidden');
    die('Acesso negado. Chave de execução do cron inválida ou ausente.');
}

require_once __DIR__ . "/classes/database.php";
require_once __DIR__ . "/classes/vds_auth_service.php";
require_once __DIR__ . "/classes/vds_ocorrencia_service.php";

$timestamp = date('Y-m-d H:i:s');
$lineBreak = $isCli ? "\n" : "<br>\n";

echo "[{$timestamp}] Iniciando sincronização automática e verificação proativa de tokens VDS...{$lineBreak}";

// 1. Obter e renovar proativamente todos os tokens registrados na vds_tokens (Condomínio + Conselheiros)
$link = DBConnect();
$resTokens = mysqli_query($link, "SELECT id, tipo, usuario_id_conselho, vds_username, status, expires_at, LENGTH(refresh_token) as has_refresh FROM vds_tokens ORDER BY id ASC");
$tokensList = [];
if ($resTokens) {
    while ($r = mysqli_fetch_assoc($resTokens)) {
        $tokensList[] = $r;
    }
}
DBClose($link);

if (empty($tokensList)) {
    echo "[{$timestamp}] AVISO: Nenhum token encontrado na tabela vds_tokens. Faça login na tela de Configurações para conectar.{$lineBreak}";
    exit(1);
}

echo "[{$timestamp}] Encontrados " . count($tokensList) . " registro(s) de token na tabela `vds_tokens`. Processando auto-refresh...{$lineBreak}";

$tokenCondominioAtivo = null;

foreach ($tokensList as $tRow) {
    $tId = (int)$tRow['id'];
    $tipo = $tRow['tipo'];
    $uId = $tRow['usuario_id_conselho'] ? (int)$tRow['usuario_id_conselho'] : null;
    $username = $tRow['vds_username'] ?? 'desconhecido';
    $statusOld = $tRow['status'] ?? 'desconhecido';

    // vds_get_token renova proativamente se expires_at estiver dentro de 10min ou status='expirado'
    if ($tipo === 'conselheiro' && $uId) {
        $tVal = vds_get_token($uId, false);
        $desc = "Conselheiro ID {$uId} ({$username})";
    } else {
        $tVal = vds_get_token(null, false);
        if ($tVal) {
            $tokenCondominioAtivo = $tVal;
        }
        $desc = "Condomínio Geral ({$username})";
    }

    $stMsg = $tVal ? "ATIVO / RENOVADO" : "EXPIRADO / FALHA";
    echo "[{$timestamp}] Token #{$tId} [{$desc}]: {$stMsg} (status anterior: {$statusOld}){$lineBreak}";
}

// Para a sincronização automática global das ocorrências (cron), utilizar o token de condomínio
// Caso o condomínio não tenha token ativo, tenta utilizar o token de qualquer conselheiro com fallback permitido
if (!$tokenCondominioAtivo) {
    $tokenCondominioAtivo = vds_get_token(null, true);
}

if (!$tokenCondominioAtivo) {
    echo "[{$timestamp}] ALERTA/ERRO: Nenhum token válido/renovável disponível para a sincronização global de ocorrências.{$lineBreak}";
    exit(1);
}

echo "[{$timestamp}] Token para sincronização global pronto. Executando vds_sync_ocorrencias...{$lineBreak}";

// 2. Executar a sincronização global (Lida=9)
$res = vds_sync_ocorrencias(null);

// 3. Descarregar confirmações de leitura pendentes no VDS
$flushedCount = vds_flush_pending_reads(null);
if ($flushedCount > 0) {
    echo "[{$timestamp}] Leitura VDS: {$flushedCount} confirmação(ões) de leitura reenviada(s) com sucesso aos conselheiros.{$lineBreak}";
}

if ($res['success']) {
    // Gravar timestamp da última sincronização bem-sucedida
    $link = DBConnect();
    $stmtUp = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto, dados_extras_json) VALUES ('controle', 'ultima_sincronizacao_ocorrencias', ?, ?) ON DUPLICATE KEY UPDATE uuid_remoto = VALUES(uuid_remoto), dados_extras_json = VALUES(dados_extras_json)");
    if ($stmtUp) {
        $jsonInfo = json_encode(['last_sync' => $timestamp, 'by' => 'cron'], JSON_UNESCAPED_UNICODE);
        mysqli_stmt_bind_param($stmtUp, "ss", $timestamp, $jsonInfo);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
    }
    DBClose($link);

    echo "[{$timestamp}] SUCESSO: Sincronização concluída! " . $res['count'] . " ocorrências atualizadas no banco local.{$lineBreak}";
    exit(0);
} else {
    echo "[{$timestamp}] ERRO ao sincronizar: " . ($res['message'] ?? 'Falha desconhecida') . "{$lineBreak}";
    exit(1);
}
