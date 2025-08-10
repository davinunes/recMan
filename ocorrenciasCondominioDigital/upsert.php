<?php
// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

// Inclui o arquivo com as funções de banco de dados
require_once '../classes/database.php';

// Função para enviar a resposta JSON e encerrar o script
function send_response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    send_response(['status' => 'error', 'message' => 'Método inválido. Apenas POST é permitido.']);
}

// Pega os dados do POST
$id = $_POST['id'] ?? null;
$bloco = $_POST['bloco'] ?? null;
$unidade = $_POST['unidade'] ?? null;
$url = $_POST['url'] ?? null;
$status = isset($_POST['status']) ? $_POST['status'] : null;
$total_mensagens = isset($_POST['total_mensagens']) ? (int)$_POST['total_mensagens'] : 0;

// Trata as datas, convertendo para o formato do banco de dados
$data_ultima_mensagem_raw = $_POST['data_ultima_mensagem'] ?? null;
$data_ultima_mensagem = null;
if ($data_ultima_mensagem_raw) {
    $data_ultima_mensagem_raw = str_replace('+', ' ', $data_ultima_mensagem_raw);
    $dateTime = DateTime::createFromFormat('d/m/Y H:i:s', $data_ultima_mensagem_raw);
    if ($dateTime) {
        $data_ultima_mensagem = $dateTime->format('Y-m-d H:i:s');
    }
}

$abertura_raw = $_POST['abertura'] ?? null;
$abertura = null;
if ($abertura_raw) {
    $abertura_raw = str_replace('+', ' ', $abertura_raw);
    $dateTime = DateTime::createFromFormat('d/m/Y H:i:s', $abertura_raw);
    if ($dateTime) {
        $abertura = $dateTime->format('Y-m-d H:i:s');
    }
}

// Trata os campos booleanos
$resolvido = (isset($_POST['resolvido']) && strtolower($_POST['resolvido']) === 'sim') ? 1 : 0;
$sindico = (isset($_POST['sindico']) && strtolower($_POST['sindico']) === 'sim') ? 1 : 0;
$sub = (isset($_POST['subsindico']) && strtolower($_POST['subsindico']) === 'sim') ? 1 : 0;
$adm = (isset($_POST['administracao']) && strtolower($_POST['administracao']) === 'sim') ? 1 : 0;

if (empty($id)) {
    http_response_code(400);
    send_response(['status' => 'error', 'message' => 'O campo ID é obrigatório.']);
}

$id = (int)$id;
$link = null;

try {
    $link = DBConnect();

    $stmt = mysqli_prepare($link, "SELECT id FROM ocorrencias WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ocorrenciaExistente = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($ocorrenciaExistente) {
        // ATUALIZAR OCORRÊNCIA EXISTENTE
        $fieldsToUpdate = [];
        $params = [];
        $types = '';

        if ($abertura) { $fieldsToUpdate[] = 'abertura = ?'; $types .= 's'; $params[] = $abertura; }
        if (isset($_POST['bloco'])) { $fieldsToUpdate[] = 'bloco = ?'; $types .= 's'; $params[] = $bloco; }
        if (isset($_POST['unidade'])) { $fieldsToUpdate[] = 'unidade = ?'; $types .= 's'; $params[] = $unidade; }
        if (isset($_POST['url'])) { $fieldsToUpdate[] = 'url = ?'; $types .= 's'; $params[] = $url; }
        if (isset($_POST['status'])) { $fieldsToUpdate[] = 'status = ?'; $types .= 's'; $params[] = $status; }
        if (isset($_POST['sindico'])) { $fieldsToUpdate[] = 'sindico = ?'; $types .= 'i'; $params[] = $sindico; }
        if (isset($_POST['subsindico'])) { $fieldsToUpdate[] = 'sub = ?'; $types .= 'i'; $params[] = $sub; }
        if (isset($_POST['administracao'])) { $fieldsToUpdate[] = 'adm = ?'; $types .= 'i'; $params[] = $adm; }
        if (isset($_POST['resolvido'])) { $fieldsToUpdate[] = 'resolvido = ?'; $types .= 'i'; $params[] = $resolvido; }
        if (isset($_POST['total_mensagens'])) { $fieldsToUpdate[] = 'total_mensagens = ?'; $types .= 'i'; $params[] = $total_mensagens; }
        if (isset($_POST['data_ultima_mensagem'])) { $fieldsToUpdate[] = 'data_ultima_mensagem = ?'; $types .= 's'; $params[] = $data_ultima_mensagem; }

        if (count($fieldsToUpdate) > 0) {
            $types .= 'i';
            $params[] = $id;
            $sql = "UPDATE ocorrencias SET " . implode(', ', $fieldsToUpdate) . " WHERE id = ?";
            
            $updateStmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($updateStmt, $types, ...$params);
            $executionSuccess = mysqli_stmt_execute($updateStmt);
            $affectedRows = mysqli_stmt_affected_rows($updateStmt);
            $errorMessage = mysqli_stmt_error($updateStmt);
            mysqli_stmt_close($updateStmt);

            $debugInfo = ['query' => $sql, 'params' => $params, 'execution_success' => $executionSuccess, 'affected_rows' => $affectedRows, 'error_message' => $errorMessage];
            send_response(['status' => 'success', 'action' => 'updated', 'id' => $id, 'debug' => $debugInfo]);
        } else {
            send_response(['status' => 'success', 'action' => 'no_change', 'message' => 'Nenhum dado para atualizar foi fornecido.', 'id' => $id]);
        }

    } else {
        // CRIAR NOVA OCORRÊNCIA
        if (empty($abertura)) {
            http_response_code(400);
            send_response(['status' => 'error', 'message' => 'O campo "abertura" é obrigatório ou está em formato inválido.']);
        }

        $sql = "INSERT INTO ocorrencias (id, abertura, bloco, unidade, url, status, sindico, sub, adm, resolvido, total_mensagens, data_ultima_mensagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($link, $sql);
        
        // --- CORREÇÃO AQUI ---
        // A string de tipos estava errada. O correto é 'isssssiiiiis' (12 tipos para 12 colunas).
        $types = "isssssiiiiis";
        mysqli_stmt_bind_param($insertStmt, $types, $id, $abertura, $bloco, $unidade, $url, $status, $sindico, $sub, $adm, $resolvido, $total_mensagens, $data_ultima_mensagem);
        
        $executionSuccess = mysqli_stmt_execute($insertStmt);
        $affectedRows = mysqli_stmt_affected_rows($insertStmt);
        $errorMessage = mysqli_stmt_error($insertStmt);
        mysqli_stmt_close($insertStmt);

        $debugInfo = [
            'query' => $sql,
            'params' => [$id, $abertura, $bloco, $unidade, $url, $status, $sindico, $sub, $adm, $resolvido, $total_mensagens, $data_ultima_mensagem],
            'execution_success' => $executionSuccess,
            'affected_rows' => $affectedRows,
            'error_message' => $errorMessage
        ];

        if ($executionSuccess && $affectedRows > 0) {
            send_response(['status' => 'success', 'action' => 'created', 'id' => $id, 'debug' => $debugInfo]);
        } else {
            http_response_code(500); // Internal Server Error
            send_response(['status' => 'error', 'action' => 'creation_failed', 'id' => $id, 'debug' => $debugInfo]);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    send_response(['status' => 'error', 'message' => 'Erro no servidor: ' . $e->getMessage()]);
} finally {
    if ($link) {
        DBClose($link);
    }
}
?>