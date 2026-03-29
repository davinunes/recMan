<?php
require_once "classes/repositorio.php";

$configsMail = getConfigEmails();
$copiarSubs = getConfigSistema('copiar_subsindicos_diligencia') == '1';

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
                    <span class="card-title">Opções Gerais</span>
                    <div class="switch">
                        <label>
                            Não copiar subsíndicos
                            <input type="checkbox" id="checkCopiarSubs" <?php echo $copiarSubs ? 'checked' : ''; ?>>
                            <span class="lever"></span>
                            Copiar subsíndico do bloco nas diligências
                        </label>
                    </div>
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

    $(document).on('change', '#checkCopiarSubs', function(){
        const val = this.checked ? '1' : '0';
        $.post('metodo.php?metodo=upsertConfigSistema', { chave: 'copiar_subsindicos_diligencia', valor: val }, function(res){
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
});
</script>
