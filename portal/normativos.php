<?php
/**
 * Portal - Visualizador de Documentos Normativos e Política de Tratamento de Dados
 * Residencial Miami Beach
 */
session_start();

// Documentos disponíveis e metadados
$documentos = [
    'ptd' => [
        'id' => 'ptd',
        'prioridade' => 1,
        'badge' => 'Prioridade 1 • LGPD & Privacidade',
        'badge_cor' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'icone' => 'security',
        'icone_cor' => 'bg-emerald-600',
        'titulo' => 'Política de Tratamento de Dados Pessoais (PTD)',
        'subtitulo' => 'Conselho Miami Beach • 2025–2027',
        'entenda_chamada' => 'Entenda como tratamos seus dados pessoais e garantimos o sigilo de seus recursos.',
        'descricao' => 'Diretrizes oficiais de privacidade e conformidade com a LGPD (Lei nº 13.709/2018). Regula a coleta, confidencialidade de imagens de CFTV, proteção de dados nos processos de notificações e critérios éticos de deliberação do Conselho.',
        'arquivo' => 'docs/normativos/politica-tratamento-dados-conselho-miami-2025-2027.pdf',
        'tamanho' => '1.3 MB (PDF Pesquisável)',
        'destaque' => true
    ],
    'convencao' => [
        'id' => 'convencao',
        'prioridade' => 2,
        'badge' => 'Prioridade 2 • Estatuto Jurídico',
        'badge_cor' => 'bg-blue-100 text-blue-800 border-blue-300',
        'icone' => 'gavel',
        'icone_cor' => 'bg-blue-600',
        'titulo' => 'Convenção de Condomínio',
        'subtitulo' => 'Estatuto Constitutivo Oficial',
        'entenda_chamada' => 'Entenda as normas fundamentais, direitos e obrigações estruturais de todos os condôminos.',
        'descricao' => 'O instrumento legal supremo do condomínio. Estabelece a especificação dos blocos, frações ideais, destinação das áreas privativas e comuns, competências do Síndico, Conselho Consultivo/Fiscal e quóruns de assembleia.',
        'arquivo' => 'docs/normativos/cc-miami-pesquisavel-interativo.pdf',
        'tamanho' => '1.3 MB (PDF Interativo)',
        'destaque' => false
    ],
    'regimento' => [
        'id' => 'regimento',
        'prioridade' => 3,
        'badge' => 'Prioridade 3 • Convivência & CFTV',
        'badge_cor' => 'bg-orange-100 text-orange-800 border-orange-300',
        'icone' => 'menu_book',
        'icone_cor' => 'bg-orange-500',
        'titulo' => 'Regimento Interno (RI)',
        'subtitulo' => 'Regras de Uso, Convivência e Ampla Defesa',
        'entenda_chamada' => 'Entenda as regras de convivência, uso das áreas comuns e procedimentos de ampla defesa.',
        'descricao' => 'Normas práticas do cotidiano: agendamento de espaços de lazer, horários de silêncio, animais, trânsito interno, penalidades e o Artigo 181, que assegura a verificação presencial das gravações de CFTV para embasamento de defesas.',
        'arquivo' => 'docs/normativos/ri-miami-pesquisavel-interativo.pdf',
        'tamanho' => '1.4 MB (PDF Interativo)',
        'destaque' => false
    ]
];

// Documento selecionado via GET (padrão: ptd)
$docSelecionado = isset($_GET['doc']) && isset($documentos[$_GET['doc']]) ? $_GET['doc'] : 'ptd';
$docAtual = $documentos[$docSelecionado];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Normativos Oficiais & Política de Dados - Conselho Miami Beach</title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="fav_portal_recurso_conselho/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="fav_portal_recurso_conselho/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="fav_portal_recurso_conselho/favicon-16x16.png">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="manifest" href="fav_portal_recurso_conselho/site.webmanifest">
    
    <!-- Tailwind CSS & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        .gradient-ptd {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #0d9488 100%);
        }
        .scroll-smooth {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen font-sans text-gray-800 antialiased scroll-smooth">

    <!-- Barra Superior de Navegação -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/95">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <img src="fav_portal_recurso_conselho/android-chrome-192x192.png" alt="Logo Conselho" class="w-10 h-10 rounded-lg shadow-sm border border-gray-200 object-contain">
                <div>
                    <span class="text-xs uppercase font-bold tracking-wider text-orange-600 block leading-tight">Transparência & Governança</span>
                    <h1 class="text-lg sm:text-xl font-extrabold text-blue-900 leading-tight">Documentos Normativos & LGPD</h1>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <a href="index.php" class="inline-flex items-center text-sm font-semibold text-gray-700 hover:text-blue-900 bg-gray-100 hover:bg-gray-200 border border-gray-300 px-3.5 py-2 rounded-lg transition duration-150">
                    <span class="material-icons text-base mr-1.5 text-gray-600">arrow_back</span>
                    Voltar à Central de Recursos
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="normativosViewer('<?= $docSelecionado ?>')">

        <!-- Banner Chamativo: Proteção de Dados e Transparência -->
        <section class="gradient-ptd text-white rounded-2xl shadow-xl p-6 sm:p-8 mb-10 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
                <span class="material-icons" style="font-size: 240px;">shield</span>
            </div>
            <div class="relative z-10 max-w-3xl">
                <div class="inline-flex items-center space-x-2 bg-emerald-900/60 text-emerald-200 text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full mb-3 border border-emerald-400/30">
                    <span class="material-icons text-sm text-emerald-300">verified_user</span>
                    <span>Conformidade com a LGPD (Lei nº 13.709/2018)</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight leading-tight mb-3">
                    Privacidade, Sigilo e Transparência em Primeiro Lugar
                </h2>
                <p class="text-emerald-50 text-sm sm:text-base leading-relaxed mb-5">
                    O Conselho do Residencial Miami Beach atua em estrita conformidade com as leis de proteção de dados e com os normativos vigentes.
                    Abaixo você pode consultar de forma <strong>imediata e inline</strong> a Política de Tratamento de Dados (PTD), a Convenção Condominial e o Regimento Interno, sem a necessidade de downloads obrigatórios.
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="selecionarDoc('ptd')" class="inline-flex items-center bg-white text-emerald-900 hover:bg-emerald-50 font-bold text-sm px-4 py-2.5 rounded-xl shadow transition duration-150">
                        <span class="material-icons text-lg mr-1.5 text-emerald-700">visibility</span>
                        Ver Política de Dados Agora
                    </button>
                    <a href="#visualizador" class="inline-flex items-center text-emerald-100 hover:text-white text-sm font-semibold underline underline-offset-4 decoration-emerald-300 hover:decoration-white transition">
                        Explorar todos os 3 documentos abaixo &darr;
                    </a>
                </div>
            </div>
        </section>

        <!-- Seção de Cards: Ordem de Prioridade PTD -> Convenção -> Regimento -->
        <section class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-extrabold text-blue-950 flex items-center">
                        <span class="material-icons mr-2 text-blue-600">library_books</span>
                        Guias Normativos Oficiais
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Selecione qualquer documento para visualizar inline no leitor interativo abaixo ou abrir em nova guia do navegador.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- 1. CARD PTD (PRIORIDADE MÁXIMA) -->
                <div :class="docAtivo === 'ptd' ? 'ring-2 ring-emerald-500 shadow-xl border-emerald-300 bg-emerald-50/20' : 'border-gray-200 hover:border-emerald-300 hover:shadow-lg bg-white'"
                     class="rounded-2xl border transition-all duration-200 flex flex-col justify-between overflow-hidden relative group">
                    
                    <div class="p-6">
                        <!-- Badge de Prioridade -->
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border bg-emerald-100 text-emerald-800 border-emerald-300 flex items-center">
                                <span class="material-icons text-xs mr-1">star</span>
                                Prioridade 1 • LGPD
                            </span>
                            <span class="text-[11px] font-medium text-gray-500">2025–2027</span>
                        </div>

                        <!-- Ícone e Título -->
                        <div class="flex items-start space-x-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-600 text-white shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                <span class="material-icons text-xl block">security</span>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-gray-900 group-hover:text-emerald-800 transition-colors">
                                    Política de Tratamento de Dados
                                </h4>
                                <span class="text-xs text-gray-500 font-medium">Conselho Miami Beach</span>
                            </div>
                        </div>

                        <!-- Bloco Entenda... -->
                        <div class="my-4 p-3.5 bg-emerald-50 rounded-xl border border-emerald-200/80">
                            <p class="text-xs font-bold text-emerald-950 flex items-center mb-1">
                                <span class="material-icons text-sm mr-1 text-emerald-600">lightbulb</span>
                                Entenda como tratamos seus dados:
                            </p>
                            <p class="text-xs text-emerald-900 leading-relaxed">
                                Saiba como garantimos a privacidade dos condôminos, o sigilo no julgamento de notificações e o uso estrito de imagens de CFTV com base na LGPD.
                            </p>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed mb-4">
                            Instrumento obrigatório que disciplina todo o ciclo de vida dos dados pessoais tratados pela Comissão e pelo Conselho Fiscal.
                        </p>
                    </div>

                    <!-- Botões de Ação do Card -->
                    <div class="p-4 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row items-center gap-2">
                        <button @click="selecionarDoc('ptd')"
                                :class="docAtivo === 'ptd' ? 'bg-emerald-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                                class="w-full inline-flex items-center justify-center font-bold text-xs py-2.5 px-3 rounded-lg shadow-sm transition">
                            <span class="material-icons text-base mr-1.5" x-text="docAtivo === 'ptd' ? 'check_circle' : 'visibility'"></span>
                            <span x-text="docAtivo === 'ptd' ? 'Visualizando Agora' : 'Visualizar Inline'"></span>
                        </button>
                        <a href="docs/normativos/politica-tratamento-dados-conselho-miami-2025-2027.pdf" target="_blank" rel="noopener noreferrer"
                           title="Abrir em nova aba do navegador"
                           class="w-full sm:w-auto inline-flex items-center justify-center text-gray-700 hover:text-emerald-800 hover:bg-emerald-100/70 border border-gray-300 hover:border-emerald-300 font-medium text-xs py-2.5 px-3 rounded-lg transition shrink-0">
                            <span class="material-icons text-base">open_in_new</span>
                        </a>
                    </div>
                </div>

                <!-- 2. CARD CONVENÇÃO -->
                <div :class="docAtivo === 'convencao' ? 'ring-2 ring-blue-500 shadow-xl border-blue-300 bg-blue-50/20' : 'border-gray-200 hover:border-blue-300 hover:shadow-lg bg-white'"
                     class="rounded-2xl border transition-all duration-200 flex flex-col justify-between overflow-hidden relative group">
                    
                    <div class="p-6">
                        <!-- Badge de Prioridade -->
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border bg-blue-100 text-blue-800 border-blue-300 flex items-center">
                                Prioridade 2 • Estatuto
                            </span>
                            <span class="text-[11px] font-medium text-gray-500">Oficial</span>
                        </div>

                        <!-- Ícone e Título -->
                        <div class="flex items-start space-x-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-blue-600 text-white shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                <span class="material-icons text-xl block">gavel</span>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-gray-900 group-hover:text-blue-800 transition-colors">
                                    Convenção de Condomínio
                                </h4>
                                <span class="text-xs text-gray-500 font-medium">Residencial Miami Beach</span>
                            </div>
                        </div>

                        <!-- Bloco Entenda... -->
                        <div class="my-4 p-3.5 bg-blue-50 rounded-xl border border-blue-200/80">
                            <p class="text-xs font-bold text-blue-950 flex items-center mb-1">
                                <span class="material-icons text-sm mr-1 text-blue-600">lightbulb</span>
                                Entenda as normas fundamentais:
                            </p>
                            <p class="text-xs text-blue-900 leading-relaxed">
                                Compreenda as bases jurídicas do condomínio, definição das frações ideais, direitos de propriedade e competências dos órgãos eletivos.
                            </p>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed mb-4">
                            Documento soberano devidamente registrado em cartório, com busca textual ativa e índice interativo para consulta direta.
                        </p>
                    </div>

                    <!-- Botões de Ação do Card -->
                    <div class="p-4 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row items-center gap-2">
                        <button @click="selecionarDoc('convencao')"
                                :class="docAtivo === 'convencao' ? 'bg-blue-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                                class="w-full inline-flex items-center justify-center font-bold text-xs py-2.5 px-3 rounded-lg shadow-sm transition">
                            <span class="material-icons text-base mr-1.5" x-text="docAtivo === 'convencao' ? 'check_circle' : 'visibility'"></span>
                            <span x-text="docAtivo === 'convencao' ? 'Visualizando Agora' : 'Visualizar Inline'"></span>
                        </button>
                        <a href="docs/normativos/cc-miami-pesquisavel-interativo.pdf" target="_blank" rel="noopener noreferrer"
                           title="Abrir em nova aba do navegador"
                           class="w-full sm:w-auto inline-flex items-center justify-center text-gray-700 hover:text-blue-800 hover:bg-blue-100/70 border border-gray-300 hover:border-blue-300 font-medium text-xs py-2.5 px-3 rounded-lg transition shrink-0">
                            <span class="material-icons text-base">open_in_new</span>
                        </a>
                    </div>
                </div>

                <!-- 3. CARD REGIMENTO INTERNO -->
                <div :class="docAtivo === 'regimento' ? 'ring-2 ring-orange-500 shadow-xl border-orange-300 bg-orange-50/20' : 'border-gray-200 hover:border-orange-300 hover:shadow-lg bg-white'"
                     class="rounded-2xl border transition-all duration-200 flex flex-col justify-between overflow-hidden relative group">
                    
                    <div class="p-6">
                        <!-- Badge de Prioridade -->
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border bg-orange-100 text-orange-800 border-orange-300 flex items-center">
                                Prioridade 3 • Convivência
                            </span>
                            <span class="text-[11px] font-medium text-gray-500">Art. 181 CFTV</span>
                        </div>

                        <!-- Ícone e Título -->
                        <div class="flex items-start space-x-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-orange-500 text-white shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                <span class="material-icons text-xl block">menu_book</span>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-gray-900 group-hover:text-orange-800 transition-colors">
                                    Regimento Interno (RI)
                                </h4>
                                <span class="text-xs text-gray-500 font-medium">Normas de Convivência</span>
                            </div>
                        </div>

                        <!-- Bloco Entenda... -->
                        <div class="my-4 p-3.5 bg-orange-50 rounded-xl border border-orange-200/80">
                            <p class="text-xs font-bold text-orange-950 flex items-center mb-1">
                                <span class="material-icons text-sm mr-1 text-orange-600">lightbulb</span>
                                Entenda as regras de convivência:
                            </p>
                            <p class="text-xs text-orange-900 leading-relaxed">
                                Regras de uso de áreas comuns, horários, barulho, mudanças e o Artigo 181 para agendamento de verificação presencial de imagens de CFTV.
                            </p>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed mb-4">
                            Guia do dia a dia condominial com critérios para aplicação de notificações, multas e garantias de ampla defesa do morador.
                        </p>
                    </div>

                    <!-- Botões de Ação do Card -->
                    <div class="p-4 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row items-center gap-2">
                        <button @click="selecionarDoc('regimento')"
                                :class="docAtivo === 'regimento' ? 'bg-orange-600 text-white' : 'bg-orange-500 hover:bg-orange-600 text-white'"
                                class="w-full inline-flex items-center justify-center font-bold text-xs py-2.5 px-3 rounded-lg shadow-sm transition">
                            <span class="material-icons text-base mr-1.5" x-text="docAtivo === 'regimento' ? 'check_circle' : 'visibility'"></span>
                            <span x-text="docAtivo === 'regimento' ? 'Visualizando Agora' : 'Visualizar Inline'"></span>
                        </button>
                        <a href="docs/normativos/ri-miami-pesquisavel-interativo.pdf" target="_blank" rel="noopener noreferrer"
                           title="Abrir em nova aba do navegador"
                           class="w-full sm:w-auto inline-flex items-center justify-center text-gray-700 hover:text-orange-800 hover:bg-orange-100/70 border border-gray-300 hover:border-orange-300 font-medium text-xs py-2.5 px-3 rounded-lg transition shrink-0">
                            <span class="material-icons text-base">open_in_new</span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- Visualizador Inline Integrado -->
        <section id="visualizador" class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden mb-12">
            
            <!-- Barra Superior do Leitor -->
            <div class="bg-slate-900 text-white p-4 sm:px-6 flex flex-wrap items-center justify-between gap-3 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <span class="p-2 rounded-lg bg-slate-800 text-slate-300">
                        <span class="material-icons text-lg block" x-text="docs[docAtivo].icone"></span>
                    </span>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-orange-400" x-text="'Prioridade ' + docs[docAtivo].prioridade"></span>
                            <span class="text-slate-400 text-xs">•</span>
                            <span class="text-slate-300 text-xs font-medium" x-text="docs[docAtivo].tamanho"></span>
                        </div>
                        <h4 class="text-base font-bold text-white tracking-wide" x-text="docs[docAtivo].titulo"></h4>
                    </div>
                </div>

                <!-- Abas Rápidas e Botão Abrir em Nova Aba -->
                <div class="flex items-center space-x-2">
                    <div class="hidden sm:inline-flex bg-slate-800 p-1 rounded-xl border border-slate-700">
                        <button @click="selecionarDoc('ptd')" 
                                :class="docAtivo === 'ptd' ? 'bg-emerald-600 text-white font-bold shadow' : 'text-slate-300 hover:text-white'"
                                class="px-3 py-1.5 rounded-lg text-xs transition">
                            1. PTD (LGPD)
                        </button>
                        <button @click="selecionarDoc('convencao')" 
                                :class="docAtivo === 'convencao' ? 'bg-blue-600 text-white font-bold shadow' : 'text-slate-300 hover:text-white'"
                                class="px-3 py-1.5 rounded-lg text-xs transition">
                            2. Convenção
                        </button>
                        <button @click="selecionarDoc('regimento')" 
                                :class="docAtivo === 'regimento' ? 'bg-orange-500 text-white font-bold shadow' : 'text-slate-300 hover:text-white'"
                                class="px-3 py-1.5 rounded-lg text-xs transition">
                            3. Regimento
                        </button>
                    </div>

                    <a :href="docs[docAtivo].arquivo" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold px-3 py-2 rounded-xl border border-slate-700 hover:border-slate-600 transition shadow-sm">
                        <span class="material-icons text-sm mr-1.5 text-orange-400">fullscreen</span>
                        <span>Tela Cheia / Nova Guia</span>
                    </a>
                </div>
            </div>

            <!-- Dica Informativa -->
            <div class="bg-slate-50 border-b border-gray-200 px-4 sm:px-6 py-2.5 text-xs text-gray-600 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center space-x-2">
                    <span class="material-icons text-sm text-blue-600">info</span>
                    <span>Documento pesquisável: utilize <kbd class="bg-gray-200 px-1 py-0.5 rounded text-[11px] font-mono text-gray-700">Ctrl + F</kbd> para localizar termos ou navegue pelos tópicos clicáveis.</span>
                </div>
                <div class="text-[11px] text-gray-500">
                    Abertura inline direta sem download obrigatório
                </div>
            </div>

            <!-- Iframe com PDF embutido (Inline) -->
            <div class="relative w-full bg-gray-200 min-h-[500px] sm:min-h-[750px]">
                <iframe :src="docs[docAtivo].arquivo + '#toolbar=1&navpanes=1'" 
                        class="w-full h-[600px] sm:h-[800px] border-0" 
                        title="Visualizador de PDF Normativo">
                    <p class="p-8 text-center text-sm text-gray-600">
                        Seu navegador não suporta visualização direta de PDFs.
                        <a :href="docs[docAtivo].arquivo" target="_blank" class="text-blue-600 font-bold underline">Clique aqui para abrir o documento diretamente</a>.
                    </p>
                </iframe>
            </div>

        </section>

        <!-- Rodapé da Página -->
        <footer class="text-center py-6 border-t border-gray-200 text-xs text-gray-500 space-y-2">
            <p class="font-medium text-gray-600">
                Conselho Fiscal & Administrativo • Residencial Miami Beach
            </p>
            <p>
                Todos os documentos normativos estão devidamente registrados e em vigor. Dúvidas sobre tratamento de dados e privacidade podem ser encaminhadas pelos canais oficiais de atendimento.
            </p>
            <p class="text-gray-400 uppercase tracking-widest pt-2">
                &copy; <?= date("Y") ?> - Central de Normativos Miami
            </p>
        </footer>

    </main>

    <!-- Script de Controle do Visualizador com Alpine.js -->
    <script>
        function normativosViewer(docInicial) {
            return {
                docAtivo: docInicial || 'ptd',
                docs: <?= json_encode($documentos, JSON_UNESCAPED_UNICODE) ?>,

                selecionarDoc(chave) {
                    if (this.docs[chave]) {
                        this.docAtivo = chave;
                        // Atualiza a URL sem recarregar
                        const novaUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?doc=' + chave;
                        window.history.pushState({ path: novaUrl }, '', novaUrl);

                        // Scroll suave até o visualizador
                        const viewerEl = document.getElementById('visualizador');
                        if (viewerEl) {
                            viewerEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                }
            }
        }
    </script>
</body>
</html>
