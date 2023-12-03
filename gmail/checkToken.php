<?php

require_once("/var/www/html/classes/repositorio.php");

$gmail = verificarToken();

if($gmail["status"] && $gmail["resta"] > 59){
	// echo $gmail["tkn"];
}else{
	header("Location: gmail/getToken.php");
}


?>