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

    // Silenciosamente verificar e resolver UUIDs do bloco e unidade na VDS e buscar moradores/inadimplência
    require_once __DIR__ . "/../classes/vds_acesso_service.php";
    $uuidsResolvidos = vds_resolve_bloco_unidade_uuid($result['bloco'], $result['unidade']);
    $resMoradores = vds_get_moradores_unidade($result['bloco'], $result['unidade']);
    $moradoresUnidade = $resMoradores['moradores'] ?? [];
    $unidadeInadimplente = $resMoradores['inadimplente'] ?? false;
    $veiculosUnidade = vds_get_veiculos_unidade($result['bloco'], $result['unidade']);
}



// Busca a notificação para recuperar o artigo (em notação regimento, ex: "14.1")
$parts = explode('/', $result['numero']);
$num = isset($parts[0]) ? (int) $parts[0] : 0;
$ano = isset($parts[1]) ? (int) $parts[1] : 0;
$anoNotifCurto = $ano ? substr((string)$ano, -2) : '';
$notifRecurso = getNotificacaoByNumeroAno($num, $ano);
$artigoNota = ($notifRecurso && isset($notifRecurso['artigo'])) ? $notifRecurso['artigo'] : null;

// Função helper para buscar artigo no regimento localmente
function obterArtigoDoRegimento($notacao)
{
    $jsonPath = dirname(__DIR__) . '/regimento/database.json';
    if (!file_exists($jsonPath))
        return null;

    $database = json_decode(file_get_contents($jsonPath), true);
    if (!$database || !isset($database['artigos']))
        return null;

    $partes = explode('.', strtolower($notacao));
    $artigoNumero = $partes[0];
    if (!isset($database['artigos'][$artigoNumero]))
        return null;

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
        if (!$proximoNivelEncontrado)
            return null;
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
            </div>' . ($unidadeInadimplente ? '
            <div class="chip red white-text font-weight-bold" style="background: #d32f2f; margin: 0; border: 1px solid rgba(255,255,255,0.4); height: 32px; line-height: 32px;">
                <i class="material-icons left" style="color: #fff; margin-top: 4px;">warning</i>
                UNIDADE INADIMPLENTE
            </div>' : '') . '
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
                        <strong class="detalhe-dia-retirada" style="font-size: 0.95rem; color: #37474f;">' . $diaRetirada . '</strong>
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

    // Container para Anexos do Supabase (Magnacom)
    echo '  <div id="supabaseFilesContainer" style="display: none; margin-bottom: 25px;">
                <h6 style="font-weight: bold; color: #37474f; margin-top: 25px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="material-icons orange-text text-darken-3">cloud_queue</i> Anexos da Notificação (Sistema Magnacom)
                </h6>
                <div id="supabaseFilesList" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                    <!-- Os cards dos arquivos serão inseridos via AJAX -->
                </div>
            </div>';

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

    // --- ACELERADORES DE ANÁLISE (CONDOMÍNIO DIGITAL API v8) ---
    require_once __DIR__ . "/../classes/vds_acesso_service.php";
    $dataOcorrencia = !empty($result['data']) ? $result['data'] : date('Y-m-d H:i:s');
    $dtInicio = date('Y-m-d\T00:00', strtotime($dataOcorrencia));
    $dtFim = date('Y-m-d\T23:59', strtotime($dataOcorrencia));

    $dtIniJanela = date('Y-m-d', strtotime($dataOcorrencia . ' -1 day'));
    $dtFimJanela = date('Y-m-d', strtotime($dataOcorrencia . ' +1 day'));

    $resMoradores = vds_get_moradores_unidade($result['bloco'], $result['unidade']);
    $moradoresUnidade = $resMoradores['moradores'] ?? [];
    $unidadeInadimplente = $resMoradores['inadimplente'] ?? false;

    $acessosUnidade = vds_get_eventos_acesso($result['bloco'], $result['unidade'], $dtInicio, $dtFim);
    $autorizacoesUnidade = vds_get_autorizacoes_acesso($result['bloco'], $result['unidade'], $dtIniJanela, $dtFimJanela);
    $entregasUnidade = vds_get_entregas_unidade($result['bloco'], $result['unidade']);
    $chamadosTag = vds_get_chamados_unidade($result['bloco'], $result['unidade']);
    ?>


    <!-- Modal de Inspeção Detalhada dos Aceleradores -->
    <div id="modalInspecionarAcelerador" class="modal" style="border-radius:8px; max-width:550px;">
        <div class="modal-content" id="conteudoInspecionarAcelerador">
            <!-- Preenchido via JavaScript -->
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-purple btn-flat font-weight-bold">Fechar</a>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Inicializar Collapsibles do Materialize
            var elemsCollapsible = document.querySelectorAll('.collapsible');
            if (elemsCollapsible.length > 0 && typeof M !== 'undefined' && M.Collapsible) {
                M.Collapsible.init(elemsCollapsible, { accordion: false });
            }

            // Inicializar Modais do Materialize
            var elemsModal = document.querySelectorAll('.modal');
            if (elemsModal.length > 0 && typeof M !== 'undefined' && M.Modal) {
                M.Modal.init(elemsModal);
            }
        });

        function formatObjStr(val) {
            if (!val) return 'N/A';
            if (typeof val === 'object') {
                return val.descricao || val.nome || val.tipo || val.detalhe || JSON.stringify(val);
            }
            return val;
        }

        function inspecionarItemAcelerador(tipo, data) {
            var html = '';
            if (tipo === 'acesso') {
                html += '<h5 style="margin-top:0; color:#6f42c1; font-weight:600; display:flex; align-items:center; gap:6px;"><i class="material-icons">fingerprint</i> Inspecionar Evento de Acesso</h5>';
                if (data.fotoUrl) {
                    html += '<div style="text-align:center; margin:15px 0;"><img src="' + data.fotoUrl + '" style="max-width:240px; max-height:200px; border-radius:12px; border:3px solid #6f42c1; box-shadow:0 4px 12px rgba(111,66,193,0.25);"></div>';
                }
                html += '<table class="striped" style="font-size:0.9rem; margin-top:10px;">';
                html += '<tr><td style="width:35%;"><b>Pessoa / Veículo:</b></td><td><b style="font-size:1.05rem;">' + formatObjStr(data.pessoaNome) + '</b></td></tr>';
                html += '<tr><td><b>Perfil / Categoria:</b></td><td><span class="badge purple lighten-4 purple-text text-darken-4" style="float:none; padding:3px 8px; border-radius:4px; font-weight:600;">' + formatObjStr(data.perfil) + '</span></td></tr>';
                if (data.modulo) {
                    html += '<tr><td><b>Ponto de Acesso:</b></td><td>' + formatObjStr(data.modulo) + '</td></tr>';
                }
                if (data.saida) {
                    html += '<tr><td><b>Sentido:</b></td><td><span class="badge blue lighten-4 blue-text text-darken-3 font-weight-bold" style="float:none; padding:2px 6px; border-radius:4px;">' + formatObjStr(data.saida) + '</span></td></tr>';
                }
                if (data.dispositivo || data.receptor) {
                    html += '<tr><td><b>Dispositivo / Leitor:</b></td><td>' + formatObjStr(data.dispositivo || data.receptor) + '</td></tr>';
                }
                html += '<tr><td><b>Data / Hora:</b></td><td>' + formatObjStr(data.dthora) + '</td></tr>';
                if (data.descricao) {
                    html += '<tr><td><b>Observações:</b></td><td>' + formatObjStr(data.descricao) + '</td></tr>';
                }
                html += '</table>';
            } else if (tipo === 'autorizacao') {
                html += '<h5 style="margin-top:0; color:#2e7d32; font-weight:600; display:flex; align-items:center; gap:6px;"><i class="material-icons">verified_user</i> Inspecionar Autorização de Acesso</h5>';
                if (data.foto) {
                    html += '<div style="text-align:center; margin:15px 0;"><img src="' + data.foto + '" style="max-width:240px; max-height:200px; border-radius:12px; border:3px solid #2e7d32; box-shadow:0 4px 12px rgba(46,125,50,0.25);"></div>';
                }
                html += '<table class="striped" style="font-size:0.9rem; margin-top:10px;">';
                html += '<tr><td style="width:35%;"><b>Visitante / Prestador:</b></td><td><b style="font-size:1.05rem;">' + formatObjStr(data.nome) + '</b></td></tr>';
                if (data.documento) {
                    html += '<tr><td><b>Documento:</b></td><td>' + formatObjStr(data.documento) + '</td></tr>';
                }
                html += '<tr><td><b>Validade da Liberação:</b></td><td>' + formatObjStr(data.dtInicio) + ' até ' + formatObjStr(data.dtFim) + '</td></tr>';
                html += '<tr><td><b>Autorizado Por (Morador):</b></td><td><span class="badge green lighten-4 green-text text-darken-4 font-weight-bold" style="float:none; padding:3px 8px; border-radius:4px;">' + formatObjStr(data.autorizadoPor) + '</span></td></tr>';
                html += '<tr><td><b>Cadastrado Por:</b></td><td>' + formatObjStr(data.registradoPor) + '</td></tr>';
                if (data.chave) {
                    html += '<tr><td><b>Chave / Código QR:</b></td><td><code style="background:#e8f5e9; color:#1b5e20; padding:2px 8px; border-radius:4px; font-weight:bold;">' + data.chave + '</code></td></tr>';
                }
                html += '<tr><td><b>Status:</b></td><td>' + formatObjStr(data.status) + '</td></tr>';
                html += '</table>';
            } else if (tipo === 'entrega') {
                inspecionarEntregaComDetalhes(data.uuid || '', data);
                return;
            }

            document.getElementById('conteudoInspecionarAcelerador').innerHTML = html;
            var elem = document.getElementById('modalInspecionarAcelerador');
            var instance = M.Modal.getInstance(elem) || M.Modal.init(elem);
            instance.open();
        }

        // Helper para verificar se um identificador ou descrição bate com o número/ano da notificação do recurso
        const numNotifRecurso = <?= (int)$num ?>;
        const anoNotifRecurso = <?= (int)$ano ?>;
        const anoNotifCurto = "<?= $anoNotifCurto ?>";
        const recNumCompleto = "<?= htmlspecialchars($result['numero'] ?? '') ?>";

        function checarMatchNotificacao(texto) {
            if (!texto || !numNotifRecurso) return false;
            const str = String(texto).trim();
            if (recNumCompleto && str.toLowerCase().includes(recNumCompleto.toLowerCase())) return true;
            const regex = new RegExp("(?:N|NOTIF|MULTA|INFRA|\\b)?[\\s\\-_]*" + numNotifRecurso + "(?:[\\s\\/-]*(?:" + anoNotifRecurso + "|" + anoNotifCurto + "))?\\b", "i");
            return regex.test(str);
        }

        let exibindoApenasMatches = false;

        function atualizarFiltroNotificacaoEntregas() {
            const rows = document.querySelectorAll('.linha-entrega-item');
            let matchesCount = 0;
            let outrosCount = 0;

            rows.forEach(r => {
                const idTxt = r.querySelector('.col-identificador') ? r.querySelector('.col-identificador').innerText : '';
                const descTxt = r.querySelector('td:nth-child(4)') ? r.querySelector('td:nth-child(4)').innerText : '';

                if (r.dataset.isNotifMatch === "true" || checarMatchNotificacao(idTxt) || checarMatchNotificacao(descTxt)) {
                    r.dataset.isNotifMatch = "true";
                    r.style.background = "#fff8e1";
                    r.style.borderLeft = "4px solid #ffa000";
                    matchesCount++;
                } else {
                    outrosCount++;
                }
            });

            const painel = document.getElementById('painel-filtro-entregas');
            const countElem = document.getElementById('count-outras-entregas');
            const headerBadge = document.getElementById('badge-entrega-match-header');

            if (matchesCount > 0) {
                if (painel) painel.style.display = 'flex';
                if (countElem) countElem.innerText = outrosCount;
                if (headerBadge) headerBadge.style.display = 'inline-flex';

                if (!window.filtroAplicadoAuto && outrosCount > 0) {
                    window.filtroAplicadoAuto = true;
                    toggleFiltroOutrasEntregas(true);
                }
            }
        }

        function toggleFiltroOutrasEntregas(forceOcultar) {
            const rows = document.querySelectorAll('.linha-entrega-item');
            const btn = document.getElementById('btn-toggle-filtro-entregas');

            if (typeof forceOcultar === 'boolean') {
                exibindoApenasMatches = forceOcultar;
            } else {
                exibindoApenasMatches = !exibindoApenasMatches;
            }

            rows.forEach(r => {
                if (r.dataset.isNotifMatch !== "true") {
                    r.style.display = exibindoApenasMatches ? 'none' : '';
                } else {
                    r.style.display = '';
                }
            });

            if (btn) {
                const outrosCount = document.getElementById('count-outras-entregas') ? document.getElementById('count-outras-entregas').innerText : '0';
                if (exibindoApenasMatches) {
                    btn.innerHTML = '<i class="material-icons left tiny">visibility</i> Exibir todas as entregas';
                    btn.className = 'btn-flat btn-small blue lighten-4 blue-text text-darken-4 font-weight-bold';
                } else {
                    btn.innerHTML = '<i class="material-icons left tiny">visibility_off</i> Ocultar outras entregas (' + outrosCount + ')';
                    btn.className = 'btn-flat btn-small amber lighten-4 amber-text text-darken-4 font-weight-bold';
                }
            }
        }

        // Fetch em segundo plano para obter identificador e foto de cada entrega
        document.addEventListener("DOMContentLoaded", function () {
            atualizarFiltroNotificacaoEntregas();

            const entregasRows = document.querySelectorAll('.linha-entrega-item[data-entrega-uuid]');
            entregasRows.forEach(function (row) {
                const uuid = row.getAttribute('data-entrega-uuid');
                if (!uuid) return;

                fetch(`metodo.php?metodo=obterDetalhesEntrega&uuid=${encodeURIComponent(uuid)}`)
                    .then(res => res.json())
                    .then(resData => {
                        if (resData && resData.success && resData.data) {
                            const d = resData.data;

                            // Atualizar Identificador / Código de Rastreio
                            if (d.identificador) {
                                const colId = row.querySelector('.col-identificador');
                                if (colId) {
                                    const isMatch = checarMatchNotificacao(d.identificador) || checarMatchNotificacao(d.descricao);
                                    if (isMatch) {
                                        colId.innerHTML = `
                                            <span class="badge amber darken-2 white-text font-weight-bold" style="float:none; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:3px; box-shadow:0 2px 6px rgba(255,160,0,0.3);">
                                                <i class="material-icons tiny">star</i> ${d.identificador}
                                            </span>
                                        `;
                                        row.dataset.isNotifMatch = "true";
                                        row.style.background = "#fff8e1";
                                        row.style.borderLeft = "4px solid #ffa000";
                                    } else {
                                        colId.innerHTML = `
                                            <span class="badge blue lighten-4 blue-text text-darken-3 font-weight-bold" style="float:none; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:3px;">
                                                <i class="material-icons tiny">qr_code</i> ${d.identificador}
                                            </span>
                                        `;
                                    }
                                }
                            }

                            // Atualizar Foto do Pacote (Miniatura super compacta 28x28px)
                            const fotoUrl = d.fotoUrlCompleta || (d.foto ? (d.foto.startsWith('http') ? d.foto : 'https://app.vidadesindico.com.br' + d.foto) : null);
                            if (fotoUrl) {
                                const colFoto = row.querySelector('.col-foto');
                                if (colFoto) {
                                    colFoto.innerHTML = `
                                    <img src="${fotoUrl}" style="width:28px; height:28px; border-radius:4px; object-fit:cover; border:1px solid #90caf9; cursor:pointer;" alt="Pacote">
                                `;
                                }
                            }

                            row.dataset.detalhesCompletos = JSON.stringify(d);
                            atualizarFiltroNotificacaoEntregas();
                        }
                    })
                    .catch(err => console.error('Erro ao carregar detalhes da entrega:', err));
            });
        });

        function inspecionarEntregaComDetalhes(uuid, baseData) {
            let data = baseData || {};
            const row = document.querySelector(`.linha-entrega-item[data-entrega-uuid="${uuid}"]`);
            if (row && row.dataset.detalhesCompletos) {
                try { data = Object.assign({}, data, JSON.parse(row.dataset.detalhesCompletos)); } catch (e) { }
            }

            const isMatch = checarMatchNotificacao(data.identificador) || checarMatchNotificacao(data.descricao);

            var html = '<h5 style="margin-top:0; color:#1e88e5; font-weight:600; display:flex; align-items:center; gap:6px;"><i class="material-icons">markunread_mailbox</i> Inspecionar Entrega / Encomenda</h5>';

            const fotoUrl = data.fotoUrlCompleta || (data.foto ? (data.foto.startsWith('http') ? data.foto : 'https://app.vidadesindico.com.br' + data.foto) : null);
            if (fotoUrl) {
                html += '<div style="text-align:center; margin:15px 0;"><img src="' + fotoUrl + '" style="max-width:280px; max-height:220px; border-radius:8px; border:2px solid #1e88e5; box-shadow:0 4px 12px rgba(30,136,229,0.2);"></div>';
            }

            html += '<table class="striped" style="font-size:0.9rem; margin-top:10px;">';
            if (data.identificador) {
                if (isMatch) {
                    html += '<tr><td style="width:35%;"><b>Identificador / Rastreio:</b></td><td><b class="amber-text text-darken-4" style="font-size:1.05rem;"><i class="material-icons tiny">star</i> ' + data.identificador + ' <span class="badge amber darken-2 white-text font-weight-bold" style="float:none; padding:2px 6px; border-radius:4px; font-size:0.75rem;">⭐ Correspondente à Notificação ' + recNumCompleto + '</span></b></td></tr>';
                } else {
                    html += '<tr><td style="width:35%;"><b>Identificador / Rastreio:</b></td><td><b class="blue-text text-darken-3" style="font-size:1.05rem;"><i class="material-icons tiny">qr_code</i> ' + data.identificador + '</b></td></tr>';
                }
            }
            if (data.protocolo) {
                html += '<tr><td><b>Protocolo VDS:</b></td><td>#' + data.protocolo + '</td></tr>';
            }
            html += '<tr><td><b>Descrição / Conteúdo:</b></td><td><b>' + formatObjStr(data.descricao) + '</b></td></tr>';
            html += '<tr><td><b>Destinatário:</b></td><td>' + formatObjStr(data.destinoNome || data.destinatario) + '</td></tr>';
            html += '<tr><td><b>Chegada na Portaria:</b></td><td>' + formatObjStr(data.dthoraChegada || (data.dthoraFormatada || data.dthora)) + '</td></tr>';
            if (data.dtFimFormatada || data.dtFim) {
                html += '<tr><td><b>Data/Hora Retirada:</b></td><td>' + formatObjStr(data.dtFimFormatada || data.dtFim) + '</td></tr>';
            }
            if (data.retiradoPor) {
                const retNome = typeof data.retiradoPor === 'object' ? data.retiradoPor.nome : data.retiradoPor;
                html += '<tr><td><b>Retirado Por:</b></td><td><span class="badge green lighten-4 green-text text-darken-4 font-weight-bold" style="float:none; padding:3px 8px; border-radius:4px;">' + retNome + '</span></td></tr>';
            }
            html += '</table>';

            if (data.eventos && Array.isArray(data.eventos) && data.eventos.length > 0) {
                html += '<h6 style="margin-top:15px; font-weight:bold; color:#555;">Histórico de Eventos da Portaria:</h6><ul class="collection" style="font-size:0.85rem;">';
                data.eventos.forEach(function (ev) {
                    const stNome = ev.status ? ev.status.nome : 'Status';
                    const regNome = ev.registradoPor ? ev.registradoPor.nome : '';
                    html += '<li class="collection-item"><b>' + stNome + '</b> ' + (regNome ? '<small class="grey-text">por ' + regNome + '</small>' : '') + '</li>';
                });
                html += '</ul>';
            }

            // === Botão de Upsert da Data de Retirada (somente quando é correspondente à notificação) ===
            if (isMatch && recNumCompleto) {
                // A data de ciência = data em que o morador RETIROU a correspondência (dtFim), não a data de chegada
                const dtRetiradaRaw = data.dtFimFormatada || data.dtFim || '';
                const dtChegadaRaw = data.dthoraChegada || data.dthoraFormatada || data.dthora || '';
                let dataSugerida = '';
                let fonteData = '';

                // Prioridade: data de retirada (dtFim) > data de chegada (dthora) como fallback
                const matchRetirada = String(dtRetiradaRaw).match(/(\d{2}\/\d{2}\/\d{4})/);
                if (matchRetirada) {
                    dataSugerida = matchRetirada[1];
                    fonteData = 'retirada pelo morador';
                } else {
                    const matchChegada = String(dtChegadaRaw).match(/(\d{2}\/\d{2}\/\d{4})/);
                    if (matchChegada) {
                        dataSugerida = matchChegada[1];
                        fonteData = 'chegada na portaria (retirada ainda não registrada)';
                    }
                }

                const diaRetiradaAtual = '<?= addslashes($diaRetirada ?? "Indisponível") ?>';
                const jaTemRetirada = diaRetiradaAtual && diaRetiradaAtual !== 'Indisponível';

                html += '<div style="margin-top:18px; padding:14px; background: linear-gradient(135deg, #fff3e0 0%, #fff8e1 100%); border:1px solid #ffe082; border-radius:8px; box-shadow:0 2px 8px rgba(255,160,0,0.12);">';
                html += '<h6 style="margin:0 0 10px 0; font-weight:700; color:#e65100; display:flex; align-items:center; gap:6px; font-size:0.95rem;"><i class="material-icons" style="font-size:1.2rem;">assignment_returned</i> Ciência / Retirada da Notificação ' + recNumCompleto + '</h6>';

                if (jaTemRetirada) {
                    html += '<p style="margin:0 0 8px; font-size:0.85rem; color:#555;"><i class="material-icons tiny green-text">check_circle</i> Data de ciência atual: <b class="green-text text-darken-3">' + diaRetiradaAtual + '</b></p>';
                } else {
                    html += '<p style="margin:0 0 8px; font-size:0.85rem; color:#c62828;"><i class="material-icons tiny">warning</i> <b>Nenhuma data de ciência cadastrada.</b>' + (dataSugerida ? ' Sugestão baseada na <b>' + fonteData + '</b>: <b>' + dataSugerida + '</b>.' : '') + '</p>';
                }

                html += '<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">';
                html += '<label style="font-size:0.8rem; font-weight:600; color:#795548;">Data de Ciência:</label>';
                html += '<input type="text" id="input-data-retirada-modal" value="' + (dataSugerida || diaRetiradaAtual.replace('Indisponível','')) + '" placeholder="DD/MM/AAAA" style="width:130px; height:32px; padding:4px 8px; border:1px solid #bdbdbd; border-radius:4px; font-size:0.9rem; text-align:center;" maxlength="10">';
                html += '<button type="button" onclick="upsertDataRetiradaNotificacao()" id="btn-salvar-retirada-modal" class="btn waves-effect waves-light amber darken-3 white-text" style="height:32px; line-height:32px; padding:0 16px; font-size:0.8rem; border-radius:4px; font-weight:600; text-transform:none;">';
                html += '<i class="material-icons left tiny" style="margin-right:4px;">save</i> ' + (jaTemRetirada ? 'Atualizar Data' : 'Salvar Data de Ciência');
                html += '</button>';
                html += '</div>';
                html += '<div id="feedback-retirada-modal" style="margin-top:6px; font-size:0.8rem;"></div>';
                html += '</div>';
            }

            document.getElementById('conteudoInspecionarAcelerador').innerHTML = html;
            var elem = document.getElementById('modalInspecionarAcelerador');
            var instance = M.Modal.getInstance(elem) || M.Modal.init(elem);
            instance.open();
        }

        function upsertDataRetiradaNotificacao() {
            const input = document.getElementById('input-data-retirada-modal');
            const feedback = document.getElementById('feedback-retirada-modal');
            const btn = document.getElementById('btn-salvar-retirada-modal');
            if (!input || !recNumCompleto) return;

            const valor = input.value.trim();
            if (!valor || !/^\d{2}\/\d{2}\/\d{4}$/.test(valor)) {
                if (feedback) feedback.innerHTML = '<span class="red-text"><i class="material-icons tiny">error</i> Formato inválido. Use DD/MM/AAAA.</span>';
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="material-icons left tiny spinning">sync</i> Salvando...';
            if (feedback) feedback.innerHTML = '<span class="grey-text"><i class="material-icons tiny spinning">hourglass_empty</i> Gravando...</span>';

            const formData = new FormData();
            formData.append('virtual', recNumCompleto);
            formData.append('dia_retirada', valor);

            fetch('metodo.php?metodo=atualizaDataRetiradaNotificacao', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(txt => {
                if (txt.trim() === 'success') {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="material-icons left tiny" style="margin-right:4px;">check</i> Salvo!';
                    btn.className = 'btn waves-effect waves-light green darken-2 white-text';
                    btn.style.cssText += 'height:32px; line-height:32px; padding:0 16px; font-size:0.8rem; border-radius:4px; font-weight:600; text-transform:none;';
                    if (feedback) feedback.innerHTML = '<span class="green-text text-darken-3"><i class="material-icons tiny">check_circle</i> Data de ciência da notificação <b>' + recNumCompleto + '</b> atualizada para <b>' + valor + '</b>.</span>';

                    // Atualizar a exibição do dia de retirada na página (se houver elemento)
                    const elemRetirada = document.querySelector('.detalhe-dia-retirada');
                    if (elemRetirada) {
                        elemRetirada.innerHTML = valor;
                        elemRetirada.classList.remove('red-text');
                        elemRetirada.classList.add('green-text', 'text-darken-3');
                    }

                    setTimeout(() => {
                        btn.innerHTML = '<i class="material-icons left tiny" style="margin-right:4px;">save</i> Atualizar Data';
                        btn.className = 'btn waves-effect waves-light amber darken-3 white-text';
                        btn.style.cssText += 'height:32px; line-height:32px; padding:0 16px; font-size:0.8rem; border-radius:4px; font-weight:600; text-transform:none;';
                    }, 3000);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="material-icons left tiny" style="margin-right:4px;">save</i> Tentar Novamente';
                    if (feedback) feedback.innerHTML = '<span class="red-text"><i class="material-icons tiny">error</i> Erro ao salvar: ' + txt + '</span>';
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="material-icons left tiny" style="margin-right:4px;">save</i> Tentar Novamente';
                if (feedback) feedback.innerHTML = '<span class="red-text"><i class="material-icons tiny">error</i> Erro de conexão: ' + err.message + '</span>';
            });
        }
    </script>

    <div class="card border-accent" style="margin-top: 20px; border-left: 4px solid #6f42c1;">
        <div class="card-content" style="padding: 15px;">
            <span class="card-title purple-text text-darken-3"
                style="font-size: 1.1rem; font-weight: 600; display:flex; align-items:center; gap:8px;">
                <i class="material-icons">search</i> Aceleradores de Análise da Defesa (Condomínio Digital)
            </span>
            <p class="grey-text text-darken-1" style="font-size: 0.85rem; margin-bottom: 12px;">
                Registros contextuais do dia da infração (<b><?= date('d/m/Y', strtotime($dataOcorrencia)) ?></b>) para
                Bloco <b><?= htmlspecialchars($result['bloco']) ?></b> / Apt
                <b><?= htmlspecialchars($result['unidade']) ?></b>.
            </p>

            <!-- Abas dos Aceleradores (Collapsible Ativo) -->
            <ul class="collapsible z-depth-0" style="border: 1px solid #e0e0e0;">
                <li>
                    <div class="collapsible-header" style="font-weight: 600;">
                        <i class="material-icons cyan-text text-darken-2">people</i>
                        Moradores da Unidade (<?= count($moradoresUnidade) ?>)
                        <?php if ($unidadeInadimplente): ?>
                            <span class="new badge red pulse" data-badge-caption="INADIMPLENTE" style="margin-left: 10px;"></span>
                        <?php endif; ?>
                    </div>
                    <div class="collapsible-body" style="padding: 10px;">
                        <?php if (empty($moradoresUnidade)): ?>
                            <p class="grey-text" style="margin:0;">Nenhum morador cadastrado encontrado para esta unidade.</p>
                        <?php else: ?>
                            <div class="row" style="margin-bottom:0;">
                                <?php foreach ($moradoresUnidade as $m): ?>
                                    <div class="col s12 m6 l3">
                                        <div class="card-panel white center-align z-depth-1 hoverable" style="border-radius:10px; padding:12px 8px; border:1px solid #e0e0e0; margin-bottom:10px;">
                                            <?php if (!empty($m['foto'])): ?>
                                                <img src="<?= htmlspecialchars($m['foto']) ?>" style="width:54px; height:54px; border-radius:50%; object-fit:cover; border:2px solid #00acc1; margin-bottom:4px;">
                                            <?php else: ?>
                                                <div style="width:54px; height:54px; border-radius:50%; background:#e0f7fa; display:flex; align-items:center; justify-content:center; margin:0 auto 4px auto; border:2px solid #00acc1;"><i class="material-icons cyan-text text-darken-2" style="font-size:2.2rem;">account_circle</i></div>
                                            <?php endif; ?>
                                            <div style="font-weight:bold; font-size:0.95rem; color:#37474f;" class="truncate" title="<?= htmlspecialchars($m['nome']) ?>"><?= htmlspecialchars($m['nome']) ?></div>
                                            <span class="badge-mini cyan darken-1 white-text" style="margin-top:4px; font-size:0.7rem; padding:2px 6px; border-radius:4px; display:inline-block;"><?= htmlspecialchars($m['tipo']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header" style="font-weight: 600;">
                        <i class="material-icons blue-grey-text text-darken-2">directions_car</i>
                        Veículos da Unidade (<?= count($veiculosUnidade) ?>)
                    </div>
                    <div class="collapsible-body" style="padding: 10px;">
                        <?php if (empty($veiculosUnidade)): ?>
                            <p class="grey-text" style="margin:0;">Nenhum veículo cadastrado encontrado para esta unidade.</p>
                        <?php else: ?>
                            <div class="row" style="margin-bottom:0;">
                                <?php foreach ($veiculosUnidade as $v): ?>
                                    <?php
                                    $isMoto = strpos(strtolower($v['tipo'] ?? ''), 'moto') !== false;
                                    $iconName = $isMoto ? 'two_wheeler' : 'directions_car';
                                    $descV = implode(' ', array_filter([$v['marca'], $v['modelo'], $v['cor']])) ?: 'Veículo';
                                    ?>
                                    <div class="col s12 m6 l3">
                                        <div class="card-panel white z-depth-1 hoverable" style="border-radius:10px; padding:12px; border:1px solid #e0e0e0; margin-bottom:10px;">
                                            <?php if (!empty($v['foto'])): ?>
                                                <img src="<?= htmlspecialchars($v['foto']) ?>" style="width:100%; height:100px; object-fit:cover; border-radius:6px; margin-bottom:8px; border:1px solid #ddd;">
                                            <?php endif; ?>
                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                                <span class="badge blue-grey darken-3 white-text font-weight-bold" style="float:none; padding:3px 8px; border-radius:4px; font-family:monospace; font-size:1rem; letter-spacing:1px; display:inline-flex; align-items:center; gap:4px;">
                                                    <i class="material-icons tiny"><?= $iconName ?></i> <?= htmlspecialchars($v['placa']) ?>
                                                </span>
                                            </div>

                                            <div style="font-weight:bold; font-size:0.95rem; color:#263238;" class="truncate" title="<?= htmlspecialchars($descV) ?>"><?= htmlspecialchars($descV) ?></div>
                                            <?php if (!empty($v['portadorNecessidade'])): ?>
                                                <span class="badge-mini blue darken-2 white-text font-weight-bold" style="font-size:0.7rem; margin-top:4px; padding:2px 6px; border-radius:4px; display:inline-block;"><i class="material-icons tiny">accessible</i> Vaga PCD</span>
                                            <?php endif; ?>
                                            <?php if (!empty($v['proprietario'])): ?>
                                                <div style="font-size:0.8rem; color:#555; margin-top:4px;" class="truncate" title="<?= htmlspecialchars($v['proprietario']) ?>"><i class="material-icons tiny">person</i> <?= htmlspecialchars($v['proprietario']) ?></div>
                                            <?php endif; ?>

                                            <?php if (!empty($v['observacao'])): ?>
                                                <div style="font-size:0.78rem; color:#757575; margin-top:4px; font-style:italic;" class="truncate" title="<?= htmlspecialchars($v['observacao']) ?>"><i class="material-icons tiny">info</i> <?= htmlspecialchars($v['observacao']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header" style="font-weight: 600;">
                        <i class="material-icons purple-text">fingerprint</i>
                        Eventos de Acesso & Visitas (<?= count($acessosUnidade) ?>)
                    </div>


                    <div class="collapsible-body" style="padding: 10px;">
                        <?php if (empty($acessosUnidade)): ?>
                            <p class="grey-text" style="margin:0;">Nenhum registro de acesso encontrado no dia.</p>
                        <?php else: ?>
                            <table class="striped highlight responsive-table" style="font-size:0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Hora</th>
                                        <th>Pessoa / Visitante</th>
                                        <th>Tipo de Evento</th>
                                        <th>Inspecionar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($acessosUnidade as $acc): ?>
                                        <?php $jsonAcc = htmlspecialchars(json_encode($acc, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
                                        <tr style="cursor:pointer;"
                                            onclick="inspecionarItemAcelerador('acesso', <?= $jsonAcc ?>)">
                                            <td><?= date('H:i:s', strtotime($acc['dthora'])) ?></td>
                                            <td>
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <?php if (!empty($acc['fotoUrl'])): ?>
                                                        <img src="<?= htmlspecialchars($acc['fotoUrl']) ?>"
                                                            style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid #6f42c1;">
                                                    <?php else: ?>
                                                        <i class="material-icons purple-text text-lighten-2 tiny">person</i>
                                                    <?php endif; ?>
                                                    <div>
                                                        <b><?= htmlspecialchars($acc['pessoaNome']) ?></b>
                                                        <small class="grey-text display-block"
                                                            style="font-size:0.75rem;">(<?= htmlspecialchars($acc['perfil']) ?>)</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($acc['tipoEvento']) ?></td>
                                            <td>
                                                <span class="btn-small waves-effect waves-light purple lighten-2 white-text"
                                                    style="height:24px; line-height:24px; padding:0 8px; font-size:0.75rem; border-radius:4px;">
                                                    Inspecionar <i class="material-icons right tiny"
                                                        style="margin-left:2px;">search</i>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header" style="font-weight: 600;">
                        <i class="material-icons green-text">verified_user</i>
                        Autorizações de Acesso / Convites (<?= count($autorizacoesUnidade) ?>)
                    </div>
                    <div class="collapsible-body" style="padding: 10px;">
                        <?php if (empty($autorizacoesUnidade)): ?>
                            <p class="grey-text" style="margin:0;">Nenhuma autorização ou convite ativo registrado para a
                                unidade no período.</p>
                        <?php else: ?>
                            <table class="striped highlight responsive-table" style="font-size:0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Visitante / Prestador</th>
                                        <th>Documento</th>
                                        <th>Validade</th>
                                        <th>Autorizado Por</th>
                                        <th>Status</th>
                                        <th>Inspecionar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($autorizacoesUnidade as $aut): ?>
                                        <?php $jsonAut = htmlspecialchars(json_encode($aut, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
                                        <tr style="cursor:pointer;"
                                            onclick="inspecionarItemAcelerador('autorizacao', <?= $jsonAut ?>)">
                                            <td>
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <?php if (!empty($aut['foto'])): ?>
                                                        <img src="<?= htmlspecialchars($aut['foto']) ?>"
                                                            style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid #2e7d32;">
                                                    <?php else: ?>
                                                        <i class="material-icons grey-text tiny">person</i>
                                                    <?php endif; ?>
                                                    <b><?= htmlspecialchars($aut['nome']) ?></b>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($aut['documento']) ?></td>
                                            <td><small><?= htmlspecialchars($aut['dtInicio']) ?><br>até
                                                    <?= htmlspecialchars($aut['dtFim']) ?></small></td>
                                            <td><span class="badge green lighten-5 green-text text-darken-4 font-weight-bold"
                                                    style="float:none; padding:2px 6px; border-radius:4px; font-size:0.75rem;"><?= htmlspecialchars($aut['autorizadoPor']) ?></span>
                                            </td>
                                            <td><span class="badge blue lighten-4 blue-text text-darken-4"
                                                    style="float:none; padding:2px 6px; border-radius:4px; font-size:0.75rem;"><?= htmlspecialchars($aut['status']) ?></span>
                                            </td>
                                            <td>
                                                <span class="btn-small waves-effect waves-light green darken-1 white-text"
                                                    style="height:24px; line-height:24px; padding:0 8px; font-size:0.75rem; border-radius:4px;">
                                                    Inspecionar <i class="material-icons right tiny"
                                                        style="margin-left:2px;">search</i>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header" style="font-weight: 600;">
                        <i class="material-icons blue-text">markunread_mailbox</i>
                        Entregas e Encomendas (<?= count($entregasUnidade) ?>)
                        <span id="badge-entrega-match-header" class="badge amber darken-2 white-text font-weight-bold" style="display:none; float:none; margin-left:8px; padding:2px 8px; border-radius:4px; font-size:0.75rem;">
                            <i class="material-icons tiny">star</i> Correspondente à Notificação <?= htmlspecialchars($result['numero'] ?? '') ?>
                        </span>
                    </div>
                    <div class="collapsible-body" style="padding: 10px;">
                        <?php if (empty($entregasUnidade)): ?>
                            <p class="grey-text" style="margin:0;">Nenhuma entrega recente registrada.</p>
                        <?php else: ?>
                            <!-- Painel de Destaque e Filtro da Notificação -->
                            <div id="painel-filtro-entregas" style="display:none; margin-bottom:10px; background:#fff8e1; border:1px solid #ffe082; padding:8px 12px; border-radius:6px; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                                <span style="color:#b78103; font-weight:600; font-size:0.85rem; display:inline-flex; align-items:center; gap:4px;">
                                    <i class="material-icons tiny amber-text text-darken-3">stars</i> 
                                    Destacando recebimento da Notificação <b><?= htmlspecialchars($result['numero'] ?? '') ?></b> na portaria
                                </span>
                                <button type="button" id="btn-toggle-filtro-entregas" class="btn-flat btn-small amber lighten-4 amber-text text-darken-4 font-weight-bold" onclick="toggleFiltroOutrasEntregas()" style="height:26px; line-height:26px; font-size:0.75rem; text-transform:none; border-radius:4px;">
                                    <i class="material-icons left tiny">visibility_off</i> Ocultar outras entregas (<span id="count-outras-entregas">0</span>)
                                </button>
                            </div>

                            <table class="striped highlight responsive-table" style="font-size:0.85rem;"
                                id="tabela-entregas-acelerador">
                                <thead>
                                    <tr>
                                        <th>Chegada</th>
                                        <th>Identificador / Rastreio</th>
                                        <th>Foto / Anexo</th>
                                        <th>Descrição</th>
                                        <th>Destinatário</th>
                                        <th>Inspecionar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $numNotifRecurso = (int)$num;
                                    $anoNotifRecurso = (int)$ano;
                                    $anoNotifCurto = $anoNotifRecurso ? substr((string)$anoNotifRecurso, -2) : '';
                                    $recNumStr = htmlspecialchars($result['numero'] ?? '');
                                    $regexPatternServer = '/(?:\bN?[\-_]?\s*' . $numNotifRecurso . '\s*[\/\-_]?\s*(?:' . $anoNotifRecurso . '|' . $anoNotifCurto . ')?\b|' . preg_quote($recNumStr, '/') . ')/i';
                                    ?>
                                    <?php foreach ($entregasUnidade as $ent): ?>
                                        <?php
                                        $entUuid = $ent['uuid'] ?? ($ent['id'] ?? '');
                                        $jsonEnt = htmlspecialchars(json_encode($ent, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                                        
                                        $idStrServer = $ent['identificador'] ?? '';
                                        $descStrServer = $ent['descricao'] ?? '';
                                        $isMatchServer = ($numNotifRecurso > 0) && (preg_match($regexPatternServer, $idStrServer) || preg_match($regexPatternServer, $descStrServer));
                                        ?>
                                        <tr data-entrega-uuid="<?= htmlspecialchars($entUuid) ?>" 
                                            data-is-notif-match="<?= $isMatchServer ? 'true' : 'false' ?>"
                                            class="linha-entrega-item"
                                            style="cursor:pointer; <?= $isMatchServer ? 'background:#fff8e1; border-left:4px solid #ffa000;' : '' ?>"
                                            onclick="inspecionarEntregaComDetalhes('<?= htmlspecialchars($entUuid) ?>', <?= $jsonEnt ?>)">
                                            <td><?= htmlspecialchars($ent['dthoraChegada']) ?></td>
                                            <td class="col-identificador">
                                                <?php if (!empty($ent['identificador'])): ?>
                                                    <?php if ($isMatchServer): ?>
                                                        <span class="badge amber darken-2 white-text font-weight-bold"
                                                            style="float:none; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:3px; box-shadow:0 2px 6px rgba(255,160,0,0.3);">
                                                            <i class="material-icons tiny">star</i>
                                                            <?= htmlspecialchars($ent['identificador']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge blue lighten-4 blue-text text-darken-3 font-weight-bold"
                                                            style="float:none; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:3px;">
                                                            <i class="material-icons tiny">qr_code</i>
                                                            <?= htmlspecialchars($ent['identificador']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php elseif (!empty($entUuid)): ?>
                                                    <span class="grey-text text-lighten-1 spin-load-id" style="font-size:0.8rem;"><i
                                                            class="material-icons tiny spinning">sync</i> Buscando...</span>
                                                <?php else: ?>
                                                    <span class="grey-text">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="col-foto">
                                                <?php if (!empty($ent['foto'])): ?>
                                                    <img src="<?= htmlspecialchars($ent['foto']) ?>"
                                                        style="width:28px; height:28px; border-radius:4px; object-fit:cover; border:1px solid #90caf9; cursor:pointer;"
                                                        alt="Pacote">
                                                <?php elseif (!empty($entUuid)): ?>
                                                    <span class="grey-text text-lighten-1 spin-load-foto"
                                                        style="font-size:0.8rem;"><i
                                                            class="material-icons tiny spinning">sync</i></span>
                                                <?php else: ?>
                                                    <span class="grey-text">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><b><?= htmlspecialchars($ent['descricao']) ?></b></td>
                                            <td><?= htmlspecialchars($ent['destinatario']) ?></td>
                                            <td>
                                                <span class="btn-small waves-effect waves-light blue lighten-2 white-text"
                                                    style="height:24px; line-height:24px; padding:0 8px; font-size:0.75rem; border-radius:4px;">
                                                    Inspecionar <i class="material-icons right tiny"
                                                        style="margin-left:2px;">search</i>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header" style="font-weight: 600;">
                        <i class="material-icons orange-text">label</i>
                        Ocorrências Onde a Unidade é Autora ou Citada (<?= count($chamadosTag) ?>)
                    </div>
                    <div class="collapsible-body" style="padding: 10px;">
                        <?php if (empty($chamadosTag)): ?>
                            <p class="grey-text" style="margin:0;">Nenhuma ocorrência vinculada a esta unidade.</p>
                        <?php else: ?>
                            <div class="collection">
                                <?php foreach ($chamadosTag as $ch): ?>
                                    <?php
                                    $vinculo = $ch['vinculo_final'] ?? ($ch['tipo_vinculo'] ?? 'autora');
                                    $tagUnidStr = strtoupper(($result['bloco'] ?? '') . ($result['unidade'] ?? ''));
                                    ?>
                                    <a href="index.php?pag=livroDeOcorrencias&id=<?= $ch['id'] ?>" target="_blank"
                                        class="collection-item"
                                        style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; padding:10px 15px;">
                                        <span>
                                            <b>Prot <?= htmlspecialchars($ch['protocolo_vds'] ?? $ch['id']) ?></b> - Bloco
                                            <?= htmlspecialchars($ch['bloco']) ?>/<?= htmlspecialchars($ch['unidade']) ?> <small
                                                class="grey-text">(<?= date('d/m/Y', strtotime($ch['abertura'])) ?>)</small>
                                            <span class="badge blue lighten-5 blue-text text-darken-3"
                                                style="float:none; margin-left:6px; font-weight:600;">Tag:
                                                <?= htmlspecialchars($tagUnidStr) ?></span>
                                        </span>
                                        <span style="display:flex; align-items:center; gap:8px;">
                                            <span class="badge orange lighten-4 orange-text text-darken-3"
                                                style="float:none; font-weight:600;">Vínculo: <?= strtoupper($vinculo) ?></span>
                                            <span class="btn-small waves-effect waves-light purple darken-1 white-text"
                                                style="height:26px; line-height:26px; padding:0 8px; font-size:0.75rem; border-radius:4px;">
                                                Inspecionar Chat <i class="material-icons right tiny"
                                                    style="margin-left:2px;">open_in_new</i>
                                            </span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <?php
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
        $(document).ready(function () {
            $('#btnSyncSupabase').click(function () {
                var rec = $(this).attr('data-rec');
                var $btn = $(this);

                // Desabilita o botão para evitar cliques duplos
                $btn.addClass('disabled').text('Sincronizando...');

                $.ajax({
                    url: 'magnacom-sistema/sync_single.php',
                    type: 'POST',
                    data: { rec: rec },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            var msg = 'Notificação sincronizada!';
                            if (response.data.artigo) {
                                msg += ' Artigo copiado: ' + response.data.artigo;
                            }
                            M.toast({ html: msg, classes: 'green' });
                            // Recarrega a página após 1.5s para exibir o artigo e dados atualizados
                            setTimeout(function () {
                                location.reload();
                            }, 1500);
                        } else {
                            M.toast({ html: 'Erro: ' + response.error, classes: 'red' });
                            $btn.removeClass('disabled').text('Sincronizar Supabase');
                        }
                    },
                    error: function (xhr, status, error) {
                        M.toast({ html: 'Erro ao conectar com o servidor.', classes: 'red' });
                        $btn.removeClass('disabled').text('Sincronizar Supabase');
                    }
                });
            });

            // Buscar anexos do Supabase para esta notificação
            var rec = $('#btnSyncSupabase').attr('data-rec');
            if (rec) {
                $.ajax({
                    url: 'magnacom-sistema/get_attachments.php',
                    type: 'GET',
                    data: { rec: rec },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success && response.attachments && response.attachments.length > 0) {
                            $('#supabaseFilesContainer').show();

                            var $list = $('#supabaseFilesList');
                            $list.empty();

                            response.attachments.forEach(function (file) {
                                var ext = file.nome_arquivo.split('.').pop().toLowerCase();
                                var sizeKb = (file.tamanho_bytes / 1024).toFixed(1);
                                var cardHtml = '';

                                cardHtml += '<div style="background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';

                                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(ext) !== -1) {
                                    cardHtml += '<div style="text-align: center; margin-bottom: 10px; background: #f9f9f9; border-radius: 6px; overflow: hidden; height: 180px; display: flex; align-items: center; justify-content: center; border: 1px solid #f0f0f0;">' +
                                        '<img src="' + file.url + '" class="responsive-img materialboxed" style="max-height: 180px; max-width: 100%; cursor: pointer;" alt="' + file.nome_arquivo + '">' +
                                        '</div>' +
                                        '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">' +
                                        '<span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' + file.nome_arquivo + '">' +
                                        file.nome_arquivo +
                                        '</span>' +
                                        '<a href="' + file.url + '" target="_blank" class="btn-flat btn-small grey lighten-4" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">file_download</i>Baixar</a>' +
                                        '</div>';
                                } else if (['mp4', 'webm', 'ogg', 'mov'].indexOf(ext) !== -1) {
                                    cardHtml += '<div style="text-align: center; margin-bottom: 10px; background: #000; border-radius: 6px; overflow: hidden; height: 180px; display: flex; align-items: center; justify-content: center;">' +
                                        '<video controls style="max-width: 100%; max-height: 180px;"><source src="' + file.url + '" type="video/' + (ext === 'mov' ? 'mp4' : ext) + '">Seu navegador não suporta vídeos.</video>' +
                                        '</div>' +
                                        '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">' +
                                        '<span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' + file.nome_arquivo + '">' +
                                        file.nome_arquivo +
                                        '</span>' +
                                        '<a href="' + file.url + '" target="_blank" class="btn-flat btn-small grey lighten-4" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">file_download</i>Baixar</a>' +
                                        '</div>';
                                } else if (ext === 'pdf') {
                                    cardHtml += '<div style="padding: 10px; background: #ffebee; border-radius: 6px; margin-bottom: 10px; border: 1px solid #ffcdd2; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; gap: 10px;">' +
                                        '<i class="material-icons red-text" style="font-size: 3rem;">picture_as_pdf</i>' +
                                        '<span style="font-weight: 500; font-size: 0.9rem; color: #c62828;">Documento PDF</span>' +
                                        '</div>' +
                                        '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">' +
                                        '<span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' + file.nome_arquivo + '">' +
                                        file.nome_arquivo +
                                        '</span>' +
                                        '<a href="' + file.url + '" target="_blank" class="btn-flat btn-small red lighten-5 red-text" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">open_in_new</i>Abrir</a>' +
                                        '</div>';
                                } else {
                                    cardHtml += '<div style="padding: 10px; background: #eceff1; border-radius: 6px; margin-bottom: 10px; border: 1px solid #cfd8dc; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; gap: 10px;">' +
                                        '<i class="material-icons grey-text text-darken-1" style="font-size: 3rem;">insert_drive_file</i>' +
                                        '<span style="font-weight: 500; font-size: 0.9rem; color: #37474f;">Arquivo .' + ext.toUpperCase() + '</span>' +
                                        '</div>' +
                                        '<div style="display: flex; justify-content: space-between; align-items: center; gap: 5px;">' +
                                        '<span style="font-size: 0.85rem; color: #424242; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="' + file.nome_arquivo + '">' +
                                        file.nome_arquivo +
                                        '</span>' +
                                        '<a href="' + file.url + '" target="_blank" class="btn-flat btn-small grey lighten-4" style="padding: 0 8px; height: 28px; line-height: 28px; font-size:0.75rem;"><i class="material-icons left" style="font-size: 1rem; margin-right: 4px;">file_download</i>Baixar</a>' +
                                        '</div>';
                                }

                                cardHtml += '</div>';
                                $list.append(cardHtml);
                            });

                            // Inicializa zoom das novas imagens injetadas
                            $('.materialboxed').materialbox();
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log('Erro ao buscar anexos remotos:', error);
                    }
                });
            }
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
                <textarea id="messageTextDiligencia" class="browser-default" name="messageText" placeholder="texto"
                    required style="width:100%; min-height:100px; padding:10px"></textarea>
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
                <textarea id="messageTextComment" class="browser-default" name="messageText" placeholder="texto"
                    required style="width:100%; min-height:100px; padding:10px"></textarea>
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
                <?php if (!empty($notifRecurso['notificacao']) && strtoupper($notifRecurso['notificacao']) === 'MULTA'): ?>
                    <td class="opVoto" voto="converter">
                        <div class="chip">Converter</div>
                    </td>
                <?php endif; ?>
            </tr>
        </table>

    </div>
</div>
</div>

<!-- Modal Prévia de E-mail de Diligência -->
<div id="modalPreviewEmailDiligencia" class="modal modal-fixed-footer"
    style="width: 80% !important; max-height: 85% !important;">
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
            <div class="col s12"
                style="border: 1px solid #ccc; padding: 20px; background: white; margin-top: 15px; min-height: 300px;"
                id="previewEmailBody">
                <!-- Conteúdo HTML do e-mail -->
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <a href="#!" id="btnConfirmSendDiligencia" class="waves-effect waves-green btn blue white-text"
            id_dil="">Confirmar e Enviar</a>
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