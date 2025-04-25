<div class="container">
    <h1>Lista de Usuários</h1><a  href="index.php?pag=novoUsuario" class="btn right">Novo Usuario</a>

    <table>
        <thead>
            <tr>
                <th>Avatar</th>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Status</th>
                <th>Unidade</th>
            </tr>
        </thead>
        <tbody>
            <?php

            include "classes/repositorio.php";

            $usuarios = getUsuarios(); // Suponha que você tenha uma função para buscar os usuários
			// var_dump($usuarios);

            foreach ($usuarios as $usuario) {
                echo "<tr>";
                echo "<td><img class='circle' src='{$usuario['avatar']}' alt='Avatar' width='50'></td>";
                echo "<td>{$usuario['id']}</td>";
                echo "<td>{$usuario['nome']}</td>";
                echo "<td>{$usuario['email']}</td>";
                echo "<td>{$usuario['status']}</td>";
                echo "<td>{$usuario['unidade']}</td>";
				echo "<td><a class='btn edit-user' userid-data='{$usuario['id']}'>Editar</a></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Modal Structure -->
<div id="modalEditarUsuario" class="modal">
    <div class="modal-content">


<div class="container">
    <h2>Editar Usuário</h2>
    <form id="formEditUser" enctype="multipart/form-data">
        <input type="hidden" name="id" id="edit_id">
        <div class="input-field">
            <input type="text" name="nome" id="edit_nome" placeholder="s">
            <label for="edit_nome">Nome</label>
        </div>
        <div class="input-field">
            <input type="email" name="email" id="edit_email" required placeholder="s">
            <label for="edit_email">Email</label>
        </div>
        <div class="input-field">
            <input type="password" name="senha" id="edit_senha">
            <label for="edit_senha">Nova Senha (deixe em branco para manter a atual)</label>
        </div>
        <div class="input-field">
            <input type="text" name="unidade" id="edit_unidade" placeholder="s">
            <label for="edit_unidade">Unidade</label>
        </div>
        <div class="input-field">
            <select name="status" id="edit_status">
                <option value="1">Ativo</option>
                <option value="0">Inativo</option>
            </select>
            <label>Status</label>
        </div>
		<div class="file-field input-field">
		  <div class="btn">
			<span>Foto</span>
			<input type="file" name="avatar" id="edit_avatar" accept="image/*">
		  </div>
		  <div class="file-path-wrapper">
			<input class="file-path validate" type="text" placeholder="Envie uma nova imagem">
		  </div>
		</div>

        <a id="salvarEdicao" class="btn waves-effect waves-light">Salvar Alterações</a>
    </form>
</div>


    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Fechar</a>
    </div>
</div>
	

