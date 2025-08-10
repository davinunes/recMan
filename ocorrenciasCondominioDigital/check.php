<?php
// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *"); // Permite requisições de qualquer origem

// Inclui o arquivo com as funções de banco de dados
// O caminho correto, assumindo que 'check.php' está na mesma pasta que 'upsert.php'
require_once '../classes/database.php';

// Função para enviar a resposta JSON e encerrar o script
function send_response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Pega o ID da requisição (aceita tanto GET quanto POST)
$id = $_REQUEST['id'] ?? null;

// Validação: O ID é obrigatório
if (empty($id)) {
    http_response_code(400); // Bad Request
    send_response(['status' => 'error', 'message' => 'O campo ID é obrigatório.']);
}

// Converte o ID para inteiro para segurança e consistência
$id = (int)$id;

$link = null; // Inicializa a variável de conexão para garantir que ela exista no bloco finally

try {
    // Obtém a conexão com o banco de dados usando sua função procedural
    $link = DBConnect();

    // Prepara a consulta para buscar todos os dados da ocorrência pelo ID
    // Usando prepared statements para máxima segurança
    $stmt = mysqli_prepare($link, "SELECT * FROM ocorrencias WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $ocorrencia = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);

    // Verifica se a ocorrência foi encontrada
    if ($ocorrencia) {
        // Sucesso: retorna o status e o objeto com os dados da ocorrência
        send_response(['status' => 'success', 'data' => $ocorrencia]);
    } else {
        // Erro: ocorrência não encontrada
        http_response_code(404); // Not Found
        send_response(['status' => 'error', 'message' => 'Ocorrência com o ID ' . $id . ' não foi encontrada.']);
    }

} catch (Exception $e) {
    // Captura exceções gerais ou erros de mysqli
    http_response_code(500); // Internal Server Error
    send_response(['status' => 'error', 'message' => 'Erro no servidor: ' . $e->getMessage()]);
} finally {
    // Garante que a conexão com o banco de dados seja sempre fechada
    if ($link) {
        DBClose($link);
    }
}
?>