<?php
header('Content-Type: application/json; charset=utf-8');

/**
 * Encontra o objeto bruto na hierarquia do JSON.
 */
function encontrarNoCaminho($dados, $notacao) {
    if (!$dados || !isset($dados['artigos']) || !$notacao) {
        return null;
    }
    $partes = explode('.', $notacao);
    $artigoNumero = $partes[0];
    if (!isset($dados['artigos'][$artigoNumero])) {
        return null;
    }
    $resultado = $dados['artigos'][$artigoNumero];
    for ($i = 1; $i < count($partes); $i++) {
        $chave = $partes[$i];
        $proximoNivelEncontrado = false;
        $ordemDeBusca = ['incisos', 'paragrafos', 'alineas'];
        foreach ($ordemDeBusca as $subnivel) {
            if (isset($resultado[$subnivel]) && isset($resultado[$subnivel][$chave])) {
                $resultado = $resultado[$subnivel][$chave];
                $proximoNivelEncontrado = true;
                break;
            }
        }
        if (!$proximoNivelEncontrado) {
            return null;
        }
    }
    return $resultado;
}

/**
 * Processa seletores complexos como "[1-3,5]" ou "[a,c]".
 */
function parsearSelecaoComplexa($seletor) {
    $chavesFinais = [];
    $partes = explode(',', $seletor);
    foreach ($partes as $parte) {
        $parte = trim($parte);
        if (strpos($parte, '-') !== false) {
            list($inicio, $fim) = explode('-', $parte);
            $range = (is_numeric($inicio) && is_numeric($fim)) ? range((int)$inicio, (int)$fim) : range($inicio, $fim);
            foreach ($range as $item) {
                $chavesFinais[] = (string)$item;
            }
        } else {
            $chavesFinais[] = $parte;
        }
    }
    return $chavesFinais;
}


/**
 * NOVA FUNÇÃO DE FORMATAÇÃO: Gera o texto de um único item.
 * @param array $item - Um objeto de resultado individual.
 * @return string - O texto formatado para este item.
 */
function formatarItemComoTexto($item) {
    $textoItem = "Referência: {$item['notacao_pesquisada']}\n";

    $gerarTextoRecursivo = function($objeto, $nivel = 0) use (&$gerarTextoRecursivo) {
        $bloco = "";
        $indentacao = str_repeat("  ", $nivel);

        if (isset($objeto['titulo_artigo'])) {
             $bloco .= $indentacao . mb_strtoupper($objeto['titulo_artigo'], 'UTF-8') . "\n";
        }

        if (isset($objeto['texto'])) {
            $bloco .= $indentacao . $objeto['texto'] . "\n";
        }
        
        $subniveis = ['paragrafos', 'incisos', 'alineas'];
        foreach($subniveis as $subnivel) {
            if (isset($objeto[$subnivel])) {
                foreach ($objeto[$subnivel] as $num => $subObj) {
                    $label = "";
                    if ($subnivel === 'paragrafos') $label = ($num === 'unico') ? 'Parágrafo único:' : "§ {$num}°:";
                    if ($subnivel === 'incisos') $label = "Inciso {$num}:";
                    if ($subnivel === 'alineas') $label = "Alínea {$num}):";
                    
                    $bloco .= "\n" . $indentacao . $label . "\n";
                    $bloco .= $gerarTextoRecursivo($subObj, $nivel + 1);
                }
            }
        }
        return $bloco;
    };

    $textoItem .= $gerarTextoRecursivo($item['conteudo']);
    return $textoItem;
}

// --- LÓGICA PRINCIPAL DO ENDPOINT ---
try {
    $jsonString = file_get_contents('database.json');
    $database = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erro ao ler ou decodificar o arquivo database.json.');
    }

    if (!isset($_GET['notacao'])) {
        http_response_code(400);
        echo json_encode(['erro' => "Parâmetro 'notacao' é obrigatório."]);
        exit;
    }

    $notacaoCompleta = $_GET['notacao'];
    $formato = isset($_GET['formato']) ? $_GET['formato'] : 'estruturado';
    $respostaFinal = null;

    if (preg_match('/(.*)\.\s*\[(.*)\]$/', $notacaoCompleta, $matches)) {
        // Lógica de busca múltipla
        $caminhoPai = $matches[1];
        $seletor = $matches[2];
        $objetoPai = encontrarNoCaminho($database, $caminhoPai);

        if ($objetoPai === null) {
            http_response_code(404);
            echo json_encode(['erro' => "O caminho base \"{$caminhoPai}\" da notação não foi encontrado."]);
            exit;
        }

        $chavesParaBuscar = parsearSelecaoComplexa($seletor);
        $resultados = [];
        foreach ($chavesParaBuscar as $chave) {
            $itemEncontrado = null;
            $ordemDeBusca = ['incisos', 'paragrafos', 'alineas'];
            foreach ($ordemDeBusca as $subnivel) {
                if (isset($objetoPai[$subnivel]) && isset($objetoPai[$subnivel][$chave])) {
                    $itemEncontrado = $objetoPai[$subnivel][$chave];
                    break;
                }
            }
            if ($itemEncontrado) {
                $artigoNumero = explode('.', $caminhoPai)[0];
                $capituloInfo = ['numero' => $database['artigos'][$artigoNumero]['capitulo'], 'titulo' => $database['capitulos'][$database['artigos'][$artigoNumero]['capitulo']]];
                $resultados[] = ['notacao_pesquisada' => "{$caminhoPai}.{$chave}", 'capitulo' => $capituloInfo, 'conteudo' => $itemEncontrado];
            }
        }
        $respostaFinal = $resultados;
    } else {
        // Lógica de busca simples
        $resultadoUnico = encontrarNoCaminho($database, $notacaoCompleta);
        if ($resultadoUnico === null) {
            http_response_code(404);
            echo json_encode(['erro' => "A notação \"{$notacaoCompleta}\" não foi encontrada."]);
            exit;
        }
        $artigoNumero = explode('.', $notacaoCompleta)[0];
        $capituloInfo = ['numero' => $database['artigos'][$artigoNumero]['capitulo'], 'titulo' => $database['capitulos'][$database['artigos'][$artigoNumero]['capitulo']]];
        $respostaFinal = [['notacao_pesquisada' => $notacaoCompleta, 'capitulo' => $capituloInfo, 'conteudo' => $resultadoUnico]];
    }

    // LÓGICA DE FORMATAÇÃO ATUALIZADA
    if ($formato === 'texto') {
        $textoConcatenado = ""; #Fundamentação com base no Regimento Interno:\n\n
        $agrupadoPorCapitulo = [];

        // Agrupa todos os resultados pelo número do capítulo
        foreach ($respostaFinal as $res) {
            $agrupadoPorCapitulo[$res['capitulo']['numero']][] = $res;
        }
        ksort($agrupadoPorCapitulo); // Garante a ordem dos capítulos

        // Itera sobre os grupos para gerar o texto
        foreach ($agrupadoPorCapitulo as $numCap => $itens) {
            $textoConcatenado .= "Capítulo {$numCap}: {$itens[0]['capitulo']['titulo']}\n";
            $textoConcatenado .= "\n"; #----------------------------------------
            foreach ($itens as $item) {
                $textoConcatenado .= formatarItemComoTexto($item) . "\n";
            }
            $textoConcatenado .= "\n";
        }
        $respostaFinal = ['texto_formatado' => trim($textoConcatenado)];
    } else {
        // Se a busca era simples, retorna um objeto, não um array de um item.
        if (count($respostaFinal) === 1 && !preg_match('/\[.*\]/', $notacaoCompleta)) {
            $respostaFinal = $respostaFinal[0];
        }
    }

    echo json_encode($respostaFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Ocorreu um erro interno no servidor.', 'detalhes' => $e->getMessage()]);
}
?>