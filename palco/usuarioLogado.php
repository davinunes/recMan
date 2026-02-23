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
			<a href="index.php?pag=dashboard" class="btn indigo">Dashboard</a>
			<a href="index.php" class="btn blue">Recursos</a>
			<a href="index.php?pag=novoRecurso" class="btn">Novo Recurso</a>
			<a href="index.php?pag=planilhaSolucoes" class="btn">Lista Com Cobrança</a>
			<a href="index.php?pag=usuarios" class="btn teal lighten-1">Gestão e Perfil</a>
			<a href="index.php?pag=tools" class="btn">Ferramentas</a>
			<a href="index.php?pag=historico" class="btn orange">Historico</a>
			<a id="logout" href="#" class="btn">Sair</a>
		</li>
	</ul>

	<a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons">menu</i></a>
	<a class="brand-logo "><?php echo $_SESSION['user_nome']; ?></a>

</nav>