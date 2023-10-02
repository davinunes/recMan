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
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
	

