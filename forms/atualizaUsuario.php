<?php
include "classes/repositorio.php";
$sql = "SELECT id, email, senha, nome, status, unidade, avatar FROM conselho.usuarios WHERE id={$esseUsuario}";
$result = DBExecute($sql);

if (mysqli_num_rows($result) > 0) {
    $usuario = mysqli_fetch_assoc($result);
    $statusChecked = $usuario['status'] == 1 ? 'checked' : '';
    $avatarUrl = !empty($usuario['avatar']) ? $usuario['avatar'] : 'https://via.placeholder.com/150';

    echo '<div class="container">';
    echo '<div class="card-panel">';
    echo '<form id="updateThisUser" enctype="multipart/form-data">';

    // Avatar + nome
    echo '<div class="row">';
        echo '<div class="col s12 center-align">';
        echo '<img src="' . $avatarUrl . '" alt="Avatar" class="circle responsive-img" style="max-width: 150px;">';
        echo '</div>';
    echo '</div>';

    echo '<input type="hidden" name="id" value="' . $usuario['id'] . '">';

    echo '<div class="row">';
        echo '<div class="input-field col s12 m6">';
        echo '<input type="text" name="nome" id="nome" value="' . $usuario['nome'] . '">';
        echo '<label for="nome" class="active">Nome</label>';
        echo '</div>';

        echo '<div class="input-field col s12 m6">';
        echo '<input type="email" name="email" id="email" value="' . $usuario['email'] . '">';
        echo '<label for="email" class="active">Email</label>';
        echo '</div>';
    echo '</div>';

    echo '<div class="row">';
        echo '<div class="input-field col s12 m6">';
        echo '<input type="text" name="unidade" id="unidade" value="' . $usuario['unidade'] . '">';
        echo '<label for="unidade" class="active">Unidade</label>';
        echo '</div>';

        echo '<div class="input-field col s12 m6">';
        echo '<div class="switch">';
        echo '<label>Status:<br>';
        echo '<input type="checkbox" name="status" id="status" value="1" ' . $statusChecked . '>';
        echo '<span class="lever"></span> Ativo';
        echo '</label>';
        echo '</div>';
        echo '</div>';
    echo '</div>';

    echo '<div class="row">';
        echo '<div class="file-field input-field col s12">';
        echo '<div class="btn">';
        echo '<span>Selecionar Avatar</span>';
        echo '<input name="avatar" id="avatar" type="file">';
        echo '</div>';
        echo '<div class="file-path-wrapper">';
        echo '<input class="file-path validate" type="text" placeholder="Escolha um arquivo">';
        echo '</div>';
        echo '</div>';
    echo '</div>';

    echo '<div class="row center-align">';
    echo '<button class="btn orange darken-3" type="submit">Atualizar</button>';
    echo '</div>';

    echo '</form>';
    echo '</div>'; // fim card-panel
    echo '</div>'; // fim container
}
?>
