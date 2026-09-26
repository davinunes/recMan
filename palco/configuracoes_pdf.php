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
?>

<div class="container" style="margin-top: 20px;">
    <div class="row">
        <div class="col s12">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h4><i class="material-icons left blue-text text-darken-2" style="font-size: 2.5rem;">picture_as_pdf</i>Configurações da API de PDF</h4>
                    <p class="grey-text">Gerencie o motor de renderização, escolha o template visual do Typst e configure elementos dos pareceres.</p>
                </div>
                <div>
                    <a href="index.php?pag=tools" class="btn-flat waves-effect waves-blue" style="border: 1px solid #cbd5e1; border-radius: 8px;">
                        <i class="material-icons left">arrow_back</i>Voltar para Ferramentas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($msgConfigSalva): ?>
        <div class="row">
            <div class="col s12">
                <div class="card-panel green lighten-4 green-text text-darken-4" style="padding: 14px; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 10px;">
                    <i class="material-icons">check_circle</i>
                    <?php echo htmlspecialchars($msgConfigSalva); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col s12">
            <div class="card z-depth-1" style="border-radius: 12px; border-top: 4px solid #1976d2; background: #ffffff;">
                <div class="card-content">
                    <span class="card-title" style="font-weight: 600; display: flex; align-items: center; gap: 10px; color: #0d47a1; margin-bottom: 20px;">
                        <i class="material-icons blue-text text-darken-2">tune</i>
                        Parâmetros do Gerador de Parecer em PDF
                    </span>

                    <form method="POST" action="index.php?pag=configuracoes_pdf">
                        <input type="hidden" name="action" value="salvar_config_pdf">
                        
                        <div class="row">
                            <!-- Motor da API -->
                            <div class="col s12 m4">
                                <label style="font-weight: bold; color: #334155; font-size: 0.95rem; display: block; margin-bottom: 8px;">Motor de PDF (API):</label>
                                <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <p style="margin-bottom: 12px;">
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
                                <label style="font-weight: bold; color: #334155; font-size: 0.95rem; display: block; margin-bottom: 8px;">Template Padrão Typst (11 Opções):</label>
                                <select name="pdf_typst_template" class="browser-default" style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px; background-color: #f8fafc; font-weight: 500; height: auto;">
                                    <option value="modern" <?php echo $currTemplate === 'modern' ? 'selected' : ''; ?>>Moderno (com Banner LayoutMiami.jpg)</option>
                                    <option value="classic" <?php echo $currTemplate === 'classic' ? 'selected' : ''; ?>>Clássico / Notarial (Sem Banner, Moldura Azul)</option>
                                    <option value="compact" <?php echo $currTemplate === 'compact' ? 'selected' : ''; ?>>Compacto / Executivo (Tabela Densa)</option>
                                    <option value="corporate" <?php echo $currTemplate === 'corporate' ? 'selected' : ''; ?>>Corporativo (Azul Royal Institucional)</option>
                                    <option value="minimal" <?php echo $currTemplate === 'minimal' ? 'selected' : ''; ?>>Minimalista / Clean (Design Nórdico)</option>
                                    <option value="juridico" <?php echo $currTemplate === 'juridico' ? 'selected' : ''; ?>>Jurídico Solene (Borda Dupla Cartório)</option>
                                    <option value="dark" <?php echo $currTemplate === 'dark' ? 'selected' : ''; ?>>Dark Mode (Fundo Escuro Premium)</option>
                                    <option value="editorial" <?php echo $currTemplate === 'editorial' ? 'selected' : ''; ?>>Editorial / Boletim (Verde Esmeralda)</option>
                                    <option value="top_header" <?php echo $currTemplate === 'top_header' ? 'selected' : ''; ?>>Topo 1ª Página (lay-top-fist-page-1.png)</option>
                                    <option value="watermark_a4" <?php echo $currTemplate === 'watermark_a4' ? 'selected' : ''; ?>>Marca d'Água Full A4 (lay-body-all-pages-1.png)</option>
                                    <option value="margem_moderna" <?php echo $currTemplate === 'margem_moderna' ? 'selected' : ''; ?>>Marca d'Água Margem Moderna (lay-body-all-pages-2.png)</option>
                                </select>
                            </div>

                            <!-- Checkbox Banner -->
                            <div class="col s12 m4">
                                <label style="font-weight: bold; color: #334155; font-size: 0.95rem; display: block; margin-bottom: 8px;">Exibição de Elementos:</label>
                                <div style="background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <label>
                                        <input name="pdf_exibir_banner" type="checkbox" value="1" <?php echo $isBannerChecked ? 'checked' : ''; ?> />
                                        <span style="font-weight: 600; color: #0f172a;">Exibir Banner no Topo (LayoutMiami.jpg)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-bottom: 0; margin-top: 15px;">
                            <div class="col s12 right-align">
                                <a href="palco/test_typst.php" target="_blank" class="btn-flat waves-effect waves-blue blue-text" style="margin-right: 10px;">
                                    <i class="material-icons left">science</i>Testar Variantes em Paralelo
                                </a>
                                <button type="submit" class="btn blue darken-2 waves-effect waves-light" style="border-radius: 6px;">
                                    <i class="material-icons left">save</i>Salvar Configurações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
