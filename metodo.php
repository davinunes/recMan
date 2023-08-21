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
		case "trocaSenha":
			session_start();
            $dados = $_POST;
			if($_SESSION['user_pwd'] != hash('sha256', $dados["currentPassword"]) ){
				echo "Senha atual incorreta!";
				break;
			}
			$dados["user_id"] = $_SESSION["user_id"];

            $response = trocaSenha($dados);
			echo $response;
			// var_dump($dados);
            break;
		case "updateThisUser":
			session_start();
            $dados = $_POST;
			if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
				$avatarTmpName = $_FILES['avatar']['tmp_name'];

				// Lê o conteúdo da imagem
				$avatarContent = file_get_contents($avatarTmpName);

				// Converte o conteúdo da imagem para base64
				$avatarBase64 = base64_encode($avatarContent);
			} else {
				// Caso nenhum arquivo tenha sido enviado, defina o valor como vazio
				$avatarBase64 = '';
			}
			
			$response = updateUsuario($dados, $avatarBase64);
			echo $response;

			// var_dump($dados);
			// var_dump($avatarBase64);
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
				$_SESSION['user_pwd'] = $usuario['senha'];
				$_SESSION['avatar'] = $usuario['avatar'];
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
