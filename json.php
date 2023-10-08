<?php
// Caminho para o arquivo JSON
$filePath = 'plan.json';
require("classes/repositorio.php");

// Lê o conteúdo do arquivo JSON em uma string
$jsonData = file_get_contents($filePath);


// Converte a string JSON em um array associativo (definido como true no segundo argumento)
$dataArray = json_decode($jsonData, true);

// Verifique se a decodificação foi bem-sucedida
if ($dataArray !== null) {
    // Agora você pode usar os dados contidos no array $dataArray
    foreach($dataArray as $linha){
		$linha["numero"] = explode(".", $linha["numero"])[0];
		$linha["numero"] = intval($linha["numero"]);
		$linha["ano"] = "2023";
		$linha["unidade"] = intval($linha["unidade"]);
		$linha["data_email"] = date("Y-m-d", strtotime($linha["data_email"]));;
		$linha["data_envio"] = date("Y-m-d", strtotime($linha["data_envio"]));;
		$linha["data_ocorrido"] = date("Y-m-d", strtotime($linha["data_ocorrido"]));;
		$linha["torre"] = preg_replace('/[^A-F]/', '', $linha["torre"]);;
		// var_dump($linha);
		upsertNotificacao($linha);
	}
} else {
    echo "Erro ao decodificar o JSON.";
}
?>
