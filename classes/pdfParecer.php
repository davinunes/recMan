<?php

function getParecerPdf($data) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://127.0.0.1:5000/gerar_pdf?base64=true',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}

// Exemplo de uso com os dados fornecidos no seu exemplo
// $dados = array(
    // "notificacao" => "186/2023",
    // "unidade" => "A1305",
    // "assunto" => "ESTACIONAMENTO INDEVIDO",
    // "fato" => "Descrição do fato...",
    // "resultado" => "Conclusão...",
    // "parecer" => "Favorável"
// );

// $respostaCurl = fazerRequisicaoCurl($dados);

// echo $respostaCurl;

?>