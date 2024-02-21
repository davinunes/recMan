<?php
require "classes/repositorio.php";

?>

<div class="container">
<div class="row">
    <form id="filtro-form" action="index.php?pag=planilhaSolucoes">
        <div class="col s12 m2">
            <label for="coluna-selecionada">Selecionar Coluna:</label>
            <select id="coluna-selecionada" name="coluna-selecionada">
                <option value="dia_retirada" <?php echo (isset($_GET['coluna-selecionada']) && $_GET['coluna-selecionada'] == 'dia_retirada') ? 'selected' : ''; ?>>Retirado pela Unidade</option>
                <option value="data_email" <?php echo (isset($_GET['coluna-selecionada']) && $_GET['coluna-selecionada'] == 'data_email') ? 'selected' : ''; ?>>E-mail recebido </option>
                <option value="data_envio" <?php echo (isset($_GET['coluna-selecionada']) && $_GET['coluna-selecionada'] == 'data_envio') ? 'selected' : ''; ?>>Notificação enviada</option>
                <option value="data_ocorrido" <?php echo (isset($_GET['coluna-selecionada']) && $_GET['coluna-selecionada'] == 'data_ocorrido') ? 'selected' : ''; ?>>Fato ocorrido</option>
                <!-- Adicione outras opções conforme necessário -->
            </select>
        </div>
        <div class="col s12 m2">
            <label for="data-inicio">Data de Início:</label>
            <input type="date" id="data-inicio" name="data-inicio" <?php echo isset($_GET['data-inicio']) ? 'value="' . $_GET['data-inicio'] . '"' : ''; ?>>
        </div>
        <div class="col s12 m2">
            <label for="data-fim">Data de Fim:</label>
            <input type="date" id="data-fim" name="data-fim" <?php echo isset($_GET['data-fim']) ? 'value="' . $_GET['data-fim'] . '"' : ''; ?>>
            <input type="hidden" id="pag" name="pag" value="planilhaSolucoes">
        </div>
<?php
	$sqlTipos = "SELECT DISTINCT notificacao FROM notificacoes";
    $resultTipos = DBExecute($sqlTipos);
	$tiposDisponiveis = array();

    if (mysqli_num_rows($resultTipos) > 0) {
        while ($rowTipo = mysqli_fetch_assoc($resultTipos)) {
            $tiposDisponiveis[] = $rowTipo['notificacao'];
        }
    }
	
	$tipoSelecionado = isset($_GET['tipo']) ? $_GET['tipo'] : 'tudo';

function gerarOpcoesDinamicas($tiposDisponiveis, $tipoSelecionado = "tudo") {
    echo "<option value='tudo' " . ($tipoSelecionado == 'tudo' ? 'selected' : '') . ">Tudo</option>";

    foreach ($tiposDisponiveis as $tipo) {
		$tipo = ($tipo === null) ? "NULL" : htmlspecialchars($tipo);
        $selected = ($tipo == $tipoSelecionado || ($tipo === null && $tipoSelecionado === null)) ? 'selected' : '';
        $tipoLabel = ($tipo === null) ? 'Sem Notificação' : $tipo;
        echo "<option value='$tipo' $selected>$tipoLabel</option>";
    }
}

?>
        <div class="col s12 m2">
            <label for="tipo">Selecionar Tipo de notificação:</label>
            <select id="tipo" name="tipo">
                <?php gerarOpcoesDinamicas($tiposDisponiveis, $tipoSelecionado); ?>
            </select>
        </div>
        <div class="col s12 m2">
            <button class="btn" id="aplicar-filtro">Aplicar Filtro</button>
        </div>
    </form>
</div>
</div>



<?php

if(isset($_GET['data-inicio']) && isset($_GET['data-fim']) && isset($_GET['coluna-selecionada']) && isset($_GET['tipo'])) {
	$dataInicio = $_GET['data-inicio'];
    $dataFim = $_GET['data-fim'];
    $colunaSelecionada = $_GET['coluna-selecionada'];
    $tipo = $_GET['tipo'];
	$lista = getNotificacoesByDateWithStatus($dataInicio, $dataFim, $colunaSelecionada, $tipo);
}else{
	$lista = getAllNotificacoes();
	
}
echo "<div class='container'>";
echo "<table class='striped' id='listaSolucoes'>";
echo "<thead>";
echo "<tr>";
	echo "<th>";
		echo "numero";
	echo "</th>";
	echo "<th>";
		echo "ano";
	echo "</th>";
	echo "<th>";
		echo "unidade";
	echo "</th>";
	echo "<th>";
		echo "bloco";
	echo "</th>";
	echo "<th>";
		echo "DataEmail";
	echo "</th>";
	echo "<th>";
		echo "DataEnvio";
	echo "</th>";
	echo "<th>";
		echo "DataOcorrido";
	echo "</th>";
	echo "<th class='teal'>";
		echo "DataCiência";
	echo "</th>";
	// echo "<th>";
		// echo "Assunto";
	// echo "</th>";
	echo "<th>";
		echo "Notificação";
	echo "</th>";
	// echo "<th>";
		// echo "Cobrança";
	// echo "</th>";
	echo "<th>";
		echo "Status";
	echo "</th>";
	// echo "<th>";
		// echo "Observação";
	// echo "</th>";
	echo "<th>";
		echo "Recorreu?";
	echo "</th>";
	echo "<th>";
		echo "Parecer";
	echo "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
foreach($lista as $item){
	echo "<tr data-id='{$item['numero']}/{$item['ano']}'>";
		echo "<td>";
			echo $item[numero];
		echo "</td>";
		echo "<td>";
			echo $item[ano];
		echo "</td>";
		echo "<td>";
			echo $item[unidade];
		echo "</td>";
		echo "<td>";
			echo $item[torre];
		echo "</td>";
		echo "<td>";
			echo $item[data_email];
		echo "</td>";
		echo "<td>";
			echo $item[data_envio];
		echo "</td>";
		echo "<td>";
			echo $item[data_ocorrido];
		echo "</td>";
		
		if (isset($item['diferenca_dias']) && $item['diferenca_dias'] < 6) {
			$prazo = "Blue";
		} else {
			$prazo = "Teal";
		}
			
		echo "<td class='edit-retirado $prazo' data-id='{$item['numero']}/{$item['ano']}'>{$item['dia_retirada']}</td>";
		// echo "<td>";
			// echo $item[assunto];
		// echo "</td>";
		echo "<td>";
			echo $item[notificacao];
		echo "</td>";
		// echo "<td>";
			// echo $item[cobranca];
			// echo $item[cobranca];
		// echo "</td>";
		echo "<td>";
			echo $item[status];
		echo "</td>";
		// echo "<td>";
			// echo $item[obs];
		// echo "</td>";
		echo "<td>";
			echo $item[existe_recurso];
		echo "</td>";
		echo "<td>";
			echo $item[existe_parecer];
		echo "</td>";

	echo "</tr>";
}
echo "</tbody>";
echo "</table>";
echo "</div>";
// dump($lista);

?>