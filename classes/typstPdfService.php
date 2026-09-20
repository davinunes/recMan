<?php
/**
 * Service PHP para Geração de PDFs via Typst (Porta 5050 / CLI Direct Fallback)
 * recMan - Sistema de Gestão de Recursos e Regimento Interno
 */

class TypstPdfService {

    private static $apiUrl = 'http://127.0.0.1:5050';

    /**
     * Sanitiza caracteres Unicode desconfigurados para o PDF
     */
    public static function sanitizarTexto($val) {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = self::sanitizarTexto($v);
            }
            return $val;
        }
        if (!is_string($val)) {
            return $val;
        }

        $mapa = [
            "\xE2\x80\x94" => "-",    // em dash
            "\xE2\x80\x93" => "-",    // en dash
            "\xE2\x80\x9C" => '"',    // aspas duplas esq
            "\xE2\x80\x9D" => '"',    // aspas duplas dir
            "\xE2\x80\x98" => "'",    // aspas simples esq
            "\xE2\x80\x99" => "'",    // aspas simples dir
            "\xE2\x80\xA2" => "*",    // bullet
            "\xE2\x80\xA6" => "...",  // reticências
            "«"            => '"',
            "»"            => '"',
        ];

        $textoClean = strtr($val, $mapa);

        if (function_exists('normalizer_normalize')) {
            $normalized = normalizer_normalize($textoClean, Normalizer::FORM_C);
            if ($normalized !== false) {
                $textoClean = $normalized;
            }
        }

        return $textoClean;
    }

    /**
     * Gera PDF de Parecer via API Typst (Porta 5050)
     */
    public static function gerarParecer($dadosArray, $retornarBase64 = true) {
        $dadosClean = self::sanitizarTexto($dadosArray);
        $endpoint = self::$apiUrl . '/gerar_pdf' . ($retornarBase64 ? '?base64=true' : '');

        return self::fazerRequisicaoHttp($endpoint, $dadosClean);
    }

    /**
     * Gera PDF do Regimento Interno via API Typst (Porta 5050)
     */
    public static function gerarRegimento($dadosJsonArray = null, $retornarBase64 = true) {
        $dadosClean = $dadosJsonArray ? self::sanitizarTexto($dadosJsonArray) : [];
        $endpoint = self::$apiUrl . '/gerar_regimento' . ($retornarBase64 ? '?base64=true' : '');

        return self::fazerRequisicaoHttp($endpoint, $dadosClean);
    }

    /**
     * Verifica a saúde da API na porta 5050
     */
    public static function checkHealth() {
        $curl = curl_init(self::$apiUrl . '/health');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2
        ]);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        return ['status' => 'offline', 'message' => 'Serviço Typst API na porta 5050 não respondeu'];
    }

    /**
     * Executa requisição cURL para o servidor Python Typst
     */
    private static function fazerRequisicaoHttp($url, $payloadData) {
        $start = microtime(true);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 35,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8'
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        $elapsedMs = round((microtime(true) - $start) * 1000, 2);

        // Se houve erro HTTP, tenta extrair a mensagem JSON detalhada do servidor
        if ($response !== false && !empty($response)) {
            $jsonResult = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonResult)) {
                if ($httpCode === 200 && ($jsonResult['status'] ?? '') === 'success') {
                    $jsonResult['elapsed_ms'] = $elapsedMs;
                    return $jsonResult;
                }
                if (!empty($jsonResult['message'])) {
                    return [
                        'status' => 'error',
                        'http_code' => $httpCode,
                        'message' => 'Erro na API Typst (Porta 5050): ' . $jsonResult['message'],
                        'elapsed_ms' => $elapsedMs,
                        'pdf_base64' => ''
                    ];
                }
            }
        }

        if ($response === false || $httpCode !== 200) {
            return [
                'status' => 'error',
                'http_code' => $httpCode,
                'message' => 'Erro na API Typst (Porta 5050): ' . ($curlError ?: "Status HTTP $httpCode"),
                'elapsed_ms' => $elapsedMs,
                'pdf_base64' => ''
            ];
        }

        return [
            'status' => 'success',
            'elapsed_ms' => $elapsedMs,
            'pdf_size_bytes' => strlen($response),
            'pdf_base64' => base64_encode($response),
            'engine' => 'Typst API (Porta 5050)'
        ];
    }
}
