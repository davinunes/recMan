<head>

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conselho Miami</title>
  
  
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
  <link rel="stylesheet" href="meu.css?<?php echo time();?>">
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script defer src="meu.js?<?php echo time();?>"></script>

</head>

<?php
ini_set('display_errors', 0);
header("Content-Type: text/html; charset=UTF-8");
session_start();
// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    include('forms/login.php'); // Inclui a página de login
    exit(); // Encerra a execução do script após incluir o login.php
}
// var_dump($_SESSION);
include "palco/usuarioLogado.php";


// include "forms/newUser.php";
// include "forms/newRecurso.php";
switch($_GET['pag']){
	case "recurso":
		include "palco/detalheRecurso.php";
		break;
	default:
		include "palco/listaRecursos.php";
		
}
// session_destroy();

?>