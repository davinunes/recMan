<?php
include "classes/repositorio.php";
$sql = "SELECT id, email, senha, nome, status, unidade, avatar FROM conselho.usuarios where id={$esseUsuario}";
$result = DBExecute($sql);

if (mysqli_num_rows($result) > 0) {
	
    $usuario = mysqli_fetch_assoc($result);
	
	echo '<form id="updateThisUser" >';
	echo '<input type="hidden" name="id" value="' . $usuario['id'] . '">';
	echo '<label for="nome">Nome:</label>';
	echo '<input type="text" name="nome" value="' . $usuario['nome'] . '">';
	echo '<label for="email">Email:</label>';
	echo '<input type="email" name="email" value="' . $usuario['email'] . '">';
	// echo '<label for="senha">Nova Senha:</label>';
	// echo '<input type="password" name="senha">';
	echo '<label for="status">Status:</label>';
	echo '<input type="number" name="status" value="' . $usuario['status'] . '">';
	echo '<label for="unidade">Unidade:</label>';
	echo '<input type="text" name="unidade" value="' . $usuario['unidade'] . '">';
	echo '<label for="avatar">Avatar:</label>';
	echo ' <div class="file-field input-field">
			  <div class="btn">
				<span>File</span>
				<input name="avatar" id="avatar" type="file">
			  </div>
			  <div class="file-path-wrapper">
				<input class="file-path validate" type="text">
			  </div>
			</div>';
	echo '<button class="btn orange darken-3" type="submit">Atualizar</button>';
	echo '</form>';
}
?>
