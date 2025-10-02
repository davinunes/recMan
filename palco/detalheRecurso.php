<?php

require "classes/repositorio.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados

$sql = "SELECT r.*, f.texto as fasee FROM recurso r
		left join fase f on f.id = r.fase 
		where r.numero = '{$_GET['rec']}'";

$result = DBExecute($sql);

$result = mysqli_fetch_assoc($result);
$esseRecurso = $result['id'];

// dump($result);

$mensagens = getMensagens($esseRecurso);
$votos = getVotos($esseRecurso);

//Verifica se Recurso está no Prazo
$dataRetirada = getDatasDeRetiradaByID($_GET['rec']);
if(isset($dataRetirada[0]["dia_retirada"])){
	$retirada = strtotime($dataRetirada[0]["dia_retirada"]);
	$diaRetirada = date('d/m/Y', strtotime($dataRetirada[0]["dia_retirada"]));
	$dataRecurso = strtotime($result["data"]);
	$delayRecurso = $dataRecurso - $retirada;
	
	$delayEmDias = $delayRecurso / 86400;
	// dump(date('Y-m-d H:i:s', $retirada));  // Mostrar a data de retirada
    // dump(date('Y-m-d H:i:s', $dataRecurso));  // Mostrar a data do recurso
    // dump($delayEmDias);
	if($delayEmDias < 7){
		$pontoDeAtencao = "green";
	}else{
		$pontoDeAtencao = "red";
	}
}else{
	$delayEmDias = "Indisponivel";
	$pontoDeAtencao = "";
	$diaRetirada = "Indisponível";
}
// $dataRetirada =  ? $dataRetirada[0]["dia_retirada"] : null;




$parecer = getParecer($result['numero']);

if (isset($result['unidade']) && isset($result['bloco']) ) {
	$historico = getNotificacoes($result['unidade'] ,$result['bloco']);
	
}

if($esseRecurso == null){
	echo "<div class='container'>
		<center>
				<h3>Não há recurso cadastrado pra essa notificação</h3>
				<a class='btn' href='javascript:void(0);' onclick='goBack();'>voltar<a>
				    <script>
						function goBack() {
							window.history.back();
						}
					</script>
		</center>
	</div>";
	exit;
}

?>

<body>
    <!-- Cabeçalho -->
	<?php
	?>
    <!-- Corpo da página -->
    <main>
		<?php
echo '<div class="row">
    <div class="col s12 m8 offset-m2">
        <div class="card">';
echo '
<nav class="header-navbar orange darken-2">
    <div class="nav-wrapper">
        <ul class="left">
            <li><a> <span id="idRecurso" idRec="'.$esseRecurso.'">'.$result['numero'].'</span></a></li>
            <li><a> <span id="unidadeRecurso">'.$result['unidade'].$result['bloco'].'</span></a></li>
            <li><a> <span id="historico">'.sizeof($historico).'</span></a></li>
            <li><a> <span id="fase">'.$result['fasee'].'</span></a></li>
            
        </ul>
        <ul class="right">
				<a class="editarRecurso" href="index.php?pag=editarRecurso&rec='.$esseRecurso.'"><i class="material-icons">edit</i></a>
        </ul>
    </div>
</nav>

	';
	
echo '      <div class="card-content">
                <h6 class="">'.$result['titulo'].'</h6>
                <div class="'.$pontoDeAtencao.'">
                    <p>Dias transcorridos entre a data de retirada e apresentação do Recurso: '.$delayEmDias.'</p>
                    <p>Retirado dia: '.$diaRetirada.'</p>
                    <p>Recurso apresentado dia: '.date('d/m/Y', strtotime($result["data"])).'</p>
                    <p>Obs: '.$dataRetirada[0]["obs"].'</p>
                </div>
				<h6 class=""><b>Fato Ocorrido</b></h6>
                <div class="grey">'.$result['fato'].'</div>
                <h6 class=""><b>Argumentação</b></h6>
                
            ';
echo '<pre>'.$result['detalhes'].'</pre>';
			
	if($parecer['concluido'] == 1){
		$link = "https://mail.google.com/mail/#inbox/".$parecer['mailId'];
	echo "<a class='btn' href='{$link}'>Email de Entrega do Parecer (abrir como conselho)</a>";
	echo '<a class="btn yellow darken-3" href="index.php?pag=emiteParecer&rec='.$result['numero'].'">Parecer</a>';
	}

				
echo '<div class="row">
    <div class="">
        <ul class="collection with-header">
            ';
            foreach ($votos as $voto) {
                echo '<li class="collection-item avatar">
				<img src="'.$voto['avatar'].'" alt="" class="circle">
				' . $voto['voto'] . '
				</li>';
            }
echo   '</ul>
    </div>
</div>';
echo "<h6><b>Comentários</b></h6>";

echo '<div class="row">
    <div class="">
        <ul class="collection with-header">
            ';
            foreach ($mensagens as $mensagem) {
				$dataFormatada = date('d/m/Y H:i:s', strtotime($mensagem['timestamp']));
				$mensagem = str_replace(["\r\n", "\r", "\n"], "<br>", $mensagem);
				if($_SESSION["user_id"] == $mensagem["id_usuario"]){
					$actions = "<span class='actions'><a class='editComment modal-trigger' href='#editaComentario' comment='{$mensagem['id']}'>$dataFormatada <i class='green-text text-darken-2 material-icons Tiny'>edit</i></a></span>";
				}else{
					$actions = "<span class='actions'>$dataFormatada</span>";
				}
                echo '<li class="collection-item avatar">
				<img src="'.$mensagem['avatar'].'" alt="" class="circle">
				' .$actions."<p>". $mensagem['texto'] . '</p>
				</li>';
            }
echo   '</ul>';
// dump($mensagens);

$vaga = getEstacionamento($result['bloco'], $result['unidade']);

foreach($vaga as $vg){
	echo "<div class='chip'>Vaga ".$vg['id_estacionamento']." ".$vg['local']." </div>";
}


echo "<h6>Histórico da unidade</h6>";

echo '<table class="striped">';
		echo "<div id=\"popup\" class=\"popup\">
				  <div id=\"popup-content\" class=\"popup-content\">
					Popup
				  </div>
				</div>";
foreach($historico as $h){
	$votos = "";
	// var_dump($result);
	$rst = getVotos($h['numero_ano_virtual']);
	// dump($rst);
	foreach($rst as $v){
		$votos .= $v['voto']."<br>";
	}
	$classe = $result['numero'] == $h['numero_ano_virtual'] ? "orange darken-1" : "";
	echo '<tr class="recurso ' . $classe . '" rec="' . $h['numero_ano_virtual'] . '" 
          data-numero="' . $h['numero_ano_virtual'] . '" 
          data-data_email="' . $h['data_email']. '" 
          data-data_envio="' . $h['data_envio'] . '" 
          data-status="' . $h['status'] . '" 
          data-cobranca="' . $h['cobranca'] . '" 
          data-tipo="' . $h['notificacao'] . '" 
          data-obs="' . $h['obs'] . '" 
          data-assunto="' . $h['assunto'] . '" 
          data-data-ocorrido="' . $h['data_ocorrido'] . '">';

		echo "<td>".$h['numero_ano_virtual']."</td>";
		echo "<td>".$h['notificacao']."</td>";
		echo "<td>".$h['assunto']."</td>";
		echo "<td>Ocorreu " . $h['data_ocorrido'] . "</td>";
		echo "<td>$votos</td>";

	echo '</tr>';
}
echo '</table>';


echo '    </div>
</div>';
echo '      </div>
            <div class="card-action">
                <a class="modal-trigger btn blue" href="#novaMensagemModal">Comentar</a>
                <a class="modal-trigger btn green darken-3" href="#alterarFaseModal">Fase</a>
                <a class="modal-trigger btn orange darken-3" href="#votoModal">Votar</a>
				';
				
if($result['fase'] == 4) echo '<a class="btn yellow darken-3" href="index.php?pag=emiteParecer&rec='.$result['numero'].'">Parecer</a>';
				
echo 				'
                <a class="modal-trigger btn right" href="index.php">Sair</a>
            </div>
        </div>
    </div>
</div>';


	?>
    </main>

    <!-- Inclua os scripts do Materialize CSS e outros recursos -->
    <!-- Inclua seu código JavaScript para controlar os modais, eventos, etc. -->
</body>




<!-- Modal de Nova Mensagem -->
<div id="novaMensagemModal" class="modal">
    <div class="modal-content">
        <h4>Novo comentário</h4>
        <p>Formulário para inserir um novo comentário...</p>
		<form id="postMessageForm">
            <div class="input-field">
                <textarea id="messageText" class="materialize-textarea" name="messageText" required></textarea>
                <label for="messageText">Mensagem</label>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a id="comentar" class="modal-close waves-effect waves-green btn-flat">Enviar</a>
    </div>
</div>
<!-- Modal de Nova Mensagem -->
<div id="editaComentario" class="modal">
    <div class="modal-content">
        <h4>Editar comentário</h4>
        <p>Edite o seu Comentário...</p>
		<form id="postMessageForm">
            <div class="input-field">
                <textarea id="messageTextComment" class="browser-default" name="messageText" placeholder="texto" required></textarea>
                <label for="messageText">Mensagem</label>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <a id="updateComment" class="modal-close waves-effect waves-green btn-flat">Salvar</a>
    </div>
</div>

<div id="votoModal" class="modal">
    <div class="modal-content">
        <h4>Votar</h4>


<label>Clique na opção desejada</label><br>
<table>
    <tr>
        <td class="opVoto" voto="manter">
            <div class="chip">Manter</div>
        </td>
        <td class="opVoto" voto="revogar">
            <div class="chip">Revogar</div>
        </td>
        <td class="opVoto" voto="converter">
            <div class="chip">Converter</div>
        </td>
    </tr>
</table>

    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
    </div>
</div>

<div id="alterarFaseModal" class="modal">
    <div class="modal-content">
        <h4>Alterar Estágio do Recurso</h4>
        <table>
			<tr>
			<?php
			
				foreach(getFasesRecurso() as $fs){
					$cor = $fs[id] == $result[fase] ? "blue" : "";
				echo "<td class='recFase ' fase='{$fs[id]}'>";
					echo "<div class='chip {$cor}'>{$fs[texto]}</div>";
					echo "</td>";
				}
			?>
			</tr>
		</table>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancelar</a>
    </div>
</div>


