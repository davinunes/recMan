<?php
    require("/var/www/html/classes/repositorio.php");

    switch($_GET['metodo']) {
        case "novoUsuario":
            $dados['email'] = $_POST['email'];
            $dados['senha'] = $_POST['senha']; // Corrigi aqui
            $dados['nome'] = $_POST['nome'];
            $dados['status'] = $_POST['status'] ? $_POST['status'] : true;
            $dados['unidade'] = $_POST['unidade'];

            $response = upsertUsuario($dados);
			echo $response;
            // var_dump($response);
            // var_dump($dados); // Alterei para exibir o array $dados
            break;
    }
?>
