<?php
	include "key.php";

	function DBConnect(){ # Abre Conexão com Database
		$link = @mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE) or die(mysqli_connect_error());
		mysqli_set_charset($link, DB_CHARSET) or die(mysqli_error($link));
		return $link;
	}

	function DBClose($link){ # Fecha Conexão com Database
		@mysqli_close($link) or die(mysqli_error($link));
	}

	function DBEscape($dados){ # Proteje contra SQL Injection
		$link = DBConnect();
		
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