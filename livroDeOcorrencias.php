<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/classes/database.php";
require_once __DIR__ . "/classes/vds_auth_service.php";
require_once __DIR__ . "/classes/vds_ocorrencia_service.php";

$usuarioIdConselho = $_SESSION['usuario_id'] ?? 1;
$toastAlert = vds_get_toast_alerts($usuarioIdConselho);

// Processar Ações POST (Sync, Nova Nota Interna, Publicar no Remoto, Atualizar Responsabilidade/Status, Vincular Tag/Recurso)
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
        $conselheiroNome = $_SESSION['usuario_nome'] ?? 'Conselheiro';
        
        if (!empty($texto)) {
            $resNota = vds_adicionar_nota_interna($ocorrenciaId, $usuarioIdConselho, $conselheiroNome, $texto);
            if ($resNota['success']) {
                $msg = "Nota interna adicionada com sucesso no Conselho (salva localmente por padrão).";
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
        $resolvido = isset($_POST['resolvido']) ? 1 : 0;
        
        $link = DBConnect();
        $stmt = mysqli_prepare($link, "UPDATE ocorrencias SET responsabilidade = ?, resolvido = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $resp, $resolvido, $ocorrenciaId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        DBClose($link);
        $msg = "Responsabilidade e status atualizados com sucesso!";
        $msgType = "success";
    } elseif ($action === 'vincular_tag') {
        $ocorrenciaId = (int)$_POST['ocorrencia_id'];
        $bloco = trim($_POST['bloco']);
        $unidade = trim($_POST['unidade']);
        $tipoVinculo = $_POST['tipo_vinculo'] ?? 'citada';
        vds_vincular_unidade_tag($ocorrenciaId, $bloco, $unidade, $tipoVinculo);
        $msg = "Tag da Unidade {$bloco}-{$unidade} vinculada com sucesso!";
        $msgType = "success";
    }
}

// Filtros de busca
$blocoFiltro = $_GET['bloco'] ?? '';
$unidadeFiltro = $_GET['unidade'] ?? '';
$protoFiltro = $_GET['protocolo'] ?? '';
$respFiltro = $_GET['responsabilidade'] ?? '';

// Montar Query de Ocorrências (Listar até 1000 da base legada / sincronizada)
$link = DBConnect();
$sqlWhere = " WHERE 1=1";
$params = [];
$types = "";

if ($blocoFiltro) { $sqlWhere .= " AND bloco = ?"; $params[] = $blocoFiltro; $types .= "s"; }
if ($unidadeFiltro) { $sqlWhere .= " AND unidade = ?"; $params[] = $unidadeFiltro; $types .= "s"; }
if ($protoFiltro) { $sqlWhere .= " AND (protocolo_vds = ? OR id = ?)"; $params[] = $protoFiltro; $params[] = (int)$protoFiltro; $types .= "si"; }
if ($respFiltro) { $sqlWhere .= " AND responsabilidade = ?"; $params[] = $respFiltro; $types .= "s"; }
// Ocultar chamados resolvidos por padrão
$sqlWhere .= " AND (resolvido IS NULL OR resolvido = 0)";

$sqlList = "SELECT * FROM ocorrencias {$sqlWhere} ORDER BY abertura DESC LIMIT 1000";
$stmtList = mysqli_prepare($link, $sqlList);
if ($types) {
    mysqli_stmt_bind_param($stmtList, $types, ...$params);
}
mysqli_stmt_execute($stmtList);
$resList = mysqli_stmt_get_result($stmtList);

$ocorrencias = [];
while ($row = mysqli_fetch_assoc($resList)) {
    $ocorrencias[] = $row;
}
mysqli_stmt_close($stmtList);
DBClose($link);

// Ocorrência selecionada para visualização
$selId = $_GET['id'] ?? null;

// Se um protocolo foi digitado no filtro ou passado via URL (?protocolo=259564), resolve e seleciona diretamente o chamado
$detalheSel = null;
if (!empty($protoFiltro)) {
    $detalhePorProto = vds_get_ocorrencia_detalhe($protoFiltro, $usuarioIdConselho);
    $detalheSel = $detalhePorProto;
    if ($detalhePorProto && isset($detalhePorProto['local']['id'])) {
        $selId = $detalhePorProto['local']['id'];

        // Adiciona ao topo da lista $ocorrencias se ainda não estiver nela
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
    .chat-header { background: #ffffff; padding: 15px 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
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
</style>

<div style="padding: 10px 20px; background: #ffffff; border-bottom: 1px solid #e0e0e0;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h5 style="margin: 0; font-weight: 600; color: #333; font-size: 1.3rem;">
            <i class="material-icons left" style="color:#0d6efd;">book</i> Livro de Ocorrências (Condomínio Digital)
        </h5>

        <!-- Form de Busca Direta por Protocolo -->
        <form method="GET" action="index.php" style="display:flex; gap:6px; align-items:center; margin:0;">
            <input type="hidden" name="pag" value="livroDeOcorrencias">
            <input type="text" name="protocolo" placeholder="Nº do Protocolo (ex: 259564)" value="<?= htmlspecialchars($protoFiltro) ?>" style="height:32px; font-size:0.85rem; width:200px; margin:0; padding:0 8px; border:1px solid #ccc; border-radius:4px; background:#fff;">
            <button type="submit" class="btn-small waves-effect waves-light blue darken-2" style="height:32px; line-height:32px; padding:0 10px;">
                <i class="material-icons left tiny">search</i> Ir p/ Protocolo
            </button>
        </form>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="sync_agora">
                <button type="submit" class="btn waves-effect waves-light blue btn-small">
                    <i class="material-icons left">sync</i> Sincronizar Agora
                </button>
            </form>
            <a href="index.php?pag=configVds" class="btn-flat btn-small purple-text">
                <i class="material-icons left">settings</i> Configurações
            </a>
        </div>
    </div>

    <!-- Alertas Toast / Banner -->
    <?php if ($toastAlert): ?>
        <div style="margin-top: 10px; padding: 10px 15px; background-color: <?= $toastAlert['type'] === 'danger' ? '#f8d7da' : '#fff3cd' ?>; color: <?= $toastAlert['type'] === 'danger' ? '#721c24' : '#856404' ?>; border-radius: 4px; font-size: 0.9rem;">
            <i class="material-icons tiny">warning</i> <?= htmlspecialchars($toastAlert['message']) ?>
        </div>
    <?php endif; ?>

    <?php
    $tokenCheck = vds_get_token($usuarioIdConselho);
    ?>
    <?php if (!$tokenCheck): ?>
        <div style="margin-top: 10px; padding: 12px 15px; background: #fff3cd; border-left: 5px solid #ffc107; border-radius: 4px; color: #856404; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <strong style="display:flex; align-items:center; gap:6px;">
                    <i class="material-icons tiny">vpn_key</i> Autenticação com a VDS Pendente
                </strong>
                Nenhum token de acesso ativo foi encontrado na tabela <code>vds_tokens</code>. Faça login uma vez para autorizar a sincronização.
            </div>
            <a href="index.php?pag=configVds" class="btn btn-small purple white-text">
                <i class="material-icons left tiny">settings</i> Ir para Configurações VDS
            </a>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div style="margin-top: 10px; padding: 10px 15px; background-color: <?= $msgType === 'success' ? '#d4edda' : ($msgType === 'warning' ? '#fff3cd' : '#f8d7da') ?>; color: <?= $msgType === 'success' ? '#155724' : ($msgType === 'warning' ? '#856404' : '#721c24') ?>; border-radius: 4px;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
</div>

<div class="row" style="margin: 0;">
    <!-- Sidebar Left: Feed de Ocorrências (Mostra base legada e sincronizada) -->
    <div class="col s12 m4 l3 sidebar-feed">
        <!-- Filtros Rápidos -->
        <form method="GET" action="index.php" style="padding: 10px 0;">
            <input type="hidden" name="pag" value="livroDeOcorrencias">
            <div style="margin-bottom: 8px;">
                <input type="text" name="protocolo" placeholder="Ir para Nº Protocolo (ex: 259564)" value="<?= htmlspecialchars($protoFiltro) ?>" style="margin:0; height:32px; font-size:0.85rem; padding:0 8px; border:1px solid #ccc; border-radius:4px; background:#fff;">
            </div>
            <div class="row" style="margin-bottom: 0;">
                <div class="input-field col s6" style="margin:0;">
                    <input type="text" name="bloco" placeholder="Bloco" value="<?= htmlspecialchars($blocoFiltro) ?>">
                </div>
                <div class="input-field col s6" style="margin:0;">
                    <input type="text" name="unidade" placeholder="Unidade" value="<?= htmlspecialchars($unidadeFiltro) ?>">
                </div>
            </div>
            <button type="submit" class="btn-small waves-effect waves-light grey darken-2" style="width:100%; margin-top:5px;">
                Filtrar / Ir p/ Protocolo (<?= count($ocorrencias) ?>)
            </button>
        </form>

        <?php if (empty($ocorrencias)): ?>
            <div style="padding: 20px; text-align: center; color: #999;">Nenhuma ocorrência encontrada no banco de dados.</div>
        <?php else: ?>
            <?php foreach ($ocorrencias as $oco): ?>
                <?php
                $tipoId = (int)($oco['oco_tipo'] ?? 115);
                $infoTipo = $mapaCoresTipo[$tipoId] ?? ['nome' => 'Ocorrência', 'bg' => '#6c757d', 'color' => '#fff'];
                $isSel = ($selId == $oco['id']);
                ?>
                <div class="item-oco <?= $isSel ? 'active' : '' ?>" onclick="window.location.href='index.php?pag=livroDeOcorrencias&id=<?= $oco['id'] ?>'">
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
                        <span>Resp: <strong><?= strtoupper($oco['responsabilidade'] ?? 'Pendente') ?></strong></span>
                        <span style="color: <?= $oco['resolvido'] ? '#28a745' : '#dc3545' ?>;">
                            <?= $oco['resolvido'] ? '✓ Resolvido' : '• Aberto' ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Main Chat & Details -->
    <div class="col s12 m8 l9 chat-container" style="padding:0;">
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
            ?>

            <!-- Header do Chat (com Botão Voltar para Mobile) -->
            <div class="chat-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="index.php?pag=livroDeOcorrencias" class="btn-flat btn-small hide-on-large-only" style="padding:0 8px;">
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

                <!-- Alterar Responsabilidade e Status -->
                <form method="POST" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="action" value="atualizar_responsabilidade">
                    <input type="hidden" name="ocorrencia_id" value="<?= $local['id'] ?>">

                    <select name="responsabilidade" class="browser-default" style="height:32px; padding:2px 5px; font-size:0.85rem; width:auto;">
                        <option value="" <?= empty($local['responsabilidade']) ? 'selected' : '' ?>>Responsável...</option>
                        <option value="sindico" <?= $local['responsabilidade'] === 'sindico' ? 'selected' : '' ?>>Síndico</option>
                        <option value="sub" <?= $local['responsabilidade'] === 'sub' ? 'selected' : '' ?>>Subsíndico</option>
                        <option value="adm" <?= $local['responsabilidade'] === 'adm' ? 'selected' : '' ?>>ADM</option>
                    </select>

                    <label style="margin:0;">
                        <input type="checkbox" name="resolvido" value="1" <?= $local['resolvido'] ? 'checked' : '' ?> />
                        <span style="font-size:0.85rem; color:#333;">Resolvido</span>
                    </label>

                    <button type="submit" class="btn-small btn-flat blue-text" style="padding:0 5px;">Salvar</button>
                </form>
            </div>

            <!-- Tags de Unidades Vinculadas -->
            <div style="background:#fff; padding:6px 20px; border-bottom:1px solid #e0e0e0; font-size:0.85rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <div>
                    <strong>Unidades Vinculadas (Tags):</strong>
                    <?php if (empty($tags)): ?>
                        <span style="color:#999; margin-left:5px;">Nenhuma tag vinculada</span>
                    <?php else: ?>
                        <?php foreach ($tags as $t): ?>
                            <span class="badge blue lighten-4 blue-text" style="float:none; padding:2px 6px; margin-left:4px;">
                                <?= htmlspecialchars($t['bloco']) ?>-<?= htmlspecialchars($t['unidade']) ?> (<?= htmlspecialchars($t['tipo_vinculo']) ?>)
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" style="display:flex; gap:5px;">
                    <input type="hidden" name="action" value="vincular_tag">
                    <input type="hidden" name="ocorrencia_id" value="<?= $local['id'] ?>">
                    <input type="text" name="bloco" placeholder="Bloco" required style="width:50px; height:24px; margin:0; font-size:0.75rem;">
                    <input type="text" name="unidade" placeholder="Apt" required style="width:50px; height:24px; margin:0; font-size:0.75rem;">
                    <select name="tipo_vinculo" class="browser-default" style="height:24px; font-size:0.75rem; padding:0 2px;">
                        <option value="citada">Citada</option>
                        <option value="reclamada">Reclamada</option>
                    </select>
                    <button type="submit" class="btn-small btn-flat green-text" style="height:24px; line-height:24px; padding:0 5px;">+ Tag</button>
                </form>
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

                // Mapear IDs de eventos VDS publicados pelo Conselho => nome do conselheiro
                $publishedEventMap = [];
                foreach ($notas as $n) {
                    if (!empty($n['vds_evento_uuid'])) {
                        $publishedEventMap[(string)$n['vds_evento_uuid']] = $n['conselheiro_nome'] ?? 'Conselheiro';
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
                                    <span style="background:#e8f5e9; color:#2e7d32; padding:2px 6px; border-radius:3px; font-size:0.7rem; font-weight:600;">
                                        <i class="material-icons tiny" style="vertical-align:middle;">person</i>
                                        Publicado por <?= htmlspecialchars($conselheiroAutor) ?> (Conselho)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Notas Internas do Conselho (somente as NÃO publicadas no remoto) -->
                <?php foreach ($notas as $n): ?>
                    <?php if ($n['enviado_remoto']) continue; ?>
                    <div class="msg-bubble msg-internal">
                        <div class="msg-author">
                            <span>
                                <i class="material-icons tiny">lock_outline</i> 
                                <?= htmlspecialchars($n['conselheiro_nome']) ?> 
                                <small>(Nota Interna do Conselho)</small>
                            </span>
                        </div>
                        <div><?= nl2br(htmlspecialchars($n['texto'])) ?></div>
                        
                        <div class="msg-time" style="display:flex; justify-content:space-between; align-items:center;">
                            <span><?= htmlspecialchars($n['created_at']) ?></span>
                            
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="publicar_remoto">
                                <input type="hidden" name="nota_id" value="<?= $n['id'] ?>">
                                <button type="submit" class="btn-small orange white-text" style="height:24px; line-height:24px; padding:0 8px; font-size:0.75rem; border-radius:3px;">
                                    Publicar no Remoto (VDS) <i class="material-icons right tiny">send</i>
                                </button>
                            </form>
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

<!-- Console de Diagnóstico & Debug VDS (Visível sempre com debug=1 ou se não carregou ocorrência/eventos) -->
<?php if (!empty($_GET['debug']) || !$detalheSel || empty($eventosRemotos)): ?>
    <div style="background:#1e1e1e; color:#00ff66; padding:15px; border-radius:6px; font-family:monospace; font-size:0.8rem; margin:15px; overflow-x:auto;">
        <strong style="color:#fff; font-size:0.9rem;"><i class="material-icons tiny">bug_report</i> Console de Diagnóstico & Debug VDS</strong>
        <hr style="border-color:#444; margin:8px 0;">
        
        <?php $dbg = $detalheSel['debug'] ?? []; ?>
        <div><strong>Input Pesquisado:</strong> <?= htmlspecialchars($protoFiltro ?: ($selId ?: 'Nenhum')) ?></div>
        <div><strong>Token Encontrado:</strong> <?= ($dbg['token_found'] ?? false) ? 'SIM (Ativo)' : '<span style="color:#ff4444;">NÃO (Ausente no vds_tokens)</span>' ?></div>
        <div><strong>Registro Local Encontrado:</strong> <?= ($dbg['local_found'] ?? false) ? 'SIM (ID: ' . ($dbg['local_record']['id'] ?? '') . ', UUID: ' . ($dbg['local_record']['uuid_remoto'] ?? 'Vazio') . ', Prot: ' . ($dbg['local_record']['protocolo_vds'] ?? 'Vazio') . ')' : 'NÃO (Registro Inexistente)' ?></div>
        
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
