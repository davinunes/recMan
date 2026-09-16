<?php
require_once "classes/repositorio.php";
?>

<div class="container">
    <div class="row">
        <div class="col s12">
            <h4><i class="material-icons left purple-text" style="font-size: 2.5rem;">psychology</i>Configurações da IA (Gemini)</h4>
            <p>Gerencie as instruções e prompts utilizados pela Inteligência Artificial para gerar as sugestões automáticas de parecer.</p>
        </div>
    </div>

    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title" style="display: flex; align-items: center; gap: 8px;">
                        <i class="material-icons purple-text">settings</i> Instruções e Schema do Modelo
                    </span>
                    <p style="margin-bottom: 20px;" class="grey-text">
                        A IA utiliza um prompt principal combinado com descrições específicas para cada um dos campos retornados no parecer final. 
                        Preencha as diretrizes abaixo para guiar a redação automática.
                    </p>
                    
                    <form id="formConfigGemini">
                        <div class="row">
                            <div class="input-field col s12 m7">
                                <input type="password" id="geminiApiKeyInput" value="<?php echo htmlspecialchars(getConfigSistema('gemini_api_key') ?? ''); ?>" placeholder="AIzaSy...">
                                <label for="geminiApiKeyInput">Chave de API do Gemini (GEMINI_API_KEY)</label>
                                <span class="helper-text">Deixe em branco para usar o valor definido no arquivo .env</span>
                            </div>
                            <div class="input-field col s12 m5">
                                <?php $geminiModel = getConfigSistema('gemini_modelo') ?: 'gemini-2.5-flash'; ?>
                                <select id="geminiModelInput">
                                    <option value="gemini-2.5-flash" <?php echo ($geminiModel === 'gemini-2.5-flash') ? 'selected' : ''; ?>>Gemini 2.5 Flash (Recomendado)</option>
                                    <option value="gemini-2.0-flash" <?php echo ($geminiModel === 'gemini-2.0-flash') ? 'selected' : ''; ?>>Gemini 2.0 Flash</option>
                                    <option value="gemini-1.5-flash" <?php echo ($geminiModel === 'gemini-1.5-flash') ? 'selected' : ''; ?>>Gemini 1.5 Flash (Estável)</option>
                                </select>
                                <label for="geminiModelInput">Modelo do Gemini</label>
                                <span class="helper-text">Fallback automático para 1.5 em caso de instabilidade</span>
                            </div>
                        </div>
                        <div class="row" style="margin-top: -5px; margin-bottom: 25px;">
                            <div class="col s12">
                                <button type="button" class="btn-small purple lighten-1 waves-effect waves-light" id="btnProbeGemini">
                                    <i class="material-icons left">sensors</i>Testar Chave e Sondar Modelos (Probe)
                                </button>
                                <span class="grey-text text-darken-1" style="font-size: 0.85rem; margin-left: 12px;">
                                    Consulta o endpoint oficial <code>models?key=...</code> para listar modelos ativos e latência da sua chave.
                                </span>
                            </div>
                        </div>

                        <!-- Card de Resultado da Probe -->
                        <div id="probeContainer" style="display: none; margin-bottom: 25px;">
                            <div class="card grey lighten-5 z-depth-1" style="border-left: 4px solid #7b1fa2; border-radius: 4px;">
                                <div class="card-content" style="padding: 16px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                                        <span style="font-weight: 600; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                                            <i class="material-icons purple-text">wifi_tethering</i>
                                            Diagnóstico da Chave e Modelos Disponíveis
                                        </span>
                                        <div id="probeBadges" style="display: flex; gap: 8px;"></div>
                                    </div>
                                    <div id="probeContent"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s12">
                                <textarea id="geminiPromptMainInput" class="materialize-textarea" style="min-height: 100px; font-family: monospace; font-size: 0.9rem;"><?php 
                                    $promptMain = getConfigSistema('gemini_prompt_main');
                                    if (empty($promptMain)) {
                                        // Tenta a chave antiga para migração suave
                                        $promptMain = getConfigSistema('gemini_system_prompt');
                                    }
                                    if (empty($promptMain)) {
                                        $promptMain = "Você é um assistente de inteligência artificial encarregado de redigir pareceres formais e profissionais para o Conselho Consultivo e Fiscal de um condomínio residencial de alto padrão.\nSeu objetivo é sugerir textos profissionais e bem redigidos para cada campo do Parecer com base nas informações fornecidas.\n\nInstruções importantes para a redação:\n1. O estilo deve ser extremamente formal, profissional, claro e objetivo, adequado para um parecer administrativo/jurídico de condomínio de luxo.\n2. Evite linguagem informal. Utilize a norma padrão da língua portuguesa (Português do Brasil).\n3. Adapte a fundamentação e a análise à decisão do Conselho:\n   - Se for MANTER, justifique tecnicamente por que a multa/advertência está de acordo com as regras e por que a defesa do morador não prospera.\n   - Se for REVOGAR, explique com razoabilidade por que a infração deve ser desconsiderada/anulada.\n   - Se for CONVERTER, fundamente a aplicação da conversão da multa para advertência (ex: por primariedade ou menor gravidade do fato).";
                                    }
                                    echo htmlspecialchars($promptMain); 
                                ?></textarea>
                                <label for="geminiPromptMainInput">Prompt Principal do Sistema (Persona e Regras Gerais)</label>
                            </div>
                        </div>

                        <div class="row" style="margin-top: 20px;">
                            <div class="col s12">
                                <span class="card-title" style="font-size: 1.1rem; font-weight: bold; margin-bottom: 15px; display: block; color: #7b1fa2;">Descrições do Schema JSON (Instruções por Campo)</span>
                                <p class="grey-text text-darken-1" style="font-size: 0.9rem; margin-bottom: 20px;">
                                    Defina as instruções de redação específicas para cada campo do parecer. Elas guiarão a geração de conteúdo focada por campo.
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="input-field col s12 m6">
                                <textarea id="geminiDescAssuntoInput" class="materialize-textarea" style="font-family: monospace; font-size: 0.9rem;"><?php 
                                    $descAssunto = getConfigSistema('gemini_desc_assunto');
                                    if (empty($descAssunto)) {
                                        $descAssunto = 'Título formal do parecer (ex: "Parecer do Conselho - Recurso de Notificação nº [NUMERO_RECURSO]").';
                                    }
                                    echo htmlspecialchars($descAssunto); 
                                ?></textarea>
                                <label for="geminiDescAssuntoInput">Instrução para 'Assunto'</label>
                            </div>
                            
                            <div class="input-field col s12 m6">
                                <textarea id="geminiDescNotificacaoInput" class="materialize-textarea" style="font-family: monospace; font-size: 0.9rem;"><?php 
                                    $descNotificacao = getConfigSistema('gemini_desc_notificacao');
                                    if (empty($descNotificacao)) {
                                        $descNotificacao = 'Resumo profissional do fato/infração ocorrido.';
                                    }
                                    echo htmlspecialchars($descNotificacao); 
                                ?></textarea>
                                <label for="geminiDescNotificacaoInput">Instrução para 'Notificação'</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="input-field col s12 m6">
                                <textarea id="geminiDescAnaliseInput" class="materialize-textarea" style="font-family: monospace; font-size: 0.9rem;"><?php 
                                    $descAnalise = getConfigSistema('gemini_desc_analise');
                                    if (empty($descAnalise)) {
                                        $descAnalise = 'Fundamentação técnica confrontando os argumentos do morador com o regimento interno.';
                                    }
                                    echo htmlspecialchars($descAnalise); 
                                ?></textarea>
                                <label for="geminiDescAnaliseInput">Instrução para 'Análise'</label>
                            </div>

                            <div class="input-field col s12 m6">
                                <textarea id="geminiDescResultadoInput" class="materialize-textarea" style="font-family: monospace; font-size: 0.9rem;"><?php 
                                    $descResultado = getConfigSistema('gemini_desc_resultado');
                                    if (empty($descResultado)) {
                                        $descResultado = 'Considerações finais baseadas nos votos/debates dos conselheiros.';
                                    }
                                    echo htmlspecialchars($descResultado); 
                                ?></textarea>
                                <label for="geminiDescResultadoInput">Instrução para 'Resultado'</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="input-field col s12">
                                <textarea id="geminiDescConclusaoInput" class="materialize-textarea" style="font-family: monospace; font-size: 0.9rem;"><?php 
                                    $descConclusao = getConfigSistema('gemini_desc_conclusao');
                                    if (empty($descConclusao)) {
                                        $descConclusao = 'Decisão e veredito final em letras maiúsculas (ex: "MANTIDA A PENALIDADE DE MULTA", "REVOGADA A PENALIDADE APLICADA", "CONVERTIDA A PENALIDADE DE MULTA EM ADVERTÊNCIA").';
                                    }
                                    echo htmlspecialchars($descConclusao); 
                                ?></textarea>
                                <label for="geminiDescConclusaoInput">Instrução para 'Conclusão'</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-action">
                    <button type="button" class="btn purple darken-2 waves-effect waves-light" id="btnSaveConfigGemini">
                        <i class="material-icons left">save</i>Salvar Configurações da IA
                    </button>
                    <a href="index.php?pag=tools" class="btn-flat waves-effect waves-purple" style="margin-left: 10px;">
                        Voltar para Ferramentas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Inicializar select do Materialize
    $('select').formSelect();

    // Garantir o auto-resize das textareas do Materialize
    setTimeout(function() {
        M.textareaAutoResize($('#geminiPromptMainInput'));
        M.textareaAutoResize($('#geminiDescAssuntoInput'));
        M.textareaAutoResize($('#geminiDescNotificacaoInput'));
        M.textareaAutoResize($('#geminiDescAnaliseInput'));
        M.textareaAutoResize($('#geminiDescResultadoInput'));
        M.textareaAutoResize($('#geminiDescConclusaoInput'));
    }, 200);

    $('#btnSaveConfigGemini').click(function(){
        const apiKey = $('#geminiApiKeyInput').val();
        const geminiModel = $('#geminiModelInput').val();
        const promptMain = $('#geminiPromptMainInput').val();
        const descAssunto = $('#geminiDescAssuntoInput').val();
        const descNotificacao = $('#geminiDescNotificacaoInput').val();
        const descAnalise = $('#geminiDescAnaliseInput').val();
        const descResultado = $('#geminiDescResultadoInput').val();
        const descConclusao = $('#geminiDescConclusaoInput').val();
        
        const btn = $(this);
        btn.addClass('disabled').text('Salvando...');

        const configs = {
            gemini_api_key: apiKey,
            gemini_modelo: geminiModel,
            gemini_prompt_main: promptMain,
            gemini_desc_assunto: descAssunto,
            gemini_desc_notificacao: descNotificacao,
            gemini_desc_analise: descAnalise,
            gemini_desc_resultado: descResultado,
            gemini_desc_conclusao: descConclusao
        };

        $.post('metodo.php?metodo=upsertMultipleConfigSistema', { configs: configs }, function(res){
            btn.removeClass('disabled').html('<i class="material-icons left">save</i>Salvar Configurações da IA');
            if(res.trim() === 'ok'){
                M.toast({html: 'Configurações da IA salvas com sucesso!', classes: 'green rounded'});
            } else {
                M.toast({html: 'Erro ao salvar as configurações: ' + res, classes: 'red rounded'});
            }
        });
    });

    // Probe da API do Gemini
    $('#btnProbeGemini').click(function(){
        const btn = $(this);
        const apiKey = $('#geminiApiKeyInput').val();
        
        btn.addClass('disabled').html('<i class="material-icons left">hourglass_empty</i>Sondando...');
        $('#probeContainer').slideDown();
        $('#probeBadges').empty();
        $('#probeContent').html('<div class="center-align" style="padding: 20px;"><div class="preloader-wrapper small active"><div class="spinner-layer spinner-purple-only"><div class="circle-clipper left"><div class="circle"></div></div><div class="gap-patch"><div class="circle"></div></div><div class="circle-clipper right"><div class="circle"></div></div></div></div><p class="grey-text" style="margin-top: 10px;">Consultando endpoint models da API Gemini...</p></div>');

        $.post('metodo.php?metodo=probeModelosGemini', { api_key: apiKey }, function(res){
            btn.removeClass('disabled').html('<i class="material-icons left">sensors</i>Testar Chave e Sondar Modelos (Probe)');
            
            if (!res.success) {
                $('#probeBadges').html('<span class="new badge red" data-badge-caption="Falha"></span>');
                let errHtml = '<div class="card-panel red lighten-4 red-text text-darken-4" style="padding: 12px; margin: 0; border-radius: 4px;">';
                errHtml += '<strong>Erro na sonda da API:</strong> ' + (res.error || 'Erro desconhecido');
                if (res.httpCode) errHtml += ' (HTTP ' + res.httpCode + ')';
                if (res.latencyMs) errHtml += ' <span class="grey-text text-darken-2">| Latência: ' + res.latencyMs + 'ms</span>';
                errHtml += '</div>';
                $('#probeContent').html(errHtml);
                return;
            }

            $('#probeBadges').html(
                '<span class="new badge green" data-badge-caption="Conectado (' + res.latencyMs + 'ms)"></span>' +
                '<span class="new badge purple" data-badge-caption="' + res.total + ' modelos"></span>'
            );

            let html = '<div style="max-height: 380px; overflow-y: auto;">';
            html += '<table class="highlight compact responsive-table" style="font-size: 0.88rem;">';
            html += '<thead><tr><th>Modelo (ID)</th><th>Nome de Exibição</th><th>Tokens (Entrada / Saída)</th><th>Ação</th></tr></thead>';
            html += '<tbody>';

            res.models.forEach(function(m){
                const isSelected = ($('#geminiModelInput').val() === m.id);
                const rowClass = isSelected ? 'style="background-color: #f3e5f5; font-weight: 500;"' : '';
                const btnAction = '<button type="button" class="btn-flat waves-effect btn-selecionar-modelo" data-model="' + m.id + '" style="font-size: 0.78rem; text-transform: none; color: #7b1fa2; padding: 0 8px;">' + (isSelected ? '✓ Selecionado' : 'Selecionar') + '</button>';

                html += '<tr ' + rowClass + '>';
                html += '<td><code>' + m.id + '</code></td>';
                html += '<td>' + (m.displayName || m.id) + '</td>';
                html += '<td class="grey-text text-darken-1">' + (m.inputTokenLimit ? Number(m.inputTokenLimit).toLocaleString('pt-BR') : '-') + ' / ' + (m.outputTokenLimit ? Number(m.outputTokenLimit).toLocaleString('pt-BR') : '-') + '</td>';
                html += '<td>' + btnAction + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            $('#probeContent').html(html);
        }, 'json').fail(function(xhr, status, error){
            btn.removeClass('disabled').html('<i class="material-icons left">sensors</i>Testar Chave e Sondar Modelos (Probe)');
            $('#probeBadges').html('<span class="new badge red" data-badge-caption="Erro de Rede"></span>');
            $('#probeContent').html('<div class="card-panel red lighten-4 red-text text-darken-4" style="padding: 12px; margin: 0; border-radius: 4px;">Falha de comunicação com o servidor local ao executar a sonda. ' + error + '</div>');
        });
    });

    $(document).on('click', '.btn-selecionar-modelo', function(){
        const modelId = $(this).data('model');
        let select = $('#geminiModelInput');
        
        if (select.find('option[value="' + modelId + '"]').length === 0) {
            select.append('<option value="' + modelId + '">' + modelId + '</option>');
        }
        select.val(modelId).formSelect();
        
        $('.btn-selecionar-modelo').text('Selecionar').closest('tr').removeAttr('style');
        $(this).text('✓ Selecionado').closest('tr').attr('style', 'background-color: #f3e5f5; font-weight: 500;');
        
        M.toast({html: 'Modelo ' + modelId + ' selecionado!', classes: 'purple rounded'});
    });
});
</script>
