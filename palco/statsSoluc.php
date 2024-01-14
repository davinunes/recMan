<?php
require "classes/repositorio.php";

// Obtenha o ano atual
$anoAtual = date('Y');

// Inicialize a variável $anoSelecionado
$anoSelecionado = isset($_POST['ano']) ? $_POST['ano'] : $anoAtual;

// Consulta SQL
$sql = "SELECT
            CASE
                WHEN assunto IS NULL THEN 'Não Especificado'
                ELSE assunto
            END AS assunto_agrupado,
            COUNT(*) as total_por_assunto
        FROM notificacoes
        WHERE ano = $anoSelecionado
        GROUP BY assunto_agrupado
        ORDER BY total_por_assunto DESC LIMIT 15";

// Executa a consulta
$result = DBExecute($sql);

// Inicializa um array para armazenar os dados formatados para o gráfico
$dadosFormatados = [];

// Loop através dos resultados da consulta
while ($row = mysqli_fetch_assoc($result)) {
    $dadosFormatados[] = [
        'name' => $row['assunto_agrupado'],
        'y' => (int)$row['total_por_assunto']
    ];
}
?>

<div class="container">
    <!-- Adicione um formulário para escolher o ano -->
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
        <button type="submit">Filtrar</button>
    </form>

    <!-- Adicione um contêiner para o gráfico -->
    <div id="grafico"></div>

    <!-- Adicione um script para criar o gráfico de barras -->
    <script>
        // Use PHP para injetar dados diretamente no script JavaScript
        var dados = <?php echo json_encode($dadosFormatados, JSON_PRETTY_PRINT); ?>;

        // Função para ajustar dinamicamente a altura da área do gráfico
        function ajustarAlturaGrafico() {
            var alturaJanela = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
            var alturaDesejada = Math.max(400, alturaJanela - 200); // Altura mínima de 400, ajuste conforme necessário
            document.getElementById('grafico').style.height = alturaDesejada + 'px';
        }

        // Chame a função ao carregar a página e redimensionar a janela
        window.addEventListener('load', ajustarAlturaGrafico);
        window.addEventListener('resize', ajustarAlturaGrafico);

        // Crie o gráfico de barras usando Highcharts
        Highcharts.chart('grafico', {
            chart: {
                type: 'bar',
                scrollablePlotArea: {
                    minHeight: 600 // Ajuste conforme necessário
                }
            },
            title: {
                text: 'Top 15 motivos das notificações (' + <?php echo $anoSelecionado; ?> + ')'
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
                name: 'Assuntos',
                colorByPoint: true,
                data: dados
            }]
        });
    </script>
</div>
