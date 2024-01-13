<nav class="blue darken-3">
<ul id="slide-out" class="sidenav">
    <li>
		<div class="user-view ">
			<div class="background">
				<!-- Background da sidenav, se desejar -->
			</div>
			<a href="#user"><img class="circle" src="<?php echo $meuAvatar; ?>"></a>
			<a href="#name"><span class="blue-text name"><?php echo $_SESSION['user_nome']; ?></span></a>
			<a href="#email"><span class="blue-text email"><?php echo $_SESSION['user_email']; ?></span></a>
		</div>
	</li>
    <li>
		<a  href="index.php?pag=novoRecurso" class="btn">Novo Recurso</a>
		<a  href="index.php" class="btn">Recursos</a>
		<a  href="index.php?pag=planilhaSolucoes" class="btn">Lista Soluções</a>
		<a  href="index.php?pag=usuarios" class="btn">Usuarios</a>
		<a  href="index.php?pag=perfil" class="btn">Perfil</a>
		<a  href="index.php?pag=senha" class="btn">Mudar Senha</a>
		<a  href="index.php?pag=tools" class="btn">Ferramentas</a>
		<a id="logout" href="#" class="btn">Sair</a>
	</li>
</ul>

<a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons">menu</i></a>
<a class="brand-logo "><?php echo $_SESSION['user_nome']; ?></a>

</nav>
