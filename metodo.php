<?php
    require_once("/var/www/html/classes/repositorio.php");

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
		case "carregarUsuario":
			$id = $_GET['id'];
			$usuario = getUsuariosById($id); // Você precisa implementar essa função no repositório
			echo json_encode($usuario);
			break;
		case "editarUsuario":
			$dados['id'] = $_POST['id'];
			$dados['email'] = $_POST['email'];

			if (!empty($_POST['senha'])) {
				$dados['senha'] = $_POST['senha'];
			}

			$dados['nome'] = $_POST['nome'];
			$dados['status'] = $_POST['status'];
			$dados['unidade'] = $_POST['unidade'];

			// Upload de imagem, apenas se houver arquivo válido
			if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
				$avatarTmpName = $_FILES['avatar']['tmp_name'];
				$avatarExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
				$avatarFileName = "avatar{$_POST['id']}.$avatarExtension";
				$avatarFullPath = "raw/$avatarFileName";

				if (move_uploaded_file($avatarTmpName, $avatarFullPath)) {
					$dados['avatar'] = $avatarFullPath; // <-- SOMENTE AQUI
				} else {
					echo "Erro ao mover o arquivo.";
					exit;
				}
			}

			$response = upsertUsuario($dados);
			echo $response;
			break;
		case "novoRecurso":
            $dados = $_POST;

            $response = upsertRecurso($dados);
			echo $response;
            // var_dump($response);
            // var_dump($dados); 
            break;
		case "buscaHistorico":
            $num = $_POST['numero'];
            $ano = $_POST['ano'];

            $response = buscaNotificacoes($num, $ano);
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
		case "editaComentario":
			session_start();
			
            $dados = $_POST;
			$dados['usuario'] = $_SESSION["user_id"];
			$response = updateComentario($dados);
			echo $response;

            break;
		case "editaParecer":
			session_start();
			
            $dados = $_POST;

			$response = updateParecer($dados) ? "ok" : "erro";
			echo $response;

            break;
		case "finalizaParecer":
			session_start();
			
            $dados = $_POST;
			$dados["userId"] = $_SESSION["user_id"];
			$response = finalizaParecer($dados) ? "ok" : "erro";
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
		case "mudaFase":
			session_start();
			
            $dados = $_POST;
			$dados["user_id"] = $_SESSION["user_id"];

            $response = upsertFase($dados);
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
				
				// Obter a extensão do arquivo
				$avatarExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);

				// Construir o nome do arquivo
				$avatarFileName = "avatar{$_SESSION['user_id']}.$avatarExtension";
				// Caminho completo para salvar
				$avatarFullPath = "raw/$avatarFileName";
				// var_dump($avatarFullPath);
				
				// Mover o arquivo para o destino final
				if (move_uploaded_file($avatarTmpName, $avatarFullPath)) {
					// echo "O arquivo foi movido com sucesso.";
				} else {
					echo "Houve um erro ao mover o arquivo.";
				}
				// Atualizar a variável $avatarBase64 para ser o caminho completo
				$avatarBase64 = $avatarFullPath;
			} else {
				// Caso nenhum arquivo tenha sido enviado, defina o valor como vazio
				$avatarBase64 = '';
			}
			
			$response = updateUsuario($dados, $avatarBase64);
			echo $response;

			// var_dump($dados);
			// var_dump($avatarBase64);
            break;
		case "atualizarRecurso":
			session_start();
            $dados = $_POST;
						
			$response = upsertRecurso($dados);
			echo $response;
            break;
		case "historicoPorUnidade":
			session_start();
            $dados = $_GET;
						
			$response = getNotificacoes($dados['unidade'], $dados['torre']);
			echo json_encode($response);
            break;
		case "atualizaDataRetiradaNotificacao":
			session_start();
            $dados = $_POST;
			$aux = explode("/", $dados['virtual']);
			$data['notificacao'] = $aux[0];
			$data['ano'] = $aux[1];
			$data['dia_retirada'] = $dados['dia_retirada'];
						
			$try = upsertDatasDeRetirada($data);
			if ($try === "ok") echo "success";
			// dump($try);
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
