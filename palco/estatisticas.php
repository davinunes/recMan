<?php
require "classes/repositorio.php";

// Obter os parâmetros da URL ou usar valores padrão
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');

// Obter estatísticas com base nos parâmetros
$resumo = getEstatisticas($mes, $ano);

// Formatar os dados para o formato aceito pelo Highcharts
$dadosFormatados = [];
foreach ($resumo as $item) {
    $dadosFormatados[] = [
        'name' => $item['conclusao'],
        'y' => (int)$item['total_pareceres'],
        'dataLabels' => [
            'enabled' => true,
            'format' => '{point.name}: {point.y}',
            'color' => 'black',
            'y' => -15
        ]
    ];
}

// Gerar as opções para o input do tipo ano (de 2023 até o ano atual)
$anos = range(2023, date('Y'));

// Gerar as opções para o input do tipo mês (por extenso)
$meses = [
    0 => 'Todos',
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];
?>

<div class="container">

<form>
	<div class="row">
		<div class="col s12 m6">
		<label for="selectAno">Escolha o ano:</label>
		<select id="selectAno" name="ano">
			<?php foreach ($anos as $anoOpcao): ?>
				<option value="<?php echo $anoOpcao; ?>" <?php echo ($anoOpcao == $ano) ? 'selected' : ''; ?>><?php echo $anoOpcao; ?></option>
			<?php endforeach; ?>
		</select>
		</div>

		<div class="col s12 m6">
		<label for="selectMes">Escolha o mês:</label>
		<select id="selectMes" name="mes">
			<?php foreach ($meses as $numMes => $nomeMes): ?>
				<option value="<?php echo $numMes; ?>" <?php echo ($numMes == $mes) ? 'selected' : ''; ?>><?php echo $nomeMes; ?></option>
			<?php endforeach; ?>
		</select>
		</div>
		
		<div class="col s12">
		<button type="button" onclick="atualizarGrafico()">Atualizar</button>
		</div>
	</div>
</form>

<div id="grafico"></div>

<script>
function atualizarGrafico() {
    var ano = document.getElementById('selectAno').value;
    var mes = document.getElementById('selectMes').value;

    // Redirecionar para a URL formatada
    window.location.href = 'index.php?pag=estatisticas&mes=' + mes + '&ano=' + ano;
}
</script>

<script>
// Usar PHP para injetar dados diretamente no script JavaScript
var dados = <?php echo json_encode($dadosFormatados); ?>;

// Criar o gráfico de colunas usando Highcharts
Highcharts.chart('grafico', {
  chart: {
    type: 'column'
  },
  title: {
    text: 'Pareceres emitidos pelo Conselho Fiscal'
  },
  xAxis: {
    type: 'category'
  },
  yAxis: {
    title: {
      text: 'Total de Pareceres'
    }
  },
  legend: {
    enabled: false
  },
  plotOptions: {
    series: {
      borderWidth: 0,
      dataLabels: {
        enabled: true
      }
    }
  },
  series: [{
    name: 'Conclusões',
    colorByPoint: true,
    data: dados
  }]
});
</script>

<!-- Adicionar uma tabela para exibir dados em formato tabular -->
<table border="1">
    <thead>
        <tr>
            <th>Conclusão</th>
            <th>Total de Pareceres</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resumo as $item): ?>
            <tr>
                <td><?php echo $item['conclusao']; ?></td>
                <td><?php echo $item['total_pareceres']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</div>
