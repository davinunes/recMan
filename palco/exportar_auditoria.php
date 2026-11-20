<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    die('Acesso negado. Faça login novamente.');
}

require_once __DIR__ . "/../classes/database.php";
require_once __DIR__ . "/../classes/vds_auditoria_service.php";

$formato = $_GET['formato'] ?? 'csv';
$sessaoId = !empty($_GET['sessao_id']) ? (int)$_GET['sessao_id'] : null;

$dataRef = $_GET['data_referencia'] ?? date('Y-m-d');
$filtros = [
    'dt_inicio' => $_GET['dt_inicio'] ?? '',
    'dt_fim' => $_GET['dt_fim'] ?? '',
    'bloco' => $_GET['bloco'] ?? '',
    'unidade' => $_GET['unidade'] ?? '',
    'oco_tipo' => $_GET['oco_tipo'] ?? '',
    'protocolo' => $_GET['protocolo'] ?? '',
    'faixa_tempo' => $_GET['faixa_tempo'] ?? '',
    'situacao_resposta' => $_GET['situacao_resposta'] ?? '',
    'situacao_chamado' => $_GET['situacao_chamado'] ?? '',
    'situacao_providencias' => $_GET['situacao_providencias'] ?? ''
];

$nomeSessaoTitulo = "Apuração sob Demanda";
$dataColetaStr = date('d/m/Y H:i:s');

if ($sessaoId) {
    $sessao = vds_auditoria_carregar_sessao($sessaoId);
    if (!$sessao) {
        die('Sessão de auditoria não encontrada.');
    }
    $itens = $sessao['itens'];
    $metricas = $sessao['totais'];
    $dataRef = $sessao['data_referencia'];
    $nomeSessaoTitulo = $sessao['titulo'] . " (" . $sessao['identificador_execucao'] . ")";
    $dataColetaStr = date('d/m/Y H:i:s', strtotime($sessao['data_coleta']));
} else {
    $resultado = vds_auditoria_consultar_tempo_real($filtros, $dataRef);
    $itens = $resultado['itens'];
    $metricas = $resultado['metricas'];
}

$timestampNome = date('Ymd_His');

// ==========================================
// 1. EXPORTAÇÃO CSV
// ==========================================
if ($formato === 'csv') {
    $fileName = "auditoria_ocorrencias_{$timestampNome}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    
    // UTF-8 BOM para abertura perfeita no Microsoft Excel
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Cabeçalho das Colunas
    fputcsv($out, [
        'Protocolo VDS',
        'ID Interno',
        'UUID Remoto',
        'Bloco',
        'Unidade',
        'Categoria OcoTipo',
        'Data Abertura',
        'Data Referência Auditoria',
        'Tempo Transcorrido (Dias)',
        'Faixa Temporal',
        'Situação da Resposta (Art. 12)',
        'Data 1ª Manifestação Gestão',
        'Tempo até 1ª Manifestação (Dias)',
        '1ª Manifestação Por',
        'Cargo Original Respondente',
        'Classificação Ator',
        'Resumo 1ª Manifestação',
        'Situação do Chamado',
        'Data Encerramento',
        'Tempo até Encerramento (Dias)',
        'Situação das Providências',
        'Data 1ª Providência Identificada',
        'Tempo até 1ª Providência (Dias)',
        'Descrição Providência Identificada',
        'Tipo Comprovação Providência',
        'Total Interações Gestão',
        'Total Interações Morador',
        'Data da Coleta/Extração'
    ], ';');

    foreach ($itens as $it) {
        fputcsv($out, [
            $it['protocolo_vds'],
            $it['ocorrencia_id'],
            $it['uuid_remoto'] ?? '',
            $it['bloco'] ?? '',
            $it['unidade'] ?? '',
            $it['categoria_oco_tipo'] ?? '',
            $it['data_abertura_iso'] ?? ($it['data_abertura'] ?? ''),
            $dataRef,
            $it['tempo_decorrido_dias'],
            $it['faixa_tempo'],
            $it['situacao_resposta'],
            $it['primeira_resposta_data'] ?? '',
            $it['primeira_resposta_tempo_dias'] !== null ? $it['primeira_resposta_tempo_dias'] : '',
            $it['primeira_resposta_por'] ?? '',
            $it['primeira_resposta_cargo_original'] ?? '',
            $it['primeira_resposta_ator'] ?? '',
            $it['primeira_resposta_resumo'] ?? '',
            $it['situacao_chamado'],
            $it['data_encerramento'] ?? '',
            $it['tempo_ate_encerramento_dias'] !== null ? $it['tempo_ate_encerramento_dias'] : '',
            $it['situacao_providencias'],
            $it['primeira_providencia_data'] ?? '',
            $it['primeira_providencia_tempo_dias'] !== null ? $it['primeira_providencia_tempo_dias'] : '',
            $it['primeira_providencia_descricao'] ?? '',
            $it['tipo_comprovacao_providencia'] ?? '',
            $it['total_interacoes_gestao'] ?? 0,
            $it['total_interacoes_morador'] ?? 0,
            $dataColetaStr
        ], ';');
    }

    fclose($out);
    exit();
}

// ==========================================
// 2. EXPORTAÇÃO JSON
// ==========================================
if ($formato === 'json') {
    $fileName = "auditoria_ocorrencias_{$timestampNome}.json";
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $exportData = [
        'metadados' => [
            'sistema' => 'recMan - Conselho Consultivo e Fiscal',
            'modulo' => 'Auditoria de Chamados do Livro de Ocorrências',
            'fundamento_legal' => 'Art. 12 do Regimento Interno (prazo de 7 dias para retorno e providências)',
            'titulo_sessao' => $nomeSessaoTitulo,
            'data_referencia' => $dataRef,
            'data_extracao' => $dataColetaStr,
            'total_registros' => count($itens)
        ],
        'filtros_aplicados' => $filtros,
        'indicadores_consolidados' => $metricas,
        'chamados' => $itens
    ];

    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// ==========================================
// 3. RELATÓRIO EXECUTIVO HTML PARA IMPRESSÃO / PDF
// ==========================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Formal de Auditoria - Ocorrências</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #222; margin: 30px; line-height: 1.5; font-size: 13px; }
        h1 { font-size: 20px; color: #0d47a1; margin-bottom: 4px; text-transform: uppercase; border-bottom: 2px solid #0d47a1; padding-bottom: 8px; }
        h2 { font-size: 15px; color: #333; margin-top: 25px; margin-bottom: 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 12px; margin-bottom: 20px; }
        .grid-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
        .card { background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; text-align: center; }
        .card-num { font-size: 22px; font-weight: bold; color: #0d47a1; }
        .card-desc { font-size: 11px; color: #666; text-transform: uppercase; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
        th, td { border: 1px solid #dee2e6; padding: 6px 8px; text-align: left; }
        th { background: #f1f3f5; font-weight: 600; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; }
        .badge-danger { background: #ffebee; color: #c62828; }
        .badge-warning { background: #fff3e0; color: #ef6c00; }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-info { background: #e3f2fd; color: #0d47a1; }
        @media print {
            body { margin: 10mm; font-size: 10px; }
            .no-print { display: none; }
            th, td { padding: 4px 6px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #0d47a1; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Imprimir Relatório (PDF)
        </button>
    </div>

    <h1>Relatório Formal de Auditoria de Chamados do Livro de Ocorrências</h1>
    
    <div class="meta-box">
        <strong>Apuração:</strong> <?= htmlspecialchars($nomeSessaoTitulo) ?><br>
        <strong>Fundamento Regimental:</strong> Art. 12 do Regimento Interno (prazo de até 7 dias para dar retorno e iniciar providências)<br>
        <strong>Data de Referência da Auditoria:</strong> <?= date('d/m/Y', strtotime($dataRef)) ?> | <strong>Data/Hora da Extração:</strong> <?= $dataColetaStr ?><br>
        <strong>Emissão Institucional:</strong> Conselho Consultivo e Fiscal | Plataforma de Trabalho recMan
    </div>

    <h2>1. Resumo dos Indicadores e Conformidade (Art. 12)</h2>
    <div class="grid-cards">
        <div class="card">
            <div class="card-num"><?= $metricas['total_chamados'] ?></div>
            <div class="card-desc">Total de Ocorrências</div>
        </div>
        <div class="card">
            <div class="card-num" style="color: #2e7d32;"><?= $metricas['respondidos_no_prazo'] ?></div>
            <div class="card-desc">Resp. em até 7 Dias (<?= $metricas['pct_respondidos_no_prazo'] ?>%)</div>
        </div>
        <div class="card">
            <div class="card-num" style="color: #c62828;"><?= $metricas['sem_resposta_extrapolada'] ?></div>
            <div class="card-desc">Sem Resposta (> 7d) (<?= $metricas['pct_sem_resposta_extrapolada'] ?>%)</div>
        </div>
        <div class="card">
            <div class="card-num" style="color: #ef6c00;"><?= $metricas['respondidos_com_atraso'] ?></div>
            <div class="card-desc">Resp. com Atraso (> 7d)</div>
        </div>
    </div>

    <div class="grid-cards">
        <div class="card">
            <div class="card-num"><?= $metricas['tempo_medio_resposta'] ?> dias</div>
            <div class="card-desc">Tempo Médio até Resposta</div>
        </div>
        <div class="card">
            <div class="card-num"><?= $metricas['tempo_mediano_resposta'] ?> dias</div>
            <div class="card-desc">Tempo Mediano até Resposta</div>
        </div>
        <div class="card">
            <div class="card-num" style="color: #c62828;"><?= $metricas['maior_tempo_sem_resposta'] ?> dias</div>
            <div class="card-desc">Maior Tempo Sem Resposta</div>
        </div>
        <div class="card">
            <div class="card-num"><?= $metricas['com_providencia'] ?></div>
            <div class="card-desc">Providência Comprovada</div>
        </div>
    </div>

    <h2>2. Relação Fática e Documental dos Chamados</h2>
    <table>
        <thead>
            <tr>
                <th>Protocolo</th>
                <th>Abertura</th>
                <th>Unidade</th>
                <th>Tempo Decorrido</th>
                <th>Situação Resposta</th>
                <th>1ª Manifestação Gestão</th>
                <th>Ator</th>
                <th>Providência Objetiva</th>
                <th>Situação Chamado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $it): 
                $badgeResp = 'badge-danger';
                $respTexto = 'Sem Resposta';
                if ($it['situacao_resposta'] === 'respondido_no_prazo') {
                    $badgeResp = 'badge-success';
                    $respTexto = 'No Prazo (' . $it['primeira_resposta_tempo_dias'] . 'd)';
                } elseif ($it['situacao_resposta'] === 'respondido_com_atraso') {
                    $badgeResp = 'badge-warning';
                    $respTexto = 'Atraso (' . $it['primeira_resposta_tempo_dias'] . 'd)';
                } elseif ($it['situacao_resposta'] === 'em_prazo_aguardando') {
                    $badgeResp = 'badge-info';
                    $respTexto = 'Em Prazo Legal';
                }
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($it['protocolo_vds']) ?></strong></td>
                <td><?= htmlspecialchars($it['data_abertura_formatada'] ?? substr($it['data_abertura_iso'] ?? '', 0, 16)) ?></td>
                <td>Bl <?= htmlspecialchars($it['bloco'] ?? '-') ?> / <?= htmlspecialchars($it['unidade'] ?? '-') ?></td>
                <td><?= $it['tempo_decorrido_dias'] ?> dias</td>
                <td><span class="badge <?= $badgeResp ?>"><?= $respTexto ?></span></td>
                <td><?= !empty($it['primeira_resposta_data_formatada']) ? $it['primeira_resposta_data_formatada'] : (!empty($it['primeira_resposta_data']) ? date('d/m/Y H:i', strtotime($it['primeira_resposta_data'])) : '-') ?></td>
                <td><?= htmlspecialchars($it['primeira_resposta_ator'] ?? '-') ?></td>
                <td><?= $it['situacao_providencias'] === 'providencia_identificavel' ? '<span class="badge badge-success">Comprovada</span>' : '<span style="color:#888;">Não identificável</span>' ?></td>
                <td><?= htmlspecialchars(ucfirst($it['situacao_chamado'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 11px; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">
        * Documento probatório gerado a partir de dados auditáveis da API oficial da plataforma Vida de Síndico. A contagem de prazos adota o critério de períodos completos de 24 horas a partir do registro de abertura, respeitando o Art. 12 do Regimento Interno.
    </div>
</body>
</html>
