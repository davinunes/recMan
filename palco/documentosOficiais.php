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
    'orientacao_tecnica'  => 'Orientação Técnica',
    'parecer_opinativo'   => 'Parecer Opinativo',
    'entendimento'        => 'Entendimento do Conselho',
    'instrucao_normativa' => 'Instrução Normativa'
];

$tiposCores = [
    'orientacao_tecnica'  => 'blue darken-2',
    'parecer_opinativo'   => 'purple darken-2',
    'entendimento'        => 'teal darken-2',
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
        box-shadow: 0 6px 15px rgba(0,0,0,0.1) !important;
    }
    .editor-btn-group .btn {
        margin-right: 5px;
    }
    .split-container {
        display: flex;
        gap: 15px;
        min-height: 500px;
    }
    .split-pane {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .rich-toolbar button {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 4px 8px;
        cursor: pointer;
        font-weight: bold;
        margin-right: 2px;
    }
    .rich-toolbar button:hover {
        background: #e2e8f0;
    }
</style>

<div class="container-fluid" style="padding: 0 20px; margin-top: 20px;">
    <!-- Cabeçalho -->
    <div class="row">
        <div class="col s12 m8">
            <h4><i class="material-icons left purple-text text-darken-2" style="font-size: 2.5rem;">gavel</i>Documentos Oficiais do Conselho</h4>
            <p class="grey-text">Gestão, emissão e numeração sequencial de Orientações Técnicas, Pareceres Opinativos e Normas.</p>
        </div>
        <div class="col s12 m4 right-align" style="margin-top: 20px;">
            <button class="btn green darken-2 waves-effect waves-light btn-large" id="btnNovoDocumento" style="border-radius: 8px;">
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
                            <select name="tipo" class="browser-default" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 8px;">
                                <option value="">Todos os Tipos</option>
                                <?php foreach ($tiposNomes as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php echo $filtroTipo === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-field col s12 m2">
                            <input type="number" name="ano" value="<?php echo htmlspecialchars($filtroAno); ?>" placeholder="Ex: 2026">
                        </div>
                        <div class="input-field col s12 m5">
                            <input type="text" name="busca" value="<?php echo htmlspecialchars($filtroBusca); ?>" placeholder="Pesquisar por título, ementa ou palavra-chave...">
                        </div>
                        <div class="input-field col s12 m2 right-align">
                            <button type="submit" class="btn blue waves-effect waves-light style-btn-search" style="border-radius: 6px; width: 100%;">
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
                                            <i class="material-icons tiny left blue-text text-darken-2" style="margin-top: 3px;">description</i>
                                            <?php echo $nomeTipo . ' nº ' . $numFormatado; ?>
                                        </td>
                                        <td>
                                            <span class="chip white-text <?php echo $corBadge; ?>" style="font-size: 0.75rem; height: 24px; line-height: 24px;">
                                                <?php echo $nomeTipo; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($doc['titulo']); ?></span>
                                            <?php if (!empty($doc['ementa'])): ?>
                                                <span class="grey-text text-darken-1 truncate" style="font-size: 0.82rem; display: block; max-width: 380px;">
                                                    <?php echo htmlspecialchars($doc['ementa']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="grey-text text-darken-2" style="font-size: 0.9rem;"><?php echo htmlspecialchars($doc['relator'] ?: 'Conselho'); ?></td>
                                        <td class="grey-text text-darken-2" style="font-size: 0.9rem;"><?php echo $dtFormatada; ?></td>
                                        <td>
                                            <?php if ($doc['modo_template'] === 'clear'): ?>
                                                <span class="chip grey lighten-3 grey-text text-darken-3" style="font-size: 0.75rem; height: 22px; line-height: 22px;">Raw Typst</span>
                                            <?php else: ?>
                                                <span class="chip blue lighten-5 blue-text text-darken-3" style="font-size: 0.75rem; height: 22px; line-height: 22px;"><?php echo htmlspecialchars($doc['variante_global'] ?: 'Global'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="right-align" style="padding-right: 20px;">
                                            <button class="btn-flat btn-small waves-effect waves-blue btnPreviewDoc" docid="<?php echo $doc['id']; ?>" title="Visualizar PDF">
                                                <i class="material-icons blue-text text-darken-2">visibility</i>
                                            </button>
                                            <button class="btn-flat btn-small waves-effect waves-orange btnEditarDoc" docid="<?php echo $doc['id']; ?>" title="Editar">
                                                <i class="material-icons orange-text text-darken-3">edit</i>
                                            </button>
                                            <button class="btn-flat btn-small waves-effect waves-red btnDeletarDoc" docid="<?php echo $doc['id']; ?>" num="<?php echo $numFormatado; ?>" title="Excluir">
                                                <i class="material-icons red-text text-darken-2">delete</i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="center-align grey-text" style="padding: 40px;">
                                        <i class="material-icons medium grey-text text-lighten-1" style="display: block; margin-bottom: 10px;">folder_open</i>
                                        Nenhum documento oficial cadastrado. Clique no botão <b>"Novo Documento Oficial"</b> para criar.
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
<div id="modalDocumentoOficial" class="modal modal-fixed-footer" style="width: 96% !important; max-height: 95% !important; height: 95% !important; top: 2.5% !important; border-radius: 12px;">
    <div class="modal-content" style="padding: 20px; height: calc(100% - 60px); overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 15px;">
            <h4 style="margin: 0; font-size: 1.4rem; font-weight: 700; color: #0f172a;" id="modalDocTitle">
                <i class="material-icons left purple-text">edit_note</i>Redação de Documento Oficial
            </h4>
            
            <!-- Alternador de Modo de Editor (WYSIWYG ↔ Split Code) -->
            <div class="editor-btn-group" style="background: #f1f5f9; padding: 4px; border-radius: 8px; border: 1px solid #cbd5e1;">
                <button type="button" class="btn-flat btn-small waves-effect active-mode" id="btnModeVisual" style="border-radius: 6px; font-weight: 600;">
                    <i class="material-icons left tiny">draw</i>Modo Visual (WYSIWYG)
                </button>
                <button type="button" class="btn-flat btn-small waves-effect" id="btnModeSplit" style="border-radius: 6px; font-weight: 600;">
                    <i class="material-icons left tiny">vertical_split</i>Modo Split (Code & Live PDF)
                </button>
            </div>
        </div>

        <form id="formDocOficial">
            <input type="hidden" id="docId" name="id" value="0">
            <input type="hidden" id="docModoEditor" name="modo_editor" value="visual">

            <!-- Linha 1: Tipo, Número, Ano, Relator e Data -->
            <div class="row" style="margin-bottom: 10px;">
                <div class="col s12 m3">
                    <label style="font-weight: bold; color: #334155;">Tipo de Documento:</label>
                    <select id="docTipo" name="tipo" class="browser-default" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 8px;">
                        <option value="orientacao_tecnica">Orientação Técnica</option>
                        <option value="parecer_opinativo">Parecer Opinativo</option>
                        <option value="entendimento">Entendimento do Conselho</option>
                        <option value="instrucao_normativa">Instrução Normativa</option>
                    </select>
                </div>
                <div class="col s6 m2">
                    <label style="font-weight: bold; color: #334155;">Número:</label>
                    <input type="number" id="docNumero" name="numero" placeholder="Auto" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px;">
                </div>
                <div class="col s6 m2">
                    <label style="font-weight: bold; color: #334155;">Ano:</label>
                    <input type="number" id="docAno" name="ano" value="<?php echo date('Y'); ?>" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px;">
                </div>
                <div class="col s12 m3">
                    <label style="font-weight: bold; color: #334155;">Relator / Emissor:</label>
                    <input type="text" id="docRelator" name="relator" value="Conselho Consultivo e Fiscal" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px;">
                </div>
                <div class="col s12 m2">
                    <label style="font-weight: bold; color: #334155;">Data Emissão:</label>
                    <input type="date" id="docDataEmissao" name="data_emissao" value="<?php echo date('Y-m-d'); ?>" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px;">
                </div>
            </div>

            <!-- Linha 2: Título e Ementa -->
            <div class="row" style="margin-bottom: 10px;">
                <div class="col s12 m6">
                    <label style="font-weight: bold; color: #334155;">Título do Documento Oficial:</label>
                    <input type="text" id="docTitulo" name="titulo" placeholder="Ex: Regras de Utilização do Salão de Festas e Áreas Comuns" required style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 8px;">
                </div>
                <div class="col s12 m6">
                    <label style="font-weight: bold; color: #334155;">Ementa / Resumo Oficial (Opção Notarial):</label>
                    <input type="text" id="docEmenta" name="ementa" placeholder="Ex: Dispõe sobre critérios de reserva, taxa de limpeza e penalidades aplicáveis..." style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 8px;">
                </div>
            </div>

            <!-- Linha 3: Modo de Template e Variante Global -->
            <div class="row" style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                <div class="col s12 m4">
                    <label style="font-weight: bold; color: #0f172a; display: block; margin-bottom: 6px;">Aplicação de Template:</label>
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
                    <label style="font-weight: bold; color: #0f172a; display: block; margin-bottom: 6px;">Variante Visual Global:</label>
                    <select id="docVarianteGlobal" name="variante_global" class="browser-default" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 6px 10px; background: white;">
                        <option value="top_header" selected>Novo Topo 1ª Pág (lay-top-fist-page-1.png)</option>
                        <option value="watermark_a4">Nova Marca d'Água Full A4 (lay-body-all-pages-1.png)</option>
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

            <!-- CONTAINER DE EDITOR (MODO VISUAL vs MODO SPLIT) -->
            <div id="wrapperEditorVisual" class="row">
                <div class="col s12">
                    <label style="font-weight: bold; color: #334155; display: block; margin-bottom: 6px;">Corpo do Documento (Editor Visual):</label>
                    <div class="rich-toolbar" style="margin-bottom: 6px;">
                        <button type="button" onclick="execEditorCmd('bold')"><b>B</b></button>
                        <button type="button" onclick="execEditorCmd('italic')"><i>I</i></button>
                        <button type="button" onclick="execEditorCmd('underline')"><u>U</u></button>
                        <button type="button" onclick="execEditorCmd('formatBlock', 'h1')">H1</button>
                        <button type="button" onclick="execEditorCmd('formatBlock', 'h2')">H2</button>
                        <button type="button" onclick="execEditorCmd('insertUnorderedList')">• Lista</button>
                        <button type="button" onclick="execEditorCmd('insertOrderedList')">1. Lista</button>
                    </div>
                    <div id="editorVisualDiv" contenteditable="true" style="min-height: 480px; max-height: 600px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; background: white; overflow-y: auto;">
                        <p>Escreva o conteúdo do seu parecer ou orientação técnica aqui...</p>
                    </div>
                </div>
            </div>

            <div id="wrapperEditorSplit" class="row hide">
                <div class="col s12">
                    <div class="split-container" style="display: flex; gap: 15px; height: calc(100vh - 310px); min-height: 520px;">
                        <!-- Painel Código Left -->
                        <div class="split-pane" style="flex: 1; display: flex; flex-direction: column;">
                            <label style="font-weight: bold; color: #334155; display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span>Código Fonte (Typst / Markdown):</span>
                                <button type="button" class="btn-flat btn-small blue-text" id="btnLiveCompile">
                                    <i class="material-icons left tiny">play_arrow</i>Atualizar PDF Preview
                                </button>
                            </label>
                            <textarea id="editorSplitCode" name="conteudo" style="width: 100%; height: 100%; font-family: monospace; font-size: 0.9rem; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #0f172a; color: #f8fafc; line-height: 1.5; resize: none;"></textarea>
                        </div>
                        <!-- Painel Live PDF Right -->
                        <div class="split-pane" style="flex: 1; display: flex; flex-direction: column;">
                            <label style="font-weight: bold; color: #334155; display: block; margin-bottom: 6px;">Live PDF Preview (Typst Porta 5050):</label>
                            <div id="splitPdfPreviewContainer" style="width: 100%; height: 100%; border: 1px solid #cbd5e1; border-radius: 8px; background: #e2e8f0; display: flex; align-items: center; justify-content: center;">
                                <span class="grey-text">Clique em "Atualizar PDF Preview" para compilar...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer" style="padding: 10px 20px;">
        <button type="button" class="modal-close btn-flat waves-effect" style="margin-right: 10px;">Cancelar</button>
        <button type="button" class="btn green darken-2 waves-effect waves-light" id="btnSalvarDocumentoOficial" style="border-radius: 6px;">
            <i class="material-icons left">save</i>Salvar Documento
        </button>
    </div>
</div>

<!-- MODAL PREVIEW DE PDF FINAL -->
<div id="modalPreviewPdfFinal" class="modal modal-fixed-footer" style="width: 95% !important; max-height: 94% !important; height: 94% !important; top: 3% !important; border-radius: 12px;">
    <div class="modal-content" style="padding: 0; height: calc(100% - 56px);">
        <iframe id="iframePdfFinal" style="width: 100%; height: 100%; border: none;"></iframe>
    </div>
    <div class="modal-footer" style="padding: 5px 20px;">
        <a href="#!" class="modal-close btn-flat waves-effect bold">Fechar Visualização</a>
    </div>
</div>

<script>
function execEditorCmd(cmd, value = null) {
    document.execCommand(cmd, false, value);
}

$(document).ready(function() {
    $('.modal').modal();
    
    // Atualizar próximo número ao alterar Tipo ou Ano
    $('#docTipo, #docAno').on('change', function() {
        if ($('#docId').val() == "0") {
            $.ajax({
                url: 'metodo.php?metodo=getProximoNumeroDocumentoOficial',
                method: 'POST',
                data: { tipo: $('#docTipo').val(), ano: $('#docAno').val() },
                success: function(res) {
                    if (res && res.success) {
                        $('#docNumero').val(res.proximo_numero);
                    }
                }
            });
        }
    });

    // Alternar entre Modo Visual e Modo Split
    $('#btnModeVisual').click(function() {
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

    $('#btnModeSplit').click(function() {
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
    $('input[name="modo_template"]').change(function() {
        if ($(this).val() === 'clear') {
            $('#containerVarianteGlobal').fadeOut();
        } else {
            $('#containerVarianteGlobal').fadeIn();
        }
    });

    // Novo Documento
    $('#btnNovoDocumento').click(function() {
        $('#modalDocTitle').html('<i class="material-icons left purple-text">edit_note</i>Redação de Documento Oficial');
        $('#docId').val('0');
        $('#docTitulo').val('');
        $('#docEmenta').val('');
        $('#editorVisualDiv').html('<p>Escreva o conteúdo do seu parecer ou orientação técnica aqui...</p>');
        $('#editorSplitCode').val('');
        $('#tmplModeGlobal').prop('checked', true).trigger('change');
        $('#docTipo').trigger('change');
        $('#btnModeVisual').trigger('click');
        $('#modalDocumentoOficial').modal('open');
    });

    // Editar Documento
    $(document).on('click', '.btnEditarDoc', function() {
        let id = $(this).attr('docid');
        M.toast({ html: 'Carregando documento...', classes: 'rounded blue' });
        
        $.ajax({
            url: 'metodo.php?metodo=getDocumentoOficialById',
            method: 'POST',
            data: { id: id },
            success: function(res) {
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

                    let modo = doc.modo_editor || 'visual';
                    if (modo === 'split_code') {
                        $('#btnModeSplit').trigger('click');
                        $('#editorSplitCode').val(doc.conteudo || '');
                        $('#editorVisualDiv').html('');
                    } else {
                        $('#btnModeVisual').trigger('click');
                        $('#editorVisualDiv').html(doc.conteudo || '');
                        $('#editorSplitCode').val('');
                    }

                    $('#modalDocumentoOficial').modal('open');
                } else {
                    M.toast({ html: (res.error || 'Erro ao carregar documento'), classes: 'rounded red' });
                }
            }
        });
    });

    // Live Preview PDF no modo Split
    $('#btnLiveCompile').click(function() {
        triggerLivePreview();
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
            modo_editor: $('#docModoEditor').val(),
            modo_template: $('input[name="modo_template"]:checked').val(),
            variante_global: $('#docVarianteGlobal').val(),
            conteudo: $('#docModoEditor').val() === 'visual' ? $('#editorVisualDiv').html() : $('#editorSplitCode').val()
        };

        $.ajax({
            url: 'metodo.php?metodo=previewDocumentoOficial',
            method: 'POST',
            data: formData,
            success: function(res) {
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
    $('#btnSalvarDocumentoOficial').click(function() {
        let conteudoFinal = $('#docModoEditor').val() === 'visual' ? $('#editorVisualDiv').html() : $('#editorSplitCode').val();
        
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
            success: function(res) {
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
    $(document).on('click', '.btnPreviewDoc', function() {
        let id = $(this).attr('docid');
        M.toast({ html: 'Gerando visualização...', classes: 'rounded blue' });
        
        $.ajax({
            url: 'metodo.php?metodo=previewDocumentoOficial',
            method: 'POST',
            data: { id: id },
            success: function(res) {
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
    $(document).on('click', '.btnDeletarDoc', function() {
        let id = $(this).attr('docid');
        let num = $(this).attr('num');
        if (!confirm(`Deseja realmente excluir o documento oficial ${num}?`)) return;

        $.ajax({
            url: 'metodo.php?metodo=deletarDocumentoOficial',
            method: 'POST',
            data: { id: id },
            success: function(res) {
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
