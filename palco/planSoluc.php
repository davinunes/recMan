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

