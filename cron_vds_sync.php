<?php
/**
 * Script de Sincronização Automática & Auto-Refresh de Token para Crontab
 * 
 * Exemplo de configuração no crontab do servidor (executar a cada 15 minutos):
 * */15 * * * * php /var/www/html/cron_vds_sync.php >> /var/www/html/storage/cron_vds.log 2>&1
 */

if (php_sapi_name() !== 'cli' && !isset($_GET['secret_cron_key'])) {
    // Proteger acesso via web simples
    header('HTTP/1.1 403 Forbidden');
    die('Acesso negado. Execução restrita ao PHP CLI/Crontab.');
}

require_once __DIR__ . "/classes/database.php";
require_once __DIR__ . "/classes/vds_auth_service.php";
require_once __DIR__ . "/classes/vds_ocorrencia_service.php";

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] Iniciando sincronização automática e verificação de token VDS...\n";

// 1. Obter e validar token (o vds_get_token faz a verificação prévia e tenta o refresh_token se estiver vencendo)
$token = vds_get_token(null);

if (!$token) {
    echo "[{$timestamp}] ALERTA/ERRO: Nenhum token ativo ou renovável disponível na tabela `vds_tokens`.\n";
    echo "[{$timestamp}] Por favor, faça login uma vez na tela de Configurações para gerar o token inicial.\n";
    exit(1);
}

// 2. Executar a sincronização global (Lida=9)
$res = vds_sync_ocorrencias(null);

if ($res['success']) {
    echo "[{$timestamp}] SUCESSO: Sincronização concluída! " . $res['count'] . " ocorrências atualizadas no banco local.\n";
    exit(0);
} else {
    echo "[{$timestamp}] ERRO ao sincronizar: " . ($res['message'] ?? 'Falha desconhecida') . "\n";
    exit(1);
}
