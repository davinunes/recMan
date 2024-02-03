<?php
require "classes/repositorio.php";

?>

<div class="container">
	<div class="row">
		<form >
			<div class="col s12 m2">
				<label for="data-inicio">Unidade</label>
				<input type="number" id="unidade" name="unidade" >
			</div>
			<div class="col s12 m2">
				<label for="data-fim">Bloco</label>
				<input type="text" id="bloco" name="bloco" >
			</div>

			<div class="col s12 m2">
				<a class="btn" id="buscaHistoricoUnidade">Procurar Histórico da Unidade</a>
			</div>
		</form>
	</div>
</div>
<div class="container" id="listaRetorno1">
</div>
<div class="container" id="listaRetorno2">
</div>