<div class="container">
        <h3>Recursos</h3>
		
		<div class='row'>
			<a class="btn left blue col s2" href="?concluidos=false" >Em aberto</a>
			<a class="btn left teal col s2" href="?concluidos=false&resumo=true" >Resumo</a>
			<a class="btn left black col s2"  	href="?concluidos=true" >Concluidos</a> 
			<a class="btn right orange col s2" 	href="index.php?pag=novoRecurso" >Novo Recurso</a>
		</div>

                <!-- Loop para exibir os recursos -->
                <?php
                require "classes/repositorio.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados
				
				$gmail = verificarToken();
				$token = $gmail["tkn"];
				
if($gmail["status"] && $gmail["resta"] > 59){
	echo "Temos Token, válido por: ".$gmail['resta']."s<br/>";
}else{
	// echo "Não temos token Gmail!<br/>";
	// echo "Clique no Link para obter um Token!<br/>";
	// echo "É necessário o token para enviar e-mails!<br/>";
	// echo "Se abrir uma tela solicitando logar com google, logue apenas com a conta do conselho!<br/>";
	// echo "<a class='btn' target='_blank' href='/gmail/checkToken.php'>Obter Token</a>";
	include("/var/www/html/gmail/refresh.php");
}
				
                if($_GET[concluidos] == "true"){
					$sql = "SELECT r.id as recurso, r.*, f.* FROM recurso r left join fase f on f.id = r.fase where f.id = 5 order by r.data";

				}elseif(isset($_GET[resumo]) and  $_GET[resumo] == "true"){
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
										
					// dump(DBQuery($sql1));
					echo "<span id='corpoEmail'>";
					$mensagem .=  "<p>Prezados, segue relação de notificações em tratamento pelo Conselho:</p>";
					$mensagem .=   "<p><b>Em análise</b>:<br>";
					foreach(DBQuery($sql1) as $analise){
						$mensagem .=   $analise['numero']."<br>";
					}
					$mensagem .=   "</p>";
					$mensagem .=   "<p><b>Recomendamos Manter a notificação</b>:<br>";
					foreach(DBQuery($sql2) as $analise){
						$mensagem .=   $analise['numero']."<br>";
						// dump($analise);
					}
					$mensagem .=   "</p>";
					$mensagem .=   "<p><b>Recomendamos Revogar a notificação</b>:<br>";
					foreach(DBQuery($sql3) as $analise){
						$mensagem .=   $analise['numero']."<br>";
						// dump($analise);
					}
					$mensagem .=   "</p>";
					$mensagem .=   "<p><b>Recomendamos Converter a notificação em Advertência</b>:<br>";
					foreach(DBQuery($sql4) as $analise){
						$mensagem .=   $analise['numero']."<br>";
						// dump($analise);
					}
					$mensagem .=   "</p>";
					$mensagem .=   "<p>Atenciosamente,<br>Membros do Conselho Consultivo e Fiscal</p>";
						
					// echo $mensagem;
					
					echo "</span>";
					
					
					//Dados do e-mail a ser enviado:
					$assunto = "Relação de Notificações com o Conselho";
					$assunto = "=?UTF-8?B?".base64_encode($assunto)."?=";
					$destinatarios = "admcond@solucoesdf.com.br, erisvaldo.soares@solucoesdf.com.br";
					// $destinatarios = "davi.nunes@gmail.com";
					$cc = "sindicogeral.miami@gmail.com";
					// $bcc = "admcond@solucoesdf.com.br";
					
					$mime .= "Content-Type: text/html; charset=UTF-8\n";
					$mime .= "to: ".$destinatarios."\n";
					$mime .= "cc: ".$cc."\n";
					// $mime .= "bcc: ".$bcc."\n";
					$mime .= "subject: $assunto"."\n"."\n";

					$mime .= $mensagem."\n";

					
					// echo "<details><summary>Visualizar prévia do e-mail</summary>";
					echo "<pre id='mime'>";
					echo $mime;
					echo "</pre>";
					// echo "</details>";
					
					// echo "<a class='btn' id='EnviaRelatorioJuridico'>Enviar e-mail ao Juridico</a>";
				}else{
					
					$sql = "SELECT r.id as recurso, r.*, f.* FROM recurso r left join fase f on f.id = r.fase where f.id != 5 order by r.data";
				}
				
                $result = DBExecute($sql);
				
				if (isset($_GET[resumo]) and  $_GET[resumo] == "true"){
					// Faz nada;
					
				}else{
					echo '        <table class="striped" id="listaRecursos">
										<thead>
											<tr class="teal darken-2">
												<th>Número</th>
												<th>Unidade</th>
												<th>Data</th>
												<th class="center-align">&#8987;</th>
												<th>Assunto</th>
												<th>Fase</th>
												<th><i class="material-icons">filter_9_plus</i></th> 
												<th>Votos</th>
												<th>e-mail</th>
											</tr>
										</thead>
										<tbody>';
					while ($row = mysqli_fetch_assoc($result)) {
						switch($row['fase']) {
							case "1": //Novo
								$classe = "green lighten-4";
								break;
							case "2": // Falta Material
								$classe = " red lighten-5";
								break;
							case "3": // Em Análise
								$classe = "indigo accent-1";
								break;
							case "4": // Conferido Parecer
								$classe = "cyan accent-3";
								break;
							case "5": // Concluido
								$classe = "amber lighten-5";
								break;
							default:
								$classe = "black";
						}
						$votos = getVotos($row['recurso']);
						$historico = sizeof(getNotificacoes($row['unidade'] ,$row['bloco']));
						$vt = '';
							foreach($votos as $v){
								switch($v['voto']) {
								case "revogar": //Novo
									$classeVoto = "green accent-2";
									break;
								case "manter": // Falta Material
									$classeVoto = " red accent-2";
									break;
								case "converter": // Concluido
									$classeVoto = "amber accent-1";
									break;
								default:
									$classeVoto = "black";
							}
							// var_dump($v);
							// $vt .= $v[nome].": ".$v[voto]."<br/>";
							$vt .= '<div class="chip '.$classeVoto.'">';
							$vt .= '<img src="'.$v[avatar].'" alt="Contact Person">';
							$vt .= $v[voto];
							$vt .= '</div>';
							$vt .= '<br/>';
						}
						// var_dump($votos);
						echo "<tr class='recurso $classe' rec='{$row['numero']}'>";
						// echo "<td>{$row['recurso']}</td>";
						echo "<td>{$row['numero']}</td>";
						echo "<td>{$row['bloco']}{$row['unidade']}</td>";
						echo "<td>" . date('d/m/Y', strtotime($row['data'])) . "</td>";
						echo "<td class='center-align'>" . calcularDiasPassados($row['data']) . "</td>";
						echo "<td>{$row['titulo']}</td>";
						echo "<td>{$row['texto']}</td>";
						echo "<td>{$historico}</td>";
						// var_dump($row);
						echo "<td>{$vt}</td>";
						echo "<td>{$row['email']}</td>";
						echo "</tr>";
						
						
						
					}
		echo '            </tbody>        </table>';
				}
				function calcularDiasPassados($dataInformada) {
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
                ?>

		
    </div>