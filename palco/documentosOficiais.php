<?php
/**
 * Módulo de Documentos Oficiais do Conselho (Orientações Técnicas, Pareceres Opinativos, etc.)
 * recMan - Sistema de Gestão de Recursos e Regimento Interno
 */

require_once __DIR__ . "/../classes/repositorio.php";

$filtroTipo = $_GET['tipo'] ?? '';
$filtroAno = $_GET['ano'] ?? '';
$filtroBusca = $_GET['busca'] ?? '';

$documentos = getDocumentosOficiais([
    'tipo' => $filtroTipo,
    'ano' => $filtroAno,
    'busca' => $filtroBusca
]);

$tiposNomes = [
    'documento' => 'Documento',
    'parecer_opinativo' => 'Parecer Opinativo',
    'orientacao_tecnica' => 'Orientação Técnica',
    'entendimento' => 'Entendimento do Conselho',
    'instrucao_normativa' => 'Instrução Normativa'
];

$tiposCores = [
    'documento' => 'green darken-2',
    'orientacao_tecnica' => 'blue darken-2',
    'parecer_opinativo' => 'purple darken-2',
    'entendimento' => 'teal darken-2',
    'instrucao_normativa' => 'orange darken-3'
];
?>

<style>
    .doc-card {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .doc-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1) !important;
    }

    .editor-btn-group .btn {
        margin-right: 5px;
    }

    .split-container {
        display: flex;
        gap: 15px;
        height: calc(100vh - 310px);
        min-height: 520px;
    }

    .split-pane {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .typst-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-bottom: none;
        border-radius: 8px 8px 0 0;
        padding: 6px 8px;
    }

    .typst-toolbar .tool-group {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        border-right: 1px solid #e2e8f0;
        padding-right: 6px;
        margin-right: 4px;
    }

    .typst-toolbar .tool-group:last-child {
        border-right: none;
        padding-right: 0;
        margin-right: 0;
    }

    .typst-toolbar button.btn-tool {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 3px 7px;
        font-size: 0.8rem;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 28px;
        transition: all 0.15s ease;
        line-height: 1;
        outline: none;
    }

    .typst-toolbar button.btn-tool:hover {
        background: #e2e8f0;
        color: #0f172a;
        border-color: #94a3b8;
    }

    .typst-toolbar button.btn-tool i.material-icons {
        font-size: 16px;
        margin: 0;
    }

    .typst-toolbar button.btn-tool.btn-magic {
        background: #ede9fe;
        color: #6d28d9;
        border-color: #c4b5fd;
        font-weight: 600;
        padding: 3px 9px;
    }

    .typst-toolbar button.btn-tool.btn-magic:hover {
        background: #ddd6fe;
        color: #5b21b6;
        border-color: #a78bfa;
    }

    .typst-toolbar button.btn-tool.btn-magic i {
        margin-right: 4px !important;
        font-size: 16px;
    }

    .typst-toolbar button.btn-tool.btn-fn {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
        font-weight: 600;
        padding: 3px 8px;
    }

    .typst-toolbar button.btn-tool.btn-fn:hover {
        background: #d1fae5;
        color: #065f46;
    }

    .typst-toolbar button.btn-tool.btn-fn i {
        margin-right: 3px !important;
        font-size: 15px;
    }

    /* Modal Fullscreen Real (96% Tela) */
    .modal.modal-fullscreen-custom {
        width: 96% !important;
        max-width: 96% !important;
        height: 94% !important;
        max-height: 94% !important;
        top: 3% !important;
        left: 2% !important;
        right: 2% !important;
        margin: 0 auto !important;
        border-radius: 12px !important;
    }

    .modal.modal-fullscreen-custom .modal-content {
        height: calc(100% - 65px) !important;
        max-height: none !important;
        padding: 20px !important;
        overflow-y: auto !important;
    }
</style>

<div class="container-fluid" style="padding: 0 20px; margin-top: 20px;">
    <!-- Cabeçalho -->
    <div class="row">
        <div class="col s12 m8">
            <h4><i class="material-icons left purple-text text-darken-2" style="font-size: 2.5rem;">gavel</i>Documentos
                Oficiais do Conselho</h4>
            <p class="grey-text">Gestão, emissão e numeração sequencial de Orientações Técnicas, Pareceres Opinativos e
                Normas.</p>
        </div>
        <div class="col s12 m4 right-align" style="margin-top: 20px;">
            <button class="btn green darken-2 waves-effect waves-light btn-large" id="btnNovoDocumento"
                style="border-radius: 8px;">
                <i class="material-icons left">add</i>Novo Documento Oficial
            </button>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="row">
        <div class="col s12">
            <div class="card z-depth-1" style="border-radius: 10px; padding: 15px;">
                <form method="GET" action="index.php">
                    <input type="hidden" name="pag" value="documentosOficiais">
                    <div class="row" style="margin-bottom: 0;">
                        <div class="input-field col s12 m3">
                            <select name="tipo" class="browser-default"
                                style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 8px;">
                                <option value="">Todos os Tipos</option>
                                <?php foreach ($tiposNomes as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php echo $filtroTipo === $k ? 'selected' : ''; ?>>
                                        <?php echo $v; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-field col s12 m2">
                            <input type="number" name="ano" value="<?php echo htmlspecialchars($filtroAno); ?>"
                                placeholder="Ex: 2026">
                        </div>
                        <div class="input-field col s12 m5">
                            <input type="text" name="busca" value="<?php echo htmlspecialchars($filtroBusca); ?>"
                                placeholder="Pesquisar por título, ementa ou palavra-chave...">
                        </div>
                        <div class="input-field col s12 m2 right-align">
                            <button type="submit" class="btn blue waves-effect waves-light style-btn-search"
                                style="border-radius: 6px; width: 100%;">
                                <i class="material-icons left">search</i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabela de Listagem -->
    <div class="row">
        <div class="col s12">
            <div class="card z-depth-1" style="border-radius: 12px; overflow: hidden;">
                <div class="card-content" style="padding: 0;">
                    <table class="highlight responsive-table" style="margin: 0;">
                        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th style="padding-left: 20px;">Documento / Numeração</th>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Relator</th>
                                <th>Data</th>
                                <th>Modo</th>
                                <th class="right-align" style="padding-right: 20px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($documentos) > 0): ?>
                                <?php foreach ($documentos as $doc):
                                    $tipoKey = $doc['tipo'];
                                    $corBadge = $tiposCores[$tipoKey] ?? 'blue-grey';
                                    $nomeTipo = $tiposNomes[$tipoKey] ?? strtoupper(str_replace('_', ' ', $tipoKey));
                                    $numFormatado = sprintf("%03d/%d", $doc['numero'], $doc['ano']);
                                    $dtFormatada = date('d/m/Y', strtotime($doc['data_emissao']));
                                    ?>
                                    <tr>
                                        <td style="padding-left: 20px; font-weight: bold; color: #0f172a;">
                                            <i class="material-icons tiny left blue-text text-darken-2"
                                                style="margin-top: 3px;">description</i>
                                            <?php echo $nomeTipo . ' nº ' . $numFormatado; ?>
                                        </td>
                                        <td>
                                            <span class="chip white-text <?php echo $corBadge; ?>"
                                                style="font-size: 0.75rem; height: 24px; line-height: 24px;">
                                                <?php echo $nomeTipo; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($doc['titulo']); ?></span>
                                            <?php if (!empty($doc['ementa'])): ?>
                                                <span class="grey-text text-darken-1 truncate"
                                                    style="font-size: 0.82rem; display: block; max-width: 380px;">
                                                    <?php echo htmlspecialchars($doc['ementa']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="grey-text text-darken-2" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($doc['relator'] ?: 'Conselho'); ?>
                                        </td>
                                        <td class="grey-text text-darken-2" style="font-size: 0.9rem;">
                                            <?php echo $dtFormatada; ?>
                                        </td>
                                        <td>
                                            <?php if ($doc['modo_template'] === 'clear'): ?>
                                                <span class="chip grey lighten-3 grey-text text-darken-3"
                                                    style="font-size: 0.75rem; height: 22px; line-height: 22px;">Raw Typst</span>
                                            <?php else: ?>
                                                <span class="chip blue lighten-5 blue-text text-darken-3"
                                                    style="font-size: 0.75rem; height: 22px; line-height: 22px;"><?php echo htmlspecialchars($doc['variante_global'] ?: 'Global'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="right-align" style="padding-right: 20px;">
                                            <button class="btn-flat btn-small waves-effect waves-blue btnPreviewDoc"
                                                docid="<?php echo $doc['id']; ?>" data-docid="<?php echo $doc['id']; ?>"
                                                title="Visualizar PDF">
                                                <i class="material-icons blue-text text-darken-2">visibility</i>
                                            </button>
                                            <button class="btn-flat btn-small waves-effect waves-orange btnEditarDoc"
                                                docid="<?php echo $doc['id']; ?>" data-docid="<?php echo $doc['id']; ?>"
                                                title="Editar">
                                                <i class="material-icons orange-text text-darken-3">edit</i>
                                            </button>
                                            <button class="btn-flat btn-small waves-effect waves-red btnDeletarDoc"
                                                docid="<?php echo $doc['id']; ?>" data-docid="<?php echo $doc['id']; ?>"
                                                num="<?php echo $numFormatado; ?>" title="Excluir">
                                                <i class="material-icons red-text text-darken-2">delete</i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="center-align grey-text" style="padding: 40px;">
                                        <i class="material-icons medium grey-text text-lighten-1"
                                            style="display: block; margin-bottom: 10px;">folder_open</i>
                                        Nenhum documento oficial cadastrado. Clique no botão <b>"Novo Documento Oficial"</b>
                                        para criar.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITOR / CADASTRO E EDIÇÃO DE DOCUMENTO OFICIAL -->
<div id="modalDocumentoOficial" class="modal modal-fixed-footer modal-fullscreen-custom">
    <div class="modal-content">
        <div
            style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 15px;">
            <h4 style="margin: 0; font-size: 1.4rem; font-weight: 700; color: #0f172a;" id="modalDocTitle">
                <i class="material-icons left purple-text">edit_note</i>Redação de Documento Oficial
            </h4>

            <!-- Badge do Redator Typst -->
            <div
                style="background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; border: 1px solid #bae6fd; display: flex; align-items: center;">
                <i class="material-icons left tiny" style="margin-right: 4px;">code</i>Redator Typst Code & Live PDF
            </div>
        </div>

        <form id="formDocOficial">
            <input type="hidden" id="docId" name="id" value="0">
            <input type="hidden" id="docModoEditor" name="modo_editor" value="split_code">

            <!-- Linha 1: Tipo, Número, Ano, Relator e Data -->
            <div class="row" style="margin-bottom: 10px;">
                <div class="col s12 m3">
                    <label style="font-weight: bold; color: #334155;">Tipo de Documento:</label>
                    <select id="docTipo" name="tipo" class="browser-default"
                        style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 8px;">
                        <option value="documento">Documento</option>
                        <option value="parecer_opinativo">Parecer Opinativo</option>
                        <option value="orientacao_tecnica">Orientação Técnica</option>
                        <option value="entendimento">Entendimento do Conselho</option>
                        <option value="instrucao_normativa">Instrução Normativa</option>
                    </select>
                </div>
                <div class="col s6 m2">
                    <label style="font-weight: bold; color: #334155;">Número:</label>
                    <input type="number" id="docNumero" name="numero" placeholder="Auto"
                        style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px;">
                </div>
                <div class="col s6 m2">
                    <label style="font-weight: bold; color: #334155;">Ano:</label>
                    <input type="number" id="docAno" name="ano" value="<?php echo date('Y'); ?>"
                        style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px;">
                </div>
                <div class="col s12 m3">
                    <label style="font-weight: bold; color: #334155;">Relator / Emissor:</label>
                    <input type="text" id="docRelator" name="relator" value="Conselho Consultivo e Fiscal"
                        style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px;">
                </div>
                <div class="col s12 m2">
                    <label style="font-weight: bold; color: #334155;">Data Emissão:</label>
                    <input type="date" id="docDataEmissao" name="data_emissao" value="<?php echo date('Y-m-d'); ?>"
                        style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px;">
                </div>
            </div>

            <!-- Linha 2: Título e Ementa -->
            <div class="row" style="margin-bottom: 10px;">
                <div class="col s12 m6">
                    <label style="font-weight: bold; color: #334155;">Título do Documento Oficial:</label>
                    <input type="text" id="docTitulo" name="titulo"
                        placeholder="Ex: Regras de Utilização do Salão de Festas e Áreas Comuns" required
                        style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 8px;">
                </div>
                <div class="col s12 m6">
                    <label style="font-weight: bold; color: #334155;">Ementa / Resumo Oficial (Opção Notarial):</label>
                    <input type="text" id="docEmenta" name="ementa"
                        placeholder="Ex: Dispõe sobre critérios de reserva, taxa de limpeza e penalidades aplicáveis..."
                        style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 8px;">
                </div>
            </div>

            <!-- Linha 3: Modo de Template e Variante Global -->
            <div class="row"
                style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                <div class="col s12 m4">
                    <label style="font-weight: bold; color: #0f172a; display: block; margin-bottom: 6px;">Aplicação de
                        Template:</label>
                    <label style="margin-right: 15px;">
                        <input name="modo_template" type="radio" value="global" id="tmplModeGlobal" checked />
                        <span>Usar Template Global</span>
                    </label>
                    <label>
                        <input name="modo_template" type="radio" value="clear" id="tmplModeClear" />
                        <span><b>Clear</b> (Raw Typst sem template)</span>
                    </label>
                </div>
                <div class="col s12 m8" id="containerVarianteGlobal">
                    <label style="font-weight: bold; color: #0f172a; display: block; margin-bottom: 6px;">Variante
                        Visual Global:</label>
                    <select id="docVarianteGlobal" name="variante_global" class="browser-default"
                        style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px; background: white;">
                        <option value="top_header" selected>Novo Topo 1ª Pág (lay-top-fist-page-1.png)</option>
                        <option value="watermark_a4">Nova Marca d'Água Full A4 (lay-body-all-pages-1.png)</option>
                        <option value="margem_moderna">Marca d'Água Margem Moderna (lay-body-all-pages-2.png)</option>
                        <option value="editorial">Editorial / Boletim (Verde Esmeralda)</option>
                        <option value="modern">Moderno (com Banner Miami)</option>
                        <option value="classic">Clássico / Notarial (Moldura Azul)</option>
                        <option value="corporate">Corporativo (Azul Royal)</option>
                        <option value="minimal">Minimalista / Clean</option>
                        <option value="juridico">Jurídico Solene (Borda Dupla)</option>
                        <option value="dark">Dark Mode (Fundo Escuro)</option>
                    </select>
                </div>
            </div>

            <!-- EDITOR TYPST CODE & LIVE PREVIEW (PADRÃO ÚNICO) -->
            <div id="wrapperEditorSplit" class="row">
                <div class="col s12">
                    <div class="split-container"
                        style="display: flex; gap: 15px; height: calc(100vh - 310px); min-height: 520px;">
                        <!-- Painel Código Left -->
                        <div class="split-pane" style="flex: 1; display: flex; flex-direction: column;">
                            <label
                                style="font-weight: bold; color: #334155; display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span>Código Fonte Typst:</span>
                                <button type="button" class="btn-flat btn-small blue-text" id="btnLiveCompile">
                                    <i class="material-icons left tiny">play_arrow</i>Atualizar PDF Preview
                                </button>
                            </label>

                            <!-- Toolbar Typst & Varinha Mágica -->
                            <div class="typst-toolbar">
                                <!-- Varinha Mágica (Markdown -> Typst) -->
                                <div class="tool-group">
                                    <button type="button" class="btn-tool btn-magic waves-effect" id="btnMagicMarkdown" title="Varinha Mágica: Converte Markdown selecionado (ou todo o texto) para Typst">
                                        <i class="material-icons">auto_awesome</i>Varinha Mágica (MD ➔ Typst)
                                    </button>
                                </div>

                                <!-- Formatação Básica -->
                                <div class="tool-group">
                                    <button type="button" class="btn-tool" id="btnToolBold" title="Negrito (*texto*)"><b>B</b></button>
                                    <button type="button" class="btn-tool" id="btnToolItalic" title="Itálico (_texto_)"><i>I</i></button>
                                    <button type="button" class="btn-tool" id="btnToolUnderline" title="Sublinhado (#underline[...])"><u>U</u></button>
                                    <button type="button" class="btn-tool" id="btnToolCase" title="Alternar MAIÚSCULAS / minúsculas"><b>Aa</b></button>
                                </div>

                                <!-- Títulos -->
                                <div class="tool-group">
                                    <button type="button" class="btn-tool" id="btnToolH1" title="Título H1 (= Título)"><b>H1</b></button>
                                    <button type="button" class="btn-tool" id="btnToolH2" title="Seção H2 (== Seção)"><b>H2</b></button>
                                    <button type="button" class="btn-tool" id="btnToolH3" title="Subseção H3 (=== Subseção)"><b>H3</b></button>
                                </div>

                                <!-- Listas & Citação -->
                                <div class="tool-group">
                                    <button type="button" class="btn-tool" id="btnToolBullet" title="Lista com Marcadores (- Item)"><i class="material-icons">format_list_bulleted</i></button>
                                    <button type="button" class="btn-tool" id="btnToolNumber" title="Lista Numerada (+ Item)"><i class="material-icons">format_list_numbered</i></button>
                                    <button type="button" class="btn-tool" id="btnToolQuote" title="Citação com Recuo (#pad)"><i class="material-icons">format_quote</i></button>
                                </div>

                                <!-- Nota de Rodapé & Estrutura -->
                                <div class="tool-group">
                                    <button type="button" class="btn-tool btn-fn waves-effect" id="btnToolFootnote" title="Inserir Nota de Rodapé no texto (#footnote[...])">
                                        <i class="material-icons">vertical_align_bottom</i>[¹] Nota Rodapé
                                    </button>
                                    <button type="button" class="btn-tool" id="btnToolLine" title="Linha Divisória Horizontal"><i class="material-icons">horizontal_rule</i></button>
                                    <button type="button" class="btn-tool" id="btnToolPagebreak" title="Quebra de Página (#pagebreak())"><i class="material-icons">insert_page_break</i></button>
                                </div>
                            </div>

                            <textarea id="editorSplitCode" name="conteudo"
                                placeholder="Digite o código Typst ou texto do documento aqui..."
                                style="width: 100%; height: calc(100% - 44px); font-family: monospace; font-size: 0.95rem; padding: 12px; border-radius: 0 0 8px 8px; border: 1px solid #cbd5e1; background: #0f172a; color: #f8fafc; line-height: 1.5; resize: none;"></textarea>
                        </div>
                        <!-- Painel Live PDF Right -->
                        <div class="split-pane" style="flex: 1; display: flex; flex-direction: column;">
                            <label style="font-weight: bold; color: #334155; display: block; margin-bottom: 6px;">Live
                                PDF Preview (Typst Porta 5050):</label>
                            <div id="splitPdfPreviewContainer"
                                style="width: 100%; height: 100%; border: 1px solid #cbd5e1; border-radius: 8px; background: #e2e8f0; display: flex; align-items: center; justify-content: center;">
                                <span class="grey-text">Digite ou clique em "Atualizar PDF Preview" para
                                    compilar...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer" style="padding: 10px 20px;">
        <button type="button" class="modal-close btn-flat waves-effect" style="margin-right: 10px;">Cancelar</button>
        <button type="button" class="btn green darken-2 waves-effect waves-light" id="btnSalvarDocumentoOficial"
            style="border-radius: 6px;">
            <i class="material-icons left">save</i>Salvar Documento
        </button>
    </div>
</div>

<!-- MODAL PREVIEW DE PDF FINAL -->
<div id="modalPreviewPdfFinal" class="modal modal-fixed-footer modal-fullscreen-custom">
    <div class="modal-content" style="padding: 0; height: calc(100% - 56px);">
        <iframe id="iframePdfFinal" style="width: 100%; height: 100%; border: none;"></iframe>
    </div>
    <div class="modal-footer" style="padding: 5px 20px;">
        <a href="#!" class="modal-close btn-flat waves-effect bold">Fechar Visualização</a>
    </div>
</div>

<!-- MODAL INSERIR NOTA DE RODAPÉ -->
<div id="modalInserirFootnote" class="modal" style="width: 520px; max-width: 90%; border-radius: 12px; top: 15% !important;">
    <div class="modal-content" style="padding: 20px;">
        <h5 style="margin-top: 0; font-size: 1.25rem; font-weight: 700; color: #065f46; display: flex; align-items: center;">
            <i class="material-icons left" style="margin-right: 6px;">vertical_align_bottom</i>Inserir Nota de Rodapé (#footnote)
        </h5>
        <p class="grey-text text-darken-1" style="font-size: 0.85rem; margin-bottom: 15px;">
            O Typst colocará automaticamente o número sobrescrito no local da seleção e renderizará a descrição numerada no rodapé da respectiva página.
        </p>
        <div>
            <label style="font-weight: 600; color: #334155;">Texto da Nota de Rodapé:</label>
            <textarea id="inputFootnoteTexto" class="materialize-textarea"
                placeholder="Ex: Contrato nº 0400769796 firmado em 06/11/2026..."
                style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; font-size: 0.9rem; min-height: 70px; background: #f8fafc; margin-top: 6px;"></textarea>
        </div>
    </div>
    <div class="modal-footer" style="padding: 10px 20px; background: #f1f5f9;">
        <button type="button" class="modal-close btn-flat waves-effect" style="margin-right: 8px;">Cancelar</button>
        <button type="button" class="btn green darken-2 waves-effect waves-light" id="btnConfirmarFootnote" style="border-radius: 6px;">
            <i class="material-icons left">check</i>Inserir no Texto
        </button>
    </div>
</div>

<script>
    function execEditorCmd(cmd, value = null) {
        document.execCommand(cmd, false, value);
    }

    $(document).ready(function () {
        $('.modal').modal();

        // ==========================================
        // Utilitários de Manipulação do Editor Typst
        // ==========================================
        function getTypstEditorSelection() {
            const textarea = document.getElementById('editorSplitCode');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selected = text.substring(start, end);
            return { textarea, start, end, text, selected };
        }

        function replaceTypstSelection(newText, selectNew = false) {
            const { textarea, start, end, text } = getTypstEditorSelection();
            textarea.value = text.substring(0, start) + newText + text.substring(end);
            if (selectNew) {
                textarea.selectionStart = start;
                textarea.selectionEnd = start + newText.length;
            } else {
                textarea.selectionStart = textarea.selectionEnd = start + newText.length;
            }
            textarea.focus();
            $(textarea).trigger('input');
        }

        function wrapTypstSelection(prefix, suffix, defaultPlaceholder = "texto") {
            const { textarea, selected } = getTypstEditorSelection();
            const content = selected || defaultPlaceholder;
            replaceTypstSelection(prefix + content + suffix, false);
        }

        function prefixLinesTypst(prefix) {
            const { textarea, start, end, text, selected } = getTypstEditorSelection();
            if (selected.length > 0) {
                const lines = selected.split('\n');
                const modified = lines.map(line => prefix + line).join('\n');
                replaceTypstSelection(modified, true);
            } else {
                replaceTypstSelection(prefix + "Título ou Item", true);
            }
        }

        // ==========================================
        // Varinha Mágica: Conversor Markdown -> Typst
        // ==========================================
        function converterMarkdownParaTypst(conteudo) {
            if (!conteudo) return "";
            let str = conteudo;

            // 1. Extrair e converter notas de rodapé estilo Markdown [^1]: texto
            const footnotes = {};
            const fnRegex = /^\[\^([a-zA-Z0-9_\-]+)\]:\s*(.+)$/gm;
            let match;
            while ((match = fnRegex.exec(str)) !== null) {
                footnotes[match[1]] = match[2].trim();
            }
            // Remove as definições de rodapé do corpo
            str = str.replace(/^\[\^([a-zA-Z0-9_\-]+)\]:\s*.+$/gm, '');

            // Substitui [^id] por #footnote[desc]
            for (const [id, desc] of Object.entries(footnotes)) {
                const descSafe = desc.replace(/\[/g, '(').replace(/\]/g, ')');
                str = str.split(`[^${id}]`).join(`#footnote[${descSafe}]`);
            }

            // 2. Extrair referências numéricas do tipo [1] Descrição... ou [4] Descrição... no final
            const refRegex = /^\[([0-9]{1,3})\]\s+([^\n\r]+)$/gm;
            const refNumericas = {};
            while ((match = refRegex.exec(str)) !== null) {
                if (match[2].trim().length > 8) {
                    refNumericas[match[1]] = match[2].trim();
                }
            }
            for (const [id, desc] of Object.entries(refNumericas)) {
                str = str.replace(new RegExp(`^\\[${id}\\]\\s+.+$`, 'gm'), '');
                const descSafe = desc.replace(/\[/g, '(').replace(/\]/g, ')');
                str = str.replace(new RegExp(`\\[${id}\\]`, 'g'), `#footnote[${descSafe}]`);
            }

            // 3. Escapa referências numéricas soltas sem definição (ex: [1], [4], [6][12])
            // No Typst, colchetes logo após parênteses ')...[1]' são lidos como indexação/chamada inválida
            str = str.replace(/(?<!\\)\[([0-9]{1,3})\]/g, '\\[$1\\]');

            // 4. Converte listas não-ordenadas do Markdown com asterisco (* Item) em hífen Typst (- Item)
            // Isso é crucial porque no Typst '*' no início da linha abre um bloco de negrito infinito!
            str = str.replace(/^(\s*)\*\s+(.+)$/gm, '$1- $2');

            // 5. Remove barras de escape desnecessárias do Markdown antes de pontos (ex: 1\. -> 1.)
            str = str.replace(/([0-9]+)\\\./g, '$1.');

            // 6. Títulos Markdown (# -> =, ## -> ==, ### -> ===, etc.)
            str = str.replace(/^(#{1,6})\s+(.+)$/gm, function(m, hashes, title) {
                const nivel = hashes.length;
                const equals = "=".repeat(nivel);
                return `\n${equals} ${title.trim()}\n`;
            });

            // 7. Limpa asteriscos redundantes nos títulos (ex: === *Título* -> === Título)
            str = str.replace(/^(={1,6})\s*\*(.+?)\*\s*$/gm, '$1 $2');

            // 8. Negrito duplo Markdown (**texto**) -> Negrito Typst (*texto*)
            str = str.replace(/\*\*(.+?)\*\*/gs, '*$1*');

            // 9. Escapa cifrões monetários (R$ 100,00 ou $ 50) para não abrir o modo matemático do Typst ($...$)
            // Um número ímpar de cifrões soltos gera fatalmente "error: unclosed delimiter"
            str = str.replace(/(?<!\\)R\$/g, 'R\\$');
            str = str.replace(/(?<!\\)\$(\s*[0-9])/g, '\\$$1');

            // 10. Linha divisória: --- ou *** -> Linha Typst
            str = str.replace(/^(\-{3,}|\*{3,})\s*$/gm, '\n#v(0.3cm)\n#line(length: 100%, stroke: 0.5pt + rgb("cbd5e1"))\n#v(0.3cm)\n');

            // 11. Links [texto](url) -> #link("url")[texto]
            str = str.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g, '#link("$2")[$1]');

            // 12. Citações Markdown (> texto) -> #pad(left: 15pt)[_texto_]
            str = str.replace(/^>\s*(.+)$/gm, '#pad(left: 15pt)[_$1_]');

            return str.trim();
        }

        // Varinha Mágica (Atua na seleção ou no texto completo)
        $('#btnMagicMarkdown').click(function () {
            const { textarea, start, end, text, selected } = getTypstEditorSelection();
            const alvo = (selected && selected.trim().length > 0) ? selected : text;

            if (!alvo || !alvo.trim()) {
                return M.toast({ html: 'Nenhum texto para converter.', classes: 'rounded amber darken-3' });
            }

            const resultado = converterMarkdownParaTypst(alvo);

            if (selected && selected.trim().length > 0) {
                replaceTypstSelection(resultado, true);
                M.toast({ html: '<i class="material-icons left tiny">auto_awesome</i>Trecho selecionado convertido de Markdown para Typst!', classes: 'rounded purple' });
            } else {
                textarea.value = resultado;
                textarea.selectionStart = textarea.selectionEnd = 0;
                textarea.focus();
                $(textarea).trigger('input');
                M.toast({ html: '<i class="material-icons left tiny">auto_awesome</i>Documento completo convertido de Markdown para Typst!', classes: 'rounded purple' });
            }
        });

        // ==========================================
        // Botões de Formatação Básica da Toolbar
        // ==========================================
        $('#btnToolBold').click(function () {
            wrapTypstSelection('*', '*', 'texto em negrito');
        });

        $('#btnToolItalic').click(function () {
            wrapTypstSelection('_', '_', 'texto em itálico');
        });

        $('#btnToolUnderline').click(function () {
            wrapTypstSelection('#underline[', ']', 'texto sublinhado');
        });

        $('#btnToolCase').click(function () {
            const { selected } = getTypstEditorSelection();
            if (!selected) {
                return M.toast({ html: 'Selecione um texto para alternar maiúsculas/minúsculas.', classes: 'rounded amber darken-3' });
            }
            const isUpper = selected === selected.toUpperCase();
            const converted = isUpper ? selected.toLowerCase() : selected.toUpperCase();
            replaceTypstSelection(converted, true);
        });

        // Títulos H1, H2, H3
        $('#btnToolH1').click(function () {
            wrapTypstSelection('\n= ', '\n', 'Título Principal');
        });

        $('#btnToolH2').click(function () {
            wrapTypstSelection('\n== ', '\n', 'Título da Seção');
        });

        $('#btnToolH3').click(function () {
            wrapTypstSelection('\n=== ', '\n', 'Subseção');
        });

        // Listas e Citação
        $('#btnToolBullet').click(function () {
            prefixLinesTypst('- ');
        });

        $('#btnToolNumber').click(function () {
            prefixLinesTypst('+ ');
        });

        $('#btnToolQuote').click(function () {
            wrapTypstSelection('\n#pad(left: 15pt)[_', '_]\n', 'Transcrição ou citação...');
        });

        // Linha Divisória e Quebra de Página
        $('#btnToolLine').click(function () {
            replaceTypstSelection('\n#v(0.3cm)\n#line(length: 100%, stroke: 0.5pt + rgb("cbd5e1"))\n#v(0.3cm)\n');
        });

        $('#btnToolPagebreak').click(function () {
            replaceTypstSelection('\n#pagebreak()\n');
        });

        // ==========================================
        // Inserção de Nota de Rodapé (#footnote)
        // ==========================================
        let savedFootnoteSelection = null;
        $('#btnToolFootnote').click(function () {
            savedFootnoteSelection = getTypstEditorSelection();
            let defaultText = '';
            if (savedFootnoteSelection.selected && savedFootnoteSelection.selected.length > 5 && !savedFootnoteSelection.selected.startsWith('[')) {
                defaultText = savedFootnoteSelection.selected;
            }
            $('#inputFootnoteTexto').val(defaultText);
            $('#modalInserirFootnote').modal('open');
            setTimeout(() => $('#inputFootnoteTexto').focus(), 250);
        });

        $('#btnConfirmarFootnote').click(function () {
            const textoNota = $('#inputFootnoteTexto').val().trim();
            if (!textoNota) {
                return M.toast({ html: 'Informe o texto da nota de rodapé!', classes: 'rounded red' });
            }
            const notaSanitizada = textoNota.replace(/\[/g, '(').replace(/\]/g, ')');
            const snippet = `#footnote[${notaSanitizada}]`;

            const textarea = document.getElementById('editorSplitCode');
            if (savedFootnoteSelection) {
                const { start, end, text } = savedFootnoteSelection;
                textarea.value = text.substring(0, start) + snippet + text.substring(end);
                textarea.selectionStart = textarea.selectionEnd = start + snippet.length;
            } else {
                replaceTypstSelection(snippet);
            }
            textarea.focus();
            $(textarea).trigger('input');
            $('#modalInserirFootnote').modal('close');
            M.toast({ html: 'Nota de rodapé (#footnote) inserida!', classes: 'rounded green' });
        });

        // Atualizar próximo número ao alterar Tipo ou Ano
        $('#docTipo, #docAno').on('change', function () {
            if ($('#docId').val() == "0") {
                $.ajax({
                    url: 'metodo.php?metodo=getProximoNumeroDocumentoOficial',
                    method: 'POST',
                    data: { tipo: $('#docTipo').val(), ano: $('#docAno').val() },
                    success: function (res) {
                        if (res && res.success) {
                            $('#docNumero').val(res.proximo_numero);
                        }
                    }
                });
            }
        });

        // Alternar entre Modo Visual e Modo Split
        $('#btnModeVisual').click(function () {
            $(this).addClass('active-mode blue white-text').removeClass('btn-flat');
            $('#btnModeSplit').removeClass('active-mode blue white-text').addClass('btn-flat');
            $('#docModoEditor').val('visual');

            // Copia texto do split pro visual se necessário
            let code = $('#editorSplitCode').val();
            if (code && !$('#editorVisualDiv').html().trim()) {
                $('#editorVisualDiv').html(code.replace(/\n/g, '<br>'));
            }

            $('#wrapperEditorVisual').removeClass('hide');
            $('#wrapperEditorSplit').addClass('hide');
        });

        $('#btnModeSplit').click(function () {
            $(this).addClass('active-mode blue white-text').removeClass('btn-flat');
            $('#btnModeVisual').removeClass('active-mode blue white-text').addClass('btn-flat');
            $('#docModoEditor').val('split_code');

            // Copia HTML do visual pro split
            let html = $('#editorVisualDiv').html();
            if (html && !$('#editorSplitCode').val().trim()) {
                $('#editorSplitCode').val(html);
            }

            $('#wrapperEditorVisual').addClass('hide');
            $('#wrapperEditorSplit').removeClass('hide');

            // Compila preview inicial
            triggerLivePreview();
        });

        // Alternar template global vs clear
        $('input[name="modo_template"]').change(function () {
            if ($(this).val() === 'clear') {
                $('#containerVarianteGlobal').fadeOut();
            } else {
                $('#containerVarianteGlobal').fadeIn();
            }
        });

        // Novo Documento
        $('#btnNovoDocumento').click(function () {
            $('#modalDocTitle').html('<i class="material-icons left purple-text">edit_note</i>Redação de Documento Oficial');
            $('#docId').val('0');
            $('#docTitulo').val('');
            $('#docEmenta').val('');
            $('#editorSplitCode').val('= Título da Seção\n\nTexto do parecer ou orientação técnica aqui...');
            $('#tmplModeGlobal').prop('checked', true).trigger('change');
            $('#docTipo').trigger('change');
            $('#modalDocumentoOficial').modal('open');
            setTimeout(function () {
                triggerLivePreview();
            }, 300);
        });

        // Editar Documento
        $(document).on('click', '.btnEditarDoc', function () {
            let id = $(this).attr('docid');
            M.toast({ html: 'Carregando documento...', classes: 'rounded blue' });

            $.ajax({
                url: 'metodo.php?metodo=getDocumentoOficialById',
                method: 'POST',
                data: { id: id },
                success: function (res) {
                    if (res && res.success && res.data) {
                        let doc = res.data;
                        let numFmt = ("000" + doc.numero).slice(-3) + "/" + doc.ano;
                        $('#modalDocTitle').html('<i class="material-icons left purple-text">edit_note</i>Editar Documento Oficial Nº ' + numFmt);
                        $('#docId').val(doc.id);
                        $('#docTipo').val(doc.tipo);
                        $('#docNumero').val(doc.numero);
                        $('#docAno').val(doc.ano);
                        $('#docRelator').val(doc.relator || 'Conselho Consultivo e Fiscal');
                        $('#docDataEmissao').val(doc.data_emissao);
                        $('#docTitulo').val(doc.titulo);
                        $('#docEmenta').val(doc.ementa || '');

                        if (doc.modo_template === 'clear') {
                            $('#tmplModeClear').prop('checked', true).trigger('change');
                        } else {
                            $('#tmplModeGlobal').prop('checked', true).trigger('change');
                        }
                        if (doc.variante_global) {
                            $('#docVarianteGlobal').val(doc.variante_global);
                        }

                        $('#editorSplitCode').val(doc.conteudo || '');
                        $('#modalDocumentoOficial').modal('open');
                        setTimeout(function () {
                            triggerLivePreview();
                        }, 300);
                    } else {
                        M.toast({ html: (res.error || 'Erro ao carregar documento'), classes: 'rounded red' });
                    }
                }
            });
        });

        // Live Preview PDF no modo Split
        $('#btnLiveCompile').click(function () {
            triggerLivePreview();
        });

        // Compilação Dinâmica Automática ao Digitar (Debounce de 600ms)
        let autoCompileTimer = null;
        $('#editorSplitCode, #docTitulo, #docEmenta, #docRelator, #docNumero, #docAno').on('input keyup change', function () {
            if ($('#modalDocumentoOficial').is(':visible')) {
                clearTimeout(autoCompileTimer);
                autoCompileTimer = setTimeout(function () {
                    triggerLivePreview();
                }, 600);
            }
        });

        $('#docTipo, #docVarianteGlobal, input[name="modo_template"]').on('change', function () {
            if ($('#modalDocumentoOficial').is(':visible')) {
                triggerLivePreview();
            }
        });

        function triggerLivePreview() {
            $('#splitPdfPreviewContainer').html('<p class="center blue-text"><i class="material-icons left spinning">refresh</i> Compilando PDF via Typst (Porta 5050)...</p>');

            let formData = {
                id: $('#docId').val(),
                tipo: $('#docTipo').val(),
                numero: $('#docNumero').val(),
                ano: $('#docAno').val(),
                titulo: $('#docTitulo').val(),
                ementa: $('#docEmenta').val(),
                relator: $('#docRelator').val(),
                data_emissao: $('#docDataEmissao').val(),
                modo_editor: 'split_code',
                modo_template: $('input[name="modo_template"]:checked').val(),
                variante_global: $('#docVarianteGlobal').val(),
                conteudo: $('#editorSplitCode').val()
            };

            $.ajax({
                url: 'metodo.php?metodo=previewDocumentoOficial',
                method: 'POST',
                data: formData,
                success: function (res) {
                    if (res && res.status === 'success' && res.pdf_base64) {
                        let pdfData = "data:application/pdf;base64," + res.pdf_base64;
                        $('#splitPdfPreviewContainer').html(`<iframe src="${pdfData}" style="width:100%; height:100%; border:none;"></iframe>`);
                    } else {
                        let errStr = (res && res.message) ? res.message : 'Erro ao compilar PDF.';
                        $('#splitPdfPreviewContainer').html(`<div style="padding:15px; color:#ef4444; font-size:0.85rem;"><b>Erro Typst:</b><br><pre>${errStr}</pre></div>`);
                    }
                }
            });
        }

        // Salvar Documento
        $('#btnSalvarDocumentoOficial').click(function () {
            let conteudoFinal = $('#editorSplitCode').val();

            if (!$('#docTitulo').val().trim()) {
                return M.toast({ html: 'Informe o título do documento!', classes: 'rounded red' });
            }

            let payload = {
                id: $('#docId').val(),
                tipo: $('#docTipo').val(),
                numero: $('#docNumero').val(),
                ano: $('#docAno').val(),
                titulo: $('#docTitulo').val(),
                ementa: $('#docEmenta').val(),
                relator: $('#docRelator').val(),
                data_emissao: $('#docDataEmissao').val(),
                modo_editor: $('#docModoEditor').val(),
                modo_template: $('input[name="modo_template"]:checked').val(),
                variante_global: $('#docVarianteGlobal').val(),
                conteudo: conteudoFinal
            };

            $.ajax({
                url: 'metodo.php?metodo=salvarDocumentoOficial',
                method: 'POST',
                data: payload,
                success: function (res) {
                    if (res && res.success) {
                        M.toast({ html: 'Documento Oficial salvo com sucesso!', classes: 'rounded green' });
                        $('#modalDocumentoOficial').modal('close');
                        window.location.reload();
                    } else {
                        M.toast({ html: (res.error || 'Erro ao salvar documento'), classes: 'rounded red' });
                    }
                }
            });
        });

        // Botão Ver PDF na Tabela
        $(document).on('click', '.btnPreviewDoc', function (e) {
            let btn = $(this).closest('.btnPreviewDoc');
            let id = btn.attr('docid') || btn.data('docid');
            M.toast({ html: 'Gerando visualização...', classes: 'rounded blue' });

            $.ajax({
                url: 'metodo.php?metodo=previewDocumentoOficial',
                method: 'POST',
                data: { id: id },
                success: function (res) {
                    if (res && res.status === 'success' && res.pdf_base64) {
                        let pdfData = "data:application/pdf;base64," + res.pdf_base64;
                        $('#iframePdfFinal').attr('src', pdfData);
                        $('#modalPreviewPdfFinal').modal('open');
                    } else {
                        M.toast({ html: (res.message || 'Erro ao gerar PDF'), classes: 'rounded red' });
                    }
                }
            });
        });

        // Botão Excluir Documento
        $(document).on('click', '.btnDeletarDoc', function () {
            let id = $(this).attr('docid');
            let num = $(this).attr('num');
            if (!confirm(`Deseja realmente excluir o documento oficial ${num}?`)) return;

            $.ajax({
                url: 'metodo.php?metodo=deletarDocumentoOficial',
                method: 'POST',
                data: { id: id },
                success: function (res) {
                    if (res && res.success) {
                        M.toast({ html: 'Documento excluído com sucesso!', classes: 'rounded orange' });
                        window.location.reload();
                    } else {
                        M.toast({ html: (res.error || 'Erro ao excluir'), classes: 'rounded red' });
                    }
                }
            });
        });
    });
</script>