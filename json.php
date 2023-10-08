<?php
// Caminho para o arquivo JSON
$filePath = 'plan.json';

// Lê o conteúdo do arquivo JSON em uma string
$jsonData = file_get_contents($filePath);


// Converte a string JSON em um array associativo (definido como true no segundo argumento)
$dataArray = json_decode($jsonData, true);

// Verifique se a decodificação foi bem-sucedida
if ($dataArray !== null) {
    // Agora você pode usar os dados contidos no array $dataArray
    print_r($dataArray);
} else {
    echo "Erro ao decodificar o JSON.";
}
?>
