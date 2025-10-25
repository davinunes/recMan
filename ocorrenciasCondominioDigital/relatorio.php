<?php
// Inclui o arquivo com as funções de banco de dados
require_once '../classes/database.php';

// --- LÓGICA DE BUSCA E FILTRO ---

// Pega os valores dos filtros da URL (via GET)
$bloco_filter = $_GET['bloco'] ?? '';
$resolvido_filter = $_GET['resolvido'] ?? ''; // Filtro para resolvido (1) ou não resolvido (0)
$responsabilidade_filter = $_GET['responsabilidade'] ?? ''; // <-- NOVO FILTRO
$mes_filter = $_GET['mes'] ?? date('n'); // Padrão: mês atual
$ano_filter = $_GET['ano'] ?? date('Y'); // Padrão: ano atual

// --- Montagem da Cláusula WHERE ---
$where_conditions = " WHERE 1=1";
$params = [];
$types = '';

if (!empty($bloco_filter)) {
    $where_conditions .= " AND bloco = ?";
    $params[] = $bloco_filter;
    $types .= 's';
}
// Filtra pela coluna booleana 'resolvido'
if ($resolvido_filter !== '') {
    $where_conditions .= " AND resolvido = ?";
    $params[] = $resolvido_filter;
    $types .= 'i';
}

// <-- LÓGICA DO NOVO FILTRO -->
if (!empty($responsabilidade_filter)) {
    if ($responsabilidade_filter === 'nenhum') {
        $where_conditions .= " AND responsabilidade IS NULL";
    } else {
        $where_conditions .= " AND responsabilidade = ?";
        $params[] = $responsabilidade_filter;
        $types .= 's';
    }
}
// <-- FIM DA NOVA LÓGICA -->

// --- LÓGICA DE FILTRO DE DATA ATUALIZADA ---
// Removemos o bloco if anterior
$abertura_conditions = [];
$abertura_params = [];
$abertura_types = '';

$ult_msg_conditions = [];
$ult_msg_params = [];
$ult_msg_types = '';

if (!empty($ano_filter)) {
    $abertura_conditions[] = "YEAR(abertura) = ?";
    $abertura_params[] = $ano_filter;
    $abertura_types .= 'i';

    $ult_msg_conditions[] = "YEAR(data_ultima_mensagem) = ?";
    $ult_msg_params[] = $ano_filter;
    $ult_msg_types .= 'i';
}

if (!empty($mes_filter)) {
    $abertura_conditions[] = "MONTH(abertura) = ?";
    $abertura_params[] = $mes_filter;
    $abertura_types .= 'i';

    $ult_msg_conditions[] = "MONTH(data_ultima_mensagem) = ?";
    $ult_msg_params[] = $mes_filter;
    $ult_msg_types .= 'i';
}

// Se houver qualquer filtro de data (mês ou ano)
if (!empty($abertura_conditions)) {
    $abertura_sql = "(" . implode(' AND ', $abertura_conditions) . ")";
    $ult_msg_sql = "(" . implode(' AND ', $ult_msg_conditions) . ")";
    
    $where_conditions .= " AND ($abertura_sql OR $ult_msg_sql)";
    
    $params = array_merge($params, $abertura_params, $ult_msg_params);
    $types .= $abertura_types . $ult_msg_types;
}
// --- FIM DA LÓGICA DE FILTRO DE DATA ---


// --- Query para buscar os dados ---
$sql = "SELECT * FROM ocorrencias $where_conditions ORDER BY abertura DESC";

$link = null;
$ocorrencias = [];

try {
    $link = DBConnect();
    $stmt = mysqli_prepare($link, $sql);

    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $ocorrencias[] = $row;
    }
    
    mysqli_stmt_close($stmt);

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
    <title>Relatório de Ocorrências</title>
	
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <div class="container mx-auto p-4 sm:p-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Relatório de Ocorrências</h1>
				<a href="https://www.toplifemiamibeach.com.br/area_restrita.aspx?id=w/wGkJW3ORpOcMRvFjzENg==&p1=a971d0e2-c12f-4d92-8359-947c67e11e14">Logar no Condominio Digital para funcionar os links das ocorrencias</a>

                <!-- Formulário de Filtros -->
                <form action="relatorio.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
                    <div>
                        <label for="mes" class="block text-sm font-medium text-gray-700">Mês</label>
                        <select name="mes" id="mes" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="" <?= $mes_filter === '' ? 'selected' : '' ?>>Todos</option>
                            <?php
                                $meses_pt = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
                                foreach ($meses_pt as $num => $nome) {
                                    echo '<option value="' . $num . '"' . ($mes_filter == $num ? ' selected' : '') . '>' . $nome . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                     <div>
                        <label for="ano" class="block text-sm font-medium text-gray-700">Ano</label>
                        <select name="ano" id="ano" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="" <?= $ano_filter === '' ? 'selected' : '' ?>>Todos</option>
                            <?php
                                for ($a = date('Y'); $a >= date('Y') - 5; $a--) {
                                    echo '<option value="' . $a . '"' . ($ano_filter == $a ? ' selected' : '') . '>' . $a . '</option>';
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
                        <label for="resolvido" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="resolvido" id="resolvido" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <option value="1" <?= $resolvido_filter === '1' ? 'selected' : '' ?>>Resolvidos</option>
                            <option value="0" <?= $resolvido_filter === '0' ? 'selected' : '' ?>>Não Resolvidos</option>
                        </select>
                    </div>
                    <!-- NOVO FILTRO DE RESPONSÁVEL -->
                    <div>
                        <label for="responsabilidade" class="block text-sm font-medium text-gray-700">Responsável</label>
                        <select name="responsabilidade" id="responsabilidade" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <option value="sindico" <?= $responsabilidade_filter === 'sindico' ? 'selected' : '' ?>>Síndico</option>
                            <option value="sub" <?= $responsabilidade_filter === 'sub' ? 'selected' : '' ?>>Subsíndico</option>
                            <option value="nenhum" <?= $responsabilidade_filter === 'nenhum' ? 'selected' : '' ?>>Não classificado</option>
                        </select>
                    </div>
                    <div class="self-end">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filtrar
                        </button>
                    </div>
                </form>

                <!-- Cabeçalho do Relatório -->
                <div class="hidden lg:flex bg-gray-50 text-xs text-gray-500 uppercase font-semibold p-3 rounded-t-lg border-b">
                    <div class="w-1/12">ID</div>
                    <div class="w-2/12">Abertura</div>
                    <div class="w-1/12">Unidade</div>
                    <div class="w-2/12">Responsável</div>
                    <div class="w-1/12 text-center">Msgs</div>
                    <div class="w-2/12">Última Mensagem</div>
                    <div class="w-2/12">Participantes</div>
                    <div class="w-1/12 text-center">URL</div>
                </div>

                <!-- Corpo do Relatório -->
                <div class="divide-y divide-gray-200">
                    <?php if (empty($ocorrencias)): ?>
                        <div class="text-center p-6 text-gray-500">
                            Nenhuma ocorrência encontrada com os filtros selecionados.
                        </div>
                    <?php else: ?>
                        <?php foreach ($ocorrencias as $ocorrencia): ?>
                            <div class="flex flex-col lg:flex-row p-3 items-start lg:items-center text-sm text-gray-700 transition-colors duration-200 <?= $ocorrencia['resolvido'] ? 'bg-green-50 hover:bg-green-100' : 'hover:bg-gray-50' ?>">
                                <div class="lg:w-1/12 font-bold"><span class="lg:hidden text-gray-500 font-semibold">ID: </span><?= htmlspecialchars($ocorrencia['id']) ?></div>
                                <div class="lg:w-2/12"><span class="lg:hidden text-gray-500 font-semibold">Abertura: </span><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ocorrencia['abertura']))) ?></div>
                                <div class="lg:w-1/12"><span class="lg:hidden text-gray-500 font-semibold">Local: </span><?= htmlspecialchars($ocorrencia['unidade']) ?> <?= htmlspecialchars($ocorrencia['bloco']) ?></div>
                                <div class="lg:w-2/12">
                                    <?php if ($ocorrencia['responsabilidade'] === 'sindico'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Síndico
                                        </span>
                                    <?php elseif ($ocorrencia['responsabilidade'] === 'sub'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                            Subsíndico
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="lg:w-1/12 text-center"><span class="lg:hidden text-gray-500 font-semibold">Mensagens: </span><?= htmlspecialchars($ocorrencia['total_mensagens']) ?></div>
                                <div class="lg:w-2/12"><span class="lg:hidden text-gray-500 font-semibold">Última Msg: </span><?= $ocorrencia['data_ultima_mensagem'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($ocorrencia['data_ultima_mensagem']))) : 'N/A' ?></div>
                                <div class="lg:w-2/12 flex space-x-2 mt-2 lg:mt-0">
                                    <?php if($ocorrencia['sindico']): ?><span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Síndico</span><?php endif; ?>
                                    <?php if($ocorrencia['sub']): ?><span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">Sub</span><?php endif; ?>
                                    <?php if($ocorrencia['adm']): ?><span class="text-xs bg-gray-200 text-gray-800 px-2 py-1 rounded">Adm</span><?php endif; ?>
                                </div>
                                <div class="lg:w-1/12 text-center mt-2 lg:mt-0">
                                    <?php if (!empty($ocorrencia['url'])): ?>
                                        <a href="<?= htmlspecialchars($ocorrencia['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-900" title="Abrir em nova aba">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
