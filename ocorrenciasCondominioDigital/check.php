<?php
// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *"); // Permite requisições de qualquer origem

// Inclui o arquivo com as funções de banco de dados
require_once '../classes/database.php';

// Função para enviar a resposta JSON e encerrar o script
function send_response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Pega o ID ou lista de IDs da requisição (aceita tanto GET quanto POST)
$id = $_REQUEST['id'] ?? null;
$ids = $_REQUEST['ids'] ?? null;

// Validação: Pelo menos um dos campos (id ou ids) é obrigatório
if (empty($id) && empty($ids)) {
    http_response_code(400); // Bad Request
    send_response(['status' => 'error', 'message' => 'É obrigatório informar o campo "id" ou "ids".']);
}

$link = null; // Inicializa a variável de conexão para garantir que ela exista no bloco finally

try {
    // Obtém a conexão com o banco de dados usando sua função procedural
    $link = DBConnect();

    // Se foi passado um ID único
    if (!empty($id)) {
        // Converte o ID para inteiro para segurança e consistência
        $id = (int)$id;

        // Prepara a consulta para buscar todos os dados da ocorrência pelo ID
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
    } 
    // Se foi passada uma lista de IDs
    else if (!empty($ids)) {
        // Valida se é uma string de IDs separados por vírgula
        if (!is_string($ids)) {
            http_response_code(400); // Bad Request
            send_response(['status' => 'error', 'message' => 'O campo "ids" deve ser uma string de IDs separados por vírgula.']);
        }

        // Converte a string de IDs em um array e valida cada ID
        $idArray = explode(',', $ids);
        $validIds = [];
        
        foreach ($idArray as $singleId) {
            $singleId = trim($singleId);
            if (is_numeric($singleId) && $singleId > 0) {
                $validIds[] = (int)$singleId;
            }
        }

        // Verifica se há IDs válidos
        if (empty($validIds)) {
            http_response_code(400); // Bad Request
            send_response(['status' => 'error', 'message' => 'Nenhum ID válido foi encontrado na lista.']);
        }

        // Remove duplicatas
        $validIds = array_unique($validIds);
        
        // Cria os placeholders para a consulta preparada (?, ?, ?, ...)
        $placeholders = str_repeat('?,', count($validIds) - 1) . '?';
        
        // Prepara a consulta para buscar todas as ocorrências pelos IDs
        $sql = "SELECT * FROM ocorrencias WHERE id IN ($placeholders) ORDER BY id";
        $stmt = mysqli_prepare($link, $sql);
        
        // Monta os tipos de parâmetros dinamicamente (todos são inteiros)
        $types = str_repeat('i', count($validIds));
        mysqli_stmt_bind_param($stmt, $types, ...$validIds);
        
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        $ocorrencias = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $ocorrencias[] = $row;
        }
        
        mysqli_stmt_close($stmt);

        // Verifica se alguma ocorrência foi encontrada
        if (!empty($ocorrencias)) {
            // Sucesso: retorna o status e o array com os dados das ocorrências
            send_response([
                'status' => 'success', 
                'data' => $ocorrencias,
                'count' => count($ocorrencias),
                'requested_ids' => $validIds,
                'found_count' => count($ocorrencias)
            ]);
        } else {
            // Erro: nenhuma ocorrência encontrada
            http_response_code(404); // Not Found
            send_response([
                'status' => 'error', 
                'message' => 'Nenhuma ocorrência foi encontrada para os IDs informados.',
                'requested_ids' => $validIds
            ]);
        }
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