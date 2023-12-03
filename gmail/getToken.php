<?php

include("api.json");
$redirectUri = 'https://mini.davinunes.eti.br/gmail/callback.php';

// URL do endpoint de autorização
$authorizationEndpoint = 'https://accounts.google.com/o/oauth2/auth';

// Parâmetros para a solicitação de autorização
$authorizationParams = [
    'response_type' => 'code',
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'scope' => 'https://www.googleapis.com/auth/gmail.modify',
];

// Construir a URL de autorização
$authorizationUrl = $authorizationEndpoint . '?' . http_build_query($authorizationParams);

// Redirecionar para a URL de autorização
header('Location: ' . $authorizationUrl);
exit;
