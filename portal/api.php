<?php
session_start();
require_once "../classes/repositorio.php";

$action = $_GET['action'] ?? '';

if ($action == 'check_notification') {
    $numero = $_POST['numero'] ?? '';
    $ano = $_POST['ano'] ?? '';
    $numeroCompleto = $numero . '/' . $ano;

    // Procura recurso
    $sqlRec = "SELECT id, email, token FROM recurso WHERE numero = '" . DBEscape($numeroCompleto) . "'";
    $resRec = DBExecute($sqlRec);

    if ($resRec && mysqli_num_rows($resRec) > 0) {
        $rec = mysqli_fetch_assoc($resRec);
        // Já existe um recurso
        echo json_encode(['exists' => true, 'has_email' => !empty($rec['email'])]);
        exit;
    }

    // Procura na tabela notificacoes
    $sqlNot = "SELECT * FROM notificacoes WHERE numero = '" . DBEscape($numero) . "' AND ano = '" . DBEscape($ano) . "'";
    $resNot = DBExecute($sqlNot);

    if ($resNot && mysqli_num_rows($resNot) > 0) {
        $not = mysqli_fetch_assoc($resNot);
        echo json_encode(['exists' => false, 'notificacao' => $not]);
    } else {
        echo json_encode(['exists' => false, 'notificacao' => null]);
    }
    exit;
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

    // Envia email via API do Gmail
    $gmail = verificarToken();
    if ($gmail["status"]) {
        $assunto = "Código de verificação - Conselho Consultivo";
        $assunto_encoded = "=?UTF-8?B?" . base64_encode($assunto) . "?=";

        $mensagem = "Seu código de verificação para o assistente de recursos é: <b>$tokenNum</b><br><br>Não compartilhe este código com ninguém. Ele servirá de senha para acesso futuro ao recurso.";

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
        echo json_encode(['success' => true]);
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
    $fato = $_POST['fato'] ?? 'Fato narrado na cópia da Notificação';

    $numeroCompleto = $numero . '/' . $ano;

    // Confirma mais uma vez que o recurso não existe pra evitar duplicação (corrida de requests)
    $chk = DBExecute("SELECT id FROM recurso WHERE numero = '" . DBEscape($numeroCompleto) . "'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        echo json_encode(['success' => false, 'error' => 'Recurso já existente.']);
        exit;
    }

    // Define query
    $faseIncial = 1;
    $titulo = "Recurso Condômino $numeroCompleto";
    $nome = "Condômino ($email)";
    $tokenDb = DBEscape($_SESSION['portal_token']['code']); // Token serve como senha

    $sqlInsert = "INSERT INTO conselho.recurso (unidade, bloco, numero, fase, email, Nome, detalhes, titulo, data, fato, token) 
                  VALUES ($unidade, '" . DBEscape($bloco) . "', '" . DBEscape($numeroCompleto) . "', $faseIncial, 
                  '" . DBEscape($email) . "', '" . DBEscape($nome) . "', '" . DBEscape($detalhes) . "', 
                  '" . DBEscape($titulo) . "', '" . date('Y-m-d') . "', '" . DBEscape($fato) . "', '$tokenDb')";

    if (!DBExecute($sqlInsert)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar o recurso no banco de dados.']);
        exit;
    }

    // Tratamento de Arquivos Anexados
    $storageDir = __DIR__ . '/../storage/anexos/';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0777, true);
    }

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
            }
        }
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

// Rotas protegidas abaixo
if ($action == 'my_resource' || $action == 'add_comment' || $action == 'add_attachments' || $action == 'get_anexo' || $action == 'download_parecer') {
    if (empty($_SESSION['portal_auth'])) {
        echo json_encode(['success' => false, 'error' => 'Não autorizado.']);
        exit;
    }

    $numeroRec = $_SESSION['portal_auth'];

    if ($action == 'my_resource') {
        $sql = "SELECT r.*, f.texto as fase_texto, p.concluido as parecer_concluido 
                FROM recurso r 
                LEFT JOIN fase f ON r.fase = f.id 
                LEFT JOIN parecer p ON p.id = r.numero 
                WHERE r.numero = '" . DBEscape($numeroRec) . "'";
        $res = DBExecute($sql);
        $recurso = mysqli_fetch_assoc($res);

        $anexos = getAnexos($numeroRec);

        // Remove campos sensiveis
        unset($recurso['token']);

        echo json_encode([
            'success' => true,
            'recurso' => $recurso,
            'anexos' => $anexos
        ]);
        exit;
    }

    if ($action == 'add_comment') {
        $texto = $_POST['comentario'] ?? '';
        if (trim($texto) != '') {
            $dataHoje = date('d/m/Y H:i');
            $append = "\n\n----------------------------\n[$dataHoje] Condômino adicionou:\n$texto";
            $sql = "UPDATE recurso SET detalhes = CONCAT(detalhes, '" . DBEscape($append) . "') WHERE numero = '" . DBEscape($numeroRec) . "'";
            DBExecute($sql);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Comentário vazio.']);
        }
        exit;
    }

    if ($action == 'add_attachments') {
        $storageDir = __DIR__ . '/../storage/anexos/';
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
                }
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action == 'get_anexo') {
        $idAnexo = (int) ($_GET['id'] ?? 0);
        $sql = "SELECT * FROM recurso_anexos WHERE id = $idAnexo AND numero_recurso = '" . DBEscape($numeroRec) . "'";
        $res = DBExecute($sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $anexo = mysqli_fetch_assoc($res);
            $file = __DIR__ . '/../storage/anexos/' . $anexo['caminho_arquivo'];
            if (file_exists($file)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($anexo['nome_arquivo']) . '"');
                readfile($file);
                exit;
            }
        }
        die("Arquivo não encontrado.");
    }

    if ($action == 'download_parecer') {
        $sql = "SELECT r.*, p.* FROM recurso r JOIN parecer p ON r.numero = p.id WHERE r.numero = '" . DBEscape($numeroRec) . "' AND p.concluido = 1";
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
                header('Content-Disposition: attachment; filename="Parecer_' . str_replace('/', '-', $numeroRec) . '.pdf"');
                echo base64_decode($resp['pdf_base64']);
                exit;
            }
        }
        die("Parecer não disponível.");
    }
}

echo json_encode(['error' => 'Ação inválida.']);
