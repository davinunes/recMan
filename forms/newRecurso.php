<div class="container">
<h2>Cadastrar Recurso</h2>
        <form id="formNewRecurso" method="post">
            <div class="input-field">
                <input type="text" name="nome" id="nome" placeholder="">
                <label for="nome">Nome da Pessoa que apresentou recurso</label>
            </div>
            <div class="input-field">
                <input type="text" name="titulo" id="titulo" placeholder="">
                <label for="titulo">Título do Recurso</label>
            </div>
		<div class="row teal lighten-4">
			<div class="input-field col s6">
				<select class="" name="bloco" id="bloco" required>
					<option value="" disabled selected>Escolha um bloco</option>
					<option value="A">A</option>
					<option value="B">B</option>
					<option value="C">C</option>
					<option value="D">D</option>
					<option value="E">E</option>
					<option value="F">F</option>
				</select>
				<label for="bloco">Bloco</label>
			</div>
            <div class="input-field col s6">
                <input type="text" name="unidade" id="unidade" required placeholder="">
                <label for="unidade">Unidade (101 a 1912)</label>
            </div>
		</div>
		<div class="row">
            <div class="input-field col s6 teal lighten-4">
                <input type="text" name="numero" id="numero" required placeholder="">
                <label for="numero">Número da Notificação (xxx/yyyy)</label>
            </div>
            <div class="input-field col s6">
                <input type="text" name="artigo" id="artigo" placeholder="">
                <label for="artigo">Artigo Relacionado no Regimento</label>
            </div>
		</div>
			<div class="input-field">
				<select name="fase" id="fase" required>
					<option value="" disabled >Escolha a fase do recurso</option>
					<?php
					// Aqui você precisa fazer a consulta ao banco de dados para obter as fases
					include "/var/www/html/classes/repositorio.php";
					
					$fases = getFasesRecurso();
					// var_dump($fases);
					$i = 0;
					foreach ($fases as $fase) {
						$selecionado = $i++ == 0 ? "selected" : "";
						echo '<option value="' . $fase['id'] . '" '.$selecionado.'>' . $fase['texto'] . ' </option>';
					}
					?>
				</select>
				<label for="fase">Fase do Recurso</label>
			</div>

            <div class="input-field">
                <input type="email" name="email" id="email">
                <label for="email">Email para Resposta</label>
            </div>
            <div class="input-field">
                <textarea name="detalhes" id="detalhes" class="materialize-textarea active" placeholder=""></textarea>
                <label for="detalhes">Detalhes do Recurso</label>
            </div>
			<div class="input-field teal lighten-4">
				<input type="date" name="data" id="data" required>
				<label for="data_recurso">Data do Recurso</label>
			</div>
            <a id="newRecurso" class="btn waves-effect waves-light" >Cadastrar</a>
        </form>
</div>