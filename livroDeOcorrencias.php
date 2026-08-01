<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/classes/database.php";
require_once __DIR__ . "/classes/vds_auth_service.php";
require_once __DIR__ . "/classes/vds_ocorrencia_service.php";

$usuarioIdConselho = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 1);
$toastAlert = vds_get_toast_alerts($usuarioIdConselho);

// Verificar se o conselheiro logado possui Ultra-Login ativo (sem fallback de condomínio)
$tokenConselheiroAtual = vds_get_token($usuarioIdConselho, false);
$hasUltraLogin = !empty($tokenConselheiroAtual);

// Visão atual (Prático = padrão; Analítico = baseado no banco local)
$visao = $_GET['visao'] ?? 'pratico';

// Processar Ações POST
$msg = null;
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'sync_agora') {
        $resSync = vds_sync_ocorrencias(null, $usuarioIdConselho);
        if ($resSync['success']) {
            $msg = "Sincronização concluída com sucesso! " . $resSync['count'] . " ocorrências atualizadas.";
            $msgType = "success";
        } else {
            $msg = "Erro ao sincronizar: " . ($resSync['message'] ?? 'Falha desconhecida');
            $msgType = "danger";
        }
    } elseif ($action === 'adicionar_nota_interna') {
        $ocorrenciaId = (int)$_POST['ocorrencia_id'];
        $texto = trim($_POST['texto'] ?? '');
        $conselheiroNome = $_SESSION['user_nome'] ?? ($_SESSION['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Conselheiro'));
        
        if (!empty($texto)) {
            $resNota = vds_adicionar_nota_interna($ocorrenciaId, $usuarioIdConselho, $conselheiroNome, $texto);
            if ($resNota['success']) {
                $msg = "Nota interna salva no Conselho com sucesso.";
                $msgType = "success";
            }
        }
    } elseif ($action === 'publicar_remoto') {
        $notaId = (int)$_POST['nota_id'];
        $resPub = vds_publicar_nota_remoto($notaId, $usuarioIdConselho);
        if ($resPub['success']) {
            $msg = $resPub['message'];
            $msgType = "success";
        } else {
            $msg = $resPub['message'];
            $msgType = $resPub['blockedByLock'] ? "warning" : "danger";
        }
    } elseif ($action === 'atualizar_responsabilidade') {
        $ocorrenciaId = (int)$_POST['ocorrencia_id'];
        $resp = $_POST['responsabilidade'] ?? null;
        if (empty($resp)) $resp = null;
        
        $link = DBConnect();
        $stmt = mysqli_prepare($link, "UPDATE ocorrencias SET responsabilidade = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $resp, $ocorrenciaId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        DBClose($link);

        if (!empty($_REQUEST['is_ajax'])) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'action' => 'atualizar_responsabilidade',
                'ocorrencia_id' => $ocorrenciaId,
                'responsabilidade' => $resp,
                'message' => "Responsabilidade atualizada com sucesso!"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $msg = "Responsabilidade atualizada com sucesso!";
        $msgType = "success";
    } elseif ($action === 'marcar_resolvido') {
        $ocorrenciaId = (int)$_POST['ocorrencia_id'];
        $resolvidoVal = (int)($_POST['resolvido_val'] ?? 1);
        
        $link = DBConnect();
        $stmt = mysqli_prepare($link, "UPDATE ocorrencias SET resolvido = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $resolvidoVal, $ocorrenciaId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        DBClose($link);

        if (!empty($_REQUEST['is_ajax'])) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'action' => 'marcar_resolvido',
                'ocorrencia_id' => $ocorrenciaId,
                'resolvido' => $resolvidoVal,
                'message' => $resolvidoVal ? "Chamado marcado como RESOLVIDO no Conselho!" : "Chamado reaberto no Conselho!"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $msg = $resolvidoVal ? "Chamado marcado como RESOLVIDO no Conselho!" : "Chamado reaberto no Conselho!";
        $msgType = "success";
    } elseif ($action === 'marcar_como_lido') {
        $ocorrenciaId = (int)($_POST['ocorrencia_id'] ?? 0);
        $uuidRemoto = $_POST['uuid_remoto'] ?? null;
        $novoStatusLido = isset($_POST['novo_status_lido']) ? (bool)(int)$_POST['novo_status_lido'] : true;
        
        $resLido = vds_marcar_como_lido($uuidRemoto, $usuarioIdConselho, $ocorrenciaId, $novoStatusLido);

        if (!empty($_REQUEST['is_ajax'])) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $resLido['success'] ?? true,
                'action' => 'marcar_como_lido',
                'ocorrencia_id' => $ocorrenciaId,
                'uuid_remoto' => $uuidRemoto,
                'isLidaVds' => $novoStatusLido,
                'message' => $resLido['message'] ?? 'Status de leitura atualizado na VDS.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $msg = $resLido['message'] ?? 'Status de leitura atualizado na VDS.';
        $msgType = "success";

        // No modo Prático, ao marcar como lido, redireciona para o feed unread atualizado
        if ($visao === 'pratico' && $novoStatusLido) {
            header("Location: index.php?pag=livroDeOcorrencias&visao=pratico");
            exit;
        }
    } elseif ($action === 'adicionar_tag_livre') {
        $ocorrenciaId = (int)$_POST['ocorrencia_id'];
        $tagInput = trim($_POST['tag_input'] ?? '');
        $resTag = ['success' => false, 'message' => 'Tag vazia.'];
        if (!empty($tagInput)) {
            $resTag = vds_adicionar_tag_livre($ocorrenciaId, $tagInput);
        }

        if (!empty($_REQUEST['is_ajax'])) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array_merge([
                'action' => 'adicionar_tag_livre',
                'ocorrencia_id' => $ocorrenciaId,
                'tag_input' => $tagInput
            ], $resTag), JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($resTag['success']) {
            $msg = $resTag['message'] ?? ($resTag['already_exists'] ? "Tag já vinculada a esta ocorrência." : "Tag adicionada com sucesso!");
            $msgType = !empty($resTag['already_exists']) ? "warning" : "success";
        } else {
            $msg = $resTag['message'] ?? "Falha ao adicionar a tag.";
            $msgType = "danger";
        }
    } elseif ($action === 'remover_tag') {
        $tagId = (int)$_POST['tag_id'];
        $ocorrenciaId = (int)$_POST['ocorrencia_id'];
        $resRem = vds_remover_tag($tagId, $ocorrenciaId);

        if (!empty($_REQUEST['is_ajax'])) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array_merge([
                'action' => 'remover_tag',
                'ocorrencia_id' => $ocorrenciaId,
                'tag_id' => $tagId
            ], $resRem), JSON_UNESCAPED_UNICODE);
            exit;
        }

        $msg = $resRem['success'] ? $resRem['message'] : ($resRem['message'] ?? 'Falha ao remover a tag.');
        $msgType = $resRem['success'] ? "success" : "danger";
    }
}

// Filtros de busca
$blocoFiltro = $_GET['bloco'] ?? '';
$unidadeFiltro = $_GET['unidade'] ?? '';
$protoFiltro = $_GET['protocolo'] ?? '';
$respFiltro = $_GET['responsabilidade'] ?? '';
$statusFiltro = $_GET['status'] ?? 'abertas';
$tipoFiltro = $_GET['oco_tipo'] ?? '';

$listaBlocos = [];
$ocorrenciasAgrupadas = [];

// Obter Lista de Ocorrências por Visão
$ocorrencias = [];

if ($visao === 'pratico') {
    // Na Visão Prática, se o conselheiro não tiver Ultra-Login pessoal, bloqueia e solicita ativação
    if (!$hasUltraLogin) {
        $msg = "Ultra-Login necessário: Para acessar a Visão Prática (chamados não lidos na VDS), você precisa conectar seu usuário pessoal nas Configurações VDS.";
        $msgType = "warning";
        $ocorrencias = [];
    } else {
        $resPratico = vds_get_ocorrencias_pratico($usuarioIdConselho, 10, 1);
        $debugPratico = $resPratico['debug'] ?? [];
        $totalRegsPratico = (int)($resPratico['totalRegs'] ?? 0);
        $hasMorePratico = !empty($resPratico['hasMore']);

        if ($resPratico['success']) {
            $ocorrencias = $resPratico['items'];
            foreach ($ocorrencias as $row) {
                $tKey = (int)($row['oco_tipo'] ?? 0);
                $ocorrenciasAgrupadas[$tKey][] = $row;
            }
        } else {
            $msg = $resPratico['message'] ?? "Falha ao consultar não lidos da VDS.";
            $msgType = "warning";
        }
    }
} else {
    // Visão Analítica: Ocorrências do banco local
    $link = DBConnect();

    // Buscar lista distinta de blocos para o filtro
    $resBlocos = mysqli_query($link, "SELECT DISTINCT bloco FROM ocorrencias WHERE bloco IS NOT NULL AND bloco != '' ORDER BY bloco ASC");
    if ($resBlocos) {
        while ($rB = mysqli_fetch_assoc($resBlocos)) {
            $listaBlocos[] = $rB['bloco'];
        }
    }

    $sqlWhere = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if ($statusFiltro === 'abertas') {
        $sqlWhere .= " AND (resolvido IS NULL OR resolvido = 0)";
    } elseif ($statusFiltro === 'resolvidas') {
        $sqlWhere .= " AND resolvido = 1";
    }

    if ($blocoFiltro !== '') { $sqlWhere .= " AND bloco = ?"; $params[] = $blocoFiltro; $types .= "s"; }
    if ($unidadeFiltro !== '') { $sqlWhere .= " AND unidade = ?"; $params[] = $unidadeFiltro; $types .= "s"; }
    if ($protoFiltro !== '') { $sqlWhere .= " AND (protocolo_vds = ? OR id = ?)"; $params[] = $protoFiltro; $params[] = (int)$protoFiltro; $types .= "si"; }
    if ($respFiltro !== '') { $sqlWhere .= " AND responsabilidade = ?"; $params[] = $respFiltro; $types .= "s"; }
    if ($tipoFiltro !== '') { $sqlWhere .= " AND oco_tipo = ?"; $params[] = (int)$tipoFiltro; $types .= "i"; }

    $sqlList = "SELECT * FROM ocorrencias {$sqlWhere} ORDER BY abertura DESC LIMIT 1000";
    $stmtList = mysqli_prepare($link, $sqlList);
    if ($types && !empty($params)) {
        mysqli_stmt_bind_param($stmtList, $types, ...$params);
    }
    mysqli_stmt_execute($stmtList);
    $resList = mysqli_stmt_get_result($stmtList);

    while ($row = mysqli_fetch_assoc($resList)) {
        $ocorrencias[] = $row;
        $tKey = (int)($row['oco_tipo'] ?? 0);
        $ocorrenciasAgrupadas[$tKey][] = $row;
    }
    mysqli_stmt_close($stmtList);
    DBClose($link);
}

// Ocorrência selecionada para visualização
$selId = $_GET['id'] ?? null;

// Se um protocolo foi digitado no filtro ou passado via URL (?protocolo=259564), resolve e seleciona diretamente
$detalheSel = null;
if (!empty($protoFiltro)) {
    $detalhePorProto = vds_get_ocorrencia_detalhe($protoFiltro, $usuarioIdConselho);
    $detalheSel = $detalhePorProto;
    if ($detalhePorProto && isset($detalhePorProto['local']['id'])) {
        $selId = $detalhePorProto['local']['id'];

        $jaNaLista = false;
        foreach ($ocorrencias as $ocoItem) {
            if ($ocoItem['id'] == $selId) { $jaNaLista = true; break; }
        }
        if (!$jaNaLista) {
            array_unshift($ocorrencias, $detalhePorProto['local']);
        }
    }
}

// Em telas grandes (desktop), se nenhuma foi especificada na URL, seleciona a primeira por padrão
$isMobileView = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']);
if (!$selId && !$isMobileView && !empty($ocorrencias)) {
    $selId = $ocorrencias[0]['id'];
}

if (!$detalheSel && $selId) {
    $detalheSel = vds_get_ocorrencia_detalhe($selId, $usuarioIdConselho);
}



// Mapa de cores para ocoTipo
$mapaCoresTipo = [
    115 => ['nome' => 'Fale com o Conselho', 'bg' => '#6f42c1', 'color' => '#ffffff'],
    247 => ['nome' => 'Monitoramento', 'bg' => '#fd7e14', 'color' => '#ffffff'],
    114 => ['nome' => 'Livro de ocorrência', 'bg' => '#0d6efd', 'color' => '#ffffff'],
    86  => ['nome' => 'Fale com o Síndico', 'bg' => '#dc3545', 'color' => '#ffffff'],
    109 => ['nome' => 'Fale com o Síndico de Bloco', 'bg' => '#b02a37', 'color' => '#ffffff'],
    102 => ['nome' => 'Fale com a Administração', 'bg' => '#20c997', 'color' => '#ffffff'],
    145 => ['nome' => 'Fale com a Mensageria', 'bg' => '#ffc107', 'color' => '#000000'],
    87  => ['nome' => 'Fale com a portaria', 'bg' => '#795548', 'color' => '#ffffff'],
    126 => ['nome' => 'Fale com a Supervisão', 'bg' => '#0dcaf0', 'color' => '#000000'],
    172 => ['nome' => 'Suporte ao Controle de Acesso', 'bg' => '#495057', 'color' => '#ffffff']
];
?>

<style>
    .sidebar-feed { background: #ffffff; border-right: 1px solid #e0e0e0; height: calc(100vh - 120px); overflow-y: auto; }
    .chat-container { background: #efeae2; height: calc(100vh - 120px); display: flex; flex-direction: column; }
    .chat-header { background: #ffffff; padding: 12px 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    .chat-body { flex: 1; overflow-y: auto; padding: 20px; }
    .chat-footer { background: #ffffff; padding: 15px; border-top: 1px solid #e0e0e0; }

    /* Estilo Balões Chat WhatsApp */
    .msg-bubble { max-width: 75%; margin-bottom: 15px; padding: 12px 16px; border-radius: 12px; font-size: 0.95rem; line-height: 1.4; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .msg-left { background: #ffffff; border-top-left-radius: 2px; align-self: flex-start; margin-right: auto; }
    .msg-internal { background: #fff3cd; border: 1px solid #ffeba0; border-top-right-radius: 2px; margin-left: auto; color: #856404; }
    .msg-right { background: #dcf8c6; border-top-right-radius: 2px; margin-left: auto; color: #111; }
    .msg-author { font-weight: bold; font-size: 0.85rem; margin-bottom: 4px; display: flex; justify-content: space-between; gap: 10px; }
    .msg-time { font-size: 0.75rem; color: #888; text-align: right; margin-top: 6px; }

    .item-oco { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s; }
    .item-oco:hover, .item-oco.active { background: #e8f0fe; }
    .badge-tipo { font-size: 0.75rem; padding: 3px 8px; border-radius: 12px; font-weight: 500; display: inline-block; }

    /* Responsividade Mobile (Telas até 992px) */
    @media (max-width: 992px) {
        .sidebar-feed {
            display: <?= ($selId || $detalheSel) ? 'none' : 'block' ?> !important;
            width: 100% !important;
            height: auto !important;
        }
        .chat-container {
            display: <?= ($selId || $detalheSel) ? 'flex' : 'none' ?> !important;
            width: 100% !important;
            height: calc(100vh - 120px) !important;
        }
    }

    /* Top Progress Bar (Vercel / GitHub Premium Style) */
    #vds-top-loader {
        position: fixed;
        top: 0;
        left: 0;
        height: 4px;
        width: 0%;
        background: linear-gradient(90deg, #0d6efd, #0dcaf0, #6f42c1, #0d6efd);
        background-size: 200% 100%;
        z-index: 999999;
        box-shadow: 0 0 14px rgba(13, 110, 253, 0.9);
        transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease;
        pointer-events: none;
    }

    /* Skeleton Screen Shimmer Animation Effect (Modern UX) */
    @keyframes vds-shimmer-pulse {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    .vds-skeleton-shimmer {
        background: linear-gradient(90deg, #eef0f3 25%, #dbe0e6 37%, #eef0f3 63%) !important;
        background-size: 200% 100% !important;
        animation: vds-shimmer-pulse 1.4s ease-in-out infinite !important;
        border-color: transparent !important;
        color: transparent !important;
        user-select: none !important;
        pointer-events: none !important;
    }

    .vds-skeleton-box {
        border-radius: 6px;
        display: inline-block;
    }

    /* Container Skeleton do Chat */
    #vds-skeleton-chat-container {
        display: none;
        flex-direction: column;
        height: 100%;
        width: 100%;
        background: #efeae2;
    }

    #vds-skeleton-chat-container.active {
        display: flex !important;
    }
</style>

<!-- Top Bar: Título, Seleção de Visão (Prático vs Analítico) e Ações Globais -->
<div style="padding: 10px 20px; background: #ffffff; border-bottom: 1px solid #e0e0e0;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <!-- Seletor de Visão: Prático x Analítico -->
        <div style="display:flex; align-items:center; gap:8px; background:#f1f3f5; padding:4px; border-radius:6px; border:1px solid #dee2e6;">
            <a href="index.php?pag=livroDeOcorrencias&visao=pratico<?= !empty($protoFiltro) ? '&protocolo='.urlencode($protoFiltro) : '' ?>"
               class="btn-small waves-effect <?= $visao === 'pratico' ? 'blue darken-2 white-text font-weight-bold' : 'btn-flat grey-text text-darken-3' ?>"
               style="height:32px; line-height:32px; padding:0 14px; font-size:0.85rem; border-radius:4px; text-transform:none;">
                <i class="material-icons left tiny" style="margin-right:4px;">flash_on</i>
                Visão Prática (VDS Não Lidos)
            </a>

            <a href="index.php?pag=livroDeOcorrencias&visao=analitico<?= !empty($protoFiltro) ? '&protocolo='.urlencode($protoFiltro) : '' ?>"
               class="btn-small waves-effect <?= $visao === 'analitico' ? 'indigo darken-2 white-text font-weight-bold' : 'btn-flat grey-text text-darken-3' ?>"
               style="height:32px; line-height:32px; padding:0 14px; font-size:0.85rem; border-radius:4px; text-transform:none;">
                <i class="material-icons left tiny" style="margin-right:4px;">analytics</i>
                Visão Analítica (Banco Local)
            </a>
        </div>

        <!-- Form de Busca Direta por Protocolo -->
        <form method="GET" action="index.php" style="display:flex; gap:6px; align-items:center; margin:0;">
            <input type="hidden" name="pag" value="livroDeOcorrencias">
            <input type="hidden" name="visao" value="<?= htmlspecialchars($visao) ?>">
            <input type="text" name="protocolo" placeholder="Nº do Protocolo (ex: 259564)" value="<?= htmlspecialchars($protoFiltro) ?>" style="height:32px; font-size:0.85rem; width:200px; margin:0; padding:0 8px; border:1px solid #ccc; border-radius:4px; background:#fff;">
            <button type="submit" class="btn-small waves-effect waves-light blue darken-2" style="height:32px; line-height:32px; padding:0 10px;">
                <i class="material-icons left tiny">search</i> Ir p/ Protocolo
            </button>
        </form>

        <!-- Botão Sincronizar Agora -->
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="sync_agora">
                <button type="submit" class="btn waves-effect waves-light blue btn-small">
                    <i class="material-icons left">sync</i> Sincronizar Agora
                </button>
            </form>
        </div>
    </div>

    <!-- Painel de Filtros Avançados / Premium (Exibido na Visão Analítica) -->
    <?php if ($visao === 'analitico'): ?>
        <div class="card-panel white z-depth-1" style="margin: 12px 0 4px 0; padding: 12px 16px; border-radius: 8px; border: 1px solid #e0e0e0; background: #fafafa;">
            <form method="GET" action="index.php" id="form-filtros-analitico" style="margin:0;">
                <input type="hidden" name="pag" value="livroDeOcorrencias">
                <input type="hidden" name="visao" value="analitico">

                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                    <!-- Filtro por Bloco -->
                    <div style="flex: 1; min-width: 120px;">
                        <label style="font-weight:bold; font-size:0.75rem; color:#495057; display:block; margin-bottom:2px;">BLOCO</label>
                        <select name="bloco" class="browser-default" onchange="this.form.submit()" style="height:32px; padding:2px 8px; font-size:0.82rem; border:1px solid #ced4da; border-radius:6px; background:#fff; width:100%;">
                            <option value="">Todos os Blocos</option>
                            <?php foreach ($listaBlocos as $bVal): ?>
                                <option value="<?= htmlspecialchars($bVal) ?>" <?= $blocoFiltro === $bVal ? 'selected' : '' ?>>Bloco <?= htmlspecialchars($bVal) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro por Unidade -->
                    <div style="flex: 1; min-width: 110px;">
                        <label style="font-weight:bold; font-size:0.75rem; color:#495057; display:block; margin-bottom:2px;">UNIDADE</label>
                        <input type="text" name="unidade" placeholder="Ex: 1108" value="<?= htmlspecialchars($unidadeFiltro) ?>" onchange="this.form.submit()" style="height:32px; line-height:32px; padding:0 8px; font-size:0.82rem; border:1px solid #ced4da; border-radius:6px; background:#fff; margin:0; width:100%; box-sizing:border-box;">
                    </div>

                    <!-- Filtro por Status (Resolvido) -->
                    <div style="flex: 1; min-width: 140px;">
                        <label style="font-weight:bold; font-size:0.75rem; color:#495057; display:block; margin-bottom:2px;">STATUS</label>
                        <select name="status" class="browser-default" onchange="this.form.submit()" style="height:32px; padding:2px 8px; font-size:0.82rem; border:1px solid #ced4da; border-radius:6px; background:#fff; width:100%;">
                            <option value="abertas" <?= $statusFiltro === 'abertas' ? 'selected' : '' ?>>Abertas / Pendentes</option>
                            <option value="resolvidas" <?= $statusFiltro === 'resolvidas' ? 'selected' : '' ?>>Resolvidas</option>
                            <option value="todas" <?= $statusFiltro === 'todas' ? 'selected' : '' ?>>Todas as Ocorrências</option>
                        </select>
                    </div>

                    <!-- Filtro por Tipo de Ocorrência -->
                    <div style="flex: 1.2; min-width: 160px;">
                        <label style="font-weight:bold; font-size:0.75rem; color:#495057; display:block; margin-bottom:2px;">TIPO / CATEGORIA</label>
                        <select name="oco_tipo" class="browser-default" onchange="this.form.submit()" style="height:32px; padding:2px 8px; font-size:0.82rem; border:1px solid #ced4da; border-radius:6px; background:#fff; width:100%;">
                            <option value="">Todos os Tipos</option>
                            <?php foreach ($mapaCoresTipo as $tId => $tInfo): ?>
                                <option value="<?= $tId ?>" <?= $tipoFiltro == $tId ? 'selected' : '' ?>><?= htmlspecialchars($tInfo['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro por Responsabilidade -->
                    <div style="flex: 1.2; min-width: 140px;">
                        <label style="font-weight:bold; font-size:0.75rem; color:#495057; display:block; margin-bottom:2px;">RESPONSABILIDADE</label>
                        <select name="responsabilidade" class="browser-default" onchange="this.form.submit()" style="height:32px; padding:2px 8px; font-size:0.82rem; border:1px solid #ced4da; border-radius:6px; background:#fff; width:100%;">
                            <option value="">Todas</option>
                            <option value="conselho" <?= $respFiltro === 'conselho' ? 'selected' : '' ?>>Conselho</option>
                            <option value="sindico" <?= $respFiltro === 'sindico' ? 'selected' : '' ?>>Síndico</option>
                            <option value="sub" <?= $respFiltro === 'sub' ? 'selected' : '' ?>>Subsíndico</option>
                            <option value="adm" <?= $respFiltro === 'adm' ? 'selected' : '' ?>>Administradora</option>
                            <option value="operacional" <?= $respFiltro === 'operacional' ? 'selected' : '' ?>>Operacional</option>
                            <option value="juridico" <?= $respFiltro === 'juridico' ? 'selected' : '' ?>>Jurídico</option>
                        </select>
                    </div>

                    <!-- Busca Rápida no Cliente -->
                    <div style="flex: 1.8; min-width: 180px;">
                        <label style="font-weight:bold; font-size:0.75rem; color:#495057; display:block; margin-bottom:2px;">BUSCA RÁPIDA (TEXTO)</label>
                        <input type="text" id="input-busca-rapida-oco" placeholder="Digite para filtrar instantaneamente..." style="height:32px; line-height:32px; padding:0 8px; font-size:0.82rem; border:1px solid #ced4da; border-radius:6px; background:#fff; margin:0; width:100%; box-sizing:border-box;">
                    </div>

                    <!-- Botão Limpar Filtros -->
                    <div style="margin-top: 14px;">
                        <a href="index.php?pag=livroDeOcorrencias&visao=analitico" class="btn-small waves-effect waves-light grey lighten-1" title="Limpar Filtros" style="height:32px; line-height:32px; padding:0 10px;">
                            <i class="material-icons tiny left">filter_alt_off</i> Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Alertas Toast / Banner -->
    <?php if ($toastAlert): ?>
        <div style="margin-top: 10px; padding: 10px 15px; background-color: <?= $toastAlert['type'] === 'danger' ? '#f8d7da' : '#fff3cd' ?>; color: <?= $toastAlert['type'] === 'danger' ? '#721c24' : '#856404' ?>; border-radius: 4px; font-size: 0.9rem;">
            <i class="material-icons tiny">warning</i> <?= htmlspecialchars($toastAlert['message']) ?>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div style="margin-top: 10px; padding: 10px 15px; background-color: <?= $msgType === 'success' ? '#d4edda' : ($msgType === 'warning' ? '#fff3cd' : '#f8d7da') ?>; color: <?= $msgType === 'success' ? '#155724' : ($msgType === 'warning' ? '#856404' : '#721c24') ?>; border-radius: 4px;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($visao === 'pratico' && !empty($debugPratico)): ?>
        <!-- Painel de Diagnóstico & Debug VDS (Visão Prática) -->
        <div style="margin-top: 10px; background: #212529; color: #f8f9fa; border-radius: 6px; padding: 10px 14px; font-family: monospace; font-size: 0.8rem; border-left: 4px solid #0d6efd;">
            <div style="display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="$('#vds-debug-details').slideToggle(150);">
                <span>
                    <strong><i class="material-icons tiny" style="vertical-align:middle; color:#0d6efd;">bug_report</i> DIAGNÓSTICO VDS (PRÁTICO):</strong>
                    HTTP <?= htmlspecialchars($debugPratico['http_code'] ?? 'N/A') ?> |
                    Total Regs: <?= (int)($totalRegsPratico ?? 0) ?> |
                    Carregados (Pág 1): <?= count($ocorrencias) ?> |
                    Ultra-Login: <?= !empty($debugPratico['token_found']) ? 'SIM (Ativo)' : 'NÃO (Ausente)' ?>
                </span>
                <i class="material-icons tiny">expand_more</i>
            </div>
            <div id="vds-debug-details" style="display:none; margin-top:8px; padding-top:8px; border-top:1px solid #343a40; word-break:break-all;">
                <div><strong>Conselheiro ID:</strong> <?= (int)($debugPratico['usuario_id_conselho'] ?? 0) ?></div>
                <div><strong>URL VDS Chamada:</strong> <?= htmlspecialchars($debugPratico['url'] ?? 'N/A') ?></div>
                <div><strong>Erro cURL:</strong> <?= htmlspecialchars($debugPratico['curl_error'] ?? 'Nenhum') ?></div>
                <div><strong>Has More Pages:</strong> <?= !empty($hasMorePratico) ? 'SIM' : 'NÃO' ?></div>
                <div style="margin-top:4px;"><strong>Response Preview:</strong></div>
                <pre style="background:#111; color:#00ff66; padding:6px; border-radius:4px; max-height:120px; overflow:auto; margin:4px 0 0 0; font-size:0.75rem;"><?= htmlspecialchars($debugPratico['response_preview'] ?? 'N/A') ?></pre>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row" style="margin: 0;">
    <!-- Sidebar Left: Feed de Ocorrências Agrupadas por Categoria/Tipo -->
    <div class="col s12 m4 l3 sidebar-feed">
        <!-- Subcabeçalho de Contexto da Lista com Master Toggle -->
        <div style="padding: 10px 15px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; font-size:0.8rem; color:#555; display:flex; justify-content:space-between; align-items:center;">
            <span>
                <strong>Modo <?= $visao === 'pratico' ? 'Prático' : 'Analítico' ?>:</strong> 
                <span id="cnt-visivel-ocorrencias"><?= count($ocorrencias) ?></span> item(ns)
            </span>
            <?php if (!empty($ocorrenciasAgrupadas) && count($ocorrenciasAgrupadas) > 1): ?>
                <button type="button" id="btn-toggle-todos-grupos" class="btn-flat btn-small" style="padding:0 4px; height:24px; line-height:24px; font-size:0.75rem; color:#0d6efd;" title="Alternar recolhimento de todas as categorias">
                    <i class="material-icons tiny left" style="margin-right:2px;">unfold_less</i> <span id="lbl-toggle-grupos">Recolher Todos</span>
                </button>
            <?php else: ?>
                <small style="color:#888;"><?= $visao === 'pratico' ? 'Não lidos (VDS)' : 'Banco Local' ?></small>
            <?php endif; ?>
        </div>

        <?php if (empty($ocorrencias)): ?>
            <div style="padding: 25px 15px; text-align: center; color: #888; font-size:0.9rem;">
                <?php if ($visao === 'pratico' && !$hasUltraLogin): ?>
                    <i class="material-icons medium" style="color:#d84315;">vpn_key</i><br>
                    <strong style="color:#d84315;">Ultra-Login Necessário</strong><br>
                    <span style="font-size:0.82rem; color:#666; display:inline-block; margin-top:6px;">Conecte seu usuário pessoal nas Configurações VDS para acessar os chamados não lidos.</span><br><br>
                    <a href="index.php?pag=configVds" class="btn-small purple waves-effect waves-light" style="border-radius:4px; text-transform:none;">Ativar Ultra-Login</a>
                <?php else: ?>
                    <i class="material-icons medium" style="color:#ccc;">check_circle_outline</i><br>
                    <?= $visao === 'pratico' ? 'Nenhuma ocorrência não lida no momento na VDS!' : 'Nenhuma ocorrência encontrada para os filtros selecionados.' ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Lista Agrupada por Tipo/Categoria de Ocorrência -->
            <?php foreach ($ocorrenciasAgrupadas as $tId => $groupItems): ?>
                <?php
                $infoTipo = $mapaCoresTipo[$tId] ?? ['nome' => 'Outros / Diversos', 'bg' => '#6c757d', 'color' => '#ffffff'];
                $groupId = "grupo-tipo-" . $tId;
                ?>
                <div class="grupo-oco-wrapper" data-tipo-id="<?= $tId ?>" style="margin-bottom: 2px;">
                    <!-- Cabeçalho Colapsável da Categoria -->
                    <div class="grupo-oco-header" data-target="<?= $groupId ?>" style="padding: 8px 12px; background: #e9ecef; border-bottom: 1px solid #dee2e6; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; transition: background 0.15s;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <i class="material-icons tiny grupo-icon-toggle" style="color:#495057; font-size: 1.1rem; transition: transform 0.2s;">expand_less</i>
                            <span class="badge-tipo" style="background-color: <?= $infoTipo['bg'] ?>; color: <?= $infoTipo['color'] ?>; font-size: 0.75rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">
                                <?= htmlspecialchars($infoTipo['nome']) ?>
                            </span>
                        </div>
                        <span class="badge grey lighten-1 black-text cnt-grupo-badge" style="border-radius: 10px; font-weight: bold; font-size:0.75rem; padding: 1px 7px; float:none; margin:0;">
                            <?= count($groupItems) ?>
                        </span>
                    </div>

                    <!-- Corpo com as Ocorrências da Categoria -->
                    <div class="grupo-oco-body" id="<?= $groupId ?>">
                        <?php foreach ($groupItems as $oco): ?>
                            <?php
                            $isSel = ($selId == $oco['id']);
                            $dadosJsonItem = !empty($oco['dados_json']) ? json_decode($oco['dados_json'], true) : [];
                            $searchContext = strtolower($oco['bloco'] . ' ' . $oco['unidade'] . ' ' . ($oco['protocolo_vds'] ?? '') . ' ' . ($oco['responsabilidade'] ?? '') . ' ' . ($dadosJsonItem['mensagem'] ?? '') . ' ' . ($dadosJsonItem['titulo'] ?? ''));
                            ?>
                            <div class="item-oco <?= $isSel ? 'active' : '' ?>" id="item-oco-<?= $oco['id'] ?>" data-search="<?= htmlspecialchars($searchContext) ?>" onclick="window.location.href='index.php?pag=livroDeOcorrencias&visao=<?= $visao ?>&id=<?= $oco['id'] ?>&bloco=<?= urlencode($blocoFiltro) ?>&unidade=<?= urlencode($unidadeFiltro) ?>&status=<?= urlencode($statusFiltro) ?>&oco_tipo=<?= urlencode($tipoFiltro) ?>&responsabilidade=<?= urlencode($respFiltro) ?>'">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <span class="badge-tipo" style="background-color: <?= $infoTipo['bg'] ?>; color: <?= $infoTipo['color'] ?>;">
                                        <?= htmlspecialchars($infoTipo['nome']) ?>
                                    </span>
                                    <span style="font-size: 0.75rem; color: #888;">
                                        Prot: <?= htmlspecialchars($oco['protocolo_vds'] ?? $oco['id']) ?>
                                    </span>
                                </div>

                                <div style="font-weight: 600; font-size: 0.95rem; color: #333;">
                                    Bloco <?= htmlspecialchars($oco['bloco']) ?> - Apt <?= htmlspecialchars($oco['unidade']) ?>
                                </div>

                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 6px; font-size:0.8rem; color:#666;">
                                    <span>Resp: <strong class="resp-text-label"><?= strtoupper($oco['responsabilidade'] ?? 'Pendente') ?></strong></span>
                                    <span class="status-resolvido-label" style="color: <?= $oco['resolvido'] ? '#28a745' : '#dc3545' ?>;">
                                        <?= $oco['resolvido'] ? '✓ Resolvido' : '• Aberto' ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Main Chat & Details -->
    <div class="col s12 m8 l9 chat-container" style="padding:0;">
        <!-- Skeleton Placeholder (Exibido Instantaneamente ao Clicar em Qualquer Ocorrência ou Filtro) -->
        <div id="vds-skeleton-chat-container">
            <!-- Header do Chat Skeleton -->
            <div class="chat-header" style="background:#ffffff; padding:12px 20px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:12px; width:55%;">
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="width:40px; height:40px; border-radius:50%;"></div>
                    <div style="flex:1;">
                        <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:16px; width:45%; margin-bottom:6px;"></div>
                        <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:12px; width:75%;"></div>
                    </div>
                </div>
                <div style="display:flex; gap:8px;">
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:30px; width:90px; border-radius:4px;"></div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:30px; width:130px; border-radius:4px;"></div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:30px; width:130px; border-radius:4px;"></div>
                </div>
            </div>

            <!-- Body do Chat Skeleton (Balões de Conversa Shimmer Estilo WhatsApp) -->
            <div class="chat-body" style="padding:20px; display:flex; flex-direction:column; gap:18px; background:#efeae2; flex:1; overflow-y:auto;">
                <!-- Balão 1: Morador (Esquerda - Branco) -->
                <div class="msg-bubble msg-left" style="width:68%; background:#ffffff; box-shadow:0 1px 2px rgba(0,0,0,0.08); padding:14px 16px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:14px; width:30%;"></div>
                        <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:12px; width:20%;"></div>
                    </div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:13px; width:92%; margin-bottom:6px;"></div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:13px; width:80%; margin-bottom:6px;"></div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:13px; width:60%;"></div>
                </div>

                <!-- Balão 2: Resposta do Conselho (Direita - Verde) -->
                <div class="msg-bubble msg-right" style="width:62%; background:#dcf8c6; margin-left:auto; box-shadow:0 1px 2px rgba(0,0,0,0.08); padding:14px 16px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:14px; width:35%;"></div>
                        <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:12px; width:22%;"></div>
                    </div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:13px; width:88%; margin-bottom:6px;"></div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:13px; width:70%;"></div>
                </div>

                <!-- Balão 3: Nota Interna (Direita - Amarelo) -->
                <div class="msg-bubble msg-internal" style="width:58%; background:#fff3cd; border:1px solid #ffeba0; margin-left:auto; box-shadow:0 1px 2px rgba(0,0,0,0.08); padding:14px 16px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:14px; width:45%;"></div>
                        <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:12px; width:20%;"></div>
                    </div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:13px; width:85%; margin-bottom:6px;"></div>
                    <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:13px; width:50%;"></div>
                </div>
            </div>

            <!-- Footer Input Skeleton -->
            <div class="chat-footer" style="background:#ffffff; padding:15px; border-top:1px solid #e0e0e0; display:flex; gap:10px; align-items:center;">
                <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:42px; flex:1; border-radius:6px;"></div>
                <div class="vds-skeleton-box vds-skeleton-shimmer" style="height:42px; width:110px; border-radius:6px;"></div>
            </div>
        </div>

        <div id="chat-real-content" style="height:100%; display:flex; flex-direction:column;">
        <?php if (!$detalheSel): ?>
            <div style="padding: 40px; text-align: center; color: #888;">Selecione uma ocorrência na lista para visualizar o chat e mensagens.</div>
        <?php else: ?>
            <?php
            $local = $detalheSel['local'];
            $notas = $detalheSel['notasInternas'];
            $tags = $detalheSel['tagsUnidades'];
            $remote = $detalheSel['remoteData'];
            $tipoId = (int)($local['oco_tipo'] ?? 115);
            $infoTipo = $mapaCoresTipo[$tipoId] ?? ['nome' => 'Ocorrência', 'bg' => '#6c757d', 'color' => '#fff'];

            $dadosJsonLoc = !empty($local['dados_json']) ? json_decode($local['dados_json'], true) : [];
            
            // Verificar status de leitura relacional específico para este conselheiro
            $linkL = DBConnect();
            vds_ensure_leitura_table_exists($linkL);
            $stmtCheckL = mysqli_prepare($linkL, "SELECT lido FROM ocorrencia_leitura_conselheiro WHERE conselheiro_id = ? AND ocorrencia_id = ? LIMIT 1");
            $isLidaConselheiro = false;
            if ($stmtCheckL) {
                $locId = (int)$local['id'];
                mysqli_stmt_bind_param($stmtCheckL, "ii", $usuarioIdConselho, $locId);
                mysqli_stmt_execute($stmtCheckL);
                $resCheckL = mysqli_stmt_get_result($stmtCheckL);
                $rowCheckL = mysqli_fetch_assoc($resCheckL);
                if ($rowCheckL) {
                    $isLidaConselheiro = ((int)$rowCheckL['lido'] === 1);
                } else {
                    $isLidaConselheiro = !empty($dadosJsonLoc['lida']) || !empty($dadosJsonLoc['isLida']);
                }
                mysqli_stmt_close($stmtCheckL);
            }
            DBClose($linkL);

            $isLidaVds = $isLidaConselheiro;
            ?>

            <!-- Header do Chat -->
            <div class="chat-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="index.php?pag=livroDeOcorrencias&visao=<?= $visao ?>" class="btn-flat btn-small hide-on-large-only" style="padding:0 8px;">
                        <i class="material-icons">arrow_back</i>
                    </a>

                    <div>
                        <span class="badge-tipo" style="background-color: <?= $infoTipo['bg'] ?>; color: <?= $infoTipo['color'] ?>; margin-bottom:3px;">
                            <?= htmlspecialchars($infoTipo['nome']) ?>
                        </span>
                        <h6 style="margin: 2px 0; font-weight:600;">
                            Bloco <?= htmlspecialchars($local['bloco']) ?> - Unidade <?= htmlspecialchars($local['unidade']) ?> 
                            <small style="color:#666;">(Protocolo: <?= htmlspecialchars($local['protocolo_vds'] ?? $local['id']) ?>)</small>
                        </h6>
                        <div style="font-size: 0.8rem; color: #666;">
                            Abertura: <?= htmlspecialchars($local['abertura']) ?>
                        </div>
                    </div>
                </div>

                <!-- Botões Práticos com Ações AJAX Silenciosas (Sem Reload e Sem Skeleton) -->
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <!-- Grupo de Ícones para Classificação de Responsabilidade -->
                    <div style="display:flex; align-items:center; gap:4px; background:#f8f9fa; padding:2px 6px; border-radius:6px; border:1px solid #dee2e6;">
                        <span style="font-weight:600; font-size:0.75rem; color:#555; margin-right:2px;">Resp:</span>

                        <!-- Botão Não Atribuído -->
                        <button type="button" id="btn-resp-none" class="btn-flat btn-small btn-resp-icon <?= empty($local['responsabilidade']) ? 'active' : '' ?>" title="Não Atribuído / Pendente" onclick="executarAcaoAjaxResponsabilidade(<?= $local['id'] ?>, '')">
                            <i class="material-icons tiny">person_off</i>
                        </button>

                        <!-- Botão Síndico -->
                        <button type="button" id="btn-resp-sindico" class="btn-flat btn-small btn-resp-icon <?= $local['responsabilidade'] === 'sindico' ? 'active-sindico' : '' ?>" title="Síndico" onclick="executarAcaoAjaxResponsabilidade(<?= $local['id'] ?>, 'sindico')">
                            <i class="material-icons tiny">gavel</i>
                        </button>

                        <!-- Botão Subsíndico -->
                        <button type="button" id="btn-resp-sub" class="btn-flat btn-small btn-resp-icon <?= $local['responsabilidade'] === 'sub' ? 'active-sub' : '' ?>" title="Subsíndico" onclick="executarAcaoAjaxResponsabilidade(<?= $local['id'] ?>, 'sub')">
                            <i class="material-icons tiny">badge</i>
                        </button>
                    </div>

                    <!-- Marcar/Desmarcar Resolvido (Local) via AJAX -->
                    <button type="button" id="btn-ajax-resolvido" class="btn-small waves-effect waves-light <?= $local['resolvido'] ? 'grey' : 'green darken-1' ?>" style="height:30px; line-height:30px; padding:0 10px; font-size:0.8rem;" title="<?= $local['resolvido'] ? 'Reabrir Chamado (Local)' : 'Marcar como Resolvido (Local)' ?>" onclick="executarAcaoAjaxResolvido(<?= $local['id'] ?>, <?= $local['resolvido'] ? 0 : 1 ?>)">
                        <i class="material-icons left tiny" id="icon-ajax-resolvido"><?= $local['resolvido'] ? 'undo' : 'check_circle' ?></i>
                        <span id="lbl-ajax-resolvido"><?= $local['resolvido'] ? 'Reabrir (Local)' : 'Marcar Resolvido (Local)' ?></span>
                    </button>

                    <!-- Marcar como Lido / Não Lido (VDS Remoto - Icon Button Sugestivo via AJAX) -->
                    <button type="button" id="btn-ajax-lido" class="btn-small waves-effect waves-light <?= $isLidaVds ? 'orange darken-3' : 'teal' ?>" style="height:30px; line-height:30px; padding:0 10px; font-size:0.8rem;" title="<?= $isLidaVds ? 'Marcar como NÃO Lido na VDS' : 'Marcar como LIDO na VDS' ?>" onclick="executarAcaoAjaxLido(<?= $local['id'] ?>, '<?= htmlspecialchars($local['uuid_remoto'] ?? '') ?>', <?= $isLidaVds ? 0 : 1 ?>)">
                        <i class="material-icons left tiny" id="icon-ajax-lido"><?= $isLidaVds ? 'mark_email_unread' : 'mark_email_read' ?></i>
                        <span id="lbl-ajax-lido"><?= $isLidaVds ? 'Marcar NÃO Lido' : 'Marcar Lido' ?></span>
                    </button>
                </div>
            </div>

            <!-- Tags Vinculadas (Entrada Inteligente + Remoção via AJAX, Sem Reload) -->
            <div style="background:#fff; padding:8px 20px; border-bottom:1px solid #e0e0e0; font-size:0.85rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:6px;">
                    <strong>Tags / Vínculos:</strong>
                    <span id="tags-container" data-ocorrencia-id="<?= (int)$local['id'] ?>" style="display:inline-flex; align-items:center; flex-wrap:wrap; gap:6px;">
                        <?php if (empty($tags)): ?>
                            <span id="tags-vazio" style="color:#999;">Nenhuma tag vinculada</span>
                        <?php else: ?>
                            <?php foreach ($tags as $t): ?>
                                <?php if ($t['bloco'] === 'NOTIF'): ?>
                                    <span class="tag-badge badge orange lighten-4 orange-text text-darken-4" data-tag-id="<?= (int)$t['id'] ?>" title="Clique direito para remover" style="float:none; padding:2px 8px; margin:0; border-radius:4px; font-weight:600; position:relative; cursor:context-menu;">
                                        📋 Notificação <?= htmlspecialchars($t['unidade']) ?><span class="tag-remove-btn" title="Remover tag" style="display:none; cursor:pointer; margin-left:6px; color:#d32f2f; font-weight:bold;">×</span>
                                    </span>
                                <?php elseif ($t['bloco'] === 'TAG'): ?>
                                    <span class="tag-badge badge grey lighten-3 grey-text text-darken-3" data-tag-id="<?= (int)$t['id'] ?>" title="Clique direito para remover" style="float:none; padding:2px 8px; margin:0; border-radius:4px; font-weight:600; position:relative; cursor:context-menu;">
                                        🏷️ <?= htmlspecialchars($t['unidade']) ?><span class="tag-remove-btn" title="Remover tag" style="display:none; cursor:pointer; margin-left:6px; color:#d32f2f; font-weight:bold;">×</span>
                                    </span>
                                <?php else: ?>
                                    <span class="tag-badge badge blue lighten-4 blue-text text-darken-4" data-tag-id="<?= (int)$t['id'] ?>" title="Clique direito para remover" style="float:none; padding:2px 8px; margin:0; border-radius:4px; font-weight:600; position:relative; cursor:context-menu;">
                                        🏢 Bloco <?= htmlspecialchars($t['bloco']) ?> - Apt <?= htmlspecialchars($t['unidade']) ?><span class="tag-remove-btn" title="Remover tag" style="display:none; cursor:pointer; margin-left:6px; color:#d32f2f; font-weight:bold;">×</span>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Input Livre Inteligente (+ Tag) via AJAX -->
                <div style="display:flex; gap:6px; align-items:center; margin:0;">
                    <input type="text" id="input-adicionar-tag" placeholder="Digite Unidade (B1108) ou Notificação (123/2026)..." autocomplete="off" onkeydown="if(event.key==='Enter'){event.preventDefault(); executarAcaoAjaxAdicionarTag(<?= (int)$local['id'] ?>);}" style="height:28px; line-height:28px; margin:0; font-size:0.8rem; width:260px; padding:0 8px; border:1px solid #ccc; border-radius:4px; background:#fff;">
                    <button type="button" id="btn-adicionar-tag" onclick="executarAcaoAjaxAdicionarTag(<?= (int)$local['id'] ?>)" class="btn-small waves-effect waves-light blue darken-1" style="height:28px; line-height:28px; padding:0 8px; font-size:0.75rem;">
                        <i class="material-icons left tiny" style="margin-right:2px;">add</i> Tag
                    </button>
                </div>
            </div>

            <!-- Feed do Chat WhatsApp -->
            <div class="chat-body">
                <!-- Mensagens da VDS (Morador / Remoto) -->
                <?php
                $eventosRemotos = [];
                if (!empty($remote)) {
                    if (isset($remote['eventos']) && is_array($remote['eventos'])) {
                        $eventosRemotos = $remote['eventos'];
                    } elseif (isset($remote['regs']) && is_array($remote['regs'])) {
                        $eventosRemotos = $remote['regs'];
                    } elseif (isset($remote['data']['eventos']) && is_array($remote['data']['eventos'])) {
                        $eventosRemotos = $remote['data']['eventos'];
                    } elseif (is_array($remote) && isset($remote[0])) {
                        $eventosRemotos = $remote;
                    }
                }

                // Fallback: Se não houver lista de eventos na resposta remota, extrai a mensagem inicial do dados_json armazenado localmente
                if (empty($eventosRemotos) && !empty($local['dados_json'])) {
                    $dadosJsonLocal = json_decode($local['dados_json'], true);
                    if (!empty($dadosJsonLocal)) {
                        if (isset($dadosJsonLocal['eventos']) && is_array($dadosJsonLocal['eventos'])) {
                            $eventosRemotos = $dadosJsonLocal['eventos'];
                        } elseif (!empty($dadosJsonLocal['mensagem']) || !empty($dadosJsonLocal['titulo'])) {
                            $eventosRemotos[] = [
                                'por' => $dadosJsonLocal['por'] ?? ($dadosJsonLocal['autor']['nome'] ?? 'Morador/Solicitante'),
                                'cargo' => $dadosJsonLocal['cargo'] ?? 'Morador',
                                'mensagem' => $dadosJsonLocal['mensagem'] ?? ($dadosJsonLocal['titulo'] ?? ''),
                                'dtHora' => $dadosJsonLocal['dtExibicao'] ?? ($dadosJsonLocal['dthora'] ?? ($dadosJsonLocal['abertura'] ?? '')),
                                'foto' => $dadosJsonLocal['foto'] ?? '',
                                'listaAnexo' => $dadosJsonLocal['listaAnexo'] ?? []
                            ];
                        }
                    }
                }

                // Mapear IDs de eventos VDS publicados pelo Conselho => dados do conselheiro (nome, avatar, id)
                $publishedEventMap = [];
                foreach ($notas as $n) {
                    if (!empty($n['vds_evento_uuid'])) {
                        $publishedEventMap[(string)$n['vds_evento_uuid']] = [
                            'nome' => $n['conselheiro_nome'] ?? 'Conselheiro',
                            'avatar' => $n['conselheiro_avatar'] ?? null,
                            'id' => $n['conselheiro_id'] ?? null
                        ];
                    }
                }
                ?>

                <?php if (!empty($eventosRemotos)): ?>
                    <?php foreach ($eventosRemotos as $ev): ?>
                        <?php
                        $evId = (string)($ev['ocorrencia'] ?? ($ev['ocorrenciaId'] ?? ($ev['id'] ?? '')));
                        $conselheiroAutor = ($evId && isset($publishedEventMap[$evId])) ? $publishedEventMap[$evId] : null;

                        $porNome = $ev['por'] ?? ($ev['autor']['nome'] ?? 'Morador/Solicitante');
                        $cargo = $ev['cargo'] ?? 'Morador';
                        $mensagemTexto = $ev['mensagem'] ?? '';
                        $dthoraStr = $ev['dtHora'] ?? ($ev['dthora'] ?? ($ev['data'] ?? ''));
                        if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $dthoraStr)) {
                            $dthoraStr = date('d/m/Y H:i', strtotime($dthoraStr));
                        }
                        
                        $foto = $ev['foto'] ?? ($ev['fotoUrl'] ?? '');
                        if ($foto && strpos($foto, '/') === 0) {
                            $foto = 'https://app.vidadesindico.com.br' . $foto;
                        }
                        ?>
                        <div class="msg-bubble msg-left">
                            <div class="msg-author">
                                <span style="display:flex; align-items:center; gap:6px;">
                                    <?php if ($foto): ?>
                                        <img src="<?= htmlspecialchars($foto) ?>" style="width:24px; height:24px; border-radius:50%; object-fit:cover;">
                                    <?php endif; ?>
                                    <b><?= htmlspecialchars($porNome) ?></b> 
                                    <small style="color:#666;">(<?= htmlspecialchars($cargo) ?>)</small>
                                </span>
                            </div>
                            
                            <div style="margin-top:4px;"><?= vds_format_mensagem_text($mensagemTexto) ?></div>

                            <?php if (!empty($ev['listaAnexo'])): ?>
                                <div style="margin-top: 8px; display:flex; flex-direction:column; gap:6px;">
                                    <?php foreach ($ev['listaAnexo'] as $anx): ?>
                                        <?php
                                        $anxUrl = $anx['url'] ?? '';
                                        if ($anxUrl && strpos($anxUrl, '/') === 0) {
                                            $anxUrl = 'https://app.vidadesindico.com.br' . $anxUrl;
                                        }
                                        $nomeAnx = $anx['nome'] ?? 'anexo';
                                        $ext = strtolower(pathinfo($nomeAnx, PATHINFO_EXTENSION));
                                        ?>
                                        <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                            <div>
                                                <img src="<?= htmlspecialchars($anxUrl) ?>" class="responsive-img materialboxed z-depth-1" style="max-width:280px; max-height:200px; border-radius:8px; cursor:pointer;" alt="<?= htmlspecialchars($nomeAnx) ?>">
                                            </div>
                                        <?php elseif (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'])): ?>
                                            <div>
                                                <video controls preload="metadata" style="max-width:300px; max-height:220px; border-radius:8px;">
                                                    <source src="<?= htmlspecialchars($anxUrl) ?>" type="video/<?= $ext === 'mov' ? 'mp4' : $ext ?>">
                                                    Seu navegador não suporta a exibição deste vídeo.
                                                </video>
                                            </div>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($anxUrl) ?>" target="_blank" class="blue-text" style="font-size:0.85rem; display:inline-flex; align-items:center; gap:4px;">
                                                <i class="material-icons tiny">attach_file</i> <?= htmlspecialchars($nomeAnx) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="msg-time" style="display:flex; justify-content:space-between; align-items:center;">
                                <span><?= htmlspecialchars($dthoraStr) ?></span>
                                <?php if ($conselheiroAutor): ?>
                                    <span style="background:#e8f5e9; color:#2e7d32; padding:2px 8px; border-radius:12px; font-size:0.75rem; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                        <?php if (!empty($conselheiroAutor['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($conselheiroAutor['avatar']) ?>" style="width:16px; height:16px; border-radius:50%; object-fit:cover;">
                                        <?php else: ?>
                                            <i class="material-icons tiny" style="font-size:1rem;">person</i>
                                        <?php endif; ?>
                                        Publicado por <?= htmlspecialchars($conselheiroAutor['nome']) ?> (ID: <?= htmlspecialchars($conselheiroAutor['id']) ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Notas Internas do Conselho (somente as NÃO publicadas no remoto) -->
                <?php foreach ($notas as $n): ?>
                    <?php if ($n['enviado_remoto']) continue; ?>
                    <?php
                    $avatarUser = !empty($n['conselheiro_avatar']) ? $n['conselheiro_avatar'] : '';
                    if (empty($avatarUser) && isset($_SESSION['avatar']) && $n['conselheiro_id'] == $usuarioIdConselho) {
                        $avatarUser = $_SESSION['avatar'];
                    }
                    if ($avatarUser && strpos($avatarUser, 'http') !== 0 && strpos($avatarUser, '/') !== 0) {
                        $avatarUser = '/' . $avatarUser;
                    }
                    $isMinhaNota = ((int)($n['conselheiro_id'] ?? 0) === (int)$usuarioIdConselho);
                    ?>
                    <div class="msg-bubble msg-internal">
                        <div class="msg-author">
                            <span style="display:flex; align-items:center; gap:6px;">
                                <?php if ($avatarUser): ?>
                                    <img src="<?= htmlspecialchars($avatarUser) ?>" style="width:24px; height:24px; border-radius:50%; object-fit:cover; border:1px solid #e0c068;">
                                <?php else: ?>
                                    <i class="material-icons tiny" style="vertical-align:middle; color:#856404;">lock_outline</i>
                                <?php endif; ?>
                                <b><?= htmlspecialchars($n['conselheiro_nome']) ?></b> 
                                <small style="color:#856404;">(ID: <?= htmlspecialchars($n['conselheiro_id'] ?? '1') ?> - Nota Interna)</small>
                            </span>
                        </div>
                        <div style="margin-top:4px; font-size:0.95rem;"><?= nl2br(htmlspecialchars($n['texto'])) ?></div>
                        
                        <div class="msg-time" style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                            <span style="font-size:0.75rem; color:#856404;"><?= htmlspecialchars($n['created_at']) ?></span>
                            
                            <?php if ($isMinhaNota): ?>
                                <?php if ($hasUltraLogin): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="publicar_remoto">
                                        <input type="hidden" name="nota_id" value="<?= $n['id'] ?>">
                                        <button type="submit" class="btn-small orange white-text font-weight-bold" style="height:26px; line-height:26px; padding:0 10px; font-size:0.75rem; border-radius:4px;">
                                            Publicar no Remoto (VDS) <i class="material-icons right tiny" style="margin-left:4px;">send</i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="index.php?pag=configVds" class="btn-small grey lighten-1 black-text font-weight-bold" style="height:26px; line-height:26px; padding:0 10px; font-size:0.72rem; border-radius:4px; text-transform:none;" title="Ative seu Ultra-Login para publicar diretamente na VDS">
                                        <i class="material-icons left tiny" style="margin-right:2px;">vpn_key</i> Requer Ultra-Login p/ Publicar VDS
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size:0.72rem; color:#856404; font-style:italic; background:#fff3cd; padding:2px 8px; border-radius:4px; border:1px solid #ffeeba; display:inline-flex; align-items:center; gap:3px;">
                                    <i class="material-icons tiny" style="vertical-align:middle; font-size:0.85rem;">lock</i> Apenas Leitura (Somente o autor pode publicar)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <!-- Footer: Adicionar Nota Interna (1º Fator) -->
            <div class="chat-footer">
                <form method="POST">
                    <input type="hidden" name="action" value="adicionar_nota_interna">
                    <input type="hidden" name="ocorrencia_id" value="<?= $local['id'] ?>">
                    
                    <div style="display:flex; gap:10px; align-items:center;">
                        <textarea name="texto" placeholder="Digite uma Nota Interna do Conselho..." required style="flex:1; border:1px solid #ccc; border-radius:6px; padding:8px; height:50px; resize:none; font-family:inherit;"></textarea>
                        <button type="submit" class="btn waves-effect waves-light amber darken-3" style="height:50px;">
                            Salvar Nota Interna <i class="material-icons right">note_add</i>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Console de Diagnóstico & Debug VDS (Visível com debug=1 ou se não carregou ocorrência/eventos) -->
<?php if (!empty($_GET['debug']) || !$detalheSel || empty($eventosRemotos) || !empty($debugPratico)): ?>
    <div style="background:#1e1e1e; color:#00ff66; padding:15px; border-radius:6px; font-family:monospace; font-size:0.8rem; margin:15px; overflow-x:auto;">
        <strong style="color:#fff; font-size:0.9rem;"><i class="material-icons tiny">bug_report</i> Console de Diagnóstico & Debug VDS (Visão: <?= strtoupper($visao) ?>)</strong>
        <hr style="border-color:#444; margin:8px 0;">
        
        <?php 
        $dbg = !empty($debugPratico) ? $debugPratico : ($detalheSel['debug'] ?? []);
        $tokenFoundPratico = !empty($debugPratico['token_found']) || ($dbg['token_found'] ?? false) || $hasUltraLogin;
        ?>
        <div><strong>ConselheiroID em Sessão:</strong> <?= htmlspecialchars($usuarioIdConselho) ?></div>
        <div><strong>Input Pesquisado:</strong> <?= htmlspecialchars($protoFiltro ?: ($selId ?: 'Nenhum')) ?></div>
        <div><strong>Token Encontrado (Conselheiro <?= htmlspecialchars($usuarioIdConselho) ?>):</strong> <?= $tokenFoundPratico ? 'SIM (Ativo)' : '<span style="color:#ff4444;">NÃO (Ausente no vds_tokens para ConselheiroID ' . htmlspecialchars($usuarioIdConselho) . ')</span>' ?></div>
        <div><strong>Registro Local Encontrado:</strong> <?= ($dbg['local_found'] ?? false) ? 'SIM (ID: ' . ($dbg['local_record']['id'] ?? '') . ', UUID: ' . ($dbg['local_record']['uuid_remoto'] ?? 'Vazio') . ', Prot: ' . ($dbg['local_record']['protocolo_vds'] ?? 'Vazio') . ')' : 'NÃO (Registro Inexistente)' ?></div>
        
        <?php if (!empty($debugPratico['url'])): ?>
            <div style="margin-top:8px;">
                <strong>Consulta Não Lidos VDS (/ocorrencia?Lida=0):</strong> Executada<br>
                URL: <code><?= htmlspecialchars($debugPratico['url']) ?></code> | HTTP Code: <strong><?= htmlspecialchars($debugPratico['http_code'] ?? 'N/A') ?></strong>
                <?php if (!empty($debugPratico['curl_error'])): ?>
                    | <span style="color:#ff4444;"><strong>Erro cURL:</strong> <?= htmlspecialchars($debugPratico['curl_error']) ?></span>
                <?php endif; ?>
                <br>
                Response Preview: <pre style="background:#2d2d2d; color:#ce9178; padding:5px; margin:4px 0; max-height:140px; overflow-y:auto; font-size:0.75rem;"><?= htmlspecialchars(substr($debugPratico['response_preview'] ?? 'Sem Resposta', 0, 1000)) ?></pre>
            </div>
        <?php endif; ?>

        <div style="margin-top:8px;">
            <strong>Busca por Protocolo na VDS:</strong> <?= ($dbg['search_api_called'] ?? false) ? 'SIM' : 'NÃO Executada' ?><br>
            <?php if (!empty($dbg['search_url'])): ?>
                URL: <code><?= htmlspecialchars($dbg['search_url']) ?></code> | HTTP Code: <strong><?= htmlspecialchars($dbg['search_http_code'] ?? 'N/A') ?></strong> | Registros Retornados: <?= htmlspecialchars($dbg['search_regs_count'] ?? 0) ?><br>
                Response Preview: <pre style="background:#2d2d2d; color:#ce9178; padding:5px; margin:4px 0; max-height:140px; overflow-y:auto; font-size:0.75rem;"><?= htmlspecialchars(substr($dbg['search_raw_response'] ?? 'Sem Resposta', 0, 1000)) ?></pre>
            <?php endif; ?>
        </div>

        <div style="margin-top:8px;">
            <strong>Consulta de Detalhes da Ocorrência (/ocorrencia/{uuid}):</strong> <?= ($dbg['detail_api_called'] ?? false) ? 'SIM' : 'NÃO Executada' ?><br>
            <?php if (!empty($dbg['detail_url'])): ?>
                URL: <code><?= htmlspecialchars($dbg['detail_url']) ?></code> | HTTP Code: <strong><?= htmlspecialchars($dbg['detail_http_code'] ?? 'N/A') ?></strong><br>
                Response Preview: <pre style="background:#2d2d2d; color:#ce9178; padding:5px; margin:4px 0; max-height:140px; overflow-y:auto; font-size:0.75rem;"><?= htmlspecialchars(substr($dbg['detail_raw_response'] ?? 'Sem Resposta', 0, 1000)) ?></pre>
            <?php endif; ?>
        </div>

        <!-- Estatísticas da tabela vds_tokens -->
        <?php $tokenStats = vds_get_tokens_debug_stats(); ?>
        <div style="margin-top:10px; background:#111; padding:10px; border-radius:4px; border:1px solid #333;">
            <strong style="color:#e0e0e0;">Estatísticas da tabela `vds_tokens`:</strong> 
            Tabela existe: <strong><?= $tokenStats['table_exists'] ? 'SIM' : 'NÃO' ?></strong> | 
            Total Registros: <strong><?= $tokenStats['total_rows'] ?></strong>
            <?php if (!empty($tokenStats['db_error'])): ?>
                | <span style="color:#ff4444;">Erro MySQL: <?= htmlspecialchars($tokenStats['db_error']) ?></span>
            <?php endif; ?>
            <?php if (!empty($tokenStats['rows'])): ?>
                <table style="width:100%; margin-top:6px; color:#ccc; font-size:0.75rem; border-collapse:collapse;" border="1" cellpadding="3">
                    <tr style="background:#222; color:#fff;">
                        <th>ID</th><th>Tipo</th><th>ConselheiroID</th><th>Username</th><th>Token Length</th><th>Status</th><th>Updated At</th>
                    </tr>
                    <?php foreach ($tokenStats['rows'] as $tr): ?>
                        <tr>
                            <td><?= $tr['id'] ?></td>
                            <td><?= htmlspecialchars($tr['tipo']) ?></td>
                            <td><?= htmlspecialchars($tr['usuario_id_conselho'] ?? 'NULL') ?></td>
                            <td><?= htmlspecialchars($tr['vds_username'] ?? 'N/A') ?></td>
                            <td><?= $tr['token_len'] ?> chars</td>
                            <td><?= htmlspecialchars($tr['status']) ?></td>
                            <td><?= htmlspecialchars($tr['updated_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($dbg['error_log'])): ?>
            <div style="margin-top:8px; color:#ff4444;">
                <strong>Logs de Alertas/Erros:</strong>
                <ul>
                    <?php foreach ($dbg['error_log'] as $err): ?>
                        <li>• <?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
        </div> <!-- Fecha #chat-real-content -->
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Finalizar a barra de progresso superior e ocultar o Skeleton Global de Tela Cheia
    let $loader = $('#vds-top-loader');
    if ($loader.length) {
        $loader.css('width', '100%');
        setTimeout(function() {
            $loader.css('opacity', 0);
            setTimeout(() => $loader.css('width', '0%'), 400);
        }, 200);
    }
    $('#vds-content-skeleton-overlay, #vds-full-page-skeleton-overlay').fadeOut(250);
    // Controller de Skeleton Screen & Progress Bar (Vercel Style)
    window.triggerVdsSkeleton = function(onlyChat = true) {
        // 1. Mostrar barra de progresso horizontal no topo da janela
        let $loader = $('#vds-top-loader');
        if (!$loader.length) {
            $('body').append('<div id="vds-top-loader"></div>');
            $loader = $('#vds-top-loader');
        }
        $loader.css({ width: '20%', opacity: 1 });
        setTimeout(() => $loader.css('width', '55%'), 150);
        setTimeout(() => $loader.css('width', '85%'), 400);

        // 2. Ocultar o conteúdo real e exibir o Esqueleto instantaneamente
        if (onlyChat) {
            $('#chat-real-content').hide();
            $('#vds-skeleton-chat-container').addClass('active').show();
        } else {
            $('#chat-real-content').hide();
            $('#vds-skeleton-chat-container').addClass('active').show();
            $('.sidebar-feed').css('opacity', '0.55');
        }
    };

    // Ativar o Skeleton ao clicar em qualquer item de ocorrência na lista
    $(document).on('click', '.item-oco', function() {
        triggerVdsSkeleton(true);
    });

    // Ativar o Skeleton ao trocar de visão (Prático x Analítico), sincronizar ou filtrar
    $(document).on('click', 'a[href*="livroDeOcorrencias"], button[type="submit"]', function() {
        triggerVdsSkeleton(false);
    });

    // Ativar ao submeter formulários de filtro / ação
    $(document).on('submit', 'form', function() {
        triggerVdsSkeleton(false);
    });
    // 1. Toggle individual de cabeçalho de categoria/grupo
    $(document).on('click', '.grupo-oco-header', function() {
        const targetId = $(this).data('target');
        const $body = $('#' + targetId);
        const $icon = $(this).find('.grupo-icon-toggle');
        
        $body.slideToggle(150, function() {
            if ($body.is(':visible')) {
                $icon.text('expand_less');
            } else {
                $icon.text('expand_more');
            }
        });
    });

    // 2. Master Toggle: Recolher / Expandir Todos os Grupos
    let todosExpandidos = true;
    $('#btn-toggle-todos-grupos').on('click', function(e) {
        e.preventDefault();
        todosExpandidos = !todosExpandidos;
        if (todosExpandidos) {
            $('.grupo-oco-body').slideDown(150);
            $('.grupo-icon-toggle').text('expand_less');
            $('#lbl-toggle-grupos').text('Recolher Todos');
        } else {
            $('.grupo-oco-body').slideUp(150);
            $('.grupo-icon-toggle').text('expand_more');
            $('#lbl-toggle-grupos').text('Expandir Todos');
        }
    });

    // 3. Busca Rápida Dinâmica no Cliente (Navegador)
    $('#input-busca-rapida-oco').on('keyup input', function() {
        const term = $(this).val().toLowerCase().trim();
        let visiveisTotais = 0;

        $('.grupo-oco-wrapper').each(function() {
            let visiveisNoGrupo = 0;
            $(this).find('.item-oco').each(function() {
                const itemData = $(this).data('search') || $(this).text().toLowerCase();
                if (!term || itemData.indexOf(term) !== -1) {
                    $(this).show();
                    visiveisNoGrupo++;
                    visiveisTotais++;
                } else {
                    $(this).hide();
                }
            });

            if (visiveisNoGrupo > 0) {
                $(this).show();
                if (term) {
                    $(this).find('.grupo-oco-body').show();
                    $(this).find('.grupo-icon-toggle').text('expand_less');
                }
            } else {
                $(this).hide();
            }
        });

        $('#cnt-visivel-ocorrencias').text(visiveisTotais);
    });

    // 4. Carregamento Progressivo via AJAX da API VDS (Página 2, 3, etc. injetadas dinamicamente)
    const mapaCoresTipoJS = <?= json_encode($mapaCoresTipo, JSON_UNESCAPED_UNICODE) ?>;
    const visaoJS = <?= json_encode($visao) ?>;
    let vdsCurrentPage = 1;
    let vdsHasMore = <?= !empty($hasMorePratico) ? 'true' : 'false' ?>;
    let vdsIsLoadingPage = false;
    const isVisaoPratico = <?= ($visao === 'pratico') ? 'true' : 'false' ?>;

    function injetarNovosItensDOM(items) {
        if (!items || !items.length) return;

        items.forEach(function(oco) {
            if ($('#item-oco-' + oco.id).length > 0) return;

            const tId = parseInt(oco.oco_tipo || 115);
            const infoTipo = mapaCoresTipoJS[tId] || { nome: 'Outros / Diversos', bg: '#6c757d', color: '#ffffff' };
            const groupId = "grupo-tipo-" + tId;

            let $groupWrapper = $('.grupo-oco-wrapper[data-tipo-id="' + tId + '"]');
            if ($groupWrapper.length === 0) {
                const groupHtml = `
                    <div class="grupo-oco-wrapper" data-tipo-id="${tId}" style="margin-bottom: 2px;">
                        <div class="grupo-oco-header" data-target="${groupId}" style="padding: 8px 12px; background: #e9ecef; border-bottom: 1px solid #dee2e6; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <i class="material-icons tiny grupo-icon-toggle" style="color:#495057; font-size: 1.1rem;">expand_less</i>
                                <span class="badge-tipo" style="background-color: ${infoTipo.bg}; color: ${infoTipo.color}; font-size: 0.75rem; padding: 2px 8px; border-radius: 10px; font-weight: 600;">
                                    ${infoTipo.nome}
                                </span>
                            </div>
                            <span class="badge grey lighten-1 black-text cnt-grupo-badge" style="border-radius: 10px; font-weight: bold; font-size:0.75rem; padding: 1px 7px; float:none; margin:0;">
                                0
                            </span>
                        </div>
                        <div class="grupo-oco-body" id="${groupId}"></div>
                    </div>
                `;
                $('.sidebar-feed').append(groupHtml);
                $groupWrapper = $('.grupo-oco-wrapper[data-tipo-id="' + tId + '"]');
            }

            const respText = oco.responsabilidade ? oco.responsabilidade.toUpperCase() : 'PENDENTE';
            const resolvidoText = oco.resolvido ? '✓ Resolvido' : '• Aberto';
            const resolvidoColor = oco.resolvido ? '#28a745' : '#dc3545';
            const protText = oco.protocolo_vds || oco.id;

            const itemHtml = `
                <div class="item-oco" id="item-oco-${oco.id}" onclick="window.location.href='index.php?pag=livroDeOcorrencias&visao=${visaoJS}&id=${oco.id}'">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <span class="badge-tipo" style="background-color: ${infoTipo.bg}; color: ${infoTipo.color};">
                            ${infoTipo.nome}
                        </span>
                        <span style="font-size: 0.75rem; color: #888;">
                            Prot: ${protText}
                        </span>
                    </div>

                    <div style="font-weight: 600; font-size: 0.95rem; color: #333;">
                        Bloco ${oco.bloco} - Apt ${oco.unidade}
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 6px; font-size:0.8rem; color:#666;">
                        <span>Resp: <strong>${respText}</strong></span>
                        <span style="color: ${resolvidoColor};">
                            ${resolvidoText}
                        </span>
                    </div>
                </div>
            `;

            $groupWrapper.find('.grupo-oco-body').append(itemHtml);

            const currentCnt = parseInt($groupWrapper.find('.cnt-grupo-badge').text()) || 0;
            $groupWrapper.find('.cnt-grupo-badge').text(currentCnt + 1);
        });

        const totalCount = $('.item-oco').length;
        $('#cnt-visivel-ocorrencias').text(totalCount);
    }

    function carregarProximaPaginaPratico() {
        if (!isVisaoPratico || !vdsHasMore || vdsIsLoadingPage) return;
        vdsIsLoadingPage = true;
        const nextPage = vdsCurrentPage + 1;

        $.ajax({
            url: 'vds_sync_async.php',
            type: 'GET',
            data: { page: nextPage, limit: 10 },
            dataType: 'json',
            timeout: 20000,
            success: function(data) {
                if (data.success && data.items && data.items.length > 0) {
                    vdsCurrentPage = data.page || nextPage;
                    vdsHasMore = !!data.hasMore;
                    injetarNovosItensDOM(data.items);
                } else {
                    vdsHasMore = false;
                }
            },
            error: function(xhr, status, err) {
                console.warn('[VDS Progressive Sync] Erro ao buscar página ' + nextPage, status);
            },
            complete: function() {
                vdsIsLoadingPage = false;
                if (vdsHasMore) {
                    setTimeout(carregarProximaPaginaPratico, 1500);
                }
            }
        });
    }

    if (isVisaoPratico && vdsHasMore) {
        setTimeout(carregarProximaPaginaPratico, 1500);
    }
});

// Funções de Ação Silenciosa por AJAX sem Reload e sem Skeleton Overlay
function executarAcaoAjaxResponsabilidade(ocorrenciaId, respVal) {
    $('.btn-resp-icon').css('opacity', '0.6');
    
    $.ajax({
        url: 'index.php?pag=livroDeOcorrencias',
        type: 'POST',
        data: {
            is_ajax: 1,
            action: 'atualizar_responsabilidade',
            ocorrencia_id: ocorrenciaId,
            responsabilidade: respVal
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('.btn-resp-icon').removeClass('active active-sindico active-sub').css('opacity', '1');
                if (!respVal) {
                    $('#btn-resp-none').addClass('active');
                } else if (respVal === 'sindico') {
                    $('#btn-resp-sindico').addClass('active-sindico');
                } else if (respVal === 'sub') {
                    $('#btn-resp-sub').addClass('active-sub');
                }

                const respText = respVal ? respVal.toUpperCase() : 'PENDENTE';
                $('#item-oco-' + ocorrenciaId + ' .resp-text-label').text(respText);
            }
        },
        error: function(err) {
            console.error('[AJAX Responsabilidade] Erro ao atualizar', err);
            $('.btn-resp-icon').css('opacity', '1');
        }
    });
}

function executarAcaoAjaxResolvido(ocorrenciaId, novoResolvidoVal) {
    const $btn = $('#btn-ajax-resolvido');
    const $icon = $('#icon-ajax-resolvido');
    const $lbl = $('#lbl-ajax-resolvido');

    $btn.css('opacity', '0.7');
    $icon.text('sync').addClass('spin-icon');

    $.ajax({
        url: 'index.php?pag=livroDeOcorrencias',
        type: 'POST',
        data: {
            is_ajax: 1,
            action: 'marcar_resolvido',
            ocorrencia_id: ocorrenciaId,
            resolvido_val: novoResolvidoVal
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                const isResolvido = !!res.resolvido;
                if (isResolvido) {
                    $btn.removeClass('green darken-1').addClass('grey').attr('title', 'Reabrir Chamado (Local)');
                    $icon.removeClass('spin-icon').text('undo');
                    $lbl.text('Reabrir (Local)');
                    $btn.attr('onclick', 'executarAcaoAjaxResolvido(' + ocorrenciaId + ', 0)');
                    $('#item-oco-' + ocorrenciaId + ' .status-resolvido-label').css('color', '#28a745').text('✓ Resolvido');
                } else {
                    $btn.removeClass('grey').addClass('green darken-1').attr('title', 'Marcar como Resolvido (Local)');
                    $icon.removeClass('spin-icon').text('check_circle');
                    $lbl.text('Marcar Resolvido (Local)');
                    $btn.attr('onclick', 'executarAcaoAjaxResolvido(' + ocorrenciaId + ', 1)');
                    $('#item-oco-' + ocorrenciaId + ' .status-resolvido-label').css('color', '#dc3545').text('• Aberto');
                }
            }
        },
        error: function(err) {
            console.error('[AJAX Resolvido] Erro ao atualizar', err);
        },
        complete: function() {
            $btn.css('opacity', '1');
            $icon.removeClass('spin-icon');
        }
    });
}

function executarAcaoAjaxLido(ocorrenciaId, uuidRemoto, novoStatusLidoVal) {
    const $btn = $('#btn-ajax-lido');
    const $icon = $('#icon-ajax-lido');
    const $lbl = $('#lbl-ajax-lido');

    $btn.css('opacity', '0.7');
    $icon.text('sync').addClass('spin-icon');

    $.ajax({
        url: 'index.php?pag=livroDeOcorrencias',
        type: 'POST',
        data: {
            is_ajax: 1,
            action: 'marcar_como_lido',
            ocorrencia_id: ocorrenciaId,
            uuid_remoto: uuidRemoto,
            novo_status_lido: novoStatusLidoVal
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                const isLida = !!res.isLidaVds;
                const isVisaoPratico = <?= ($visao === 'pratico') ? 'true' : 'false' ?>;
                if (isLida) {
                    $btn.removeClass('teal').addClass('orange darken-3').attr('title', 'Marcar como NÃO Lido na VDS');
                    $icon.removeClass('spin-icon').text('mark_email_unread');
                    $lbl.text('Marcar NÃO Lido');
                    $btn.attr('onclick', 'executarAcaoAjaxLido(' + ocorrenciaId + ', "' + uuidRemoto + '", 0)');

                    if (isVisaoPratico) {
                        $('#item-oco-' + ocorrenciaId).fadeOut(300, function() {
                            const visiveis = $('.item-oco:visible').length;
                            $('#cnt-visivel-ocorrencias').text(visiveis);
                        });
                    }
                } else {
                    $btn.removeClass('orange darken-3').addClass('teal').attr('title', 'Marcar como LIDO na VDS');
                    $icon.removeClass('spin-icon').text('mark_email_read');
                    $lbl.text('Marcar Lido');
                    $btn.attr('onclick', 'executarAcaoAjaxLido(' + ocorrenciaId + ', "' + uuidRemoto + '", 1)');

                    if (isVisaoPratico) {
                        $('#item-oco-' + ocorrenciaId).fadeIn(300, function() {
                            const visiveis = $('.item-oco:visible').length;
                            $('#cnt-visivel-ocorrencias').text(visiveis);
                        });
                    }
                }
            }
        },
        error: function(err) {
            console.error('[AJAX Lido] Erro ao atualizar', err);
        },
        complete: function() {
            $btn.css('opacity', '1');
            $icon.removeClass('spin-icon');
        }
    });
}

/* ================= Tags via AJAX (Sem Reload) ================= */

function renderTagBadge(tag) {
    let label, cls;
    if (tag.bloco === 'NOTIF') {
        label = '📋 Notificação ' + tag.unidade;
        cls = 'badge orange lighten-4 orange-text text-darken-4';
    } else if (tag.bloco === 'TAG') {
        label = '🏷️ ' + tag.unidade;
        cls = 'badge grey lighten-3 grey-text text-darken-3';
    } else {
        label = '🏢 Bloco ' + tag.bloco + ' - Apt ' + tag.unidade;
        cls = 'badge blue lighten-4 blue-text text-darken-4';
    }

    return $('<span>')
        .addClass('tag-badge ' + cls)
        .attr('data-tag-id', tag.id)
        .attr('title', 'Clique direito para remover')
        .css({ float: 'none', padding: '2px 8px', margin: '0', borderRadius: '4px', fontWeight: '600', position: 'relative', cursor: 'context-menu' })
        .html(label + '<span class="tag-remove-btn" title="Remover tag" style="display:none; cursor:pointer; margin-left:6px; color:#d32f2f; font-weight:bold;">×</span>');
}

function executarAcaoAjaxAdicionarTag(ocorrenciaId) {
    const $input = $('#input-adicionar-tag');
    const $btn = $('#btn-adicionar-tag');
    const tagInput = $input.val().trim();
    if (!tagInput) return;

    $btn.css('opacity', '0.7').prop('disabled', true);

    $.ajax({
        url: 'index.php?pag=livroDeOcorrencias',
        type: 'POST',
        data: {
            is_ajax: 1,
            action: 'adicionar_tag_livre',
            ocorrencia_id: ocorrenciaId,
            tag_input: tagInput
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#tags-vazio').remove();
                if (res.already_exists) {
                    M.toast({ html: res.message || 'Tag já vinculada a esta ocorrência.', classes: 'rounded orange' });
                } else if (res.tag) {
                    const $badge = renderTagBadge(res.tag);
                    $('#tags-container').append($badge);
                    $badge.hide().fadeIn(200);
                    M.toast({ html: res.message || 'Tag adicionada!', classes: 'rounded green' });
                } else {
                    M.toast({ html: res.message || 'Tag vinculada!', classes: 'rounded green' });
                }
                $input.val('').focus();
            } else {
                M.toast({ html: res.message || 'Falha ao adicionar tag.', classes: 'rounded red' });
            }
        },
        error: function(err) {
            console.error('[AJAX Tag] Erro ao adicionar', err);
            M.toast({ html: 'Erro de conexão ao adicionar tag.', classes: 'rounded red' });
        },
        complete: function() {
            $btn.css('opacity', '1').prop('disabled', false);
        }
    });
}

function removerTag(tagId, ocorrenciaId) {
    if (!confirm('Remover esta tag da ocorrência?')) return;

    $.ajax({
        url: 'index.php?pag=livroDeOcorrencias',
        type: 'POST',
        data: {
            is_ajax: 1,
            action: 'remover_tag',
            tag_id: tagId,
            ocorrencia_id: ocorrenciaId
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('[data-tag-id="' + tagId + '"]').fadeOut(200, function() {
                    $(this).remove();
                    if ($('#tags-container').find('.tag-badge').length === 0) {
                        $('#tags-container').html('<span id="tags-vazio" style="color:#999;">Nenhuma tag vinculada</span>');
                    }
                });
                M.toast({ html: res.message || 'Tag removida!', classes: 'rounded green' });
            } else {
                M.toast({ html: res.message || 'Falha ao remover tag.', classes: 'rounded red' });
            }
        },
        error: function(err) {
            console.error('[AJAX Tag] Erro ao remover', err);
            M.toast({ html: 'Erro de conexão ao remover tag.', classes: 'rounded red' });
        }
    });
}

$(document).on('mouseenter', '.tag-badge', function() {
    $(this).find('.tag-remove-btn').css('display', 'inline');
});
$(document).on('mouseleave', '.tag-badge', function() {
    $(this).find('.tag-remove-btn').css('display', 'none');
});
$(document).on('click', '.tag-remove-btn', function(e) {
    e.stopPropagation();
    const $badge = $(this).closest('.tag-badge');
    const ocorrenciaId = $('#tags-container').data('ocorrencia-id');
    removerTag($badge.data('tag-id'), ocorrenciaId);
});
$(document).on('contextmenu', '.tag-badge', function(e) {
    e.preventDefault();
    const $badge = $(this);
    const ocorrenciaId = $('#tags-container').data('ocorrencia-id');
    removerTag($badge.data('tag-id'), ocorrenciaId);
});
</script>

<style>
.btn-resp-icon {
    padding: 0 6px !important;
    height: 28px !important;
    line-height: 28px !important;
    border-radius: 4px !important;
    border: 1px solid #ced4da !important;
    background: #fff !important;
    color: #6c757d !important;
    transition: all 0.2s ease !important;
}
.btn-resp-icon:hover {
    background: #e9ecef !important;
}
.btn-resp-icon.active {
    background: #6c757d !important;
    color: #fff !important;
    border-color: #6c757d !important;
}
.btn-resp-icon.active-sindico {
    background: #dc3545 !important;
    color: #fff !important;
    border-color: #dc3545 !important;
}
.btn-resp-icon.active-sub {
    background: #6f42c1 !important;
    color: #fff !important;
    border-color: #6f42c1 !important;
}
.spin-icon {
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
</style>
