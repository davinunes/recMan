<div class="container">
        <h3>Recursos em Andamento</h3><a  href="index.php?pag=novoRecurso" class="btn right">Novo Recurso</a>
        <table class="striped">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Unidade</th>
                    <th>Data</th>
                    <th>Dias</th>
                    <th>Assunto</th>
                    <th>Fase</th>
                    <th>Votos</th>
                </tr>
            </thead>
            <tbody>
                <!-- Loop para exibir os recursos -->
                <?php
                require "classes/repositorio.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados
                $sql = "SELECT r.id as recurso, r.*, f.* FROM recurso r left join fase f on f.id = r.fase where f.id != 5 order by r.data";
                $result = DBExecute($sql);
                while ($row = mysqli_fetch_assoc($result)) {
					$votos = getVotos($row['recurso']);
					$vt = '';
					foreach($votos as $v){
						// $vt .= $v[nome].": ".$v[voto]."<br/>";
						$vt .= '<div class="chip">';
						$vt .= '<img src="'.$v[avatar].'" alt="Contact Person">';
						$vt .= $v[voto];
						$vt .= '</div>';
						$vt .= '<br/>';
					}
					// var_dump($votos);
                    echo "<tr class='recurso' rec='{$row['numero']}'>";
                    // echo "<td>{$row['recurso']}</td>";
                    echo "<td>{$row['numero']}</td>";
                    echo "<td>{$row['bloco']}{$row['unidade']}</td>";
                    echo "<td>" . date('d/m/Y', strtotime($row['data'])) . "</td>";
                    echo "<td>" . calcularDiasPassados($row['data']) . "</td>";
                    echo "<td>{$row['titulo']}</td>";
                    echo "<td>{$row['texto']}</td>";
                    echo "<td>{$vt}</td>";
                    // echo "<td>{$row['email']}</td>";
                    echo "</tr>";
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
            </tbody>
        </table>
		
    </div>