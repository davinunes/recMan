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

function getMensagens($recurso) {
    $sql = "SELECT id, id_usuario, id_recurso, texto, timestamp 
            FROM conselho.mensagem
            WHERE id_recurso = '$recurso'";

    $result = DBExecute($sql);
    $dados = array(); // Inicializa a variável $dados como um array vazio

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
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

function upsertComentario($dados) {
    $id_recurso = $dados['id_recurso'];
    $id_usuario = $dados['user_id']; // Supondo que você tenha o ID do usuário na sessão
    $mensagem = DBEscape($dados['messageText']); 

    $sql  = "INSERT INTO conselho.mensagem ";
    $sql .= "(id_usuario, id_recurso, texto) ";
    $sql .= "VALUES ('$id_usuario', '$id_recurso', '$mensagem') ";
    
    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function upsertVoto($dados) {
    $id_recurso = $dados['idRec'];
    $id_usuario = $dados['user_id'];
    $voto = $dados['voto'];

    $sql = "INSERT INTO conselho.votos ";
    $sql .= "(id_recurso, id_usuario, voto) ";
    $sql .= "VALUES ('$id_recurso', '$id_usuario', '$voto') ";
    $sql .= "ON DUPLICATE KEY UPDATE ";
    $sql .= "voto = '$voto'";

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

?>