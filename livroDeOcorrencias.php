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

// Montar Query de Ocorrências
$link = DBConnect();
$sqlWhere = " WHERE 1=1";
$params = [];
$types = "";

if ($blocoFiltro) { $sqlWhere .= " AND bloco = ?"; $params[] = $blocoFiltro; $types .= "s"; }
if ($unidadeFiltro) { $sqlWhere .= " AND unidade = ?"; $params[] = $unidadeFiltro; $types .= "s"; }
if ($protoFiltro) { $sqlWhere .= " AND (protocolo_vds = ? OR id = ?)"; $params[] = $protoFiltro; $params[] = (int)$protoFiltro; $types .= "si"; }
if ($respFiltro) { $sqlWhere .= " AND responsabilidade = ?"; $params[] = $respFiltro; $types .= "s"; }

$sqlList = "SELECT * FROM ocorrencias {$sqlWhere} ORDER BY abertura DESC LIMIT 50";
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
$selId = $_GET['id'] ?? ($ocorrencias[0]['id'] ?? null);
$detalheSel = $selId ? vds_get_ocorrencia_detalhe($selId, $usuarioIdConselho) : null;

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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Livro de Ocorrências - Conselho Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .sidebar-feed { background: #ffffff; border-right: 1px solid #e0e0e0; height: calc(100vh - 120px); overflow-y: auto; }
        .chat-container { background: #efeae2; height: calc(100vh - 120px); display: flex; flex-direction: column; }
        .chat-header { background: #ffffff; padding: 15px 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
        .chat-body { flex: 1; overflow-y: auto; padding: 20px; }
        .chat-footer { background: #ffffff; padding: 15px; border-top: 1px solid #e0e0e0; }

        /* Estilo Balões Chat WhatsApp */
        .msg-bubble { max-width: 70%; margin-bottom: 15px; padding: 12px 16px; border-radius: 12px; font-size: 0.95rem; line-height: 1.4; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .msg-left { background: #ffffff; border-top-left-radius: 2px; align-self: flex-start; margin-right: auto; }
        .msg-internal { background: #fff3cd; border: 1px solid #ffebaba; border-top-right-radius: 2px; margin-left: auto; color: #856404; }
        .msg-right { background: #dcf8c6; border-top-right-radius: 2px; margin-left: auto; color: #111; }
        .msg-author { font-weight: bold; font-size: 0.85rem; margin-bottom: 4px; display: flex; justify-content: space-between; gap: 10px; }
        .msg-time { font-size: 0.75rem; color: #888; text-align: right; margin-top: 6px; }

        .item-oco { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s; }
        .item-oco:hover, .item-oco.active { background: #e8f0fe; }
        .badge-tipo { font-size: 0.75rem; padding: 3px 8px; border-radius: 12px; font-weight: 500; display: inline-block; }
    </style>
</head>
<body>

<div style="padding: 10px 20px; background: #ffffff; border-bottom: 1px solid #e0e0e0;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h5 style="margin: 0; font-weight: 600; color: #333;">
            <i class="material-icons left" style="color:#0d6efd;">book</i> Livro de Ocorrências (Condomínio Digital)
        </h5>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="sync_agora">
                <button type="submit" class="btn waves-effect waves-light blue btn-small">
                    <i class="material-icons left">sync</i> Sincronizar Agora
                </button>
            </form>
            <a href="forms/configVds.php" class="btn-flat btn-small purple-text">
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

    <?php if ($msg): ?>
        <div style="margin-top: 10px; padding: 10px 15px; background-color: <?= $msgType === 'success' ? '#d4edda' : ($msgType === 'warning' ? '#fff3cd' : '#f8d7da') ?>; color: <?= $msgType === 'success' ? '#155724' : ($msgType === 'warning' ? '#856404' : '#721c24') ?>; border-radius: 4px;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
</div>

<div class="row" style="margin: 0;">
    <!-- Sidebar Left: Feed de Ocorrências -->
    <div class="col s12 m4 l3 sidebar-feed">
        <!-- Filtros Rápidos -->
        <form method="GET" style="padding: 10px 0;">
            <div class="row" style="margin-bottom: 0;">
                <div class="input-field col s6" style="margin:0;">
                    <input type="text" name="bloco" placeholder="Bloco" value="<?= htmlspecialchars($blocoFiltro) ?>">
                </div>
                <div class="input-field col s6" style="margin:0;">
                    <input type="text" name="unidade" placeholder="Unidade" value="<?= htmlspecialchars($unidadeFiltro) ?>">
                </div>
            </div>
            <button type="submit" class="btn-small waves-effect waves-light grey darken-2 width-100%" style="width:100%; margin-top:5px;">
                Filtrar
            </button>
        </form>

        <?php if (empty($ocorrencias)): ?>
            <div style="padding: 20px; text-align: center; color: #999;">Nenhuma ocorrência encontrada. Clique em 'Sincronizar Agora'.</div>
        <?php else: ?>
            <?php foreach ($ocorrencias as $oco): ?>
                <?php
                $tipoId = (int)($oco['oco_tipo'] ?? 115);
                $infoTipo = $mapaCoresTipo[$tipoId] ?? ['nome' => 'Ocorrência', 'bg' => '#6c757d', 'color' => '#fff'];
                $isSel = ($selId == $oco['id']);
                ?>
                <div class="item-oco <?= $isSel ? 'active' : '' ?>" onclick="window.location.href='livroDeOcorrencias.php?id=<?= $oco['id'] ?>'">
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
                        <span>Responsável: <strong><?= strtoupper($oco['responsabilidade'] ?? 'Pendente') ?></strong></span>
                        <span style="color: <?= $oco['resolvido'] ? '#28a745' : '#dc3545' ?>;">
                            <?= $oco['resolvido'] ? '✓ Resolvido' : '• Em Aberto' ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Main Chat & Details -->
    <div class="col s12 m8 l9 chat-container" style="padding:0;">
        <?php if (!$detalheSel): ?>
            <div style="padding: 40px; text-align: center; color: #888;">Selecione uma ocorrência na lista à esquerda para visualizar as mensagens.</div>
        <?php else: ?>
            <?php
            $local = $detalheSel['local'];
            $notas = $detalheSel['notasInternas'];
            $tags = $detalheSel['tagsUnidades'];
            $remote = $detalheSel['remoteData'];
            $tipoId = (int)($local['oco_tipo'] ?? 115);
            $infoTipo = $mapaCoresTipo[$tipoId] ?? ['nome' => 'Ocorrência', 'bg' => '#6c757d', 'color' => '#fff'];
            ?>

            <!-- Header do Chat -->
            <div class="chat-header">
                <div>
                    <span class="badge-tipo" style="background-color: <?= $infoTipo['bg'] ?>; color: <?= $infoTipo['color'] ?>; margin-bottom:5px;">
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

                <!-- Alterar Responsabilidade e Status -->
                <form method="POST" style="display:flex; gap:10px; align-items:center;">
                    <input type="hidden" name="action" value="atualizar_responsabilidade">
                    <input type="hidden" name="ocorrencia_id" value="<?= $local['id'] ?>">

                    <select name="responsabilidade" class="browser-default" style="height:32px; padding:2px 5px; font-size:0.85rem;">
                        <option value="" <?= empty($local['responsabilidade']) ? 'selected' : '' ?>>Responsável...</option>
                        <option value="sindico" <?= $local['responsabilidade'] === 'sindico' ? 'selected' : '' ?>>Síndico</option>
                        <option value="sub" <?= $local['responsabilidade'] === 'sub' ? 'selected' : '' ?>>Subsíndico</option>
                        <option value="adm" <?= $local['responsabilidade'] === 'adm' ? 'selected' : '' ?>>ADM</option>
                    </select>

                    <label style="margin:0;">
                        <input type="checkbox" name="resolvido" value="1" <?= $local['resolvido'] ? 'checked' : '' ?> />
                        <span style="font-size:0.85rem; color:#333;">Resolvido</span>
                    </label>

                    <button type="submit" class="btn-small btn-flat blue-text">Salvar</button>
                </form>
            </div>

            <!-- Tags de Unidades Vinculadas -->
            <div style="background:#fff; padding:6px 20px; border-bottom:1px solid #e0e0e0; font-size:0.85rem; display:flex; justify-content:space-between; align-items:center;">
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
                <?php if ($remote && isset($remote['eventos'])): ?>
                    <?php foreach ($remote['eventos'] as $ev): ?>
                        <?php
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

                            <div class="msg-time"><?= htmlspecialchars($dthoraStr) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Notas Internas do Conselho -->
                <?php foreach ($notas as $n): ?>
                    <div class="msg-bubble <?= $n['enviado_remoto'] ? 'msg-right' : 'msg-internal' ?>">
                        <div class="msg-author">
                            <span>
                                <i class="material-icons tiny"><?= $n['enviado_remoto'] ? 'cloud_done' : 'lock_outline' ?></i> 
                                <?= htmlspecialchars($n['conselheiro_nome']) ?> 
                                <small>(<?= $n['enviado_remoto'] ? 'Publicado no Remoto' : 'Nota Interna do Conselho' ?>)</small>
                            </span>
                        </div>
                        <div><?= nl2br(htmlspecialchars($n['texto'])) ?></div>
                        
                        <div class="msg-time" style="display:flex; justify-content:space-between; align-items:center;">
                            <span><?= htmlspecialchars($n['created_at']) ?></span>
                            
                            <?php if (!$n['enviado_remoto']): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="publicar_remoto">
                                    <input type="hidden" name="nota_id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn-small orange white-text" style="height:24px; line-height:24px; padding:0 8px; font-size:0.75rem; border-radius:3px;">
                                        Publicar no Remoto (VDS) <i class="material-icons right tiny">send</i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="green-text" style="font-weight:600;">✓ Enviado à VDS</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer: Adicionar Nota Interna (1º Fator) -->
            <div class="chat-footer">
                <form method="POST">
                    <input type="hidden" name="action" value="adicionar_nota_interna">
                    <input type="hidden" name="ocorrencia_id" value="<?= $local['id'] ?>">
                    
                    <div style="display:flex; gap:10px; align-items:center;">
                        <textarea name="texto" placeholder="Digite uma Nota Interna do Conselho... (Por padrão fica salva apenas internamente)" required style="flex:1; border:1px solid #ccc; border-radius:6px; padding:8px; height:50px; resize:none; font-family:inherit;"></textarea>
                        <button type="submit" class="btn waves-effect waves-light amber darken-3" style="height:50px;">
                            Salvar Nota Interna <i class="material-icons right">note_add</i>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
