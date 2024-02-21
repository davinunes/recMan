<?php

include_once("/var/www/html/classes/repositorio.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['conteudo_arquivo']) && !empty($_POST['conteudo_arquivo'])) {
        $conteudo_arquivo = $_POST['conteudo_arquivo'];
        $semData = array();

        // Dividir o conteúdo em blocos usando "bloco:" como termo
        $blocos = strtolower($conteudo_arquivo);
        $blocos = explode('bloco', $blocos);
        // dump($blocos);

        // Processar cada bloco
        foreach ($blocos as $bloco) {
            processarBloco($bloco);
        }

        // dump($semData);

        echo "Conteúdo processado com sucesso!";
		exit;
    } else {
        // echo "Erro no envio do arquivo.";
		// exit;
    }
} else {
    echo '
    <form action="datasRetirada.php" method="post">
        <label for="conteudo_arquivo">Cole o conteúdo do e-mail enviado pela ADM do A:</label><br>
        <textarea name="conteudo_arquivo" id="conteudo_arquivo" rows="10" cols="30" required></textarea>
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
        return $dataFormatada->format('d/m/Y');
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

<?php
if(isset($_POST['processar']) && $_POST['processar'] === 'sim') {
	
    // Obtém o JSON enviado via POST
    $jsonData = $_POST['jsonData'];
    
    // Converte o JSON em um array associativo
    $dataArray = json_decode($jsonData, true);

    // Verifique se a decodificação foi bem-sucedida
    if ($dataArray !== null) {
        // Agora você pode usar os dados contidos no array $dataArray
        foreach ($dataArray as $linha) {
            $linha["ano"] = explode("/", $linha["notificacao"])[1];
            $linha["notificacao"] = explode("/", $linha["notificacao"])[0];
			$linha["dia_retirada"] = explode(" ", $linha["dia_retirada"])[0];
            // var_dump($linha);
            upsertDatasDeRetirada($linha);
        }
        echo "JSON processado com sucesso!";
    } else {
        echo "Erro ao decodificar o JSON recebido via POST.";
    }
} else {
?>


	<!DOCTYPE html>
	<html>
	<head>
		<title>Conversor JSON</title>
		<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	</head>
	<body>
		<h1>Conversor JSON</h1>
		<a href="https://products.aspose.app/cells/conversion/xlsx-to-json">Conversor de XLS em Json</a><br/>
		<label for="json2">Cole o JSON no segundo formato:</label><br>
		<textarea id="json2" rows="10" cols="50"></textarea><br>
		<button id="converter">Converter</button>
		
		<div id="resultado" style="display: none;">
			<h2>JSON no primeiro formato:</h2>
			<button id="processar">Processar</button>
			<pre id="json1"></pre>
		</div>

	<script>
		var dataArray1; // Declarando a variável dataArray1 global
		$(document).ready(function() {
$("#converter").click(function () {
    // Obtém o JSON do segundo formato do campo de texto
    var json2 = $("#json2").val();

    // Converte o JSON do segundo formato em um array de objetos
    var dataArray2 = JSON.parse(json2);

    // Inicializa um array para armazenar os resultados
    dataArray1 = [];

    // Itera pelos objetos do array e realiza a conversão
    dataArray2.forEach(function (data2) {
		if(!data2["Retirada em"]){
			console.log("Sem Data Retirada");
			return;
		}
        let txt1 = data2["Unidade"];
		let info = data2["Info"];
        let match = getBlockAndNumber(txt1);

        if (match) {
            var data3 = {
                bloco: match[1],
                numero: match[2]
            };
        }
		
		  // Verificar se a chave "Código de rastreio" existe
		  if(!data2["Código de rastreio"]) {
			data2["Código de rastreio"] = data2["Info"];
			console.log("Sem Codigo");
			
		  } else {
			// Se não existir, usar o valor da chave "Info"
		  }

        var data1 = {
            "notificacao": extractNotificationNumber(data2["Código de rastreio"]),
            "bloco": data3.bloco,
            "apartamento": data3.numero,
            "dia_retirada": data2["Retirada em"],
            "obs": data2["Retirada por"]
        };

        dataArray1.push(data1);
    });

    // Exibe o resultado na página
    $("#json1").text(JSON.stringify(dataArray1, null, 2));
    $("#resultado").show();
});
// Função para extrair a data sem a hora
function extractDateWithoutTime(dateTimeString) {
    // Converte a string de data/hora para um objeto Date
    let dateTime = new Date(dateTimeString);

    // Formata a data sem a hora
    let dateWithoutTime = dateTime.toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });

    return dateWithoutTime;
}

// Função para obter o número da notificação no formato "numero/numero"
function extractNotificationNumber(notificationCode) {
    // Remove letras e mantém apenas os números
    let numericPart = notificationCode.replace(/[^\d]/g, '');

    // Adiciona a barra entre os dois números
    let formattedNumber = numericPart.slice(0, numericPart.length / 2) + '/' + numericPart.slice(numericPart.length / 2);

    return formattedNumber;
}
function getBlockAndNumber(str) {
    const regex = /Bloco\s+([A-Z])\s+-\s+Unidade\s+(\d+)/i;
    return str.match(regex);
}

			$("#processar").click(function() {
				// Realize uma solicitação AJAX para enviar os dados transformados para json.php
				$.ajax({
					type: "POST",
					url: "datasRetirada.php",
					data: { processar: "sim", jsonData: JSON.stringify(dataArray1) },
					success: function(response) {
						// Faça algo com a resposta da página json.php, se necessário
						console.log(response);
						$("#json1").text(response);
					}
				});
			});
		});
		
	</script>

	</body>
	</html>


<?php
}
?>

