<?php
/**
 * Portal - Documentos Normativos e Política de Tratamento de Dados
 * Residencial Miami Beach
 */
session_start();

// Documentos disponíveis na ordem de prioridade
$documentos = [
    [
        'id' => 'ptd',
        'prioridade' => 1,
        'badge' => 'Prioridade 1 • Privacidade & LGPD',
        'badge_cor' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'card_borda' => 'border-emerald-300 hover:border-emerald-500 hover:shadow-xl',
        'icone' => 'security',
        'icone_cor' => 'bg-emerald-600',
        'btn_cor' => 'bg-emerald-600 hover:bg-emerald-700 text-white',
        'titulo' => 'Política de Tratamento de Dados (PTD)',
        'subtitulo' => 'Conselho Miami Beach • 2025–2027',
        'entenda_chamada' => 'Entenda como tratamos seus dados pessoais e garantimos o sigilo de seus recursos.',
        'descricao' => 'Explica como protegemos suas informações, o sigilo durante o julgamento de notificações e como funciona o acesso restrito a imagens de segurança (CFTV) em total conformidade com a LGPD.',
        'arquivo' => 'docs/normativos/politica-tratamento-dados-conselho-miami-2025-2027.pdf',
        'formato' => 'PDF Oficial • Pesquisável'
    ],
    [
        'id' => 'convencao',
        'prioridade' => 2,
        'badge' => 'Prioridade 2 • Estatuto Oficial',
        'badge_cor' => 'bg-blue-100 text-blue-800 border-blue-300',
        'card_borda' => 'border-blue-200 hover:border-blue-400 hover:shadow-lg',
        'icone' => 'gavel',
        'icone_cor' => 'bg-blue-600',
        'btn_cor' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'titulo' => 'Convenção de Condomínio',
        'subtitulo' => 'Estatuto Constitutivo Oficial',
        'entenda_chamada' => 'Entenda as normas fundamentais, direitos e deveres de todos os moradores.',
        'descricao' => 'O documento principal do condomínio registrado em cartório. Define direitos e obrigações dos condôminos, divisão das unidades, despesas comuns e como funcionam o Síndico, Conselho e Assembleias.',
        'arquivo' => 'docs/normativos/cc-miami-pesquisavel-interativo.pdf',
        'formato' => 'PDF Oficial • Pesquisável'
    ],
    [
        'id' => 'regimento',
        'prioridade' => 3,
        'badge' => 'Prioridade 3 • Regras de Convivência',
        'badge_cor' => 'bg-orange-100 text-orange-800 border-orange-300',
        'card_borda' => 'border-orange-200 hover:border-orange-400 hover:shadow-lg',
        'icone' => 'menu_book',
        'icone_cor' => 'bg-orange-500',
        'btn_cor' => 'bg-orange-500 hover:bg-orange-600 text-white',
        'titulo' => 'Regimento Interno (RI)',
        'subtitulo' => 'Regras de Uso, Convivência e Ampla Defesa',
        'entenda_chamada' => 'Entenda as regras de convivência, uso das áreas comuns e ampla defesa.',
        'descricao' => 'Regulamenta o dia a dia: horários de silêncio, salão de festas, piscina, garagem, penalidades e o Artigo 181, que garante ao morador o agendamento presencial para assistir filmagens de câmeras antes de recorrer.',
        'arquivo' => 'docs/normativos/ri-miami-pesquisavel-interativo.pdf',
        'formato' => 'PDF Oficial • Pesquisável'
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Normativos & Política de Tratamento de Dados - Conselho Miami Beach</title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="fav_portal_recurso_conselho/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="fav_portal_recurso_conselho/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="fav_portal_recurso_conselho/favicon-16x16.png">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="manifest" href="fav_portal_recurso_conselho/site.webmanifest">
    
    <!-- Tailwind CSS & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        .gradient-banner {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #0d9488 100%);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen font-sans text-gray-800 antialiased flex flex-col justify-between">

    <!-- Barra Superior de Navegação -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/95">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <img src="fav_portal_recurso_conselho/android-chrome-192x192.png" alt="Logo Conselho" class="w-10 h-10 rounded-lg shadow-sm border border-gray-200 object-contain">
                <div>
                    <span class="text-xs uppercase font-bold tracking-wider text-orange-600 block leading-tight">Transparência & Governança</span>
                    <h1 class="text-lg sm:text-xl font-extrabold text-blue-900 leading-tight">Documentos Normativos & LGPD</h1>
                </div>
            </div>
            
            <div>
                <a href="index.php" class="inline-flex items-center text-sm font-semibold text-gray-700 hover:text-blue-900 bg-gray-100 hover:bg-gray-200 border border-gray-300 px-3.5 py-2 rounded-lg transition duration-150">
                    <span class="material-icons text-base mr-1.5 text-gray-600">arrow_back</span>
                    Voltar à Central de Recursos
                </a>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-grow">

        <!-- Banner Chamativo de Privacidade -->
        <section class="gradient-banner text-white rounded-2xl shadow-xl p-6 sm:p-8 mb-8 relative overflow-hidden">
            <div class="absolute -right-8 -bottom-8 opacity-10 pointer-events-none">
                <span class="material-icons" style="font-size: 220px;">shield</span>
            </div>
            <div class="relative z-10 max-w-3xl">
                <div class="inline-flex items-center space-x-2 bg-emerald-900/60 text-emerald-200 text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full mb-3 border border-emerald-400/30">
                    <span class="material-icons text-sm text-emerald-300">verified_user</span>
                    <span>Conformidade LGPD (Lei nº 13.709/2018)</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight leading-tight mb-3">
                    Privacidade, Sigilo e Transparência
                </h2>
                <p class="text-emerald-50 text-sm sm:text-base leading-relaxed mb-4">
                    Aqui você consulta os documentos oficiais que regem o condomínio e protegem seus direitos. 
                    Ao clicar em qualquer um deles, o arquivo abre diretamente na tela do seu navegador, seja no celular ou no computador, sem precisar baixar arquivos no seu aparelho.
                </p>
                <div class="inline-flex items-center text-xs font-medium text-emerald-200 bg-emerald-950/40 px-3 py-1.5 rounded-lg border border-emerald-500/30">
                    <span class="material-icons text-sm mr-1.5 text-emerald-300">touch_app</span>
                    Clique no botão do documento desejado para abrir em tela cheia no navegador.
                </div>
            </div>
        </section>

        <!-- Grade com os 3 Documentos Normativos (Ordem: PTD -> Convenção -> Regimento) -->
        <section class="space-y-6 mb-10">

            <?php foreach ($documentos as $doc): ?>
                <div class="bg-white rounded-2xl border <?= $doc['card_borda'] ?> shadow-sm transition-all duration-200 overflow-hidden flex flex-col md:flex-row items-stretch justify-between">
                    
                    <div class="p-6 sm:p-7 flex-1">
                        <!-- Badge de Prioridade e Formato -->
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border <?= $doc['badge_cor'] ?> inline-flex items-center">
                                <?php if ($doc['prioridade'] === 1): ?>
                                    <span class="material-icons text-xs mr-1">star</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($doc['badge']) ?>
                            </span>
                            <span class="text-xs font-medium text-gray-500 flex items-center">
                                <span class="material-icons text-xs mr-1 text-gray-400">picture_as_pdf</span>
                                <?= htmlspecialchars($doc['formato']) ?>
                            </span>
                        </div>

                        <!-- Título e Ícone -->
                        <div class="flex items-start space-x-3 mb-3">
                            <div class="p-2.5 rounded-xl <?= $doc['icone_cor'] ?> text-white shrink-0 shadow-sm mt-0.5">
                                <span class="material-icons text-xl block"><?= $doc['icone'] ?></span>
                            </div>
                            <div>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-900 leading-snug">
                                    <?= htmlspecialchars($doc['titulo']) ?>
                                </h3>
                                <p class="text-xs text-gray-500 font-medium"><?= htmlspecialchars($doc['subtitulo']) ?></p>
                            </div>
                        </div>

                        <!-- Caixa de Destaque: Entenda... -->
                        <div class="my-3.5 p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                            <p class="text-xs sm:text-sm font-bold text-gray-900 flex items-center mb-1">
                                <span class="material-icons text-base mr-1.5 text-amber-500">lightbulb</span>
                                <?= htmlspecialchars($doc['entenda_chamada']) ?>
                            </p>
                            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                                <?= htmlspecialchars($doc['descricao']) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Coluna de Ação / Botão de Abertura -->
                    <div class="p-6 md:p-7 md:w-64 bg-gray-50/70 border-t md:border-t-0 md:border-l border-gray-100 flex flex-col justify-center items-center text-center gap-3 shrink-0">
                        <a href="<?= htmlspecialchars($doc['arquivo']) ?>" target="_blank" rel="noopener noreferrer"
                           class="w-full inline-flex items-center justify-center font-bold text-sm py-3 px-5 rounded-xl shadow-md transition duration-150 transform hover:-translate-y-0.5 <?= $doc['btn_cor'] ?>">
                            <span class="material-icons text-lg mr-2">open_in_new</span>
                            Visualizar no Navegador
                        </a>
                        <p class="text-[11px] text-gray-500 leading-tight">
                            Abre diretamente na tela do seu aparelho
                        </p>
                    </div>

                </div>
            <?php endforeach; ?>

        </section>

        <!-- Dicas Simples para o Morador -->
        <section class="bg-blue-50 border border-blue-200 rounded-2xl p-5 sm:p-6 mb-8 text-blue-950">
            <div class="flex items-start space-x-3">
                <span class="p-2 bg-blue-600 text-white rounded-lg shrink-0 mt-0.5 shadow-sm">
                    <span class="material-icons text-lg block">help_outline</span>
                </span>
                <div class="text-xs sm:text-sm leading-relaxed">
                    <h4 class="font-bold text-blue-950 mb-1">Dica de navegação</h4>
                    <p class="text-blue-900">
                        Ao abrir qualquer documento, você pode aproximar com os dedos no celular (zoom) ou utilizar a busca rápida do seu navegador 
                        (<kbd class="bg-white px-1.5 py-0.5 rounded border border-blue-300 font-mono text-[11px]">Ctrl + F</kbd> no computador) 
                        para localizar imediatamente artigos, palavras ou regras específicas.
                    </p>
                </div>
            </div>
        </section>

    </main>

    <!-- Rodapé -->
    <footer class="bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-500 space-y-1">
        <p class="font-medium text-gray-700">Conselho Fiscal & Administrativo • Residencial Miami Beach</p>
        <p>&copy; <?= date("Y") ?> - Todos os documentos normativos estão em vigor e disponíveis para consulta pública dos condôminos.</p>
    </footer>

</body>
</html>
