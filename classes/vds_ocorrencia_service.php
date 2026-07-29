<?php

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/vds_auth_service.php";

define('VDS_TEST_PROTOCOL', '259564');
define('VDS_TEST_MODE', true);

/**
 * Limpa e formata texto de mensagem evitando exibição de tags HTML brutas como <br/>.
 */
function vds_format_mensagem_text($text) {
    if (!$text) return '';
    $clean = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $clean = preg_replace('/<\/p>/i', "\n", $clean);
    $clean = strip_tags($clean);
    return nl2br(htmlspecialchars(trim($clean)));
}

/**
 * Consulta a lista de ocorrências da VDS e sincroniza na tabela `ocorrencias`.
 */
function vds_sync_ocorrencias($condominioUuid = null, $usuarioIdConselho = null) {
    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        return ['success' => false, 'message' => 'Nenhum token ativo disponível para sincronização.'];
    }

    $url = VDS_BASE_URL . '/ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=desc&Caixa=0&Lida=9';
    if ($condominioUuid) {
        $url .= '&Condominio.Uuid=' . urlencode($condominioUuid);
    }

    $ch = curl_init($url);
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

    if ($httpCode === 401) {
        return ['success' => false, 'httpCode' => 401, 'message' => 'Token expirado ao sincronizar ocorrências.'];
    }

    if ($httpCode !== 200 || !$response) {
        return ['success' => false, 'httpCode' => $httpCode, 'message' => 'Erro ao consultar ocorrências da VDS.'];
    }

    $data = json_decode($response, true);
    $items = $data['regs'] ?? ($data['items'] ?? ($data['data'] ?? (is_array($data) ? $data : [])));

    $link = DBConnect();
    $count = 0;

    foreach ($items as $item) {
        $protocolo = $item['protocolo'] ?? ($item['Protocolo'] ?? null);
        $uuidRemoto = $item['uuid'] ?? ($item['Uuid'] ?? null);
        $bloco = $item['bloco'] ?? ($item['Bloco'] ?? ($item['unidade']['bloco']['nome'] ?? null));
        $unidade = $item['unidade'] ?? ($item['Unidade'] ?? ($item['unidade']['numero'] ?? null));
        $abertura = $item['dtExibicao'] ?? ($item['abertura'] ?? date('Y-m-d H:i:s'));
        $ocoTipo = (int)($item['tipoId'] ?? ($item['tipo'] ?? ($item['ocoTipo'] ?? 115)));
        $status = $item['statusNome'] ?? ($item['statusStr'] ?? ($item['status'] ?? 'Aberto'));

        if (!$protocolo && !$uuidRemoto) continue;

        // Tentar obter ID local se já existir pelo protocolo
        $stmtFind = mysqli_prepare($link, "SELECT id FROM ocorrencias WHERE id = ? OR protocolo_vds = ? OR uuid_remoto = ?");
        $protoStr = (string)$protocolo;
        $uuidStr = (string)$uuidRemoto;
        mysqli_stmt_bind_param($stmtFind, "sss", $protoStr, $protoStr, $uuidStr);
        mysqli_stmt_execute($stmtFind);
        $resFind = mysqli_stmt_get_result($stmtFind);
        $rowFind = mysqli_fetch_assoc($resFind);
        mysqli_stmt_close($stmtFind);

        $jsonEncoded = json_encode($item, JSON_UNESCAPED_UNICODE);

        if ($rowFind) {
            $stmtUp = mysqli_prepare($link, "UPDATE ocorrencias SET uuid_remoto = ?, protocolo_vds = ?, oco_tipo = ?, bloco = IFNULL(?, bloco), unidade = IFNULL(?, unidade), status = ?, dados_json = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmtUp, "ssissssi", $uuidStr, $protoStr, $ocoTipo, $bloco, $unidade, $status, $jsonEncoded, $rowFind['id']);
            mysqli_stmt_execute($stmtUp);
            mysqli_stmt_close($stmtUp);
        } else {
            $stmtIns = mysqli_prepare($link, "INSERT INTO ocorrencias (abertura, bloco, unidade, status, uuid_remoto, protocolo_vds, oco_tipo, dados_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmtIns, "ssssssis", $abertura, $bloco, $unidade, $status, $uuidStr, $protoStr, $ocoTipo, $jsonEncoded);
            mysqli_stmt_execute($stmtIns);
            mysqli_stmt_close($stmtIns);
        }

        // Mapear unidade na vds_uuid_mapping se houver bloco e unidade
        if ($bloco && $unidade && !empty($item['unidade']['uuid'])) {
            $chave = strtoupper($bloco) . ":" . trim($unidade);
            $stmtMap = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto) VALUES ('unidade', ?, ?) ON DUPLICATE KEY UPDATE uuid_remoto = VALUES(uuid_remoto)");
            $uUuid = $item['unidade']['uuid'];
            mysqli_stmt_bind_param($stmtMap, "ss", $chave, $uUuid);
            mysqli_stmt_execute($stmtMap);
            mysqli_stmt_close($stmtMap);
        }

        $count++;
    }

    DBClose($link);
    return ['success' => true, 'count' => $count];
}

/**
 * Retorna os detalhes de uma ocorrência (histórico de eventos e notas internas).
 * Se a ocorrência legada não possui uuid_remoto, busca na VDS pelo protocolo para resolver o UUID e sincronizar.
 */
function vds_get_ocorrencia_detalhe($ocorrenciaId, $usuarioIdConselho = null) {
    $link = DBConnect();

    // 1. Buscar ocorrência local (por ID ou protocolo)
    $stmt = mysqli_prepare($link, "SELECT * FROM ocorrencias WHERE id = ? OR protocolo_vds = ? LIMIT 1");
    $ocoStr = (string)$ocorrenciaId;
    mysqli_stmt_bind_param($stmt, "is", $ocorrenciaId, $ocoStr);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ocorrencia = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$ocorrencia) {
        DBClose($link);
        return null;
    }

    $token = vds_get_token($usuarioIdConselho);
    $uuidRemoto = $ocorrencia['uuid_remoto'] ?? null;
    $protocolo = $ocorrencia['protocolo_vds'] ?? $ocorrencia['id'];

    // Se a ocorrência legada não possui uuid_remoto, busca na VDS pelo protocolo para resolver o UUID
    if (empty($uuidRemoto) && !empty($protocolo) && $token) {
        $urlProto = VDS_BASE_URL . '/ocorrencia?page=1&limit=20&sortBy=dtExibicao&order=desc&Lida=9&Caixa=0&Protocolo=' . urlencode($protocolo);
        $chProto = curl_init($urlProto);
        curl_setopt_array($chProto, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $respProto = curl_exec($chProto);
        $codeProto = curl_getinfo($chProto, CURLINFO_HTTP_CODE);
        curl_close($chProto);

        if ($codeProto === 200 && $respProto) {
            $dataProto = json_decode($respProto, true);
            $regsProto = $dataProto['regs'] ?? ($dataProto['items'] ?? []);
            if (!empty($regsProto[0]['uuid'])) {
                $uuidRemoto = $regsProto[0]['uuid'];
                $protocoloVds = $regsProto[0]['protocolo'] ?? $protocolo;
                $ocoTipo = (int)($regsProto[0]['tipoId'] ?? ($regsProto[0]['tipo'] ?? 115));
                $jsonEnc = json_encode($regsProto[0], JSON_UNESCAPED_UNICODE);

                // Atualiza ocorrência legada no banco com o UUID resolvido
                $stmtUp = mysqli_prepare($link, "UPDATE ocorrencias SET uuid_remoto = ?, protocolo_vds = ?, oco_tipo = ?, dados_json = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmtUp, "ssisi", $uuidRemoto, $protocoloVds, $ocoTipo, $jsonEnc, $ocorrencia['id']);
                mysqli_stmt_execute($stmtUp);
                mysqli_stmt_close($stmtUp);

                $ocorrencia['uuid_remoto'] = $uuidRemoto;
                $ocorrencia['protocolo_vds'] = $protocoloVds;
                $ocorrencia['oco_tipo'] = $ocoTipo;
            }
        }
    }

    // 2. Buscar Notas Internas locais do Conselho
    $stmtNotas = mysqli_prepare($link, "SELECT * FROM ocorrencia_notas_internas WHERE ocorrencia_id = ? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmtNotas, "i", $ocorrencia['id']);
    mysqli_stmt_execute($stmtNotas);
    $resNotas = mysqli_stmt_get_result($stmtNotas);
    $notasInternas = [];
    while ($n = mysqli_fetch_assoc($resNotas)) {
        $notasInternas[] = $n;
    }
    mysqli_stmt_close($stmtNotas);

    // 3. Buscar Tags de Unidades vinculadas
    $stmtTags = mysqli_prepare($link, "SELECT * FROM ocorrencia_unidade_tag WHERE ocorrencia_id = ?");
    mysqli_stmt_bind_param($stmtTags, "i", $ocorrencia['id']);
    mysqli_stmt_execute($stmtTags);
    $resTags = mysqli_stmt_get_result($stmtTags);
    $tagsUnidades = [];
    while ($t = mysqli_fetch_assoc($resTags)) {
        $tagsUnidades[] = $t;
    }
    mysqli_stmt_close($stmtTags);

    DBClose($link);

    // 4. Buscar os detalhes reais na API VDS via HTTP GET /ocorrencia/{uuid}
    $remoteData = null;

    if ($uuidRemoto && $token) {
        $ch = curl_init(VDS_BASE_URL . '/ocorrencia/' . urlencode($uuidRemoto));
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

        if ($httpCode === 200 && $response) {
            $remoteData = json_decode($response, true);
        }
    }

    return [
        'local' => $ocorrencia,
        'notasInternas' => $notasInternas,
        'tagsUnidades' => $tagsUnidades,
        'remoteData' => $remoteData
    ];
}

/**
 * Adiciona uma Nota Interna do Conselho (1º Fator - Salva localmente por padrão).
 */
function vds_adicionar_nota_interna($ocorrenciaId, $conselheiroId, $conselheiroNome, $texto, $anexoCaminho = null) {
    $link = DBConnect();

    // Obter protocolo
    $stmtOco = mysqli_prepare($link, "SELECT protocolo_vds FROM ocorrencias WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtOco, "i", $ocorrenciaId);
    mysqli_stmt_execute($stmtOco);
    $resOco = mysqli_stmt_get_result($stmtOco);
    $rowOco = mysqli_fetch_assoc($resOco);
    $protocolo = $rowOco['protocolo_vds'] ?? null;
    mysqli_stmt_close($stmtOco);

    $stmt = mysqli_prepare($link, "INSERT INTO ocorrencia_notas_internas (ocorrencia_id, protocolo_vds, conselheiro_id, conselheiro_nome, texto, anexo_caminho, enviado_remoto) VALUES (?, ?, ?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmt, "isisss", $ocorrenciaId, $protocolo, $conselheiroId, $conselheiroNome, $texto, $anexoCaminho);
    $success = mysqli_stmt_execute($stmt);
    $notaId = mysqli_insert_id($link);
    mysqli_stmt_close($stmt);

    DBClose($link);
    return ['success' => $success, 'notaId' => $notaId];
}

/**
 * Publica uma Nota Interna no Sistema Remoto VDS (2º Fator).
 * COM TRAVA DE SEGURANÇA: Permite postagem apenas no protocolo 259564 em modo de teste.
 */
function vds_publicar_nota_remoto($notaId, $usuarioIdConselho = null) {
    $link = DBConnect();

    $stmt = mysqli_prepare($link, "SELECT n.*, o.uuid_remoto FROM ocorrencia_notas_internas n JOIN ocorrencias o ON o.id = n.ocorrencia_id WHERE n.id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $notaId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $nota = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$nota) {
        DBClose($link);
        return ['success' => false, 'message' => 'Nota interna não encontrada.'];
    }

    // TRAVA DE SEGURANÇA PARA TESTES DE ESCRITA
    $protocolo = $nota['protocolo_vds'];
    if (VDS_TEST_MODE && $protocolo !== VDS_TEST_PROTOCOL) {
        DBClose($link);
        return [
            'success' => false,
            'blockedByLock' => true,
            'message' => 'Modo de Teste Ativo: A publicação remota está restrita EXCLUSIVAMENTE ao Protocolo ' . VDS_TEST_PROTOCOL . '. (Protocolo atual: ' . ($protocolo ?? 'Desconhecido') . ').'
        ];
    }

    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        DBClose($link);
        return ['success' => false, 'message' => 'Nenhum token ativo para publicar no remoto.'];
    }

    // Enviar mensagem/comentário para a API VDS (Endpoint: POST /ocorrencia com ocorrenciaPaiId ou ocorrenciaUuid)
    $payload = json_encode([
        'ocorrenciaPaiId' => (int)($nota['ocorrencia_id'] ?? 0),
        'ocorrenciaUuid' => $nota['uuid_remoto'],
        'mensagem' => $nota['texto']
    ]);

    $ch = curl_init(VDS_BASE_URL . '/ocorrencia');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
        $stmtUp = mysqli_prepare($link, "UPDATE ocorrencia_notas_internas SET enviado_remoto = 1, data_envio_remoto = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmtUp, "i", $notaId);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
        DBClose($link);
        return ['success' => true, 'message' => 'Nota publicada com sucesso no sistema remoto!'];
    }

    DBClose($link);
    return ['success' => false, 'httpCode' => $httpCode, 'message' => 'Erro ao enviar mensagem para a VDS (' . $httpCode . ').'];
}

/**
 * Upload de mídias/anexos na VDS.
 */
function vds_upload_midia($base64String, $usuarioIdConselho = null) {
    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        return ['success' => false, 'message' => 'Token indisponível para upload.'];
    }

    $payload = json_encode(['base64String' => $base64String]);

    $ch = curl_init(VDS_BASE_URL . '/upload');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        return ['success' => true, 'data' => json_decode($response, true)];
    }

    return ['success' => false, 'httpCode' => $httpCode, 'message' => 'Erro no upload de imagem/arquivo.'];
}

/**
 * Vincula uma unidade (Tag) a uma ocorrência.
 */
function vds_vincular_unidade_tag($ocorrenciaId, $bloco, $unidade, $tipoVinculo = 'citada') {
    $link = DBConnect();
    $stmtOco = mysqli_prepare($link, "SELECT protocolo_vds FROM ocorrencias WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtOco, "i", $ocorrenciaId);
    mysqli_stmt_execute($stmtOco);
    $resOco = mysqli_stmt_get_result($stmtOco);
    $rowOco = mysqli_fetch_assoc($resOco);
    $protocolo = $rowOco['protocolo_vds'] ?? null;
    mysqli_stmt_close($stmtOco);

    $stmt = mysqli_prepare($link, "INSERT INTO ocorrencia_unidade_tag (ocorrencia_id, protocolo_vds, bloco, unidade, tipo_vinculo) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", $ocorrenciaId, $protocolo, $bloco, $unidade, $tipoVinculo);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    DBClose($link);
    return ['success' => $success];
}
