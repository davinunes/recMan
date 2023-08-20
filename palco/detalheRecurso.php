<?php
// require "classes/database.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados
require "classes/repositorio.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados
$sql = "SELECT * FROM recurso where numero = '{$_GET['rec']}'";
$result = DBExecute($sql);
$result = mysqli_fetch_assoc($result);
$esseRecurso = $result['id'];

$mensagens = getMensagens($esseRecurso);
// var_dump($mensagens);
?>

<body>
    <!-- Cabeçalho -->
	<?php
echo '<header class="header-container">
<nav class="header-navbar">
    <div class="nav-wrapper">
        <ul class="left">
            <li><a>Recurso <span id="idRecurso" idRec="'.$esseRecurso.'">'.$result['numero'].'</span></a></li>
            <li><a>Unidade <span id="unidadeRecurso">'.$result['unidade'].$result['bloco'].'</span></a></li>
            <li><a><span id="blocoRecurso">'.$result['titulo'].'</span></a></li>
        </ul>
        <ul class="right">
            <li>Votos:</li>
            <li><a>Manter: <span id="votosManter">5</span></a></li>
            <li><a>Revogar: <span id="votosRevogar">3</span></a></li>
            <li><a>Converter: <span id="votosConverter">2</span></a></li>
        </ul>
    </div>
</nav>
</header>
	';
	?>
    <!-- Corpo da página -->
    <main>
		<?php
echo '<div class="row">
    <div class="col s12 m8 offset-m2">
        <div class="card">
            <div class="card-content">
                <span class="card-title">Texto na Notificação</span>
                <p>'.$result['detalhes'].'</p>';
				// Adicione uma lista para exibir as mensagens
echo '<div class="row">
    <div class="">
        <ul class="collection with-header">
            ';
            foreach ($mensagens as $mensagem) {
                echo '<li class="collection-item">' . $mensagem['texto'] . '</li>';
            }
echo   '</ul>
    </div>
</div>';
echo '      </div>
            <div class="card-action">
                <a class="modal-trigger" href="#novaMensagemModal">Comentar</a>
                <a class="modal-trigger" href="#votoModal">Votar</a>
                <a class="modal-trigger" href="#alterarFaseModal">Mudar  Status</a>
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
        <p>Formulário para votar...</p>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Enviar Voto</a>
    </div>
</div>

<div id="alterarFaseModal" class="modal">
    <div class="modal-content">
        <h4>Alterar Estágio do Recurso</h4>
        <p>Formulário para alterar o estágio do recurso...</p>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Salvar Alterações</a>
    </div>
</div>
