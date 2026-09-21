<?php
require_once __DIR__ . "/../classes/repositorio.php";

$msgConfigSalva = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_config_pdf') {
    $engine = $_POST['pdf_engine'] ?? 'typst';
    $template = $_POST['pdf_typst_template'] ?? 'modern';
    $exibirBanner = isset($_POST['pdf_exibir_banner']) ? '1' : '0';
    
    upsertConfigSistema('pdf_engine', $engine);
    upsertConfigSistema('pdf_typst_template', $template);
    upsertConfigSistema('pdf_exibir_banner', $exibirBanner);
    
    $msgConfigSalva = "Configurações de emissão de PDF atualizadas com sucesso!";
}

$currEngine = getConfigSistema('pdf_engine') ?: 'typst';
$currTemplate = getConfigSistema('pdf_typst_template') ?: 'modern';
$currBanner = getConfigSistema('pdf_exibir_banner');
$isBannerChecked = ($currBanner === null || $currBanner === '' || $currBanner === '1');

// Define as ferramentas e suas categorias para facilitar a manutenção
$tools = [
    'Recursos e Pareceres' => [
        ['label' => 'Recursos Sem E-mail', 'url' => 'recursosSemEmail.php', 'icon' => 'warning', 'target' => '_self', 'color' => 'deep-orange'],
        ['label' => 'Sincronizar Enviados', 'url' => 'finalizarPareceresEnviados.php', 'icon' => 'done_all', 'target' => '_self', 'color' => 'green darken-2'],
        ['label' => 'Ambiente de Testes Typst 5050', 'url' => 'palco/test_typst.php', 'icon' => 'picture_as_pdf', 'target' => '_blank', 'color' => 'blue darken-3'],
    ],
    'Sincronização' => [
        ['label' => 'Importar Planilha de Multas', 'url' => 'json.php?importar_magnacom=1', 'icon' => 'grid_on', 'target' => '_self', 'color' => 'blue'],
        ['label' => 'GIT Deploy', 'url' => 'git.php', 'icon' => 'cloud_sync', 'target' => '_blank', 'color' => 'black'],
    ],
    'Segurança e Sistema' => [
        ['label' => 'Gerenciar Backups', 'url' => 'backup.php', 'icon' => 'settings_backup_restore', 'target' => '_blank', 'color' => 'teal'],
        ['label' => 'Pesquisar Regimento', 'url' => 'regimento/', 'icon' => 'search', 'target' => '_blank', 'color' => 'orange'],
    ],
    'Gmail API' => [
        ['label' => 'Gerar Novo Token', 'url' => 'gmail/getToken.php', 'icon' => 'vpn_key', 'target' => '_blank', 'color' => 'red darken-1'],
        ['label' => 'Atualizar Token', 'url' => 'gmail/refresh.php', 'icon' => 'refresh', 'target' => '_blank', 'color' => 'red darken-3'],
        ['label' => 'E-mails e Notificações', 'url' => 'index.php?pag=configuracoes_email', 'icon' => 'contact_mail', 'target' => '_self', 'color' => 'red accent-2'],
    ],
    'IA' => [
        ['label' => 'Configurações da IA', 'url' => 'index.php?pag=configuracoes_ia', 'icon' => 'psychology', 'target' => '_self', 'color' => 'purple darken-2'],
    ],
    'Condomínio Digital (VDS API v8)' => [
        ['label' => 'Livro de Ocorrências (Chat)', 'url' => 'index.php?pag=livroDeOcorrencias', 'icon' => 'question_answer', 'target' => '_self', 'color' => 'blue darken-3'],
        ['label' => 'Configurações VDS & Ultra-Login', 'url' => 'index.php?pag=configVds', 'icon' => 'settings_suggest', 'target' => '_self', 'color' => 'purple darken-1'],
        ['label' => 'Relatórios e Gráficos (Legado)', 'url' => 'ocorrenciasCondominioDigital/relatorio.php', 'icon' => 'insert_chart', 'target' => '_blank', 'color' => 'indigo'],
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
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2) !important;
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

<div class="container-fluid" style="padding: 0 15px;">
    <div class="row" style="margin-top: 20px;">
        <div class="col s12">
            <h3 class="grey-text text-darken-2">Painel de Ferramentas</h3>
            <p class="grey-text">Acesse as ferramentas administrativas, utilitários e configurações de PDF do sistema.</p>
        </div>
    </div>

    <!-- CARD DE CONFIGURAÇÃO DO GERADOR DE PDF -->
    <div class="row">
        <div class="col s12">
            <div class="card z-depth-1" style="border-radius: 12px; border-top: 4px solid #1976d2; background: #ffffff;">
                <div class="card-content">
                    <span class="card-title" style="font-weight: 600; display: flex; align-items: center; gap: 10px; color: #0d47a1;">
                        <i class="material-icons blue-text text-darken-2">picture_as_pdf</i>
                        Configurações da API de PDF (Gerador de Parecer)
                    </span>
                    <p class="grey-text text-darken-1" style="font-size: 0.9rem; margin-bottom: 20px;">
                        Escolha qual motor de renderização o sistema utilizará para gerar e enviar pareceres por e-mail e nos endpoints de consulta.
                    </p>

                    <?php if ($msgConfigSalva): ?>
                        <div class="card-panel green lighten-4 green-text text-darken-4" style="padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 500;">
                            <i class="material-icons left" style="margin-right: 8px;">check_circle</i>
                            <?php echo htmlspecialchars($msgConfigSalva); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?pag=tools">
                        <input type="hidden" name="action" value="salvar_config_pdf">
                        
                        <div class="row">
                            <!-- Motor da API -->
                            <div class="col s12 m4">
                                <label style="font-weight: bold; color: #334155; font-size: 0.95rem; display: block; margin-bottom: 8px;">Motor de PDF (API):</label>
                                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <p style="margin-bottom: 10px;">
                                        <label>
                                            <input name="pdf_engine" type="radio" value="typst" <?php echo $currEngine === 'typst' ? 'checked' : ''; ?> />
                                            <span style="font-weight: 600; color: #0f172a;">⚡ API Typst (Moderna - Porta 5050)</span>
                                        </label>
                                    </p>
                                    <p style="margin: 0;">
                                        <label>
                                            <input name="pdf_engine" type="radio" value="legado" <?php echo $currEngine === 'legado' ? 'checked' : ''; ?> />
                                            <span style="font-weight: 600; color: #475569;">🐢 Python FPDF (Legada - Porta 5000)</span>
                                        </label>
                                    </p>
                                </div>
                            </div>

                            <!-- Template Padrão do Typst -->
                            <div class="col s12 m4">
                                <label style="font-weight: bold; color: #334155; font-size: 0.95rem; display: block; margin-bottom: 8px;">Template Padrão do Typst (8 Opções):</label>
                                <select name="pdf_typst_template" class="browser-default" style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px; background-color: #f8fafc; font-weight: 500;">
                                    <option value="modern" <?php echo $currTemplate === 'modern' ? 'selected' : ''; ?>>Moderno (com Banner LayoutMiami.jpg)</option>
                                    <option value="classic" <?php echo $currTemplate === 'classic' ? 'selected' : ''; ?>>Clássico / Notarial (Sem Banner, Moldura Azul)</option>
                                    <option value="compact" <?php echo $currTemplate === 'compact' ? 'selected' : ''; ?>>Compacto / Executivo (Tabela Densa)</option>
                                    <option value="corporate" <?php echo $currTemplate === 'corporate' ? 'selected' : ''; ?>>Corporativo (Azul Royal Institucional)</option>
                                    <option value="minimal" <?php echo $currTemplate === 'minimal' ? 'selected' : ''; ?>>Minimalista / Clean (Design Nórdico)</option>
                                    <option value="juridico" <?php echo $currTemplate === 'juridico' ? 'selected' : ''; ?>>Jurídico Solene (Borda Dupla Cartório)</option>
                                    <option value="dark" <?php echo $currTemplate === 'dark' ? 'selected' : ''; ?>>Dark Mode (Fundo Escuro Premium)</option>
                                    <option value="editorial" <?php echo $currTemplate === 'editorial' ? 'selected' : ''; ?>>Editorial / Boletim (Verde Esmeralda)</option>
                                </select>
                            </div>

                            <!-- Checkbox Banner -->
                            <div class="col s12 m4">
                                <label style="font-weight: bold; color: #334155; font-size: 0.95rem; display: block; margin-bottom: 8px;">Exibição de Elementos:</label>
                                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <label>
                                        <input name="pdf_exibir_banner" type="checkbox" value="1" <?php echo $isBannerChecked ? 'checked' : ''; ?> />
                                        <span style="font-weight: 600; color: #0f172a;">Exibir Banner no Topo (LayoutMiami.jpg)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-bottom: 0; margin-top: 10px;">
                            <div class="col s12 right-align">
                                <a href="palco/test_typst.php" target="_blank" class="btn-flat waves-effect waves-blue blue-text" style="margin-right: 10px;">
                                    <i class="material-icons left">visibility</i>Testar Variantes em Paralelo
                                </a>
                                <button type="submit" class="btn blue darken-2 waves-effect waves-light">
                                    <i class="material-icons left">save</i>Salvar Configurações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- CARDS DE FERRAMENTAS POR CATEGORIA -->
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