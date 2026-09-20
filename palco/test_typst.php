<?php
/**
 * Painel de Testes em Paralelo da API Typst (Porta 5050)
 * recMan - Sistema de Gestão de Recursos e Regimento Interno
 */

require_once __DIR__ . "/../classes/typstPdfService.php";

$action = $_GET['action'] ?? 'index';
$statusHealth = TypstPdfService::checkHealth();
$resultPdf = null;
$errorMsg = null;
$elapsedMs = null;
$selectedVariant = $_POST['variant'] ?? 'modern';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'test_regimento') {
        $regimentoFile = __DIR__ . '/../regimento/database.json';
        if (file_exists($regimentoFile)) {
            $jsonData = json_decode(file_get_contents($regimentoFile), true);
            $jsonData['variant'] = $selectedVariant;
            $res = TypstPdfService::gerarRegimento($jsonData, true);
            if ($res['status'] === 'success' && !empty($res['pdf_base64'])) {
                $resultPdf = $res['pdf_base64'];
                $elapsedMs = $res['elapsed_ms'] ?? null;
            } else {
                $errorMsg = $res['message'] ?? 'Falha ao gerar o PDF do Regimento Interno via Typst.';
            }
        } else {
            $errorMsg = 'Arquivo regimento/database.json não encontrado.';
        }
    } elseif ($action === 'test_parecer') {
        $dadosParecer = [
            'notificacao'  => trim($_POST['notificacao'] ?? '186/2023'),
            'unidade'      => trim($_POST['unidade'] ?? 'A1305'),
            'assunto'      => trim($_POST['assunto'] ?? 'ESTACIONAMENTO INDEVIDO'),
            'fato'         => trim($_POST['fato'] ?? 'Veículo posicionado temporariamente na área de circulação para descarregamento de pertences.'),
            'parecer'      => trim($_POST['parecer'] ?? 'Favorável ao Recurso'),
            'relator'      => trim($_POST['relator'] ?? 'Conselho Consultivo e Fiscal'),
            'data_emissao' => date('d/m/Y'),
            'variant'      => $selectedVariant,
            'fundamentacao'=> trim($_POST['fundamentacao'] ?? 'O Conselho verificou que o tempo de permanência foi inferior a 10 minutos, sem prejuízo aos demais condôminos.')
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
    'modern'    => 'Moderno (com Banner LayoutMiami.jpg e Capa Escura)',
    'classic'   => 'Clássico / Notarial (Sem Banner, Moldura Azul)',
    'compact'   => 'Compacto / Executivo (Tabela Densa, Sem Capa Extra)',
    'corporate' => 'Corporativo (Azul Royal Institucional)',
    'minimal'   => 'Minimalista / Clean (Design Nórdico em Tons Cinza)',
    'juridico'  => 'Jurídico Solene (Borda Dupla Cartório e Selo Verde)',
    'dark'      => 'Dark Mode (Fundo Escuro Premium e Detalhes Ciano)',
    'editorial' => 'Editorial / Boletim (Capa Verde Esmeralda)'
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

        .pdf-preview {
            width: 100%;
            height: 650px;
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

            <!-- Card 1: Testar Regimento Interno -->
            <div class="card">
                <h2><span class="material-icons">auto_stories</span> Regimento Interno (JSON Completo)</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                    Compila o <code>regimento/database.json</code> (33 capítulos) em PDF com sumário interativo escolhendo entre as 8 variantes visuais.
                </p>
                <form method="POST" action="test_typst.php?action=test_regimento">
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
                        <span class="material-icons">picture_as_pdf</span> Gerar PDF do Regimento
                    </button>
                </form>
            </div>

            <!-- Card 2: Testar Parecer de Recurso -->
            <div class="card">
                <h2><span class="material-icons">gavel</span> Parecer de Recurso Notificação</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 14px;">
                    Testa a geração de parecer com 8 opções de layout visual.
                </p>
                <form method="POST" action="test_typst.php?action=test_parecer">
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
                        <label>Notificação</label>
                        <input type="text" name="notificacao" value="186/2023" required>
                    </div>
                    <div class="form-group">
                        <label>Unidade</label>
                        <input type="text" name="unidade" value="A1305" required>
                    </div>
                    <div class="form-group">
                        <label>Assunto</label>
                        <input type="text" name="assunto" value="ESTACIONAMENTO INDEVIDO" required>
                    </div>
                    <div class="form-group">
                        <label>Parecer / Conclusão</label>
                        <select name="parecer">
                            <option value="Favorável ao Recurso">Favorável ao Recurso (DEFERIDO)</option>
                            <option value="Desfavorável ao Recurso">Desfavorável ao Recurso (INDEFERIDO)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Relato do Fato</label>
                        <textarea name="fato" rows="2">Veículo posicionado temporariamente na área de circulação para descarregamento de pertences.</textarea>
                    </div>
                    <div class="form-group">
                        <label>Fundamentação</label>
                        <textarea name="fundamentacao" rows="3">Analisadas as atenuantes e o tempo reduzido de permanência, o Conselho deliberou pelo provimento.</textarea>
                    </div>
                    <button type="submit" class="btn">
                        <span class="material-icons">bolt</span> Compilar Parecer com Typst
                    </button>
                </form>
            </div>

            <!-- Card 3: Como Iniciar a API no Servidor -->
            <div class="card">
                <h2><span class="material-icons">terminal</span> Instrução do Servidor (Porta 5050)</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">
                    Para rodar o microserviço no servidor remoto:
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
                <div style="height: 650px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); border: 2px dashed var(--border); border-radius: 8px;">
                    <span class="material-icons" style="font-size: 48px; margin-bottom: 12px; color: var(--border);">picture_as_pdf</span>
                    <p>Clique em um dos botões à esquerda para compilar e visualizar o PDF aqui.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
