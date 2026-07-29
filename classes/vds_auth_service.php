<?php

require_once __DIR__ . "/database.php";

define('VDS_BASE_URL', 'https://apiv8.vds.app.br');
define('VDS_ORIGIN_HEADER', 'https://app1.vidadesindico.com.br');

/**
 * Obtém o token anônimo de boot da VDS.
 */
function vds_get_anon_token() {
    $ch = curl_init(VDS_BASE_URL . '/auth/anon');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $json = json_decode($response, true);
        if (isset($json['token'])) {
            return $json['token'];
        }
        if (is_string($json)) {
            return $json;
        }
    }
    return null;
}

/**
 * Autentica credenciais na VDS e retorna a resposta com o Bearer Token.
 */
function vds_authenticate($username, $password, $app = "976") {
    $anonToken = vds_get_anon_token();
    if (!$anonToken) {
        return ['success' => false, 'message' => 'Falha ao obter token anônimo inicial da VDS.'];
    }

    $payload = json_encode([
        'app' => (string)$app,
        'username' => (string)$username,
        'password' => (string)$password,
        'crypt' => false
    ]);

    $ch = curl_init(VDS_BASE_URL . '/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $anonToken,
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $json = json_decode($response, true);
        $userToken = $json['token'] ?? ($json['bearerToken'] ?? null);
        $refreshToken = $json['refreshToken'] ?? null;
        $expires = $json['expires'] ?? null;
        if ($userToken) {
            return [
                'success' => true,
                'token' => $userToken,
                'refreshToken' => $refreshToken,
                'expires' => $expires,
                'userUuid' => $json['usuarioUuid'] ?? ($json['uuid'] ?? null),
                'username' => $username,
                'raw' => $json
            ];
        }
    }

    return [
        'success' => false,
        'httpCode' => $httpCode,
        'message' => 'Credenciais inválidas na VDS ou erro de login (' . $httpCode . ').'
    ];
}

/**
 * Salva ou atualiza o token do Condomínio.
 */
function vds_save_condominio_token($username, $password) {
    $auth = vds_authenticate($username, $password);
    if (!$auth['success']) {
        return $auth;
    }

    $link = DBConnect();
    $stmt = mysqli_prepare($link, "INSERT INTO vds_tokens (tipo, usuario_id_conselho, vds_username, vds_user_uuid, bearer_token, refresh_token, expires_at, status) VALUES ('condominio', NULL, ?, ?, ?, ?, ?, 'ativo') ON DUPLICATE KEY UPDATE vds_username = VALUES(vds_username), vds_user_uuid = VALUES(vds_user_uuid), bearer_token = VALUES(bearer_token), refresh_token = VALUES(refresh_token), expires_at = VALUES(expires_at), status = 'ativo', updated_at = NOW()");
    $expiresFormatted = !empty($auth['expires']) ? date('Y-m-d H:i:s', strtotime($auth['expires'])) : null;
    mysqli_stmt_bind_param($stmt, "sssss", $auth['username'], $auth['userUuid'], $auth['token'], $auth['refreshToken'], $expiresFormatted);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    DBClose($link);

    return ['success' => $success, 'token' => $auth['token']];
}

/**
 * Salva ou atualiza o "Ultra-Login" do Conselheiro. (NUNCA salva a senha).
 */
function vds_save_conselheiro_token($usuarioIdConselho, $username, $password) {
    $auth = vds_authenticate($username, $password);
    if (!$auth['success']) {
        return $auth;
    }

    $link = DBConnect();
    $stmt = mysqli_prepare($link, "INSERT INTO vds_tokens (tipo, usuario_id_conselho, vds_username, vds_user_uuid, bearer_token, refresh_token, expires_at, status) VALUES ('conselheiro', ?, ?, ?, ?, ?, ?, 'ativo') ON DUPLICATE KEY UPDATE vds_username = VALUES(vds_username), vds_user_uuid = VALUES(vds_user_uuid), bearer_token = VALUES(bearer_token), refresh_token = VALUES(refresh_token), expires_at = VALUES(expires_at), status = 'ativo', updated_at = NOW()");
    $expiresFormatted = !empty($auth['expires']) ? date('Y-m-d H:i:s', strtotime($auth['expires'])) : null;
    mysqli_stmt_bind_param($stmt, "isssss", $usuarioIdConselho, $auth['username'], $auth['userUuid'], $auth['token'], $auth['refreshToken'], $expiresFormatted);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Mapear também na vds_uuid_mapping para consulta rápida
    if (!empty($auth['userUuid'])) {
        $chave = "conselheiro_" . $usuarioIdConselho;
        $stmtMap = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto, dados_extras_json) VALUES ('usuario', ?, ?, ?) ON DUPLICATE KEY UPDATE uuid_remoto = VALUES(uuid_remoto), dados_extras_json = VALUES(dados_extras_json)");
        $json = json_encode(['username' => $auth['username'], 'conselheiroId' => $usuarioIdConselho], JSON_UNESCAPED_UNICODE);
        mysqli_stmt_bind_param($stmtMap, "sss", $chave, $auth['userUuid'], $json);
        mysqli_stmt_execute($stmtMap);
        mysqli_stmt_close($stmtMap);
    }

    DBClose($link);
    return ['success' => $success, 'token' => $auth['token'], 'userUuid' => $auth['userUuid']];
}

/**
 * Recupera o Token ativo do banco de dados (prioriza token de conselheiro se fornecido ID).
 */
function vds_get_token($usuarioIdConselho = null) {
    $link = DBConnect();
    if ($usuarioIdConselho) {
        $stmt = mysqli_prepare($link, "SELECT bearer_token, vds_username, status FROM vds_tokens WHERE tipo = 'conselheiro' AND usuario_id_conselho = ? AND status = 'ativo' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $usuarioIdConselho);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        if ($row && !empty($row['bearer_token'])) {
            DBClose($link);
            return $row['bearer_token'];
        }
    }

    // Fallback para Token do Condomínio
    $resCond = mysqli_query($link, "SELECT bearer_token FROM vds_tokens WHERE tipo = 'condominio' AND status = 'ativo' LIMIT 1");
    $rowCond = mysqli_fetch_assoc($resCond);
    DBClose($link);

    return $rowCond['bearer_token'] ?? null;
}

/**
 * Valida o status do Token na VDS (GET /usuario/status).
 */
function vds_check_token_status($token) {
    if (!$token) return false;
    $ch = curl_init(VDS_BASE_URL . '/usuario/status');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200);
}

/**
 * Retorna alertas de sessão para o Toast/Banner da interface.
 */
function vds_get_toast_alerts($usuarioIdConselho = null) {
    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        return [
            'type' => 'warning',
            'message' => 'Nenhum Ultra-Login ativado. Acesse a tela de configurações para conectar ao Condomínio Digital.'
        ];
    }

    if (!vds_check_token_status($token)) {
        return [
            'type' => 'danger',
            'message' => 'Seu token de acesso ao Condomínio Digital expirou. Efetue o Ultra-Login novamente na tela de configurações.'
        ];
    }

    return null;
}
