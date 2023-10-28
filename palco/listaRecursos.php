<div class="container">
        <h3>Recursos em Andamento</h3>
		
		<a  href="?concluidos=true" class="btn left">Concluidos</a> 
		<a  href="?concluidos=false" class="btn left blue">Em aberto</a>
		<a  href="?concluidos=false&resumo=true" class="btn left blue">Resumo</a>
		<a  href="index.php?pag=novoRecurso" class="btn right">Novo Recurso</a>

                <!-- Loop para exibir os recursos -->
                <?php
                require "classes/repositorio.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados
                if($_GET[concluidos] == "true"){
					$sql = "SELECT r.id as recurso, r.*, f.* FROM recurso r left join fase f on f.id = r.fase where f.id = 5 order by r.data";

				}elseif(isset($_GET[resumo]) and  $_GET[resumo] == "true"){
					$sql = "SELECT r.id as recurso, r.*, f.* FROM recurso r left join fase f on f.id = r.fase where f.id = 4 order by r.data";
				}else{
					
					$sql = "SELECT r.id as recurso, r.*, f.* FROM recurso r left join fase f on f.id = r.fase where f.id != 5 order by r.data";
				}
                $result = DBExecute($sql);
				if (isset($_GET[resumo]) and  $_GET[resumo] == "true"){
					
					echo '<button class="btn" data-clipboard-target="#sql">Copiar para colar</button>
					<table id="sql" class="striped">
										<thead>
											<tr>
												<th>unidade</th>
												<th>numero</th>
												<th>ano</th>
												<th>email</th>
												<th>emailsub</th>
												<th>data</th>
												<th>gerar</th> 
												<th>arts</th>
												<th>fechamento</th>
												<th>assunto</th>
												<th>notificação</th>
												<th>analise</th>
												<th>conclusão</th>
											</tr>
										</thead>
										<tbody>';
					while ($row = mysqli_fetch_assoc($result)) {

						$votos = getVotos($row['recurso']);
						$vt = '';
						foreach($votos as $v){
							$vt .= $v[voto]." ";
						}
						// unidade, numero, ano, email, emailsub,data, gerar, arts, fechamento, assunto, notificação, analise, conclusão 33626336 27 7h manha
						$unidade = explode("/",$row['numero']);
						// var_dump($unidade);
						echo "<tr>";
						echo "<td>{$row['bloco']}{$row['unidade']}</td>";
						echo "<td>{$unidade[0]}</td>";
						echo "<td>{$unidade[1]}</td>";
						echo "<td>{$row['email']}</td>";
						echo "<td></td>";
						echo "<td></td>";
						echo "<td>$vt</td>";
						echo "<td>{$row['artigo']}</td>";
						echo "<td></td>";
						echo "<td>{$row['titulo']}</td>";
						echo "<td></td>";
						echo "<td></td>";
						echo "<td></td>";
						echo "</tr>";
						
						
						
					}
				echo "
					<script>
						new ClipboardJS('.btn');
					</script>
				";
				}else{
					echo '        <table class="striped">
										<thead>
											<tr>
												<th>Número</th>
												<th>Unidade</th>
												<th>Data</th>
												<th>Dias</th>
												<th>Assunto</th>
												<th>Fase</th>
												<th>#H</th> 
												<th>Votos</th>
												<th>mail</th>
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
						echo "<td>" . calcularDiasPassados($row['data']) . "</td>";
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