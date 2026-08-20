<?php
require "classes/repositorio.php";
?>

<div class="container" style="width: 95%; max-width: 1440px;">
    <!-- Painel de Filtros e Busca Rápida -->
    <div class="card z-depth-1" style="border-radius: 12px; margin-top: 15px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
        <div class="card-content" style="padding: 16px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                <h5 style="margin: 0; font-size: 1.3rem; font-weight: 700; color: #1565c0; display: flex; align-items: center; gap: 8px;">
                    <i class="material-icons">payments</i> Lista com Cobrança de Multas
                </h5>
                <button type="button" id="btn-toggle-filtros" class="btn-flat waves-effect waves-teal" style="font-weight: 600; color: #1565c0; display: flex; align-items: center; gap: 4px;">
                    <i class="material-icons">tune</i> <span id="texto-toggle-filtros">Ocultar Filtros</span>
                </button>
            </div>

            <!-- Formulário de Filtros PHP -->
            <form id="filtro-form" action="index.php?pag=planilhaSolucoes" method="GET">
                <input type="hidden" id="pag" name="pag" value="planilhaSolucoes">
                <div id="painel-filtros-body" class="row" style="margin-bottom: 0;">
                    <div class="col s12 m6 l2">
                        <label for="ano" style="font-weight: 600;">Ano:</label>
                        <select id="ano" name="ano" class="browser-default custom-select-cobranca">
                            <option value="todos" <?php echo (!isset($_GET['ano']) || $_GET['ano'] == 'todos') ? 'selected' : ''; ?>>Todos os anos</option>
                            <?php
                            $sqlAnos = "SELECT DISTINCT ano FROM notificacoes ORDER BY ano DESC";
                            $resultAnos = DBExecute($sqlAnos);
                            if (mysqli_num_rows($resultAnos) > 0) {
                                while ($rowAno = mysqli_fetch_assoc($resultAnos)) {
                                    $anoVal = $rowAno['ano'];
                                    $selected = (isset($_GET['ano']) && $_GET['ano'] == $anoVal) ? 'selected' : '';
                                    echo "<option value='$anoVal' $selected>$anoVal</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col s12 m6 l2">
                        <label for="status" style="font-weight: 600;">Status:</label>
                        <select id="status" name="status" class="browser-default custom-select-cobranca">
                            <option value="todos" <?php echo (!isset($_GET['status']) || $_GET['status'] == 'todos') ? 'selected' : ''; ?>>Todos os status</option>
                            <?php
                            $sqlStatus = "SELECT DISTINCT status FROM notificacoes WHERE status IS NOT NULL AND status != '' ORDER BY status";
                            $resultStatus = DBExecute($sqlStatus);
                            if (mysqli_num_rows($resultStatus) > 0) {
                                while ($rowStatus = mysqli_fetch_assoc($resultStatus)) {
                                    $statusVal = htmlspecialchars($rowStatus['status']);
                                    $selected = (isset($_GET['status']) && $_GET['status'] == $statusVal) ? 'selected' : '';
                                    echo "<option value='$statusVal' $selected>$statusVal</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col s12 m6 l2">
                        <label for="cobrada" style="font-weight: 600;">Multa Cobrada:</label>
                        <select id="cobrada" name="cobrada" class="browser-default custom-select-cobranca">
                            <option value="todos" <?php echo (!isset($_GET['cobrada']) || $_GET['cobrada'] == 'todos') ? 'selected' : ''; ?>>Todos</option>
                            <option value="Sim" <?php echo (isset($_GET['cobrada']) && $_GET['cobrada'] == 'Sim') ? 'selected' : ''; ?>>Sim (Cobrada)</option>
                            <option value="Nao" <?php echo (isset($_GET['cobrada']) && $_GET['cobrada'] == 'Nao') ? 'selected' : ''; ?>>Não (Pendente)</option>
                        </select>
                    </div>

                    <div class="col s12 m6 l2">
                        <label for="tipo" style="font-weight: 600;">Tipo:</label>
                        <select id="tipo" name="tipo" class="browser-default custom-select-cobranca">
                            <option value="todos" <?php echo (!isset($_GET['tipo']) || $_GET['tipo'] == 'todos') ? 'selected' : ''; ?>>Todos os tipos</option>
                            <?php
                            $sqlTipos = "SELECT DISTINCT notificacao FROM notificacoes WHERE notificacao IS NOT NULL AND notificacao != '' ORDER BY notificacao";
                            $resultTipos = DBExecute($sqlTipos);
                            if (mysqli_num_rows($resultTipos) > 0) {
                                while ($rowTipo = mysqli_fetch_assoc($resultTipos)) {
                                    $tipoVal = htmlspecialchars($rowTipo['notificacao']);
                                    $selected = (isset($_GET['tipo']) && $_GET['tipo'] == $tipoVal) ? 'selected' : '';
                                    echo "<option value='$tipoVal' $selected>$tipoVal</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col s12 m6 l2">
                        <label for="bloco" style="font-weight: 600;">Bloco:</label>
                        <select id="bloco" name="bloco" class="browser-default custom-select-cobranca">
                            <option value="todos" <?php echo (!isset($_GET['bloco']) || $_GET['bloco'] == 'todos') ? 'selected' : ''; ?>>Todos os blocos</option>
                            <?php
                            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $bl) {
                                $selected = (isset($_GET['bloco']) && $_GET['bloco'] == $bl) ? 'selected' : '';
                                echo "<option value='$bl' $selected>Bloco $bl</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col s12 m6 l2" style="display: flex; gap: 6px; align-items: flex-end; padding-top: 14px;">
                        <button type="submit" class="btn waves-effect waves-light blue darken-2" style="flex: 1; border-radius: 6px; height: 38px; line-height: 38px; padding: 0 10px;">
                            Filtrar
                        </button>
                        <a href="index.php?pag=planilhaSolucoes" class="btn red lighten-1 waves-effect waves-light" style="border-radius: 6px; height: 38px; line-height: 38px; padding: 0 10px;" title="Limpar Filtros">
                            <i class="material-icons">clear</i>
                        </a>
                    </div>
                </div>
            </form>

            <hr style="border: none; border-top: 1px solid #eeeeee; margin: 15px 0 12px 0;">

            <!-- Barra de Busca Rápida Instantânea & Estatísticas -->
            <div class="row" style="margin-bottom: 0; display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div class="col s12 m7 l8">
                    <div class="search-wrapper-cobranca" style="position: relative;">
                        <i class="material-icons grey-text" style="position: absolute; left: 12px; top: 10px; font-size: 20px;">search</i>
                        <input type="text" id="busca-rapida-cards" placeholder="Busca rápida em tempo real (ex: B-102, 2026, cobrado, deferido, R$ 250)..." style="padding-left: 40px; border-radius: 20px; border: 1px solid #cfd8dc; height: 40px; margin: 0; box-sizing: border-box; background: #f8fafc;">
                        <i class="material-icons grey-text hide" id="limpar-busca-rapida" style="position: absolute; right: 12px; top: 10px; font-size: 18px; cursor: pointer;" title="Limpar busca">close</i>
                    </div>
                </div>
                <div class="col s12 m5 l4" style="text-align: right;">
                    <div id="stats-resumo-cobrancas" style="font-size: 0.85rem; color: #546e7a; font-weight: 500;">
                        <!-- Preenchido dinamicamente via PHP / JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para inspecionar boletos da unidade -->
    <div id="modal-inspecionar-boletos" class="modal modal-fixed-footer" style="width: 90%; max-width: 1100px; max-height: 88%; border-radius: 12px;">
        <div class="modal-content" style="padding: 20px 24px;">
            <h4 style="display:flex; align-items:center; gap:10px; font-size:1.4rem; font-weight:700; margin-top:0; color:#00695c;">
                <i class="material-icons teal-text">receipt_long</i>
                Inspecionar Boletos VDS / Superlógica
            </h4>
            <div id="boletos-modal-subtitle" class="grey-text text-darken-1" style="margin-bottom: 15px; font-weight: 500; font-size: 0.95rem;">
                Carregando boletos...
            </div>

            <div id="boletos-loading" class="center-align" style="padding: 40px 0;">
                <div class="preloader-wrapper big active">
                    <div class="spinner-layer spinner-teal-only">
                        <div class="circle-clipper left"><div class="circle"></div></div>
                        <div class="gap-patch"><div class="circle"></div></div>
                        <div class="circle-clipper right"><div class="circle"></div></div>
                    </div>
                </div>
                <p class="grey-text" style="margin-top:15px; font-weight:500;">Consultando API VDS e buscando faturas...</p>
            </div>

            <div id="boletos-empty" class="center-align hide" style="padding: 40px 0;">
                <i class="material-icons grey-text text-lighten-1" style="font-size: 64px;">request_quote</i>
                <p class="grey-text text-darken-1 font-weight-bold">Nenhum boleto encontrado para esta unidade no ano selecionado.</p>
            </div>

            <!-- Seção de Sugestões de Lançamento de Multas (Exibida no Topo) -->
            <div id="boletos-sugestoes-container" class="hide" style="margin-top: 15px; margin-bottom: 25px;">
                <div class="card-panel amber lighten-5" style="border-left: 5px solid #ff9800; padding: 15px; border-radius: 8px;">
                    <h5 style="margin-top:0; font-size: 1.15rem; font-weight: bold; color: #e65100; display:flex; align-items:center; gap:8px;">
                        <i class="material-icons">auto_awesome</i> Sugestões Automatizadas de Lançamento de Multa
                    </h5>
                    <p style="margin-bottom: 12px; font-size:0.85rem; color:#555;">
                        Identificamos itens de penalidade no espelho da fatura Superlógica. Clique em <b>Confirmar Lançamento</b> para registrar no sistema:
                    </p>
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="striped centered" style="background:#fff; border-radius:6px; overflow:hidden; border: 1px solid #ffe0b2; font-size: 0.85rem;">
                            <thead>
                                <tr class="amber lighten-4" style="color: #e65100;">
                                    <th>Notificação</th>
                                    <th>Descrição no Boleto</th>
                                    <th>Valor Extrator</th>
                                    <th>Vencimento</th>
                                    <th>Status Fatura</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody id="boletos-sugestoes-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Cards de Boletos da Unidade (Exibidos Abaixo das Sugestões) -->
            <div id="boletos-cards-container" class="row hide"></div>
        </div>
        <div class="modal-footer" style="padding: 10px 20px;">
            <a href="#!" class="modal-close waves-effect waves-grey btn-flat" style="border-radius:6px;">Fechar</a>
        </div>
    </div>

    <!-- Modal para editar / lançar multa cobrada -->
    <div id="modal-multa" class="modal" style="max-width: 520px; border-radius: 12px;">
        <div class="modal-content" style="padding: 24px;">
            <h4 style="font-size: 1.35rem; font-weight: 700; margin-top: 0; color: #2e7d32; display: flex; align-items: center; gap: 8px;">
                <i class="material-icons">monetization_on</i> Registrar / Editar Cobrança
            </h4>
            
            <div class="card-panel grey lighten-4" style="padding: 12px; border-radius: 8px; margin-bottom: 18px; border: 1px solid #e0e0e0;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <div><strong>Notificação:</strong> <span id="modal-multa-numero" class="blue-text text-darken-2 font-weight-bold"></span></div>
                    <div><strong>Unidade:</strong> Bloco <span id="modal-multa-bloco" class="font-weight-bold"></span> - Apt <span id="modal-multa-unidade" class="font-weight-bold"></span></div>
                </div>
            </div>

            <div class="row" style="margin-bottom: 0;">
                <div class="input-field col s12">
                    <input type="number" step="0.01" id="valor-multa" required placeholder="0.00">
                    <label for="valor-multa" class="active">Valor da Multa (R$)*</label>
                </div>
                <div class="input-field col s12 m6">
                    <input type="date" id="data-vencimento" required>
                    <label for="data-vencimento" class="active">Data de Vencimento*</label>
                </div>
                <div class="input-field col s12 m6">
                    <input type="date" id="data-pagamento">
                    <label for="data-pagamento" class="active">Data Pagamento (Opcional)</label>
                </div>
            </div>
            <small class="grey-text">* Campos obrigatórios para registro da cobrança</small>
        </div>
        <div class="modal-footer" style="padding: 10px 20px; background: #f8fafc; border-top: 1px solid #eeeeee;">
            <input type="hidden" id="modal-multa-id">
            <input type="hidden" id="modal-multa-numero-raw">
            <input type="hidden" id="modal-multa-ano-raw">
            <a href="#!" class="modal-close waves-effect waves-red btn-flat" style="border-radius: 6px;">Cancelar</a>
            <a href="#!" id="salvar-multa" class="waves-effect waves-light btn green darken-1" style="border-radius: 6px;">
                <i class="material-icons left">check</i> Salvar Cobrança
            </a>
        </div>
    </div>

    <!-- Modal para editar data de ciência / retirada -->
    <div id="modal-data-ciencia" class="modal" style="max-width: 420px; border-radius: 12px;">
        <div class="modal-content" style="padding: 24px;">
            <h4 style="font-size: 1.25rem; font-weight: 700; margin-top: 0; color: #0288d1; display: flex; align-items: center; gap: 8px;">
                <i class="material-icons">edit_calendar</i> Ajustar Data de Ciência
            </h4>
            <p id="modal-ciencia-subtitulo" class="grey-text text-darken-1" style="font-size: 0.9rem; margin-bottom: 16px;"></p>
            <div class="input-field" style="margin-top: 20px;">
                <input type="date" id="input-nova-data-ciencia" required>
                <label for="input-nova-data-ciencia" class="active">Data de Retirada / Ciência</label>
            </div>
        </div>
        <div class="modal-footer" style="padding: 10px 20px; background: #f8fafc;">
            <input type="hidden" id="modal-ciencia-id">
            <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
            <a href="#!" id="salvar-data-ciencia" class="waves-effect waves-light btn blue darken-1">
                <i class="material-icons left">save</i> Atualizar Data
            </a>
        </div>
    </div>

    <?php
    // Lógica para buscar dados com filtros
    if (isset($_GET['ano']) || isset($_GET['status']) || isset($_GET['tipo']) || isset($_GET['bloco']) || isset($_GET['cobrada'])) {
        $ano = isset($_GET['ano']) && $_GET['ano'] != 'todos' ? $_GET['ano'] : null;
        $status = isset($_GET['status']) && $_GET['status'] != 'todos' ? $_GET['status'] : null;
        $tipo = isset($_GET['tipo']) && $_GET['tipo'] != 'todos' ? $_GET['tipo'] : null;
        $bloco = isset($_GET['bloco']) && $_GET['bloco'] != 'todos' ? $_GET['bloco'] : null;
        $multa_cobrada = isset($_GET['cobrada']) && $_GET['cobrada'] != 'todos' ? $_GET['cobrada'] : null;
        $lista = getNotificacoesByFilters($ano, $status, $tipo, $bloco, $multa_cobrada);
    } else {
        $lista = getAllNotificacoes();
    }

    $totalRegistros = count($lista);
    $totalCobrados = 0;
    $totalPendentes = 0;
    $valorTotalCobrado = 0.0;

    foreach ($lista as $it) {
        if (!empty($it['multa_cobrada']) && $it['multa_cobrada'] == 'Sim') {
            $totalCobrados++;
            if (!empty($it['valor'])) {
                $valorTotalCobrado += (float)$it['valor'];
            }
        } else {
            $totalPendentes++;
        }
    }
    ?>

    <!-- Grid de Cards Responsivos (Estilo Linha/Row Esticada em Desktop e Card no Mobile) -->
    <div id="cards-cobranca-container" class="row" style="margin-top: 10px;">
        <?php if (empty($lista)): ?>
            <div class="col s12 center-align" style="padding: 60px 0;">
                <i class="material-icons grey-text text-lighten-1" style="font-size: 72px;">folder_off</i>
                <h5 class="grey-text text-darken-1" style="font-weight: 600;">Nenhum registro encontrado</h5>
                <p class="grey-text">Tente alterar os filtros selecionados acima.</p>
                <a href="index.php?pag=planilhaSolucoes" class="btn blue darken-1 waves-effect waves-light" style="border-radius: 6px; margin-top: 10px;">
                    Limpar Filtros
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($lista as $item): 
                $temMulta = !empty($item['multa_cobrada']) && $item['multa_cobrada'] == 'Sim';
                $idNotificacao = $item['numero'] . '/' . $item['ano'];
                $cardClasseStatus = $temMulta ? 'card-status-cobrado' : 'card-status-pendente';
                
                // Tipo badge
                $tipoUpper = strtoupper($item['notificacao'] ?? '');
                $tipoBadgeClass = 'chip-tipo-outro';
                if (strpos($tipoUpper, 'MULTA') !== false) {
                    $tipoBadgeClass = 'chip-tipo-multa';
                } elseif (strpos($tipoUpper, 'NOTIF') !== false || strpos($tipoUpper, 'ADVERT') !== false) {
                    $tipoBadgeClass = 'chip-tipo-notificacao';
                }

                // Prazo ciência
                $diferencaDias = isset($item['diferenca_dias']) ? (int)$item['diferenca_dias'] : null;
                $temCiencia = !empty($item['dia_retirada']);
                
                // Valor formatado
                $valorNum = (!empty($item['valor']) && (float)$item['valor'] > 0) ? (float)$item['valor'] : 0.0;
                $valorFormatado = $valorNum > 0 ? 'R$ ' . number_format($valorNum, 2, ',', '.') : '-';

                $parecerTexto = trim($item['existe_parecer'] ?? '');

                // Dados em formato seguro para busca rápida
                $searchableText = strtolower(implode(' ', [
                    $item['numero'] ?? '',
                    $item['ano'] ?? '',
                    $idNotificacao,
                    'bloco ' . ($item['torre'] ?? ''),
                    'apt ' . ($item['unidade'] ?? ''),
                    'ap ' . ($item['unidade'] ?? ''),
                    ($item['torre'] ?? '') . '-' . ($item['unidade'] ?? ''),
                    $item['notificacao'] ?? '',
                    $item['status'] ?? '',
                    $temMulta ? 'cobrado sim paga' : 'pendente nao cobrado',
                    $item['existe_recurso'] ? 'recurso sim' : 'sem recurso',
                    $parecerTexto,
                    $valorFormatado
                ]));
            ?>
            <div class="col s12 card-cobranca-wrapper" 
                 data-id="<?php echo htmlspecialchars($idNotificacao); ?>" 
                 data-search="<?php echo htmlspecialchars($searchableText); ?>"
                 data-cobrado="<?php echo $temMulta ? '1' : '0'; ?>"
                 data-valor="<?php echo $valorNum; ?>"
                 data-parecer="<?php echo htmlspecialchars($parecerTexto); ?>">
                
                <div class="card card-cobranca-row hoverable <?php echo $cardClasseStatus; ?>">
                    
                    <!-- Coluna 1: Identificação, Unidade e Status -->
                    <div class="cobranca-col-identificacao">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="custom-chip-badge <?php echo $tipoBadgeClass; ?>">
                                <?php echo htmlspecialchars($item['notificacao'] ?: 'NOTIFICAÇÃO'); ?>
                            </span>
                            <span class="card-cobranca-num">#<?php echo htmlspecialchars($idNotificacao); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                            <div class="chip-unidade-destaque">
                                <i class="material-icons tiny">business</i>
                                <span>Bloco <b><?php echo htmlspecialchars($item['torre']); ?></b> - Apt <b><?php echo htmlspecialchars($item['unidade']); ?></b></span>
                            </div>
                            <span class="chip-status-geral" title="Status da Notificação">
                                <?php echo htmlspecialchars($item['status'] ?: 'N/A'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Coluna 2: Bloco Financeiro (Cobrança, Valor, Vencimento, Pagamento) -->
                    <div class="cobranca-col-financeiro">
                        <div class="box-financeiro-cobranca-row <?php echo $temMulta ? 'box-fin-pago' : 'box-fin-pendente'; ?>">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 4px; font-weight: 700; font-size: 0.82rem;">
                                    <?php if ($temMulta): ?>
                                        <i class="material-icons green-text text-darken-2 tiny">check_circle</i>
                                        <span class="green-text text-darken-3">Cobrada</span>
                                    <?php else: ?>
                                        <i class="material-icons amber-text text-darken-3 tiny">pending_actions</i>
                                        <span class="amber-text text-darken-4">Pendente</span>
                                    <?php endif; ?>
                                </div>
                                <div class="valor-multa-destaque <?php echo $temMulta ? 'green-text text-darken-3' : 'grey-text text-darken-1'; ?>">
                                    <?php echo $valorFormatado; ?>
                                </div>
                            </div>
                            <div style="display: flex; gap: 12px; font-size: 0.75rem; color: #555; margin-top: 3px;">
                                <div><b>Venc:</b> <span class="card-val-venc"><?php echo !empty($item['data_vencimento']) ? htmlspecialchars($item['data_vencimento']) : '-'; ?></span></div>
                                <div><b>Pagto:</b> <span class="card-val-pagto"><?php echo !empty($item['data_pagamento']) ? htmlspecialchars($item['data_pagamento']) : '-'; ?></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna 3: Prazos e Datas (Ocorrido, Envio, Ciência) -->
                    <div class="cobranca-col-datas">
                        <div class="grid-datas-cobranca-row">
                            <div class="item-data-cobranca">
                                <span class="lbl-data">Ocorrido</span>
                                <span class="val-data"><?php echo !empty($item['data_ocorrido']) ? htmlspecialchars($item['data_ocorrido']) : '-'; ?></span>
                            </div>
                            <div class="item-data-cobranca">
                                <span class="lbl-data">Envio</span>
                                <span class="val-data"><?php echo !empty($item['data_envio']) ? htmlspecialchars($item['data_envio']) : '-'; ?></span>
                            </div>
                            <div class="item-data-cobranca">
                                <span class="lbl-data">Ciência</span>
                                <span class="val-data edit-retirado-touch" data-id="<?php echo htmlspecialchars($idNotificacao); ?>" data-dia="<?php echo htmlspecialchars($item['dia_retirada'] ?? ''); ?>" title="Clique para editar data de ciência">
                                    <?php if ($temCiencia): ?>
                                        <span class="badge-ciencia <?php echo ($diferencaDias !== null && $diferencaDias < 6) ? 'badge-prazo-ok' : 'badge-prazo-long'; ?>">
                                            <?php echo htmlspecialchars($item['dia_retirada']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="grey-text text-lighten-1" style="font-size: 0.75rem;">+ Adicionar</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna 4: Jurídico (Recurso e Parecer com suporte a clique triplo) -->
                    <div class="cobranca-col-juridico">
                        <div style="font-size: 0.8rem; margin-bottom: 3px;">
                            <span class="grey-text text-darken-1">Recurso:</span>
                            <b><?php echo !empty($item['existe_recurso']) ? '<span class="blue-text text-darken-2">Sim</span>' : '<span class="grey-text">Não</span>'; ?></b>
                        </div>
                        <div>
                            <?php if (!empty($parecerTexto)): ?>
                                <span class="chip-parecer parecer click-triplo-target" data-valor="<?php echo htmlspecialchars($parecerTexto); ?>" title="Clique 3x para ocultar todos com este parecer">
                                    ⚖️ <?php echo htmlspecialchars($parecerTexto); ?>
                                </span>
                            <?php else: ?>
                                <span class="grey-text" style="font-size: 0.75rem;">Sem parecer</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Coluna 5: Ações (Boletos VDS e Lançar/Editar Cobrança) -->
                    <div class="cobranca-col-acoes">
                        <button type="button" 
                                class="btn-card-action btn-inspecionar-boletos waves-effect waves-light" 
                                data-bloco="<?php echo htmlspecialchars($item['torre']); ?>" 
                                data-unidade="<?php echo htmlspecialchars($item['unidade']); ?>" 
                                data-ano="<?php echo htmlspecialchars($item['ano']); ?>" 
                                title="Inspecionar Boletos VDS da Unidade <?php echo htmlspecialchars($item['torre'] . '-' . $item['unidade']); ?>">
                            <i class="material-icons">receipt</i> Boletos VDS
                        </button>
                        
                        <button type="button" 
                                class="btn-card-action btn-abrir-modal-cobranca waves-effect waves-light <?php echo $temMulta ? 'btn-cobranca-editar' : 'btn-cobranca-lancar'; ?>" 
                                data-id="<?php echo htmlspecialchars($idNotificacao); ?>"
                                data-numero="<?php echo htmlspecialchars($item['numero']); ?>"
                                data-ano="<?php echo htmlspecialchars($item['ano']); ?>"
                                data-unidade="<?php echo htmlspecialchars($item['unidade']); ?>"
                                data-bloco="<?php echo htmlspecialchars($item['torre']); ?>"
                                data-multa="<?php echo htmlspecialchars($item['multa_cobrada'] ?? ''); ?>"
                                data-valor="<?php echo htmlspecialchars($item['valor'] ?? ''); ?>"
                                data-vencimento="<?php echo htmlspecialchars($item['data_vencimento'] ?? ''); ?>"
                                data-pagamento="<?php echo htmlspecialchars($item['data_pagamento'] ?? ''); ?>"
                                title="<?php echo $temMulta ? 'Editar Lançamento de Cobrança' : 'Lançar Cobrança de Multa'; ?>">
                            <i class="material-icons"><?php echo $temMulta ? 'edit' : 'attach_money'; ?></i> 
                            <?php echo $temMulta ? 'Editar' : 'Lançar'; ?>
                        </button>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Estilos Específicos para a Visualização em Row-Cards Responsivos */
.custom-select-cobranca {
    display: block;
    width: 100%;
    height: 38px;
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #cfd8dc;
    background-color: #fff;
    font-size: 0.9rem;
    color: #37474f;
    outline: none;
    transition: border-color 0.2s;
}
.custom-select-cobranca:focus {
    border-color: #1565c0;
}

/* Card em Formato de Linha Esticada (Row-Card) */
.card-cobranca-row {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    background: #ffffff;
    margin-bottom: 10px;
    padding: 10px 16px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.card-cobranca-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.08);
    background: #fafbfc;
}

.card-status-cobrado {
    border-left: 5px solid #2e7d32 !important;
}

.card-status-pendente {
    border-left: 5px solid #f59e0b !important;
}

/* Colunas do Row-Card */
.cobranca-col-identificacao {
    flex: 1.2;
    min-width: 180px;
}

.cobranca-col-financeiro {
    flex: 1.4;
    min-width: 210px;
}

.cobranca-col-datas {
    flex: 1.3;
    min-width: 200px;
}

.cobranca-col-juridico {
    flex: 1;
    min-width: 130px;
}

.cobranca-col-acoes {
    display: flex;
    gap: 8px;
    align-items: center;
    justify-content: flex-end;
    min-width: 220px;
}

.card-cobranca-num {
    font-weight: 700;
    font-size: 0.95rem;
    color: #1e293b;
}

.custom-chip-badge {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.chip-tipo-multa {
    background: #fee2e2;
    color: #b91c1c;
}

.chip-tipo-notificacao {
    background: #fef3c7;
    color: #b45309;
}

.chip-tipo-outro {
    background: #e2e8f0;
    color: #475569;
}

.chip-unidade-destaque {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #e0f2fe;
    color: #0369a1;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.chip-status-geral {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
}

.box-financeiro-cobranca-row {
    padding: 6px 10px;
    border-radius: 6px;
}

.box-fin-pago {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.box-fin-pendente {
    background: #fffbeb;
    border: 1px solid #fef3c7;
}

.valor-multa-destaque {
    font-size: 1rem;
    font-weight: 800;
}

.grid-datas-cobranca-row {
    display: flex;
    gap: 12px;
    background: #fafafa;
    border-radius: 6px;
    padding: 6px 10px;
    border: 1px solid #f0f0f0;
}

.item-data-cobranca {
    display: flex;
    flex-direction: column;
}

.lbl-data {
    font-size: 0.65rem;
    color: #78909c;
    text-transform: uppercase;
    font-weight: 600;
}

.val-data {
    font-size: 0.78rem;
    font-weight: 600;
    color: #37474f;
}

.badge-ciencia {
    display: inline-block;
    padding: 1px 4px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.badge-prazo-ok {
    background: #e0f2fe;
    color: #0284c7;
}

.badge-prazo-long {
    background: #f0fdf4;
    color: #16a34a;
}

.chip-parecer {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
    background: #f3e8ff;
    color: #7e22ce;
    cursor: pointer;
    user-select: none;
    transition: transform 0.1s, opacity 0.1s;
}

.chip-parecer:active {
    transform: scale(0.96);
    opacity: 0.8;
}

.btn-card-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border: none;
    border-radius: 6px;
    height: 34px;
    line-height: 34px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.2s, box-shadow 0.2s;
    padding: 0 10px;
    outline: none;
}

.btn-card-action i {
    font-size: 1rem;
}

.btn-inspecionar-boletos {
    background: #00897b;
    color: #ffffff;
}
.btn-inspecionar-boletos:hover {
    background: #00796b;
}

.btn-cobranca-lancar {
    background: #f59e0b;
    color: #ffffff;
}
.btn-cobranca-lancar:hover {
    background: #d97706;
}

.btn-cobranca-editar {
    background: #2e7d32;
    color: #ffffff;
}
.btn-cobranca-editar:hover {
    background: #1b5e20;
}

.edit-retirado-touch {
    cursor: pointer;
}
.edit-retirado-touch:hover {
    opacity: 0.8;
}

/* Responsividade Mobile (< 992px) */
@media (max-width: 992px) {
    .card-cobranca-row {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding: 14px;
    }
    .cobranca-col-identificacao,
    .cobranca-col-financeiro,
    .cobranca-col-datas,
    .cobranca-col-juridico,
    .cobranca-col-acoes {
        min-width: 100%;
        width: 100%;
    }
    .cobranca-col-acoes {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 4px;
    }
    .btn-card-action {
        width: 100%;
        height: 38px;
        line-height: 38px;
        font-size: 0.8rem;
    }
    .grid-datas-cobranca-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
    }
}
</style>

<script>
// Atualizar estatísticas de resumo dinamicamente
(function() {
    window.recalcularResumoCobranca = function() {
        var total = 0;
        var cobrados = 0;
        var pendentes = 0;
        var valorTotalCobrado = 0.0;

        $('.card-cobranca-wrapper:visible').each(function() {
            total++;
            var isCobrado = $(this).attr('data-cobrado') === '1';
            if (isCobrado) {
                cobrados++;
                var v = parseFloat($(this).attr('data-valor') || 0);
                if (!isNaN(v)) valorTotalCobrado += v;
            } else {
                pendentes++;
            }
        });

        var valorFmt = "R$ " + valorTotalCobrado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        var htmlStats = `<b>Total:</b> ${total} &nbsp;|&nbsp; <b class="green-text text-darken-2">Cobrados:</b> ${cobrados} (${valorFmt}) &nbsp;|&nbsp; <b class="amber-text text-darken-3">Pendentes:</b> ${pendentes}`;
        var el = document.getElementById('stats-resumo-cobrancas');
        if (el) el.innerHTML = htmlStats;
    };

    recalcularResumoCobranca();
})();
</script>


