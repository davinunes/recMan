<?php

include_once("/var/www/html/classes/repositorio.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
		$semData = array();

        // Ler o conteúdo do arquivo
        $conteudo_arquivo = file_get_contents($arquivo_tmp);

        // Dividir o conteúdo em blocos usando "bloco:" como termo
		$blocos = strtolower($conteudo_arquivo);
        $blocos = explode('bloco', $blocos);
		// dump($blocos);

        // Processar cada bloco
        foreach ($blocos as $bloco) {
            processarBloco($bloco);
        }
		
		// dump($semData);

        echo "Arquivo processado com sucesso!";
    } else {
        echo "Erro no envio do arquivo.";
    }
} else {
    echo '
    <form action="datasRetirada.php" method="post" enctype="multipart/form-data">
        <label for="arquivo">Selecione o arquivo TXT:</label>
        <input type="file" name="arquivo" id="arquivo" accept=".txt" required>
        <br>
        <input type="submit" value="Enviar">
    </form>
    ';
}

?>

<?php
// Função para processar e inserir os blocos
function processarBloco($bloco) {
	global $semData;
    // Converter tudo para lowercase e remover espaços extras e quebras de linha
    $blocoLimpo = strtolower(trim(preg_replace('/\s+/', ' ', $bloco)));

    // Identificar a letra do bloco
    preg_match('/\s*([a-z])\s*apa/', $blocoLimpo, $matches);
    if (!empty($matches[1])) {
        $torre = $matches[1];
    }

    // Obter o número do apartamento
    $ap = extrairApartamento($blocoLimpo);
	
	// Nova função para extrair a data
	$dia = extrairData($blocoLimpo);
	if(!$dia){
		$cartas = extrairCartas($blocoLimpo);
		$semData[] = $cartas;
		foreach($cartas as $carta){
			echo "$carta <br>";
		}
		return;
	}
	
	$cartas = extrairCartas($blocoLimpo);

    foreach($cartas as $carta){
		$carta = explode("/", $carta);
		$dados['notificacao'] = $carta[0];
		$dados['ano'] = $carta[1];
		$dados['dia_retirada'] = $dia;
		$dados['bloco'] = $torre;
		$dados['apartamento'] = $ap;
		$dados['obs'] = $blocoLimpo;
		
	}
	// dump($dados);
	upsertDatasDeRetirada($dados);
	
}

// Função para extrair dados específicos
function extrairDado($texto) {
    $dado = trim(str_replace(array(':', 'do condomínio'), '', $texto));
    return $dado;
}

// Nova função para extrair a data
function extrairData($blocoLimpo) {
    preg_match_all('/(\d{2}\/\d{2}\/\d{4})/', $blocoLimpo, $matches);
    $dia = !empty($matches[1]) ? $matches[1][0] : null;

    if ($dia) {
        $dataFormatada = DateTime::createFromFormat('d/m/Y', $dia);
        return $dataFormatada->format('Y-m-d');
    } else {
        return $dia;
    }
}


// Nova função para extrair a data
function extrairApartamento($blocoLimpo) {
	$b = explode("apa",$blocoLimpo)[1];
	$b = explode("nome",$b)[0];
	$b = explode(":",$b)[1];
    preg_match_all('/\d+/', $b, $matches);
	// dump($matches);
	// dump($matches[0][0]);
    return !empty($matches[0]) ? $matches[0][0] : null;
}

// Nova função para extrair a lista de cartas
function extrairCartas($blocoLimpo) {
    $cartas = array();
	
	$b = explode("carta",$blocoLimpo)[1];
	$b = explode("dia",$b)[0];
	$b = explode("data",$b)[0];
	// dump($b);
	
	preg_match_all('/(\d+\/\d+)/', $b, $b);
    
	// dump($b[0]);
	$cartas = $b[0];
    
    if(sizeof($cartas) > 0) return $cartas;
	return null;
}
?>
