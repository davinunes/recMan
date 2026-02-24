<?php
require "classes/repositorio.php";

// Parâmetros de filtro
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');
$mes = isset($_GET['mes']) ? $_GET['mes'] : 0; // 0 = Todos os meses

// Dados para os cards de resumo
$resumoGeral = getResumoGeral($ano);

// Dados para o gráfico de Pareceres (Recursos Analisados)
$estatisticasPareceres = getEstatisticas($mes, $ano);

// Dados para o gráfico de Notificações (Temas)
$estatisticasNotificacoes = getEstatisticasNotificacoes($ano, 'assunto');

// Formatação para Highcharts - Pareceres
$dadosPareceres = [];
foreach ($estatisticasPareceres as $item) {
    if (empty($item['conclusao']))
        continue;
    $dadosPareceres[] = [
        'name' => $item['conclusao'],
        'y' => (int) $item['total_pareceres']
    ];
}

// Formatação para Highcharts - Notificações
$dadosNotificacoesMultas = [];
$dadosNotificacoesAdvs = [];
$categoriasNotificacoes = [];
foreach ($estatisticasNotificacoes as $item) {
    if (empty($item['chave_agrupado']))
        continue;
    $categoriasNotificacoes[] = $item['chave_agrupado'];
    $dadosNotificacoesMultas[] = (int) $item['total_multas'];
    $dadosNotificacoesAdvs[] = (int) $item['total_advertencias'];
}

$anosDisponiveis = range(2023, date('Y'));
$meses = [
    0 => 'Ano Inteiro',
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

<div class="container-fluid" style="padding: 20px;">
    <!-- Cabeçalho e Filtros -->
    <div class="row valign-wrapper">
        <div class="col s12 m6">
            <h4 style="font-weight: 300; margin: 0;"><i class="material-icons left"
                    style="font-size: 2.5rem;">dashboard</i> Dashboard Executivo</h4>
        </div>
        <div class="col s12 m6">
            <div class="card-panel white z-depth-1" style="padding: 10px 20px; margin: 0;">
                <form id="formFiltroDashboard" method="GET" action="index.php">
                    <input type="hidden" name="pag" value="dashboard">
                    <div class="row" style="margin-bottom: 0;">
                        <div class="input-field col s5" style="margin-top: 5px;">
                            <select name="ano" id="selectAnoDash">
                                <?php foreach ($anosDisponiveis as $a): ?>
                                    <option value="<?php echo $a; ?>" <?php echo ($a == $ano) ? 'selected' : ''; ?>>
                                        <?php echo $a; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Ano</label>
                        </div>
                        <div class="input-field col s5" style="margin-top: 5px;">
                            <select name="mes" id="selectMesDash">
                                <?php foreach ($meses as $mIdx => $mNome): ?>
                                    <option value="<?php echo $mIdx; ?>" <?php echo ($mIdx == $mes) ? 'selected' : ''; ?>>
                                        <?php echo $mNome; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Mês (Pareceres)</label>
                        </div>
                        <div class="col s2" style="padding-top: 15px;">
                            <button type="submit" class="btn-floating blue waves-effect waves-light">
                                <i class="material-icons">refresh</i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row">
        <div class="col s12 m6 l3">
            <div class="card gradient-45deg-light-blue-cyan gradient-shadow white-text card-resumo">
                <div class="padding-4 row" style="margin-bottom: 0;">
                    <div class="col s7">
                        <i class="material-icons background-round mt-5">assignment</i>
                        <p>Recursos</p>
                    </div>
                    <div class="col s5 right-align">
                        <h5 class="mb-0 white-text">
                            <?php echo $resumoGeral['total_recursos']; ?>
                        </h5>
                        <p class="no-margin">Total</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col s12 m6 l3">
            <div class="card gradient-45deg-red-pink gradient-shadow white-text card-resumo">
                <div class="padding-4 row" style="margin-bottom: 0;">
                    <div class="col s7">
                        <i class="material-icons background-round mt-5">pending_actions</i>
                        <p>Em Aberto</p>
                    </div>
                    <div class="col s5 right-align">
                        <h5 class="mb-0 white-text">
                            <?php echo $resumoGeral['recursos_abertos']; ?>
                        </h5>
                        <p class="no-margin">Pendentes</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col s12 m6 l3">
            <div class="card gradient-45deg-amber-amber gradient-shadow white-text card-resumo">
                <div class="padding-4 row" style="margin-bottom: 0;">
                    <div class="col s7">
                        <i class="material-icons background-round mt-5">description</i>
                        <p>Pareceres</p>
                    </div>
                    <div class="col s5 right-align">
                        <h5 class="mb-0 white-text">
                            <?php echo $resumoGeral['pareceres_ano']; ?>
                        </h5>
                        <p class="no-margin">em
                            <?php echo $ano; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col s12 m6 l3">
            <div class="card gradient-45deg-green-teal gradient-shadow white-text card-resumo">
                <div class="padding-4 row" style="margin-bottom: 0;">
                    <div class="col s7">
                        <i class="material-icons background-round mt-5">notifications</i>
                        <p>Notificações</p>
                    </div>
                    <div class="col s5 right-align">
                        <h5 class="mb-0 white-text">
                            <?php echo $resumoGeral['notificacoes_ano']; ?>
                        </h5>
                        <p class="no-margin">em
                            <?php echo $ano; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <div class="col s12 l5">
            <div class="card z-depth-2" style="border-radius: 12px; overflow: hidden;">
                <div class="card-content">
                    <span class="card-title" style="font-weight: 400; color: #333;">Distribuição de Pareceres</span>
                    <p class="grey-text" style="font-size: 0.9rem; margin-bottom: 20px;">Análise por resultado das
                        decisões do Conselho</p>
                    <div id="chartPareceres" style="height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col s12 l7">
            <div class="card z-depth-2" style="border-radius: 12px; overflow: hidden;">
                <div class="card-content">
                    <span class="card-title" style="font-weight: 400; color: #333;">Temas Recorrentes de
                        Notificações</span>
                    <p class="grey-text" style="font-size: 0.9rem; margin-bottom: 20px;">Top 15 assuntos que geraram
                        multas e advertências em
                        <?php echo $ano; ?>
                    </p>
                    <div id="chartTemas" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card-resumo {
        padding: 10px;
        border-radius: 10px;
        min-height: 100px;
    }

    .padding-4 {
        padding: 15px !important;
    }

    .mt-5 {
        margin-top: 5px;
    }

    .mb-0 {
        margin-bottom: 0;
    }

    .no-margin {
        margin: 0;
    }

    .gradient-45deg-light-blue-cyan {
        background: linear-gradient(45deg, #0288d1 0%, #26c6da 100%);
    }

    .gradient-45deg-red-pink {
        background: linear-gradient(45deg, #ff5252 0%, #f48fb1 100%);
    }

    .gradient-45deg-amber-amber {
        background: linear-gradient(45deg, #ff6f00 0%, #ffca28 100%);
    }

    .gradient-45deg-green-teal {
        background: linear-gradient(45deg, #43a047 0%, #1de9b6 100%);
    }

    .gradient-shadow {
        box-shadow: 0 6px 20px 0 rgba(0, 0, 0, 0.19);
    }

    .background-round {
        background: rgba(255, 255, 255, 0.3);
        padding: 8px;
        border-radius: 50%;
        font-size: 2rem;
    }

    .container-fluid {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
    }
</style>

<script>
    $(document).ready(function () {
        $('select').formSelect();

        // Gráfico de Pareceres (Donut/Pizza)
        Highcharts.chart('chartPareceres', {
            chart: { type: 'pie', backgroundColor: null },
            title: { text: null },
            tooltip: { pointFormat: '<b>{point.y}</b> ({point.percentage:.1f}%)' },
            accessibility: { point: { valueSuffix: '%' } },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: { enabled: true, format: '{point.name}: {point.y}' },
                    innerSize: '50%'
                }
            },
            series: [{
                name: 'Conclusão',
                colorByPoint: true,
                data: <?php echo json_encode($dadosPareceres); ?>
            }],
            credits: { enabled: false }
        });

        // Gráfico de Temas (Barras Empilhadas)
        Highcharts.chart('chartTemas', {
            chart: { type: 'bar', backgroundColor: null },
            title: { text: null },
            xAxis: {
                categories: <?php echo json_encode($categoriasNotificacoes); ?>, title: { text: null }
            },
            yAxis: { min: 0, title: { text: 'Total', align: 'high' }, labels: { overflow: 'justify' } },
            tooltip: { valueSuffix: ' casos' },
            plotOptions: {
                bar: { dataLabels: { enabled: true }, stacking: 'normal' }
            },
            legend: { enabled: true },
            series: [{
                name: 'Multas',
                color: '#ef5350',
                data: <?php echo json_encode($dadosNotificacoesMultas); ?>
            }, {
                name: 'Advertências',
                color: '#66bb6a',
                data: <?php echo json_encode($dadosNotificacoesAdvs); ?>
            }],
            credits: { enabled: false }
        });
    });
</script>