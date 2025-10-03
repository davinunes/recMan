<?php
header('Content-Type: application/json; charset=utf-8');

// --- Funções Auxiliares (sem alterações) ---

function encontrarNoCaminho($dados, $notacao) {
    if (!$dados || !isset($dados['artigos']) || !$notacao) return null;
    $partes = explode('.', strtolower($notacao));
    $artigoNumero = $partes[0];
    if (!isset($dados['artigos'][$artigoNumero])) return null;
    $resultado = $dados['artigos'][$artigoNumero];
    for ($i = 1; $i < count($partes); $i++) {
        $parteDoCaminho = $partes[$i];
        $proximoNivelEncontrado = false;
        if (preg_match('/^([pia])(.+)$/', $parteDoCaminho, $matches)) {
            $tipo = $matches[1]; $chave = $matches[2];
            $mapaTipos = ['p' => 'paragrafos', 'i' => 'incisos', 'a' => 'alineas'];
            $subnivelAlvo = $mapaTipos[$tipo];
            if (isset($resultado[$subnivelAlvo]) && isset($resultado[$subnivelAlvo][$chave])) {
                $resultado = $resultado[$subnivelAlvo][$chave];
                $proximoNivelEncontrado = true;
            }
        } else {
            $chave = $parteDoCaminho;
            $ordemDeBusca = ['incisos', 'paragrafos', 'alineas'];
            foreach ($ordemDeBusca as $subnivel) {
                if (isset($resultado[$subnivel]) && isset($resultado[$subnivel][$chave])) {
                    $resultado = $resultado[$subnivel][$chave];
                    $proximoNivelEncontrado = true;
                    break;
                }
            }
        }
        if (!$proximoNivelEncontrado) return null;
    }
    return $resultado;
}

function parsearSelecaoComplexa($seletor) {
    $chavesFinais = [];
    $partes = explode(',', $seletor);
    foreach ($partes as $parte) {
        $parte = trim($parte);
        if (strpos($parte, '-') !== false) {
            list($inicio, $fim) = explode('-', $parte);
            $range = (is_numeric($inicio) && is_numeric($fim)) ? range((int)$inicio, (int)$fim) : range($inicio, $fim);
            foreach ($range as $item) { $chavesFinais[] = (string)$item; }
        } else { $chavesFinais[] = $parte; }
    }
    return $chavesFinais;
}

function formatarItemComoTexto($item) {
    $textoItem = "Referência: {$item['notacao_pesquisada']}\n";
    $gerarTextoRecursivo = function($objeto, $nivel = 0) use (&$gerarTextoRecursivo) {
        $bloco = "";
        $indentacao = str_repeat("  ", $nivel);
        if (isset($objeto['titulo_artigo'])) { $bloco .= $indentacao . mb_strtoupper($objeto['titulo_artigo'], 'UTF-8') . "\n"; }
        if (isset($objeto['texto'])) { $bloco .= $indentacao . $objeto['texto'] . "\n"; }
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


// --- LÓGICA PRINCIPAL DO ENDPOINT (TOTALMENTE REESTRUTURADA) ---
try {
    $jsonString = file_get_contents('database.json');
    $database = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) { throw new Exception('Erro ao ler ou decodificar o arquivo database.json.'); }

    if (!isset($_GET['notacao'])) {
        http_response_code(400);
        echo json_encode(['erro' => "Parâmetro 'notacao' é obrigatório."]);
        exit;
    }

    $notacaoCompleta = $_GET['notacao'];
    $formato = isset($_GET['formato']) ? $_GET['formato'] : 'estruturado';
    $respostaFinal = [];

    // 1. Divide a consulta em uma lista de tarefas, separadas por vírgula.
    $tarefasDeBusca = explode(',', $notacaoCompleta);

    // 2. Itera sobre cada tarefa.
    foreach ($tarefasDeBusca as $tarefa) {
        $tarefa = trim($tarefa);
        if (empty($tarefa)) continue;

        // 3. Para cada tarefa, verifica se é complexa (com colchetes) ou simples.
        if (preg_match('/(.*)\.\s*\[(.*)\]$/', $tarefa, $matches)) {
            // A tarefa é complexa (ex: "58.[10,7,9]")
            $caminhoPai = $matches[1];
            $seletor = $matches[2];
            $objetoPai = encontrarNoCaminho($database, $caminhoPai);

            if ($objetoPai) {
                $chavesParaBuscar = parsearSelecaoComplexa($seletor);
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
                        $respostaFinal[] = ['notacao_pesquisada' => "{$caminhoPai}.{$chave}", 'capitulo' => $capituloInfo, 'conteudo' => $itemEncontrado];
                    }
                }
            }
        } else {
            // A tarefa é simples (ex: "14.5")
            $itemEncontrado = encontrarNoCaminho($database, $tarefa);
            if ($itemEncontrado) {
                $artigoNumero = explode('.', $tarefa)[0];
                $capituloInfo = ['numero' => $database['artigos'][$artigoNumero]['capitulo'], 'titulo' => $database['capitulos'][$database['artigos'][$artigoNumero]['capitulo']]];
                $respostaFinal[] = ['notacao_pesquisada' => $tarefa, 'capitulo' => $capituloInfo, 'conteudo' => $itemEncontrado];
            }
        }
    }

    // 4. Se nenhum item foi encontrado em nenhuma das tarefas, retorna erro.
    if (empty($respostaFinal)) {
        http_response_code(404);
        echo json_encode(['erro' => "Nenhum item válido encontrado para a notação \"{$notacaoCompleta}\"."]);
        exit;
    }

    // 5. Formata a saída (já funciona com listas, então não precisa mudar)
    if ($formato === 'texto') {
        // ... (lógica de formatação de texto agrupada)
        $textoConcatenado = "Fundamentação com base no Regimento Interno:\n\n";
        $agrupadoPorCapitulo = [];
        foreach ($respostaFinal as $res) { $agrupadoPorCapitulo[$res['capitulo']['numero']][] = $res; }
        ksort($agrupadoPorCapitulo);
        foreach ($agrupadoPorCapitulo as $numCap => $itens) {
            $textoConcatenado .= "Capítulo {$numCap}: {$itens[0]['capitulo']['titulo']}\n----------------------------------------\n";
            foreach ($itens as $item) { $textoConcatenado .= formatarItemComoTexto($item) . "\n"; }
            $textoConcatenado .= "\n";
        }
        $respostaFinal = ['texto_formatado' => trim($textoConcatenado)];

    } else {
        // Se a busca original era para um único item simples, retorna um objeto em vez de uma lista com um item.
        if (count($respostaFinal) === 1 && strpos($notacaoCompleta, ',') === false && strpos($notacaoCompleta, '[') === false) {
            $respostaFinal = $respostaFinal[0];
        }
    }

    echo json_encode($respostaFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Ocorreu um erro interno no servidor.', 'detalhes' => $e->getMessage()]);
}
?>