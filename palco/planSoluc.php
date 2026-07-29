<?php
require "classes/repositorio.php";
?>

<div class="container">
<div class="row">
    <form id="filtro-form" action="index.php?pag=planilhaSolucoes">
        <div class="col s12 m2">
            <label for="ano">Filtrar por Ano:</label>
            <select id="ano" name="ano">
                <option value="todos" <?php echo (!isset($_GET['ano']) || $_GET['ano'] == 'todos') ? 'selected' : ''; ?>>Todos os anos</option>
                <?php
                // Buscar anos disponíveis no banco
                $sqlAnos = "SELECT DISTINCT ano FROM notificacoes ORDER BY ano DESC";
                $resultAnos = DBExecute($sqlAnos);
                
                if (mysqli_num_rows($resultAnos) > 0) {
                    while ($rowAno = mysqli_fetch_assoc($resultAnos)) {
                        $ano = $rowAno['ano'];
                        $selected = (isset($_GET['ano']) && $_GET['ano'] == $ano) ? 'selected' : '';
                        echo "<option value='$ano' $selected>$ano</option>";
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="col s12 m2">
            <label for="status">Filtrar por Status:</label>
            <select id="status" name="status">
                <option value="todos" <?php echo (!isset($_GET['status']) || $_GET['status'] == 'todos') ? 'selected' : ''; ?>>Todos os status</option>
                <?php
                // Buscar status disponíveis no banco
                $sqlStatus = "SELECT DISTINCT status FROM notificacoes WHERE status IS NOT NULL AND status != '' ORDER BY status";
                $resultStatus = DBExecute($sqlStatus);
                
                if (mysqli_num_rows($resultStatus) > 0) {
                    while ($rowStatus = mysqli_fetch_assoc($resultStatus)) {
                        $status = htmlspecialchars($rowStatus['status']);
                        $selected = (isset($_GET['status']) && $_GET['status'] == $status) ? 'selected' : '';
                        echo "<option value='$status' $selected>$status</option>";
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="col s12 m2">
            <label for="cobrada">Multa Cobrada:</label>
            <select id="cobrada" name="cobrada">
                <option value="todos" <?php echo (!isset($_GET['cobrada']) || $_GET['cobrada'] == 'todos') ? 'selected' : ''; ?>>Todos</option>
                <option value="Sim" <?php echo (isset($_GET['cobrada']) && $_GET['cobrada'] == 'Sim') ? 'selected' : ''; ?>>Sim</option>
                <option value="Nao" <?php echo (isset($_GET['cobrada']) && $_GET['cobrada'] == 'Nao') ? 'selected' : ''; ?>>Não</option>
            </select>
        </div>

        <div class="col s12 m2">
            <label for="tipo">Filtrar por Tipo:</label>
            <select id="tipo" name="tipo">
                <option value="todos" <?php echo (!isset($_GET['tipo']) || $_GET['tipo'] == 'todos') ? 'selected' : ''; ?>>Todos os tipos</option>
                <?php
                $sqlTipos = "SELECT DISTINCT notificacao FROM notificacoes WHERE notificacao IS NOT NULL AND notificacao != '' ORDER BY notificacao";
                $resultTipos = DBExecute($sqlTipos);
                
                if (mysqli_num_rows($resultTipos) > 0) {
                    while ($rowTipo = mysqli_fetch_assoc($resultTipos)) {
                        $tipo = htmlspecialchars($rowTipo['notificacao']);
                        $selected = (isset($_GET['tipo']) && $_GET['tipo'] == $tipo) ? 'selected' : '';
                        echo "<option value='$tipo' $selected>$tipo</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="col s12 m2">
            <label for="bloco">Filtrar por Bloco:</label>
            <select id="bloco" name="bloco">
                <option value="todos" <?php echo (!isset($_GET['bloco']) || $_GET['bloco'] == 'todos') ? 'selected' : ''; ?>>Todos os blocos</option>
                <option value="A" <?php echo (isset($_GET['bloco']) && $_GET['bloco'] == 'A') ? 'selected' : ''; ?>>Bloco A</option>
                <option value="B" <?php echo (isset($_GET['bloco']) && $_GET['bloco'] == 'B') ? 'selected' : ''; ?>>Bloco B</option>
                <option value="C" <?php echo (isset($_GET['bloco']) && $_GET['bloco'] == 'C') ? 'selected' : ''; ?>>Bloco C</option>
                <option value="D" <?php echo (isset($_GET['bloco']) && $_GET['bloco'] == 'D') ? 'selected' : ''; ?>>Bloco D</option>
                <option value="E" <?php echo (isset($_GET['bloco']) && $_GET['bloco'] == 'E') ? 'selected' : ''; ?>>Bloco E</option>
                <option value="F" <?php echo (isset($_GET['bloco']) && $_GET['bloco'] == 'F') ? 'selected' : ''; ?>>Bloco F</option>
            </select>
        </div>
        
        <div class="col s12 m2">
            <input type="hidden" id="pag" name="pag" value="planilhaSolucoes">
            <button class="btn" id="aplicar-filtro">Aplicar Filtro</button>
            <a href="index.php?pag=planilhaSolucoes" class="btn red">Limpar Filtros</a>
        </div>
    </form>
</div>
</div>

<!-- Modal para inspecionar boletos da unidade -->
<div id="modal-inspecionar-boletos" class="modal modal-fixed-footer" style="width: 85%; max-height: 85%;">
    <div class="modal-content">
        <h4 style="display:flex; align-items:center; gap:10px; font-size:1.5rem; margin-top:0;">
            <i class="material-icons teal-text">receipt_long</i>
            Inspecionar Boletos VDS / Superlógica
        </h4>
        <div id="boletos-modal-subtitle" class="grey-text text-darken-1" style="margin-bottom: 15px; font-weight: 500;">
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
            <p class="grey-text" style="margin-top:15px;">Consultando API VDS e buscando faturas...</p>
        </div>

        <div id="boletos-empty" class="center-align hide" style="padding: 40px 0;">
            <i class="material-icons grey-text text-lighten-1" style="font-size: 64px;">request_quote</i>
            <p class="grey-text text-darken-1 font-weight-bold">Nenhum boleto encontrado para esta unidade no ano selecionado.</p>
        </div>

        <!-- Cards de Boletos da Unidade (Exibidos em 1º Lugar) -->
        <div id="boletos-cards-container" class="row hide"></div>

        <!-- Seção de Sugestões de Lançamento de Multas (Preenchida Assincronamente) -->
        <div id="boletos-sugestoes-container" class="hide" style="margin-top: 15px; margin-bottom: 25px;">
            <div class="card-panel amber lighten-5" style="border-left: 5px solid #ff9800; padding: 15px; border-radius: 8px;">
                <h5 style="margin-top:0; font-size: 1.2rem; font-weight: bold; color: #e65100; display:flex; align-items:center; gap:8px;">
                    <i class="material-icons">auto_awesome</i> Sugestões Automatizadas de Lançamento de Multa
                </h5>
                <p style="margin-bottom: 12px; font-size:0.9rem; color:#555;">
                    Identificamos itens de penalidade no espelho da fatura Superlógica. Clique em <b>Confirmar Lançamento</b> para registrar no sistema:
                </p>
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="striped centered" style="background:#fff; border-radius:6px; overflow:hidden; border: 1px solid #ffe0b2;">
                        <thead>
                            <tr class="amber lighten-4" style="color: #e65100; font-size: 0.85rem;">
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
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-grey btn-flat">Fechar</a>
    </div>
</div>

<!-- Modal para editar multa cobrada -->
<div id="modal-multa" class="modal">
    <div class="modal-content">
        <h4>Editar Multa Cobrada</h4>
        <div class="row">
            <div class="col s12">
                <p><strong>Notificação:</strong> <span id="modal-multa-numero"></span></p>
                <p><strong>Unidade:</strong> <span id="modal-multa-unidade"></span></p>
                <p><strong>Bloco:</strong> <span id="modal-multa-bloco"></span></p>
            </div>
            <div class="input-field col s12 m6">
                <input type="number" step="0.01" id="valor-multa" required>
                <label for="valor-multa">Valor da Multa (R$)*</label>
            </div>
            <div class="input-field col s12 m6">
                <input type="date" id="data-vencimento" required>
                <label for="data-vencimento">Data de Vencimento*</label>
            </div>
            <div class="input-field col s12 m6">
                <input type="date" id="data-pagamento">
                <label for="data-pagamento">Data de Pagamento (Opcional)</label>
            </div>
        </div>
        <small style="color: #666;">* Campos obrigatórios</small>
    </div>
    <div class="modal-footer">
        <input type="hidden" id="modal-multa-id">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
        <a href="#!" id="salvar-multa" class="waves-effect waves-green btn">Salvar</a>
    </div>
</div>

<style>
.tr-multa-cobrada {
    background-color: #fff8e1 !important; /* Dourado claro */
    border-left: 4px solid #ffd54f; /* Dourado mais forte na borda */
}

.tr-multa-cobrada:hover {
    background-color: #ffecb3 !important; /* Dourado mais escuro no hover */
}

.btn-boletos-unidade {
    padding: 0 8px;
    height: 28px;
    line-height: 28px;
    font-size: 0.8rem;
}
.btn-boletos-unidade i {
    font-size: 1.1rem;
    line-height: 28px;
}
</style>

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

// Exibir a tabela
// echo "<div class='container'>";
echo "<div class='table-responsive' style='overflow-x: auto;'>";
echo "<table class='striped' id='listaSolucoes'>";
echo "<thead>";
echo "<tr>";
    echo "<th>Ações</th>";
    echo "<th>Número</th>";
    echo "<th>Ano</th>";
    echo "<th>Unidade</th>";
    echo "<th>Bloco</th>";
    echo "<th>Data Email</th>";
    echo "<th>Data Envio</th>";
    echo "<th>Data Ocorrido</th>";
    echo "<th class='teal'>Data Ciência</th>";
    echo "<th>Notificação</th>";
    echo "<th>Status</th>";
    echo "<th>Multa Cobrada</th>";
    echo "<th>Valor</th>";
    echo "<th>Data Venc.</th>";
    echo "<th>Data Pag.</th>";
    echo "<th>Recorreu?</th>";
    echo "<th>Parecer</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

foreach($lista as $item){
    // Verifica se tem multa cobrada
    $temMulta = !empty($item['multa_cobrada']) && $item['multa_cobrada'] == 'Sim';
    $classeLinha = $temMulta ? 'tr-multa-cobrada' : '';
    
    echo "<tr class='$classeLinha' data-id='{$item['numero']}/{$item['ano']}'>";
    echo "<td class='center-align'>";
    echo "<button type='button' class='btn-small btn-floating waves-effect waves-light teal btn-inspecionar-boletos' data-bloco='{$item['torre']}' data-unidade='{$item['unidade']}' data-ano='{$item['ano']}' title='Inspecionar Boletos VDS da Unidade {$item['torre']}-{$item['unidade']}'>";
    echo "<i class='material-icons'>receipt</i>";
    echo "</button>";
    echo "</td>";
    echo "<td class='edit-multa-cobrada'>{$item['numero']}</td>";
    echo "<td>{$item['ano']}</td>";
    echo "<td class='edit-multa-cobrada'>{$item['unidade']}</td>";
    echo "<td>{$item['torre']}</td>";
    echo "<td>{$item['data_email']}</td>";
    echo "<td>{$item['data_envio']}</td>";
    echo "<td>{$item['data_ocorrido']}</td>";
    
    if (isset($item['diferenca_dias']) && $item['diferenca_dias'] < 6) {
        $prazo = "blue";
    } else {
        $prazo = "teal";
    }
        
    echo "<td class='edit-retirado $prazo' data-id='{$item['numero']}/{$item['ano']}'>{$item['dia_retirada']}</td>";
    echo "<td>{$item['notificacao']}</td>";
    echo "<td>{$item['status']}</td>";
    
    // Colunas da multa cobrada
    echo "<td class='edit-multa-cobrada'>" . (!empty($item['multa_cobrada']) ? $item['multa_cobrada'] : '') . "</td>";
    echo "<td>" . (!empty($item['valor']) ? 'R$ ' . number_format($item['valor'], 2, ',', '.') : '-') . "</td>";
    echo "<td>" . (!empty($item['data_vencimento']) ? $item['data_vencimento'] : '-') . "</td>";
    echo "<td>" . (!empty($item['data_pagamento']) ? $item['data_pagamento'] : '-') . "</td>";
    
    echo "<td>{$item['existe_recurso']}</td>";
    echo "<td class='parecer'>{$item['existe_parecer']}</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</div>";
// echo "</div>";

