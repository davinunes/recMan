<?php

include_once("/var/www/html/gmail/api.php");
include_once("/var/www/html/classes/repositorio.php");

$refresh_token = getLastRefreshTokenFromDatabase();

// dump($refresh_token);
// exit;

$tokenEndpoint = 'https://oauth2.googleapis.com/token';

// Parâmetros para a solicitação de token
$params = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'refresh_token' => $refresh_token,
    'grant_type' => 'refresh_token',
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
	// var_dump($tokenData);

    // Verificar se o token de acesso está presente na resposta
    if (isset($tokenData['access_token'])) {
        $accessToken = $tokenData['access_token'];
        $expires_in = $tokenData['expires_in'];
        $scope = $tokenData['scope'];
        $token_type = $tokenData['token_type'];
		include_once("/var/www/html/classes/repositorio.php");
		if(upsertGmailToken($accessToken, $expires_in, $scope, $token_type,"NULL")){
			// echo $accessToken;
			// header("Location: /index.php");
		}
        
    } else {
        echo '<div class="container"><span class="red-text">Precisa renovar token do Gmail</span></div>';
    }
}

// Fechar a sessão cURL
curl_close($ch);

?>