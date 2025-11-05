<?php
// Inclui o arquivo com as funções de banco de dados
require_once '../classes/database.php';

// --- LÓGICA DE BUSCA E FILTRO ---

// Lista de meses para os selects
$meses_pt = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// Pega os valores dos filtros da URL (via GET)
$bloco_filter = $_GET['bloco'] ?? '';
$unidade_filter = $_GET['unidade'] ?? '';
$status_filter = $_GET['status'] ?? '';
$responsabilidade_filter = $_GET['responsabilidade'] ?? '';
$agrupar_filter = $_GET['agrupar'] ?? 'total'; // --- NOVO FILTRO ---

// --- LÓGICA DE FILTRO DE DATA (RANGE) ---
$default_mes = date('n');
$default_ano = date('Y');

$mes_ini_filter = $_GET['mes_ini'] ?? $default_mes;
$ano_ini_filter = $_GET['ano_ini'] ?? $default_ano;
$mes_fim_filter = $_GET['mes_fim'] ?? $default_mes;
$ano_fim_filter = $_GET['ano_fim'] ?? $default_ano;

// Monta as datas de início e fim
$data_inicio = date('Y-m-d H:i:s', strtotime("{$ano_ini_filter}-{$mes_ini_filter}-01 00:00:00"));
// Pega o último dia do mês final
$data_fim = date('Y-m-t 23:59:59', strtotime("{$ano_fim_filter}-{$mes_fim_filter}-01"));


// --- Montagem da Cláusula WHERE ---
$where_conditions = " WHERE 1=1";
$params = [];
$types = '';

if (!empty($bloco_filter)) {
    $where_conditions .= " AND bloco = ?";
    $params[] = $bloco_filter;
    $types .= 's';
}
if (!empty($unidade_filter)) {
    $where_conditions .= " AND unidade = ?";
    $params[] = $unidade_filter;
    $types .= 's';
}
if (!empty($status_filter)) {
    $where_conditions .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if (!empty($responsabilidade_filter)) {
    if ($responsabilidade_filter === 'nenhum') {
        $where_conditions .= " AND responsabilidade IS NULL";
    } else {
        $where_conditions .= " AND responsabilidade = ?";
        $params[] = $responsabilidade_filter;
        $types .= 's';
    }
}

// --- NOVA CONDIÇÃO DE FILTRO DE DATA ---
$where_conditions .= " AND ( (abertura BETWEEN ? AND ?) OR (data_ultima_mensagem BETWEEN ? AND ?) )";
$params[] = $data_inicio;
$params[] = $data_fim;
$params[] = $data_inicio;
$params[] = $data_fim;
$types .= 'ssss';


// --- Query para o Relatório (Condicional) ---

$sql_select_fields = "";
$sql_group_by = "";

if ($agrupar_filter === 'bloco') {
    // Agrupa por bloco
    $sql_select_fields = "
        bloco,
        COUNT(id) AS total_ocorrencias,
        SUM(sindico) AS inter_sindico_total,
        SUM(sub) AS inter_sub_total,
        SUM(adm) AS inter_adm_total,
        SUM(resolvido) AS total_resolvido,
        SUM(CASE WHEN responsabilidade = 'sindico' THEN 1 ELSE 0 END) AS resp_sindico_total,
        SUM(CASE WHEN responsabilidade = 'sub' THEN 1 ELSE 0 END) AS resp_sub_total,
        SUM(CASE WHEN responsabilidade = 'sindico' AND sindico = 1 THEN 1 ELSE 0 END) AS inter_sindico_na_sua_resp,
        SUM(CASE WHEN responsabilidade = 'sub' AND sub = 1 THEN 1 ELSE 0 END) AS inter_sub_na_sua_resp,
        SUM(CASE WHEN responsabilidade = 'sindico' AND sub = 1 THEN 1 ELSE 0 END) AS inter_sub_na_resp_sindico,
        SUM(CASE WHEN responsabilidade = 'sub' AND sindico = 1 THEN 1 ELSE 0 END) AS inter_sindico_na_resp_sub
    ";
    $sql_group_by = "GROUP BY bloco ORDER BY bloco";
} else {
    // Total Geral
    $sql_select_fields = "
        COUNT(id) AS total_ocorrencias,
        SUM(sindico) AS inter_sindico_total,
        SUM(sub) AS inter_sub_total,
        SUM(adm) AS inter_adm_total,
        SUM(resolvido) AS total_resolvido,
        SUM(CASE WHEN responsabilidade = 'sindico' THEN 1 ELSE 0 END) AS resp_sindico_total,
        SUM(CASE WHEN responsabilidade = 'sub' THEN 1 ELSE 0 END) AS resp_sub_total,
        SUM(CASE WHEN responsabilidade = 'sindico' AND sindico = 1 THEN 1 ELSE 0 END) AS inter_sindico_na_sua_resp,
        SUM(CASE WHEN responsabilidade = 'sub' AND sub = 1 THEN 1 ELSE 0 END) AS inter_sub_na_sua_resp,
        SUM(CASE WHEN responsabilidade = 'sindico' AND sub = 1 THEN 1 ELSE 0 END) AS inter_sub_na_resp_sindico,
        SUM(CASE WHEN responsabilidade = 'sub' AND sindico = 1 THEN 1 ELSE 0 END) AS inter_sindico_na_resp_sub
    ";
    $sql_group_by = ""; // Sem group by para total geral
}

$sql_query = "SELECT $sql_select_fields FROM ocorrencias $where_conditions $sql_group_by";


$link = null;
$dados_relatorio = [];

try {
    $link = DBConnect();

    $stmt_geral = mysqli_prepare($link, $sql_query);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt_geral, $types, ...$params);
    }
    mysqli_stmt_execute($stmt_geral);
    $result_geral = mysqli_stmt_get_result($stmt_geral);
    
    while ($row = mysqli_fetch_assoc($result_geral)) {
         $dados_relatorio[] = $row;
    }
    mysqli_stmt_close($stmt_geral);

} catch (Exception $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
} finally {
    if ($link) {
        DBClose($link);
    }
}

// --- GERAÇÃO DO TEXTO DINÂMICO ---
$periodo_label = "de " . $meses_pt[(int)$mes_ini_filter] . "/" . $ano_ini_filter . " até " . $meses_pt[(int)$mes_fim_filter] . "/" . $ano_fim_filter;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Quantitativo de Ocorrências</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <div class="container mx-auto p-4 sm:p-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Relatório Quantitativo</h1>

                <!-- Formulário de Filtros -->
                <form action="quantitativos.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    
                    <!-- Filtros de Data Inicial -->
                    <fieldset class="col-span-1 sm:col-span-2 lg:col-span-1 p-3 border rounded-md">
                        <legend class="text-sm font-medium text-gray-700 px-1">Data Inicial</legend>
                        <div class="flex gap-2">
                            <select name="mes_ini" class="mt-1 block w-1/2 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <?php foreach ($meses_pt as $num => $nome): ?>
                                    <option value="<?= $num ?>" <?= $mes_ini_filter == $num ? 'selected' : '' ?>><?= substr($nome, 0, 3) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="ano_ini" class="mt-1 block w-1/2 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <?php for ($ano = date('Y'); $ano >= date('Y') - 5; $ano--): ?>
                                    <option value="<?= $ano ?>" <?= $ano_ini_filter == $ano ? 'selected' : '' ?>><?= $ano ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </fieldset>
                    
                    <!-- Filtros de Data Final -->
                     <fieldset class="col-span-1 sm:col-span-2 lg:col-span-1 p-3 border rounded-md">
                        <legend class="text-sm font-medium text-gray-700 px-1">Data Final</legend>
                        <div class="flex gap-2">
                            <select name="mes_fim" class="mt-1 block w-1/2 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <?php foreach ($meses_pt as $num => $nome): ?>
                                    <option value="<?= $num ?>" <?= $mes_fim_filter == $num ? 'selected' : '' ?>><?= substr($nome, 0, 3) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="ano_fim" class="mt-1 block w-1/2 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <?php for ($ano = date('Y'); $ano >= date('Y') - 5; $ano--): ?>
                                    <option value="<?= $ano ?>" <?= $ano_fim_filter == $ano ? 'selected' : '' ?>><?= $ano ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </fieldset>

                    <!-- Outros Filtros -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-3 col-span-1 sm:col-span-2 lg:col-span-1 gap-4">
                        <!-- NOVO FILTRO DE AGRUPAMENTO -->
                        <div class="col-span-2 sm:col-span-4 lg:col-span-3">
                            <label for="agrupar" class="block text-sm font-medium text-gray-700">Agrupamento</label>
                            <select name="agrupar" id="agrupar" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="total" <?= $agrupar_filter == 'total' ? 'selected' : '' ?>>Total Geral</option>
                                <option value="bloco" <?= $agrupar_filter == 'bloco' ? 'selected' : '' ?>>Por Bloco</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="bloco" class="block text-sm font-medium text-gray-700">Bloco</label>
                            <select name="bloco" id="bloco" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Todos</option>
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'Z'] as $b): ?>
                                    <option value="<?= $b ?>" <?= $bloco_filter == $b ? 'selected' : '' ?>><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="unidade" class="block text-sm font-medium text-gray-700">Unidade</label>
                            <input type="text" name="unidade" id="unidade" value="<?= htmlspecialchars($unidade_filter) ?>" class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" placeholder="Ex: 101">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Todos</D>
                                <option value="Aberto" <?= $status_filter == 'Aberto' ? 'selected' : '' ?>>Aberto</option>
                                <option value="Em andamento" <?= $status_filter == 'Em andamento' ? 'selected' : '' ?>>Em andamento</option>
                                <option value="Fechado" <?= $status_filter == 'Fechado' ? 'selected' : '' ?>>Fechado</option>
                            </select>
                        </div>
                        <div>
                            <label for="responsabilidade" class="block text-sm font-medium text-gray-700">Responsável</label>
                            <select name="responsabilidade" id="responsabilidade" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Todos</option>
                                <option value="sindico" <?= $responsabilidade_filter == 'sindico' ? 'selected' : '' ?>>Síndico</option>
                                <option value="sub" <?= $responsabilidade_filter == 'sub' ? 'selected' : '' ?>>Subsíndico</option>
                                <option value="nenhum" <?= $responsabilidade_filter == 'nenhum' ? 'selected' : '' ?>>Não classificado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botão de Filtro -->
                    <div class="col-span-1 sm:col-span-2 lg:col-span-1 self-end">
                        <button type="submit" class="w-full h-10 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filtrar
                        </button>
                    </div>
                </form>

                <!-- Tabela Matriz -->
                <div class="border-t border-gray-200 pt-6">
                    <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4">Matriz Quantitativa</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <!-- NOVA COLUNA DE CABEÇALHO DA TABELA -->
                                    <th rowspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                                        <?= $agrupar_filter === 'bloco' ? 'Bloco' : 'Agrupamento' ?>
                                    </th>
                                    <th rowspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">Total</th>
                                    <th colspan="3" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-gray-100">Participação</th>
                                    <th colspan="2" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-gray-200">Responsabilidade</th>
                                    <th rowspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">Considerado Resolvido</th>
                                    <th rowspan="2" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tratado pelo Responsável</th>
                                </tr>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-gray-100">Síndico</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-gray-100">Subsíndico</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-gray-100">Administração</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-gray-200">Síndico</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-gray-200">Subsíndico</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($dados_relatorio) || ($agrupar_filter === 'total' && empty($dados_relatorio[0]['total_ocorrencias']))): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Nenhum dado encontrado com os filtros selecionados.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($dados_relatorio as $data): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-700 border-r">
                                            <?= $agrupar_filter === 'bloco' ? htmlspecialchars($data['bloco']) : 'Total Geral' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 border-r"><?= $data['total_ocorrencias'] ?></td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 border-r bg-gray-50"><?= $data['inter_sindico_total'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 border-r bg-gray-50"><?= $data['inter_sub_total'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 border-r bg-gray-50"><?= $data['inter_adm_total'] ?></td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 border-r bg-gray-100"><?= $data['resp_sindico_total'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 border-r bg-gray-100"><?= $data['resp_sub_total'] ?></td>
                                        
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 border-r"><?= $data['total_resolvido'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $data['inter_sindico_na_sua_resp'] + $data['inter_sub_na_sua_resp'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Relatório Descritivo -->
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4">Relatório Descritivo</h2>
                    
                    <?php if (empty($dados_relatorio) || ($agrupar_filter === 'total' && empty($dados_relatorio[0]['total_ocorrencias']))): ?>
                        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                            <p class="text-gray-700 leading-relaxed text-base sm:text-lg">
                                Nenhum dado encontrado com os filtros selecionados para gerar o relatório.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($dados_relatorio as $data): ?>
                            <?php
                                // --- GERAÇÃO DO TEXTO DINÂMICO ---
                                
                                if ($agrupar_filter === 'bloco') {
                                    $bloco_label = "o bloco " . htmlspecialchars($data['bloco']);
                                    $texto_relatorio = "<b class='text-lg text-indigo-700'>Bloco " . htmlspecialchars($data['bloco']) . ":</b> Em auditoria, constatou-se que no período $periodo_label, ";
                                } else {
                                    $bloco_label = "todos os blocos";
                                    if (!empty($bloco_filter)) {
                                        $bloco_label = "o bloco " . htmlspecialchars($bloco_filter);
                                    }
                                    if (!empty($unidade_filter)) {
                                        $bloco_label .= " (unidade " . htmlspecialchars($unidade_filter) . ")";
                                    }
                                    $texto_relatorio = "Em auditoria, constatou-se que no período $periodo_label, para $bloco_label, ";
                                }

                                $texto_relatorio .= "foram registradas {$data['total_ocorrencias']} ocorrências, das quais, ";
                                $texto_relatorio .= "{$data['inter_sub_total']} foram movimentadas pelo subsíndico, ";
                                $texto_relatorio .= "{$data['inter_sindico_total']} foram movimentadas pelo síndico, e ";
                                $texto_relatorio .= "{$data['inter_adm_total']} foram movimentadas por colaboradores (administração). ";
                                
                                $texto_relatorio .= "Ainda, {$data['resp_sindico_total']} eram demandas do síndico e {$data['resp_sub_total']} eram demandas do subsíndico. ";
                                
                                $texto_relatorio .= "Dessas demandas, o síndico interagiu com {$data['inter_sindico_na_sua_resp']} das {$data['resp_sindico_total']} que lhe competiam, ";
                                $texto_relatorio .= "e o subsíndico interagiu com {$data['inter_sub_na_sua_resp']} das {$data['resp_sub_total']} que lhe competiam. ";

                                if ($data['inter_sub_na_resp_sindico'] > 0) {
                                    $plural = $data['inter_sub_na_resp_sindico'] > 1 ? 'demandas' : 'demanda';
                                    $texto_relatorio .= "Destaca-se que o subsíndico ainda auxiliou no andamento de {$data['inter_sub_na_resp_sindico']} $plural que competiam ao síndico. ";
                                }
                                if ($data['inter_sindico_na_resp_sub'] > 0) {
                                    $plural = $data['inter_sindico_na_resp_sub'] > 1 ? 'demandas' : 'demanda';
                                    $texto_relatorio .= "O síndico, por sua vez, auxiliou em {$data['inter_sindico_na_resp_sub']} $plural de responsabilidade do subsíndico. ";
                                }

                                $texto_relatorio .= "Este conselho considerou que, do total filtrado, {$data['total_resolvido']} ocorrências foram resolvidas, por terem alcançado o objetivo ou por não se ter mais o que relatar no chamado.";
                            ?>
                            
                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 mb-4">
                                <p class="text-gray-700 leading-relaxed text-base sm:text-lg">
                                    <?= $texto_relatorio ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>

</body>
</html>