<?php
require "/var/www/html/classes/database.php";

function getUsuarios($id){
	$sql  = " SELECT id, email, senha, nome, status, unidade ";
	$sql .= " FROM conselho.usuarios" ;
	
	$result	= DBExecute($sql);
	// var_dump($sql);
	if(!mysqli_num_rows($result)){

	}else{
		while($retorno = mysqli_fetch_assoc($result)){
			$dados[] = $retorno;
		}
	}
	return $dados;
}

function getUsuario($login){
	$sql  = " SELECT id, email, nome, status, unidade ";
	$sql .= " FROM conselho.usuarios" ;
	$sql .= " where email = '$login'" ;
	
	$result	= DBExecute($sql);
	// var_dump($sql);
	if(!mysqli_num_rows($result)){

	}else{
		while($retorno = mysqli_fetch_assoc($result)){
			$dados[] = $retorno;
		}
	}
	return $dados[0];
}

function verificarLogin($username, $password){
	$password = hash('sha256', $password);
	
	$sql  = " SELECT id, email, senha, nome, status, unidade ";
	$sql .= " FROM conselho.usuarios " ;
	$sql .= " WHERE email = '$username' AND senha = '$password'" ;
	
	$result	= DBExecute($sql);
	$result	= mysqli_num_rows($result);
	// var_dump($result);

	return $result;
    DBClose($link); // Feche a conexão com o banco de dados
}

function getFasesRecurso(){
	$sql  = " SELECT id, texto
				FROM conselho.fase" ;
	
	$result	= DBExecute($sql);
	if(!mysqli_num_rows($result)){

	}else{
		while($retorno = mysqli_fetch_assoc($result)){
			$dados[] = $retorno;
		}
	}
	return $dados;
}

function upsertUsuario($dados) {
    $email = $dados['email'];
    $senha = hash('sha256', $dados['senha']);
    // $senha = $dados['senha'];
    $nome = $dados['nome'];
    $status = isset($_POST['status']) ? $_POST['status'] : 1;
    $unidade = $dados['unidade'];

    $sql  = "INSERT INTO conselho.usuarios ";
    $sql .= "(email, senha, nome, status, unidade) ";
    $sql .= "VALUES ('$email', '$senha', '$nome', '$status', '$unidade') ";
    $sql .= "ON DUPLICATE KEY UPDATE ";
    $sql .= "email = '$email', ";
    $sql .= "senha = '$senha', ";
    $sql .= "nome = '$nome', ";
    $sql .= "status = $status, ";
    $sql .= "unidade = '$unidade' ";
	
	// var_dump($sql);
	
	
	// sha2('{$_POST[password]}', '256') 

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function upsertRecurso($dados) {
    $unidade = $dados['unidade'];
    $bloco = $dados['bloco'];
    $numero = $dados['numero'];
    $artigo = $dados['artigo'];
    $fase = $dados['fase'];
    $email = $dados['email'];
    $nome = $dados['nome'];
    $detalhes = $dados['detalhes'];
    $titulo = $dados['titulo'];
	$data = $dados['data'];

    $sql  = "INSERT INTO recurso ";
    $sql .= "(unidade, bloco, numero, artigo, fase, email, Nome, detalhes, titulo) ";
    $sql .= "VALUES ('$unidade', '$bloco', '$numero', '$artigo', '$fase', '$email', '$nome', '$detalhes', '$titulo') ";
    $sql .= "ON DUPLICATE KEY UPDATE ";
    $sql .= "unidade = '$unidade', ";
    $sql .= "bloco = '$bloco', ";
    $sql .= "numero = '$numero', ";
    $sql .= "artigo = '$artigo', ";
    $sql .= "fase = '$fase', ";
    $sql .= "email = '$email', ";
    $sql .= "Nome = '$nome', ";
    $sql .= "detalhes = '$detalhes', ";
    $sql .= "titulo = '$titulo', ";
	$sql .= "data = '$data'";

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}


?>