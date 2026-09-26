<?php
// Configuração do ambiente
$htmlPath = file_exists('/var/www/mini/py/ssh.py') ? '/var/www/mini' : __DIR__;
$pythonPath = '/usr/bin/python3';
$sshScript = $htmlPath . '/py/ssh.py';

if (isset($_GET['action'])) {
	header('Content-Type: application/json');
	$output = "";

	if ($_GET['action'] == 'status') {
		$cmd = "cd $htmlPath && git status 2>&1";
		$output = shell_exec($cmd);
		echo json_encode(['success' => true, 'output' => $output]);
		exit;
	}

	if ($_GET['action'] == 'push') {
		$mensagem = isset($_POST['msg']) && !empty($_POST['msg']) ? $_POST['msg'] : "Atualização automática via Painel";

		// Comando encadeado para Deploy
		$gitCmd = "cd $htmlPath && git pull && git add . && git commit -m \"$mensagem\" && git push";

		// Executa via script SSH Python existente no projeto
		$fullCmd = "$pythonPath $sshScript '$gitCmd'";

		$output = shell_exec($fullCmd);
		echo json_encode(['success' => true, 'output' => $output]);
		exit;
	}

	if ($_GET['action'] == 'pull') {
		$mensagem = isset($_POST['msg']) && !empty($_POST['msg']) ? $_POST['msg'] : "Atualização automática via Painel";

		// Comando encadeado para Deploy
		$gitCmd = "cd $htmlPath && git pull";

		// Executa via script SSH Python existente no projeto
		$fullCmd = "$pythonPath $sshScript '$gitCmd'";

		$output = shell_exec($fullCmd);
		echo json_encode(['success' => true, 'output' => $output]);
		exit;
	}

	if ($_GET['action'] == 'restart_pdf_api') {
		// Comando encadeado para reiniciar o serviço Python PDF via venv
		$pyCmd = "cd /var/www/reportPDFpython && (pkill -9 -f parecer.py || true) && (nohup venv/bin/python parecer.py > parecer.log 2>&1 &) && sleep 1 && ps aux | grep parecer.py | grep -v grep 2>&1";

		$fullCmd = "$pythonPath $sshScript '$pyCmd'";
		$output = shell_exec($fullCmd);
		$cleanOutput = trim($output) ?: 'Serviço reiniciado com sucesso.';
		echo json_encode(['success' => true, 'output' => $cleanOutput]);
		exit;
	}

	if ($_GET['action'] == 'pull_pdf_api') {
		// Comando para atualizar via git pull 2>&1 e reiniciar a API Python PDF
		$pyCmd = "cd /var/www/reportPDFpython && git pull 2>&1 && (pkill -9 -f parecer.py || true) && (nohup venv/bin/python parecer.py > parecer.log 2>&1 &) && sleep 1 && ps aux | grep parecer.py | grep -v grep 2>&1";

		$fullCmd = "$pythonPath $sshScript '$pyCmd'";
		$output = shell_exec($fullCmd);
		$cleanOutput = trim($output) ?: 'Git pull executado e serviço reiniciado com sucesso.';
		echo json_encode(['success' => true, 'output' => $cleanOutput]);
		exit;
	}

	if ($_GET['action'] == 'restart_typst_api') {
		// Comando com desacoplamento total via disown e verificação do ps aux
		$pyCmd = "cd /var/www/mini/py && (pkill -9 -f typst_server.py || true) && (nohup /usr/bin/python3 -u typst_server.py </dev/null > /var/log/typst_server.log 2>&1 & disown) && sleep 1 && (ps aux | grep typst_server.py | grep -v grep || cat /var/log/typst_server.log)";

		$fullCmd = "$pythonPath -u $sshScript '$pyCmd' 2>&1";
		$output = shell_exec($fullCmd);
		
		$debugOutput = "=== DEBUG PHP SHELL EXEC ===\nFULL CMD: " . $fullCmd . "\nRAW OUTPUT:\n" . ($output ?: "[NENHUMA SAIDA RETORNADA PELO SHELL_EXEC]");
		echo json_encode(['success' => true, 'output' => $debugOutput]);
		exit;
	}
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>GIT Deploy Control</title>
	<!-- Favicon -->
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="shortcut icon" href="/favicon.ico">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
	<style>
		body {
			background-color: #eceff1;
		}

		.console {
			background-color: #263238;
			color: #afff00;
			padding: 15px;
			border-radius: 4px;
			font-family: 'Courier New', Courier, monospace;
			min-height: 200px;
			max-height: 500px;
			overflow-y: auto;
			white-space: pre-wrap;
			box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.5);
		}

		.card {
			border-radius: 10px;
		}

		.btn-deploy {
			margin-top: 10px;
			width: 100%;
		}
	</style>
</head>

<body>

	<div class="container">
		<div class="row" style="margin-top: 30px;">
			<div class="col s12">
				<h4 class="blue-grey-text text-darken-3">
					<i class="material-icons medium left">cloud_queue</i>
					GIT Deploy Control
				</h4>
			</div>
		</div>

		<div class="row">
			<!-- Coluna de Ações -->
			<div class="col s12 l5">
				<div class="card white">
					<div class="card-content">
						<span class="card-title">Realizar Deploy</span>
						<p class="grey-text">Executa: <code>pull -> add -> commit -> push</code></p>

						<div class="input-field" style="margin-top: 30px;">
							<i class="material-icons prefix">message</i>
							<input type="text" id="msg" placeholder="Descreva as alterações...">
							<label for="msg">Mensagem do Commit</label>
						</div>

						<button id="btnPull" class="btn btn-large waves-effect waves-light black btn-deploy">
							<i class="material-icons left">refresh</i> Atualizar
						</button>

						<button id="btnPush" class="btn btn-large waves-effect waves-light black btn-deploy">
							<i class="material-icons left">publish</i> Enviar para Produção
						</button>

						<button id="btnStatus" class="btn-flat waves-effect btn-deploy grey-text text-darken-1">
							<i class="material-icons left">info_outline</i> Verificar Status Atual
						</button>
					</div>
				</div>

				<div class="card indigo darken-3 white-text">
					<div class="card-content">
						<span class="card-title"><i class="material-icons left">picture_as_pdf</i>API Python PDF (/var/www/reportPDFpython)</span>
						<p style="font-size:0.9rem; opacity:0.9;">Gerencia o processo do serviço Flask em Python na porta 5000.</p>

						<button id="btnRestartPdf" class="btn btn-small waves-effect waves-light amber darken-3 btn-deploy" style="margin-top:15px;">
							<i class="material-icons left">autorenew</i> Reiniciar Serviço PDF (5000)
						</button>

						<button id="btnPullPdf" class="btn btn-small waves-effect waves-light deep-purple darken-1 btn-deploy">
							<i class="material-icons left">cloud_download</i> Git Pull + Reiniciar PDF
						</button>
					</div>
				</div>

				<div class="card teal darken-3 white-text">
					<div class="card-content">
						<span class="card-title"><i class="material-icons left">bolt</i>API Typst PDF CLI (Porta 5050)</span>
						<p style="font-size:0.9rem; opacity:0.9;">Gerencia o processo do microserviço HTTP Typst em <code>py/typst_server.py</code>.</p>

						<button id="btnRestartTypst" class="btn btn-small waves-effect waves-light cyan darken-3 btn-deploy" style="margin-top:15px;">
							<i class="material-icons left">autorenew</i> Reiniciar Serviço Typst (5050)
						</button>
					</div>
				</div>

				<div class="card blue-grey darken-4 white-text">
					<div class="card-content">
						<h6>Dicas Rápidas</h6>
						<ul style="font-size: 0.9rem; margin-top: 10px;">
							<li>• Sempre verifique o <b>status</b> antes do push.</li>
							<li>• Commits claros ajudam no histórico.</li>
							<li>• O processo usa o script <code>ssh.py</code> interno.</li>
							<li>• Use o botão <b>Reiniciar Serviço PDF</b> após atualizar scripts Python.</li>
						</ul>
					</div>
				</div>
			</div>

			<!-- Coluna do Console -->
			<div class="col s12 l7">
				<div class="card">
					<div class="card-content">
						<span class="card-title grey-text text-darken-2">Terminal de Saída</span>
						<div id="terminal" class="console">> Aguardando comando...</div>

						<div id="loader" style="display:none; margin-top: 10px;">
							<div class="progress grey lighten-3">
								<div class="indeterminate blue-grey darken-3"></div>
							</div>
						</div>
					</div>
					<div class="card-action">
						<button onclick="$('#terminal').empty().html('> Console limpo.')"
							class="btn-small btn-flat">Limpar Terminal</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		$(document).ready(function () {
			function log(msg) {
				const time = new Date().toLocaleTimeString();
				$('#terminal').append('\n[' + time + '] ' + msg);
				$('#terminal').scrollTop($('#terminal')[0].scrollHeight);
			}

			$('#btnStatus').click(function () {
				$('#loader').show();
				log('Consultando status do repositório...');
				$.get('git.php?action=status', function (res) {
					$('#terminal').append('\n' + res.output);
					log('Status finalizado.');
				}).always(() => $('#loader').hide());
			});

			$('#btnPull').click(function () {
				const msg = $('#msg').val();

				$('#loader').show();
				$('#btnPull').addClass('disabled');
				log('Iniciando sequência de pull...');

				$.post('git.php?action=pull', { msg: msg }, function (res) {
					$('#terminal').append('\n' + res.output);
					log('Processo de pull concluído.');
					$('#msg').val('');
					M.toast({ html: 'Pull finalizado com sucesso!', classes: 'green' });
				}).fail(function () {
					log('ERRO: Falha na comunicação com o servidor.');
				}).always(() => {
					$('#loader').hide();
					$('#btnPull').removeClass('disabled');
				});
			});

			$('#btnPush').click(function () {
				const msg = $('#msg').val();
				if (!msg) {
					M.toast({ html: 'Por favor, digite uma mensagem de commit!', classes: 'red' });
					return;
				}

				if (!confirm('Deseja realmente iniciar o deploy para produção?')) return;

				$('#loader').show();
				$('#btnPush').addClass('disabled');
				log('Iniciando sequência de push...');

				$.post('git.php?action=push', { msg: msg }, function (res) {
					$('#terminal').append('\n' + res.output);
					log('Processo de push concluído.');
					$('#msg').val('');
					M.toast({ html: 'Push finalizado com sucesso!', classes: 'green' });
				}).fail(function () {
					log('ERRO: Falha na comunicação com o servidor.');
				}).always(() => {
					$('#loader').hide();
					$('#btnPush').removeClass('disabled');
				});
			});

			$('#btnRestartPdf').click(function () {
				$('#loader').show();
				$('#btnRestartPdf').addClass('disabled');
				log('Reiniciando serviço da API Python PDF (/var/www/reportPDFpython)...');

				$.get('git.php?action=restart_pdf_api', function (res) {
					var outText = (res && res.output && res.output !== 'null') ? res.output : 'Serviço da API Python de PDF reiniciado com sucesso.';
					$('#terminal').append('\n' + outText);
					log('Serviço da API Python de PDF reiniciado.');
					M.toast({ html: 'API Python PDF reiniciada!', classes: 'green' });
				}).fail(function () {
					log('ERRO: Falha na comunicação com o servidor.');
				}).always(() => {
					$('#loader').hide();
					$('#btnRestartPdf').removeClass('disabled');
				});
			});

			$('#btnPullPdf').click(function () {
				$('#loader').show();
				$('#btnPullPdf').addClass('disabled');
				log('Fazendo git pull em /var/www/reportPDFpython e reiniciando a API...');

				$.get('git.php?action=pull_pdf_api', function (res) {
					var outText = (res && res.output && res.output !== 'null') ? res.output : 'Git pull realizado e API Python reiniciada com sucesso.';
					$('#terminal').append('\n' + outText);
					log('Git pull e reinício da API Python concluídos.');
					M.toast({ html: 'API Python PDF atualizada e reiniciada!', classes: 'green' });
				}).fail(function () {
					log('ERRO: Falha na comunicação com o servidor.');
				}).always(() => {
					$('#loader').hide();
					$('#btnPullPdf').removeClass('disabled');
				});
			});

			$('#btnRestartTypst').click(function () {
				$('#loader').show();
				$('#btnRestartTypst').addClass('disabled');
				log('Reiniciando serviço da API Typst (Porta 5050) em py/typst_server.py...');

				$.get('git.php?action=restart_typst_api', function (res) {
					var outText = (res && res.output && res.output !== 'null') ? res.output : 'Serviço da API Typst 5050 reiniciado com sucesso.';
					$('#terminal').append('\n' + outText);
					log('Serviço da API Typst 5050 reiniciado.');
					M.toast({ html: 'API Typst (Porta 5050) reiniciada!', classes: 'green' });
				}).fail(function () {
					log('ERRO: Falha na comunicação com o servidor.');
				}).always(() => {
					$('#loader').hide();
					$('#btnRestartTypst').removeClass('disabled');
				});
			});
		});
	</script>

</body>

</html>