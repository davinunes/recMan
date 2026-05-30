<?php
session_start();
require_once "../classes/repositorio.php";
require_once "../classes/push_helper.php";

// Constantes
define('EMAIL_SINDICO_NOTIFICACAO', 'sindicogeral.miami@gmail.com, centralderecursosmiamibeach@gmail.com');

$action = $_GET['action'] ?? '';

function sincronizarNotificacaoSupabase($numero, $ano) {
    $envPath = __DIR__ . '/../magnacom-sistema/.env';
    if (!file_exists($envPath)) return null;
    
    // Ler variáveis do .env
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            if (preg_match('/^"([^"]*)"$/', $val, $matches) || preg_match('/^\'([^\']*)\'$/', $val, $matches)) {
                $val = $matches[1];
            }
            $env[$key] = $val;
        }
    }
    
    $url = $env['SUPABASE_URL'] ?? '';
    $apiKey = $env['SUPABASE_ANON_KEY'] ?? '';
    $email = $env['SUPABASE_EMAIL'] ?? '';
    $password = $env['SUPABASE_PASSWORD'] ?? '';
    
    if (empty($url) || empty($apiKey) || empty($email) || empty($password)) return null;
    
    try {
        // 1. Autenticação
        $authUrl = rtrim($url, '/') . '/auth/v1/token?grant_type=password';
        $authPayload = json_encode(['email' => $email, 'password' => $password]);
        
        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $authPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'apikey: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $authResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) return null;
        
        $authData = json_decode($authResponse, true);
        $token = $authData['access_token'] ?? '';
        if (empty($token)) return null;
        
        // 2. Consulta
        $rec = $numero . '/' . $ano;
        $dbUrl = rtrim($url, '/') . '/rest/v1/notificacoes?select=*&numero=eq.' . urlencode($rec);
        
        $ch = curl_init($dbUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $apiKey,
            'authorization: Bearer ' . $token,
            'accept-profile: public',
            'accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $dbResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) return null;
        
        $rows = json_decode($dbResponse, true);
        if (!is_array($rows) || count($rows) === 0) return null;
        
        $row = $rows[0];
        
        // 3. Mapeamento
        $torre = null;
        if (isset($row['torre']) && $row['torre'] !== '') {
            $torre = preg_replace('/[^A-F]/i', '', strtoupper($row['torre']));
            $torre = !empty($torre) ? $torre : null;
        }
        
        $unidade = 0;
        if (isset($row['apto']) && $row['apto'] !== '') {
            $unidade = intval(preg_replace('/[^\d]/', '', $row['apto']));
        }
        
        $tipo = isset($row['tipo']) ? trim($row['tipo']) : '';
        $normalizedTipo = null;
        if ($tipo !== '') {
            $accents = ['à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','À','Á','Â','Ã','Ä','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý'];
            $clean = ['a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','U','Y'];
            $normalized = strtoupper(str_replace($accents, $clean, $tipo));
            if (strpos($normalized, 'MULTA') !== false) {
                $normalizedTipo = 'MULTA';
            } elseif (strpos($normalized, 'ADVERT') !== false) {
                $normalizedTipo = 'ADVERTENCIA';
            } else {
                $normalizedTipo = $normalized;
            }
        }
        
        $assunto = isset($row['infracao']) && $row['infracao'] !== '' ? trim($row['infracao']) : null;
        
        $parseDate = function($dateStr) {
            if (empty($dateStr)) return null;
            $dateStr = trim($dateStr);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
            $timestamp = strtotime($dateStr);
            return $timestamp ? date('Y-m-d', $timestamp) : null;
        };
        
        $data_email = $parseDate($row['confeccao'] ?? '');
        $data_envio = $parseDate($row['envio'] ?? '');
        $data_ocorrido = $parseDate($row['data_ocorrido'] ?? '');
        $status = isset($row['resultado']) && $row['resultado'] !== '' ? trim($row['resultado']) : null;
        
        $cobrado = (isset($row['cobrado_realizado']) && $row['cobrado_realizado']) ? 'Cobrado' : 'Não cobrado';
        $parcelamento = isset($row['parcelamento']) && $row['parcelamento'] !== '' ? trim($row['parcelamento']) : '';
        $cobranca = $cobrado;
        if ($parcelamento !== '') {
            $cobranca .= ' - ' . $parcelamento;
        }
        $obs = ($parcelamento !== '') ? $parcelamento : null;
        
        // Mapeia tipificação para notação do regimento
        $artigoClean = preg_replace('/^art(igo)?\.?\s+/i', '', trim($row['tipificacao'] ?? ''));
        if (preg_match('/^(\d+)/', $artigoClean, $matches)) {
            $artigoNum = $matches[1];
            $resto = trim(substr($artigoClean, strlen($artigoNum)));
            $romanos = [
                'xiv' => 14, 'xiii' => 13, 'xii' => 12, 'xi' => 11, 'x' => 10,
                'ix' => 9, 'viii' => 8, 'vii' => 7, 'vi' => 6, 'v' => 5,
                'iv' => 4, 'iii' => 3, 'ii' => 2, 'i' => 1
            ];
            $artigoNotacao = $artigoNum;
            if (preg_match('/(?:inciso\s+)?([ivxldcm]+)\b/i', $resto, $matchesInc)) {
                $romano = strtolower($matchesInc[1]);
                if (isset($romanos[$romano])) $artigoNotacao = $artigoNum . '.' . $romanos[$romano];
            } elseif (preg_match('/inciso\s+(\d+)/i', $resto, $matchesInc)) {
                $artigoNotacao = $artigoNum . '.' . $matchesInc[1];
            } elseif (preg_match('/par[aá]grafo\s+u[nni]co/i', $resto)) {
                $artigoNotacao = $artigoNum . '.unico';
            } elseif (preg_match('/(?:par[aá]grafo\s+|§\s*)(\d+)/ui', $resto, $matchesInc)) {
                $artigoNotacao = $artigoNum . '.' . $matchesInc[1];
            }
        } else {
            $artigoNotacao = null;
        }
        
        $linha = [
            'numero' => $numero,
            'ano' => $ano,
            'torre' => $torre,
            'unidade' => $unidade,
            'notificacao' => $normalizedTipo,
            'assunto' => $assunto,
            'artigo' => $artigoNotacao,
            'data_email' => $data_email,
            'data_envio' => $data_envio,
            'data_ocorrido' => $data_ocorrido,
            'status' => $status,
            'cobranca' => $cobranca,
            'obs' => $obs
        ];
        
        // Upsert
        ob_start();
        $resNotif = upsertNotificacao($linha);
        ob_get_clean();
        
        if ($resNotif === 'ok') {
            // Datas de retirada
            $dia_retirada = null;
            if (!empty($row['recebimento_fisico'])) {
                $dia_retirada = $parseDate($row['recebimento_fisico']);
            } elseif (!empty($row['conf_leitura_digital'])) {
                $dia_retirada = $parseDate($row['conf_leitura_digital']);
            }
            
            if ($dia_retirada !== null) {
                $dadosRetirada = [
                    'notificacao' => $numero,
                    'ano' => $ano,
                    'dia_retirada' => $dia_retirada,
                    'bloco' => $torre,
                    'apartamento' => $unidade,
                    'obs' => $cobranca
                ];
                upsertDatasDeRetirada($dadosRetirada);
            }
            
            $linha['numero_ano_virtual'] = $numero . '/' . $ano;
            return $linha;
        }
    } catch (Exception $e) {
        // Silencia erro e retorna null
    }
    return null;
}

if ($action == 'check_notification') {
    $numero = $_POST['numero'] ?? '';
    $ano = $_POST['ano'] ?? '';
    $numeroCompleto = $numero . '/' . $ano;

    // Procura recurso
    $sqlRec = "SELECT id, email, token FROM recurso WHERE numero = '" . DBEscape($numeroCompleto) . "'";
    $resRec = DBExecute($sqlRec);

    if ($resRec && mysqli_num_rows($resRec) > 0) {
        $rec = mysqli_fetch_assoc($resRec);

        $email = $rec['email'];
        $maskedEmail = '';
        if (!empty($email) && strpos($email, '@') !== false) {
            $parts = explode('@', $email);
            if (count($parts) == 2) {
                $alias = $parts[0];
                $domain = $parts[1];
                if (strlen($alias) > 3) {
                    $maskedEmail = substr($alias, 0, 3) . str_repeat('*', strlen($alias) - 3) . '@' . $domain;
                } else {
                    $maskedEmail = substr($alias, 0, 1) . str_repeat('*', strlen($alias) - 1) . '@' . $domain;
                }
            }
        }

        // Já existe um recurso
        echo json_encode(['exists' => true, 'has_email' => !empty($rec['email']), 'masked_email' => $maskedEmail]);
        exit;
    }

    // Procura na tabela notificacoes
    $sqlNot = "SELECT * FROM notificacoes WHERE numero = '" . DBEscape($numero) . "' AND ano = '" . DBEscape($ano) . "'";
    $resNot = DBExecute($sqlNot);

    if ($resNot && mysqli_num_rows($resNot) > 0) {
        $not = mysqli_fetch_assoc($resNot);
        echo json_encode(['exists' => false, 'notificacao' => $not]);
    } else {
        // Notificação não encontrada localmente, tenta obter em tempo real do Supabase
        $not = sincronizarNotificacaoSupabase(intval($numero), intval($ano));
        if ($not) {
            echo json_encode(['exists' => false, 'notificacao' => $not]);
        } else {
            // Não existe em nenhuma das bases: retorna erro explicativo
            echo json_encode([
                'exists' => false,
                'notificacao' => null,
                'error' => "A Notificação nº " . htmlspecialchars($numeroCompleto) . " não foi localizada na base de dados do condomínio nem no sistema da Magnacom.\n\nPor favor, verifique se o número e o ano foram digitados corretamente no formato número/ano (ex: 165/2026).\n\nAtenção: Não utilize o número de protocolo do Livro de Ocorrências Eletrônico (que é de outro sistema). Utilize apenas o número oficial da Notificação/Multa."
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    exit;
}

if ($action == 'resend_existing') {
    $numero = $_POST['numero'] ?? '';
    $ano = $_POST['ano'] ?? '';
    $numeroCompleto = $numero . '/' . $ano;

    $sqlRec = "SELECT id, email, token FROM recurso WHERE numero = '" . DBEscape($numeroCompleto) . "'";
    $resRec = DBExecute($sqlRec);

    if ($resRec && mysqli_num_rows($resRec) > 0) {
        $rec = mysqli_fetch_assoc($resRec);
        if (!empty($rec['email'])) {
            // Seta o e-mail internamente para que o bloco action=send_token faça o envio
            $_POST['email'] = $rec['email'];
            $action = 'send_token';
        } else {
            echo json_encode(['success' => false, 'error' => 'Recurso não possui e-mail cadastrado.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Recurso não encontrado.']);
        exit;
    }
}

if ($action == 'send_token') {
    $email = $_POST['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Email inválido.']);
        exit;
    }


    $tokenNum = rand(100000, 999999);
    $_SESSION['portal_token'] = [
        'email' => $email,
        'code' => $tokenNum,
        'expires' => time() + 3600
    ];

    $numeroStr = isset($_POST['numero']) && isset($_POST['ano'])
        ? filter_var($_POST['numero'], FILTER_SANITIZE_STRING) . '/' . filter_var($_POST['ano'], FILTER_SANITIZE_STRING)
        : '';

    if ($numeroStr !== '') {
        DBExecute("UPDATE recurso SET token = '$tokenNum' WHERE numero = '" . DBEscape($numeroStr) . "'");
    }

    // Envia email via API do Gmail
    $gmail = verificarToken();
    if ($gmail["status"]) {

        $assunto = "Código de verificação" . ($numeroStr ? " - Notificação $numeroStr" : " - Conselho Consultivo");
        $assunto_encoded = "=?UTF-8?B?" . base64_encode($assunto) . "?=";

        $mensagem = "Referência: Notificação / Recurso <b>" . ($numeroStr ? $numeroStr : "Não informada") . "</b><br><br>";
        $mensagem .= "Seu código de verificação para o assistente de recursos é: <b>$tokenNum</b><br><br>";
        $mensagem .= "<b>Não compartilhe este código com ninguém.</b> Ele servirá de senha para acesso futuro ao recurso no
painel de acompanhamento.";

        $mime = "Content-Type: text/html; charset=UTF-8\r\n";
        $mime .= "to: " . $email . "\r\n";
        $mime .= "subject: $assunto_encoded\r\n\r\n";
        $mime .= $mensagem . "\r\n";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.googleapis.com/upload/gmail/v1/users/me/messages/send?uploadType=media',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $mime,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: message/rfc822',
                'Authorization: Bearer ' . $gmail["tkn"]
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Serviço de email do servidor indisponível no momento.']);
    }
    exit;
}

if ($action == 'verify_token') {
    $token = $_POST['token'] ?? '';
    $sess = $_SESSION['portal_token'] ?? null;

    if ($sess && $sess['code'] == $token && time() <= $sess['expires']) {
        $_SESSION['portal_verified'] = true;
        echo
            json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Código inválido ou expirado.']);
    }
    exit;
}

if ($action == 'submit') {
    if (empty($_SESSION['portal_verified'])) {
        echo json_encode(['success' => false, 'error' => 'Sessão não verificada. Faça o fluxo novamente.']);
        exit;
    }

    $numero = $_POST['numero'] ?? '';
    $ano = $_POST['ano'] ?? '';
    $bloco = $_POST['bloco'] ?? '';
    $unidade = (int) ($_POST['unidade'] ?? 0);
    $email = $_SESSION['portal_token']['email'] ?? '';
    $detalhes = $_POST['detalhes'] ?? '';
    $fato = ($_POST['fato'] ?? 'undefined');
    if ($fato === 'undefined' || empty($fato)) {
        $fato = 'Fato descrito em epígrafe.';
    }

    $numeroCompleto = $numero . '/' . $ano;

    // Confirma mais uma vez que o recurso não existe pra evitar duplicação (corrida de requests)
    $chk = DBExecute("SELECT id FROM recurso WHERE numero = '" . DBEscape($numeroCompleto) . "'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        echo json_encode(['success' => false, 'error' => 'Recurso já existente.']);
        exit;
    }

    // Define query
    $faseIncial = 1;
    $titulo = !empty($_POST['assunto']) ? $_POST['assunto'] : "Recurso Condômino $numeroCompleto";
    $nome = "Condômino ($email)";
    $tokenDb = DBEscape($_SESSION['portal_token']['code']); // Token serve como senha

    $sqlInsert = "INSERT INTO conselho.recurso (unidade, bloco, numero, fase, email, Nome, detalhes, titulo, data, fato,
    token)
    VALUES ($unidade, '" . DBEscape($bloco) . "', '" . DBEscape($numeroCompleto) . "', $faseIncial,
    '" . DBEscape($email) . "', '" . DBEscape($nome) . "', '" . DBEscape($detalhes) . "',
    '" . DBEscape($titulo) . "', '" . date('Y-m-d') . "', '" . DBEscape($fato) . "', '$tokenDb')";

    if (!DBExecute($sqlInsert)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar o recurso no banco de dados.']);
        exit;
    }

    // --- ENVIAR PUSH NOTIFICATION (Background) ---
    $tituloPush = "Novo Recurso ($numeroCompleto)";
    $mensagemPush = "Bloco {$bloco}-{$unidade} protocolou nova defesa.";
    
    // Detecta host e path base
    $httpHost = $_SERVER['HTTP_HOST'] ?? "mini.davinunes.eti.br";
    // Se o portal está em /recMan/portal, assumimos que o index.php está em /recMan/
    $basePathUrl = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/recMan/') !== false) ? '/recMan/' : '/';
    $urlPush = "https://" . $httpHost . $basePathUrl . "index.php?pag=recurso&rec=" . urlencode($numeroCompleto);
    
    sendPushBackground($tituloPush, $mensagemPush, $urlPush);

    // Tratamento de Arquivos Anexados
    $storageDir = __DIR__ . '/../storage/anexos/';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0777, true);
    }

    $anexosAdicionados = [];
    $anexosGrandesPulos = 0;

    if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
        foreach ($_FILES['anexos']['name'] as $key => $name) {
            $tmp = $_FILES['anexos']['tmp_name'][$key];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            // Salvar com nome seguro e único
            $newName = uniqid() . '_' . time() . '.' . $ext;
            $dest = $storageDir . $newName;

            if (move_uploaded_file($tmp, $dest)) {
                $sqlAnexo = "INSERT INTO recurso_anexos (numero_recurso, nome_arquivo, caminho_arquivo) VALUES (
        '" . DBEscape($numeroCompleto) . "',
        '" . DBEscape($name) . "',
        '" . DBEscape($newName) . "'
        )";
                DBExecute($sqlAnexo);

                // Track para email do síndico
                $tamanhoMb = filesize($dest) / 1048576;
                if ($tamanhoMb <= 10) {
                    $anexosAdicionados[] = ['nome' => $name, 'caminho' => $dest, 'mime' => mime_content_type($dest)];
                } else {
                    $anexosGrandesPulos++;
                    $anexosAdicionados[] = ['nome' => "[ARQUIVO GRANDE > 10MB - VEJA NO SISTEMA] " . $name]; // Apenas citação no texto
                }
            }
        }
    }

    // --- ENVIAR EMAIL PARA SÍNDICO ---
    $gmail = verificarToken();
    if ($gmail["status"]) {
        $assunto = "Novo Recurso Interposto - Notificação $numeroCompleto";
        $assunto_encoded = "=?UTF-8?B?" . base64_encode($assunto) . "?=";

        $msgHtml = "<h3>Novo Recurso Cadastrado ($numeroCompleto)</h3>";
        $msgHtml .= "<p><b>Condômino:</b> $email<br>";
        $msgHtml .= "<b>Unidade/Bloco:</b> {$unidade}{$bloco}<br>";
        $msgHtml .= "<b>Data:</b> " . date('d/m/Y H:i') . "</p>";
        $msgHtml .= "<h4>Alegações/Defesa:</h4>";
        $msgHtml .= "<div style='padding: 15px; border: 1px solid #ccc; background: #f9f9f9;'>" . nl2br(htmlspecialchars($detalhes)) . "</div>";

        $msgHtml .= "<br><b>Anexos Submetidos pelo Morador:</b><br>";
        if (empty($anexosAdicionados)) {
            $msgHtml .= "<i>Nenhum arquivo enviado ou detectado.</i><br>";
        } else {
            $msgHtml .= "<ul>";
            foreach ($anexosAdicionados as $aa) {
                $msgHtml .= "<li>" . htmlspecialchars($aa['nome']) . "</li>";
            }
            $msgHtml .= "</ul>";
        }
        $msgHtml .= "<br><br><small>Por favor, acesse o painel (recMan) para visualizar o recurso na íntegra.</small>";

        // Fetch dynamic recipients from config
        $resRecipients = DBExecute("SELECT * FROM config_emails_diretoria WHERE ativo = 1");
        $to_list = [];
        $cc_list = [$email]; // Resident is always CC

        $copiarSubs = getConfigSistema('copiar_subsindicos_novo_recurso') == '1';

        if ($resRecipients && mysqli_num_rows($resRecipients) > 0) {
            while ($cfg = mysqli_fetch_assoc($resRecipients)) {
                if ($cfg['funcao'] == 'sindico' || $cfg['funcao'] == 'administracao') {
                    $to_list[] = $cfg['email'];
                } else if ($cfg['funcao'] == 'subsindico' && $copiarSubs) {
                    if ($cfg['bloco'] == $bloco) $cc_list[] = $cfg['email'];
                }
            }
        }

        if (empty($to_list)) $to_list = explode(',', EMAIL_SINDICO_NOTIFICACAO);
        
        $to_str = implode(', ', array_unique(array_map('trim', $to_list)));
        $cc_str = implode(', ', array_unique(array_map('trim', $cc_list)));

        // Multipart Email Configuration
        $boundary = uniqid('np');
        $mime = "To: $to_str\r\n";
        if (!empty($cc_str)) $mime .= "Cc: $cc_str\r\n";
        $mime .= "Subject: $assunto_encoded\r\n";
        $mime .= "MIME-Version: 1.0\r\n";
        $mime .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";

        // Corpo HTML
        $mime .= "--$boundary\r\n";
        $mime .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $mime .= $msgHtml . "\r\n\r\n";

        // Anexos Físicos (só os que tem caminho, que passaram na regra dos < 10mb)
        foreach ($anexosAdicionados as $aa) {
            if (isset($aa['caminho']) && file_exists($aa['caminho'])) {
                $file_content = file_get_contents($aa['caminho']);
                $b64 = chunk_split(base64_encode($file_content));
                $mimeType = $aa['mime'] ?: 'application/octet-stream';

                $mime .= "--$boundary\r\n";
                $mime .= "Content-Type: $mimeType; name=\"" . htmlspecialchars($aa['nome']) . "\"\r\n";
                $mime .= "Content-Disposition: attachment; filename=\"" . htmlspecialchars($aa['nome']) . "\"\r\n";
                $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $mime .= $b64 . "\r\n\r\n";
            }
        }
        $mime .= "--$boundary--\r\n";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.googleapis.com/upload/gmail/v1/users/me/messages/send?uploadType=media',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $mime,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: message/rfc822',
                'Authorization: Bearer ' . $gmail["tkn"]
            ),
        ));
        curl_exec($curl);
        curl_close($curl);
    }

    // Finaliza sessão de verificação
    unset($_SESSION['portal_verified']);
    unset($_SESSION['portal_token']);

    echo json_encode(['success' => true]);
    exit;
}

if ($action == 'login') {
    $numero = $_POST['numero'] ?? '';
    $ano = $_POST['ano'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $numeroCompleto = $numero . '/' . $ano;
    $sql = "SELECT id, fase, token FROM recurso WHERE numero = '" . DBEscape($numeroCompleto) . "'";
    $res = DBExecute($sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $rec = mysqli_fetch_assoc($res);
        if ($rec['token'] === $senha) {
            $_SESSION['portal_auth'] = $numeroCompleto;
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Notificação ou senha não encontrados.']);
    exit;
}

if ($action == 'logout') {
    unset($_SESSION['portal_auth']);
    echo json_encode(['success' => true]);
    exit;
}

// Rotas protegidas para Portal (moradores) e algumas para Conselho
if (
    $action == 'my_resource' || $action == 'add_comment' || $action == 'add_attachments' || $action ==
    'download_parecer' || $action == 'get_anexo'
) {

    // get_anexo é a única que o conselho pode bater diretamente daqui
    $isCouncil = !empty($_SESSION['user_id']);
    $isPortal = !empty($_SESSION['portal_auth']);

    if (!$isPortal && !$isCouncil) {
        if ($action == 'get_anexo') {
            die("Não autorizado.");
        } else {
            echo json_encode(['success' => false, 'error' => 'Não autorizado.']);
            exit;
        }
    }

    if (!$isPortal && $action != 'get_anexo') {
        echo json_encode(['success' => false, 'error' => 'Não autorizado para conselheiros aqui.']);
        exit;
    }

    $numeroRec = $isPortal ? $_SESSION['portal_auth'] : null;

    if ($action == 'my_resource') {
        $sql = "SELECT r.*, f.texto as fase_texto, p.concluido as parecer_concluido
                FROM recurso r
                LEFT JOIN fase f ON r.fase = f.id
                LEFT JOIN parecer p ON p.id = r.numero
                WHERE r.numero = '" . DBEscape($numeroRec) . "'";
        $res = DBExecute($sql);
        $recurso = mysqli_fetch_assoc($res);

        $anexos = getAnexos($numeroRec);
        
        // Buscar diligências enviadas ao requerente
        $sqlDil = "SELECT id, texto, timestamp FROM conselho.diligencia WHERE id_recurso = '" . DBEscape($recurso['id']) . "' AND enviada_ao_requerente = 1 ORDER BY timestamp ASC";
        $resDil = DBExecute($sqlDil);
        $diligencias = [];
        if ($resDil) {
            while ($d = mysqli_fetch_assoc($resDil)) {
                $d['anexos'] = getDiligenciaAnexos($d['id']);
                $diligencias[] = $d;
            }
        }

        // Remove campos sensiveis
        unset($recurso['token']);

        echo json_encode([
            'success' => true,
            'recurso' => $recurso,
            'anexos' => $anexos,
            'diligencias' => $diligencias
        ]);
        exit;
    }

    if ($action == 'add_comment') {
        $texto = $_POST['comentario'] ?? '';
        if (trim($texto) != '') {
            $dataHoje = date('d/m/Y H:i');
            $append = "\n\n----------------------------\n[$dataHoje] Condômino adicionou:\n$texto";
            $sql = "UPDATE recurso SET detalhes = CONCAT(detalhes, '" . DBEscape($append) . "') WHERE numero = '" .
                DBEscape($numeroRec) . "'";
            DBExecute($sql);

            // Notifica conselheiros
            $tituloPush = "Novo Comentário ($numeroRec)";
            $mensagemPush = "O condômino adicionou informações ao recurso.";
            $urlPush = "https://" . ($_SERVER['HTTP_HOST'] ?? "mini.davinunes.eti.br") . "/index.php?pag=recurso&rec=" . urlencode($numeroRec);
            sendPushBackground($tituloPush, $mensagemPush, $urlPush);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Comentário vazio.']);
        }
        exit;
    }

    if ($action == 'add_attachments') {
        $storageDir = __DIR__ . '/../storage/anexos/';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }
        $pushedAny = false;
        if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
            foreach ($_FILES['anexos']['name'] as $key => $name) {
                $tmp = $_FILES['anexos']['tmp_name'][$key];
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $newName = uniqid() . '_' . time() . '.' . $ext;
                $dest = $storageDir . $newName;

                if (move_uploaded_file($tmp, $dest)) {
                    $sqlAnexo = "INSERT INTO recurso_anexos (numero_recurso, nome_arquivo, caminho_arquivo) VALUES (
                '" . DBEscape($numeroRec) . "',
                '" . DBEscape($name) . "',
                '" . DBEscape($newName) . "'
                )";
                    DBExecute($sqlAnexo);
                    $pushedAny = true;
                }
            }
        }
        if ($pushedAny) {
            // Notifica conselheiros
            $tituloPush = "Novos Anexos ($numeroRec)";
            $mensagemPush = "O condômino enviou novos arquivos para o recurso.";
            $urlPush = "https://" . ($_SERVER['HTTP_HOST'] ?? "mini.davinunes.eti.br") . "/index.php?pag=recurso&rec=" . urlencode($numeroRec);
            sendPushBackground($tituloPush, $mensagemPush, $urlPush);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action == 'get_anexo') {
        $idAnexo = (int) ($_GET['id'] ?? 0);
        $sql = "SELECT * FROM recurso_anexos WHERE id = $idAnexo";

        if ($isPortal && !$isCouncil) {
            $sql .= " AND numero_recurso = '" . DBEscape($numeroRec) . "'";
        }

        $res = DBExecute($sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $anexo = mysqli_fetch_assoc($res);
            $file = __DIR__ . '/../storage/anexos/' . $anexo['caminho_arquivo'];
            if (file_exists($file)) {
                $isView = isset($_GET['view']) && $_GET['view'] == '1';
                $mime = mime_content_type($file);

                header('Content-Type: ' . ($mime ? $mime : 'application/octet-stream'));

                if (
                    $isView && strpos($mime, 'image/') === 0 || strpos($mime, 'video/') === 0 || strpos($mime, 'audio/')
                    === 0
                ) {
                    header('Content-Disposition: inline; filename="' . basename($anexo['nome_arquivo']) . '"');
                } else {
                    header('Content-Disposition: attachment; filename="' . basename($anexo['nome_arquivo']) . '"');
                }

                readfile($file);
                exit;
            }
        }
        die("Arquivo não encontrado.");
    }

    if ($action == 'download_parecer') {
        $sql = "SELECT r.*, p.* FROM recurso r JOIN parecer p ON r.numero = p.id WHERE r.numero = '" .
            DBEscape($numeroRec) . "' AND p.concluido = 1";
        $res = DBExecute($sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $parecer = mysqli_fetch_assoc($res);
            include_once "../classes/pdfParecer.php";
            $pdfData = [
                'notificacao' => $parecer['id'],
                'unidade' => $parecer['unidade'],
                'assunto' => $parecer['assunto'],
                'fato' => $parecer['notificacao'],
                'analise' => $parecer['analise'],
                'resultado' => $parecer['resultado'],
                'parecer' => $parecer['conclusao'],
                'data_emissao' => date('Y-m-d', strtotime($parecer['data']))
            ];

            $resp = json_decode(getParecerPdf($pdfData), true);
            if (isset($resp['pdf_base64'])) {
                header("Content-type: application/pdf");
                header('Content-Disposition: attachment; filename="Parecer_' . str_replace('/', '-', $numeroRec) .
                    '.pdf"');
                echo base64_decode($resp['pdf_base64']);
                exit;
            }
        }
        die("Parecer não disponível.");
    }
}

echo json_encode(['error' => 'Ação inválida.']);