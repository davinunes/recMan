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
			$recursos = buscaRecursoParecer($num, $ano);
			
			// Prepara a resposta em formato JSON
			$response = [
				'success' => true,
				'data' => [
					'notificacoes' => $response,
					'recursos' => $recursos
				],
				'timestamp' => date('Y-m-d H:i:s')
			];
			
			header('Content-Type: application/json; charset=utf-8');
			
			echo json_encode($response, JSON_UNESCAPED_UNICODE);
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
case "novaDiligencia":
			session_start();
			
            $dados = $_POST;
			$dados["user_id"] = $_SESSION["user_id"];

            $id_diligencia = upsertDiligencia($dados);
			if (is_numeric($id_diligencia) || $id_diligencia === "ok") {
                // If it's the first time and returning ID (I should update upsertDiligencia to return ID)
                if ($id_diligencia === "ok") {
                    // This is a bit of a hack since old version returned "ok", I'll check last insert id
                    // Actually, I should update upsertDiligencia to return the ID. 
                    // Let's assume for now we can get the ID from the database if needed, 
                    // but I'll update upsertDiligencia in the next step.
                }

                // Handle file uploads
                if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
                    // I need the actual ID now. Let's get it.
                    $sql_id = "SELECT id FROM conselho.diligencia WHERE id_usuario = '{$dados["user_id"]}' AND id_recurso = '{$dados["id_recurso"]}' ORDER BY id DESC LIMIT 1";
                    $res_id = DBExecute($sql_id);
                    $row_id = mysqli_fetch_assoc($res_id);
                    $id_dil = $row_id['id'];

                    foreach ($_FILES['anexos']['name'] as $i => $name) {
                        if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                            $tmpName = $_FILES['anexos']['tmp_name'][$i];
                            $ext = pathinfo($name, PATHINFO_EXTENSION);
                            $fileName = "dil_{$id_dil}_{$i}_" . time() . ".$ext";
                            $fullPath = "storage/diligencias/$fileName";
                            
                            if (!is_dir("storage/diligencias")) mkdir("storage/diligencias", 0777, true);

                            if (move_uploaded_file($tmpName, $fullPath)) {
                                upsertDiligenciaAnexo($id_dil, $name, $fullPath);
                            }
                        }
                    }
                }
                echo "ok";
            } else {
                echo $id_diligencia;
            }
            break;
		case "editaDiligencia":
			session_start();
			
            $dados = $_POST;
			$dados['usuario'] = $_SESSION["user_id"];
			$response = updateDiligencia($dados);
			echo $response;
            break;
        case "notificarRequerente":
            session_start();
            require_once "classes/mail_helper.php";
            
            $id_diligencia = $_POST['id_diligencia'];
            $diligencia = getDiligenciaById($id_diligencia);
            if (!$diligencia) {
                echo "Diligência não encontrada";
                break;
            }

            $recurso = getRecursoById($diligencia['id_recurso']);
            if (!$recurso) {
                echo "Recurso não encontrado";
                break;
            }

            $emailRequerente = $recurso['email'];
            if (!$emailRequerente) {
                echo "E-mail do requerente não cadastrado";
                break;
            }

            // Get boardroom emails
            $configs = getConfigEmails();
            $cc = [];
            $sindicoEmail = null;
            $copiarSubs = getConfigSistema('copiar_subsindicos_diligencia') == '1';

            foreach ($configs as $config) {
                if (!$config['ativo']) continue;
                if ($config['funcao'] == 'sindico') {
                    $sindicoEmail = $config['email'];
                } else if ($config['funcao'] == 'administracao') {
                    $cc[] = $config['email'];
                } else if ($config['funcao'] == 'subsindico' && $copiarSubs) {
                    // Check if it's the sub for the resource block
                    if ($config['bloco'] == $recurso['bloco']) {
                        $cc[] = $config['email'];
                    }
                }
            }
            
            if ($sindicoEmail) {
                $cc[] = $sindicoEmail;
            }

            $assunto = "Diligência do Recurso: " . $recurso['numero'];
            $corpo = "<h3>Olá,</h3>
                      <p>O Conselho de Administração do Condomínio registrou uma diligência referente ao seu recurso <b>{$recurso['numero']}</b>:</p>
                      <hr>
                      <p><i>\"" . nl2br(htmlspecialchars($diligencia['texto'])) . "\"</i></p>
                      <hr>
                      <p>Você pode acompanhar o andamento do seu recurso através do portal do condômino.</p>
                      <p>Atenciosamente,<br>Conselho de Administração</p>";

            $anexos = getDiligenciaAnexos($id_diligencia);
            $mailAnexos = [];
            foreach ($anexos as $anexo) {
                $mailAnexos[] = [
                    'path' => $anexo['caminho_arquivo'],
                    'name' => $anexo['nome_arquivo']
                ];
            }

            $mime = MailHelper::buildMimeMessage($emailRequerente, $assunto, $corpo, $cc, [], $mailAnexos);
            $resMail = MailHelper::sendViaGmail($mime);

            if (isset($resMail['id'])) {
                marcarDiligenciaEnviada($id_diligencia, $resMail['id']);
                echo "ok";
            } else {
                echo "Erro ao enviar email: " . (isset($resMail['error']) ? $resMail['error'] : json_encode($resMail));
            }
            break;
        case "buscarOcorrencia":
            $termo = $_POST['termo'];
            $res = buscarOcorrenciaDigital($termo);
            echo json_encode($res);
            break;
        case "vincularOcorrencia":
            $idRec = $_POST['id_recurso'];
            $idOc = $_POST['id_ocorrencia'];
            if (linkRecursoOcorrencia($idRec, $idOc)) {
                echo "ok";
            } else {
                echo "Erro ao vincular";
            }
            break;
        case "getConfigEmails":
            echo json_encode(getConfigEmails());
            break;
        case "upsertConfigEmail":
            if (upsertConfigEmail($_POST)) echo "ok"; else echo "erro";
            break;
        case "upsertConfigSistema":
            if (upsertConfigSistema($_POST['chave'], $_POST['valor'])) echo "ok"; else echo "erro";
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
		case "upsertMultaCobrada":
			session_start();
			$dados = $_POST;
			
			// Extrair número e ano do ID (formato: numero/ano)
			$aux = explode("/", $dados['id']);
			$data['numero'] = $aux[0];
			$data['ano'] = $aux[1];
			$data['valor'] = $dados['valor'];
			$data['data_vencimento'] = $dados['data_vencimento'];
			
			// Data de pagamento pode ser NULL se estiver vazia
			$data['data_pagamento'] = !empty($dados['data_pagamento']) ? $dados['data_pagamento'] : null;
			
			// Validações
			if (empty($data['valor']) || empty($data['data_vencimento'])) {
				echo "Valor e Data de Vencimento são obrigatórios";
				break;
			}
			
			// Buscar unidade e bloco da notificação original
			$notificacao = getNotificacaoByNumeroAno($data['numero'], $data['ano']);
			if ($notificacao) {
				$data['unidade'] = $notificacao['unidade'];
				$data['bloco'] = $notificacao['torre'];
			} else {
				echo "Notificação não encontrada";
				break;
			}
			
			$try = upsertMultaCobrada($data);
			if ($try === "ok") echo "success";
			else echo "Erro no banco de dados";
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
