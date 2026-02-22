<?php
// Define as ferramentas e suas categorias para facilitar a manutenção
$tools = [
    'Sincronização' => [
        ['label' => 'Importar Planilha Soluções', 'url' => 'json.php?importar_magnacom=1', 'icon' => 'grid_on', 'target' => '_self', 'color' => 'blue'],
        ['label' => 'GIT Deploy', 'url' => 'git.php', 'icon' => 'cloud_sync', 'target' => '_blank', 'color' => 'black'],
    ],
    'Segurança e Sistema' => [
        ['label' => 'Gerenciar Backups', 'url' => 'backup.php', 'icon' => 'settings_backup_restore', 'target' => '_blank', 'color' => 'teal'],
        ['label' => 'Pesquisar Regimento', 'url' => 'regimento/', 'icon' => 'search', 'target' => '_blank', 'color' => 'orange'],
    ],
    'Gmail API' => [
        ['label' => 'Gerar Novo Token', 'url' => 'gmail/getToken.php', 'icon' => 'vpn_key', 'target' => '_blank', 'color' => 'red darken-1'],
        ['label' => 'Atualizar Token', 'url' => 'gmail/refresh.php', 'icon' => 'refresh', 'target' => '_blank', 'color' => 'red darken-3'],
    ],
    'Relatórios e Dados' => [
        ['label' => 'Ocorrências Diretas', 'url' => 'ocorrenciasCondominioDigital/relatorio.php', 'icon' => 'assignment', 'target' => '_blank', 'color' => 'indigo'],
        ['label' => 'Quantitativos / Gráficos', 'url' => 'ocorrenciasCondominioDigital/quantitativos.php', 'icon' => 'insert_chart', 'target' => '_blank', 'color' => 'purple'],
    ]
];
?>

<style>
    .tool-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border-radius: 12px;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 20px !important;
        margin-bottom: 20px;
    }
    .tool-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.2) !important;
    }
    .tool-card i {
        font-size: 3rem;
        margin-bottom: 10px;
    }
    .tool-card .card-title {
        font-size: 1.1rem !important;
        font-weight: 500;
        line-height: 1.2;
    }
    .section-title {
        margin-top: 30px;
        margin-bottom: 15px;
        border-left: 5px solid #2196f3;
        padding-left: 15px;
        font-size: 1.5rem;
        color: #555;
    }
</style>

<div class="container-fluid">
    <div class="row" style="margin-top: 20px;">
        <div class="col s12">
            <h3 class="grey-text text-darken-2">Painel de Ferramentas</h3>
            <p class="grey-text">Acesse as ferramentas administrativas e utilitários do sistema.</p>
        </div>
    </div>

    <?php foreach ($tools as $category => $items): ?>
            <div class="row">
                <div class="col s12">
                    <h5 class="section-title"><?php echo $category; ?></h5>
                </div>
                <?php foreach ($items as $tool): ?>
                        <div class="col s12 m6 l3">
                            <a href="<?php echo $tool['url']; ?>" target="<?php echo $tool['target']; ?>">
                                <div class="card tool-card waves-effect waves-light <?php echo $tool['color']; ?> white-text">
                                    <i class="material-icons"><?php echo $tool['icon']; ?></i>
                                    <span class="card-title"><?php echo $tool['label']; ?></span>
                                </div>
                            </a>
                        </div>
                <?php endforeach; ?>
            </div>
    <?php endforeach; ?>
</div>