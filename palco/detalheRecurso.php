<?php

require "classes/repositorio.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados

$sql = "SELECT r.*, f.texto as fasee FROM recurso r
		left join fase f on f.id = r.fase 
		where r.numero = '{$_GET['rec']}'";

$result = DBExecute($sql);

$result = mysqli_fetch_assoc($result);
$esseRecurso = $result['id'];

$mensagens = getMensagens($esseRecurso);
$votos = getVotos($esseRecurso);

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
echo '            <div class="card-content">
                <h6 class="">'.$result['titulo'].'</h6>
                <pre>'.$result['detalhes'].'</pre>';
				
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
echo "<h6>Comentários</h6>";

echo '<div class="row">
    <div class="">
        <ul class="collection with-header">
            ';
            foreach ($mensagens as $mensagem) {
                echo '<li class="collection-item avatar">
				<img src="'.$mensagem['avatar'].'" alt="" class="circle">
				' . $mensagem['texto'] . '
				</li>';
            }
echo   '</ul>';
echo "<h6>Histórico da unidade</h6>";

echo '<table class="striped">';
		echo "<div id=\"popup\" class=\"popup\">
				  <div id=\"popup-content\" class=\"popup-content\">
					Popup
				  </div>
				</div>";
foreach($historico as $h){
	// var_dump($result);
	$classe = $result['numero'] == $h['numero_ano_virtual'] ? "orange darken-1" : "";
	echo '<tr class="recurso ' . $classe . '" rec="' . $h['numero_ano_virtual'] . '" 
          data-numero="' . $h['numero_ano_virtual'] . '" 
          data-data_email="' . date("d/m/Y", strtotime($h['data_email'])) . '" 
          data-data_envio="' . date("d/m/Y", strtotime($h['data_envio'])) . '" 
          data-status="' . $h['status'] . '" 
          data-cobranca="' . $h['cobranca'] . '" 
          data-tipo="' . $h['notificacao'] . '" 
          data-obs="' . $h['obs'] . '" 
          data-assunto="' . $h['assunto'] . '" 
          data-data-ocorrido="' . date("d/m/Y", strtotime($h['data_ocorrido'])) . '">';

		echo "<td>".$h['numero_ano_virtual']."</td>";
		echo "<td>".$h['notificacao']."</td>";
		echo "<td>".$h['assunto']."</td>";
		echo "<td>Ocorreu " . date("d/m/Y", strtotime($h['data_ocorrido'])) . "</td>";

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


