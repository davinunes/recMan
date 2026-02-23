<div class="container">
	<div class="row valign-wrapper" style="margin-top: 20px;">
		<div class="col s12 m6">
			<h4 style="margin: 0; font-weight: 300;">Recursos</h4>
		</div>
		<div class="col s12 m6 right-align">
			<a class="btn-small blue waves-effect" href="?concluidos=false">Abertos</a>
			<a class="btn-small black waves-effect" href="?concluidos=true">Concluídos</a>
			<button class="btn-small teal waves-effect modal-trigger" data-target="modalEmailResumo">E-mail
				Resumo</button>
			<a class="btn-small orange waves-effect" href="index.php?pag=novoRecurso"><i
					class="material-icons">add</i></a>
		</div>
	</div>

	<!-- Modal de Resumo para E-mail -->
	<div id="modalEmailResumo" class="modal modal-fixed-footer">
		<div class="modal-content">
			<h5>Relatório para o Jurídico</h5>
			<?php
			require_once "classes/repositorio.php";

			$gmail = verificarToken();
			$token = $gmail["tkn"];

			if ($gmail["status"] && $gmail["resta"] > 59) {
				// OK
			}

			$sql1 = "SELECT r.numero, f.texto FROM recurso r left join fase f on f.id = r.fase where f.id < 4 order by r.numero";
			$sql2 = "SELECT r.numero
								FROM conselho.recurso r
								 JOIN (
									SELECT id_recurso
									FROM conselho.votos
									WHERE voto = 'manter'
									GROUP BY id_recurso
									HAVING COUNT(*) >= 2
								) votos_manter ON r.id = votos_manter.id_recurso
								 JOIN conselho.fase f ON f.id = r.fase
								WHERE f.id = 4
								ORDER BY r.numero
								";
			$sql3 = "SELECT r.numero
								FROM conselho.recurso r
								 JOIN (
									SELECT id_recurso
									FROM conselho.votos
									WHERE voto = 'revogar'
									GROUP BY id_recurso
									HAVING COUNT(*) >= 2
								) votos_manter ON r.id = votos_manter.id_recurso
								 JOIN conselho.fase f ON f.id = r.fase
								WHERE f.id = 4
								ORDER BY r.numero
								";
			$sql4 = "SELECT r.numero
								FROM conselho.recurso r
								 JOIN (
									SELECT id_recurso
									FROM conselho.votos
									WHERE voto = 'converter'
									GROUP BY id_recurso
									HAVING COUNT(*) >= 2
								) votos_manter ON r.id = votos_manter.id_recurso
								 JOIN conselho.fase f ON f.id = r.fase
								WHERE f.id = 4
								ORDER BY r.numero
								";

			$mensagem = "<p>Prezados, segue relação de notificações em tratamento pelo Conselho:</p>";
			$mensagem .= "<p><b>Em análise</b>:<br>";
			foreach (DBQuery($sql1) as $analise) {
				$mensagem .= $analise['numero'] . "<br>";
			}
			$mensagem .= "</p>";
			$mensagem .= "<p><b>Recomendamos Manter a notificação</b>:<br>";
			foreach (DBQuery($sql2) as $analise) {
				$mensagem .= $analise['numero'] . "<br>";
			}
			$mensagem .= "</p>";
			$mensagem .= "<p><b>Recomendamos Revogar a notificação</b>:<br>";
			foreach (DBQuery($sql3) as $analise) {
				$mensagem .= $analise['numero'] . "<br>";
			}
			$mensagem .= "</p>";
			$mensagem .= "<p><b>Recomendamos Converter a notificação em Advertência</b>:<br>";
			foreach (DBQuery($sql4) as $analise) {
				$mensagem .= $analise['numero'] . "<br>";
			}
			$mensagem .= "</p>";
			$mensagem .= "<p>Atenciosamente,<br>Membros do Conselho Consultivo e Fiscal</p>";

			$assunto = "Relação de Notificações com o Conselho";
			$assunto = "=?UTF-8?B?" . base64_encode($assunto) . "?=";
			$destinatarios = "sindicogeral.miami@gmail.com, centralderecursosmiamibeach@gmail.com";
			$cc = "";

			$mime = "Content-Type: text/html; charset=UTF-8\n";
			$mime .= "to: " . $destinatarios . "\n";
			if (!empty($cc)) {
				$mime .= "cc: " . $cc . "\n";
			}
			// $mime .= "bcc: ".$bcc."\n";
			$mime .= "subject: $assunto" . "\n" . "\n";
			$mime .= $mensagem . "\n";
			?>
			<div class="card-panel grey lighten-4">
				<pre id='mime' style="white-space: pre-wrap; font-size: 0.8rem;"><?php echo $mime; ?></pre>
			</div>
		</div>
		<div class="modal-footer">
			<a href="#!" class="modal-close waves-effect btn-flat">Fechar</a>
			<a class='btn blue' id='EnviaRelatorioJuridico'>Enviar E-mail</a>
		</div>
	</div>

	<?php
	// Carregamento dos dados
	if (isset($_GET['concluidos']) && $_GET['concluidos'] == "true") {
		$sql = "SELECT r.id as recurso, r.*, f.* FROM recurso r left join fase f on f.id = r.fase where f.id = 5 order by r.id desc limit 50";
	} else {
		$sql = "SELECT r.id as recurso, r.*, f.*, n.notificacao as tipo FROM recurso r left join fase f on f.id = r.fase left join notificacoes n on n.numero_ano_virtual = r.numero where f.id != 5 order by r.data";
	}

	$result = DBExecute($sql);
	?>
	<!-- Barra de Estatísticas Compacta -->
	<?php
	// Buscando contagens rápidas para o cabeçalho
	$countSql = "SELECT fase, COUNT(*) as total FROM recurso GROUP BY fase";
	$countsRaw = DBQuery($countSql);
	$counts = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
	foreach ($countsRaw as $c) {
		$counts[$c['fase']] = $c['total'];
	}
	$totalAbertos = $counts['1'] + $counts['2'] + $counts['3'] + $counts['4'];
	?>
	<div class="row" style="margin-top: -10px; margin-bottom: 20px;">
		<div class="col s12">
			<div class="grey-text text-darken-1" style="font-size: 0.8rem; display: flex; gap: 20px; flex-wrap: wrap;">
				<span><b class="blue-text"><?php echo $totalAbertos; ?></b> Recursos em Aberto</span>
				<span><b class="indigo-text"><?php echo $counts['3']; ?></b> Em Análise</span>
				<span><b class="cyan-text text-darken-2"><?php echo $counts['4']; ?></b> Parecer Conferido</span>
				<span><b class="amber-text text-darken-3"><?php echo $counts['5']; ?></b> Concluídos</span>
			</div>
		</div>
	</div>

	<style>
		.card-recurso {
			border-left: 5px solid #ccc;
			transition: all 0.2s ease;
			margin: 0.5rem 0 !important;
		}

		.card-recurso:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		}

		.card-recurso.fase-1 {
			border-left-color: #81c784;
			background-color: #f1f8e9;
		}

		/* Novo */
		.card-recurso.fase-2 {
			border-left-color: #e57373;
			background-color: #ffebee;
		}

		/* Falta Material */
		.card-recurso.fase-3 {
			border-left-color: #7986cb;
			background-color: #e8eaf6;
		}

		/* Em Análise */
		.card-recurso.fase-4 {
			border-left-color: #4dd0e1;
			background-color: #e0f7fa;
		}

		/* Conferido Parecer */
		.card-recurso.fase-5 {
			border-left-color: #ffb74d;
			background-color: #fff8e1;
		}

		/* Concluido */

		@media only screen and (min-width: 993px) {
			.flex-row-recurso {
				display: flex !important;
				flex-direction: row;
				align-items: center;
			}

			.recurso-header {
				display: none;
			}
		}

		@media only screen and (max-width: 992px) {
			.flex-row-recurso {
				display: block !important;
			}

			.recurso-item {
				margin-bottom: 10px;
			}

			.recurso-header {
				font-size: 0.7rem;
				text-transform: uppercase;
				color: #777;
				display: block;
				margin-bottom: 2px;
			}
		}

		.delay-badge {
			width: 28px;
			height: 28px;
			line-height: 28px;
			border-radius: 50%;
			display: inline-block;
			text-align: center;
			color: white;
			font-weight: bold;
			font-size: 0.8rem;
		}
	</style>

	<div id="listaRecursosCards" class="row">
		<?php
		while ($row = mysqli_fetch_assoc($result)) {
			$faseClass = "fase-" . $row['fase'];

			$dataRetirada = getDatasDeRetiradaByID($row['numero']);
			$delayEmDias = "";
			$pontoDeAtencao = "";

			if (isset($dataRetirada[0]["dia_retirada"])) {
				$retirada = strtotime($dataRetirada[0]["dia_retirada"]);
				$dataRecurso = strtotime($row["data"]);
				$delayRecurso = $dataRecurso - $retirada;
				$delayEmDias = floor($delayRecurso / 86400);
				$pontoDeAtencao = ($delayEmDias < 7) ? "green" : "red";
			}

			$votos = getVotos($row['recurso']);
			$historico = sizeof(getNotificacoes($row['unidade'], $row['bloco']));
			$vt = '';
			foreach ($votos as $v) {
				$classeVoto = match ($v['voto']) {
					'revogar' => 'green accent-2',
					'manter' => 'red accent-2',
					'converter' => 'amber accent-1',
					default => 'grey'
				};
				$vt .= '<div class="chip ' . $classeVoto . '" style="margin:2px"><img src="' . $v['avatar'] . '"></div>';
			}
			?>
			<div class="col s12">
				<div class="card card-recurso <?php echo $faseClass; ?> hoverable recurso"
					rec="<?php echo $row['numero']; ?>" style="cursor:pointer">
					<div class="card-content" style="padding: 12px 20px;">
						<div class="row valign-wrapper flex-row-recurso" style="margin-bottom: 0;">

							<!-- Numero e Unidade -->
							<div class="col s12 m2">
								<span class="recurso-header">Número / Unidade</span>
								<b style="font-size: 1.1rem;"><?php echo $row['numero']; ?></b><br>
								<span class="grey-text text-darken-2"><?php echo $row['bloco'] . $row['unidade']; ?></span>
							</div>

							<!-- Tipo e Tempo -->
							<div class="col s12 m2">
								<span class="recurso-header">Tipo / Espera</span>
								<span class="badge-mini blue white-text"
									style="padding:2px 5px; border-radius:3px; font-size:0.75rem"><?php echo $row['tipo']; ?></span><br>
								<span class="grey-text" style="font-size:0.9rem"><i class="material-icons tiny">timer</i>
									<?php echo calcularDiasPassados($row['data']); ?> dias</span>
							</div>

							<!-- Assunto -->
							<div class="col s12 m3">
								<span class="recurso-header">Assunto</span>
								<span class="truncate" style="display:block; font-weight:500"
									title="<?php echo $row['titulo']; ?>">
									<?php echo $row['titulo']; ?>
								</span>
							</div>

							<!-- Fase e Histórico -->
							<div class="col s12 m2 center-align">
								<span class="recurso-header">Fase / Hist.</span>
								<span class="badge-mini grey darken-1 white-text"
									style="padding:2px 5px; border-radius:3px; font-size:0.75rem"><?php echo $row['texto']; ?></span>
								<div style="margin-top:5px">
									<i class="material-icons tiny grey-text">history</i> <span
										class="grey-text"><?php echo $historico; ?></span>
									<?php if ($delayEmDias !== ""): ?>
										<span class="delay-badge <?php echo $pontoDeAtencao; ?>" style="margin-left:10px"
											title="Dias entre retirada e recurso"><?php echo $delayEmDias; ?></span>
									<?php endif; ?>
								</div>
							</div>

							<!-- Votos -->
							<div class="col s12 m3 right-align">
								<span class="recurso-header">Votos</span>
								<div class="votos-container">
									<?php echo $vt ?: '<small class="grey-text">Sem votos</small>'; ?>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
			<?php
		}
		echo '</div>';

		if (!function_exists('calcularDiasPassados')) {
			function calcularDiasPassados($dataInformada)
			{
				// Converte a data informada em um objeto DateTime
				$dataInformadaObj = new DateTime($dataInformada);

				// Obtém a data atual
				$dataAtualObj = new DateTime();

				// Calcula a diferença entre as datas
				$diferenca = $dataAtualObj->diff($dataInformadaObj);

				// Obtém o número de dias passados
				$diasPassados = $diferenca->days;

				return $diasPassados;
			}
		}
		?>


	</div>

	<script>
		$(document).ready(function () {
			$('.modal').modal();
		});
	</script>
	<?php
	// Fim do arquivo
	?>