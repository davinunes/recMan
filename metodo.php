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
		case "novoRecurso":
            $dados = $_POST;

            $response = upsertRecurso($dados);
			echo $response;
            // var_dump($response);
            // var_dump($dados); 
            break;
		case "novoComentario":
			session_start();
			
            $dados = $_POST;
			$dados["user_id"] = $_SESSION["user_id"];

            $response = upsertComentario($dados);
			echo $response;
            break;
		case "votar":
			session_start();
			
            $dados = $_POST;
			$dados["user_id"] = $_SESSION["user_id"];

            $response = upsertVoto($dados);
			echo $response;
			// var_dump($dados);
            break;
		case "logon":
            $dados = $_POST;
            $response = verificarLogin($dados['email'], $dados['password']);
			if($response){
				session_start();
				$usuario = getUsuario($dados['email']);
				$_SESSION['user_id'] = $usuario['id'];
				$_SESSION['user_email'] = $usuario['email'];
				$_SESSION['user_nome'] = $usuario['nome'];
				echo "ok";
			}else{
				echo "erro";
			}
            break;
		case "logout":
			session_start();
			session_destroy();
            break;
    }
?>
