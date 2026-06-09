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

// Busca a notificação para recuperar o artigo (em notação regimento, ex: "14.1")
$parts = explode('/', $result['numero']);
$num = isset($parts[0]) ? (int)$parts[0] : 0;
$ano = isset($parts[1]) ? (int)$parts[1] : 0;
$notifRecurso = getNotificacaoByNumeroAno($num, $ano);
$artigoNota = ($notifRecurso && isset($notifRecurso['artigo'])) ? $notifRecurso['artigo'] : null;

// Função helper para buscar artigo no regimento localmente
function obterArtigoDoRegimento($notacao) {
    $jsonPath = dirname(__DIR__) . '/regimento/database.json';
    if (!file_exists($jsonPath)) return null;
    
    $database = json_decode(file_get_contents($jsonPath), true);
    if (!$database || !isset($database['artigos'])) return null;
    
    $partes = explode('.', strtolower($notacao));
    $artigoNumero = $partes[0];
    if (!isset($database['artigos'][$artigoNumero])) return null;
    
    $artigoPai = $database['artigos'][$artigoNumero];
    $resultado = $artigoPai;
    
    for ($i = 1; $i < count($partes); $i++) {
        $parteDoCaminho = $partes[$i];
        $proximoNivelEncontrado = false;
        
        if (preg_match('/^([pia])(.+)$/', $parteDoCaminho, $matches)) {
            $tipo = $matches[1]; 
            $chave = $matches[2];
            $mapaTipos = ['p' => 'paragrafos', 'i' => 'incisos', 'a' => 'alineas'];
            $subnivelAlvo = $mapaTipos[$tipo];
            if (isset($resultado[$subnivelAlvo]) && isset($resultado[$subnivelAlvo][$chave])) {
                $resultado = $resultado[$subnivelAlvo][$chave];
                $proximoNivelEncontrado = true;
            }
        } else {
            $chave = $parteDoCaminho;
            $ordemDeBusca = ['incisos', 'paragrafos', 'alineas'];
            foreach ($ordemDeBusca as $subnivel) {
                if (isset($resultado[$subnivel]) && isset($resultado[$subnivel][$chave])) {
                    $resultado = $resultado[$subnivel][$chave];
                    $proximoNivelEncontrado = true;
                    break;
                }
            }
        }
        if (!$proximoNivelEncontrado) return null;
    }
    
    return [
        'artigo_numero' => $artigoNumero,
        'texto_pai' => count($partes) > 1 ? ($artigoPai['texto'] ?? null) : null,
        'titulo_pai' => count($partes) > 1 ? ($artigoPai['titulo_artigo'] ?? null) : null,
        'conteudo' => $resultado,
        'notacao' => $notacao
    ];
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
    $dataOcorrido = "Não informada";
    if (!empty($notifRecurso['data_ocorrido'])) {
        $dataOcorrido = date('d/m/Y', strtotime($notifRecurso['data_ocorrido']));
    }
    $cobranca = isset($notifRecurso['cobranca']) ? $notifRecurso['cobranca'] : "Não informada";

    echo '<div class="container" style="margin-top: 20px;">
    <div class="row">
    <div class="col s12">
        <div class="card premium-card" style="border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #cfd8dc; margin-bottom: 25px;">';
    
    // Premium Header
    echo '
    <div style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 18px 24px; color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <div style="background: rgba(255,255,255,0.15); padding: 8px 12px; border-radius: 6px; font-weight: bold; border-left: 3px solid #ff9800; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                <i class="material-icons" style="font-size: 1.2rem; color: #ff9800;">assignment</i>
                Recurso <span id="idRecurso" idRec="' . $esseRecurso . '">' . $result['numero'] . '</span>
            </div>
            <div class="chip white-text" style="background: rgba(255,255,255,0.1); margin: 0; border: 1px solid rgba(255,255,255,0.2); height: 32px; line-height: 32px;">
                <i class="material-icons left" style="color: #fff; margin-top: 4px;">home</i>
                Unidade: <span id="unidadeRecurso">' . $result['unidade'] . $result['bloco'] . '</span>
            </div>
            <div class="chip white-text" style="background: rgba(255,255,255,0.1); margin: 0; border: 1px solid rgba(255,255,255,0.2); height: 32px; line-height: 32px;">
                <i class="material-icons left" style="color: #fff; margin-top: 4px;">history</i>
                Histórico: <span id="historico">' . sizeof($historico) . '</span> Notif.
            </div>
            <div class="chip white-text" style="background: rgba(255,255,255,0.1); margin: 0; border: 1px solid rgba(255,255,255,0.2); height: 32px; line-height: 32px;">
                <i class="material-icons left" style="color: #fff; margin-top: 4px;">flag</i>
                Fase: <span id="fase">' . $result['fasee'] . '</span>
            </div>
            <div class="chip white-text" style="background: rgba(255,255,255,0.1); margin: 0; border: 1px solid rgba(255,255,255,0.2); height: 32px; line-height: 32px;">
                <i class="material-icons left" style="color: #fff; margin-top: 4px;">event_note</i>
                Ocorrido: ' . $dataOcorrido . '
            </div>
            <div class="chip white-text" style="background: rgba(255,255,255,0.1); margin: 0; border: 1px solid rgba(255,255,255,0.2); height: 32px; line-height: 32px;">
                <i class="material-icons left" style="color: #fff; margin-top: 4px;">monetization_on</i>
                Cobrança: ' . htmlspecialchars($cobranca) . '
            </div>
        </div>
        <div>
            <a class="btn-floating btn-small waves-effect waves-light orange" href="index.php?pag=editarRecurso&rec=' . $esseRecurso . '" title="Editar Recurso">
                <i class="material-icons">edit</i>
            </a>
        </div>
    </div>';

    echo '<div class="card-content" style="padding: 24px; background-color: #fcfdfe;">
            <h5 style="margin-top: 0; margin-bottom: 20px; font-weight: bold; color: #37474f; font-size: 1.3rem;">' . $result['titulo'] . '</h5>
            
            <!-- Timeline & Dates Alert Box -->
            <div style="background-color: #fffde7; border: 1px solid #fff59d; border-left: 4px solid #fbc02d; padding: 15px; border-radius: 6px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <span style="font-size: 0.8rem; color: #757575; display: block;">Prazo de Apresentação</span>
                        <strong style="font-size: 0.95rem; color: ' . ($pontoDeAtencao == "red" ? "#c62828" : "#2e7d32") . ';">
                            ' . $delayEmDias . ' dias transcorridos
                        </strong>
                    </div>
                    <div>
                        <span style="font-size: 0.8rem; color: #757575; display: block;">Data de Retirada</span>
                        <strong style="font-size: 0.95rem; color: #37474f;">' . $diaRetirada . '</strong>
                    </div>
                    <div>
                        <span style="font-size: 0.8rem; color: #757575; display: block;">Apresentação do Recurso</span>
                        <strong style="font-size: 0.95rem; color: #37474f;">' . date('d/m/Y', strtotime($result["data"])) . '</strong>
                    </div>
                </div>';
    if (!empty($dataRetirada[0]["obs"])) {
        echo '  <div style="margin-top: 10px; border-top: 1px solid #fff59d; padding-top: 8px; font-size: 0.85rem; color: #5d4037;">
                    <strong>Obs. Retirada:</strong> ' . htmlspecialchars($dataRetirada[0]["obs"]) . '
                </div>';
    }
    echo '  </div>

            <!-- Fato Ocorrido -->
            <div style="margin-bottom: 20px;">
                <h6 style="font-weight: bold; color: #263238; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <i class="material-icons orange-text" style="font-size: 1.2rem;">warning</i> Fato Ocorrido
                </h6>
                <div style="background-color: #fafafa; border-left: 4px solid #ff9800; padding: 15px; border-radius: 4px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02); font-size: 0.95rem; line-height: 1.6; color: #263238;">
                    ' . $result['fato'] . '
                </div>
            </div>';

    // RENDERIZA O CARD DO ARTIGO SE HOUVER
    $artigoHtml = '';
    if (!empty($artigoNota)) {
        $artigoData = obterArtigoDoRegimento($artigoNota);
        if ($artigoData) {
            $artigoHtml .= '<div class="card blue-grey lighten-5" style="border-radius: 8px; border: 1px solid #b2dfdb; margin: 15px 0;">';
            $artigoHtml .= '  <div class="card-content black-text" style="padding: 15px;">';
            $artigoHtml .= '    <span class="card-title" style="font-size: 1.15rem; font-weight: bold; color: #00796b; margin-bottom: 8px; display: flex; align-items: center;">';
            $artigoHtml .= '      <i class="material-icons left" style="color: #00796b; margin-right: 8px;">gavel</i> Regulamento Interno: Artigo ' . htmlspecialchars($artigoData['artigo_numero']);
            if ($artigoData['notacao'] !== $artigoData['artigo_numero']) {
                $artigoHtml .= ' (' . htmlspecialchars($artigoData['notacao']) . ')';
            }
            $artigoHtml .= '    </span>';
            
            if ($artigoData['texto_pai']) {
                $artigoHtml .= '    <p class="grey-text text-darken-3" style="font-size: 0.85rem; margin-bottom: 12px; font-style: italic; line-height: 1.35;">';
                if ($artigoData['titulo_pai']) {
                    $artigoHtml .= '      <strong>' . htmlspecialchars($artigoData['titulo_pai']) . '</strong><br>';
                }
                $artigoHtml .= '      ' . htmlspecialchars($artigoData['texto_pai']);
                $artigoHtml .= '    </p>';
            }
            
            $conteudo = $artigoData['conteudo'];
            $artigoHtml .= '    <div class="white" style="padding: 12px; border-radius: 5px; border-left: 4px solid #009688; font-size: 0.95rem; line-height: 1.45; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
            if (isset($conteudo['texto'])) {
                $artigoHtml .= '      ' . htmlspecialchars($conteudo['texto']);
            } else {
                $artigoHtml .= '      ' . htmlspecialchars($conteudo['texto'] ?? '');
            }
            
            if (isset($conteudo['paragrafos']) || isset($conteudo['incisos']) || isset($conteudo['alineas'])) {
                $artigoHtml .= '      <ul style="margin: 8px 0 0 15px; padding-left: 0; list-style-type: none;">';
                if (isset($conteudo['paragrafos'])) {
                    foreach ($conteudo['paragrafos'] as $n => $sub) {
                        $lbl = ($n === 'unico') ? 'Parágrafo único:' : "§ {$n}°:";
                        $artigoHtml .= '        <li style="margin-top: 5px;"><strong>' . $lbl . '</strong> ' . htmlspecialchars($sub['texto']) . '</li>';
                    }
                }
                if (isset($conteudo['incisos'])) {
                    foreach ($conteudo['incisos'] as $n => $sub) {
                        $artigoHtml .= '        <li style="margin-top: 5px;"><strong>Inciso ' . $n . ':</strong> ' . htmlspecialchars($sub['texto']) . '</li>';
                    }
                }
                if (isset($conteudo['alineas'])) {
                    foreach ($conteudo['alineas'] as $n => $sub) {
                        $artigoHtml .= '        <li style="margin-top: 5px;"><strong>Alínea ' . $n . '):</strong> ' . htmlspecialchars($sub['texto']) . '</li>';
                    }
                }
                $artigoHtml .= '      </ul>';
            }
            $artigoHtml .= '    </div>';
            $artigoHtml .= '  </div>';
            $artigoHtml .= '</div>';
        }
    }
    echo $artigoHtml;

    echo '      <!-- Argumentação -->
            <div style="margin-bottom: 20px;">
                <h6 style="font-weight: bold; color: #263238; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <i class="material-icons blue-text" style="font-size: 1.2rem;">description</i> Argumentação / Justificativa
                </h6>
                <div style="background-color: #ffffff; border: 1px solid #e0e0e0; border-left: 4px solid #2196f3; padding: 18px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); font-size: 0.95rem; line-height: 1.6; color: #37474f;">
                    <pre style="margin: 0; background: none; border: none; padding: 0; font-family: inherit; font-size: inherit; color: inherit; white-space: pre-wrap;">' . $result['detalhes'] . '</pre>
                </div>
            </div>';

    echo '      <!-- Anexos Grid Premium -->
            <h6 style="font-weight: bold; color: #37474f; margin-top: 25px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="material-icons blue-text">attachment</i> Anexos do Condômino (Enviados via Portal)
            </h6>';
    if (!empty($anexos)) {
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 25px;">';
        foreach ($anexos as $anx) {
            $ext = strtolower(pathinfo($anx['nome_arquivo'], PATHINFO_EXTENSION));
            $urlDownload = 'portal/api.php?action=get_anexo&id=' . $anx['id'];
            $urlView = $urlDownload . '&view=1';

            echo '<div style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                echo '<div style="text-align: center; margin-bottom: 10px; background: #f9f9f9; border-radius: 6px; overflow: hidden; height: 180px; display: flex; align-items: center; justify-content: center; border: 1px solid #f0f0f0;">
                        <img src="' . $urlView . '" class="responsive-img materialboxed" style="max-height: 180px; max-width: 100%; cursor: pointer;" alt="' . htmlspecialchars($anx['nome_arquivo']) . '">
                      </div>';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">
                        <span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' . htmlspecialchars($anx['nome_arquivo']) . '">
                            ' . htmlspecialchars($anx['nome_arquivo']) . '
                        </span>
                        <a href="' . $urlDownload . '" target="_blank" class="btn-flat btn-small grey lighten-4" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">file_download</i>Baixar</a>
                      </div>';
            } elseif (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'])) {
                echo '<div style="text-align: center; margin-bottom: 10px; background: #000; border-radius: 6px; overflow: hidden; height: 180px; display: flex; align-items: center; justify-content: center;">
                        <video controls style="max-width: 100%; max-height: 180px;"><source src="' . $urlView . '" type="video/' . ($ext == 'mov' ? 'mp4' : $ext) . '">Seu navegador não suporta vídeos.</video>
                      </div>';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">
                        <span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' . htmlspecialchars($anx['nome_arquivo']) . '">
                            ' . htmlspecialchars($anx['nome_arquivo']) . '
                        </span>
                        <a href="' . $urlDownload . '" target="_blank" class="btn-flat btn-small grey lighten-4" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">file_download</i>Baixar</a>
                      </div>';
            } elseif (in_array($ext, ['mp3', 'wav', 'aac'])) {
                echo '<div style="padding: 10px; background: #fafafa; border-radius: 6px; margin-bottom: 10px; border: 1px solid #f0f0f0; display: flex; align-items: center; height: 180px; justify-content: center;">
                        <audio controls style="width: 100%;"><source src="' . $urlView . '" type="audio/' . ($ext == 'mp3' ? 'mpeg' : $ext) . '">Seu navegador não suporta áudio.</audio>
                      </div>';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">
                        <span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' . htmlspecialchars($anx['nome_arquivo']) . '">
                            ' . htmlspecialchars($anx['nome_arquivo']) . '
                        </span>
                        <a href="' . $urlDownload . '" target="_blank" class="btn-flat btn-small grey lighten-4" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">file_download</i>Baixar</a>
                      </div>';
            } elseif ($ext === 'pdf') {
                echo '<div style="padding: 10px; background: #ffebee; border-radius: 6px; margin-bottom: 10px; border: 1px solid #ffcdd2; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; gap: 10px;">
                        <i class="material-icons red-text" style="font-size: 3rem;">picture_as_pdf</i>
                        <span style="font-weight: 500; font-size: 0.9rem; color: #c62828;">Documento PDF</span>
                      </div>';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">
                        <span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' . htmlspecialchars($anx['nome_arquivo']) . '">
                            ' . htmlspecialchars($anx['nome_arquivo']) . '
                        </span>
                        <a href="' . $urlView . '" target="_blank" class="btn-flat btn-small red lighten-5 red-text" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">open_in_new</i>Abrir</a>
                      </div>';
            } else {
                echo '<div style="padding: 10px; background: #eceff1; border-radius: 6px; margin-bottom: 10px; border: 1px solid #cfd8dc; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; gap: 10px;">
                        <i class="material-icons grey-text text-darken-1" style="font-size: 3rem;">insert_drive_file</i>
                        <span style="font-weight: 500; font-size: 0.9rem; color: #37474f;">Arquivo .' . strtoupper($ext) . '</span>
                      </div>';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">
                        <span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' . htmlspecialchars($anx['nome_arquivo']) . '">
                            ' . htmlspecialchars($anx['nome_arquivo']) . '
                        </span>
                        <a href="' . $urlDownload . '" target="_blank" class="btn-flat btn-small grey lighten-4" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">file_download</i>Baixar</a>
                      </div>';
            }

            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="grey-text" style="margin-bottom: 25px;">Nenhum anexo extra fornecido.</p>';
    }

    if ($parecer['concluido'] == 1) {
        $link = "https://mail.google.com/mail/#inbox/" . $parecer['mailId'];
        echo "<div style='margin-bottom: 20px;'>";
        echo "<a class='btn blue' href='{$link}' style='margin-right:10px;'><i class='material-icons left'>email</i>Email do Parecer</a>";
        echo '<a class="btn yellow darken-3" href="index.php?pag=emiteParecer&rec=' . $result['numero'] . '"><i class="material-icons left">assignment</i>Ver Parecer</a>';
        echo "</div>";
    }

    // Exibição dos votos do Conselho
    echo '<div style="margin-top: 25px; margin-bottom: 25px;">
            <h6 style="font-weight: bold; color: #37474f; margin-bottom: 15px;">Votação do Conselho</h6>';
    if (!empty($votos)) {
        echo '<div style="display: flex; flex-wrap: wrap; gap: 12px;">';
        foreach ($votos as $voto) {
            echo '
            <div style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 20px; padding: 6px 16px 6px 6px; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <img src="' . $voto['avatar'] . '" alt="" style="width: 28px; height: 28px; border-radius: 50%;">
                <span style="font-weight: 500; font-size: 0.9rem; color: #37474f;">' . htmlspecialchars($voto['nome'] ?? 'Conselheiro') . ':</span>
                <span class="chip ' . (strtolower($voto['voto']) == 'revogar' ? 'teal white-text' : 'red darken-4 white-text') . '" style="margin:0; height:22px; line-height:22px; font-size:0.75rem;">
                    ' . strtoupper($voto['voto']) . '
                </span>
            </div>';
        }
        echo '</div>';
    } else {
        echo '<p class="grey-text">Nenhum voto registrado ainda.</p>';
    }
    echo '</div>';

    // Diligências
    echo '<h6 style="font-weight: bold; color: #37474f; margin-top: 30px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="material-icons orange-text text-darken-3">search</i> Diligências (Visível ao Morador)
          </h6>';
    if (!empty($diligencias)) {
        echo '<div style="margin-bottom: 30px;">';
        foreach ($diligencias as $mensagem) {
            $dataFormatada = date('d/m/Y H:i:s', strtotime($mensagem['timestamp']));
            $textoFormatado = str_replace(["\r\n", "\r", "\n"], "<br>", htmlspecialchars($mensagem['texto']));
            $enviada = ($mensagem['enviada_ao_requerente'] == 1);
            
            $cardBg = $enviada ? "#e8f5e9" : "#ffffff";
            $borderCol = $enviada ? "#2e7d32" : "#ff9800";
            $badgeText = $enviada ? "Enviada ao Requerente" : "Diligência Interna (Não enviada)";
            
            $actions = "";
            if ($_SESSION["user_id"] == $mensagem["id_usuario"] && !$enviada) {
                $actions .= "<a class='editDiligence modal-trigger btn-flat btn-small' href='#editaDiligencia' comment='{$mensagem['id']}' style='padding: 0 8px; height: 28px; line-height: 28px; margin-left: 10px; display: inline-flex; align-items: center;'><i class='green-text text-darken-2 material-icons' style='font-size: 1.1rem; margin-right: 4px;'>edit</i>Editar</a>";
            }
            
            if (!$enviada) {
                $actions .= " <a class='notificarRequerente btn-flat btn-small' comment='{$mensagem['id']}' style='padding: 0 8px; height: 28px; line-height: 28px; margin-left: 10px; display: inline-flex; align-items: center;'><i class='material-icons orange-text text-darken-3' style='font-size: 1.1rem; margin-right: 4px;'>send</i>Enviar p/ Morador</a>";
            }
            
            echo '
            <div class="diligence-card" style="background-color: ' . $cardBg . '; border: 1px solid #e0e0e0; border-left: 4px solid ' . $borderCol . '; border-radius: 8px; padding: 16px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.03);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="' . $mensagem['avatar'] . '" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div>
                            <span style="font-weight: bold; color: #37474f; font-size: 0.95rem;">' . htmlspecialchars($mensagem['nome'] ?? 'Usuário') . '</span>
                            <span style="font-size: 0.75rem; color: #757575; display: block;">' . $dataFormatada . '</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="chip ' . ($enviada ? 'green lighten-4 green-text text-darken-4' : 'orange lighten-4 orange-text text-darken-4') . '" style="margin: 0; font-size: 0.8rem; height: 24px; line-height: 24px;">
                            ' . $badgeText . '
                        </span>
                        ' . $actions . '
                    </div>
                </div>
                <div class="mensagem-texto" style="font-size: 0.95rem; line-height: 1.5; color: #37474f; padding-left: 46px;">' . $textoFormatado . '</div>';
            
            $dilAnexos = getDiligenciaAnexos($mensagem['id']);
            if (!empty($dilAnexos)) {
                echo '<div style="margin-top:12px; padding-left: 46px; display: flex; flex-wrap: wrap; gap: 10px;">';
                foreach ($dilAnexos as $da) {
                    $ext = strtolower(pathinfo($da['caminho_arquivo'], PATHINFO_EXTENSION));
                    $caminho = $da['caminho_arquivo'];
                    $nome = $da['nome_arquivo'];
                    
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        echo "
                        <div style='position: relative; max-width: 200px;'>
                            <img src='{$caminho}' class='responsive-img materialboxed z-depth-1 hoverable' style='max-height:120px; border-radius:6px; cursor:pointer; border: 1px solid #e0e0e0;' alt='{$nome}'>
                        </div>";
                    } else if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                        echo "<div style='max-width: 250px;'><video controls class='responsive-video' style='border-radius:6px; border: 1px solid #e0e0e0;'><source src='{$caminho}' type='video/{$ext}'></video></div>";
                    } else if (in_array($ext, ['mp3', 'wav'])) {
                        echo "<div style='width: 250px;'><audio controls style='width:100%; height:32px;'><source src='{$caminho}' type='audio/mpeg'></audio></div>";
                    } else {
                        echo '<a href="' . $caminho . '" target="_blank" class="chip hoverable" style="height: 28px; line-height: 28px; margin: 0; background: #f5f5f5;"><i class="material-icons left" style="font-size: 1.1rem;">insert_drive_file</i>' . htmlspecialchars($nome) . '</a> ';
                    }
                }
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="grey-text" style="margin-bottom: 30px;">Nenhuma diligência registrada.</p>';
    }

    // Comentários Internos
    echo '<h6 style="font-weight: bold; color: #37474f; margin-top: 30px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="material-icons blue-text">chat_bubble_outline</i> Comentários Internos (Privativo)
          </h6>';
    if (!empty($mensagens)) {
        echo '<div style="margin-bottom: 30px;">';
        foreach ($mensagens as $mensagem) {
            $dataFormatada = date('d/m/Y H:i:s', strtotime($mensagem['timestamp']));
            $textoFormatado = str_replace(["\r\n", "\r", "\n"], "<br>", htmlspecialchars($mensagem['texto']));
            
            $actions = "";
            if ($_SESSION["user_id"] == $mensagem["id_usuario"]) {
                $actions = "<a class='editComment modal-trigger btn-flat btn-small' href='#editaComentario' comment='{$mensagem['id']}' style='padding: 0 8px; height: 28px; line-height: 28px; display: inline-flex; align-items: center;'><i class='green-text text-darken-2 material-icons' style='font-size: 1.1rem; margin-right: 4px;'>edit</i>Editar</a>";
            }
            
            echo '
            <div class="comment-card" style="background-color: #ffffff; border: 1px solid #e0e0e0; border-left: 4px solid #2196f3; border-radius: 8px; padding: 16px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.03);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="' . $mensagem['avatar'] . '" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div>
                            <span style="font-weight: bold; color: #37474f; font-size: 0.95rem;">' . htmlspecialchars($mensagem['nome'] ?? 'Usuário') . '</span>
                            <span style="font-size: 0.75rem; color: #757575; display: block;">' . $dataFormatada . '</span>
                        </div>
                    </div>
                    <div>
                        ' . $actions . '
                    </div>
                </div>
                <div class="mensagem-texto" style="font-size: 0.95rem; line-height: 1.5; color: #37474f; padding-left: 46px;">' . $textoFormatado . '</div>';
            
            if (!empty($mensagem['anexos'])) {
                echo '<div style="margin-top:12px; padding-left: 46px; display: flex; flex-wrap: wrap; gap: 10px;">';
                foreach ($mensagem['anexos'] as $ma) {
                    $ext = strtolower(pathinfo($ma['caminho_arquivo'], PATHINFO_EXTENSION));
                    $caminho = $ma['caminho_arquivo'];
                    $nome = $ma['nome_arquivo'];
                    
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        echo "
                        <div style='position: relative; max-width: 200px;'>
                            <img src='{$caminho}' class='responsive-img materialboxed z-depth-1 hoverable' style='max-height:120px; border-radius:6px; cursor:pointer; border: 1px solid #e0e0e0;' alt='{$nome}'>
                        </div>";
                    } else if (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                        echo "<div style='max-width: 250px;'><video controls class='responsive-video' style='border-radius:6px; border: 1px solid #e0e0e0;'><source src='{$caminho}' type='video/{$ext}'></video></div>";
                    } else if (in_array($ext, ['mp3', 'wav'])) {
                        echo "<div style='width: 250px;'><audio controls style='width:100%; height:32px;'><source src='{$caminho}' type='audio/mpeg'></audio></div>";
                    } else {
                        echo '<a href="' . $caminho . '" target="_blank" class="chip hoverable" style="height: 28px; line-height: 28px; margin: 0; background: #f5f5f5;"><i class="material-icons left" style="font-size: 1.1rem;">insert_drive_file</i>' . htmlspecialchars($nome) . '</a> ';
                    }
                }
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="grey-text" style="margin-bottom: 30px;">Nenhum comentário registrado.</p>';
    }
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

    echo '<button class="btn deep-orange" id="btnSyncSupabase" data-rec="' . htmlspecialchars($result['numero']) . '">Sincronizar Supabase</button> ';

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
    <script>
    $(document).ready(function() {
        $('#btnSyncSupabase').click(function() {
            var rec = $(this).attr('data-rec');
            var $btn = $(this);
            
            // Desabilita o botão para evitar cliques duplos
            $btn.addClass('disabled').text('Sincronizando...');
            
            $.ajax({
                url: 'magnacom-sistema/sync_single.php',
                type: 'POST',
                data: { rec: rec },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var msg = 'Notificação sincronizada!';
                        if (response.data.artigo) {
                            msg += ' Artigo copiado: ' + response.data.artigo;
                        }
                        M.toast({html: msg, classes: 'green'});
                        // Recarrega a página após 1.5s para exibir o artigo e dados atualizados
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        M.toast({html: 'Erro: ' + response.error, classes: 'red'});
                        $btn.removeClass('disabled').text('Sincronizar Supabase');
                    }
                },
                error: function(xhr, status, error) {
                    M.toast({html: 'Erro ao conectar com o servidor.', classes: 'red'});
                    $btn.removeClass('disabled').text('Sincronizar Supabase');
                }
            });
        });
    });
    </script>
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