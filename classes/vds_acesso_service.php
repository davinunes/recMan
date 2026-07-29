<?php

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/vds_auth_service.php";

/**
 * Consulta eventos de acesso da unidade na VDS para a janela temporal especificada.
 */
function vds_get_eventos_acesso($bloco, $unidade, $dtInicio, $dtFim, $usuarioIdConselho = null) {
    $token = vds_get_token($usuarioIdConselho);
    
    // Tentar obter UUIDs do bloco e da unidade
    $link = DBConnect();
    $chave = strtoupper($bloco) . ":" . trim($unidade);
    $stmt = mysqli_prepare($link, "SELECT uuid_remoto FROM vds_uuid_mapping WHERE entidade_tipo = 'unidade' AND chave_local = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $chave);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    $unidadeUuid = $row['uuid_remoto'] ?? null;
    mysqli_stmt_close($stmt);
    DBClose($link);

    if ($token && $unidadeUuid) {
        $url = VDS_BASE_URL . '/evento_acesso?page=1&limit=50&sortBy=dthora&order=desc'
             . '&dtInicio=' . urlencode($dtInicio)
             . '&dtFim=' . urlencode($dtFim)
             . '&unidade.uuid=' . urlencode($unidadeUuid);

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

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            return $json['regs'] ?? ($json['data'] ?? ($json['items'] ?? $json));
        }
    }

    // Fallback: Mock para testes offline
    $mockPath = __DIR__ . '/../docs/mocks/mock_evento_acesso.json';
    if (file_exists($mockPath)) {
        $mock = json_decode(file_get_contents($mockPath), true);
        return $mock['regs'] ?? ($mock['data'] ?? []);
    }

    return [];
}

/**
 * Consulta entregas/correspondências recentes da unidade.
 */
function vds_get_entregas_unidade($bloco, $unidade, $usuarioIdConselho = null) {
    $token = vds_get_token($usuarioIdConselho);
    if ($token) {
        $url = VDS_BASE_URL . '/entrega?page=1&limit=20&sortBy=dthora&order=desc';
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

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            return $json['regs'] ?? ($json['data'] ?? []);
        }
    }

    // Fallback Mock
    $mockPath = __DIR__ . '/../docs/mocks/mock_entrega.json';
    if (file_exists($mockPath)) {
        $mock = json_decode(file_get_contents($mockPath), true);
        return $mock['data'] ?? [];
    }

    return [];
}

/**
 * Busca todos os chamados onde a unidade é autora, reclamada ou citada.
 */
function vds_get_chamados_unidade($bloco, $unidade) {
    $link = DBConnect();
    
    $sql = "SELECT DISTINCT o.*, t.tipo_vinculo 
            FROM ocorrencias o
            LEFT JOIN ocorrencia_unidade_tag t ON t.ocorrencia_id = o.id
            WHERE (o.bloco = ? AND o.unidade = ?) 
               OR (t.bloco = ? AND t.unidade = ?)
            ORDER BY o.abertura DESC";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $bloco, $unidade, $bloco, $unidade);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $chamados = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $chamados[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    DBClose($link);

    return $chamados;
}
