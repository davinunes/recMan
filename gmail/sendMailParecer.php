<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode("Sessão Expirada!");
    exit(); // Encerra a execução do script após incluir o login.php
}

require_once "/var/www/html/classes/repositorio.php";

$gmail = verificarToken();

if($gmail["status"] && $gmail["resta"] > 5){
	// Validar e filtrar os dados de entrada (exemplo básico)
	$mime = isset($_POST['mime']) ? $_POST['mime'] : '';
	$token = $gmail["tkn"];

	// Verificar se as variáveis estão definidas antes de continuar
	if (empty($mime) || empty($token)) {
		die("Parâmetros ausentes.");
	}

	// Inicializar cURL
	$curl = curl_init();

	// Configurar as opções do cURL
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://www.googleapis.com/upload/gmail/v1/users/me/messages/send?uploadType=media',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => $mime,
		CURLOPT_HTTPHEADER => array(
			'Content-Type: message/rfc822',
			'Authorization: Bearer ' . $token
		),
	));

	// Executar a requisição cURL
	$response = curl_exec($curl);

	// Verificar erros
	if (curl_errno($curl)) {
		echo 'Erro cURL: ' . curl_error($curl);
	}

	// Fechar a sessão cURL
	curl_close($curl);

	// Exibir a resposta
	echo $response;
}else{
	echo json_encode("Token Inválido");
}
?>
