<?php

include("api.php");
$redirectUri = 'https://mini.davinunes.eti.br/gmail/callback.php';
$code = $_GET['code'];

// URL do endpoint de token
$tokenEndpoint = 'https://oauth2.googleapis.com/token';

// Parâmetros para a solicitação de token
$params = [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
];

// Configuração da solicitação cURL
$ch = curl_init($tokenEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

// Executar a solicitação cURL
$response = curl_exec($ch);

// Verificar se houve algum erro
if (curl_errno($ch)) {
    echo 'Erro ao obter token: ' . curl_error($ch);
} else {
    // Decodificar a resposta JSON
    $tokenData = json_decode($response, true);
	var_dump($tokenData);

    // Verificar se o token de acesso está presente na resposta
    if (isset($tokenData['access_token'])) {
        $accessToken = $tokenData['access_token'];
        $expires_in = $tokenData['expires_in'];
        $scope = $tokenData['scope'];
        $token_type = $tokenData['token_type'];
		include("../classes/repositorio.php");
		if(upsertGmailToken($accessToken, $expires_in, $scope, $token_type)){
			echo $accessToken;
			// header("Location: ../index.php");
		}
        
    } else {
        echo 'Erro ao obter token: ' . print_r($tokenData, true);
    }
}

// Fechar a sessão cURL
curl_close($ch);




?>