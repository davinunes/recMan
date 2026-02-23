<head>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Conselho Miami</title>


	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
	<link rel="stylesheet" href="meu.css?<?php echo time(); ?>">

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://raw.githubusercontent.com/jackmoore/autosize/master/src/autosize.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
	<script type="text/javascript" charset="utf8"
		src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
	<script src="clip.js"></script>
	<script defer src="meu.js?<?php echo time(); ?>"></script>
	<script src="https://code.highcharts.com/highcharts.js"></script>

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
$esseUsuario = $_SESSION["user_id"];
$meuAvatar = $_SESSION["avatar"];
include "palco/usuarioLogado.php";

switch ($_GET['pag']) {
	case "recurso":
		include "palco/detalheRecurso.php";
		break;
	case "usuarios":
	case "senha":
	case "perfil":
		include "palco/gerenciaUsuario.php";
		break;
	case "novoUsuario":
		include "forms/newUser.php";
		break;
	case "novoRecurso":
		include "forms/newRecurso.php";
		break;
	case "emiteParecer":
		include "palco/emiteParecer.php";
		break;

	case "editarRecurso":
		include "forms/atualizarRecurso.php";
		break;
	case "planilhaSolucoes":
		include "palco/planSoluc.php";
		break;
	case "tools":
		include "palco/tools.php";
		break;
	case "historico":
		include "palco/historico.php";
		break;
	case "estatisticas":
		include "palco/estatisticas.php";
		break;
	case "statsSoluc":
		include "palco/statsSoluc.php";
		break;
	case "dashboard":
		include "palco/dashboard.php";
		break;
	default:
		include "palco/listaRecursos.php";

}

require_once "classes/repositorio.php";
$gmail = verificarToken();
$token = $gmail["tkn"];

if ($gmail["status"] && $gmail["resta"] > 59) {
	echo $gmail['resta'] . "s<br/>";
} else {
	include("/var/www/html/gmail/refresh.php");
}

?>