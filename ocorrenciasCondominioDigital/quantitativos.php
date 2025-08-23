<?php
// Inclui o arquivo com as funções de banco de dados
require_once '../classes/database.php';

// --- LÓGICA DE BUSCA E FILTRO ---

// Pega os valores dos filtros da URL (via GET)
$bloco_filter = $_GET['bloco'] ?? '';
$status_filter = $_GET['status'] ?? '';
// Define o filtro de mês, com o padrão sendo o mês e ano atuais
$mes_filter = $_GET['mes'] ?? date('Y-m');

// --- Montagem da Cláusula WHERE ---
$where_conditions = " WHERE 1=1";
$params = [];
$types = '';

if (!empty($bloco_filter)) {
    $where_conditions .= " AND bloco = ?";
    $params[] = $bloco_filter;
    $types .= 's';
}
if (!empty($status_filter)) {
    $where_conditions .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if (!empty($mes_filter)) {
    list($ano, $mes) = explode('-', $mes_filter);
    $where_conditions .= " AND ((YEAR(abertura) = ? AND MONTH(abertura) = ?) OR (YEAR(data_ultima_mensagem) = ? AND MONTH(data_ultima_mensagem) = ?))";
    $params = array_merge($params, [$ano, $mes, $ano, $mes]);
    $types .= 'iiii';
}

// --- Query para dados por Bloco ---
$sql_blocos = "
    SELECT
        bloco,
        COUNT(id) AS total_ocorrencias,
        SUM(sindico) AS total_sindico,
        SUM(sub) AS total_sub,
        SUM(adm) AS total_adm,
        SUM(resolvido) AS total_resolvido
    FROM ocorrencias
    $where_conditions
    GROUP BY bloco
    ORDER BY bloco ASC";

// --- Query para o Total Geral ---
$sql_total = "
    SELECT
        COUNT(id) AS total_ocorrencias,
        SUM(sindico) AS total_sindico,
        SUM(sub) AS total_sub,
        SUM(adm) AS total_adm,
        SUM(resolvido) AS total_resolvido
    FROM ocorrencias
    $where_conditions";

$link = null;
$dados_blocos = [];
$dados_total = [];

try {
    $link = DBConnect();

    // Executa a query por blocos
    $stmt_blocos = mysqli_prepare($link, $sql_blocos);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt_blocos, $types, ...$params);
    }
    mysqli_stmt_execute($stmt_blocos);
    $result_blocos = mysqli_stmt_get_result($stmt_blocos);
    while ($row = mysqli_fetch_assoc($result_blocos)) {
        $dados_blocos[] = $row;
    }
    mysqli_stmt_close($stmt_blocos);

    // Executa a query do total geral
    $stmt_total = mysqli_prepare($link, $sql_total);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt_total, $types, ...$params);
    }
    mysqli_stmt_execute($stmt_total);
    $result_total = mysqli_stmt_get_result($stmt_total);
    $dados_total = mysqli_fetch_assoc($result_total);
    mysqli_stmt_close($stmt_total);

} catch (Exception $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
} finally {
    if ($link) {
        DBClose($link);
    }
}
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
                    <div>
                        <label for="mes" class="block text-sm font-medium text-gray-700">Mês</label>
                        <select name="mes" id="mes" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <?php
                                // --- MUDANÇA PARA GARANTIR PORTUGUÊS ---
                                $meses_pt = [
                                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                ];

                                for ($i = 0; $i <= 12; $i++) {
                                    $time = strtotime(date("Y-m-01") . " -$i months");
                                    $value = date("Y-m", $time);
                                    $mes_num = (int)date('n', $time);
                                    $ano_num = date('Y', $time);
                                    $label = $meses_pt[$mes_num] . ' de ' . $ano_num;
                                    echo '<option value="' . $value . '"' . ($mes_filter == $value ? ' selected' : '') . '>' . $label . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="bloco" class="block text-sm font-medium text-gray-700">Bloco</label>
                        <select name="bloco" id="bloco" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $b): ?>
                                <option value="<?= $b ?>" <?= $bloco_filter == $b ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <option value="Aberto" <?= $status_filter == 'Aberto' ? 'selected' : '' ?>>Aberto</option>
                            <option value="Em andamento" <?= $status_filter == 'Em andamento' ? 'selected' : '' ?>>Em andamento</option>
                            <option value="Fechado" <?= $status_filter == 'Fechado' ? 'selected' : '' ?>>Fechado</option>
                        </select>
                    </div>
                    <div class="self-end">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filtrar
                        </button>
                    </div>
                </form>

                <!-- Tabela de Resultados -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bloco</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Ocorr.</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Síndico</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sub</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Adm</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Resolvidos</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($dados_blocos)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        Nenhum dado encontrado com os filtros selecionados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dados_blocos as $bloco_data): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($bloco_data['bloco']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?= htmlspecialchars($bloco_data['total_ocorrencias']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?= htmlspecialchars($bloco_data['total_sindico']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?= htmlspecialchars($bloco_data['total_sub']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?= htmlspecialchars($bloco_data['total_adm']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"><?= htmlspecialchars($bloco_data['total_resolvido']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-gray-100">
                            <tr class="font-bold text-gray-700">
                                <td class="px-6 py-3 text-left text-sm uppercase">Total Geral</td>
                                <td class="px-6 py-3 text-center text-sm"><?= htmlspecialchars($dados_total['total_ocorrencias'] ?? 0) ?></td>
                                <td class="px-6 py-3 text-center text-sm"><?= htmlspecialchars($dados_total['total_sindico'] ?? 0) ?></td>
                                <td class="px-6 py-3 text-center text-sm"><?= htmlspecialchars($dados_total['total_sub'] ?? 0) ?></td>
                                <td class="px-6 py-3 text-center text-sm"><?= htmlspecialchars($dados_total['total_adm'] ?? 0) ?></td>
                                <td class="px-6 py-3 text-center text-sm"><?= htmlspecialchars($dados_total['total_resolvido'] ?? 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
