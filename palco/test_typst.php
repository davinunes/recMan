<?php
/**
 * Painel de Testes em Paralelo da API Typst (Porta 5050)
 * recMan - Sistema de Gestão de Recursos e Regimento Interno
 */

require_once __DIR__ . "/../classes/typstPdfService.php";

// Incluir banco de dados se disponível para consultar pareceres reais
if (file_exists(__DIR__ . "/../classes/database.php")) {
    @include_once __DIR__ . "/../classes/database.php";
}
if (file_exists(__DIR__ . "/../classes/repositorio.php")) {
    @include_once __DIR__ . "/../classes/repositorio.php";
}

$action = $_GET['action'] ?? 'index';
$statusHealth = TypstPdfService::checkHealth();
$resultPdf = null;
$errorMsg = null;
$elapsedMs = null;
$selectedVariant = $_POST['variant'] ?? 'modern';
$selectedDocType = $_POST['doc_type'] ?? 'regimento';


$searchNum = trim($_POST['search_numero'] ?? '');
$searchAno = trim($_POST['search_ano'] ?? '');
$searchStatus = null;
$searchMsg = null;

// Buscar pareceres reais no banco de dados do sistema para lista de atalhos
$listaPareceresReais = [];
if (function_exists('DBConnect')) {
    try {
        $sql = "SELECT id, unidade, assunto, notificacao, analise, resultado, conclusao, modelo, data FROM parecer ORDER BY id DESC LIMIT 30";
        $res = @DBExecute($sql);
        if (!$res) {
            $sql = "SELECT id, unidade, assunto, notificacao, analise, resultado, conclusao, modelo, data FROM conselho.parecer ORDER BY id DESC LIMIT 30";
            $res = @DBExecute($sql);
        }
        if ($res && mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                $listaPareceresReais[] = $row;
            }
        }
    } catch (Throwable $t) {
        // Silencioso se sem conexão com DB no momento
    }
}

// Valores padrão ou POST
$notificacaoVal  = $_POST['notificacao'] ?? '186/2023';
$unidadeVal      = $_POST['unidade'] ?? 'A1305';
$assuntoVal      = $_POST['assunto'] ?? 'ESTACIONAMENTO INDEVIDO';
$fatoVal         = $_POST['fato'] ?? 'Veículo posicionado temporariamente na área de circulação para descarregamento de pertences.';
$analiseVal      = $_POST['analise'] ?? 'Após verificação, atestou-se o tempo reduzido de permanência e a ausência de prejuízo aos demais condôminos.';
$resultadoVal    = $_POST['resultado'] ?? 'Diante das atenuantes apuradas, o parecer conclui pelo provimento do recurso com revogação da penalidade.';
$parecerVal      = $_POST['parecer'] ?? 'Favorável ao Recurso';
$modeloVal       = $_POST['modelo'] ?? 'estatico';
$dataEmissaoVal  = $_POST['data_emissao'] ?? date('d/m/Y');

// Processar busca por número e ano do recurso se solicitada
if ($action === 'buscar_parecer' && ($searchNum !== '' || $searchAno !== '')) {
    if (function_exists('DBConnect')) {
        $searchAno2 = strlen($searchAno) === 4 ? substr($searchAno, 2) : $searchAno;
        $searchAno4 = strlen($searchAno) === 2 ? '20' . $searchAno : $searchAno;

        $possibilidades = [];
        if ($searchNum !== '' && $searchAno !== '') {
            $possibilidades[] = "{$searchNum}/{$searchAno4}";
            $possibilidades[] = "{$searchNum}/{$searchAno2}";
        }
        if ($searchNum !== '') {
            $possibilidades[] = $searchNum;
        }

        $parecerEncontrado = null;
        foreach ($possibilidades as $pId) {
            $escapedId = DBEscape($pId);
            $sql = "SELECT id, unidade, assunto, notificacao, analise, resultado, conclusao, modelo, data FROM parecer WHERE id = '$escapedId' LIMIT 1";
            $res = @DBExecute($sql);
            if (!$res) {
                $sql = "SELECT id, unidade, assunto, notificacao, analise, resultado, conclusao, modelo, data FROM conselho.parecer WHERE id = '$escapedId' LIMIT 1";
                $res = @DBExecute($sql);
            }
            if ($res && mysqli_num_rows($res) > 0) {
                $parecerEncontrado = mysqli_fetch_assoc($res);
                break;
            }
        }

        if ($parecerEncontrado) {
            $notificacaoVal = $parecerEncontrado['id'] ?: $parecerEncontrado['notificacao'];
            $unidadeVal     = $parecerEncontrado['unidade'] ?: $unidadeVal;
            $assuntoVal     = $parecerEncontrado['assunto'] ?: $assuntoVal;
            $fatoVal        = $parecerEncontrado['notificacao'] ?: $fatoVal;
            $analiseVal     = $parecerEncontrado['analise'] ?: $analiseVal;
            $resultadoVal   = $parecerEncontrado['resultado'] ?: $resultadoVal;
            $parecerVal     = $parecerEncontrado['conclusao'] ?: $parecerVal;
            $modeloVal      = $parecerEncontrado['modelo'] ?: 'estatico';
            if (!empty($parecerEncontrado['data'])) {
                $parts = explode(' ', $parecerEncontrado['data'])[0];
                $dParts = explode('-', $parts);
                if (count($dParts) === 3) {
                    $dataEmissaoVal = "{$dParts[2]}/{$dParts[1]}/{$dParts[0]}";
                }
            }
            $searchStatus = 'success';
            $searchMsg = "Parecer do Recurso " . htmlspecialchars($notificacaoVal) . " localizado com sucesso no banco de dados!";

            // Compila o PDF do parecer localizado automaticamente
            $dadosParecer = [
                'notificacao'  => trim($notificacaoVal),
                'unidade'      => trim($unidadeVal),
                'assunto'      => trim($assuntoVal),
                'fato'         => trim($fatoVal),
                'analise'      => trim($analiseVal),
                'resultado'    => trim($resultadoVal),
                'parecer'      => trim($parecerVal),
                'modelo'       => trim($modeloVal),
                'relator'      => 'Conselho Consultivo e Fiscal',
                'data_emissao' => trim($dataEmissaoVal),
                'variant'      => $selectedVariant,
            ];
            $resPdf = TypstPdfService::gerarParecer($dadosParecer, true);
            if ($resPdf['status'] === 'success' && !empty($resPdf['pdf_base64'])) {
                $resultPdf = $resPdf['pdf_base64'];
                $elapsedMs = $resPdf['elapsed_ms'] ?? null;
            }
        } else {
            $searchStatus = 'warning';
            $termosStr = trim("{$searchNum}/{$searchAno}", "/");
            $searchMsg = "Parecer do Recurso '{$termosStr}' não foi localizado no banco de dados.";
        }
    } else {
        $searchStatus = 'error';
        $searchMsg = "Banco de dados MySQL não está disponível para validar.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'test_regimento') {
        if ($selectedDocType === 'convencao') {

            $normativoFile = __DIR__ . '/../convencao_coletiva/convencao_coletiva_miami_json.json';
            $docNome = 'Convenção de Condomínio & Anexo I';
        } else {
            $normativoFile = __DIR__ . '/../regimento/database.json';
            $docNome = 'Regimento Interno';
        }

        if (file_exists($normativoFile)) {
            $jsonData = json_decode(file_get_contents($normativoFile), true);
            $jsonData['variant'] = $selectedVariant;
            $res = TypstPdfService::gerarRegimento($jsonData, true);
            if ($res['status'] === 'success' && !empty($res['pdf_base64'])) {
                $resultPdf = $res['pdf_base64'];
                $elapsedMs = $res['elapsed_ms'] ?? null;
            } else {
                $errorMsg = $res['message'] ?? "Falha ao gerar o PDF do {$docNome} via Typst.";
            }
        } else {
            $errorMsg = "Arquivo do {$docNome} não encontrado.";
        }
    }
 elseif ($action === 'test_parecer') {
        $dadosParecer = [
            'notificacao'  => trim($notificacaoVal),
            'unidade'      => trim($unidadeVal),
            'assunto'      => trim($assuntoVal),
            'fato'         => trim($fatoVal),
            'analise'      => trim($analiseVal),
            'resultado'    => trim($resultadoVal),
            'parecer'      => trim($parecerVal),
            'modelo'       => trim($modeloVal),
            'relator'      => 'Conselho Consultivo e Fiscal',
            'data_emissao' => trim($dataEmissaoVal),
            'variant'      => $selectedVariant,
        ];
        $res = TypstPdfService::gerarParecer($dadosParecer, true);
        if ($res['status'] === 'success' && !empty($res['pdf_base64'])) {
            $resultPdf = $res['pdf_base64'];
            $elapsedMs = $res['elapsed_ms'] ?? null;
        } else {
            $errorMsg = $res['message'] ?? 'Falha ao gerar o PDF do Parecer via Typst.';
        }
    }
}

$variantesDisponiveis = [
    'modern'       => 'Moderno (com Banner LayoutMiami.jpg e Capa Escura)',
    'classic'      => 'Clássico / Notarial (Sem Banner, Moldura Azul)',
    'compact'      => 'Compacto / Executivo (Tabela Densa, Sem Capa Extra)',
    'corporate'    => 'Corporativo (Azul Royal Institucional)',
    'minimal'      => 'Minimalista / Clean (Design Nórdico em Tons Cinza)',
    'juridico'     => 'Jurídico Solene (Borda Dupla Cartório e Selo Verde)',
    'dark'         => 'Dark Mode (Fundo Escuro Premium e Detalhes Ciano)',
    'editorial'    => 'Editorial / Boletim (Capa Verde Esmeralda)',
    'top_header'   => 'Novo Topo 1ª Pág (lay-top-fist-page-1.png)',
    'watermark_a4' => 'Nova Marca d\'Água A4 Full (lay-body-all-pages-1.png)',
    'margem_moderna' => 'Marca d\'Água Margem Moderna (lay-body-all-pages-2.png)'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambiente de Testes - Typst PDF API (Porta 5050)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --panel-bg: #1e293b;
            --accent: #3b82f6;
            --accent-green: #22c55e;
            --accent-red: #ef4444;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            padding: 24px;
            line-height: 1.5;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }
        .header h1 { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-online { background: rgba(34, 197, 94, 0.15); color: var(--accent-green); border: 1px solid var(--accent-green); }
        .badge-offline { background: rgba(239, 68, 68, 0.15); color: var(--accent-red); border: 1px solid var(--accent-red); }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }

        .card {
            background: var(--panel-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .card h2 { font-size: 1.15rem; margin-bottom: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px;
            color: var(--text);
            font-family: inherit;
        }
        .btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-green { background: var(--accent-green); }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-error { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid var(--accent-red); }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: #86efac; border: 1px solid var(--accent-green); }
        .alert-warning { background: rgba(234, 179, 8, 0.15); color: #fde047; border: 1px solid #eab308; }

        .pdf-preview {
            width: 100%;
            height: 700px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #000;
        }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #60a5fa; }
    </style>
</head>
<body>

    <div class="header">
        <h1><span class="material-icons" style="color: var(--accent)">picture_as_pdf</span> Testes Typst PDF API (Porta 5050)</h1>
        <div>
            <?php if (($statusHealth['status'] ?? '') === 'ok'): ?>
                <span class="badge badge-online">
                    <span class="material-icons" style="font-size: 16px">check_circle</span> API Typst Online (Porta 5050)
                </span>
            <?php else: ?>
                <span class="badge badge-offline">
                    <span class="material-icons" style="font-size: 16px">error</span> API Typst Offline (Porta 5050)
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($searchMsg): ?>
        <?php if ($searchStatus === 'success'): ?>
            <div class="alert alert-success">
                <span class="material-icons" style="vertical-align: middle; font-size: 18px;">check_circle</span> <strong>Encontrado!</strong> <?= $searchMsg ?>
            </div>
        <?php elseif ($searchStatus === 'warning'): ?>
            <div class="alert alert-warning">
                <span class="material-icons" style="vertical-align: middle; font-size: 18px;">warning</span> <strong>Atenção:</strong> <?= $searchMsg ?>
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                <strong>Erro:</strong> <?= htmlspecialchars($searchMsg) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert alert-error">
            <strong>Erro:</strong> <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($elapsedMs !== null): ?>
        <div class="alert alert-success">
            <strong>Sucesso!</strong> PDF compilado com Typst (Variante: <code><?= htmlspecialchars($selectedVariant) ?></code>) em <strong><?= $elapsedMs ?> ms</strong>.
        </div>
    <?php endif; ?>

    <div class="grid">
        <!-- Coluna Esquerda: Controles -->
        <div style="display: flex; flex-direction: column; gap: 20px;">

            <!-- Card 1: Testar Regimento Interno / Convenção de Condomínio -->
            <div class="card">
                <h2><span class="material-icons">auto_stories</span> Documentos Normativos (Regimento & Convenção)</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                    Compila o <code>Regimento Interno</code> ou a <code>Convenção de Condomínio & Anexo I</code> em PDF oficial com sumário interativo escolhendo entre as 8 variantes visuais.
                </p>
                <form method="POST" action="test_typst.php?action=test_regimento">
                    <div class="form-group">
                        <label>Documento Normativo</label>
                        <select name="doc_type" id="selectDocType">
                            <option value="regimento" <?= ($selectedDocType ?? 'regimento') === 'regimento' ? 'selected' : '' ?>>
                                Regimento Interno (33 Capítulos - database.json)
                            </option>
                            <option value="convencao" <?= ($selectedDocType ?? 'regimento') === 'convencao' ? 'selected' : '' ?>>
                                Convenção de Condomínio & Anexo I (convencao_coletiva_miami_json.json)
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Variante de Layout Visual (8 Opções)</label>
                        <select name="variant">
                            <?php foreach ($variantesDisponiveis as $varKey => $varLabel): ?>
                                <option value="<?= $varKey ?>" <?= $selectedVariant === $varKey ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($varLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-green">
                        <span class="material-icons">picture_as_pdf</span> Gerar PDF do Documento Selecionado
                    </button>
                </form>
            </div>

            <!-- Card 2: Testar Parecer de Recurso com Validação no Banco de Dados -->
            <div class="card">
                <h2><span class="material-icons">gavel</span> Parecer do Conselho Consultivo e Fiscal</h2>
                
                <!-- Bloco de Busca por Número e Ano -->
                <div style="background: rgba(59, 130, 246, 0.1); padding: 14px; border-radius: 8px; border: 1px solid var(--accent); margin-bottom: 18px;">
                    <label style="color: #93c5fd; font-weight: 600; display: block; margin-bottom: 8px;">
                        <span class="material-icons" style="font-size: 18px; vertical-align: middle;">search</span> Digite o Número e Ano do Recurso para Validar no Sistema
                    </label>
                    <form method="POST" action="test_typst.php?action=buscar_parecer" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="variant" value="<?= htmlspecialchars($selectedVariant) ?>">
                        <div style="flex: 1; min-width: 110px;">
                            <input type="text" name="search_numero" placeholder="Nº Recurso (ex: 186)" value="<?= htmlspecialchars($searchNum) ?>" style="width: 100%;" required>
                        </div>
                        <div style="width: 100px;">
                            <input type="text" name="search_ano" placeholder="Ano (ex: 2023)" value="<?= htmlspecialchars($searchAno) ?>" style="width: 100%;">
                        </div>
                        <button type="submit" class="btn" style="padding: 10px 14px; background: #2563eb;">
                            <span class="material-icons" style="font-size: 18px;">find_in_page</span> Validar e Carregar
                        </button>
                    </form>
                    
                    <?php if (!empty($listaPareceresReais)): ?>
                        <div style="margin-top: 10px; font-size: 0.8rem; color: var(--text-muted);">
                            <span>Ou escolha um dos pareceres recentemente cadastrados:</span>
                            <select id="selectParecerDb" onchange="carregarParecerDb(this.value)" style="margin-top: 4px; padding: 6px; font-size: 0.85rem;">
                                <option value="">-- Selecionar dos Recentes --</option>
                                <?php foreach ($listaPareceresReais as $pItem): ?>
                                    <option value="<?= htmlspecialchars($pItem['id']) ?>">
                                        Recurso <?= htmlspecialchars($pItem['id']) ?> | Unidade <?= htmlspecialchars($pItem['unidade']) ?> - <?= htmlspecialchars($pItem['assunto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="test_typst.php?action=test_parecer" id="formParecer">
                    <div class="form-group">
                        <label>Variante de Layout Visual (8 Opções)</label>
                        <select name="variant">
                            <?php foreach ($variantesDisponiveis as $varKey => $varLabel): ?>
                                <option value="<?= $varKey ?>" <?= $selectedVariant === $varKey ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($varLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Modelo de Parecer</label>
                        <select name="modelo" id="inputModelo">
                            <option value="estatico" <?= $modeloVal === 'estatico' ? 'selected' : '' ?>>Modelo Padrão Seccionado (Notificação, Análise e Conclusão)</option>
                            <option value="full_dinamico" <?= $modeloVal === 'full_dinamico' ? 'selected' : '' ?>>Modelo Full Dinâmico (Corpo Único Corrido)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notificação / Nº Recurso</label>
                        <input type="text" name="notificacao" id="inputNotificacao" value="<?= htmlspecialchars($notificacaoVal) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Unidade</label>
                        <input type="text" name="unidade" id="inputUnidade" value="<?= htmlspecialchars($unidadeVal) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Assunto</label>
                        <input type="text" name="assunto" id="inputAssunto" value="<?= htmlspecialchars($assuntoVal) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Data de Emissão</label>
                        <input type="text" name="data_emissao" id="inputDataEmissao" value="<?= htmlspecialchars($dataEmissaoVal) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Parecer / Conclusão</label>
                        <select name="parecer" id="inputParecer">
                            <option value="Favorável ao Recurso" <?= $parecerVal === 'Favorável ao Recurso' ? 'selected' : '' ?>>Favorável ao Recurso (DEFERIDO)</option>
                            <option value="Desfavorável ao Recurso" <?= $parecerVal === 'Desfavorável ao Recurso' ? 'selected' : '' ?>>Desfavorável ao Recurso (INDEFERIDO)</option>
                            <option value="REVOGAR A PENALIDADE" <?= $parecerVal === 'REVOGAR A PENALIDADE' ? 'selected' : '' ?>>Revogar a Penalidade</option>
                            <option value="CONVERTER EM ADVERTÊNCIA" <?= $parecerVal === 'CONVERTER EM ADVERTÊNCIA' ? 'selected' : '' ?>>Converter em Advertência</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Relato da Notificação / Fato</label>
                        <textarea name="fato" id="inputFato" rows="2"><?= htmlspecialchars($fatoVal) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Análise do Conselho</label>
                        <textarea name="analise" id="inputAnalise" rows="3"><?= htmlspecialchars($analiseVal) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Conclusão / Resultado</label>
                        <textarea name="resultado" id="inputResultado" rows="2"><?= htmlspecialchars($resultadoVal) ?></textarea>
                    </div>
                    <button type="submit" class="btn">
                        <span class="material-icons">bolt</span> Compilar Parecer do Conselho com Typst
                    </button>
                </form>
            </div>

            <!-- Card 3: Instruções do Servidor -->
            <div class="card">
                <h2><span class="material-icons">terminal</span> Execução da API no Servidor</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">
                    Microserviço Python/Typst operando na porta 5050:
                </p>
                <pre style="background: #0f172a; padding: 10px; border-radius: 6px; font-size: 0.8rem; overflow-x: auto; color: #a5f3fc;">python3 py/typst_server.py</pre>
            </div>
        </div>

        <!-- Coluna Direita: Visualização do PDF -->
        <div class="card">
            <h2><span class="material-icons">visibility</span> Visualizador de PDF Interativo</h2>
            <?php if ($resultPdf): ?>
                <iframe class="pdf-preview" src="data:application/pdf;base64,<?= $resultPdf ?>"></iframe>
            <?php else: ?>
                <div style="height: 700px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); border: 2px dashed var(--border); border-radius: 8px;">
                    <span class="material-icons" style="font-size: 48px; margin-bottom: 12px; color: var(--border);">picture_as_pdf</span>
                    <p>Escolha um parecer e clique no botão para compilar e visualizar em tempo real.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const pareceresReais = <?= json_encode($listaPareceresReais, JSON_UNESCAPED_UNICODE) ?>;
        
        function carregarParecerDb(id) {
            if (!id) return;
            const p = pareceresReais.find(item => item.id == id);
            if (!p) return;

            document.getElementById('inputNotificacao').value = p.id || p.notificacao || '';
            document.getElementById('inputUnidade').value = p.unidade || '';
            document.getElementById('inputAssunto').value = p.assunto || '';
            document.getElementById('inputFato').value = p.notificacao || p.fato || '';
            document.getElementById('inputAnalise').value = p.analise || '';
            document.getElementById('inputResultado').value = p.resultado || '';
            
            if (p.conclusao) {
                const sel = document.getElementById('inputParecer');
                let found = false;
                for (let i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].value.toLowerCase().includes(p.conclusao.toLowerCase())) {
                        sel.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    const opt = new Option(p.conclusao, p.conclusao, true, true);
                    sel.add(opt);
                }
            }

            if (p.modelo && document.getElementById('inputModelo')) {
                document.getElementById('inputModelo').value = p.modelo;
            }

            if (p.data) {
                const parts = p.data.split(' ')[0].split('-');
                if (parts.length === 3) {
                    document.getElementById('inputDataEmissao').value = `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
            }
        }
    </script>
</body>
</html>

