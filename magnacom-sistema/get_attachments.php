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
session_write_close(); // Libera trava de sessão antes das consultas externas ao Supabase

$rec = isset($_REQUEST['rec']) ? trim($_REQUEST['rec']) : '';
if (empty($rec)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parâmetro identificador do recurso (rec) não informado.']);
    exit;
}

// --- 2. CARREGAMENTO DAS CONFIGURAÇÕES E CLASSES ---
$repositorioPath = dirname(__DIR__) . "/classes/repositorio.php";
if (!file_exists($repositorioPath)) {
    echo json_encode(['success' => false, 'error' => 'Arquivo de repositório local não encontrado.']);
    exit;
}
require_once $repositorioPath;

$cfg = getMagnacomConfig();
$supabaseUrl = $cfg['url'];
$supabaseAnonKey = $cfg['anon_key'];

if (empty($supabaseUrl) || empty($supabaseAnonKey)) {
    echo json_encode(['success' => false, 'error' => 'Configurações de URL e Anon Key do Supabase ausentes. Acesse Ferramentas > Configurações Magnacom.']);
    exit;
}

// --- 3. REQUISITA OU RECUPERA TOKEN DO SUPABASE ---
try {
    $token = null;
    if (isset($_SESSION['supabase_token']) && isset($_SESSION['supabase_token_expires']) && $_SESSION['supabase_token_expires'] > time()) {
        $token = $_SESSION['supabase_token'];
    } else {
        $token = getValidMagnacomToken();
        if (empty($token)) {
            throw new Exception("Token de acesso JWT não disponível para o Supabase. Configure em Ferramentas > Configurações Magnacom.");
        }

        $_SESSION['supabase_token'] = $token;
        $_SESSION['supabase_token_expires'] = time() + 3500;
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
