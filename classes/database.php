<?php

ini_set('display_errors', 0);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (file_exists(__DIR__ . "/key.php")) {
	include __DIR__ . "/key.php";
} else {
	// Fallback ou apenas silenciar o erro se os constantes já estiverem definidos
}

function DBConnect()
{
	if (!defined('DB_HOSTNAME')) {
		die("Configurações de banco de dados não encontradas (key.php ausente).");
	}
	$link = @mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
	// var_dump($link);

	if (!$link) {
		die("Erro de conexão: " . mysqli_connect_error());
		// var_dump(mysqli_connect_error());
	}

	mysqli_set_charset($link, DB_CHARSET) or die("Erro de charset: " . mysqli_error($link));

	return $link;
}


function DBClose($link)
{ # Fecha Conexão com Database
	mysqli_close($link) or die(mysqli_error($link));
}

function DBEscape($dados)
{ # Proteje contra SQL Injection
	$link = DBConnect();
	// var_dump($link);

	if (!is_array($dados)) {
		$dados = mysqli_real_escape_string($link, $dados);
	} else {
		$arr = $dados;
		foreach ($arr as $key => $value) {
			$key = mysqli_real_escape_string($link, $key);
			$value = mysqli_real_escape_string($link, $value);

			$dados[$key] = $value;
		}
	}
	DBClose($link);
	return $dados;
}

function DBExecute($query, $returnId = false, $die = true)
{ # Executa um Comando na Conexão
	$link = DBConnect();
	$result = mysqli_query($link, $query);

	if (!$result) {
		$error = mysqli_error($link) . " [Query: $query]";
		DBClose($link);
		if ($die) {
			die($error);
		}
		return false;
	}

	if ($returnId) {
		$res = mysqli_insert_id($link);
	} else {
		$res = $result;
	}

	DBClose($link);
	return $res;
}

function DBQuery($sql)
{ # Executa um Comando na Conexão
	$result = DBExecute($sql);
	// echo $sql;
	if (mysqli_num_rows($result) > 0) {
		while ($retorno = mysqli_fetch_assoc($result)) {
			$dados[] = $retorno;
		}

		return $dados;
	} else {
		return null;

	}
}

function DBInsertID()
{
	$link = DBConnect();
	$id = mysqli_insert_id($link);
	DBClose($link);
	return $id;
}

function DBMultiExecute($query)
{
	$link = DBConnect();
	$result = mysqli_multi_query($link, $query);
	if (!$result) {
		die(mysqli_error($link) . " [MultiQuery: $query]");
	}
	while (mysqli_more_results($link) && mysqli_next_result($link));
	DBClose($link);
	return true;
}
?>