<?php
// Define o cabeçalho da resposta como JSON.
header('Content-Type: application/json; charset=utf-8');

/**
 * Função de busca por notação (sem alteração).
 * @param array $dados O array associativo completo do banco de dados.
 * @param string $notacao A notação a ser buscada (ex: "6.20.a").
 * @return array O objeto encontrado ou um array de erro.
 */
function buscarPorNotacao($dados, $notacao) {
    if (!$dados || !isset($dados['artigos']) || !$notacao) {
        return ['erro' => 'Dados ou notação inválida.'];
    }
    $partes = explode('.', $notacao);
    $artigoNumero = $partes[0];
    if (!isset($dados['artigos'][$artigoNumero])) {
        return ['erro' => "Artigo \"{$artigoNumero}\" não encontrado."];
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
            return ['erro' => "Hierarquia \"{$notacao}\" inválida."];
        }
    }
    $capituloInfo = [
        'numero' => $dados['artigos'][$artigoNumero]['capitulo'],
        'titulo' => $dados['capitulos'][$dados['artigos'][$artigoNumero]['capitulo']]
    ];
    return [
        'notacao_pesquisada' => $notacao,
        'capitulo' => $capituloInfo,
        'conteudo' => $resultado
    ];
}

/**
 * NOVA FUNÇÃO: Formata o resultado estruturado em um texto único e legível.
 * @param array $resultado - O resultado da função buscarPorNotacao.
 * @return string - O texto formatado.
 */
function formatarResultadoComoTexto($resultado) {
    $textoFinal = "Fundamentação com base no Regimento Interno:\n\n";
    $textoFinal .= "Referência: {$resultado['notacao_pesquisada']}\n";
    $textoFinal .= "Capítulo {$resultado['capitulo']['numero']}: {$resultado['capitulo']['titulo']}\n";
    $textoFinal .= "----------------------------------------\n";

    // Função interna e recursiva para varrer o conteúdo
    $gerarTextoRecursivo = function($objeto, $nivel = 0) use (&$gerarTextoRecursivo) {
        $textoBloco = "";
        $indentacao = str_repeat("  ", $nivel); // Adiciona 2 espaços por nível de profundidade

        if (isset($objeto['texto'])) {
            $textoBloco .= $indentacao . $objeto['texto'] . "\n";
        }

        if (isset($objeto['paragrafos'])) {
            foreach ($objeto['paragrafos'] as $num => $p) {
                $label = ($num === 'unico') ? 'Parágrafo único:' : "§ {$num}°:";
                $textoBloco .= "\n" . $indentacao . $label . "\n";
                $textoBloco .= $gerarTextoRecursivo($p, $nivel + 1);
            }
        }
        if (isset($objeto['incisos'])) {
            foreach ($objeto['incisos'] as $num => $i) {
                $textoBloco .= "\n" . $indentacao . "Inciso {$num}:\n";
                $textoBloco .= $gerarTextoRecursivo($i, $nivel + 1);
            }
        }
        if (isset($objeto['alineas'])) {
            foreach ($objeto['alineas'] as $letra => $a) {
                $textoBloco .= "\n" . $indentacao . "Alínea {$letra}):\n";
                $textoBloco .= $gerarTextoRecursivo($a, $nivel + 1);
            }
        }
        return $textoBloco;
    };

    $textoFinal .= $gerarTextoRecursivo($resultado['conteudo']);
    return $textoFinal;
}


// --- LÓGICA PRINCIPAL DO ENDPOINT ---

try {
    $jsonString = file_get_contents('database.json');
    $database = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erro ao ler ou decodificar o arquivo database.json.');
    }

    if (!isset($_GET['notacao'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['erro' => "Parâmetro 'notacao' é obrigatório."]);
        exit;
    }
    
    $notacao = $_GET['notacao'];
    // NOVO: Verifica o parâmetro de formato. O padrão é 'estruturado'.
    $formato = isset($_GET['formato']) ? $_GET['formato'] : 'estruturado';

    $resultado = buscarPorNotacao($database, $notacao);
    
    if (isset($resultado['conteudo']['erro']) || isset($resultado['erro'])) {
        http_response_code(404); // Not Found
        echo json_encode($resultado);
        exit;
    }
    
    // NOVO: Decide o que responder com base no formato solicitado
    if ($formato === 'texto') {
        $textoFormatado = formatarResultadoComoTexto($resultado);
        $respostaFinal = ['texto_formatado' => $textoFormatado];
    } else {
        $respostaFinal = $resultado;
    }
    
    // Envia a resposta final
    echo json_encode($respostaFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['erro' => 'Ocorreu um erro interno no servidor.', 'detalhes' => $e->getMessage()]);
}

?>