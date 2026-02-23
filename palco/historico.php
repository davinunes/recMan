<?php
require "classes/repositorio.php";

?>


<div class="container">
	<div class="row">
		<div class="col s12">
			<h4 style="font-weight: 300;"><i class="material-icons left" style="font-size: 2.5rem;">history</i>
				Histórico por Unidade</h4>
		</div>
	</div>

	<!-- Formulário de Busca -->
	<div class="card-panel white z-depth-1">
		<div class="row" style="margin-bottom: 0;">
			<div class="input-field col s12 m4">
				<i class="material-icons prefix">business</i>
				<input type="number" id="unidade" name="unidade" class="validate">
				<label for="unidade">Número da Unidade (ex: 101)</label>
			</div>
			<div class="input-field col s12 m4">
				<i class="material-icons prefix">location_city</i>
				<select id="bloco" name="bloco">
					<option value="" disabled selected>Escolha o Bloco</option>
					<option value="A">Bloco A</option>
					<option value="B">Bloco B</option>
					<option value="C">Bloco C</option>
					<option value="D">Bloco D</option>
					<option value="E">Bloco E</option>
					<option value="F">Bloco F</option>
				</select>
				<label>Bloco / Torre</label>
			</div>
			<div class="col s12 m4" style="padding-top: 15px;">
				<button class="btn waves-effect waves-light blue darken-2" id="buscaHistoricoUnidade"
					style="width: 100%;">
					<i class="material-icons left">search</i>Procurar Histórico
				</button>
			</div>
		</div>
	</div>

	<!-- Área do Brief (Dashboard da Unidade) - Preenchida via JS -->
	<div id="unitBrief" class="row hide" style="margin-top: 30px;">
		<!-- Injetado via JS -->
	</div>

	<!-- Área de Resultados (Cards) -->
	<div id="listaRetornoCards" class="row" style="margin-top: 20px;">
		<div class="col s12 center-align grey-text" id="emptyState" style="padding: 50px;">
			<i class="material-icons" style="font-size: 5rem; opacity: 0.2;">search_off</i>
			<p>Informe a unidade e o bloco para visualizar o histórico de notificações.</p>
		</div>
	</div>
</div>

<style>
	.unit-stat-card {
		padding: 15px;
		border-radius: 8px;
		color: white;
	}

	.card-notificacao {
		border-left: 5px solid #ccc;
		transition: all 0.3s ease;
	}

	.card-notificacao:hover {
		transform: scale(1.02);
		z-depth: 3;
	}

	.card-notificacao.MULTA {
		border-left-color: #f44336;
	}

	.card-notificacao.ADVERTENCIA {
		border-left-color: #ff9800;
	}

	.card-notificacao.RECURSO {
		border-left-color: #2196f3;
	}
</style>

<script>
	$(document).ready(function () {
		$('select').formSelect();
	});
</script>