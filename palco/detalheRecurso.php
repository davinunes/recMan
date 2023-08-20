<?php
require "classes/database.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados
$sql = "SELECT * FROM recurso where numero = '{$_GET['rec']}'";
$result = DBExecute($sql);
$result = mysqli_fetch_assoc($result);
// var_dump($result);
?>

<body>
    <!-- Cabeçalho -->
	<?php
echo '<header class="header-container">
<nav class="header-navbar">
    <div class="nav-wrapper">
        <ul class="left">
            <li><a>Recurso <span id="numeroRecurso">'.$result['numero'].'</span></a></li>
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
                <span class="card-title">Detalhes da Notificação/Recurso</span>
                <p>'.$result['detalhes'].'</p>
            </div>
            <div class="card-action">
                <a class="modal-trigger" href="#novaMensagemModal">Nova Mensagem</a>
                <a class="modal-trigger" href="#votoModal">Votar</a>
                <a class="modal-trigger" href="#alterarFaseModal">Alterar Estágio</a>
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
        <h4>Nova Mensagem</h4>
        <p>Formulário para inserir uma nova mensagem...</p>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Enviar</a>
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
