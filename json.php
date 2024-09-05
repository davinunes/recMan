<?php
    if(isset($_POST['dataFormat'])){
		$dataFormat = $_POST['dataFormat'];
	}else{
		$dataFormat = "d/m/Y";
	}
	require("classes/repositorio.php");
		
if(isset($_POST['processar']) && $_POST['processar'] === 'sim') {
	
    // Obtém o JSON enviado via POST
    $jsonData = $_POST['jsonData'];
    
    // Converte o JSON em um array associativo
    $dataArray = json_decode($jsonData, true);

    // Verifique se a decodificação foi bem-sucedida
    if ($dataArray !== null) {
        // Agora você pode usar os dados contidos no array $dataArray
        foreach ($dataArray as $linha) {
			$linha["ano"] = "20" . substr(explode(".", $linha["numero"])[1], 0, 2);
            $linha["numero"] = explode(".", $linha["numero"])[0];
            $linha["numero"] = intval($linha["numero"]);
            $linha["unidade"] = intval($linha["unidade"]);
            $linha["data_email"] = date("Y-m-d", DateTime::createFromFormat($dataFormat, $linha["data_email"])->getTimestamp());
			$linha["data_envio"] = date("Y-m-d", DateTime::createFromFormat($dataFormat, $linha["data_envio"])->getTimestamp());
			$linha["data_ocorrido"] = date("Y-m-d", DateTime::createFromFormat($dataFormat, $linha["data_ocorrido"])->getTimestamp());

            $linha["torre"] = preg_replace('/[^A-F]/', '', $linha["torre"]);
            // var_dump($linha);
            upsertNotificacao($linha);
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
		<label for="json3">Verifique se as datas ficaram no formato certo:</label><br>
		<input id="json3" type="text" value="m/d/Y"></input><br>
		<button id="converter">Converter</button>
		
		<div id="resultado" style="display: none;">
			<h2>JSON no primeiro formato:</h2>
			<button id="processar">Processar</button>
			<pre id="json1"></pre>
		</div>

	<script>
		var dataArray1; // Declarando a variável dataArray1 global
		$(document).ready(function() {
			$("#importar_magnacom").click(function() {
				// Obtém a URL atual
				var currentUrl = window.location.href;

				// Verifica se já existe um "?" na URL
				if (currentUrl.indexOf('?') === -1) {
					// Se não houver, adiciona o parâmetro
					window.location.href = currentUrl + "?importar_magnacom=1";
				} else {
					// Se já houver outros parâmetros, adiciona o novo parâmetro com "&"
					window.location.href = currentUrl + "&importar_magnacom=1";
				}
			});

			$("#converter").click(function() {
				// Obtém o JSON do segundo formato do campo de texto
				var json2 = $("#json2").val();
				
				// Converte o JSON do segundo formato em um array de objetos
				var dataArray2 = JSON.parse(json2);
				
				// Inicializa um array para armazenar os resultados
				dataArray1 = [];
				
				var firstIteration = true; // Variável de controle para a primeira iteração

				// Itera pelos objetos do array e realiza a conversão
				dataArray2.forEach(function(data2) {
					
					if (firstIteration) {
						firstIteration = false;
						return; // Pula a primeira iteração
					}
				   var data1 = {
						"numero": data2["NOTIFICAÇÕES MIAMI BEACH - 2023"],
						"torre": data2["Column2"],
						"unidade": data2["Column3"],
						"data_email": data2["Column4"],
						"data_envio": data2["Column5"],
						"data_ocorrido": data2["Column6"],
						"status": data2["Column7"],
						"assunto": data2["Column8"],
						"notificacao": data2["Column9"],
						"cobranca": data2["Column10"],
						"obs": data2["Column11"]
					};
					
					dataArray1.push(data1);
				});
				
				// Exibe o resultado na página
				$("#json1").text(JSON.stringify(dataArray1, null, 2));
				$("#resultado").show();
			});

			$("#processar").click(function() {
				// Realize uma solicitação AJAX para enviar os dados transformados para json.php
				$.ajax({
					type: "POST",
					url: "json.php",
					data: { processar: "sim", jsonData: JSON.stringify(dataArray1), dataFormat:$("#json3").val() },
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


<?php
function fetchJsonFromUrl($url) {
    // Realiza a requisição HTTP
    $response = file_get_contents($url);

    // Verifica se houve algum erro na requisição
    if ($response === false) {
        return null; // Retorna null em caso de erro
    }

    // Converte o JSON recebido para um array associativo do PHP
    $json = json_decode($response, true);

    // Verifica se houve algum erro na decodificação do JSON
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
        return null; // Retorna null em caso de erro
    }

    return $json; // Retorna o JSON como array associativo do PHP
}

// Exemplo de uso da função
$url = 'https://opensheet.elk.sh/19g62-RqOPWkbzDcloTFB4D7wh4ft_iL-H_zi2ym9-CI/clone';
$jsonData = fetchJsonFromUrl($url);

// Verifica se houve algum erro ao buscar o JSON
if(!$_GET['importar_magnacom']){
	echo "<button id='importar_magnacom'>Importar da Magnacom</button>";
	exit;
}
if ($jsonData === null) {
    echo "Erro ao obter o JSON da URL.";
} else {
    foreach ($jsonData as $row) {
        $linha = []; // Inicializa o array vazio a cada iteração
        
        // Verifica e seta o valor de 'ano'
        if (isset($row['Nº'])) {
            $numeroParts = explode("/", $row['Nº']);
            if (isset($numeroParts[1])) {
                $linha["ano"] = "20" . substr($numeroParts[1], 0, 2);
            }
            $linha["numero"] = isset($numeroParts[0]) ? intval(preg_replace('/\D/', '', $numeroParts[0])) : null;
        }

        // Verifica e seta o valor de 'unidade'
        if (isset($row["APTO."])) {
            $linha["unidade"] = intval($row["APTO."]);
        }

        // Verifica e seta o valor de 'notificacao'
        if (isset($row["NOTIFICAÇÃO"])) {
            $linha["notificacao"] = $row["NOTIFICAÇÃO"];
        }

        // Verifica e seta o valor de 'assunto'
        if (isset($row["MOTIVO"])) {
            $linha["assunto"] = $row["MOTIVO"];
        }

        // Verifica e seta o valor de 'cobranca'
        if (isset($row["COBRANÇA"]) or isset($row["VALOR DA PENALIDADE"])) {
            @$linha["cobranca"] = $row["COBRANÇA"] . " - " . $row["VALOR DA PENALIDADE"];
        }

        // Verifica e seta a data do 'data_email'
		$date2 = isset($row["ENVIO"]) ? DateTime::createFromFormat($dataFormat, $row["ENVIO"]) : false;
        if ($date2 !== false) {
            $linha["data_email"] = date("Y-m-d", $date2->getTimestamp());
        }

        // Verifica e seta a data do 'data_ocorrido'
		@$date = DateTime::createFromFormat($dataFormat, $row["OCORRIDO"]);

		if ($date !== false) {
			$linha["data_ocorrido"] = date("Y-m-d", $date->getTimestamp());
		}


        // Verifica e seta o valor de 'torre'
        if (isset($row["TORRE"])) {
            $linha["torre"] = preg_replace('/[^A-F]/', '', $row["TORRE"]);
        }
		
		if(sizeof($linha) > 2){
			upsertNotificacao($linha);
			// var_dump($linha);
			
		}
        echo "<br>";

        // Verifica e seta os valores para 'dados'
        if (isset($linha["numero"]) && isset($linha["ano"]) && isset($row["RECEBIMENTO FISICO"]) && isset($linha["torre"]) && isset($linha["unidade"])) {
            $dados['notificacao'] = $linha["numero"];
            $dados['ano'] = $linha["ano"];
            $dados['dia_retirada'] = $row["RECEBIMENTO FISICO"];
            $dados['bloco'] = $linha["torre"];
            $dados['apartamento'] = $linha["unidade"];
            $dados['obs'] = isset($linha["cobranca"]) ? $linha["cobranca"] : '';
			
			if(strlen($dados['dia_retirada']) == 10){
				
				upsertDatasDeRetirada($dados);
				// var_dump($dados);
				// echo "<br>";
			}

        }
    }
}

?>


<?php
echo "<br>Importando da Magnacom";


?>