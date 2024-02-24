<?php
if(isset($_POST['processar']) && $_POST['processar'] === 'sim') {
	
	require("classes/repositorio.php");
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
            $linha["data_email"] = date("Y-m-d", DateTime::createFromFormat('d/m/Y', $linha["data_email"])->getTimestamp());
			$linha["data_envio"] = date("Y-m-d", DateTime::createFromFormat('d/m/Y', $linha["data_envio"])->getTimestamp());
			$linha["data_ocorrido"] = date("Y-m-d", DateTime::createFromFormat('d/m/Y', $linha["data_ocorrido"])->getTimestamp());

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
		<button id="converter">Converter</button>
		
		<div id="resultado" style="display: none;">
			<h2>JSON no primeiro formato:</h2>
			<button id="processar">Processar</button>
			<pre id="json1"></pre>
		</div>

	<script>
		var dataArray1; // Declarando a variável dataArray1 global
		$(document).ready(function() {
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

