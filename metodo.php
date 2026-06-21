<?php
require_once __DIR__ . "/classes/repositorio.php";

switch ($_GET['metodo']) {
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

        $id_mensagem = upsertComentario($dados);
        if (is_numeric($id_mensagem)) {
            // Process uploads
            if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
                foreach ($_FILES['anexos']['name'] as $i => $name) {
                    if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['anexos']['tmp_name'][$i];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $fileName = "msg_{$id_mensagem}_{$i}_" . time() . ".$ext";
                        $fullPath = "storage/comentarios/$fileName";

                        if (!is_dir("storage/comentarios")) mkdir("storage/comentarios", 0777, true);

                        if (move_uploaded_file($tmpName, $fullPath)) {
                            if (!upsertMensagemAnexo($id_mensagem, $name, $fullPath)) {
                                if (file_exists($fullPath)) unlink($fullPath);
                                error_log("Erro ao vincular anexo no banco: $fullPath");
                            }
                        } else {
                            error_log("Erro ao mover arquivo para: $fullPath");
                            echo "Erro ao salvar anexo: no permissões?"; 
                            exit;
                        }
                    }
                }
            }
            echo "ok";
        } else {
            echo $id_mensagem;
        }
        break;
    case "editaComentario":
        session_start();

        $dados = $_POST;
        $dados['id_comentario'] = $dados['id_mensagem']; // Ajuste para o repositório
        $dados['comentario'] = $dados['messageText'];    // Ajuste para o repositório
        $dados['usuario'] = $_SESSION["user_id"];
        
        $response = updateComentario($dados);
        
        if ($response === "ok") {
            $id_msg = $dados['id_mensagem'];
            // Process uploads
            if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
                foreach ($_FILES['anexos']['name'] as $i => $name) {
                    if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['anexos']['tmp_name'][$i];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $fileName = "msg_{$id_msg}_updated_{$i}_" . time() . ".$ext";
                        $fullPath = "storage/comentarios/$fileName";

                        if (!is_dir("storage/comentarios")) mkdir("storage/comentarios", 0777, true);

                        if (move_uploaded_file($tmpName, $fullPath)) {
                            if (!upsertMensagemAnexo($id_msg, $name, $fullPath)) {
                                if (file_exists($fullPath)) unlink($fullPath);
                                error_log("Erro ao vincular anexo atualizado no banco: $fullPath");
                            }
                        } else {
                            error_log("Erro ao mover arquivo atualizado para: $fullPath");
                            echo "Erro ao salvar anexo durante edição";
                            exit;
                        }
                    }
                }
            }
        }
        echo $response;
        break;
    case "getComentarioAnexos":
        $id = $_POST['id_mensagem'];
        echo json_encode(getMensagemAnexos($id));
        break;
    case "deleteAnexoComentario":
        $id = $_POST['id_anexo'];
        if (deleteMensagemAnexo($id)) {
            echo "ok";
        } else {
            echo "Erro ao deletar";
        }
        break;
    case "novaDiligencia":
        session_start();

        $dados = $_POST;
        $dados["user_id"] = $_SESSION["user_id"];

        $id_diligencia = upsertDiligencia($dados);
        if (is_numeric($id_diligencia)) {
            // Handle file uploads
            if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
                $id_dil = $id_diligencia;

                foreach ($_FILES['anexos']['name'] as $i => $name) {
                    if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['anexos']['tmp_name'][$i];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $fileName = "dil_{$id_dil}_{$i}_" . time() . ".$ext";
                        $fullPath = "storage/diligencias/$fileName";

                        if (!is_dir("storage/diligencias"))
                            mkdir("storage/diligencias", 0777, true);

                        if (move_uploaded_file($tmpName, $fullPath)) {
                            if (!upsertDiligenciaAnexo($id_diligencia, $name, $fullPath)) {
                                if (file_exists($fullPath)) unlink($fullPath);
                                error_log("Erro ao vincular anexo diligencia no banco: $fullPath");
                            }
                        } else {
                            error_log("Erro ao mover anexo diligencia para: $fullPath");
                            echo "Erro ao salvar anexo de diligência";
                            exit;
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

        if ($response === "ok") {
            // Se houver novos arquivos no upload
            if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
                $id_dil = $dados['id_diligencia'];
                foreach ($_FILES['anexos']['name'] as $i => $name) {
                    if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['anexos']['tmp_name'][$i];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $fileName = "dil_{$id_dil}_updated_{$i}_" . time() . ".$ext";
                        $fullPath = "storage/diligencias/$fileName";

                        if (!is_dir("storage/diligencias"))
                            mkdir("storage/diligencias", 0777, true);

                        if (move_uploaded_file($tmpName, $fullPath)) {
                            if (!upsertDiligenciaAnexo($id_dil, $name, $fullPath)) {
                                if (file_exists($fullPath)) unlink($fullPath);
                                error_log("Erro ao vincular anexo diligencia atualizado no banco: $fullPath");
                            }
                        }
                    }
                }
            }
        }

        echo $response;
        break;
    case "previaEmailDiligencia":
    case "notificarRequerente":
        session_start();
        require_once "classes/mail_helper.php";
        $action = $_GET['metodo'];

        $id_diligencia = $_POST['id_diligencia'];
        $diligencia = getDiligenciaById($id_diligencia);
        if (!$diligencia) {
            echo json_encode(['error' => "Diligência não encontrada"]);
            break;
        }

        $recurso = getRecursoById($diligencia['id_recurso']);
        if (!$recurso) {
            echo json_encode(['error' => "Recurso não encontrado"]);
            break;
        }

        $emailRequerente = $recurso['email'];
        if (!$emailRequerente) {
            echo json_encode(['error' => "E-mail do requerente não cadastrado"]);
            break;
        }

        // Get boardroom emails
        $configs = getConfigEmails();
        $cc = [];
        $sindicoEmail = null;
        $copiarSubs = getConfigSistema('copiar_subsindicos_diligencia') == '1';

        foreach ($configs as $config) {
            if (!$config['ativo'])
                continue;
            if ($config['funcao'] == 'sindico') {
                $sindicoEmail = $config['email'];
            } else if ($config['funcao'] == 'administracao') {
                $cc[] = $config['email'];
            } else if ($config['funcao'] == 'subsindico' && $copiarSubs) {
                if ($config['bloco'] == $recurso['bloco']) {
                    $cc[] = $config['email'];
                }
            }
        }
        if ($sindicoEmail)
            $cc[] = $sindicoEmail;

        $assunto = "Diligência do Recurso: " . $recurso['numero'];
        $corpo = "<html><body>";
        $corpo .= "<p>Prezado condômino,</p>";
        $corpo .= "<p>Ao analisar o pedido de recurso, foi registrada uma diligência referente ao seu recurso <b>{$recurso['numero']}</b>:</p>";
        $corpo .= "<div style='padding: 15px; border: 1px solid #eee; background: #f9f9f9; font-style: italic;'>";
        $corpo .= nl2br(htmlspecialchars($diligencia['texto']));
        $corpo .= "</div>";
        // $corpo .= "<p>Você também pode acompanhar o andamento do seu recurso através sistema, usando o token <b>{$recurso['token']}</b> e o número do recurso <b>{$recurso['numero']}</b>.</p>";

        $anexos = getDiligenciaAnexos($id_diligencia);
        if (!empty($anexos)) {
            $corpo .= "<p><b>Anexos incluídos:</b></p><ul>";
            foreach ($anexos as $ax) {
                $corpo .= "<li>{$ax['nome_arquivo']}</li>";
            }
            $corpo .= "</ul>";
        }

        $corpo .= "<p>Atenciosamente,<br>Conselho Consultivo e Fiscal</p>";
        $corpo .= "</body></html>";

        if ($action == 'previaEmailDiligencia') {
            echo json_encode([
                'success' => true,
                'to' => $emailRequerente,
                'cc' => array_unique($cc),
                'subject' => $assunto,
                'body' => $corpo,
                'attachments' => $anexos
            ]);
        } else {
            // Proceder com o envio real - notificarRequerente
            $attachments = [];
            foreach ($anexos as $ax) {
                $attachments[] = ['name' => $ax['nome_arquivo'], 'path' => $ax['caminho_arquivo']];
            }

            $mime = MailHelper::buildMimeMessage($emailRequerente, $assunto, $corpo, array_unique($cc), [], $attachments);
            $res = MailHelper::sendViaGmail($mime);

            if (isset($res['id'])) {
                marcarDiligenciaEnviada($id_diligencia, $res['id']);
                echo "ok";
            } else {
                $errorMsg = "Erro desconhecido";
                if (isset($res['error'])) {
                    if (is_array($res['error'])) {
                        $errorMsg = $res['error']['message'] ?? json_encode($res['error']);
                    } else {
                        $errorMsg = $res['error'];
                    }
                }
                echo "Erro ao enviar e-mail: " . $errorMsg;
            }
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
    case "getDiligenciaAnexos":
        $id = $_POST['id_diligencia'];
        echo json_encode(getDiligenciaAnexos($id));
        break;
    case "deleteAnexoDiligencia":
        $id = $_POST['id_anexo'];
        if (deleteDiligenciaAnexo($id)) {
            echo "ok";
        } else {
            echo "Erro ao deletar";
        }
        break;
    case "getConfigEmails":
        echo json_encode(getConfigEmails());
        break;
    case "upsertConfigEmail":
        if (upsertConfigEmail($_POST))
            echo "ok";
        else
            echo "erro";
        break;
    case "upsertConfigSistema":
        if (upsertConfigSistema($_POST['chave'], $_POST['valor']))
            echo "ok";
        else
            echo "erro";
        break;
    case "sugerirParecerIA":
        session_start();
        header('Content-Type: application/json; charset=utf-8');

        $rec = $_POST['rec'] ?? '';
        if (empty($rec)) {
            echo json_encode(['success' => false, 'error' => 'Parâmetro rec ausente.']);
            break;
        }

        // 1. Obter os dados do recurso
        $sql = "SELECT r.* FROM recurso r WHERE r.numero = '" . DBEscape($rec) . "'";
        $res = DBExecute($sql);
        if (!$res || mysqli_num_rows($res) === 0) {
            echo json_encode(['success' => false, 'error' => 'Recurso não encontrado.']);
            break;
        }
        $recurso = mysqli_fetch_assoc($res);
        $recursoId = $recurso['id'];

        // 2. Obter o tipo e artigo da notificação correspondente
        $parts = explode('/', $rec);
        $num = isset($parts[0]) ? (int) $parts[0] : 0;
        $ano = isset($parts[1]) ? (int) $parts[1] : 0;
        $notifRecurso = getNotificacaoByNumeroAno($num, $ano);
        $artigoNota = ($notifRecurso && isset($notifRecurso['artigo'])) ? $notifRecurso['artigo'] : null;

        $artigoTexto = "Não informado.";
        if ($artigoNota) {
            $artigoTexto = getArtigoRegimentoTexto($artigoNota);
        }

        // 3. Obter comentários dos conselheiros
        $mensagens = getMensagens($recursoId);
        $comentariosStr = "";
        if (!empty($mensagens)) {
            foreach ($mensagens as $msg) {
                $comentariosStr .= "- " . ($msg['nome'] ?? 'Conselheiro') . ": " . $msg['texto'] . "\n";
            }
        } else {
            $comentariosStr = "Nenhum comentário registrado.";
        }

        // 4. Obter resultado da votação
        $votos = getMaisVotado($recursoId);
        $resultadoVoto = ($votos && isset($votos['voto'])) ? strtoupper($votos['voto']) : 'MANTER'; // assume MANTER por padrão se não houver votos

        // 5. Carregar chave da API Gemini
        $geminiKey = getConfigSistema('gemini_api_key');
        if (empty($geminiKey)) {
            $envPath = __DIR__ . '/magnacom-sistema/.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) continue;
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2 && trim($parts[0]) === 'GEMINI_API_KEY') {
                        $geminiKey = trim($parts[1]);
                        if (preg_match('/^"([^"]*)"$/', $geminiKey, $matches) || preg_match('/^\'([^\']*)\'$/', $geminiKey, $matches)) {
                            $geminiKey = $matches[1];
                        }
                        break;
                    }
                }
            }
        }

        if (empty($geminiKey)) {
            echo json_encode(['success' => false, 'error' => 'Chave do Gemini (GEMINI_API_KEY) não configurada no .env ou no banco de dados.']);
            break;
        }

        // 6. Preparar o prompt
        $prompt = "Você é um assistente de inteligência artificial encarregado de redigir pareceres formais e profissionais para o Conselho Consultivo e Fiscal de um condomínio residencial de alto padrão.\n";
        $prompt .= "Seu objetivo é sugerir textos profissionais e bem redigidos para cada campo do Parecer com base nas informações fornecidas.\n\n";
        $prompt .= "Informações do Recurso:\n";
        $prompt .= "- Unidade/Bloco: {$recurso['bloco']}-{$recurso['unidade']}\n";
        $prompt .= "- Decisão do Conselho (Resultado do Voto): {$resultadoVoto}\n";
        $prompt .= "- Argumentação do Morador (Defesa):\n{$recurso['detalhes']}\n\n";
        $prompt .= "- Artigo do Regulamento Interno infringido:\n{$artigoTexto}\n\n";
        $prompt .= "- Comentários/Debates dos Conselheiros:\n{$comentariosStr}\n\n";
        $prompt .= "Instruções importantes para a redação:\n";
        $prompt .= "1. O estilo deve ser extremamente formal, profissional, claro e objetivo, adequado para um parecer administrativo/jurídico de condomínio de luxo.\n";
        $prompt .= "2. Evite linguagem informal. Utilize a norma padrão da língua portuguesa (Português do Brasil).\n";
        $prompt .= "3. Adapte a fundamentação e a análise à decisão do Conselho (\"{$resultadoVoto}\"):\n";
        $prompt .= "   - Se for MANTER, justifique tecnicamente por que a multa/advertência está de acordo com as regras e por que a defesa do morador não prospera.\n";
        $prompt .= "   - Se for REVOGAR, explique com razoabilidade por que a infração deve ser desconsiderada/anulada.\n";
        $prompt .= "   - Se for CONVERTER, fundamente a aplicação da conversão da multa para advertência (ex: por primariedade ou menor gravidade do fato).\n";
        $prompt .= "4. O campo 'assunto' deve conter o título formal (ex: \"Parecer do Conselho - Recurso de Notificação nº {$rec}\").\n";
        $prompt .= "5. O campo 'notificacao' deve resumir formalmente o fato ocorrido.\n";
        $prompt .= "6. O campo 'analise' deve conter a fundamentação confrontando os argumentos do morador com o regimento interno.\n";
        $prompt .= "7. O campo 'resultado' deve conter as considerações finais.\n";
        $prompt .= "8. O campo 'conclusao' deve conter a decisão e veredito final em letras maiúsculas (ex: \"MANTIDA A PENALIDADE DE MULTA\", \"REVOGADA A PENALIDADE APLICADA\", \"CONVERTIDA A PENALIDADE DE MULTA EM ADVERTÊNCIA\").\n\n";
        $prompt .= "Retorne apenas o JSON correspondente ao schema solicitado, sem marcações markdown de bloco de código.";

        // 7. Fazer a requisição HTTP para a API do Gemini
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $geminiKey;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'assunto' => [
                            'type' => 'string',
                            'description' => 'Título formal do parecer.'
                        ],
                        'notificacao' => [
                            'type' => 'string',
                            'description' => 'Resumo profissional do fato/infração.'
                        ],
                        'analise' => [
                            'type' => 'string',
                            'description' => 'Fundamentação técnica e confronto com as regras.'
                        ],
                        'resultado' => [
                            'type' => 'string',
                            'description' => 'Considerações finais baseadas nos votos/debates.'
                        ],
                        'conclusao' => [
                            'type' => 'string',
                            'description' => 'Veredito final em maiúsculas.'
                        ]
                    ],
                    'required' => ['assunto', 'notificacao', 'analise', 'resultado', 'conclusao']
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg = isset($errData['error']['message']) ? $errData['error']['message'] : 'Erro desconhecido na API do Gemini.';
            echo json_encode(['success' => false, 'error' => "API Gemini retornou código HTTP $httpCode: $errMsg"]);
            break;
        }

        $resData = json_decode($response, true);
        $jsonText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        if (empty($jsonText)) {
            echo json_encode(['success' => false, 'error' => 'Nenhum text retornado pela IA.']);
            break;
        }

        $suggestions = json_decode(trim($jsonText), true);
        if (!$suggestions) {
            echo json_encode(['success' => false, 'error' => 'Resposta da IA não pôde ser decodificada como JSON: ' . $jsonText]);
            break;
        }

        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
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
        if ($try === "ok")
            echo "success";
        else
            echo "Erro no banco de dados";
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
        if ($_SESSION['user_pwd'] != hash('sha256', $dados["currentPassword"])) {
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
        if ($try === "ok")
            echo "success";
        // dump($try);
        break;
    case "logon":
        $dados = $_POST;
        $response = verificarLogin($dados['email'], $dados['password']);
        if ($response) {
            session_start();
            $usuario = getUsuario($dados['email']);
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_nome'] = $usuario['nome'];
            $_SESSION['user_pwd'] = $usuario['senha'];
            $_SESSION['avatar'] = $usuario['avatar'];
            echo "ok";
        } else {
            echo "erro";
        }
        break;
    case "logout":
        session_start();
        session_destroy();
        break;
}
?>