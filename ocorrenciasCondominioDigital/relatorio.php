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
    die("Erro ao buscar dados: " . " " . $e->getMessage());
} finally {
    if ($link) {
        DBClose($link);
    }
}

// Lista de meses para o select
$meses_pt = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
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
                <form action="relatorio.php" method="GET"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-4 mb-6">
                    <div>
                        <label for="mes" class="block text-sm font-medium text-gray-700">Mês</label>
                        <select name="mes" id="mes"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <?php foreach ($meses_pt as $num => $nome): ?>
                                <option value="<?= $num ?>" <?= $mes_filter == $num ? 'selected' : '' ?>><?= $nome ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="ano" class="block text-sm font-medium text-gray-700">Ano</label>
                        <select name="ano" id="ano"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <?php for ($ano = date('Y'); $ano >= date('Y') - 5; $ano--): ?>
                                <option value="<?= $ano ?>" <?= $ano_filter == $ano ? 'selected' : '' ?>><?= $ano ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label for="bloco" class="block text-sm font-medium text-gray-700">Bloco</label>
                        <select name="bloco" id="bloco"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $b): ?>
                                <option value="<?= $b ?>" <?= $bloco_filter == $b ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- NOVO FILTRO DE UNIDADE -->
                    <div>
                        <label for="unidade" class="block text-sm font-medium text-gray-700">Unidade</label>
                        <input type="text" name="unidade" id="unidade" value="<?= htmlspecialchars($unidade_filter) ?>"
                            class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                            placeholder="Ex: 101">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <option value="nao_resolvido" <?= $status_filter == 'nao_resolvido' ? 'selected' : '' ?>>Não
                                Resolvidos</option>
                            <option value="resolvido" <?= $status_filter == 'resolvido' ? 'selected' : '' ?>>Resolvidos
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="responsabilidade"
                            class="block text-sm font-medium text-gray-700">Responsável</label>
                        <select name="responsabilidade" id="responsabilidade"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <option value="sindico" <?= $responsabilidade_filter == 'sindico' ? 'selected' : '' ?>>Síndico
                            </option>
                            <option value="sub" <?= $responsabilidade_filter == 'sub' ? 'selected' : '' ?>>Subsíndico
                            </option>
                            <option value="nenhum" <?= $responsabilidade_filter == 'nenhum' ? 'selected' : '' ?>>Não
                                classificado</option>
                        </select>
                    </div>

                    <div class="self-end">
                        <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
            <a class="btn"
                href="https://www.toplifemiamibeach.com.br/area_restrita.aspx?id=w/wGkJW3ORpOcMRvFjzENg==&p1=a971d0e2-c12f-4d92-8359-947c67e11e14">Link
                pra logar no sistema</a>
            <!-- Tabela de Resultados (Layout de Lista) -->
            <div class="overflow-x-auto">
                <div class="min-w-full">
                    <!-- Cabeçalho da Lista -->
                    <div
                        class="hidden lg:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-50 border-t border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                                <!-- Container Wrapper da Ocorrência -->
                                <?php $resolvidoClass = $ocorrencia['resolvido'] ? 'bg-green-50' : ''; ?>
                                <div
                                    class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200 <?= $resolvidoClass ?>">

                                    <!-- Linha Principal -->
                                    <div class="grid grid-cols-2 lg:grid-cols-12 gap-4 px-6 py-4 items-center text-sm">

                                        <!-- ID -->
                                        <div class="col-span-1 lg:col-span-1">
                                            <span class="lg:hidden font-bold">ID: </span>
                                            <span
                                                class="font-medium text-gray-900"><?= htmlspecialchars($ocorrencia['id']) ?></span>
                                        </div>

                                        <!-- Abertura -->
                                        <div class="col-span-1 lg:col-span-2">
                                            <span class="lg:hidden font-bold">Abertura: </span>
                                            <span
                                                class="text-gray-700"><?= (new DateTime($ocorrencia['abertura']))->format('d/m/Y H:i') ?></span>
                                        </div>

                                        <!-- Unidade -->
                                        <div class="col-span-1 lg:col-span-1">
                                            <span class="lg:hidden font-bold">Unidade: </span>
                                            <span
                                                class="font-medium text-gray-900"><?= htmlspecialchars($ocorrencia['unidade']) ?>
                                                <?= htmlspecialchars($ocorrencia['bloco']) ?></span>
                                        </div>

                                        <!-- Responsável -->
                                        <div class="col-span-1 lg:col-span-1">
                                            <span class="lg:hidden font-bold">Respons.: </span>
                                            <?php if ($ocorrencia['responsabilidade'] === 'sindico'): ?>
                                                <span
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800">Síndico</span>
                                            <?php elseif ($ocorrencia['responsabilidade'] === 'sub'): ?>
                                                <span
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Subsíndico</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Total Mensagens -->
                                        <div class="col-span-1 lg:col-span-1">
                                            <span class="lg:hidden font-bold">Msgs: </span>
                                            <span
                                                class="text-gray-700"><?= htmlspecialchars($ocorrencia['total_mensagens']) ?></span>
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
                                                <span
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800">Síndico</span>
                                            <?php endif; ?>
                                            <?php if ($ocorrencia['sub']): ?>
                                                <span
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Subsíndico</span>
                                            <?php endif; ?>
                                            <?php if ($ocorrencia['adm']): ?>
                                                <span
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-200 text-gray-800">Adm</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Botão de Toggle -->
                                        <div class="col-span-1 lg:col-span-1 text-left lg:text-center">
                                            <button type="button"
                                                onclick="toggleIframe(this, 'iframe-container-<?= $ocorrencia['id'] ?>', '<?= htmlspecialchars($ocorrencia['url']) ?>')"
                                                class="text-indigo-600 hover:text-indigo-900 focus:outline-none transition-colors"
                                                title="Abrir/Fechar Ocorrência">
                                                <!-- Ícone SVG de Chevron (Seta para baixo) -->
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-6 w-6 inline-block transition-transform duration-300 transform"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>

                                    </div>

                                    <!-- Container do Iframe -->
                                    <div id="iframe-container-<?= $ocorrencia['id'] ?>" class="hidden px-6 pb-6 w-full fade-in">
                                        <div
                                            class="bg-gray-100 p-3 rounded-lg border border-gray-300 shadow-inner mt-1 relative">
                                            <!-- Top Bar do Iframe -->
                                            <div
                                                class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gray-200 px-4 py-2 rounded-t-lg mb-2 shadow-sm border border-gray-300">
                                                <span class="text-sm font-semibold text-gray-800 mb-2 sm:mb-0">
                                                    Visualizando Ocorrência #<?= htmlspecialchars($ocorrencia['id']) ?>
                                                </span>
                                                <div class="flex items-center space-x-4">
                                                    <a href="<?= htmlspecialchars($ocorrencia['url']) ?>" target="_blank"
                                                        class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium transition-colors"
                                                        title="Abrir esta ocorrência em uma nova aba para melhor visualização">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                        Nova Aba
                                                    </a>
                                                    <button type="button"
                                                        onclick="closeIframe('iframe-container-<?= $ocorrencia['id'] ?>')"
                                                        class="inline-flex items-center justify-center p-1 rounded-full text-red-500 hover:text-red-700 hover:bg-red-100 transition-colors focus:outline-none"
                                                        title="Fechar visualização">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                            viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Iframe propriamente dito -->
                                            <div class="w-full bg-white rounded-b-lg border border-gray-300 overflow-hidden shadow-sm relative"
                                                style="height: 60vh; min-height: 400px; max-height: 800px;">
                                                <!-- Loading Placeholder que desaparece ao carregar -->
                                                <div
                                                    class="iframe-loading absolute inset-0 flex items-center justify-center bg-gray-50 z-0 text-gray-400">
                                                    <div class="flex flex-col items-center">
                                                        <svg class="animate-spin h-8 w-8 text-indigo-500 mb-2"
                                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                            </path>
                                                        </svg>
                                                        <span class="text-sm">Carregando conteúdo...</span>
                                                    </div>
                                                </div>
                                                <iframe src="" class="absolute inset-0 w-full h-full z-10 bg-white"
                                                    frameborder="0"
                                                    onload="if(this.src && this.src !== window.location.href) { this.previousElementSibling.style.display='none'; }"></iframe>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts para Manipulação da UI (Iframe Toggle) -->
    <script>
        function toggleIframe(btnElement, containerId, url) {
            const container = document.getElementById(containerId);
            const iframe = container.querySelector('iframe');
            const svgIcon = btnElement.querySelector('svg');
            const loadingSkel = container.querySelector('.iframe-loading');

            if (container.classList.contains('hidden')) {
                // Ao Abrir
                if (!iframe.src || iframe.getAttribute('src') === '' || iframe.src === window.location.href) {
                    // Reexibe o loading se necessário
                    if (loadingSkel) loadingSkel.style.display = 'flex';
                    iframe.src = url;
                }

                // Exibir o container
                container.classList.remove('hidden');

                // Rotacionar o ícone da seta
                if (svgIcon) svgIcon.classList.add('rotate-180');

                // Opcional: rolagem suave para garantir que o cabeçalho e iframe fiquem visíveis
                // Usando setTimeout para garantir que a renderização inicial terminou
                setTimeout(() => {
                    const rowDiv = container.closest('.border-b');
                    if (rowDiv) {
                        const yOffset = -20; // margem superior em pixels
                        const y = rowDiv.getBoundingClientRect().top + window.pageYOffset + yOffset;
                        window.scrollTo({ top: y, behavior: 'smooth' });
                    }
                }, 100);

            } else {
                // Ao Fechar
                container.classList.add('hidden');
                // Retornar a rotação do ícone
                if (svgIcon) svgIcon.classList.remove('rotate-180');
            }
        }

        function closeIframe(containerId) {
            const container = document.getElementById(containerId);
            const parentDiv = container.closest('.border-b');
            if (parentDiv) {
                // Encontra o botão principal daquela linha e clica nele
                const mainBtn = parentDiv.querySelector('button[onclick^="toggleIframe"]');
                if (mainBtn) {
                    mainBtn.click(); // Usa o clique para reaproveitar a lógica de rotação
                    return;
                }
            }
            // Fallback caso não encontre (proteção extra)
            container.classList.add('hidden');
        }
    </script>
</body>

</html>