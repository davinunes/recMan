<?php
session_start();
$sessaoAtiva = isset($_SESSION['portal_auth']) ? $_SESSION['portal_auth'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistente de Recursos - Conselho</title>
    <!-- Tailwind CSS (via CDN para módulo independente) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen font-sans text-gray-800">

    <div x-data="assistenteData()"
        class="max-w-xl mx-auto py-8 px-4 sm:px-6 lg:px-8 h-full flex flex-col justify-center min-h-[90vh]">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-blue-900 border-b-4 border-orange-500 inline-block pb-2">Central de
                Recursos</h1>
            <p class="text-gray-600 mt-2 text-lg">Conselho Miami Beach</p>
        </div>

        <!-- Feedback Messages -->
        <div x-show="erro" x-transition
            class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm"
            style="display: none;">
            <p x-text="erroMensagem"></p>
        </div>

        <div x-show="sucesso" x-transition
            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm"
            style="display: none;">
            <p x-text="sucessoMensagem"></p>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">

            <!-- ETAPA 0: Bem vindo -->
            <div x-show="etapa === 0" x-transition class="p-8 text-center" style="display: none;">
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Bem-vindo(a)!</h2>
                <p class="mb-8 text-gray-600">Este é o assistente oficial para interposição de recursos contra
                    notificações aplicadas pelo condomínio.</p>

                <div class="space-y-4">
                    <button @click="iniciarNovo()"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition duration-200 ease-in-out transform hover:-translate-y-1">
                        Apresentar Novo Recurso
                    </button>
                    <!-- Funcionalidade de consultar -->
                    <button @click="etapa = 7"
                        class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-4 rounded-lg shadow-sm transition duration-200">
                        Acompanhar Recurso Existente
                    </button>
                </div>
            </div>

            <!-- ETAPA 1: Número da Notificação -->
            <div x-show="etapa === 1" x-transition class="p-6 sm:p-8" style="display: none;">
                <h3 class="text-xl font-bold mb-2">Identificação da Notificação</h3>
                <p class="text-sm text-gray-500 mb-6">Informe os números exatamente como constam no documento recebido.
                </p>

                <form @submit.prevent="checarNotificacao()">
                    <div class="flex gap-4 mb-6">
                        <div class="w-2/3">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Número</label>
                            <input x-model="notificacaoStr" type="text" placeholder="Ex: 154" required
                                class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg">
                        </div>
                        <div class="w-1/3 text-center pt-8 text-2xl text-gray-400">/</div>
                        <div class="w-2/3">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Ano</label>
                            <input x-model="anoStr" type="text" placeholder="Ex: 2026" required
                                class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg">
                        </div>
                    </div>

                    <div x-show="notificacaoNaoEncontrada" x-transition
                         class="mb-6 p-4 bg-orange-50 text-orange-800 rounded-lg border-l-4 border-orange-400">
                        <div class="flex">
                            <i class="material-icons mr-2 text-orange-500">warning</i>
                            <div>
                                <p class="font-bold">Notificação não localizada!</p>
                                <p class="text-xs mt-1">Verifique o número e o ano informados. Caso os dados estejam corretos em seu documento físico, clique em "Continuar" para prosseguir com a validação manual.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-8">
                        <button type="button" @click="etapa = 0"
                            class="text-gray-500 font-medium hover:text-gray-800 transition">Voltar</button>
                        <button type="submit" :disabled="carregando"
                            class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg shadow-md transition flex items-center">
                            <span x-show="!carregando">Continuar</span>
                            <span x-show="carregando">Processando...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ETAPA 2: Já Existe -->
            <div x-show="etapa === 2" x-transition class="p-6 sm:p-8 text-center" style="display: none;">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 text-orange-500 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2 text-gray-800">Recurso já registrado</h3>
                <p class="text-gray-600 mb-6">Já constamos em nossa base um recurso ativo para a notificação <b><span
                            x-text="notificacaoStr + '/' + anoStr"></span></b>.</p>

                <div class="bg-gray-50 p-4 rounded-lg border mb-6 text-sm text-gray-700 text-left">
                    Se você é o proprietário e precisa acessar, pode gerar um novo código de acesso que será enviado
                    para o seu e-mail atrelado a este recurso <strong
                        x-text="maskedEmailEncontrado !== '' ? '(' + maskedEmailEncontrado + ')' : ''"></strong>.
                </div>

                <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
                    <button @click="etapa = 0"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-lg transition w-full sm:w-auto">Início</button>

                    <button @click="reenviarExistente()" :disabled="!podeReenviar || carregandoAcao"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto flex flex-col items-center justify-center">
                        <span x-show="podeReenviar && !carregandoAcao">Gerar Novo Código <span
                                class="text-xs block font-normal">(Reenviar)</span></span>
                        <span x-show="carregandoAcao">Enviando...</span>
                        <span x-show="!podeReenviar && !carregandoAcao">Aguarde <span x-text="timerReenvio"></span>s
                            para reenviar</span>
                    </button>

                    <button @click="etapa = 7"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition w-full sm:w-auto"
                        title="Ir para acessar recurso">
                        Já tenho um // Acessar
                    </button>
                </div>
            </div>

            <!-- ETAPA 3: Coletar Email e Enviar Código -->
            <div x-show="etapa === 3" x-transition class="p-6 sm:p-8" style="display: none;">
                <h3 class="text-xl font-bold mb-2">Comunicação</h3>
                <p class="text-sm text-gray-500 mb-6">Para sua segurança e envio do parecer, precisamos de um
                    e-mail válido com o qual entraremos em contato.</p>

                <form @submit.prevent="enviarCodigo()">
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Seu e-mail</label>
                        <input x-model="emailContato" type="email" required
                            class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="voce@email.com">
                    </div>

                    <div class="flex justify-between items-center mt-8">
                        <button type="button" @click="etapa = 1"
                            class="text-gray-500 font-medium hover:text-gray-800 transition">Voltar</button>
                        <button type="submit" :disabled="carregando"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition">
                            <span x-show="!carregando">Enviar Código Seguro</span>
                            <span x-show="carregando">Enviando...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ETAPA 4: Validar Código -->
            <div x-show="etapa === 4" x-transition class="p-6 sm:p-8" style="display: none;">
                <h3 class="text-xl font-bold mb-2">Verificação</h3>
                <p class="text-sm text-gray-600 mb-6">Enviamos um código de 6 dígitos para o e-mail <b><span
                            x-text="emailContato"></span></b>.</p>

                <form @submit.prevent="validarCodigo()">
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2 text-center">Código recebido</label>
                        <input x-model="codigoValidacao" type="text" maxlength="6" required
                            class="shadow appearance-none border rounded-lg w-full max-w-[200px] mx-auto block py-3 px-4 text-center tracking-widest text-2xl text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="000000">
                    </div>

                    <p class="text-center text-xs text-gray-400 mb-6 cursor-pointer" @click="enviarCodigo()">Não
                        recebeu? Reenviar código</p>

                    <div class="flex justify-between items-center mt-8">
                        <button type="button" @click="etapa = 3"
                            class="text-gray-500 font-medium hover:text-gray-800 transition">Voltar</button>
                        <button type="submit" :disabled="carregando"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition">
                            <span x-show="!carregando">Validar</span>
                            <span x-show="carregando">Validando...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ETAPA 5: O Formulário Real de Redação -->
            <div x-show="etapa === 5" x-transition class="p-6 sm:p-8" style="display: none;">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r">
                    <p class="text-sm text-blue-800">Assunto: <b>Recurso para Notificação <span
                                x-text="notificacaoStr+'/'+anoStr"></span></b></p>
                </div>

                <form @submit.prevent="enviarRecursoFinal()">
                    <!-- Dados auto-preenchidos ou solicitados -->
                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-700 text-xs font-bold mb-1">Bloco</label>
                            <select x-model="dados.bloco" required
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 text-sm bg-gray-100">
                                <option value="" disabled selected>Selecione</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                                <option value="F">F</option>
                            </select>
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-700 text-xs font-bold mb-1">Unidade</label>
                            <input x-model="dados.unidade" type="number" required
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 text-sm bg-gray-100"
                                placeholder="Ex: 504">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Fundamentação (Texto do Recurso)</label>
                        <textarea x-model="dados.detalhes" required rows="6"
                            class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Redija aqui os motivos pelos quais solicita reavaliação ou cancelamento..."></textarea>
                    </div>

                    <!-- Sessão Crítica: Uploads -->
                    <div class="mb-6 p-4 border border-dashed border-gray-400 rounded-lg bg-gray-50 relative">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Anexar Documentos e Provas</label>
                        <p class="text-xs text-gray-500 mb-3">Tamanho máximo total: 15MB. Formatos comuns aceitos: PDF,
                            JPEG, PNG. <b>(Selecione vários arquivos de uma vez segurando no celular ou PC)</b></p>

                        <input type="file" id="anexosInput" name="anexos[]" multiple="multiple"
                            accept="image/*,application/pdf"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">

                        <div class="mt-3 p-3 bg-yellow-50 text-yellow-800 text-xs rounded border border-yellow-200">
                            <b>⚠️ ATENÇÃO E PRÉ-REQUISITO:</b><br>
                            Você DEVE anexar uma foto ou scan da Cópia da Notificação recebida.<br>
                            O Conselho <b>não tem acesso aos documentos originais retidos pelo Síndico</b>, logo, é
                            recomendado instruir seu recurso com essas cópias.
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-start">
                            <input type="checkbox" required
                                class="mt-1 mr-2 cursor-pointer h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="text-xs text-gray-600">Compreendo que o Conselho apenas avalia. Declaro
                                ter ciência de que posso agendar com o subsíndico do meu bloco ou com a administração,
                                o acesso a sala de CFTV para visualizar as imagens que por ventura tenham sido
                                vinculadas a minha notificação.</span>
                        </label>
                    </div>

                    <div class="flex justify-end pt-4 border-t">
                        <button type="submit" :disabled="carregando"
                            class="w-full bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition">
                            <span x-show="!carregando">Finalizar e Protocolar Recurso</span>
                            <span x-show="carregando">Processando e Enviando...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ETAPA 6: Sucesso Oficial -->
            <div x-show="etapa === 6" x-transition class="p-8 text-center" style="display: none;">
                <div
                    class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 text-green-500 mb-6 shadow">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold mb-4 text-gray-800">Protocolado com Sucesso!</h2>
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 mb-6 text-left">
                    <p class="mb-3 text-gray-700">Seu recurso de número <b><span
                                x-text="notificacaoStr + '/' + anoStr"></span></b> foi oficializado com sucesso pela
                        Central.</p>
                    <p class="mb-3 text-gray-700">O seu e-mail atrelado já é a principal garantia de rastreabilidade. O
                        código validado servirá como senha no futuro da sua área restrita.</p>
                    <p class="text-sm font-bold text-blue-800 mt-4"><i
                            class="material-icons align-bottom text-base mr-1">⏳</i> Prazo Regimental</p>
                    <p class="text-sm text-gray-600 mt-1">Conforme Regimento Interno, o Conselho tem até 15 dias
                        para analisar a documentação de ambas as partes, fatos exarados e emitir um parecer oficial.</p>
                    <p class="text-sm text-gray-600 mt-2">Você receberá e-mail com a deliberação quando for findada a
                        fase investigatória.</p>
                </div>

                <button @click="location.reload()"
                    class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 px-8 rounded-lg shadow transition">
                    Voltar ao Início
                </button>
            </div>

            <!-- ETAPA 7: Login Recurso Existente -->
            <div x-show="etapa === 7" x-transition class="p-6 sm:p-8" style="display: none;">
                <h3 class="text-xl font-bold mb-2">Acesso ao Recurso</h3>
                <p class="text-sm text-gray-500 mb-6">Informe o número do auto e sua senha (código recebido por e-mail
                    no registro).</p>

                <form @submit.prevent="loginExisting()">
                    <div class="flex gap-4 mb-4">
                        <div class="w-1/2">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Número</label>
                            <input x-model="notificacaoStr" @input.debounce.500ms="verificarExistenciaLogin()"
                                type="text" placeholder="Ex: 154" required
                                class="shadow border rounded-lg w-full py-3 px-4 text-gray-700">
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Ano</label>
                            <input x-model="anoStr" @input.debounce.500ms="verificarExistenciaLogin()" type="text"
                                placeholder="Ex: 2026" required
                                class="shadow border rounded-lg w-full py-3 px-4 text-gray-700">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Senha (Código Verificador)</label>
                        <div class="relative">
                            <input x-model="senhaAcesso" :type="verSenha ? 'text' : 'password'" required
                                class="shadow border rounded-lg w-full py-3 px-4 text-center text-lg tracking-widest text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" @click="verSenha = !verSenha"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <span class="material-icons" x-text="verSenha ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="resourceExisteLogin" x-transition class="mb-6 text-center">
                        <template x-if="podeReenviar">
                            <p class="text-xs text-blue-600 hover:underline cursor-pointer"
                                @click="reenviarExistente()">
                                Esqueci minha senha / Reenviar código
                            </p>
                        </template>
                        <template x-if="!podeReenviar">
                            <p class="text-xs text-gray-400">
                                Aguarde <span class="font-bold" x-text="timerReenvio"></span>s para solicitar novamente
                            </p>
                        </template>
                    </div>

                    <div class="flex justify-between items-center mt-8">
                        <button type="button" @click="etapa = 0"
                            class="text-gray-500 font-medium hover:text-gray-800 transition">Voltar</button>
                        <button type="submit" :disabled="carregando"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition">
                            <span x-show="!carregando">Entrar</span>
                            <span x-show="carregando">Processando...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ETAPA 8: Dashboard / Acompanhamento -->
            <div x-show="etapa === 8" x-transition class="p-6 sm:p-8" style="display: none;">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-blue-900">Acompanhamento de Recurso</h3>
                        <p class="text-sm text-gray-600">Autos: <b><span x-text="dadosRecurso.numero || ''"></span></b>
                        </p>
                    </div>
                    <button @click="efetuarLogout()" class="text-gray-500 hover:text-red-500 text-sm flex items-center">
                        <span class="material-icons align-middle mr-1">logout</span>
                        <span>Sair</span>
                    </button>
                </div>

                <div class="mb-6">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest block mb-1">Status
                        Atual</span>
                    <div class="inline-block bg-orange-100 text-orange-800 font-semibold px-4 py-2 rounded-full border border-orange-200"
                        x-text="dadosRecurso.fase_texto || 'Carregando...'"></div>
                </div>

                <template x-if="dadosRecurso.parecer_concluido == 1">
                    <div
                        class="bg-green-50 border border-green-200 p-4 rounded-lg mb-6 flex justify-between items-center">
                        <div>
                            <p class="font-bold text-green-800 mb-1">Decisão do Conselho (Parecer)</p>
                            <p class="text-xs text-green-700">O seu recurso já foi analisado. Baixe o PDF oficial com o
                                parecer.</p>
                        </div>
                        <a href="api.php?action=download_parecer"
                            class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded text-sm font-bold whitespace-nowrap shadow transition"
                            target="_blank">Baixar Parecer</a>
                    </div>
                </template>

                <div class="mb-6">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest block mb-1">Argumentação
                        Registrada</span>
                    <div class="bg-gray-50 p-4 rounded-lg border text-sm text-gray-700 whitespace-pre-wrap max-h-48 overflow-y-auto"
                        x-text="dadosRecurso.detalhes || ''"></div>
                </div>

                <!-- Diligências do Conselho -->
                <template x-if="listaDiligencias && listaDiligencias.length > 0">
                    <div class="mb-6">
                        <span class="text-xs font-bold text-orange-500 uppercase tracking-widest block mb-1">Diligências do Conselho</span>
                        <div class="space-y-4">
                            <template x-for="dil in listaDiligencias" :key="dil.id">
                                <div class="bg-white p-4 rounded-lg border-l-4 border-orange-400 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase" x-text="new Date(dil.timestamp.replace(' ', 'T')).toLocaleString()"></span>
                                        <span class="bg-orange-100 text-orange-600 text-[10px] font-bold px-2 py-0.5 rounded">OFICIAL</span>
                                    </div>
                                    <p class="text-sm text-gray-800 whitespace-pre-wrap" x-text="dil.texto"></p>
                                    
                                    <!-- Anexos da Diligência -->
                                    <template x-if="dil.anexos && dil.anexos.length > 0">
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <template x-for="anx in dil.anexos" :key="anx.id">
                                                <a :href="'../'+anx.caminho_arquivo" target="_blank" class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-[10px] rounded border transition">
                                                    <span class="material-icons text-xs mr-1">attach_file</span>
                                                    <span x-text="anx.nome_arquivo"></span>
                                                </a>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="mb-6" x-show="listaAnexos && listaAnexos.length > 0">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest block mb-2">Arquivos e
                        Provas</span>
                    <ul class="space-y-2">
                        <template x-for="anx in listaAnexos" :key="anx.id">
                            <li class="flex items-center justify-between p-3 border rounded hover:bg-gray-50 bg-white">
                                <span class="text-sm truncate mr-4 text-blue-600 font-medium"
                                    x-text="anx.nome_arquivo"></span>
                                <a :href="'api.php?action=get_anexo&id='+anx.id" target="_blank"
                                    class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-800 py-1 px-3 rounded whitespace-nowrap">Baixar</a>
                            </li>
                        </template>
                    </ul>
                </div>

                <!-- Adicionar Complementos -->
                <div class="mt-8 border-t pt-6" x-show="dadosRecurso.fase < 5">
                    <h4 class="font-bold mb-4 text-gray-800">Adicionar Complemento</h4>
                    <form @submit.prevent="appendComment()" class="mb-4">
                        <textarea x-model="novoComentario" required rows="3"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 text-sm mb-2"
                            placeholder="Escreva aqui novas informações, alegações ou justificativas..."></textarea>
                        <button type="submit" :disabled="carregandoAcao"
                            class="bg-gray-800 text-white py-2 px-4 rounded text-sm shadow hover:bg-gray-900 transition w-full">Apensar
                            Texto</button>
                    </form>

                    <form @submit.prevent="addAttachments()"
                        class="mt-6 border border-dashed rounded-lg p-4 bg-blue-50">
                        <p class="text-sm font-bold text-blue-800 mb-2">Enviar Mais Documentos</p>
                        <p class="text-xs text-gray-500 mb-2">Você pode selecionar várias imagens ou PDFs ao mesmo
                            tempo.</p>
                        <input type="file" id="novosAnexosInput" name="anexos[]" multiple="multiple"
                            accept="image/*,application/pdf" required
                            class="mb-3 block w-full text-sm text-gray-500 file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:bg-blue-100 file:text-blue-700 cursor-pointer">
                        <button type="submit" :disabled="carregandoAcao"
                            class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded text-sm shadow transition w-full">Fazer
                            Upload</button>
                    </form>
                </div>
            </div>

        </div>

        <div class="text-center mt-8 pt-4">
            <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">&copy;
                <?= date("Y") ?> - Conselho Fiscal Miami
            </p>
        </div>
    </div>

    <!-- Script de Gestão Global Vue/Alpine -->
    <script>
        function assistenteData() {
            let sessInit = '<?= $sessaoAtiva ?>';
            let initialEtapa = sessInit !== '' ? 8 : 0;
            let initialNot = '';
            let initialAno = new Date().getFullYear().toString();

            if (sessInit !== '') {
                // Separa o "154/2026"
                let parts = sessInit.split('/');
                if (parts.length == 2) {
                    initialNot = parts[0];
                    initialAno = parts[1];
                }
            }

            return {
                etapa: initialEtapa,
                carregando: false,
                erro: false,
                erroMensagem: '',
                sucesso: false,
                sucessoMensagem: '',

                notificacaoStr: initialNot,
                anoStr: initialAno,

                emailContato: '',
                codigoValidacao: '',
                senhaAcesso: '',
                novoComentario: '',
                maskedEmailEncontrado: '',
                podeReenviar: true,
                timerReenvio: 0,
                intervalReenvio: null,
                verSenha: false,
                resourceExisteLogin: false,

                dadosRecurso: {},
                listaAnexos: [],
                listaDiligencias: [],
                carregandoAcao: false,

                dados: {
                    bloco: '',
                    unidade: '',
                    detalhes: ''
                },
                notificacaoData: null,
                notificacaoNaoEncontrada: false,

                init() {
                    // Já tenta carregar as info se começou na etapa 8 por estar logado
                    if (this.etapa === 8 && this.notificacaoStr !== '') {
                        this.loadMyResource();
                    }
                },

                mostraErro(msg) {
                    this.erroMensagem = msg;
                    this.erro = true;
                    this.sucesso = false;
                    // Auto-hide
                    setTimeout(() => { this.erro = false; }, 5000);
                },

                mostraSucesso(msg) {
                    this.sucessoMensagem = msg;
                    this.sucesso = true;
                    this.erro = false;
                    // Auto-hide
                    setTimeout(() => { this.sucesso = false; }, 8000);
                },

                iniciarNovo() {
                    this.erro = false;
                    this.notificacaoNaoEncontrada = false;
                    this.etapa = 1;
                },

                async checarNotificacao() {
                    if (!this.notificacaoStr || !this.anoStr) return;
                    this.carregando = true;
                    this.erro = false;

                    let fd = new FormData();
                    fd.append('numero', this.notificacaoStr);
                    fd.append('ano', this.anoStr);

                    try {
                        let req = await fetch('api.php?action=check_notification', { method: 'POST', body: fd });
                        let res = await req.json();

                        if (res.exists) {
                            // Ja tem
                            this.maskedEmailEncontrado = res.masked_email || '';
                            this.etapa = 2; // Tela de "Ja foi recebido"
                        } else {
                            // Continuar fluxo de cadastro.
                            if (res.notificacao) {
                                // Temos na listagem geral do sindico -> auto preencher
                                this.dados.bloco = res.notificacao.torre || res.notificacao.bloco || '';
                                this.dados.unidade = res.notificacao.unidade;
                                this.notificacaoData = res.notificacao;
                                this.notificacaoNaoEncontrada = false;
                                this.etapa = 3; // Próximo passo
                            } else {
                                // Não encontramos a notificação
                                if (!this.notificacaoNaoEncontrada) {
                                    // Primeira vez, avisa visualmente
                                    this.notificacaoNaoEncontrada = true;
                                    return;
                                } else {
                                    // Segunda vez (insistiu), mostra alert
                                    if (confirm("Esta notificação não consta em nossa base de dados oficial.\n\nVocê poderá prosseguir, mas o recurso passará por validação e poderá ser recusado se as informações forem procedentes. Deseja continuar?")) {
                                        this.etapa = 3;
                                    }
                                }
                            }
                        }
                    } catch (e) {
                        this.mostraErro("Erro ao consultar os servidores da central.");
                    } finally {
                        this.carregando = false;
                    }
                },

                async verificarExistenciaLogin() {
                    if (this.etapa !== 7) return;
                    if (!this.notificacaoStr || !this.anoStr) {
                        this.resourceExisteLogin = false;
                        return;
                    }

                    let fd = new FormData();
                    fd.append('numero', this.notificacaoStr);
                    fd.append('ano', this.anoStr);

                    try {
                        let req = await fetch('api.php?action=check_notification', { method: 'POST', body: fd });
                        let res = await req.json();
                        this.resourceExisteLogin = !!res.exists;
                        if (res.exists) {
                            this.maskedEmailEncontrado = res.masked_email || '';
                        }
                    } catch (e) {
                        this.resourceExisteLogin = false;
                    }
                },

                async reenviarExistente() {
                    if (!this.podeReenviar || this.carregandoAcao) return;
                    this.erro = false;
                    this.carregandoAcao = true;

                    let fd = new FormData();
                    fd.append('numero', this.notificacaoStr);
                    fd.append('ano', this.anoStr);

                    try {
                        let req = await fetch('api.php?action=resend_existing', { method: 'POST', body: fd });
                        let res = await req.json();

                        if (res.success) {
                            // Iniciar timeout de 10 segundos
                            this.podeReenviar = false;
                            this.timerReenvio = 10;
                            clearInterval(this.intervalReenvio);
                            this.intervalReenvio = setInterval(() => {
                                this.timerReenvio--;
                                if (this.timerReenvio <= 0) {
                                    this.podeReenviar = true;
                                    clearInterval(this.intervalReenvio);
                                }
                            }, 1000);

                            // Mostra tela de digitar o token do email
                            this.etapa = 7;
                            let msg = "Novo código enviado com sucesso!";
                            if (this.maskedEmailEncontrado) {
                                msg += " Verifique o e-mail: " + this.maskedEmailEncontrado;
                            }
                            this.mostraSucesso(msg);
                        } else {
                            this.mostraErro(res.error || "Houve uma falha ao preparar o envio do email.");
                        }
                    } catch (e) {
                        this.mostraErro("Comunicação falhou.");
                    } finally {
                        this.carregandoAcao = false;
                    }
                },

                async enviarCodigo() {
                    if (!this.emailContato) return;
                    this.carregando = true;
                    this.erro = false;

                    let fd = new FormData();
                    fd.append('email', this.emailContato);
                    fd.append('numero', this.notificacaoStr);
                    fd.append('ano', this.anoStr);

                    try {
                        let req = await fetch('api.php?action=send_token', { method: 'POST', body: fd });
                        let res = await req.json();

                        if (res.success) {
                            this.etapa = 4;
                            this.codigoValidacao = ''; // reset do input
                        } else {
                            this.mostraErro(res.error || "Houve uma falha ao preparar o envio do email.");
                        }
                    } catch (e) {
                        this.mostraErro("Comunicação falhou.");
                    } finally {
                        this.carregando = false;
                    }
                },

                async validarCodigo() {
                    if (this.codigoValidacao.length < 5) return;
                    this.carregando = true;
                    this.erro = false;

                    let fd = new FormData();
                    fd.append('token', this.codigoValidacao);

                    try {
                        let req = await fetch('api.php?action=verify_token', { method: 'POST', body: fd });
                        let res = await req.json();

                        if (res.success) {
                            this.etapa = 5; // Vai formulário real
                        } else {
                            this.mostraErro(res.error || "Token fornecido inválido.");
                        }
                    } catch (e) {
                        this.mostraErro("Problema ao contatar o validador.");
                    } finally {
                        this.carregando = false;
                    }
                },

                async enviarRecursoFinal() {
                    this.carregando = true;
                    this.erro = false;

                    let fd = new FormData();
                    fd.append('numero', this.notificacaoStr);
                    fd.append('ano', this.anoStr);
                    fd.append('bloco', this.dados.bloco);
                    fd.append('unidade', this.dados.unidade);
                    fd.append('detalhes', this.dados.detalhes);
                    fd.append('assunto', this.notificacaoData ? this.notificacaoData.assunto : '');

                    // Se não tiver notificacao oficial puxada no preenchimento, deixa msg fallback pro FATO:
                    fd.append('fato', this.notificacaoData ? this.notificacaoData.fato : 'Fato narrado na Notificação.');

                    // Upload files logic
                    const inputFile = document.getElementById("anexosInput");
                    if (inputFile.files.length > 0) {
                        for (let i = 0; i < inputFile.files.length; i++) {
                            fd.append('anexos[]', inputFile.files[i]);
                        }
                    }

                    try {
                        let req = await fetch('api.php?action=submit', { method: 'POST', body: fd });
                        let res = await req.json();

                        if (res.success) {
                            this.etapa = 6;
                        } else {
                            this.mostraErro(res.error || "Sua solicitação não pôde ser salva.");
                        }
                    } catch (e) {
                        console.error(e);
                        this.mostraErro("Interrupção grave no armazenamento!");
                    } finally {
                        this.carregando = false;
                    }
                },

                async loginExisting() {
                    if (!this.notificacaoStr || !this.anoStr || !this.senhaAcesso) return;
                    this.carregando = true;
                    this.erro = false;

                    let fd = new FormData();
                    fd.append('numero', this.notificacaoStr);
                    fd.append('ano', this.anoStr);
                    fd.append('senha', this.senhaAcesso);

                    try {
                        let req = await fetch('api.php?action=login', { method: 'POST', body: fd });
                        let res = await req.json();

                        if (res.success) {
                            this.etapa = 8;
                            await this.loadMyResource();
                        } else {
                            this.mostraErro(res.error || "Acesso negado.");
                        }
                    } catch (e) {
                        this.mostraErro("Falha no login.");
                    } finally {
                        this.carregando = false;
                    }
                },

                async efetuarLogout() {
                    this.carregando = true;
                    try {
                        await fetch('api.php?action=logout');
                        location.reload();
                    } catch (e) {
                        location.reload();
                    }
                },

                async loadMyResource() {
                    try {
                        let req = await fetch('api.php?action=my_resource');
                        let res = await req.json();

                        if (res.success) {
                            this.dadosRecurso = res.recurso;
                            this.listaAnexos = res.anexos;
                            this.listaDiligencias = res.diligencias || [];
                        } else {
                            this.mostraErro(res.error || "Erro ao carregar os dados do recurso.");
                        }
                    } catch (e) {
                        this.mostraErro("Problema de rede.");
                    }
                },

                async appendComment() {
                    if (!this.novoComentario) return;
                    this.carregandoAcao = true;

                    let fd = new FormData();
                    fd.append('comentario', this.novoComentario);

                    try {
                        let req = await fetch('api.php?action=add_comment', { method: 'POST', body: fd });
                        let res = await req.json();
                        if (res.success) {
                            this.novoComentario = '';
                            alert("Comentário adicionado com sucesso!");
                            await this.loadMyResource();
                        } else {
                            this.mostraErro("Erro ao salvar comentário.");
                        }
                    } catch (e) {
                        this.mostraErro("Problema de rede ao apensar comentário.");
                    } finally {
                        this.carregandoAcao = false;
                    }
                },

                async addAttachments() {
                    const inp = document.getElementById("novosAnexosInput");
                    if (inp.files.length === 0) return;

                    this.carregandoAcao = true;
                    let fd = new FormData();
                    for (let i = 0; i < inp.files.length; i++) {
                        fd.append('anexos[]', inp.files[i]);
                    }

                    try {
                        let req = await fetch('api.php?action=add_attachments', { method: 'POST', body: fd });
                        let res = await req.json();
                        if (res.success) {
                            inp.value = '';
                            alert("Arquivos anexados com sucesso!");
                            await this.loadMyResource();
                        } else {
                            this.mostraErro("Erro ao fazer upload dos arquivos.");
                        }
                    } catch (e) {
                        this.mostraErro("Falha na rede durante o upload.");
                    } finally {
                        this.carregandoAcao = false;
                    }
                }
            }
        }
    </script>
</body>

</html>