<?php

require "classes/repositorio.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados

$sql = "SELECT r.*, f.texto as fasee FROM recurso r
		left join fase f on f.id = r.fase 
		where r.numero = '{$_GET['rec']}'";

$result = DBExecute($sql);

$result = mysqli_fetch_assoc($result);
$esseRecurso = $result['id'];

// dump($result);

$mensagens = getMensagens($esseRecurso);
$diligencias = getDiligencias($esseRecurso);
$votos = getVotos($esseRecurso);
$anexos = getAnexos($_GET['rec']);

//Verifica se Recurso está no Prazo
$dataRetirada = getDatasDeRetiradaByID($_GET['rec']);
if (isset($dataRetirada[0]["dia_retirada"])) {
    $retirada = strtotime($dataRetirada[0]["dia_retirada"]);
    $diaRetirada = date('d/m/Y', strtotime($dataRetirada[0]["dia_retirada"]));
    $dataRecurso = strtotime($result["data"]);
    $delayRecurso = $dataRecurso - $retirada;

    $delayEmDias = $delayRecurso / 86400;
    // dump(date('Y-m-d H:i:s', $retirada));  // Mostrar a data de retirada
    // dump(date('Y-m-d H:i:s', $dataRecurso));  // Mostrar a data do recurso
    // dump($delayEmDias);
    if ($delayEmDias < 7) {
        $pontoDeAtencao = "green";
    } else {
        $pontoDeAtencao = "red";
    }
} else {
    $delayEmDias = "Indisponivel";
    $pontoDeAtencao = "";
    $diaRetirada = "Indisponível";
}
// $dataRetirada =  ? $dataRetirada[0]["dia_retirada"] : null;




$parecer = getParecer($result['numero']);

if (isset($result['unidade']) && isset($result['bloco'])) {
    $historico = getNotificacoes($result['unidade'], $result['bloco']);

}

if ($esseRecurso == null) {
    echo "<div class='container'>
		<center>
				<h3>Não há recurso cadastrado pra essa notificação</h3>
				<a class='btn' href='javascript:void(0);' onclick='goBack();'>voltar<a>
				    <script>
						function goBack() {
							window.history.back();
						}
					</script>
		</center>
	</div>";
    exit;
}

?>

<body>
    <!-- Cabeçalho -->
    <?php
    ?>
    <!-- Corpo da página -->
    <?php
    echo '<div class="container" style="margin-top: 20px;">
    <div class="row">
    <div class="col s12">
        <div class="card">';
    echo '
<nav class="header-navbar orange darken-2">
    <div class="nav-wrapper">
        <ul class="left">
            <li><a> <span id="idRecurso" idRec="' . $esseRecurso . '">' . $result['numero'] . '</span></a></li>
            <li><a> <span id="unidadeRecurso">' . $result['unidade'] . $result['bloco'] . '</span></a></li>
            <li><a> <span id="historico">' . sizeof($historico) . '</span></a></li>
            <li><a> <span id="fase">' . $result['fasee'] . '</span></a></li>
            
        </ul>
        <ul class="right">
				<a class="editarRecurso" href="index.php?pag=editarRecurso&rec=' . $esseRecurso . '"><i class="material-icons">edit</i></a>
        </ul>
    </div>
</nav>

	';

    echo '      <div class="card-content">
                <h6 class="">' . $result['titulo'] . '</h6>
                <div class="' . $pontoDeAtencao . '">
                    <p>Dias transcorridos entre a data de retirada e apresentação do Recurso: ' . $delayEmDias . '</p>
                    <p>Retirado dia: ' . $diaRetirada . '</p>
                    <p>Recurso apresentado dia: ' . date('d/m/Y', strtotime($result["data"])) . '</p>
                    <p>Obs: ' . $dataRetirada[0]["obs"] . '</p>
                </div>
				<h6 class=""><b>Fato Ocorrido</b></h6>
                <div class="grey">' . $result['fato'] . '</div>
                <h6 class=""><b>Argumentação</b></h6>
                
            ';
    echo '<pre>' . $result['detalhes'] . '</pre>';

    echo '<h6><b>Anexos do Condômino (Enviados via Portal)</b></h6>';
    if (!empty($anexos)) {
        echo '<div class="collection">';
        foreach ($anexos as $anx) {
            $ext = strtolower(pathinfo($anx['nome_arquivo'], PATHINFO_EXTENSION));
            $urlDownload = 'portal/api.php?action=get_anexo&id=' . $anx['id'];
            $urlView = $urlDownload . '&view=1';

            echo '<div class="collection-item">';

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                echo '<div style="margin-bottom: 10px;"><img src="' . $urlView . '" class="responsive-img materialboxed z-depth-1" style="max-height: 400px; border-radius: 8px; border: 1px solid #ccc; cursor: pointer;" alt="' . htmlspecialchars($anx['nome_arquivo']) . '"></div>';
                echo '<a href="' . $urlDownload . '" target="_blank" class="btn-flat grey lighten-4" style="height:auto;line-height:30px;"><i class="material-icons left">image</i> ' . htmlspecialchars($anx['nome_arquivo']) . '</a>';
            } elseif (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'])) {
                echo '<div style="margin-bottom: 10px;"><video controls style="max-width: 100%; max-height: 400px; border-radius: 4px; border: 1px solid #ccc; background: #000;"><source src="' . $urlView . '" type="video/' . ($ext == 'mov' ? 'mp4' : $ext) . '">Seu navegador não suporta vídeos.</video></div>';
                echo '<a href="' . $urlDownload . '" target="_blank" class="btn-flat grey lighten-4" style="height:auto;line-height:30px;"><i class="material-icons left">videocam</i> Baixar Vídeo Original (' . htmlspecialchars($anx['nome_arquivo']) . ')</a>';
            } elseif (in_array($ext, ['mp3', 'wav', 'aac'])) {
                echo '<div style="margin-bottom: 10px;"><audio controls style="width: 100%;"><source src="' . $urlView . '" type="audio/' . ($ext == 'mp3' ? 'mpeg' : $ext) . '">Seu navegador não suporta áudio.</audio></div>';
                echo '<a href="' . $urlDownload . '" target="_blank" class="btn-flat grey lighten-4" style="height:auto;line-height:30px;"><i class="material-icons left">audiotrack</i> ' . htmlspecialchars($anx['nome_arquivo']) . '</a>';
            } elseif ($ext === 'pdf') {
                echo '<a href="' . $urlView . '" target="_blank" class="btn-flat grey lighten-4" style="height:auto;line-height:30px; display:block;"><i class="material-icons left text-red">picture_as_pdf</i> Abrir/Baixar ' . htmlspecialchars($anx['nome_arquivo']) . '</a>';
            } else {
                echo '<a href="' . $urlDownload . '" target="_blank" class="btn-flat grey lighten-4" style="height:auto;line-height:30px; display:block;"><i class="material-icons left">attach_file</i> Baixar ' . htmlspecialchars($anx['nome_arquivo']) . '</a>';
            }

            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="grey-text">Nenhum anexo extra fornecido.</p>';
    }

    if ($parecer['concluido'] == 1) {
        $link = "https://mail.google.com/mail/#inbox/" . $parecer['mailId'];
        echo "<a class='btn' href='{$link}'>Email de Entrega do Parecer (abrir como conselho)</a>";
        echo '<a class="btn yellow darken-3" href="index.php?pag=emiteParecer&rec=' . $result['numero'] . '">Parecer</a>';
    }


    echo '<div class="row">
    <div class="">
        <ul class="collection with-header">
            ';
    foreach ($votos as $voto) {
        echo '<li class="collection-item avatar">
				<img src="' . $voto['avatar'] . '" alt="" class="circle">
				' . $voto['voto'] . '
				</li>';
    }
    echo '</ul>
    </div>
</div>';

    echo "<h6><b>Diligências (Visível ao Morador)</b></h6>";

    echo '<div class="row">
    <div class="">
        <ul class="collection with-header">
            ';
    foreach ($diligencias as $mensagem) {
        $dataFormatada = date('d/m/Y H:i:s', strtotime($mensagem['timestamp']));
        $textoFormatado = str_replace(["\r\n", "\r", "\n"], "<br>", htmlspecialchars($mensagem['texto']));
        $enviada = ($mensagem['enviada_ao_requerente'] == 1);
        $classeEnviada = $enviada ? "green lighten-5" : "";
        $iconeEnviada = $enviada ? "<i class='material-icons tiny blue-text' title='Enviada ao requerente (ID: {$mensagem['gmail_id']})'>email</i>" : "";
        
        $actions = "<span class='actions'>";
        if ($_SESSION["user_id"] == $mensagem["id_usuario"] && !$enviada) {
            $actions .= "<a class='editDiligence modal-trigger' href='#editaDiligencia' comment='{$mensagem['id']}'>$dataFormatada <i class='green-text text-darken-2 material-icons Tiny'>edit</i></a>";
        } else {
            $actions .= "$dataFormatada $iconeEnviada";
        }
        
        if (!$enviada) {
            $actions .= " <a class='notificarRequerente' comment='{$mensagem['id']}' style='cursor:pointer'><i class='material-icons tiny orange-text text-darken-3' title='Notificar Requerente via E-mail'>send</i></a>";
        }
        $actions .= "</span>";

        echo '<li class="collection-item avatar ' . $classeEnviada . '">
				<img src="' . $mensagem['avatar'] . '" alt="" class="circle">
				' . $actions . "<p>" . $textoFormatado . '</p>';
        
        $dilAnexos = getDiligenciaAnexos($mensagem['id']);
        if (!empty($dilAnexos)) {
            echo '<div style="margin-top:10px">';
            foreach ($dilAnexos as $da) {
                $ext = strtolower(pathinfo($da['caminho_arquivo'], PATHINFO_EXTENSION));
                $caminho = $da['caminho_arquivo'];
                $nome = $da['nome_arquivo'];
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    echo "<div style='margin-bottom:10px;'><img src='{$caminho}' class='responsive-img materialboxed z-depth-1' style='max-height:300px; border-radius:8px; cursor:pointer' alt='{$nome}'></div>";
                } else if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                    echo "<div style='margin-bottom:10px; max-width:400px'><video controls class='responsive-video' style='border-radius:8px;'><source src='{$caminho}' type='video/{$ext}'></video></div>";
                } else if (in_array($ext, ['mp3', 'wav'])) {
                    echo "<div style='margin-bottom:10px; max-width:400px'><audio controls style='width:100%'><source src='{$caminho}' type='audio/mpeg'></audio></div>";
                } else {
                    echo '<a href="' . $caminho . '" target="_blank" class="chip" style="height: 24px; line-height: 24px; margin-bottom:5px;"><i class="material-icons tiny left">insert_drive_file</i>' . $nome . '</a> ';
                }
            }
            echo '</div>';
        }
		echo '</li>';
    }
    echo '</ul>';


    echo "<h6><b>Comentários Internos (Privativo)</b></h6>";

    echo '<div class="row">
    <div class="">
        <ul class="collection with-header">
            ';
    foreach ($mensagens as $mensagem) {
        $dataFormatada = date('d/m/Y H:i:s', strtotime($mensagem['timestamp']));
        $textoFormatado = str_replace(["\r\n", "\r", "\n"], "<br>", htmlspecialchars($mensagem['texto']));
        if ($_SESSION["user_id"] == $mensagem["id_usuario"]) {
            $actions = "<span class='actions'><a class='editComment modal-trigger' href='#editaComentario' comment='{$mensagem['id']}'>$dataFormatada <i class='green-text text-darken-2 material-icons Tiny'>edit</i></a></span>";
        } else {
            $actions = "<span class='actions'>$dataFormatada</span>";
        }
        echo '<li class="collection-item avatar">
				<img src="' . $mensagem['avatar'] . '" alt="" class="circle">
				' . $actions . "<p>" . $textoFormatado . '</p>';
        
        if (!empty($mensagem['anexos'])) {
            echo '<div style="margin-top:10px">';
            foreach ($mensagem['anexos'] as $ma) {
                $ext = strtolower(pathinfo($ma['caminho_arquivo'], PATHINFO_EXTENSION));
                $caminho = $ma['caminho_arquivo'];
                $nome = $ma['nome_arquivo'];
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    echo "<div style='margin-bottom:10px;'><img src='{$caminho}' class='responsive-img materialboxed z-depth-1' style='max-height:300px; border-radius:8px; cursor:pointer' alt='{$nome}'></div>";
                } else if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                    echo "<div style='margin-bottom:10px; max-width:400px'><video controls class='responsive-video' style='border-radius:8px;'><source src='{$caminho}' type='video/{$ext}'></video></div>";
                } else if (in_array($ext, ['mp3', 'wav'])) {
                    echo "<div style='margin-bottom:10px; max-width:400px'><audio controls style='width:100%'><source src='{$caminho}' type='audio/mpeg'></audio></div>";
                } else {
                    echo '<a href="' . $caminho . '" target="_blank" class="chip" style="height: 24px; line-height: 24px; margin-bottom:5px;"><i class="material-icons tiny left">insert_drive_file</i>' . $nome . '</a> ';
                }
            }
            echo '</div>';
        }
        echo '</li>';
    }
    echo '</ul>';
    // dump($mensagens);
    
    $vaga = getEstacionamento($result['bloco'], $result['unidade']);

    foreach ($vaga as $vg) {
        echo "<div class='chip'>Vaga " . $vg['id_estacionamento'] . " " . $vg['local'] . " </div>";
    }


    // Seção de Ocorrências Vinculadas
    $ocorrenciasVinculadas = getOcorrenciasVinculadas($esseRecurso);
    echo "<h6><b>Ocorrências Condomínio Digital Vinculadas</b></h6>";
    echo '<div class="collection">';
    if (!empty($ocorrenciasVinculadas)) {
        foreach ($ocorrenciasVinculadas as $oc) {
            echo '<a href="' . $oc['url'] . '" target="_blank" class="collection-item">
                    <span class="new badge blue" data-badge-caption="">ID ' . $oc['id'] . '</span>
                    <b>' . $oc['bloco'] . ' / ' . $oc['unidade'] . '</b> - ' . date('d/m/Y H:i', strtotime($oc['abertura'])) . '
                    <span class="secondary-content"><i class="material-icons">open_in_new</i></span>
                  </a>';
        }
    } else {
        echo '<p class="grey-text p-10" style="padding:10px">Nenhuma ocorrência vinculada.</p>';
    }
    echo '</div>';


    echo "<h6><b>Histórico da unidade</b></h6>";

    echo "<div id=\"popup\" class=\"popup\">
                  <div id=\"popup-content\" class=\"popup-content\">
                    Popup
                  </div>
                </div>";
    echo '<div class="collection">';

    foreach ($historico as $h) {
        $votos = "";
        $rst = getVotos($h['numero_ano_virtual']);
        foreach ($rst as $v) {
            $votos .= "<span class='chip' style='padding: 0 8px; height: 24px; line-height: 24px; margin: 2px;'>" . $v['voto'] . "</span>";
        }
        $classe = $result['numero'] == $h['numero_ano_virtual'] ? "orange lighten-4" : "";
        echo '<a class="collection-item recurso black-text ' . $classe . '" rec="' . $h['numero_ano_virtual'] . '" 
          data-numero="' . $h['numero_ano_virtual'] . '" 
          data-data_email="' . $h['data_email'] . '" 
          data-data_envio="' . $h['data_envio'] . '" 
          data-status="' . $h['status'] . '" 
          data-cobranca="' . $h['cobranca'] . '" 
          data-tipo="' . $h['notificacao'] . '" 
          data-obs="' . $h['obs'] . '" 
          data-assunto="' . $h['assunto'] . '" 
          data-data-ocorrido="' . $h['data_ocorrido'] . '" style="cursor: pointer; display: block;">';

        echo "<div class='row' style='margin-bottom: 0px; display: flex; flex-wrap: wrap; align-items: center;'>";
        echo "<div class='col s12 m2'><h6><b class='blue-text text-darken-2'>" . $h['numero_ano_virtual'] . "</b></h6><span class='badge-mini grey white-text left' style='padding:2px 5px; border-radius:3px; font-size:0.75rem'>" . $h['notificacao'] . "</span></div>";
        echo "<div class='col s12 m6'><span style='font-weight: 500;'>" . $h['assunto'] . "</span><br><span class='grey-text' style='font-size: 0.8rem;'><i class='material-icons tiny'>event</i> Ocorreu em " . $h['data_ocorrido'] . "</span></div>";
        echo "<div class='col s12 m4 right-align' style='margin-top: 5px;'>" . $votos . "</div>";
        echo "</div>";

        echo '</a>';
    }
    echo '</div>';


    echo '    </div>
</div>';
    echo '      </div>
            <div class="card-action">
                <a class="modal-trigger btn blue" href="#novaMensagemModal">Comentar</a> 
                <a class="modal-trigger btn green darken-3" href="#alterarFaseModal">Fase</a> ';
    if ($result['fase'] != 5) {
        echo '<a class="modal-trigger btn orange darken-3" href="#votoModal">Votar</a> ';
    }
    echo '<a class="modal-trigger btn orange black-3" href="#addiligencia">Adicionar Diligencia</a> ';
    echo '<a class="modal-trigger btn indigo" href="#vincularOcorrenciaModal">Vincular Livro</a> ';

    if ($result['fase'] == 4)
        echo '<a class="btn yellow darken-3" href="index.php?pag=emiteParecer&rec=' . $result['numero'] . '">Parecer</a>';

    echo '
                <a class="modal-trigger btn right" href="index.php">Sair</a>
            </div>
        </div>
    </div>
    </div>
</div>';


    ?>

    <!-- Inclua os scripts do Materialize CSS e outros recursos -->
    <!-- Inclua seu código JavaScript para controlar os modais, eventos, etc. -->
</body>




<!-- Modal de Nova Mensagem -->
<div id="novaMensagemModal" class="modal">
    <div class="modal-content">
        <h4>Novo comentário</h4>
        <p>Formulário para inserir um novo comentário...</p>
        <form id="postMessageForm" enctype="multipart/form-data">
            <div class="input-field">
                <textarea id="messageText" class="materialize-textarea" name="messageText" required></textarea>
                <label for="messageText">Mensagem</label>
            </div>
            <div id="pastePreviewComment" class="row" style="margin-bottom:0"></div>
            <div class="file-field input-field">
                <div class="btn blue">
                    <span>+ Anexos</span>
                    <input type="file" name="anexos[]" multiple>
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Anexar arquivos ao comentário">
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a id="comentar" class="modal-close waves-effect waves-green btn-flat">Enviar</a>
    </div>
</div>
<!-- Modal de Diligencia -->
<div id="addiligencia" class="modal">
    <div class="modal-content">
        <h4>Nova Diligência</h4>
        <p>Relate como se deu a apuração do fato. Estas mensagens serão visíveis ao morador caso você as notifique.</p>
        <form id="postDiligenciaForm" enctype="multipart/form-data">
            <div class="input-field">
                <textarea id="diligenciaText" class="materialize-textarea" name="messageText" required></textarea>
                <label for="diligenciaText">Mensagem</label>
            </div>
            <div id="pastePreviewDiligence" class="row" style="margin-bottom:0"></div>
            <div class="file-field input-field">
                <div class="btn blue">
                    <span>Anexos</span>
                    <input type="file" name="anexos[]" multiple>
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Upload de um ou mais arquivos">
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a id="diligenciar" class="modal-close waves-effect waves-green btn-flat">Anotar</a>
    </div>
</div>

<div id="editaDiligencia" class="modal">
    <div class="modal-content">
        <h4>Editar Diligência</h4>
        <p>Edite a sua Diligência...</p>
        <form id="editDiligenciaForm" enctype="multipart/form-data">
            <input type="hidden" name="id_diligencia" id="editDiligenciaId">
            <div class="input-field">
                <textarea id="messageTextDiligencia" class="browser-default" name="messageText" placeholder="texto" required style="width:100%; min-height:100px; padding:10px"></textarea>
                <label for="messageTextDiligencia">Mensagem</label>
            </div>
            
            <div id="existingAttachmentsDiligence" class="row" style="margin-bottom: 0px;">
                <!-- Anexos aparecerão aqui via JS -->
            </div>
            <div class="file-field input-field">
                <div class="btn blue">
                    <span>+ Anexos</span>
                    <input type="file" name="anexos[]" multiple>
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Adicionar mais arquivos à diligência">
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a id="updateDiligence" class="modal-close waves-effect waves-green btn-flat">Salvar</a>
    </div>
</div>

<div id="vincularOcorrenciaModal" class="modal">
    <div class="modal-content">
        <h4>Vincular Livro de Ocorrência</h4>
        <p>Busque por ID ou por Unidade (Ex: A/101)</p>
        <div class="row">
            <div class="input-field col s9">
                <input type="text" id="buscaOcorrenciaInput" placeholder="ID ou Bloco/Unidade">
            </div>
            <div class="col s3">
                <button class="btn" id="btnBuscaOcorrencia" style="margin-top:15px">Buscar</button>
            </div>
        </div>
        <div id="resultadoBuscaOcorrencia" class="collection">
            <!-- Resultados aparecerão aqui -->
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
    </div>
</div>
<!-- Modal de Nova Mensagem -->
<div id="editaComentario" class="modal">
    <div class="modal-content">
        <h4>Editar comentário</h4>
        <p>Edite o seu Comentário...</p>
        <form id="editMessageForm" enctype="multipart/form-data">
            <input type="hidden" name="id_mensagem" id="editMessageId">
            <div class="input-field">
                <textarea id="messageTextComment" class="browser-default" name="messageText" placeholder="texto" required style="width:100%; min-height:100px; padding:10px"></textarea>
                <label for="messageText">Mensagem</label>
            </div>
            <div id="pastePreviewCommentEdit" class="row" style="margin-bottom:0"></div>
            <div id="existingAttachmentsComment" class="row" style="margin-bottom: 0px;">
                <!-- Anexos aparecerão aqui via JS -->
            </div>
            <div class="file-field input-field">
                <div class="btn blue">
                    <span>+ Anexos</span>
                    <input type="file" name="anexos[]" multiple>
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text" placeholder="Adicionar mais arquivos ao comentário">
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a id="updateComment" class="modal-close waves-effect waves-green btn-flat">Salvar</a>
    </div>
</div>

<div id="votoModal" class="modal">
    <div class="modal-content">
        <h4>Votar</h4>


        <label>Clique na opção desejada</label><br>
        <table>
            <tr>
                <td class="opVoto" voto="manter">
                    <div class="chip red darken-4 white-text">Manter</div>
                </td>
                <td class="opVoto" voto="revogar">
                    <div class="chip teal  white-text">Revogar</div>
                </td>
                <!-- <td class="opVoto" voto="converter">
            <div class="chip">Converter</div>
        </td> -->
            </tr>
        </table>

    </div>
    </div>
</div>

<!-- Modal Prévia de E-mail de Diligência -->
<div id="modalPreviewEmailDiligencia" class="modal modal-fixed-footer" style="width: 80% !important; max-height: 85% !important;">
    <div class="modal-content">
        <h4>Prévia da Notificação</h4>
        <div class="row" style="background: #f1f1f1; padding: 10px; border-radius: 5px;">
            <div class="col s12">
                <p style="margin: 5px 0;"><strong>Para:</strong> <span id="previewEmailTo"></span></p>
                <p style="margin: 5px 0;"><strong>Cópia (CC):</strong> <span id="previewEmailCc"></span></p>
                <p style="margin: 5px 0;"><strong>Assunto:</strong> <span id="previewEmailSubject"></span></p>
            </div>
        </div>
        <div class="row">
            <div class="col s12" style="border: 1px solid #ccc; padding: 20px; background: white; margin-top: 15px; min-height: 300px;" id="previewEmailBody">
                <!-- Conteúdo HTML do e-mail -->
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <a href="#!" id="btnConfirmSendDiligencia" class="waves-effect waves-green btn blue white-text" id_dil="">Confirmar e Enviar</a>
    </div>
</div>

<div id="alterarFaseModal" class="modal">
    <div class="modal-content">
        <h4>Alterar Estágio do Recurso</h4>
        <table>
            <tr>
                <?php

                foreach (getFasesRecurso() as $fs) {
                    $cor = $fs['id'] == $result['fase'] ? "blue" : "";
                    echo "<td class='recFase ' fase='{$fs["id"]}'>";
                    echo "<div class='chip {$cor}'>{$fs["texto"]}</div>";
                    echo "</td>";
                }
                ?>
            </tr>
        </table>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
    </div>
</div>