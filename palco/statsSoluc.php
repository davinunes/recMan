<?php
require "classes/repositorio.php";

// Obtenha o ano atual
$anoAtual = date('Y');

// Inicialize as variáveis $anoSelecionado e $totalizacao
$anoSelecionado = isset($_POST['ano']) ? $_POST['ano'] : $anoAtual;
$totalizacao = isset($_POST['totalizacao']) ? $_POST['totalizacao'] : 'por_assunto';

// Escolha os campos de agrupamento com base na opção escolhida
$campoAgrupamento = ($totalizacao == 'por_assunto') ? 'assunto' : 'torre';

// Consulta SQL
$sql = "SELECT
            $campoAgrupamento AS chave_agrupado,
            COUNT(*) as total_por_agrupado
        FROM notificacoes
        WHERE ano = $anoSelecionado
        GROUP BY chave_agrupado
        ORDER BY $campoAgrupamento DESC";

// Executa a consulta
$result = DBExecute($sql);

// Inicializa um array para armazenar os dados formatados para o gráfico
$dadosFormatados = [];

// Loop através dos resultados da consulta
while ($row = mysqli_fetch_assoc($result)) {
    $dadosFormatados[] = [
        'name' => $row['chave_agrupado'],
        'y' => (int)$row['total_por_agrupado']
    ];
}
// dump();
?>

<div class="container">
    <!-- Adicione um formulário para escolher o ano e a totalização -->
    <form method="post" action="">
        <label for="ano">Escolha o ano:</label>
        <select name="ano" id="ano">
            <?php
            // Adicione opções para os anos de 2023 até o ano atual
            for ($ano = 2023; $ano <= $anoAtual; $ano++) {
                echo "<option value=\"$ano\"";
                if ($ano == $anoSelecionado) {
                    echo " selected";
                }
                echo ">$ano</option>";
            }
            ?>
        </select>

        <label for="totalizacao">Totalizar por:</label>
        <select name="totalizacao" id="totalizacao">
            <option value="por_assunto" <?php echo $totalizacao == 'por_assunto' ? 'selected' : ''; ?>>Por Assunto</option>
            <option value="por_torre" <?php echo $totalizacao == 'por_torre' ? 'selected' : ''; ?>>Por Torre</option>
        </select>

        <button type="submit">Filtrar</button>
    </form>

    <!-- Adicione um contêiner para o gráfico -->
    <div id="grafico" alturaIdeal="<?php echo max(sizeof($dadosFormatados) * 16, 400); ?>" style="height:500px!important;"></div>

    <!-- Adicione um script para criar o gráfico de barras -->
    <script>
        // Use PHP para injetar dados diretamente no script JavaScript
        var dados = <?php echo json_encode($dadosFormatados, JSON_PRETTY_PRINT); ?>;
		
		// Função para ajustar dinamicamente a altura da área do gráfico
        function ajustarAlturaGrafico() {
            var alturaIdeal = document.getElementById('grafico').getAttribute('alturaIdeal');
            document.getElementById('grafico').style.height = alturaIdeal + 'px';
        }

        // Chame a função ao carregar a página
        window.addEventListener('load', ajustarAlturaGrafico);

        $(document).ready(function () {
			ajustarAlturaGrafico();
		});

		function ajustarAlturaGrafico() {
			var alturaIdeal = $('#grafico').attr('alturaIdeal');
			$('#grafico').height(alturaIdeal);
		}

        // Crie o gráfico de barras usando Highcharts
        Highcharts.chart('grafico', {
            chart: {
                type: 'bar',
            },
            title: {
                text: 'Notificações ' + (<?php echo $totalizacao == 'por_assunto' ? "'por Assunto'" : "'por Torre'"; ?>) + ' (' + <?php echo $anoSelecionado; ?> + ')'
            },
            xAxis: {
                type: 'category'
            },
            yAxis: {
                title: {
                    text: 'Total de Notificações'
                }
            },
            legend: {
                enabled: false
            },
            plotOptions: {
                bar: {
                    pointPadding: 0,
                    borderWidth: 0,
                    groupPadding: 0,
                    shadow: false
                },
                series: {
                    dataLabels: {
                        enabled: true,
                        format: '{point.y}',
                        color: 'black',
                        style: {
                            fontSize: '12px'
                        }
                    }
                }
            },
            series: [{
                name: 'Agrupamento',
                colorByPoint: true,
                data: dados
            }]
        });
    </script>
</div>
