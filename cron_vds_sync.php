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

echo "[{$timestamp}] Iniciando sincronização automática e verificação de token VDS...{$lineBreak}";

// 1. Obter e validar token (o vds_get_token faz a verificação prévia e tenta o refresh_token se estiver vencendo)
$token = vds_get_token(null);

if (!$token) {
    echo "[{$timestamp}] ALERTA/ERRO: Nenhum token ativo ou renovável disponível na tabela `vds_tokens`.{$lineBreak}";
    echo "[{$timestamp}] Por favor, faça login uma vez na tela de Configurações para gerar o token inicial.{$lineBreak}";
    exit(1);
}

// 2. Executar a sincronização global (Lida=9)
$res = vds_sync_ocorrencias(null);

if ($res['success']) {
    echo "[{$timestamp}] SUCESSO: Sincronização concluída! " . $res['count'] . " ocorrências atualizadas no banco local.{$lineBreak}";
    exit(0);
} else {
    echo "[{$timestamp}] ERRO ao sincronizar: " . ($res['message'] ?? 'Falha desconhecida') . "{$lineBreak}";
    exit(1);
}
