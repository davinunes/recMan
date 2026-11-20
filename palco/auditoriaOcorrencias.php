<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../classes/database.php";
require_once __DIR__ . "/../classes/repositorio.php";
require_once __DIR__ . "/../classes/vds_auditoria_service.php";
require_once __DIR__ . "/../classes/vds_ocorrencia_service.php";

$usuarioIdConselho = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 1);

// Processar ações AJAX antes da renderização HTML
if (!empty($_REQUEST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_REQUEST['action'];

    // 1. Salvar Sessão Congelada
    if ($action === 'salvar_sessao') {
        $titulo = $_POST['titulo'] ?? '';
        $dataRef = $_POST['data_referencia'] ?? date('Y-m-d');
        $filtrosSalvar = [
            'dt_inicio' => $_POST['dt_inicio'] ?? '',
            'dt_fim' => $_POST['dt_fim'] ?? '',
            'bloco' => $_POST['bloco'] ?? '',
            'oco_tipo' => $_POST['oco_tipo'] ?? '',
            'protocolo' => $_POST['protocolo'] ?? '',
            'finalidade_conselho' => $_POST['finalidade_conselho'] ?? ''
        ];

        $resSalvar = vds_auditoria_criar_sessao_congelada($titulo, $dataRef, $filtrosSalvar, $usuarioIdConselho);
        echo json_encode($resSalvar, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 2. Obter Detalhes Completos de um Chamado para Modal
    if ($action === 'detalhe_chamado') {
        $ocoId = $_REQUEST['ocorrencia_id'] ?? null;
        $proto = $_REQUEST['protocolo'] ?? null;
        $detalhes = vds_get_ocorrencia_detalhe($proto ?: $ocoId, $usuarioIdConselho);
        
        $analise = null;
        if (!empty($detalhes['local'])) {
            $dataRefReq = $_REQUEST['data_referencia'] ?? date('Y-m-d');
            $evs = $detalhes['remoteData']['eventos'] ?? ($detalhes['remoteData']['regs'] ?? []);
            $analise = vds_auditoria_analisar_chamado($detalhes['local'], $evs, $dataRefReq);
        }

        echo json_encode([
            'success' => !empty($detalhes['local']),
            'local' => $detalhes['local'] ?? null,
            'remoteData' => $detalhes['remoteData'] ?? null,
            'analise' => $analise,
            'notasInternas' => $detalhes['notasInternas'] ?? []
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Parâmetros de Filtro e Seleção
$dataReferencia = $_GET['data_referencia'] ?? date('Y-m-d');
$sessaoIdSelecionada = !empty($_GET['sessao_id']) ? (int)$_GET['sessao_id'] : null;

// Multi-seleção de categorias (pode vir como array em oco_tipo[])
$ocoTipoParam = $_GET['oco_tipo'] ?? [];
if (!is_array($ocoTipoParam) && $ocoTipoParam !== '') {
    $ocoTipoParam = explode(',', (string)$ocoTipoParam);
}
$ocoTipoParam = array_filter(array_map('intval', (array)$ocoTipoParam));

$anoFiltro = $_GET['ano'] ?? date('Y');
$trimestreFiltro = $_GET['trimestre'] ?? '';

$dtInicioFiltro = $_GET['dt_inicio'] ?? '';
$dtFimFiltro = $_GET['dt_fim'] ?? '';

// Preencher período automaticamente se trimestre e ano foram selecionados
if (!empty($trimestreFiltro) && !empty($anoFiltro)) {
    $anoInt = (int)$anoFiltro;
    if ($trimestreFiltro === 'Q1') {
        $dtInicioFiltro = "{$anoInt}-01-01";
        $dtFimFiltro = "{$anoInt}-03-31";
    } elseif ($trimestreFiltro === 'Q2') {
        $dtInicioFiltro = "{$anoInt}-04-01";
        $dtFimFiltro = "{$anoInt}-06-30";
    } elseif ($trimestreFiltro === 'Q3') {
        $dtInicioFiltro = "{$anoInt}-07-01";
        $dtFimFiltro = "{$anoInt}-09-30";
    } elseif ($trimestreFiltro === 'Q4') {
        $dtInicioFiltro = "{$anoInt}-10-01";
        $dtFimFiltro = "{$anoInt}-12-31";
    }
}

$filtros = [
    'dt_inicio' => $dtInicioFiltro,
    'dt_fim' => $dtFimFiltro,
    'trimestre' => $trimestreFiltro,
    'ano' => $anoFiltro,
    'bloco' => $_GET['bloco'] ?? '',
    'unidade' => $_GET['unidade'] ?? '',
    'oco_tipo' => $ocoTipoParam,
    'protocolo' => $_GET['protocolo'] ?? '',
    'faixa_tempo' => $_GET['faixa_tempo'] ?? '',
    'situacao_resposta' => $_GET['situacao_resposta'] ?? '',
    'situacao_chamado' => $_GET['situacao_chamado'] ?? '',
    'situacao_providencias' => $_GET['situacao_providencias'] ?? '',
    'finalidade_conselho' => $_GET['finalidade_conselho'] ?? ''
];

$modoHistorico = false;
$sessaoAtual = null;

if ($sessaoIdSelecionada) {
    $sessaoAtual = vds_auditoria_carregar_sessao($sessaoIdSelecionada);
    if ($sessaoAtual) {
        $modoHistorico = true;
        $dataReferencia = $sessaoAtual['data_referencia'];
        $itens = $sessaoAtual['itens'];
        $metricas = $sessaoAtual['totais'];
    }
}

if (!$modoHistorico) {
    $dadosConsulta = vds_auditoria_consultar_tempo_real($filtros, $dataReferencia);
    $itens = $dadosConsulta['itens'];
    $metricas = $dadosConsulta['metricas'];
}

$sessoesSalvas = vds_auditoria_listar_sessoes();

// Mapa de Categorias Conhecidas
$mapaCategorias = [
    114 => ['nome' => 'Livro de Ocorrência', 'cor' => '#0d6efd', 'bg' => '#e7f1ff'],
    86  => ['nome' => 'Fale com o Síndico', 'cor' => '#dc3545', 'bg' => '#ffebee'],
    109 => ['nome' => 'Fale com o Síndico de Bloco', 'cor' => '#b02a37', 'bg' => '#f8d7da'],
    102 => ['nome' => 'Fale com a Administração', 'cor' => '#198754', 'bg' => '#d1e7dd'],
    145 => ['nome' => 'Fale com a Mensageria', 'cor' => '#856404', 'bg' => '#fff3cd'],
    87  => ['nome' => 'Fale com a Portaria', 'cor' => '#795548', 'bg' => '#efebe9'],
    126 => ['nome' => 'Fale com a Supervisão', 'cor' => '#0dcaf0', 'bg' => '#cff4fc'],
    115 => ['nome' => 'Fale com o Conselho', 'cor' => '#6f42c1', 'bg' => '#f3e8ff'],
    247 => ['nome' => 'Monitoramento', 'cor' => '#fd7e14', 'bg' => '#ffe8d6'],
    172 => ['nome' => 'Suporte Acesso', 'cor' => '#495057', 'bg' => '#e9ecef']
];

// Query String base para exportação
$paramsExport = $filtros;
if (is_array($paramsExport['oco_tipo'])) {
    $paramsExport['oco_tipo'] = implode(',', $paramsExport['oco_tipo']);
}
$paramsExport['data_referencia'] = $dataReferencia;
$paramsExport['sessao_id'] = $sessaoIdSelecionada;
$qsExport = http_build_query($paramsExport);
?>

<style>
/* Reset de Selects para impedir conflitos Materialize */
.auditoria-container select.browser-default {
    display: block !important;
    width: 100% !important;
    height: 36px !important;
    background-color: #ffffff !important;
    border: 1px solid #ced4da !important;
    border-radius: 4px !important;
    padding: 4px 8px !important;
    font-size: 0.85rem !important;
    color: #333 !important;
    outline: none !important;
    box-sizing: border-box !important;
}

.auditoria-container .select-wrapper {
    display: none !important; /* Esconde qualquer wrapper duplicado que o Materialize tente injetar */
}

.auditoria-container input[type="date"],
.auditoria-container input[type="text"] {
    display: block !important;
    width: 100% !important;
    height: 36px !important;
    background-color: #ffffff !important;
    border: 1px solid #ced4da !important;
    border-radius: 4px !important;
    padding: 0 10px !important;
    font-size: 0.85rem !important;
    box-sizing: border-box !important;
    margin: 0 !important;
}

.card-kpi {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    background: #fff;
    padding: 14px 18px;
    height: 100%;
    border-top: 4px solid #1a237e;
    box-sizing: border-box;
}

.cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid #dee2e6;
    margin: 2px;
    user-select: none;
    transition: all 0.2s ease;
}
.cat-pill.active {
    background-color: #1a237e !important;
    color: #fff !important;
    border-color: #1a237e !important;
}
</style>

<div class="auditoria-container" style="padding: 15px 25px;">
    <!-- Topo / Cabeçalho -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; margin-bottom: 15px; border-bottom: 2px solid #e0e0e0; padding-bottom: 12px;">
        <div>
            <span class="badge blue darken-4 white-text" style="font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                <i class="material-icons tiny left" style="line-height: inherit;">gavel</i> Art. 12 do Regimento Interno
            </span>
            <h4 style="margin: 6px 0 2px 0; font-weight: 700; color: #1a237e; font-size: 1.8rem;">
                Auditoria de Ocorrências & Prazos
            </h4>
            <p style="margin: 0; color: #555; font-size: 0.9rem;">
                Apuração fática e documental do prazo de até 7 dias para retorno do Síndico e Subsíndicos e acompanhamento de providências.
            </p>
        </div>

        <div style="display: flex; gap: 8px; align-items: center; margin-top: 8px; flex-wrap: wrap;">
            <!-- Seletor de Sessões Salvas -->
            <div style="display: inline-flex; align-items: center; background: #f8f9fa; border: 1px solid #ced4da; border-radius: 4px; padding: 2px 8px;">
                <i class="material-icons tiny grey-text text-darken-2" style="margin-right: 4px;">history</i>
                <select id="selectSessao" class="browser-default" style="width: auto; height: 32px; border: none; background: transparent; font-size: 0.85rem; padding: 0 4px;" onchange="alternarSessao(this.value)">
                    <option value="">-- Apuração em Tempo Real --</option>
                    <?php foreach ($sessoesSalvas as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $sessaoIdSelecionada == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['identificador_execucao']) ?> (<?= date('d/m/Y', strtotime($s['data_referencia'])) ?> - <?= $s['total_chamados'] ?> ch.)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Botão Salvar Snapshot -->
            <?php if (!$modoHistorico): ?>
                <button type="button" onclick="abrirModalSalvarSessao()" class="btn-small waves-effect waves-light green darken-3" style="text-transform: none; font-weight: 600; height: 34px; line-height: 34px;">
                    <i class="material-icons tiny left">archive</i> Salvar Apuração Formal
                </button>
            <?php else: ?>
                <a href="index.php?pag=auditoriaOcorrencias" class="btn-small waves-effect waves-light grey darken-2" style="text-transform: none; height: 34px; line-height: 34px;">
                    <i class="material-icons tiny left">refresh</i> Voltar para Tempo Real
                </a>
            <?php endif; ?>

            <!-- Botões de Exportação -->
            <a href="palco/exportar_auditoria.php?formato=csv&<?= $qsExport ?>" class="btn-small waves-effect waves-light teal darken-2" title="Exportar tabela completa em CSV (Excel)" style="text-transform: none; height: 34px; line-height: 34px;">
                <i class="material-icons tiny left">table_view</i> CSV
            </a>
            <a href="palco/exportar_auditoria.php?formato=json&<?= $qsExport ?>" class="btn-small waves-effect waves-light indigo darken-1" title="Exportar dados estruturados em JSON" style="text-transform: none; height: 34px; line-height: 34px;">
                <i class="material-icons tiny left">code</i> JSON
            </a>
            <a href="palco/exportar_auditoria.php?formato=html&<?= $qsExport ?>" target="_blank" class="btn-small waves-effect waves-light purple darken-2" title="Imprimir Relatório Formal" style="text-transform: none; height: 34px; line-height: 34px;">
                <i class="material-icons tiny left">print</i> Relatório
            </a>
        </div>
    </div>

    <?php if ($modoHistorico): ?>
        <div style="background: #e8eaf6; border-left: 5px solid #3f51b5; padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="color: #1a237e;">Sessão Histórica Congelada:</strong> <?= htmlspecialchars($sessaoAtual['titulo']) ?> (<?= htmlspecialchars($sessaoAtual['identificador_execucao']) ?>)<br>
                <small class="grey-text text-darken-2">Coletado em: <?= date('d/m/Y H:i:s', strtotime($sessaoAtual['data_coleta'])) ?> | Data de Referência: <?= date('d/m/Y', strtotime($sessaoAtual['data_referencia'])) ?> | Responsável: <?= htmlspecialchars($sessaoAtual['conselheiro_nome'] ?? 'Conselheiro') ?></small>
            </div>
            <span class="badge indigo white-text" style="font-weight: bold;">EVIDÊNCIA CONGELADA</span>
        </div>
    <?php endif; ?>

    <!-- PAINEL DE FILTROS AVANÇADOS -->
    <div class="card" style="margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
        <div class="card-content" style="padding: 16px;">
            <form method="GET" action="index.php" id="formFiltros">
                <input type="hidden" name="pag" value="auditoriaOcorrencias">
                <?php if ($sessaoIdSelecionada): ?>
                    <input type="hidden" name="sessao_id" value="<?= $sessaoIdSelecionada ?>">
                <?php endif; ?>

                <!-- Linha 1: Data de Corte, Trimestre e Período de Abertura -->
                <div class="row" style="margin-bottom: 10px;">
                    <!-- Data de Referência (Corte) -->
                    <div class="col s12 m3 l3">
                        <label style="font-weight: bold; color: #1a237e; font-size: 0.85rem;">Data de Referência (Corte):</label>
                        <input type="date" name="data_referencia" id="data_referencia" value="<?= htmlspecialchars($dataReferencia) ?>" <?= $modoHistorico ? 'disabled' : '' ?>>
                    </div>

                    <!-- Filtro Rápido por Trimestre -->
                    <div class="col s12 m3 l3">
                        <label style="font-weight: bold; color: #1a237e; font-size: 0.85rem;">Filtrar por Trimestre:</label>
                        <div style="display: flex; gap: 4px;">
                            <select name="ano" id="filtroAno" class="browser-default" style="width: 45%;" onchange="aplicarTrimestre()">
                                <?php for ($y = (int)date('Y'); $y >= 2024; $y--): ?>
                                    <option value="<?= $y ?>" <?= $anoFiltro == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="trimestre" id="filtroTrimestre" class="browser-default" style="width: 55%;" onchange="aplicarTrimestre()">
                                <option value="">Trimestre...</option>
                                <option value="Q1" <?= $trimestreFiltro === 'Q1' ? 'selected' : '' ?>>1º Trim (Jan-Mar)</option>
                                <option value="Q2" <?= $trimestreFiltro === 'Q2' ? 'selected' : '' ?>>2º Trim (Abr-Jun)</option>
                                <option value="Q3" <?= $trimestreFiltro === 'Q3' ? 'selected' : '' ?>>3º Trim (Jul-Set)</option>
                                <option value="Q4" <?= $trimestreFiltro === 'Q4' ? 'selected' : '' ?>>4º Trim (Out-Dez)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Abertura De e Até -->
                    <div class="col s6 m3 l3">
                        <label style="font-weight: bold; color: #333; font-size: 0.85rem;">Abertura De:</label>
                        <input type="date" name="dt_inicio" id="dt_inicio" value="<?= htmlspecialchars($filtros['dt_inicio']) ?>">
                    </div>
                    <div class="col s6 m3 l3">
                        <label style="font-weight: bold; color: #333; font-size: 0.85rem;">Abertura Até:</label>
                        <input type="date" name="dt_fim" id="dt_fim" value="<?= htmlspecialchars($filtros['dt_fim']) ?>">
                    </div>
                </div>

                <!-- Linha 2: Multi-seleção de Categorias -->
                <div class="row" style="margin-bottom: 10px; background: #fafafa; border: 1px solid #e9ecef; border-radius: 6px; padding: 10px;">
                    <div class="col s12" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
                        <label style="font-weight: bold; color: #333; font-size: 0.85rem;">
                            Categorias / Assunto (Multi-seleção):
                        </label>
                        <div style="font-size: 0.8rem;">
                            <a href="javascript:void(0)" onclick="selecionarTodasCategorias(true)" class="blue-text" style="margin-right: 10px;">Marcar Todas</a>
                            <a href="javascript:void(0)" onclick="selecionarTodasCategorias(false)" class="grey-text">Limpar Seleção</a>
                        </div>
                    </div>
                    <div class="col s12" style="display: flex; flex-wrap: wrap; gap: 4px;">
                        <?php foreach ($mapaCategorias as $catId => $catInfo): 
                            $isSelected = in_array($catId, $ocoTipoParam);
                        ?>
                            <label class="cat-pill <?= $isSelected ? 'active' : '' ?>" id="cat_pill_<?= $catId ?>" style="background: <?= $catInfo['bg'] ?>; color: <?= $catInfo['cor'] ?>;">
                                <input type="checkbox" name="oco_tipo[]" value="<?= $catId ?>" <?= $isSelected ? 'checked' : '' ?> style="display: none;" onchange="togglePill(<?= $catId ?>, this.checked)">
                                <span><?= htmlspecialchars($catInfo['nome']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Linha 3: Filtros Ortogonais (Bloco, Situação Resposta, Status VDS, Finalidade Conselho, Faixa) -->
                <div class="row" style="margin-bottom: 0;">
                    <!-- Bloco -->
                    <div class="col s6 m2 l2">
                        <label style="font-weight: bold; color: #333; font-size: 0.85rem;">Bloco:</label>
                        <select name="bloco" class="browser-default">
                            <option value="">Todos os Blocos</option>
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $b): ?>
                                <option value="<?= $b ?>" <?= $filtros['bloco'] === $b ? 'selected' : '' ?>>Bloco <?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Situação da Resposta (Art. 12) -->
                    <div class="col s6 m3 l3">
                        <label style="font-weight: bold; color: #333; font-size: 0.85rem;">Resposta (Art. 12):</label>
                        <select name="situacao_resposta" class="browser-default">
                            <option value="">Todas as Situações</option>
                            <option value="respondido_no_prazo" <?= $filtros['situacao_resposta'] === 'respondido_no_prazo' ? 'selected' : '' ?>>Respondido no prazo (≤ 7d)</option>
                            <option value="respondido_com_atraso" <?= $filtros['situacao_resposta'] === 'respondido_com_atraso' ? 'selected' : '' ?>>Respondido com atraso (> 7d)</option>
                            <option value="sem_resposta" <?= $filtros['situacao_resposta'] === 'sem_resposta' ? 'selected' : '' ?>>Sem resposta registrada (> 7d)</option>
                            <option value="em_prazo_aguardando" <?= $filtros['situacao_resposta'] === 'em_prazo_aguardando' ? 'selected' : '' ?>>Em prazo regulamentar (< 7d)</option>
                        </select>
                    </div>

                    <!-- Status Oficial VDS -->
                    <div class="col s6 m2 l2">
                        <label style="font-weight: bold; color: #333; font-size: 0.85rem;">Status VDS:</label>
                        <select name="situacao_chamado" class="browser-default">
                            <option value="">Todos</option>
                            <option value="aberto" <?= $filtros['situacao_chamado'] === 'aberto' ? 'selected' : '' ?>>Aberto</option>
                            <option value="em_andamento" <?= $filtros['situacao_chamado'] === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="encerrado" <?= $filtros['situacao_chamado'] === 'encerrado' ? 'selected' : '' ?>>Encerrado</option>
                        </select>
                    </div>

                    <!-- Sugestão do Conselho: Finalidade Atingida -->
                    <div class="col s6 m3 l3">
                        <label style="font-weight: bold; color: #1a237e; font-size: 0.85rem;">Finalidade (Conselho):</label>
                        <select name="finalidade_conselho" class="browser-default">
                            <option value="">Todas as Classificações</option>
                            <option value="atingida" <?= $filtros['finalidade_conselho'] === 'atingida' ? 'selected' : '' ?>>✓ Finalidade Atingida (Resolvido Local)</option>
                            <option value="pendente" <?= $filtros['finalidade_conselho'] === 'pendente' ? 'selected' : '' ?>>• Pendente de Finalidade</option>
                        </select>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="col s12 m2 l2" style="display: flex; gap: 6px; align-items: flex-end; height: 58px;">
                        <button type="submit" class="btn-small waves-effect waves-light blue darken-3" style="height: 36px; line-height: 36px; flex: 1; font-weight: 600;">
                            <i class="material-icons tiny left">filter_list</i> Filtrar
                        </button>
                        <a href="index.php?pag=auditoriaOcorrencias" class="btn-small waves-effect waves-light grey lighten-1" title="Limpar Filtros" style="height: 36px; line-height: 36px; padding: 0 10px;">
                            <i class="material-icons tiny">clear</i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- CARDS DE INDICADORES GERAIS (CONFORMIDADE DO ART. 12) -->
    <div class="row" style="margin-bottom: 15px;">
        <div class="col s12 m6 l3">
            <div class="card-kpi" style="border-top-color: #1a237e;">
                <span class="grey-text text-darken-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Universo Auditado</span>
                <h4 style="margin: 4px 0; font-weight: bold; color: #1a237e; font-size: 1.8rem;"><?= $metricas['total_chamados'] ?></h4>
                <div style="font-size: 0.78rem; color: #555;">
                    <b><?= $metricas['pendentes_total'] ?></b> pendentes | <b><?= $metricas['encerrados'] ?></b> encerrados
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card-kpi" style="border-top-color: #2e7d32;">
                <span class="grey-text text-darken-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Respondidos no Prazo (≤ 7d)</span>
                <h4 style="margin: 4px 0; font-weight: bold; color: #2e7d32; font-size: 1.8rem;"><?= $metricas['respondidos_no_prazo'] ?></h4>
                <div style="font-size: 0.78rem; color: #2e7d32; font-weight: bold;">
                    <?= $metricas['pct_respondidos_no_prazo'] ?>% do total auditável
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card-kpi" style="border-top-color: #c62828;">
                <span class="grey-text text-darken-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Sem Resposta (> 7d)</span>
                <h4 style="margin: 4px 0; font-weight: bold; color: #c62828; font-size: 1.8rem;"><?= $metricas['sem_resposta_extrapolada'] ?></h4>
                <div style="font-size: 0.78rem; color: #c62828; font-weight: bold;">
                    <?= $metricas['pct_sem_resposta_extrapolada'] ?>% extrapolou o prazo
                </div>
            </div>
        </div>

        <div class="col s12 m6 l3">
            <div class="card-kpi" style="border-top-color: #ef6c00;">
                <span class="grey-text text-darken-1" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Finalidade (Conselho)</span>
                <h4 style="margin: 4px 0; font-weight: bold; color: #283593; font-size: 1.8rem;"><?= $metricas['conselho_atingiu_finalidade'] ?></h4>
                <div style="font-size: 0.78rem; color: #555;">
                    Classificados como resolvidos localmente
                </div>
            </div>
        </div>
    </div>

    <!-- CARDS DE ESTATÍSTICAS DE TEMPO E PROVIDÊNCIAS -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col s12 m3 l3">
            <div class="card-panel" style="padding: 10px 14px; margin: 0 0 10px 0; border: 1px solid #e0e0e0; border-radius: 6px; background:#fff;">
                <span class="grey-text text-darken-2" style="font-size: 0.8rem;">Tempo Médio até 1ª Resposta:</span>
                <div style="font-size: 1.3rem; font-weight: bold; color: #0d47a1;"><?= $metricas['tempo_medio_resposta'] ?> dias</div>
                <small class="grey-text">Mediana: <?= $metricas['tempo_mediano_resposta'] ?> dias</small>
            </div>
        </div>
        <div class="col s12 m3 l3">
            <div class="card-panel" style="padding: 10px 14px; margin: 0 0 10px 0; border: 1px solid #e0e0e0; border-radius: 6px; background:#fff;">
                <span class="grey-text text-darken-2" style="font-size: 0.8rem;">Maior Tempo Sem Resposta:</span>
                <div style="font-size: 1.3rem; font-weight: bold; color: #c62828;"><?= $metricas['maior_tempo_sem_resposta'] ?> dias</div>
                <small class="grey-text">Chamado mais crítico identificado</small>
            </div>
        </div>
        <div class="col s12 m3 l3">
            <div class="card-panel" style="padding: 10px 14px; margin: 0 0 10px 0; border: 1px solid #e0e0e0; border-radius: 6px; background:#fff;">
                <span class="grey-text text-darken-2" style="font-size: 0.8rem;">Tempo Médio até Encerramento:</span>
                <div style="font-size: 1.3rem; font-weight: bold; color: #2e7d32;"><?= $metricas['tempo_medio_encerramento'] ?> dias</div>
                <small class="grey-text">Para os chamados finalizados</small>
            </div>
        </div>
        <div class="col s12 m3 l3">
            <div class="card-panel" style="padding: 10px 14px; margin: 0 0 10px 0; border: 1px solid #e0e0e0; border-radius: 6px; background:#fff;">
                <span class="grey-text text-darken-2" style="font-size: 0.8rem;">Providências Comprovadas:</span>
                <div style="font-size: 1.3rem; font-weight: bold; color: #4a148c;"><?= $metricas['com_providencia'] ?> chamados</div>
                <small class="grey-text"><?= $metricas['apenas_manifestacao'] ?> tiveram apenas manifestação textual</small>
            </div>
        </div>
    </div>

    <!-- TABELA PRINCIPAL DE CHAMADOS AUDITADOS -->
    <div class="card" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
        <div class="card-content" style="padding: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
                <h5 style="margin: 0; font-size: 1.2rem; font-weight: bold; color: #1a237e;">
                    Registros Fáticos Auditados (<?= count($itens) ?> chamados)
                </h5>
                <small class="grey-text">
                    * Prazos calculados por períodos de 24 horas contados do registro de abertura.
                </small>
            </div>

            <div class="table-responsive">
                <table id="tabelaAuditoria" class="striped highlight" style="font-size: 0.85rem; width: 100%;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th>Protocolo</th>
                            <th>Abertura</th>
                            <th>Local</th>
                            <th>Categoria</th>
                            <th>Tempo Decorrido</th>
                            <th>Resposta (Art. 12)</th>
                            <th>1ª Manifestação</th>
                            <th>Providência</th>
                            <th>Status VDS</th>
                            <th>Finalidade (Conselho)</th>
                            <th style="text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $it): 
                            $badgeFaixa = '#e3f2fd; color:#0d47a1;';
                            if ($it['faixa_tempo'] === 'mais_365_dias') {
                                $badgeFaixa = 'background:#ffebee; color:#b71c1c; border:1px solid #ffcdd2;';
                            } elseif ($it['faixa_tempo'] === '91_a_365_dias') {
                                $badgeFaixa = 'background:#fff3e0; color:#e65100; border:1px solid #ffe0b2;';
                            } elseif ($it['faixa_tempo'] === '31_a_90_dias') {
                                $badgeFaixa = 'background:#fffde7; color:#f57f17; border:1px solid #fff9c4;';
                            } elseif ($it['faixa_tempo'] === '8_a_30_dias') {
                                $badgeFaixa = 'background:#fbe9e7; color:#d84315; border:1px solid #ffccbc;';
                            } else {
                                $badgeFaixa = 'background:#e8f5e9; color:#1b5e20; border:1px solid #c8e6c9;';
                            }

                            // Badge Situação da Resposta
                            $badgeRespHtml = '';
                            if ($it['situacao_resposta'] === 'respondido_no_prazo') {
                                $badgeRespHtml = '<span style="display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:12px; background:#e8f5e9; color:#2e7d32; font-weight:600; border:1px solid #c8e6c9;" title="Primeira manifestação em ' . $it['primeira_resposta_tempo_dias'] . ' dias"><i class="material-icons tiny">check</i> No Prazo (' . $it['primeira_resposta_tempo_dias'] . 'd)</span>';
                            } elseif ($it['situacao_resposta'] === 'respondido_com_atraso') {
                                $badgeRespHtml = '<span style="display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:12px; background:#fff3e0; color:#ef6c00; font-weight:600; border:1px solid #ffe0b2;" title="Primeira manifestação em ' . $it['primeira_resposta_tempo_dias'] . ' dias (prazo extrapolado)"><i class="material-icons tiny">warning</i> Atraso (' . $it['primeira_resposta_tempo_dias'] . 'd)</span>';
                            } elseif ($it['situacao_resposta'] === 'sem_resposta') {
                                $badgeRespHtml = '<span style="display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:12px; background:#ffebee; color:#c62828; font-weight:bold; border:1px solid #ffcdd2;" title="Prazo de 7 dias extrapolado sem resposta"><i class="material-icons tiny">error</i> Sem Resposta</span>';
                            } elseif ($it['situacao_resposta'] === 'em_prazo_aguardando') {
                                $badgeRespHtml = '<span style="display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:12px; background:#e3f2fd; color:#0d47a1; font-weight:600; border:1px solid #bbdefb;" title="Aberto há menos de 7 dias. Em prazo legal."><i class="material-icons tiny">schedule</i> Em Prazo (< 7d)</span>';
                            } else {
                                $badgeRespHtml = '<span class="grey-text">Dados Insuficientes</span>';
                            }

                            // Badge Providências (Encaminhamento NÃO é providência)
                            $badgeProvHtml = '';
                            if ($it['situacao_providencias'] === 'providencia_identificavel') {
                                $badgeProvHtml = '<span style="color:#2e7d32; font-weight:600;" title="' . htmlspecialchars($it['primeira_providencia_descricao'] ?? '') . '"><i class="material-icons tiny left" style="color:#2e7d32;">task_alt</i> Comprovada</span>';
                            } elseif ($it['situacao_providencias'] === 'apenas_manifestacao') {
                                $badgeProvHtml = '<span style="color:#666;" title="Apenas troca de mensagens textuais ou encaminhamentos internos sem comprovação formal de ato executivo"><i class="material-icons tiny left" style="color:#9e9e9e;">chat_bubble_outline</i> Apenas texto</span>';
                            } else {
                                $badgeProvHtml = '<span style="color:#999;">-</span>';
                            }

                            $catInfoRow = $mapaCategorias[$it['categoria_oco_tipo']] ?? ['nome' => 'Ocorrência', 'cor' => '#333', 'bg' => '#eee'];
                        ?>
                            <tr>
                                <!-- 1. Protocolo -->
                                <td>
                                    <strong><?= htmlspecialchars($it['protocolo_vds']) ?></strong>
                                    <br><small class="grey-text">ID <?= $it['ocorrencia_id'] ?></small>
                                </td>

                                <!-- 2. Abertura -->
                                <td>
                                    <?= htmlspecialchars($it['data_abertura_formatada'] ?? substr($it['data_abertura_iso'] ?? '', 0, 16)) ?>
                                </td>

                                <!-- 3. Local -->
                                <td>
                                    Bl <b><?= htmlspecialchars($it['bloco'] ?? '-') ?></b> / <?= htmlspecialchars($it['unidade'] ?? '-') ?>
                                </td>

                                <!-- 4. Categoria (Presente e Alinhada com TH) -->
                                <td>
                                    <span style="display:inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.72rem; font-weight: 600; background: <?= $catInfoRow['bg'] ?>; color: <?= $catInfoRow['cor'] ?>;">
                                        <?= htmlspecialchars($catInfoRow['nome']) ?>
                                    </span>
                                </td>

                                <!-- 5. Tempo Transcorrido -->
                                <td>
                                    <span style="display:inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; <?= $badgeFaixa ?>">
                                        <?= $it['tempo_decorrido_dias'] ?> dias
                                    </span>
                                </td>

                                <!-- 6. Situação da Resposta -->
                                <td>
                                    <?= $badgeRespHtml ?>
                                </td>

                                <!-- 7. 1ª Manifestação da Gestão -->
                                <td>
                                    <?php if (!empty($it['primeira_resposta_data'])): ?>
                                        <div style="font-size: 0.8rem; font-weight: 600;">
                                            <?= !empty($it['primeira_resposta_data_formatada']) ? $it['primeira_resposta_data_formatada'] : date('d/m/Y H:i', strtotime($it['primeira_resposta_data'])) ?>
                                        </div>
                                        <small class="grey-text">
                                            <?= htmlspecialchars($it['primeira_resposta_por'] ?? '') ?> 
                                            (<?= htmlspecialchars(ucfirst($it['primeira_resposta_ator'] ?? '')) ?>)
                                        </small>
                                    <?php else: ?>
                                        <span class="grey-text">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- 8. Providência Formal -->
                                <td>
                                    <?= $badgeProvHtml ?>
                                </td>

                                <!-- 9. Status Oficial VDS -->
                                <td>
                                    <?php if ($it['situacao_chamado'] === 'encerrado'): ?>
                                        <span class="badge grey darken-1 white-text" style="font-size:0.75rem; border-radius:3px;">Encerrado</span>
                                    <?php elseif ($it['situacao_chamado'] === 'em_andamento'): ?>
                                        <span class="badge orange darken-2 white-text" style="font-size:0.75rem; border-radius:3px;">Em Andamento</span>
                                    <?php else: ?>
                                        <span class="badge blue darken-2 white-text" style="font-size:0.75rem; border-radius:3px;">Aberto</span>
                                    <?php endif; ?>
                                </td>

                                <!-- 10. Sugestão / Avaliação do Conselho (Finalidade Atingida) -->
                                <td>
                                    <?php if (!empty($it['conselho_resolvido'])): ?>
                                        <span style="display:inline-flex; align-items:center; gap:3px; padding:2px 6px; border-radius:4px; font-size:0.72rem; font-weight:600; background:#e8f5e9; color:#1b5e20; border:1px solid #c8e6c9;" title="Conselho classificou localmente como Resolvido / Finalidade Atingida">
                                            <i class="material-icons tiny">check_circle</i> Finalidade Atingida
                                        </span>
                                    <?php else: ?>
                                        <span style="display:inline-flex; align-items:center; gap:3px; padding:2px 6px; border-radius:4px; font-size:0.72rem; color:#666; background:#f5f5f5; border:1px solid #e0e0e0;">
                                            <i class="material-icons tiny">pending</i> Pendente Conselho
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- 11. Ações (Total de 11 colunas exatas) -->
                                <td style="text-align: center; white-space: nowrap;">
                                    <button type="button" class="btn-small btn-flat waves-effect" title="Ver Linha do Tempo e Evidências" onclick="abrirLinhaDoTempo(<?= $it['ocorrencia_id'] ?>, '<?= htmlspecialchars($it['protocolo_vds']) ?>')">
                                        <i class="material-icons blue-text text-darken-3" style="font-size: 1.25rem;">timeline</i>
                                    </button>
                                    <a href="index.php?pag=livroDeOcorrencias&protocolo=<?= urlencode($it['protocolo_vds']) ?>" target="_blank" class="btn-small btn-flat waves-effect" title="Abrir no Livro de Ocorrências">
                                        <i class="material-icons grey-text text-darken-2" style="font-size: 1.25rem;">open_in_new</i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Salvar Apuração Formal / Snapshot Congelado -->
<div id="modalSalvarSessao" class="modal" style="max-width: 500px; border-radius: 8px;">
    <div class="modal-content" style="padding: 20px;">
        <h5 style="margin-top:0; color:#1a237e; font-weight:bold;">Congelar Apuração Probatória</h5>
        <p style="font-size:0.85rem; color:#555;">
            Esta ação gravará uma fotografia imutável dos <b><?= count($itens) ?> chamados</b> auditados com a data de corte <b><?= date('d/m/Y', strtotime($dataReferencia)) ?></b>, garantindo preservação para a Assembleia Geral mesmo se os chamados forem alterados no sistema oficial posteriormente.
        </p>
        <div style="margin-top: 15px;">
            <label style="font-weight: bold; color: #333; font-size: 0.85rem;">Título ou Finalidade da Apuração:</label>
            <input type="text" id="tituloSessaoInput" placeholder="Ex: Apuração Periódica - Assembleia Geral Extraordinária">
        </div>
    </div>
    <div class="modal-footer" style="background:#f5f5f5; padding: 10px 20px;">
        <button type="button" class="modal-close btn-flat waves-effect">Cancelar</button>
        <button type="button" onclick="confirmarSalvarSessao()" class="btn waves-effect waves-light green darken-3" id="btnConfirmarSalvar">
            <i class="material-icons tiny left">lock</i> Congelar Snapshot
        </button>
    </div>
</div>

<!-- MODAL: Linha do Tempo e Detalhamento de Evidências -->
<div id="modalTimeline" class="modal modal-fixed-footer" style="max-width: 800px; height: 80%; border-radius: 8px;">
    <div class="modal-content" style="padding: 20px;">
        <div id="timelineLoading" style="text-align: center; padding: 40px;">
            <div class="preloader-wrapper small active">
                <div class="spinner-layer spinner-blue-only">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
            </div>
            <p style="color: #666; margin-top: 10px;">Carregando linha do tempo e eventos auditados...</p>
        </div>

        <div id="timelineConteudo" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; margin-bottom: 15px;">
                <div>
                    <h5 style="margin:0; font-weight:bold; color:#1a237e;" id="modalTituloProt">Protocolo</h5>
                    <span class="grey-text" id="modalSubtitulo">Bloco / Unidade</span>
                </div>
                <div id="modalBadgeStatus"></div>
            </div>

            <!-- Resumo das 4 Dimensões neste Chamado -->
            <div class="row" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 10px; margin-bottom: 15px;">
                <div class="col s6 m3" style="border-right: 1px solid #dee2e6;">
                    <small class="grey-text">Tempo Decorrido</small>
                    <div style="font-weight: bold; color: #0d47a1;" id="modalTempoDecorrido">-</div>
                </div>
                <div class="col s6 m3" style="border-right: 1px solid #dee2e6;">
                    <small class="grey-text">Resposta (Art. 12)</small>
                    <div style="font-weight: bold;" id="modalRespostaStatus">-</div>
                </div>
                <div class="col s6 m3" style="border-right: 1px solid #dee2e6;">
                    <small class="grey-text">Situação VDS</small>
                    <div style="font-weight: bold;" id="modalSituacaoChamado">-</div>
                </div>
                <div class="col s6 m3">
                    <small class="grey-text">Conselho (Finalidade)</small>
                    <div style="font-weight: bold;" id="modalConselhoStatus">-</div>
                </div>
            </div>

            <!-- Linha do Tempo Visual dos Eventos -->
            <h6 style="font-weight: bold; color: #333; margin-top: 20px; border-bottom: 1px solid #ddd; padding-bottom: 6px;">
                Linha do Tempo Cronológica dos Eventos Registrados na API
            </h6>
            <div id="eventosTimelineContainer" style="margin-top: 15px; position: relative; padding-left: 20px; border-left: 3px solid #cfd8dc;">
                <!-- Preenchido via JavaScript -->
            </div>
        </div>
    </div>
    <div class="modal-footer" style="background: #f5f5f5;">
        <button type="button" class="modal-close btn-flat waves-effect">Fechar</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    // Destruir instância anterior se existir para evitar duplicação
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#tabelaAuditoria')) {
        $('#tabelaAuditoria').DataTable().destroy();
    }

    // Inicialização Limpa do DataTables com 11 colunas idênticas no thead e tbody
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#tabelaAuditoria').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
            },
            pageLength: 25,
            order: [[1, 'desc']],
            responsive: true,
            columnDefs: [
                { orderable: false, targets: 10 } // Desativa ordenação na coluna de Ações
            ]
        });
    }
});

function togglePill(catId, isChecked) {
    var pill = document.getElementById('cat_pill_' + catId);
    if (pill) {
        if (isChecked) {
            pill.classList.add('active');
        } else {
            pill.classList.remove('active');
        }
    }
}

function selecionarTodasCategorias(marcar) {
    var checkboxes = document.querySelectorAll('input[name="oco_tipo[]"]');
    checkboxes.forEach(function(cb) {
        cb.checked = marcar;
        var pill = document.getElementById('cat_pill_' + cb.value);
        if (pill) {
            if (marcar) {
                pill.classList.add('active');
            } else {
                pill.classList.remove('active');
            }
        }
    });
}

function aplicarTrimestre() {
    var ano = document.getElementById('filtroAno').value;
    var trim = document.getElementById('filtroTrimestre').value;
    var dtIni = document.getElementById('dt_inicio');
    var dtFim = document.getElementById('dt_fim');

    if (!trim) return;

    if (trim === 'Q1') {
        dtIni.value = ano + '-01-01';
        dtFim.value = ano + '-03-31';
    } else if (trim === 'Q2') {
        dtIni.value = ano + '-04-01';
        dtFim.value = ano + '-06-30';
    } else if (trim === 'Q3') {
        dtIni.value = ano + '-07-01';
        dtFim.value = ano + '-09-30';
    } else if (trim === 'Q4') {
        dtIni.value = ano + '-10-01';
        dtFim.value = ano + '-12-31';
    }
}

function alternarSessao(sessaoId) {
    if (sessaoId) {
        window.location.href = 'index.php?pag=auditoriaOcorrencias&sessao_id=' + encodeURIComponent(sessaoId);
    } else {
        window.location.href = 'index.php?pag=auditoriaOcorrencias';
    }
}

function abrirModalSalvarSessao() {
    var elem = document.getElementById('modalSalvarSessao');
    var instance = M.Modal.getInstance(elem);
    document.getElementById('tituloSessaoInput').value = 'Auditoria Formal de Ocorrências - ' + new Date().toLocaleDateString('pt-BR');
    instance.open();
}

function confirmarSalvarSessao() {
    var titulo = document.getElementById('tituloSessaoInput').value.trim();
    if (!titulo) {
        M.toast({ html: 'Informe um título descritivo para a apuração.', classes: 'red' });
        return;
    }

    var btn = document.getElementById('btnConfirmarSalvar');
    btn.disabled = true;
    btn.innerHTML = '<i class="material-icons tiny left">hourglass_empty</i> Salvando...';

    var dataRef = document.getElementById('data_referencia').value;
    var formData = $('#formFiltros').serializeArray();
    formData.push({ name: 'action', value: 'salvar_sessao' });
    formData.push({ name: 'titulo', value: titulo });
    formData.push({ name: 'data_referencia', value: dataRef });

    $.ajax({
        url: 'palco/auditoriaOcorrencias.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="material-icons tiny left">lock</i> Congelar Snapshot';
            if (res.success) {
                M.toast({ html: 'Apuração formal salva com sucesso! (ID: ' + res.identificador + ')', classes: 'green' });
                setTimeout(function() {
                    window.location.href = 'index.php?pag=auditoriaOcorrencias&sessao_id=' + res.sessao_id;
                }, 1000);
            } else {
                M.toast({ html: res.message || 'Erro ao salvar apuração.', classes: 'red' });
            }
        },
        error: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="material-icons tiny left">lock</i> Congelar Snapshot';
            M.toast({ html: 'Erro de comunicação ao salvar apuração.', classes: 'red' });
        }
    });
}

function abrirLinhaDoTempo(ocoId, protocolo) {
    var elem = document.getElementById('modalTimeline');
    var instance = M.Modal.getInstance(elem);
    instance.open();

    document.getElementById('timelineLoading').style.display = 'block';
    document.getElementById('timelineConteudo').style.display = 'none';

    var dataRef = document.getElementById('data_referencia') ? document.getElementById('data_referencia').value : '<?= $dataReferencia ?>';

    $.ajax({
        url: 'palco/auditoriaOcorrencias.php',
        type: 'GET',
        data: {
            action: 'detalhe_chamado',
            ocorrencia_id: ocoId,
            protocolo: protocolo,
            data_referencia: dataRef
        },
        dataType: 'json',
        success: function(res) {
            document.getElementById('timelineLoading').style.display = 'none';
            document.getElementById('timelineConteudo').style.display = 'block';

            if (!res.success || !res.local) {
                document.getElementById('eventosTimelineContainer').innerHTML = '<p class="grey-text">Detalhes do chamado não encontrados na base.</p>';
                return;
            }

            var loc = res.local;
            var analise = res.analise || {};

            document.getElementById('modalTituloProt').innerText = 'Protocolo VDS #' + (loc.protocolo_vds || loc.id);
            document.getElementById('modalSubtitulo').innerText = 'Bloco ' + (loc.bloco || '-') + ' / Unidade ' + (loc.unidade || '-') + ' | Abertura: ' + (loc.abertura || '-');
            
            document.getElementById('modalTempoDecorrido').innerText = (analise.tempo_decorrido_dias || 0) + ' dias';
            document.getElementById('modalRespostaStatus').innerText = analise.situacao_resposta || '-';
            document.getElementById('modalSituacaoChamado').innerText = (analise.situacao_chamado || '-').toUpperCase();
            document.getElementById('modalConselhoStatus').innerText = analise.conselho_rotulo || 'Pendente';

            // Montar Lista de Eventos
            var eventos = (res.remoteData && res.remoteData.eventos) ? res.remoteData.eventos : [];
            var html = '';

            if (eventos.length === 0) {
                html = '<div style="padding:15px; color:#888;">Nenhum evento detalhado retornado pela API para este chamado.</div>';
            } else {
                eventos.forEach(function(ev, idx) {
                    var cargo = ev.cargo || '';
                    var por = ev.por || 'Desconhecido';
                    var dthora = ev.dtHora || ev.dthora || '';
                    var msg = ev.mensagem || '';
                    var isEncaminhamento = (ev.tipo === 79 || msg.toLowerCase().indexOf('encaminhado') !== -1);

                    var badgeAtor = 'background:#e0e0e0; color:#333;';
                    var cargoLower = cargo.toLowerCase();
                    if (cargoLower.indexOf('conselh') !== -1) {
                        badgeAtor = 'background:#f3e8ff; color:#6f42c1;';
                    } else if (cargoLower.indexOf('síndic') !== -1 || cargoLower.indexOf('sindic') !== -1) {
                        badgeAtor = 'background:#ffebee; color:#c62828;';
                    } else if (cargoLower.indexOf('administra') !== -1 || cargoLower.indexOf('gestão') !== -1) {
                        badgeAtor = 'background:#e7f1ff; color:#0d6efd;';
                    } else if (cargoLower.indexOf('morador') !== -1) {
                        badgeAtor = 'background:#f5f5f5; color:#555;';
                    }

                    html += '<div style="margin-bottom: 20px; position: relative;">';
                    html += '  <div style="position: absolute; left: -26px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: ' + (isEncaminhamento ? '#ef6c00' : '#1a237e') + '; border: 2px solid #fff;"></div>';
                    html += '  <div style="background:#fff; border:1px solid #dee2e6; border-radius:6px; padding:10px 14px;">';
                    html += '    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">';
                    html += '      <span><b>' + por + '</b> <span style="display:inline-block; padding:1px 6px; border-radius:10px; font-size:0.75rem; font-weight:600; ' + badgeAtor + '">' + (cargo || 'Participante') + '</span></span>';
                    html += '      <small class="grey-text">' + dthora + '</small>';
                    html += '    </div>';
                    if (isEncaminhamento) {
                        html += '    <div style="display:inline-block; background:#fff3e0; color:#e65100; font-size:0.75rem; font-weight:bold; padding:2px 8px; border-radius:4px; margin-bottom:6px;"><i class="material-icons tiny left" style="line-height:inherit;">reply</i> TRÂMITE / ENCAMINHAMENTO INTERNO (NÃO É PROVIDÊNCIA CABÍVEL)</div>';
                    }
                    html += '    <div style="font-size:0.85rem; color:#222; margin-top:4px;">' + msg + '</div>';
                    html += '  </div>';
                    html += '</div>';
                });
            }

            document.getElementById('eventosTimelineContainer').innerHTML = html;
        },
        error: function() {
            document.getElementById('timelineLoading').style.display = 'none';
            document.getElementById('timelineConteudo').style.display = 'block';
            document.getElementById('eventosTimelineContainer').innerHTML = '<p class="red-text">Erro ao carregar detalhes do chamado.</p>';
        }
    });
}
</script>
