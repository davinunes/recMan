<?php
require_once "classes/repositorio.php";

$configsMail = getConfigEmails();
$copiarSubsDiligencia = getConfigSistema('copiar_subsindicos_diligencia') == '1';
$copiarSubsParecer = getConfigSistema('copiar_subsindicos_parecer') == '1';
$copiarSubsNovo = getConfigSistema('copiar_subsindicos_novo_recurso') == '1';

?>

<div class="container">
    <div class="row">
        <div class="col s12">
            <h4>Configurações de E-mails e Notificações</h4>
            <p>Gerencie os destinatários que recebem as cópias das diligências e notificações.</p>
        </div>
    </div>

    <!-- Switch Copiar Subsíndicos -->
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Opções Gerais de Notificação (Bloco)</span>
                    <div class="row">
                        <div class="col s12 m4">
                            <label>Diligências</label>
                            <div class="switch">
                                <label>
                                    Não copiar
                                    <input type="checkbox" id="checkCopiarSubsDiligencia" <?php echo $copiarSubsDiligencia ? 'checked' : ''; ?>>
                                    <span class="lever"></span>
                                    Copiar Sub.
                                </label>
                            </div>
                        </div>
                        <div class="col s12 m4">
                            <label>Pareceres / Decisões</label>
                            <div class="switch">
                                <label>
                                    Não copiar
                                    <input type="checkbox" id="checkCopiarSubsParecer" <?php echo $copiarSubsParecer ? 'checked' : ''; ?>>
                                    <span class="lever"></span>
                                    Copiar Sub.
                                </label>
                            </div>
                        </div>
                        <div class="col s12 m4">
                            <label>Portal (Novo Recurso)</label>
                            <div class="switch">
                                <label>
                                    Não copiar
                                    <input type="checkbox" id="checkCopiarSubsNovo" <?php echo $copiarSubsNovo ? 'checked' : ''; ?>>
                                    <span class="lever"></span>
                                    Copiar Sub.
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuração do Gemini / IA -->
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title" style="display: flex; align-items: center; gap: 8px;">
                        <i class="material-icons purple-text">psychology</i> Integração Gemini (Inteligência Artificial)
                    </span>
                    <p style="margin-bottom: 20px;" class="grey-text">Configure a chave de API do Gemini e o prompt de sistema utilizado para gerar sugestões automáticas dos pareceres.</p>
                    
                    <form id="formConfigGemini">
                        <div class="row">
                            <div class="input-field col s12">
                                <input type="password" id="geminiApiKeyInput" value="<?php echo htmlspecialchars(getConfigSistema('gemini_api_key') ?? ''); ?>" placeholder="AIzaSy...">
                                <label for="geminiApiKeyInput">Chave de API do Gemini (GEMINI_API_KEY)</label>
                                <span class="helper-text">Deixe em branco para usar o valor definido no arquivo .env</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="input-field col s12">
                                <textarea id="geminiPromptInput" class="materialize-textarea" style="min-height: 150px; font-family: monospace; font-size: 0.9rem;"><?php 
                                    $promptDb = getConfigSistema('gemini_system_prompt');
                                    if (empty($promptDb)) {
                                        $promptDb = "Você é um assistente de inteligência artificial encarregado de redigir pareceres formais e profissionais para o Conselho Consultivo e Fiscal de um condomínio residencial de alto padrão.\nSeu objetivo é sugerir textos profissionais e bem redigidos para cada campo do Parecer com base nas informações fornecidas.\n\nInstruções importantes para a redação:\n1. O estilo deve ser extremamente formal, profissional, claro e objetivo, adequado para um parecer administrativo/jurídico de condomínio de luxo.\n2. Evite linguagem informal. Utilize a norma padrão da língua portuguesa (Português do Brasil).\n3. Adapte a fundamentação e a análise à decisão do Conselho:\n   - Se for MANTER, justifique tecnicamente por que a multa/advertência está de acordo com as regras e por que a defesa do morador não prospera.\n   - Se for REVOGAR, explique com razoabilidade por que a infração deve ser desconsiderada/anulada.\n   - Se for CONVERTER, fundamente a aplicação da conversão da multa para advertência (ex: por primariedade ou menor gravidade do fato).\n4. O campo 'assunto' deve conter o título formal (ex: \"Parecer do Conselho - Recurso de Notificação nº [NUMERO_RECURSO]\").\n5. O campo 'notificacao' deve resumir formalmente o fato ocorrido.\n6. O campo 'analise' deve conter a fundamentação confrontando os argumentos do morador com o regimento interno.\n7. O campo 'resultado' deve conter as considerações finais.\n8. O campo 'conclusao' deve conter a decisão e veredito final em letras maiúsculas.";
                                    }
                                    echo htmlspecialchars($promptDb); 
                                ?></textarea>
                                <label for="geminiPromptInput">Prompt de Sistema (Instruções da IA)</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-action">
                    <button type="button" class="btn purple darken-2 waves-effect waves-light" id="btnSaveConfigGemini">
                        <i class="material-icons left">save</i>Salvar Configurações da IA
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Destinatários -->
    <div class="row">
        <div class="col s12">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Listagem de Destinatários (Diretoria/Admin)</span>
                    <table class="highlight">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Função</th>
                                <th>Bloco</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($configsMail as $c): ?>
                            <tr>
                                <td><?php echo $c['nome']; ?></td>
                                <td><?php echo $c['email']; ?></td>
                                <td><?php echo ucfirst($c['funcao']); ?></td>
                                <td><?php echo $c['bloco'] ?: '-'; ?></td>
                                <td><?php echo $c['ativo'] ? '<span class="green-text">Ativo</span>' : '<span class="red-text">Inativo</span>'; ?></td>
                                <td>
                                    <a href="#modalEditConfigEmail" class="modal-trigger editConfigEmail" 
                                       data-id="<?php echo $c['id']; ?>"
                                       data-nome="<?php echo $c['nome']; ?>"
                                       data-email="<?php echo $c['email']; ?>"
                                       data-funcao="<?php echo $c['funcao']; ?>"
                                       data-bloco="<?php echo $c['bloco']; ?>"
                                       data-ativo="<?php echo $c['ativo']; ?>">
                                        <i class="material-icons">edit</i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-action">
                    <a href="#modalEditConfigEmail" class="modal-trigger btn blue" id="btnAddConfigEmail">Adicionar Novo</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar/Editar -->
<div id="modalEditConfigEmail" class="modal">
    <div class="modal-content">
        <h4 id="titleModalEmail">Configurar Destinatário</h4>
        <form id="formConfigEmail">
            <input type="hidden" name="id" id="configId">
            <div class="row">
                <div class="input-field col s12 m6">
                    <input type="text" name="nome" id="configNome" required>
                    <label for="configNome">Nome</label>
                </div>
                <div class="input-field col s12 m6">
                    <input type="email" name="email" id="configEmail" required>
                    <label for="configEmail">E-mail</label>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12 m4">
                    <select name="funcao" id="configFuncao" class="browser-default">
                        <option value="sindico">Síndico</option>
                        <option value="subsindico">Subsíndico</option>
                        <option value="administracao">Administração</option>
                    </select>
                    <label style="position:relative; top:-10px">Função</label>
                </div>
                <div class="input-field col s12 m4">
                    <input type="text" name="bloco" id="configBloco" maxlength="2">
                    <label for="configBloco">Bloco (Obrigatório para Subsíndico)</label>
                </div>
                <div class="input-field col s12 m4">
                    <div class="switch">
                        <label>
                            Inativo
                            <input type="checkbox" name="ativo" id="configAtivo" value="1" checked>
                            <span class="lever"></span>
                            Ativo
                        </label>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <a href="#!" id="btnSaveConfigEmail" class="waves-effect waves-green btn-flat">Salvar</a>
    </div>
</div>

<script>
$(document).ready(function(){
    $('.modal').modal();

    $(document).on('change', '#checkCopiarSubsDiligencia, #checkCopiarSubsParecer, #checkCopiarSubsNovo', function(){
        const val = this.checked ? '1' : '0';
        let chave = 'copiar_subsindicos_diligencia';
        if(this.id === 'checkCopiarSubsParecer') chave = 'copiar_subsindicos_parecer';
        if(this.id === 'checkCopiarSubsNovo') chave = 'copiar_subsindicos_novo_recurso';

        $.post('metodo.php?metodo=upsertConfigSistema', { chave: chave, valor: val }, function(res){
            if(res.trim() === 'ok') M.toast({html: 'Configuração atualizada!'});
        });
    });

    $('#btnAddConfigEmail').click(function(){
        $('#formConfigEmail')[0].reset();
        $('#configId').val('');
        $('#titleModalEmail').text('Adicionar Destinatário');
    });

    $(document).on('click', '.editConfigEmail', function(){
        const d = $(this).data();
        $('#configId').val(d.id);
        $('#configNome').val(d.nome);
        $('#configEmail').val(d.email);
        $('#configFuncao').val(d.funcao);
        $('#configBloco').val(d.bloco);
        $('#configAtivo').prop('checked', d.ativo == 1);
        $('#titleModalEmail').text('Editar Destinatário');
        M.updateTextFields();
    });

    $('#btnSaveConfigEmail').click(function(){
        const data = $('#formConfigEmail').serialize();
        if(!$('#configAtivo').is(':checked')) {
            // Se não estiver marcado, serialize() não envia nada, então forçamos ativo=0
            // Mas serialize() envia ativo=1 se marcado. Então se não marcado:
        }
        
        $.post('metodo.php?metodo=upsertConfigEmail', data, function(res){
            if(res.trim() === 'ok'){
                M.toast({html: 'Salvo com sucesso!', classes: 'green'});
                setTimeout(() => window.location.reload(), 1000);
            } else {
                M.toast({html: 'Erro ao salvar: ' + res, classes: 'red'});
            }
        });
    });

    $('#btnSaveConfigGemini').click(function(){
        const apiKey = $('#geminiApiKeyInput').val();
        const prompt = $('#geminiPromptInput').val();
        const btn = $(this);
        
        btn.addClass('disabled').text('Salvando...');

        $.post('metodo.php?metodo=upsertConfigSistema', { chave: 'gemini_api_key', valor: apiKey }, function(res1){
            if(res1.trim() === 'ok'){
                $.post('metodo.php?metodo=upsertConfigSistema', { chave: 'gemini_system_prompt', valor: prompt }, function(res2){
                    btn.removeClass('disabled').html('<i class="material-icons left">save</i>Salvar Configurações da IA');
                    if(res2.trim() === 'ok'){
                        M.toast({html: 'Configurações da IA salvas com sucesso!', classes: 'green'});
                    } else {
                        M.toast({html: 'Erro ao salvar o Prompt.', classes: 'red'});
                    }
                });
            } else {
                btn.removeClass('disabled').html('<i class="material-icons left">save</i>Salvar Configurações da IA');
                M.toast({html: 'Erro ao salvar a Chave API.', classes: 'red'});
            }
        });
    });
});
</script>
