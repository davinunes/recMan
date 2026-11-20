<?php
// Script de Enriquecimento e Atualização em Lote de Chamados Legados (VDS API v8)
// Atualiza uuid_remoto, oco_tipo, bloco/unidade, status e dados_json consultando por protocolo na VDS.
//
// Execução via CLI:
// php cron_vds_enrich_legadas.php --limit=100 --filtro=outros --delay=150
//
// Execução via Navegador/Web:
// https://mini.davinunes.eti.br/cron_vds_enrich_legadas.php?limit=100&filtro=outros
// (Requer estar logado como conselheiro ou passar &secret_key=vds_cron_sync_sec_2026)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('CRON_SECRET_KEY', 'vds_cron_sync_sec_2026');

$isCli = (php_sapi_name() === 'cli');
$providedKey = $_GET['secret_key'] ?? ($_GET['secret_cron_key'] ?? '');
$isLoggedUser = !empty($_SESSION['user_id']) || !empty($_SESSION['usuario_id']);

if (!$isCli && !$isLoggedUser && $providedKey !== CRON_SECRET_KEY) {
    header('HTTP/1.1 403 Forbidden');
    die('Acesso negado. Faça login no sistema ou informe secret_key válida.');
}

require_once __DIR__ . "/classes/database.php";
require_once __DIR__ . "/classes/vds_auth_service.php";
require_once __DIR__ . "/classes/vds_ocorrencia_service.php";

// Parâmetros de execução
$filtro = 'outros';
$limit = 100;
$delayMs = 150;

if ($isCli) {
    $options = getopt("", ["filtro::", "limit::", "delay::"]);
    if (isset($options['filtro'])) $filtro = trim($options['filtro']);
    if (isset($options['limit'])) $limit = (int)$options['limit'];
    if (isset($options['delay'])) $delayMs = (int)$options['delay'];
} else {
    if (isset($_GET['filtro'])) $filtro = trim($_GET['filtro']);
    if (isset($_GET['limit'])) $limit = (int)$_GET['limit'];
    if (isset($_GET['delay'])) $delayMs = (int)$_GET['delay'];
}

$usuarioIdConselho = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? null);
$token = vds_get_token($usuarioIdConselho);
if (!$token) {
    $token = vds_get_token(null, true);
}

// Cabeçalho Web com Streaming
if (!$isCli) {
    // Desabilitar buffers para permitir streaming linha a linha
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', 1);
    }
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    for ($i = 0; $i < ob_get_level(); $i++) {
        ob_end_flush();
    }
    ob_implicit_flush(1);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Roleta de Atualização de Chamados Legados - VDS</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <style>
            body { background: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .console-card { background: #18191c; border-radius: 8px; color: #a6e22e; font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace; font-size: 0.85rem; padding: 15px; height: 480px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); line-height: 1.5; }
            .console-card .log-ok { color: #5af78e; }
            .console-card .log-warn { color: #f3f99d; }
            .console-card .log-err { color: #ff5c57; }
            .console-card .log-info { color: #57c7ff; }
            .stat-box { background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #e0e0e0; text-align: center; }
            .stat-val { font-size: 1.6rem; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container" style="margin-top: 25px; margin-bottom: 40px; max-width: 960px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <div>
                    <h5 style="margin:0; font-weight:600; color:#1a237e; display:flex; align-items:center; gap:8px;">
                        <i class="material-icons" style="font-size: 1.8rem; color:#3949ab;">autorenew</i>
                        <span>Roleta de Atualização de Chamados Legados</span>
                    </h5>
                    <span style="font-size:0.85rem; color:#666;">
                        Enriquecimento de UUID remoto e classificação VDS por protocolo | Filtro: <strong><?= htmlspecialchars($filtro) ?></strong> | Limite: <strong><?= $limit > 0 ? $limit : 'Sem limite' ?></strong>
                    </span>
                </div>
                <a href="index.php?pag=livroDeOcorrencias" class="btn-small waves-effect waves-light grey darken-2" style="border-radius:4px; text-transform:none;">
                    <i class="material-icons left tiny">arrow_back</i> Voltar ao Livro
                </a>
            </div>

            <!-- Progresso Geral -->
            <div id="progress-container" style="background:#fff; padding:15px; border-radius:8px; border:1px solid #e0e0e0; margin-bottom:15px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <span id="progress-text" style="font-weight:600; font-size:0.9rem; color:#333;">Iniciando varredura...</span>
                    <span id="progress-percent" style="font-size:0.85rem; color:#666;">0%</span>
                </div>
                <div class="progress" style="margin:0; height:8px; border-radius:4px; background:#e0e0e0;">
                    <div id="progress-bar" class="determinate indigo darken-1" style="width: 0%;"></div>
                </div>
            </div>

            <!-- Console de Execução Streaming -->
            <div class="console-card" id="console-output">
    <?php
    flush();
}

$startTs = microtime(true);
$timestamp = date('Y-m-d H:i:s');

if ($isCli) {
    echo "[{$timestamp}] Iniciando roleta de atualização de chamados legados (Filtro: {$filtro}, Limite: {$limit})...\n";
} else {
    echo "<span class='log-info'>[{$timestamp}] Conectando à VDS API v8... Verificando tokens...</span><br>\n";
    flush();
}

if (!$token) {
    $errToken = "ERRO: Nenhum token VDS ativo encontrado. Faça login em Configurações.";
    if ($isCli) {
        echo "[{$timestamp}] {$errToken}\n";
        exit(1);
    } else {
        echo "<span class='log-err'>[{$timestamp}] {$errToken}</span><br></div></div></body></html>\n";
        exit;
    }
}

// Callback para streaming ao vivo
$callbackProgresso = function($indice, $total, $row, $resultado) use ($isCli) {
    $percent = $total > 0 ? round(($indice / $total) * 100) : 100;
    $proto = $row['protocolo_vds'] ?: $row['id'];
    $dataHora = date('H:i:s');

    if ($resultado['success']) {
        $tipoNome = $resultado['tipo_nome'] ?? 'Ocorrência';
        $bloco = $resultado['bloco'] ? "Bl {$resultado['bloco']}" : "";
        $unid = $resultado['unidade'] ? "Apt {$resultado['unidade']}" : "";
        $loc = trim("{$bloco} {$unid}");
        $uuidShort = substr($resultado['uuid'] ?? '', 0, 10) . '...';
        
        $protoRealInfo = "";
        if (!empty($resultado['protocolo']) && (string)$resultado['protocolo'] !== (string)$proto) {
            $protoRealInfo = " <span style='color:#e1bee7;'>(Prot Real: #" . htmlspecialchars($resultado['protocolo']) . ")</span>";
        }
        $protoRealCli = (!empty($resultado['protocolo']) && (string)$resultado['protocolo'] !== (string)$proto) ? " (Prot Real: #{$resultado['protocolo']})" : "";

        $msgCli = "[{$dataHora}] [{$indice}/{$total}] ({$percent}%) Chamado #{$proto}{$protoRealCli} -> OK! Tipo: [{$tipoNome}] {$loc} (UUID: {$uuidShort})";
        $msgWeb = "<span class='log-ok'>[{$dataHora}] [{$indice}/{$total}] Chamado #{$proto}{$protoRealInfo} &rarr; OK! Tipo: [{$tipoNome}] {$loc} (UUID: {$uuidShort})</span><br>\n";
    } else {
        $motivo = $resultado['message'] ?? 'Falha';
        $msgCli = "[{$dataHora}] [{$indice}/{$total}] ({$percent}%) Chamado #{$proto} -> AVISO: {$motivo}";
        $msgWeb = "<span class='log-warn'>[{$dataHora}] [{$indice}/{$total}] Chamado #{$proto} &rarr; {$motivo}</span><br>\n";
    }

    if ($isCli) {
        echo "{$msgCli}\n";
    } else {
        echo $msgWeb;
        echo "<script>
            document.getElementById('progress-bar').style.width = '{$percent}%';
            document.getElementById('progress-percent').innerText = '{$percent}%';
            document.getElementById('progress-text').innerText = 'Processando registro {$indice} de {$total}...';
            var c = document.getElementById('console-output');
            c.scrollTop = c.scrollHeight;
        </script>\n";
        flush();
    }
};

// Executar varredura
$resultadoGeral = vds_enrich_legacy_ocorrencias($filtro, $limit, $delayMs, $usuarioIdConselho, $callbackProgresso);

$elapsed = round(microtime(true) - $startTs, 2);
$endTs = date('Y-m-d H:i:s');

if ($isCli) {
    echo "\n------------------------------------------------------------\n";
    echo "[{$endTs}] Concluído em {$elapsed}s!\n";
    echo "Total Analisado: {$resultadoGeral['total']}\n";
    echo "Atualizados com Sucesso: {$resultadoGeral['updated']}\n";
    echo "Não Encontrados na VDS: {$resultadoGeral['nao_encontrados']}\n";
    echo "Erros/Falhas: {$resultadoGeral['erros']}\n";
    echo "------------------------------------------------------------\n";
} else {
    echo "<span class='log-info'>------------------------------------------------------------</span><br>\n";
    echo "<span class='log-ok'>[{$endTs}] Processo concluído com sucesso em {$elapsed} segundos!</span><br>\n";
    echo "</div>\n"; // fecha console-card
    ?>
        <!-- Painel de Estatísticas Finais -->
        <div class="row" style="margin-top: 20px;">
            <div class="col s12 m3">
                <div class="stat-box">
                    <div style="font-size:0.8rem; color:#666; font-weight:600; text-transform:uppercase;">Total Analisado</div>
                    <div class="stat-val indigo-text text-darken-3"><?= (int)$resultadoGeral['total'] ?></div>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="stat-box">
                    <div style="font-size:0.8rem; color:#666; font-weight:600; text-transform:uppercase;">Atualizados (OK)</div>
                    <div class="stat-val green-text text-darken-2"><?= (int)$resultadoGeral['updated'] ?></div>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="stat-box">
                    <div style="font-size:0.8rem; color:#666; font-weight:600; text-transform:uppercase;">Não Encontrados</div>
                    <div class="stat-val orange-text text-darken-3"><?= (int)$resultadoGeral['nao_encontrados'] ?></div>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="stat-box">
                    <div style="font-size:0.8rem; color:#666; font-weight:600; text-transform:uppercase;">Tempo Decorrido</div>
                    <div class="stat-val purple-text text-darken-2"><?= $elapsed ?>s</div>
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; flex-wrap:wrap; gap:10px;">
            <div style="font-size:0.85rem; color:#555;">
                <?php if ($resultadoGeral['total'] >= $limit && $limit > 0): ?>
                    <em>Ainda podem haver outros registros pendentes (limite de lote atingido).</em>
                <?php else: ?>
                    <strong>Todos os chamados no critério foram processados!</strong>
                <?php endif; ?>
            </div>
            <div style="display:flex; gap:10px;">
                <?php if ($resultadoGeral['total'] >= $limit && $limit > 0): ?>
                    <a href="cron_vds_enrich_legadas.php?filtro=<?= urlencode($filtro) ?>&limit=<?= (int)$limit ?>&delay=<?= (int)$delayMs ?>" class="btn waves-effect waves-light blue darken-2" style="border-radius:4px; text-transform:none;">
                        <i class="material-icons left">replay</i> Processar Próximo Lote (<?= (int)$limit ?>)
                    </a>
                <?php endif; ?>
                <a href="index.php?pag=livroDeOcorrencias" class="btn waves-effect waves-light green darken-2" style="border-radius:4px; text-transform:none;">
                    <i class="material-icons left">check</i> Concluído! Voltar ao Livro
                </a>
            </div>
        </div>

        <script>
            document.getElementById('progress-bar').style.width = '100%';
            document.getElementById('progress-percent').innerText = '100%';
            document.getElementById('progress-text').innerText = 'Processamento concluído!';
        </script>
    </div>
</body>
</html>
    <?php
}
