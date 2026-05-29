<?php
// Habilitar cabeçalhos CORS e tipo de retorno JSON
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Se for OPTIONS (pre-flight do CORS), retornar status OK e encerrar
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configurações de erro limpas - qualquer erro será lançado como exceção e retornado como JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// --- AUTENTICAÇÃO BEARER (Desabilitada temporariamente para testes) ---
/*
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token Bearer não fornecido.']);
    exit;
}
$token = $matches[1];
if ($token !== 'NOSSO_TOKEN_SECRETO') { // Substitua pelo token real do sistema
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Não autorizado: token inválido.']);
    exit;
}
*/

// Carrega as dependências necessárias
require_once __DIR__ . '/../classes/repositorio.php';
require_once __DIR__ . '/../classes/pdfParecer.php';

$method = $_SERVER['REQUEST_METHOD'];

// Helper para converter diversos formatos de data para YYYY-MM-DD
function parseDateToMysql($dateStr) {
    if (empty($dateStr)) return null;
    $dateStr = trim($dateStr);
    
    // YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        return $dateStr;
    }
    // DD/MM/YYYY
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $matches)) {
        return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
    }
    // Tenta usar strtotime
    $time = strtotime($dateStr);
    if ($time) {
        return date('Y-m-d', $time);
    }
    return null;
}

if ($method === 'GET') {
    // --- METODO GET ---
    
    // Captura parâmetros de filtro
    $numeros = [];
    if (isset($_GET['numeros'])) {
        if (is_array($_GET['numeros'])) {
            $numeros = $_GET['numeros'];
        } else {
            $numeros = explode(',', $_GET['numeros']);
        }
    }
    if (isset($_GET['numero'])) {
        $numeros[] = $_GET['numero'];
    }
    $numeros = array_filter(array_map('trim', $numeros));

    $mes = isset($_GET['mes']) && $_GET['mes'] !== '' ? (int)$_GET['mes'] : null;
    $ano = isset($_GET['ano']) && $_GET['ano'] !== '' ? (int)$_GET['ano'] : null;

    // Monta consulta SQL para recursos (incluindo data retirada, obs, data cobrança e valor)
    $sql = "SELECT r.id, r.unidade, r.bloco, r.numero, r.artigo, r.fase, r.email, r.Nome, r.detalhes, r.titulo, r.data, r.fato,
                   d.dia_retirada AS data_retirada,
                   d.obs AS observacao_retirada,
                   mc.data_vencimento AS data_cobranca,
                   mc.valor AS valor_cobranca
            FROM recurso r
            LEFT JOIN DatasDeRetirada d ON d.virtual = r.numero
            LEFT JOIN multas_cobradas mc ON mc.numero = CAST(SUBSTRING_INDEX(r.numero, '/', 1) AS UNSIGNED) 
                                        AND mc.ano = CAST(SUBSTRING_INDEX(r.numero, '/', -1) AS UNSIGNED)
            WHERE 1=1";

    if (!empty($numeros)) {
        $escaped_numeros = array_map('DBEscape', $numeros);
        $sql .= " AND r.numero IN ('" . implode("','", $escaped_numeros) . "')";
    }

    if ($mes !== null) {
        $sql .= " AND MONTH(r.data) = " . $mes;
    }

    if ($ano !== null) {
        $sql .= " AND YEAR(r.data) = " . $ano;
    }

    $sql .= " ORDER BY r.data DESC, r.id DESC";

    $recursosRes = DBQuery($sql);
    $recursos = [];

    if ($recursosRes) {
        foreach ($recursosRes as $row) {
            $recursoNumero = $row['numero'];
            
            // Busca o parecer correspondente (se existir)
            $sqlParecer = "SELECT * FROM parecer WHERE id = '" . DBEscape($recursoNumero) . "'";
            $parecerRes = DBQuery($sqlParecer);
            
            $parecerObj = null;
            if ($parecerRes && count($parecerRes) > 0) {
                $parecerRow = $parecerRes[0];
                
                // Monta estrutura de dados para o PDF
                $pdfData = [
                    'notificacao' => $parecerRow['id'] ?? '',
                    'unidade' => $parecerRow['unidade'] ?? '',
                    'assunto' => $parecerRow['assunto'] ?? '',
                    'fato' => $parecerRow['notificacao'] ?? 'Fato narrado na cópia da Notificação',
                    'analise' => $parecerRow['analise'] ?? 'Foram apreciadas as provas apresentadas pela administração e confrontadas com a argumentação e demais fatos descritos no recurso',
                    'resultado' => $parecerRow['resultado'] ?? 'Considerações finais a serem realizadas',
                    'parecer' => $parecerRow['conclusao'] ?? '',
                    'data_emissao' => !empty($parecerRow['data']) ? date('Y-m-d', strtotime($parecerRow['data'])) : date('Y-m-d')
                ];
                
                $pdfBase64 = null;
                $pdfHash = null;
                
                // Aciona a API Python via cURL (limite de timeout 3s)
                try {
                    $pdfResponse = getParecerPdf($pdfData);
                    if ($pdfResponse) {
                        $pdfJson = json_decode($pdfResponse, true);
                        if (isset($pdfJson['pdf_base64'])) {
                            $pdfBase64 = $pdfJson['pdf_base64'];
                            $pdfHash = hash('sha256', base64_decode($pdfBase64));
                        }
                    }
                } catch (Exception $e) {
                    // Ignora falhas da API python para não travar a listagem do recurso
                }
                
                $parecerObj = [
                    'data' => $parecerRow['data'],
                    'resultado' => $parecerRow['resultado'],
                    'pdf' => $pdfBase64,
                    'pdf_hash' => $pdfHash
                ];
            }
            
            $row['parecer'] = $parecerObj;
            $recursos[] = $row;
        }
    }

    echo json_encode($recursos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;

} elseif ($method === 'POST') {
    // --- METODO POST ---
    
    // Lê payload JSON ou campos POST normais
    $input = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['content-type'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $rawBody = file_get_contents('php://input');
        $input = json_decode($rawBody, true) ?? [];
    } else {
        $input = $_POST;
    }

    // Identifica o recurso (via ID do banco ou Número do Recurso)
    $id = $input['recurso_id'] ?? ($input['id'] ?? null);
    $numero = $input['recurso_numero'] ?? ($input['numero'] ?? null);

    if (!$id && !$numero) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Identificador do recurso (recurso_id ou recurso_numero) não fornecido.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Busca o recurso
    $recurso = null;
    if ($id) {
        $recurso = getRecursoById($id);
    } elseif ($numero) {
        $escapedNum = DBEscape($numero);
        $sql = "SELECT * FROM recurso WHERE numero = '$escapedNum'";
        $res = DBQuery($sql);
        if ($res && count($res) > 0) {
            $recurso = $res[0];
        }
    }

    if (!$recurso) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Recurso correspondente não encontrado na base de dados.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $recursoNumero = $recurso['numero'];
    $parts = explode('/', $recursoNumero);
    $notificacao_numero = isset($parts[0]) ? (int)$parts[0] : null;
    $notificacao_ano = isset($parts[1]) ? (int)$parts[1] : null;

    if (!$notificacao_numero || !$notificacao_ano) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'O recurso possui um número inválido para mapeamento da notificação correspondente.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 1. Atualizar Data de Ciência da Notificação (Tabela DatasDeRetirada)
    $dia_retirada = isset($input['data_ciencia']) ? parseDateToMysql($input['data_ciencia']) : (isset($input['dia_retirada']) ? parseDateToMysql($input['dia_retirada']) : null);
    $obs_retirada = $input['obs_retirada'] ?? ($input['obs'] ?? null);

    $updateDatasDeRetirada = false;
    $datasDeRetiradaFields = [
        'notificacao' => $notificacao_numero,
        'ano' => $notificacao_ano,
        'bloco' => $recurso['bloco'],
        'apartamento' => $recurso['unidade']
    ];
    $datasDeRetiradaUpdates = [];

    if ($dia_retirada !== null) {
        $datasDeRetiradaFields['dia_retirada'] = $dia_retirada;
        $datasDeRetiradaUpdates[] = "dia_retirada = '" . DBEscape($dia_retirada) . "'";
        $updateDatasDeRetirada = true;
    }
    if ($obs_retirada !== null) {
        $datasDeRetiradaFields['obs'] = $obs_retirada;
        $datasDeRetiradaUpdates[] = "obs = '" . DBEscape($obs_retirada) . "'";
        $updateDatasDeRetirada = true;
    }

    if ($updateDatasDeRetirada) {
        $existsSql = "SELECT 1 FROM DatasDeRetirada WHERE notificacao = $notificacao_numero AND ano = $notificacao_ano";
        $existsRes = DBQuery($existsSql);
        
        if ($existsRes && count($existsRes) > 0) {
            $sql = "UPDATE DatasDeRetirada SET " . implode(", ", $datasDeRetiradaUpdates) . " WHERE notificacao = $notificacao_numero AND ano = $notificacao_ano";
            DBExecute($sql);
        } else {
            $cols = implode(", ", array_keys($datasDeRetiradaFields));
            $vals = [];
            foreach ($datasDeRetiradaFields as $k => $v) {
                $vals[] = $v === null ? "NULL" : "'" . DBEscape($v) . "'";
            }
            $sql = "INSERT INTO DatasDeRetirada ($cols) VALUES (" . implode(", ", $vals) . ")";
            DBExecute($sql);
        }
    }

    // 2. Atualizar Cadastro da Notificação Geral (Tabela notificacoes)
    $notifFields = [
        'numero' => $notificacao_numero,
        'ano' => $notificacao_ano,
        'torre' => $recurso['bloco'],
        'unidade' => $recurso['unidade']
    ];
    $notifUpdates = [];
    $updateNotif = false;

    $mappableNotif = [
        'assunto' => 'assunto',
        'notificacao' => 'notificacao',
        'cobranca' => 'cobranca',
        'obs_notificacao' => 'obs',
        'obs_solucoes' => 'obs',
    ];

    foreach ($mappableNotif as $inputKey => $colName) {
        if (isset($input[$inputKey])) {
            $val = $input[$inputKey];
            $notifFields[$colName] = $val;
            $notifUpdates[] = "$colName = " . ($val === null ? "NULL" : "'" . DBEscape($val) . "'");
            $updateNotif = true;
        }
    }

    $dateFieldsNotif = [
        'data_email' => 'data_email',
        'data_envio' => 'data_envio',
        'data_ocorrido' => 'data_ocorrido'
    ];

    foreach ($dateFieldsNotif as $inputKey => $colName) {
        if (isset($input[$inputKey])) {
            $val = parseDateToMysql($input[$inputKey]);
            $notifFields[$colName] = $val;
            $notifUpdates[] = "$colName = " . ($val === null ? "NULL" : "'" . DBEscape($val) . "'");
            $updateNotif = true;
        }
    }

    if ($updateNotif) {
        $existsSql = "SELECT 1 FROM notificacoes WHERE numero = $notificacao_numero AND ano = $notificacao_ano";
        $existsRes = DBQuery($existsSql);
        
        if ($existsRes && count($existsRes) > 0) {
            $sql = "UPDATE notificacoes SET " . implode(", ", $notifUpdates) . " WHERE numero = $notificacao_numero AND ano = $notificacao_ano";
            DBExecute($sql);
        } else {
            $cols = implode(", ", array_keys($notifFields));
            $vals = [];
            foreach ($notifFields as $k => $v) {
                $vals[] = $v === null ? "NULL" : "'" . DBEscape($v) . "'";
            }
            $sql = "INSERT INTO notificacoes ($cols) VALUES (" . implode(", ", $vals) . ")";
            DBExecute($sql);
        }
    }

    // 3. Atualizar/Inserir Multas Cobradas (Tabela multas_cobradas)
    $multa_valor = $input['valor_cobranca'] ?? ($input['multa_valor'] ?? ($input['valor'] ?? null));
    $multa_vencimento = isset($input['data_cobranca']) ? parseDateToMysql($input['data_cobranca']) : 
                        (isset($input['data_vencimento']) ? parseDateToMysql($input['data_vencimento']) : 
                        (isset($input['multa_vencimento']) ? parseDateToMysql($input['multa_vencimento']) : null));
    $multa_pagamento = isset($input['multa_pagamento']) ? parseDateToMysql($input['multa_pagamento']) : 
                       (isset($input['data_pagamento']) ? parseDateToMysql($input['data_pagamento']) : null);

    $updateMulta = ($multa_valor !== null || $multa_vencimento !== null || $multa_pagamento !== null);

    if ($updateMulta) {
        $existsSql = "SELECT 1 FROM multas_cobradas WHERE numero = $notificacao_numero AND ano = $notificacao_ano";
        $existsRes = DBQuery($existsSql);
        
        if ($existsRes && count($existsRes) > 0) {
            $updates = [];
            if ($multa_valor !== null) $updates[] = "valor = '" . DBEscape($multa_valor) . "'";
            if ($multa_vencimento !== null) $updates[] = "data_vencimento = '" . DBEscape($multa_vencimento) . "'";
            if ($multa_pagamento !== null) {
                $updates[] = "data_pagamento = '" . DBEscape($multa_pagamento) . "'";
            } elseif (isset($input['multa_pagamento']) || isset($input['data_pagamento'])) {
                $updates[] = "data_pagamento = NULL";
            }
            $updates[] = "updated_at = NOW()";
            
            $sql = "UPDATE multas_cobradas SET " . implode(", ", $updates) . " WHERE numero = $notificacao_numero AND ano = $notificacao_ano";
            DBExecute($sql);
        } else {
            $unidade = $recurso['unidade'];
            $bloco = $recurso['bloco'];
            $vencimento_val = $multa_vencimento !== null ? "'" . DBEscape($multa_vencimento) . "'" : "CURRENT_DATE()";
            $pagamento_val = $multa_pagamento !== null ? "'" . DBEscape($multa_pagamento) . "'" : "NULL";
            $valor_val = $multa_valor !== null ? "'" . DBEscape($multa_valor) . "'" : "0.00";
            
            $sql = "INSERT INTO multas_cobradas (unidade, bloco, data_vencimento, data_pagamento, valor, numero, ano, created_at, updated_at) 
                    VALUES ('$unidade', '$bloco', $vencimento_val, $pagamento_val, $valor_val, $notificacao_numero, $notificacao_ano, NOW(), NOW())";
            DBExecute($sql);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cadastro de notificação atualizado com sucesso.',
        'data' => [
            'notificacao' => $notificacao_numero,
            'ano' => $notificacao_ano,
            'recurso_numero' => $recursoNumero
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;

} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido.'
    ]);
    exit;
}
