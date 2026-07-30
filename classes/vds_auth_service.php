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
    @mysqli_query($link, "CREATE TABLE IF NOT EXISTS vds_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tipo ENUM('condominio', 'conselheiro') NOT NULL DEFAULT 'conselheiro',
        usuario_id_conselho INT DEFAULT NULL,
        vds_username VARCHAR(100) DEFAULT NULL,
        vds_user_uuid VARCHAR(100) DEFAULT NULL,
        bearer_token TEXT NOT NULL,
        refresh_token TEXT DEFAULT NULL,
        status ENUM('ativo', 'expirado', 'erro') DEFAULT 'ativo',
        expires_at DATETIME DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_tipo_usuario (tipo, usuario_id_conselho)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Limpar registros anteriores de condomínio para evitar duplicações/expirados
    mysqli_query($link, "DELETE FROM vds_tokens WHERE tipo = 'condominio'");

    $expiresFormatted = !empty($auth['expires']) ? date('Y-m-d H:i:s', strtotime($auth['expires'])) : null;

    $stmtIns = mysqli_prepare($link, "INSERT INTO vds_tokens (tipo, usuario_id_conselho, vds_username, vds_user_uuid, bearer_token, refresh_token, expires_at, status) VALUES ('condominio', NULL, ?, ?, ?, ?, ?, 'ativo')");
    mysqli_stmt_bind_param($stmtIns, "sssss", $auth['username'], $auth['userUuid'], $auth['token'], $auth['refreshToken'], $expiresFormatted);
    $success = mysqli_stmt_execute($stmtIns);
    mysqli_stmt_close($stmtIns);

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
    @mysqli_query($link, "CREATE TABLE IF NOT EXISTS vds_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tipo ENUM('condominio', 'conselheiro') NOT NULL DEFAULT 'conselheiro',
        usuario_id_conselho INT DEFAULT NULL,
        vds_username VARCHAR(100) DEFAULT NULL,
        vds_user_uuid VARCHAR(100) DEFAULT NULL,
        bearer_token TEXT NOT NULL,
        refresh_token TEXT DEFAULT NULL,
        status ENUM('ativo', 'expirado', 'erro') DEFAULT 'ativo',
        expires_at DATETIME DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_tipo_usuario (tipo, usuario_id_conselho)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Limpar registros legados deste conselheiro
    $uId = (int)$usuarioIdConselho;
    mysqli_query($link, "DELETE FROM vds_tokens WHERE tipo = 'conselheiro' AND usuario_id_conselho = {$uId}");

    $expiresFormatted = !empty($auth['expires']) ? date('Y-m-d H:i:s', strtotime($auth['expires'])) : null;

    $stmtIns = mysqli_prepare($link, "INSERT INTO vds_tokens (tipo, usuario_id_conselho, vds_username, vds_user_uuid, bearer_token, refresh_token, expires_at, status) VALUES ('conselheiro', ?, ?, ?, ?, ?, ?, 'ativo')");
    mysqli_stmt_bind_param($stmtIns, "isssss", $usuarioIdConselho, $auth['username'], $auth['userUuid'], $auth['token'], $auth['refreshToken'], $expiresFormatted);
    $success = mysqli_stmt_execute($stmtIns);
    mysqli_stmt_close($stmtIns);

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
 * Tenta renovar o Bearer Token usando o Refresh Token registrado na VDS.
 * Endpoint confirmado via Postman (item 1.4): POST /login/refresh
 * Body exigido: {"token":"<bearerAtual>","refreshToken":"<refreshToken>","crypt":false}
 */
function vds_refresh_token($tokenId) {
    $link = DBConnect();
    $stmt = mysqli_prepare($link, "SELECT id, tipo, usuario_id_conselho, refresh_token, bearer_token FROM vds_tokens WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $tokenId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$row || empty($row['refresh_token'])) {
        DBClose($link);
        return null;
    }

    $refreshToken = $row['refresh_token'];
    $bearerToken  = $row['bearer_token'];

    // Endpoint confirmado pelo mapeamento Postman (captura real do browser)
    $url = VDS_BASE_URL . '/login/refresh';

    // Body exige o token atual + refreshToken + crypt (conforme item 1.4 da collection)
    $payload = json_encode([
        'token'        => $bearerToken,
        'refreshToken' => $refreshToken,
        'crypt'        => false
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER,
            'Accept: application/json, text/plain, */*'
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $newToken        = null;
    $newRefreshToken = null;
    $newExpires      = null;

    if ($httpCode === 200 && $response) {
        $json = json_decode($response, true);
        $t = $json['token'] ?? ($json['bearerToken'] ?? null);
        if ($t) {
            $newToken        = $t;
            $newRefreshToken = $json['refreshToken'] ?? $refreshToken;
            $newExpires      = $json['expires'] ?? null;
        }
    }

    if ($newToken) {
        $expiresFormatted = !empty($newExpires) ? date('Y-m-d H:i:s', strtotime($newExpires)) : null;
        $stmtUp = mysqli_prepare($link, "UPDATE vds_tokens SET bearer_token = ?, refresh_token = ?, expires_at = ?, status = 'ativo', updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmtUp, "sssi", $newToken, $newRefreshToken, $expiresFormatted, $row['id']);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
        DBClose($link);
        return $newToken;
    }

    // Refresh falhou — marcar como expirado para forçar novo login manual
    $stmtErr = mysqli_prepare($link, "UPDATE vds_tokens SET status = 'expirado', updated_at = NOW() WHERE id = ?");
    if ($stmtErr) {
        mysqli_stmt_bind_param($stmtErr, "i", $row['id']);
        mysqli_stmt_execute($stmtErr);
        mysqli_stmt_close($stmtErr);
    }
    DBClose($link);
    return null;
}

/**
 * Marca explicitamente um token como expirado quando a API retorna HTTP 401.
 */
function vds_mark_token_expired($token) {
    if (!$token) return;
    $link = DBConnect();
    $stmt = mysqli_prepare($link, "UPDATE vds_tokens SET status = 'expirado' WHERE bearer_token = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    DBClose($link);
}

/**
 * Recupera o Token ativo do banco de dados.
 * Se $usuarioIdConselho for fornecido, busca especificamente tipo='conselheiro'.
 * Se for null, busca especificamente tipo='condominio'.
 */
function vds_get_token($usuarioIdConselho = null) {
    $link = DBConnect();
    
    @mysqli_query($link, "CREATE TABLE IF NOT EXISTS vds_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tipo ENUM('condominio', 'conselheiro') NOT NULL DEFAULT 'conselheiro',
        usuario_id_conselho INT DEFAULT NULL,
        vds_username VARCHAR(100) DEFAULT NULL,
        vds_user_uuid VARCHAR(100) DEFAULT NULL,
        bearer_token TEXT NOT NULL,
        refresh_token TEXT DEFAULT NULL,
        status ENUM('ativo', 'expirado', 'erro') DEFAULT 'ativo',
        expires_at DATETIME DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_tipo_usuario (tipo, usuario_id_conselho)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $targetRow = null;

    // 1. Se fornecido ID de conselheiro, buscar token do conselheiro
    if ($usuarioIdConselho) {
        $stmt = mysqli_prepare($link, "SELECT * FROM vds_tokens WHERE tipo = 'conselheiro' AND usuario_id_conselho = ? AND bearer_token IS NOT NULL AND bearer_token != '' ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $usuarioIdConselho);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $targetRow = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
        }
    }

    // 2. Se $usuarioIdConselho for null (sistema/sincronização global), buscar especificamente o token de condomínio
    if (!$targetRow && !$usuarioIdConselho) {
        $resCond = mysqli_query($link, "SELECT * FROM vds_tokens WHERE tipo = 'condominio' AND bearer_token IS NOT NULL AND bearer_token != '' ORDER BY id DESC LIMIT 1");
        if ($resCond) {
            $targetRow = mysqli_fetch_assoc($resCond);
        }
    }

    // 3. Fallback genérico para qualquer Token cadastrado na vds_tokens
    if (!$targetRow) {
        $resAny = mysqli_query($link, "SELECT * FROM vds_tokens WHERE bearer_token IS NOT NULL AND bearer_token != '' ORDER BY id DESC LIMIT 1");
        if ($resAny) {
            $targetRow = mysqli_fetch_assoc($resAny);
        }
    }

    DBClose($link);

    if (!$targetRow || empty($targetRow['bearer_token'])) {
        return null;
    }

    // Se o status local estiver marcado como 'expirado', tentar renovar imediatamente
    if ($targetRow['status'] === 'expirado') {
        if (!empty($targetRow['refresh_token'])) {
            $refreshedToken = vds_refresh_token($targetRow['id']);
            if ($refreshedToken) {
                return $refreshedToken;
            }
        }
        return null; // Expirado e refresh falhou → login manual necessário
    }

    // Verificação proativa: se expires_at está definido e vai vencer nos próximos 10 minutos, renovar agora
    if (!empty($targetRow['expires_at']) && !empty($targetRow['refresh_token'])) {
        $expiresTs  = strtotime($targetRow['expires_at']);
        $nowTs      = time();
        $windowSecs = 10 * 60; // 10 minutos de antecedência

        if ($expiresTs !== false && ($expiresTs - $nowTs) <= $windowSecs) {
            $refreshedToken = vds_refresh_token($targetRow['id']);
            if ($refreshedToken) {
                return $refreshedToken;
            }
            // Se o refresh falhou mas o token ainda não venceu, usar o atual por enquanto
            if ($nowTs < $expiresTs) {
                return $targetRow['bearer_token'];
            }
            return null;
        }
    }

    return $targetRow['bearer_token'];
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

/**
 * Retorna estatísticas de diagnósticos detalhados da tabela vds_tokens.
 */
function vds_get_tokens_debug_stats() {
    $link = DBConnect();
    $stats = [
        'table_exists' => false,
        'total_rows' => 0,
        'rows' => [],
        'db_error' => null
    ];

    $resCheck = mysqli_query($link, "SHOW TABLES LIKE 'vds_tokens'");
    if ($resCheck && mysqli_num_rows($resCheck) > 0) {
        $stats['table_exists'] = true;
        $resCount = mysqli_query($link, "SELECT COUNT(*) as total FROM vds_tokens");
        if ($resCount) {
            $rowCount = mysqli_fetch_assoc($resCount);
            $stats['total_rows'] = (int)($rowCount['total'] ?? 0);
        }

        $resRows = mysqli_query($link, "SELECT id, tipo, usuario_id_conselho, vds_username, vds_user_uuid, status, LENGTH(bearer_token) as token_len, expires_at, updated_at FROM vds_tokens ORDER BY id DESC LIMIT 10");
        if ($resRows) {
            while ($r = mysqli_fetch_assoc($resRows)) {
                $stats['rows'][] = $r;
            }
        } else {
            $stats['db_error'] = mysqli_error($link);
        }
    } else {
        $stats['db_error'] = "Tabela 'vds_tokens' não existe no banco de dados.";
    }

    DBClose($link);
    return $stats;
}
