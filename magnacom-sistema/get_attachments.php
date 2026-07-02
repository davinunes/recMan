<?php
/**
 * Script de Consulta de Anexos do Supabase
 * 
 * Este endpoint é chamado via AJAX ao abrir os detalhes de um recurso.
 * Ele autentica no Supabase, obtém o UUID da notificação e, se encontrar,
 * busca todos os anexos vinculados gerando URLs assinadas válidas por 1 hora.
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
    echo json_encode(['success' => false, 'error' => 'Acesso negado: usuário não autenticado.']);
    exit;
}

$rec = isset($_REQUEST['rec']) ? trim($_REQUEST['rec']) : '';
if (empty($rec)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parâmetro identificador do recurso (rec) não informado.']);
    exit;
}

// --- 2. CARREGAMENTO DO .ENV ---
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
    echo json_encode(['success' => false, 'error' => 'Arquivo .env não localizado.']);
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

// --- 3. REQUISITA OU RECUPERA TOKEN DO SUPABASE ---
try {
    $token = null;
    if (isset($_SESSION['supabase_token']) && isset($_SESSION['supabase_token_expires']) && $_SESSION['supabase_token_expires'] > time()) {
        $token = $_SESSION['supabase_token'];
    } else {
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
            throw new Exception("Falha de rede na autenticação: " . $curlErr);
        }
        if ($httpCode !== 200) {
            throw new Exception("Falha de autenticação no Supabase (HTTP $httpCode)");
        }

        $authData = json_decode($authResponse, true);
        $token = $authData['access_token'] ?? '';
        $expiresIn = $authData['expires_in'] ?? 3600;
        
        if (empty($token)) {
            throw new Exception("Token de acesso JWT não retornado pelo Supabase.");
        }

        $_SESSION['supabase_token'] = $token;
        $_SESSION['supabase_token_expires'] = time() + $expiresIn - 60; // margem de segurança de 60 segundos
    }

    // --- 4. CONSULTA A NOTIFICAÇÃO PARA OBTER O ID ---
    $dbUrl = rtrim($supabaseUrl, '/') . '/rest/v1/notificacoes?select=id&numero=eq.' . urlencode($rec);
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
        throw new Exception("Erro de rede na consulta da notificação: " . $curlErr);
    }
    if ($httpCode !== 200) {
        throw new Exception("Erro na consulta da notificação (HTTP $httpCode)");
    }

    $rows = json_decode($dbResponse, true);
    if (!is_array($rows) || count($rows) === 0) {
        // Se a notificação não existe no Supabase, retorna sucesso com lista vazia
        echo json_encode(['success' => true, 'attachments' => []]);
        exit;
    }

    $notificacaoId = $rows[0]['id'];

    // --- 5. CONSULTA ANEXOS DA NOTIFICAÇÃO ---
    $anexosUrl = rtrim($supabaseUrl, '/') . '/rest/v1/notificacao_anexos?select=*&notificacao_id=eq.' . urlencode($notificacaoId) . '&order=created_at.desc';
    $ch = curl_init($anexosUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabaseAnonKey,
        'authorization: Bearer ' . $token,
        'accept-profile: public',
        'accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $anexosResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Erro de rede na consulta dos anexos: " . $curlErr);
    }
    if ($httpCode !== 200) {
        throw new Exception("Erro na consulta dos anexos (HTTP $httpCode)");
    }

    $anexos = json_decode($anexosResponse, true);
    if (!is_array($anexos)) {
        throw new Exception("Dados de anexos inválidos.");
    }

    $attachmentsList = [];

    // --- 6. GERA URLS ASSINADAS PARA OS ANEXOS ---
    foreach ($anexos as $anexo) {
        $storagePath = $anexo['storage_path'] ?? '';
        if (empty($storagePath)) continue;

        // Codifica cada parte do caminho separadamente para evitar codificar as barras "/"
        $pathParts = explode('/', $storagePath);
        $encodedPath = implode('/', array_map('rawurlencode', $pathParts));

        $signUrl = rtrim($supabaseUrl, '/') . '/storage/v1/object/sign/anexos-notificacoes/' . ltrim($encodedPath, '/');
        
        $ch = curl_init($signUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['expiresIn' => 3600]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $supabaseAnonKey,
            'authorization: Bearer ' . $token,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $signResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            // Se falhar em um anexo específico, registra no log mas continua processando os outros
            error_log("Erro de rede na assinatura do arquivo {$storagePath}: " . $curlErr);
            continue;
        }

        if ($httpCode === 200) {
            $signData = json_decode($signResponse, true);
            $signedPath = $signData['signedURL'] ?? '';
            if (!empty($signedPath)) {
                $absoluteUrl = rtrim($supabaseUrl, '/') . '/storage/v1' . $signedPath;
                $attachmentsList[] = [
                    'id' => $anexo['id'],
                    'nome_arquivo' => $anexo['nome_arquivo'],
                    'tipo_arquivo' => $anexo['tipo_arquivo'],
                    'tamanho_bytes' => $anexo['tamanho_bytes'],
                    'url' => $absoluteUrl
                ];
            }
        } else {
            error_log("Erro ao assinar arquivo {$storagePath} (HTTP {$httpCode}): " . $signResponse);
        }
    }

    echo json_encode([
        'success' => true,
        'attachments' => $attachmentsList
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
