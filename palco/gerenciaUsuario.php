<?php
include "classes/repositorio.php";

// Obter dados do usuário logado
$sql = "SELECT id, email, senha, nome, status, unidade, avatar FROM conselho.usuarios WHERE id={$esseUsuario}";
$result = DBExecute($sql);
$usuarioLogado = mysqli_fetch_assoc($result);

$statusChecked = $usuarioLogado['status'] == 1 ? 'checked' : '';
$avatarUrl = !empty($usuarioLogado['avatar']) ? $usuarioLogado['avatar'] : 'https://via.placeholder.com/150';

// Obter lista de todos os usuários
$usuarios = getUsuarios();
?>

<div class="container">
    <div class="row">
        <div class="col s12">
            <h4 class="center-align" style="margin-top: 2rem; margin-bottom: 2rem; font-weight: 300;">Gestão de Conta
            </h4>
            <ul class="tabs tabs-fixed-width z-depth-1">
                <li class="tab col s4"><a href="#tabPerfil" class="active">Meu Perfil</a></li>
                <li class="tab col s4"><a href="#tabSenha">Segurança</a></li>
                <li class="tab col s4"><a href="#tabUsuarios">Usuários</a></li>
            </ul>
        </div>

        <!-- ABA PERFIL -->
        <div id="tabPerfil" class="col s12">
            <div class="card-panel" style="margin-top: 20px;">
                <form id="updateThisUser" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col s12 center-align">
                            <img src="<?php echo $avatarUrl; ?>" alt="Avatar" class="circle responsive-img"
                                style="max-width: 150px; border: 3px solid #eee;">
                        </div>
                    </div>

                    <input type="hidden" name="id" value="<?php echo $usuarioLogado['id']; ?>">

                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">person</i>
                            <input type="text" name="nome" id="nome" value="<?php echo $usuarioLogado['nome']; ?>">
                            <label for="nome" class="active">Nome Completo</label>
                        </div>

                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">email</i>
                            <input type="email" name="email" id="email" value="<?php echo $usuarioLogado['email']; ?>">
                            <label for="email" class="active">Email</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">business</i>
                            <input type="text" name="unidade" id="unidade"
                                value="<?php echo $usuarioLogado['unidade']; ?>">
                            <label for="unidade" class="active">Unidade</label>
                        </div>

                        <div class="input-field col s12 m6" style="padding-top: 15px;">
                            <div class="switch">
                                <label>
                                    Status:<br>
                                    <input type="checkbox" name="status" id="status" value="1" <?php echo $statusChecked; ?>>
                                    <span class="lever"></span> Ativo
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="file-field input-field col s12">
                            <div class="btn blue-grey darken-1">
                                <span><i class="material-icons left">photo_camera</i>Mudar Avatar</span>
                                <input name="avatar" id="avatar" type="file">
                            </div>
                            <div class="file-path-wrapper">
                                <input class="file-path validate" type="text"
                                    placeholder="Escolha uma imagem para seu perfil">
                            </div>
                        </div>
                    </div>

                    <div class="row center-align">
                        <button class="btn waves-effect waves-light orange darken-3" type="submit">
                            <i class="material-icons left">save</i>Atualizar Perfil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ABA SENHA -->
        <div id="tabSenha" class="col s12">
            <div class="card-panel" style="margin-top: 20px;">
                <h5 class="center-align" style="margin-bottom: 30px;">Alterar Senha de Acesso</h5>
                <form id="changePasswordForm">
                    <div class="row">
                        <div class="input-field col s12 m8 offset-m2">
                            <i class="material-icons prefix">lock</i>
                            <input type="password" id="currentPassword" name="currentPassword" required>
                            <label for="currentPassword">Senha Atual</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12 m8 offset-m2">
                            <i class="material-icons prefix">vpn_key</i>
                            <input type="password" id="newPassword" name="newPassword" required>
                            <label for="newPassword">Nova Senha</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12 m8 offset-m2">
                            <i class="material-icons prefix">check_circle</i>
                            <input type="password" id="confirmPassword" name="confirmPassword" required>
                            <label for="confirmPassword">Confirme a Nova Senha</label>
                        </div>
                    </div>
                    <div class="row center-align">
                        <button type="submit" class="btn waves-effect waves-light blue darken-2">
                            <i class="material-icons left">sync</i>Alterar Minha Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ABA USUARIOS -->
        <div id="tabUsuarios" class="col s12">
            <div style="margin-top: 20px;">
                <div class="row">
                    <div class="col s12">
                        <a href="index.php?pag=novoUsuario"
                            class="btn-floating btn-large waves-effect waves-light red right" title="Novo Usuário">
                            <i class="material-icons">add</i>
                        </a>
                        <h5>Diretório de Usuários</h5>
                        <p class="grey-text">Gerencie todos os usuários cadastrados no sistema.</p>
                    </div>
                </div>

                <div class="row">
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                        $uAvatar = !empty($u['avatar']) ? $u['avatar'] : 'https://via.placeholder.com/150';
                        $uStatusClass = $u['status'] == 1 ? 'green' : 'red';
                        $uStatusText = $u['status'] == 1 ? 'Ativo' : 'Inativo';
                        ?>
                        <div class="col s12 m6 l4">
                            <div class="card hoverable user-card">
                                <div class="card-content">
                                    <div class="row valign-wrapper" style="margin-bottom: 0;">
                                        <div class="col s4">
                                            <img src="<?php echo $uAvatar; ?>" alt="" class="circle responsive-img"
                                                style="border: 2px solid #f0f0f0;">
                                        </div>
                                        <div class="col s8">
                                            <span class="card-title truncate" title="<?php echo $u['nome']; ?>"
                                                style="font-size: 1.1rem; line-height: 1.2; font-weight: 500; margin-bottom: 5px;">
                                                <?php echo $u['nome']; ?>
                                            </span>
                                            <p class="truncate grey-text text-darken-1"
                                                style="font-size: 0.8rem; margin-bottom: 5px;">
                                                <i class="material-icons tiny left" style="margin-top: 2px;">email</i>
                                                <?php echo $u['email']; ?>
                                            </p>
                                            <p style="font-size: 0.9rem;">
                                                <i class="material-icons tiny left" style="margin-top: 3px;">business</i>
                                                <b>Unidade:</b>
                                                <?php echo $u['unidade']; ?>
                                            </p>
                                            <div style="margin-top: 10px;">
                                                <span class="badge <?php echo $uStatusClass; ?> white-text left"
                                                    style="margin-left: 0; border-radius: 4px; padding: 0 8px; float: none; display: inline-block;">
                                                    <?php echo $uStatusText; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-action right-align">
                                    <button class="btn-small waves-effect waves-light blue-grey lighten-1 edit-user"
                                        userid-data="<?php echo $u['id']; ?>">
                                        <i class="material-icons left">edit</i>Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Usuário (mantendo compatibilidade com meu.js) -->
<div id="modalEditarUsuario" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h4><i class="material-icons left">person</i>Editar Usuário</h4>
        <div class="divider"></div>
        <form id="formEditUser" enctype="multipart/form-data" style="margin-top: 20px;">
            <input type="hidden" name="id" id="edit_id">

            <div class="row">
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">person</i>
                    <input type="text" name="nome" id="edit_nome" required>
                    <label for="edit_nome">Nome</label>
                </div>
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">email</i>
                    <input type="email" name="email" id="edit_email" required>
                    <label for="edit_email">Email</label>
                </div>
            </div>

            <div class="row">
                <div class="input-field col s12 m8">
                    <i class="material-icons prefix">lock_outline</i>
                    <input type="password" name="senha" id="edit_senha">
                    <label for="edit_senha">Nova Senha (deixe em branco para manter)</label>
                </div>
                <div class="input-field col s12 m4">
                    <i class="material-icons prefix">business</i>
                    <input type="text" name="unidade" id="edit_unidade">
                    <label for="edit_unidade">Unidade</label>
                </div>
            </div>

            <div class="row">
                <div class="input-field col s12 m6">
                    <select name="status" id="edit_status">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                    <label>Status do Usuário</label>
                </div>
                <div class="file-field input-field col s12 m6">
                    <div class="btn-small blue-grey">
                        <span>Foto</span>
                        <input type="file" name="avatar" id="edit_avatar" accept="image/*">
                    </div>
                    <div class="file-path-wrapper">
                        <input class="file-path validate" type="text" placeholder="Alterar avatar">
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <a id="salvarEdicao" class="waves-effect waves-green btn green"><i class="material-icons left">check</i>Salvar
            Alterações</a>
    </div>
</div>

<style>
    .tabs .tab a {
        color: #546e7a;
    }

    .tabs .tab a:hover,
    .tabs .tab a.active {
        color: #1e88e5;
    }

    .tabs .indicator {
        background-color: #1e88e5;
    }

    .user-card {
        border-radius: 8px;
        transition: transform 0.2s;
    }

    .user-card:hover {
        transform: translateY(-5px);
    }

    .user-card .card-content {
        padding: 15px;
    }

    .user-card .card-action {
        padding: 8px 15px;
        border-top: 1px solid rgba(160, 160, 160, 0.2);
    }

    .exContainer {
        padding: 0 10px;
    }
</style>

<script>
    $(document).ready(function () {
        $('.tabs').tabs();
        $('select').formSelect();
        $('.modal').modal();
    });
</script>