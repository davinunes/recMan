<?php
/**
 * Script de Sincronização de Recurso Individual (Supabase)
 * 
 * Este endpoint é chamado via AJAX a partir da tela de detalhes do recurso.
 * Ele autentica-se no Supabase, consulta a notificação individual e
 * sincroniza tanto os dados cadastrais (tabelas notificacoes/DatasDeRetirada)
 * quanto copia a 'tipificacao' da notificação para o campo 'artigo' do Recurso.
 * 
 * Requer uma sessão ativa de usuário autenticado.
 */

header("Content-Type: application/json; charset=utf-8");

// Configurações de erro limpas (erros serão retornados como JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_exception_handler(function($e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage()
    ]);
    exit;
});

// --- 1. VALIDAÇÃO DE AUTENTICAÇÃO E PARÂMETROS ---
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acesso negado: sessão expirada ou usuário não autenticado.']);
    exit;
}

$rec = isset($_REQUEST['rec']) ? trim($_REQUEST['rec']) : '';
if (empty($rec)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parâmetro identificador do recurso (rec) não informado.']);
    exit;
}

// Separa número e ano a partir do identificador (ex: "165/2026")
$parts = explode('/', $rec);
$numero = isset($parts[0]) ? intval(preg_replace('/[^\d]/', '', $parts[0])) : null;
$ano = isset($parts[1]) ? intval(preg_replace('/[^\d]/', '', $parts[1])) : null;

if (!$numero || !$ano) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Identificador do recurso inválido. Use o formato número/ano (ex: 165/2026).']);
    exit;
}

// --- 2. CARREGAMENTO DO .ENV E CLASSES ---
function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
    return true;
}

$envPath = __DIR__ . '/.env';
if (!loadEnv($envPath)) {
    echo json_encode(['success' => false, 'error' => 'Arquivo .env não localizado na pasta magnacom-sistema.']);
    exit;
}

$supabaseUrl = getenv('SUPABASE_URL');
$supabaseAnonKey = getenv('SUPABASE_ANON_KEY');
$supabaseEmail = getenv('SUPABASE_EMAIL');
$supabasePassword = getenv('SUPABASE_PASSWORD');

if (empty($supabaseUrl) || empty($supabaseAnonKey) || empty($supabaseEmail) || empty($supabasePassword)) {
    echo json_encode(['success' => false, 'error' => 'Configurações de credenciais do Supabase ausentes no arquivo .env.']);
    exit;
}

$repositorioPath = dirname(__DIR__) . "/classes/repositorio.php";
if (!file_exists($repositorioPath)) {
    echo json_encode(['success' => false, 'error' => 'Arquivo de repositório local não encontrado.']);
    exit;
}
require_once $repositorioPath;

// --- 3. REQUISIÇÃO AO SUPABASE VIA cURL ---

try {
    // A. Autenticação para obter o Token JWT
    $authUrl = rtrim($supabaseUrl, '/') . '/auth/v1/token?grant_type=password';
    $authPayload = json_encode([
        'email' => $supabaseEmail,
        'password' => $supabasePassword
    ]);

    $ch = curl_init($authUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $authPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabaseAnonKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $authResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Falha de comunicação de rede no Auth: " . $curlErr);
    }
    if ($httpCode !== 200) {
        throw new Exception("Falha de autenticação (HTTP $httpCode): " . $authResponse);
    }

    $authData = json_decode($authResponse, true);
    $token = $authData['access_token'] ?? '';
    if (empty($token)) {
        throw new Exception("Token de acesso JWT não retornado pelo Supabase.");
    }

    // B. Consulta à Notificação Específica
    // Formato de query: numero=eq.165/2026 (urlencode para a barra)
    $dbUrl = rtrim($supabaseUrl, '/') . '/rest/v1/notificacoes?select=*&numero=eq.' . urlencode($rec);

    $ch = curl_init($dbUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabaseAnonKey,
        'authorization: Bearer ' . $token,
        'accept-profile: public',
        'accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $dbResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Falha de comunicação de rede na consulta: " . $curlErr);
    }
    if ($httpCode !== 200) {
        throw new Exception("Falha de consulta no banco remoto (HTTP $httpCode): " . $dbResponse);
    }

    $rows = json_decode($dbResponse, true);
    if (!is_array($rows) || count($rows) === 0) {
        echo json_encode([
            'success' => false,
            'error' => "Notificação '$rec' não foi encontrada no banco do Supabase."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $row = $rows[0];

    // --- 4. MAPEA E PROCESSA DADOS PARA O BANCO LOCAL ---

    // A. Torre
    $torre = null;
    if (isset($row['torre']) && $row['torre'] !== '') {
        $torre = preg_replace('/[^A-F]/i', '', strtoupper($row['torre']));
        $torre = !empty($torre) ? $torre : null;
    }

    // B. Unidade (apto)
    $unidade = 0;
    if (isset($row['apto']) && $row['apto'] !== '') {
        $unidade = intval(preg_replace('/[^\d]/', '', $row['apto']));
    }

    // C. Normalização do tipo
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

    // D. Assunto (infracao)
    $assunto = isset($row['infracao']) && $row['infracao'] !== '' ? trim($row['infracao']) : null;

    // E. Datas
    function parseDateToDb($dateStr) {
        if (empty($dateStr)) return null;
        $dateStr = trim($dateStr);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
        $timestamp = strtotime($dateStr);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    function parseTipificacaoToNotacao($tipificacao) {
        $tipificacao = trim($tipificacao);
        if (empty($tipificacao)) return null;
        $clean = preg_replace('/^art(igo)?\.?\s+/i', '', $tipificacao);
        if (!preg_match('/^(\d+)/', $clean, $matches)) return null;
        $artigo = $matches[1];
        $resto = trim(substr($clean, strlen($artigo)));
        $romanos = [
            'xiv' => 14, 'xiii' => 13, 'xii' => 12, 'xi' => 11, 'x' => 10,
            'ix' => 9, 'viii' => 8, 'vii' => 7, 'vi' => 6, 'v' => 5,
            'iv' => 4, 'iii' => 3, 'ii' => 2, 'i' => 1
        ];
        if (preg_match('/(?:inciso\s+)?([ivxldcm]+)\b/i', $resto, $matches)) {
            $romano = strtolower($matches[1]);
            if (isset($romanos[$romano])) return $artigo . '.' . $romanos[$romano];
        }
        if (preg_match('/inciso\s+(\d+)/i', $resto, $matches)) {
            return $artigo . '.' . $matches[1];
        }
        if (preg_match('/par[aá]grafo\s+u[nni]co/i', $resto)) {
            return $artigo . '.unico';
        }
        if (preg_match('/(?:par[aá]grafo\s+|§\s*)(\d+)/ui', $resto, $matches)) {
            return $artigo . '.' . $matches[1];
        }
        return $artigo;
    }

    $data_email = parseDateToDb($row['confeccao'] ?? '');
    $data_envio = parseDateToDb($row['envio'] ?? '');
    $data_ocorrido = parseDateToDb($row['data_ocorrido'] ?? '');

    // F. Status (resultado)
    $status = isset($row['resultado']) && $row['resultado'] !== '' ? trim($row['resultado']) : null;

    // G. Cobrança e Parcelamento
    $cobrado = (isset($row['cobrado_realizado']) && $row['cobrado_realizado']) ? 'Cobrado' : 'Não cobrado';
    $parcelamento = isset($row['parcelamento']) && $row['parcelamento'] !== '' ? trim($row['parcelamento']) : '';
    $cobranca = $cobrado;
    if ($parcelamento !== '') {
        $cobranca .= ' - ' . $parcelamento;
    }

    // H. Observação (obs)
    $obs = ($parcelamento !== '') ? $parcelamento : null;

    // I. Montagem do array da Notificação
    $linha = [
        'numero' => $numero,
        'ano' => $ano,
        'torre' => $torre,
        'unidade' => $unidade,
        'notificacao' => $normalizedTipo,
        'assunto' => $assunto,
        'artigo' => parseTipificacaoToNotacao($row['tipificacao'] ?? ''),
        'data_email' => $data_email,
        'data_envio' => $data_envio,
        'data_ocorrido' => $data_ocorrido,
        'status' => $status,
        'cobranca' => $cobranca,
        'obs' => $obs
    ];

    // Captura qualquer saída de texto (ex: o styledDump do repositorio.php)
    // para não quebrar a estrutura de retorno JSON da chamada AJAX.
    ob_start();
    $resNotif = upsertNotificacao($linha);
    $dbOutput = ob_get_clean();

    if ($resNotif !== 'ok') {
        throw new Exception("Falha ao salvar dados de notificação localmente: " . $resNotif);
    }

    // J. Mapeamento e Upsert de Datas de Retirada
    $dia_retirada = null;
    if (!empty($row['recebimento_fisico'])) {
        $dia_retirada = parseDateToDb($row['recebimento_fisico']);
    } elseif (!empty($row['conf_leitura_digital'])) {
        $dia_retirada = parseDateToDb($row['conf_leitura_digital']);
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

    // K. Sincronização do Artigo (Mapeia a tipificação)
    $artigoNotacao = parseTipificacaoToNotacao($row['tipificacao'] ?? '');

    echo json_encode([
        'success' => true,
        'message' => 'Notificação individual sincronizada com sucesso.',
        'data' => [
            'numero' => $numero,
            'ano' => $ano,
            'unidade' => $unidade,
            'torre' => $torre,
            'notificacao' => $normalizedTipo,
            'assunto' => $assunto,
            'artigo' => $artigoNotacao
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
