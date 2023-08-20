<?php

	ini_set('display_errors', 0);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);

	include "/var/www/html/classes/key.php";

	function DBConnect() {
		$link = @mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		// var_dump($link);

		if (!$link) {
			die("Erro de conexão: " . mysqli_connect_error());
			// var_dump(mysqli_connect_error());
		}

		mysqli_set_charset($link, DB_CHARSET) or die("Erro de charset: " . mysqli_error($link));

		return $link;
	}


	function DBClose($link){ # Fecha Conexão com Database
		mysqli_close($link) or die(mysqli_error($link));
	}

	function DBEscape($dados){ # Proteje contra SQL Injection
		$link = DBConnect();
		var_dump($link);
		
		if(!is_array($dados)){
			$dados = mysqli_real_escape_string($link,$dados);
		}else{
			$arr = $dados;
			foreach($arr as $key => $value){
				$key	= mysqli_real_escape_string($link, $key);
				$value	= mysqli_real_escape_string($link, $value);
				
				$dados[$key] = $value;
			}
		}
		DBClose($link);
		return $dados;
	}

	function DBExecute($query){ # Executa um Comando na Conexão
		$link = DBConnect();
		$result = mysqli_query($link,$query) or die(mysqli_error($link));
		
		DBClose($link);
		return $result;
	}
?>