<?php
// Inclui o arquivo com as funções de banco de dados
require_once '../classes/database.php';

// --- LÓGICA DE BUSCA E FILTRO ---

// Pega os valores dos filtros da URL (via GET)
$bloco_filter = $_GET['bloco'] ?? '';
$unidade_filter = $_GET['unidade'] ?? ''; // <-- NOVO FILTRO
$status_filter = $_GET['status'] ?? '';
$responsabilidade_filter = $_GET['responsabilidade'] ?? '';

// Define o filtro de mês/ano, com o padrão sendo o mês e ano atuais
$default_mes = date('n');
$default_ano = date('Y');
$mes_filter = $_GET['mes'] ?? $default_mes;
$ano_filter = $_GET['ano'] ?? $default_ano;


// --- Montagem da Cláusula WHERE ---
$where_conditions = " WHERE 1=1";
$params = [];
$types = '';

if (!empty($bloco_filter)) {
    $where_conditions .= " AND bloco = ?";
    $params[] = $bloco_filter;
    $types .= 's';
}

// --- LÓGICA DO NOVO FILTRO DE UNIDADE ---
if (!empty($unidade_filter)) {
    $where_conditions .= " AND unidade = ?";
    $params[] = $unidade_filter;
    $types .= 's';
}

if ($status_filter === 'resolvido') {
    $where_conditions .= " AND resolvido = 1";
} elseif ($status_filter === 'nao_resolvido') {
    $where_conditions .= " AND resolvido = 0";
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

// Filtro de Mês e Ano
if (!empty($mes_filter)) {
    $where_conditions .= " AND (MONTH(abertura) = ? OR MONTH(data_ultima_mensagem) = ?)";
    $params[] = $mes_filter;
    $params[] = $mes_filter;
    $types .= 'ii';
}
if (!empty($ano_filter)) {
    $where_conditions .= " AND (YEAR(abertura) = ? OR YEAR(data_ultima_mensagem) = ?)";
    $params[] = $ano_filter;
    $params[] = $ano_filter;
    $types .= 'ii';
}

// --- Query Principal ---
$sql = "SELECT * FROM ocorrencias
        $where_conditions
        ORDER BY abertura DESC";

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
    die("Erro ao buscar dados: ". " " . $e->getMessage());
} finally {
    if ($link) {
        DBClose($link);
    }
}

// Lista de meses para o select
$meses_pt = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Relatório de Ocorrências</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <div class="container mx-auto p-4 sm:p-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Relatório de Ocorrências</h1>

                <!-- Formulário de Filtros -->
                <!-- Ajustado para 6 colunas em telas grandes -->
                <form action="relatorio.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-4 mb-6">
                    <div>
                        <label for="mes" class="block text-sm font-medium text-gray-700">Mês</label>
                        <select name="mes" id="mes" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <?php foreach ($meses_pt as $num => $nome): ?>
                                <option value="<?= $num ?>" <?= $mes_filter == $num ? 'selected' : '' ?>><?= $nome ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="ano" class="block text-sm font-medium text-gray-700">Ano</label>
                        <select name="ano" id="ano" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <?php for ($ano = date('Y'); $ano >= date('Y') - 5; $ano--): ?>
                                <option value="<?= $ano ?>" <?= $ano_filter == $ano ? 'selected' : '' ?>><?= $ano ?></option>
                            <?php endfor; ?>
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
                    
                    <!-- NOVO FILTRO DE UNIDADE -->
                    <div>
                        <label for="unidade" class="block text-sm font-medium text-gray-700">Unidade</label>
                        <input type="text" name="unidade" id="unidade" value="<?= htmlspecialchars($unidade_filter) ?>" class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" placeholder="Ex: 101">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <option value="nao_resolvido" <?= $status_filter == 'nao_resolvido' ? 'selected' : '' ?>>Não Resolvidos</option>
                            <option value="resolvido" <?= $status_filter == 'resolvido' ? 'selected' : '' ?>>Resolvidos</option>
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

                    <div class="self-end">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
            <a class="btn" href="https://www.toplifemiamibeach.com.br/area_restrita.aspx?id=w/wGkJW3ORpOcMRvFjzENg==&p1=a971d0e2-c12f-4d92-8359-947c67e11e14">Link pra logar no sistema</a>
            <!-- Tabela de Resultados (Layout de Lista) -->
            <div class="overflow-x-auto">
                <div class="min-w-full">
                    <!-- Cabeçalho da Lista -->
                    <div class="hidden lg:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-t border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="col-span-1">ID</div>
                        <div class="col-span-2">Abertura</div>
                        <div class="col-span-1">Unidade</div>
                        <div class="col-span-1">Responsável</div>
                        <div class="col-span-1">Msgs</div>
                        <div class="col-span-2">Última Mensagem</div>
                        <div class="col-span-3">Participantes</div>
                        <div class="col-span-1 text-center">Link</div>
                    </div>

                    <!-- Corpo da Lista -->
                    <div class="bg-white">
                        <?php if (empty($ocorrencias)): ?>
                            <div class="px-6 py-4 text-center text-sm text-gray-500">
                                Nenhuma ocorrência encontrada com os filtros selecionados.
                            </div>
                        <?php else: ?>
                            <?php foreach ($ocorrencias as $ocorrencia): ?>
                                <!-- Linha da Tabela -->
                                <?php $resolvidoClass = $ocorrencia['resolvido'] ? 'bg-green-50' : ''; ?>
                                <div class="grid grid-cols-2 lg:grid-cols-12 gap-4 px-6 py-4 border-b border-gray-200 items-center text-sm <?= $resolvidoClass ?> hover:bg-gray-50">
                                    
                                    <!-- ID -->
                                    <div class="col-span-1 lg:col-span-1">
                                        <span class="lg:hidden font-bold">ID: </span>
                                        <span class="font-medium text-gray-900"><?= htmlspecialchars($ocorrencia['id']) ?></span>
                                    </div>
                                    
                                    <!-- Abertura -->
                                    <div class="col-span-1 lg:col-span-2">
                                        <span class="lg:hidden font-bold">Abertura: </span>
                                        <span class="text-gray-700"><?= (new DateTime($ocorrencia['abertura']))->format('d/m/Y H:i') ?></span>
                                    </div>

                                    <!-- Unidade -->
                                    <div class="col-span-1 lg:col-span-1">
                                        <span class="lg:hidden font-bold">Unidade: </span>
                                        <span class="font-medium text-gray-900"><?= htmlspecialchars($ocorrencia['unidade']) ?> <?= htmlspecialchars($ocorrencia['bloco']) ?></span>
                                    </div>
                                    
                                    <!-- Responsável -->
                                    <div class="col-span-1 lg:col-span-1">
                                        <span class="lg:hidden font-bold">Respons.: </span>
                                        <?php if ($ocorrencia['responsabilidade'] === 'sindico'): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800">Síndico</span>
                                        <?php elseif ($ocorrencia['responsabilidade'] === 'sub'): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Subsíndico</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Total Mensagens -->
                                    <div class="col-span-1 lg:col-span-1">
                                        <span class="lg:hidden font-bold">Msgs: </span>
                                        <span class="text-gray-700"><?= htmlspecialchars($ocorrencia['total_mensagens']) ?></span>
                                    </div>

                                    <!-- Última Mensagem -->
                                    <div class="col-span-1 lg:col-span-2">
                                        <span class="lg:hidden font-bold">Últ. Msg: </span>
                                        <span class="text-gray-700">
                                            <?= $ocorrencia['data_ultima_mensagem'] ? (new DateTime($ocorrencia['data_ultima_mensagem']))->format('d/m/Y H:i') : 'N/A' ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Participantes -->
                                    <div class="col-span-2 lg:col-span-3 flex flex-wrap gap-2 items-center">
                                        <span class="lg:hidden font-bold">Particip.: </span>
                                        <?php if ($ocorrencia['sindico']): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800">Síndico</span>
                                        <?php endif; ?>
                                        <?php if ($ocorrencia['sub']): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Subsíndico</span>
                                        <?php endif; ?>
                                        <?php if ($ocorrencia['adm']): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-200 text-gray-800">Adm</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Link -->
                                    <div class="col-span-1 lg:col-span-1 text-left lg:text-center">
                                        <a href="<?= htmlspecialchars($ocorrencia['url']) ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900" title="Abrir em nova aba">
                                            <!-- Ícone SVG para Link -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

</body>
</html>

