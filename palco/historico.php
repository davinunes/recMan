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
			<div class="col s12 m2" style="padding-top: 15px;">
				<button class="btn waves-effect waves-light blue darken-2" id="buscaHistoricoUnidade"
					style="width: 100%;">
					<i class="material-icons">search</i>
				</button>
			</div>
		</div>

		<!-- Área do Brief (Dashboard da Unidade) - Compacto -->
		<div id="unitBrief" class="row hide" style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px;">
			<!-- Injetado via JS -->
		</div>
	</div>

	<!-- Área de Resultados (Cards) -->
	<div id="listaRetornoCards" class="row" style="margin-top: 20px;">
		<div class="col s12 center-align grey-text" id="emptyState" style="padding: 50px;">
			<i class="material-icons" style="font-size: 5rem; opacity: 0.2;">search_off</i>
			<p>Informe a unidade e o bloco para visualizar o histórico de notificações.</p>
		</div>
	</div>

	<!-- Paginação -->
	<div id="historyPagination" class="center-align" style="margin-bottom: 30px;"></div>
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
		margin: 0.5rem 0 !important;
	}

	.card-notificacao:hover {
		transform: scale(1.01);
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

	/* Cores de Fundo por Parecer */
	.parecer-manter {
		background-color: #ffebee !important;
	}

	/* Vermelho clarinho */
	.parecer-converter {
		background-color: #fff3e0 !important;
	}

	/* Laranja clarinho */
	.parecer-revogar {
		background-color: #e8f5e9 !important;
	}

	/* Verde clarinho */

	/* Layout Híbrido */
	@media only screen and (min-width: 993px) {
		.flex-responsive {
			display: flex !important;
			flex-direction: row;
			align-items: center;
		}
	}

	@media only screen and (max-width: 992px) {
		.flex-responsive {
			display: block !important;
		}

		.flex-responsive .col {
			margin-bottom: 10px;
		}
	}

	.badge-mini {
		padding: 2px 6px;
		border-radius: 3px;
		font-size: 0.7rem;
		font-weight: bold;
		display: inline-block;
	}
</style>

<script>
	$(document).ready(function () {
		$('select').formSelect();
	});
</script>