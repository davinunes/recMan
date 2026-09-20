<?php

function sanitizarTextoParaPdf($val) {
    if (is_array($val)) {
        foreach ($val as $k => $v) {
            $val[$k] = sanitizarTextoParaPdf($v);
        }
        return $val;
    }
    if (!is_string($val)) {
        return $val;
    }

    // Mapa de substituição de caracteres Unicode tipográficos por equivalentes padrão
    $mapa = [
        "\xE2\x80\x94" => "-",    // — (em dash)
        "\xE2\x80\x93" => "-",    // – (en dash)
        "\xE2\x80\x9C" => '"',    // “ (aspas duplas esquerda)
        "\xE2\x80\x9D" => '"',    // ” (aspas duplas direita)
        "\xE2\x80\x98" => "'",    // ‘ (aspas simples esquerda)
        "\xE2\x80\x99" => "'",    // ’ (aspas simples direita/apóstrofe)
        "\xE2\x80\xA2" => "*",    // • (bullet point)
        "\xE2\x80\xA6" => "...",  // … (reticências)
        "«"            => '"',
        "»"            => '"',
        "–"            => "-",
        "—"            => "-",
        "“"            => '"',
        "”"            => '"',
        "’"            => "'",
        "‘"            => "'",
        "•"            => "*",
        "…"            => "..."
    ];

    $textoClean = strtr($val, $mapa);

    // Caso a extensão intl esteja habilitada, normaliza os caracteres
    if (function_exists('normalizer_normalize')) {
        $normalized = normalizer_normalize($textoClean, Normalizer::FORM_C);
        if ($normalized !== false) {
            $textoClean = $normalized;
        }
    }

    return $textoClean;
}

function getParecerPdf($data) {
    $dataClean = sanitizarTextoParaPdf($data);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://127.0.0.1:5000/gerar_pdf?base64=true',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($dataClean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json; charset=utf-8'
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);

    curl_close($curl);

    if ($response === false || $httpCode !== 200) {
        // Fallback em formato JSON de erro amigável se a API Python falhar
        return json_encode([
            'status' => 'error',
            'http_code' => $httpCode,
            'message' => 'Erro ao comunicar com o servidor de PDF Python: ' . ($curlError ?: "Status $httpCode"),
            'pdf_base64' => ''
        ]);
    }

    return $response;
}

// Exemplo de uso com os dados fornecidos no seu exemplo
// $dados = array(
    // "notificacao" => "186/2023",
    // "unidade" => "A1305",
    // "assunto" => "ESTACIONAMENTO INDEVIDO",
    // "fato" => "Descrição do fato...",
    // "resultado" => "Conclusão...",
    // "parecer" => "Favorável"
// );

// $respostaCurl = fazerRequisicaoCurl($dados);

// echo $respostaCurl;

?>