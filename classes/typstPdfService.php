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
            "\xE2\x80\xA6" => "...",  // reticências
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
     * Converte código HTML (do editor WYSIWYG) em marcação Typst limpa
     */
    public static function htmlToTypst($html) {
        if (empty($html)) return "";
        
        $str = $html;
        $str = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', "\n= $1\n", $str);
        $str = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', "\n== $1\n", $str);
        $str = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', "\n=== $1\n", $str);
        $str = preg_replace('/<h4[^>]*>(.*?)<\/h4>/is', "\n==== $1\n", $str);

        $str = preg_replace('/<strong[^>]*>(.*?)<\/strong>/is', '*$1*', $str);
        $str = preg_replace('/<b[^>]*>(.*?)<\/b>/is', '*$1*', $str);
        $str = preg_replace('/<em[^>]*>(.*?)<\/em>/is', '_$1_', $str);
        $str = preg_replace('/<i[^>]*>(.*?)<\/i>/is', '_$1_', $str);
        $str = preg_replace('/<u[^>]*>(.*?)<\/u>/is', '#underline[$1]', $str);

        $str = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "- $1\n", $str);
        $str = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "$1\n\n", $str);
        $str = preg_replace('/<br\s*\/?>/i', "\n", $str);
        $str = preg_replace('/<blockquote[^>]*>(.*?)<\/blockquote>/is', "\n#pad(left: 14pt)[_$1_]\n", $str);

        $str = strip_tags($str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($str);
    }

    /**
     * Converte marcações Markdown comuns em sintaxe válida Typst Markup
     * Evita erros como "unclosed delimiter" e "expected identifier"
     */
    public static function markdownToTypstMarkup($texto) {
        if (empty($texto) || !is_string($texto)) return "";

        $str = $texto;

        // 1. Extrai notas de rodapé Markdown estilo [^1]: texto explicativo
        $footnotes = [];
        if (preg_match_all('/^\[\^([a-zA-Z0-9_\-]+)\]:\s*(.+)$/m', $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $id = $m[1];
                $desc = trim($m[2]);
                $footnotes[$id] = $desc;
            }
            // Remove as definições de rodapé do corpo principal
            $str = preg_replace('/^\[\^([a-zA-Z0-9_\-]+)\]:\s*.+$/m', '', $str);
        }

        // Substitui chamadas [^1] por #footnote[desc]
        foreach ($footnotes as $id => $desc) {
            $descSanit = str_replace(['[', ']'], ['(', ')'], $desc);
            $str = str_replace("[^$id]", "#footnote[$descSanit]", $str);
        }

        // 2. Extrai referências numéricas finais do tipo [1] Texto da referência...
        $refNumericas = [];
        if (preg_match_all('/^\[([0-9]{1,3})\]\s+([^\n\r]+)$/m', $str, $matchesRef, PREG_SET_ORDER)) {
            foreach ($matchesRef as $m) {
                $id = $m[1];
                $desc = trim($m[2]);
                // Apenas se a descrição tiver tamanho significativo (evita falsos positivos)
                if (strlen($desc) > 8) {
                    $refNumericas[$id] = $desc;
                }
            }
            if (!empty($refNumericas)) {
                foreach ($refNumericas as $id => $desc) {
                    // Remove a linha de referência do final
                    $str = preg_replace('/^\[' . $id . '\]\s+.+$/m', '', $str);
                    $descSanit = str_replace(['[', ']'], ['(', ')'], $desc);
                    // Substitui a chamada [1] no texto por #footnote[desc]
                    $str = preg_replace('/\[(' . $id . ')\]/', '#footnote[' . $descSanit . ']', $str);
                }
            }
        }

        // 3. Escapa referências numéricas soltas sem definição (ex: [1], [4], [6][12])
        // No Typst, colchetes logo após parênteses ')...[1]' são lidos como indexação/chamada inválida
        $str = preg_replace('/(?<!\\\\)\[([0-9]{1,3})\]/', '\\[$1\\]', $str);

        // 4. Converte listas não-ordenadas do Markdown com asterisco (* Item) em hífen Typst (- Item)
        // Isso é crucial porque no Typst '*' no início da linha abre um bloco de negrito infinito!
        $str = preg_replace('/^(\s*)\*\s+(.+)$/m', '$1- $2', $str);

        // 5. Remove barras de escape desnecessárias do Markdown antes de pontos (ex: 1\. -> 1.)
        $str = preg_replace('/([0-9]+)\\\\\./', '$1.', $str);

        // 6. Títulos Markdown (# -> =, ## -> ==, ### -> ===, etc.)
        $str = preg_replace_callback('/^(#{1,6})\s+(.+)$/m', function($m) {
            $nivel = strlen($m[1]);
            $equals = str_repeat('=', $nivel);
            $titulo = trim($m[2]);
            return "\n$equals $titulo\n";
        }, $str);

        // 7. Limpa asteriscos redundantes nos títulos (ex: === *Título* -> === Título)
        $str = preg_replace('/^(={1,6})\s*\*(.+?)\*\s*$/m', '$1 $2', $str);

        // 8. Negrito duplo Markdown (**texto**) -> Negrito Typst (*texto*)
        $str = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $str);

        // 9. Escapa cifrões monetários (R$ 100,00 ou $ 50) para não abrir o modo matemático do Typst ($...$)
        // Um número ímpar de cifrões soltos gera fatalmente "error: unclosed delimiter"
        $str = preg_replace('/(?<!\\\\)R\$/', 'R\\$', $str);
        $str = preg_replace('/(?<!\\\\)\$(\s*[0-9])/', '\\$$1', $str);

        // 10. Linha divisória (--- ou ***)
        $str = preg_replace('/^(\-{3,}|\*{3,})\s*$/m', "\n#v(0.3cm)\n#line(length: 100%, stroke: 0.5pt + rgb(\"cbd5e1\"))\n#v(0.3cm)\n", $str);

        // 11. Links [texto](url) -> #link("url")[texto]
        $str = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/', '#link("$2")[$1]', $str);

        // 12. Citações Markdown (> texto) -> #pad(left: 15pt)[_texto_]
        $str = preg_replace('/^>\s*(.+)$/m', '#pad(left: 15pt)[_$1_]', $str);

        return trim($str);
    }

    /**
     * Gera PDF de Documento Oficial do Conselho (Orientação Técnica, Parecer Opinativo, etc.)
     */
    public static function gerarDocumentoOficial($dadosArray, $retornarBase64 = true) {
        $dadosClean = self::sanitizarTexto($dadosArray);
        
        if (($dadosClean['modo_editor'] ?? '') === 'visual' && !empty($dadosClean['conteudo'])) {
            $dadosClean['conteudo_typst'] = self::htmlToTypst($dadosClean['conteudo']);
        } else {
            // No modo split_code, pré-processa prevenindo quebras por Markdown acidental
            $conteudoOriginal = $dadosClean['conteudo'] ?? '';
            $dadosClean['conteudo_typst'] = self::markdownToTypstMarkup($conteudoOriginal);
        }

        $endpoint = self::$apiUrl . '/gerar_documento_oficial' . ($retornarBase64 ? '?base64=true' : '');
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
