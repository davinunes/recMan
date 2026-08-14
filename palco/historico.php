<?php
require_once __DIR__ . "/../classes/repositorio.php";
$mesAtualDefault = date('Y-m');
$userIdDebug = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$isDebugUser = ($userIdDebug === 5);
?>

<script>
    window.RECMAN_USER_ID = <?php echo $userIdDebug; ?>;
    window.RECMAN_IS_DEBUG_USER = <?php echo $isDebugUser ? 'true' : 'false'; ?>;
    window.lastEntregaDebugData = null;

    window.isDebugUser = function () {
        return window.RECMAN_IS_DEBUG_USER === true;
    };

    window.abrirModalDebugJson = function (titulo, obj) {
        try {
            var tituloEl = document.getElementById('debugJsonTitulo');
            if (tituloEl) tituloEl.textContent = titulo || 'Dados JSON';
            var preEl = document.getElementById('debugJsonConteudo');
            if (preEl) {
                preEl.textContent = JSON.stringify(obj, null, 2);
            }
            var modalEl = document.getElementById('modalDebugJson');
            if (modalEl && typeof M !== 'undefined' && M.Modal) {
                var inst = M.Modal.getInstance(modalEl);
                if (inst) inst.open();
            }
        } catch (e) {
            console.error('Erro ao abrir modal de debug JSON:', e);
            alert('Erro ao exibir JSON: ' + e.message);
        }
    };

    window.copiarDebugJson = function () {
        var preEl = document.getElementById('debugJsonConteudo');
        if (!preEl) return;
        var texto = preEl.textContent || '';
        if (!texto) {
            M.toast({ html: 'Nada para copiar.', classes: 'orange rounded' });
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texto).then(function () {
                M.toast({ html: 'JSON copiado para a área de transferência!', classes: 'green rounded' });
            }).catch(function () {
                fallbackCopiar(texto);
            });
        } else {
            fallbackCopiar(texto);
        }
    };

    function fallbackCopiar(texto) {
        try {
            var ta = document.createElement('textarea');
            ta.value = texto;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            M.toast({ html: 'JSON copiado para a área de transferência!', classes: 'green rounded' });
        } catch (e) {
            M.toast({ html: 'Não foi possível copiar o JSON.', classes: 'red rounded' });
        }
    }

    window.htmlIconeDebugTerminal = function (onClickFnStr, titulo) {
        if (!window.isDebugUser()) return '';
        var onclickAttr = onClickFnStr ? ' onclick="' + onClickFnStr.replace(/"/g, '&quot;') + '"' : '';
        var titleAttr = titulo ? ' title="' + titulo.replace(/"/g, '&quot;') + '"' : ' title="Inspecionar JSON bruto (Debug)"';
        return '' +
            '<i class="material-icons debug-terminal-icon tooltipped" style="' +
            'font-size:1.1rem; cursor:pointer; color:#1565c0; opacity:0.85; ' +
            'transition:all 0.2s ease; vertical-align:middle; margin-left:4px;"' +
            onclickAttr + titleAttr + ' data-tooltip="' + (titulo ? titulo.replace(/"/g, '&quot;') : 'Inspecionar JSON bruto') + '">' +
            'terminal' +
            '</i>';
    };
</script>

<div class="container" style="width: 95%; max-width: 1400px;">
    <!-- Cabeçalho Principal -->
    <div class="row style-header-toolset" style="margin-bottom: 10px; margin-top: 15px;">
        <div class="col s12">
            <h4 style="font-weight: 300; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="material-icons blue-text text-darken-2" style="font-size: 2.5rem;">build_circle</i>
                Toolset Operacional por Unidade
            </h4>
            <p class="grey-text text-darken-1" style="margin: 5px 0 0 0; font-size: 0.95rem;">
                Painel unificado de dados da unidade: Notificações, Recursos, Encomendas, Autorizações, Reservas, Ocorrências e Boletos.
            </p>
        </div>
    </div>

    <!-- Card de Filtros (Unidade, Bloco e Período) -->
    <div class="card-panel white z-depth-1" style="border-radius: 12px; padding: 18px 24px; margin-bottom: 20px;">
        <div class="row valign-wrapper flex-responsive" style="margin-bottom: 0;">
            <div class="input-field col s12 m3" style="margin-top: 0;">
                <i class="material-icons prefix">business</i>
                <input type="number" id="unidade" name="unidade" class="validate" placeholder="Ex: 101">
                <label for="unidade" class="active">Unidade</label>
            </div>
            <div class="input-field col s12 m3" style="margin-top: 0;">
                <i class="material-icons prefix">location_city</i>
                <select id="bloco" name="bloco">
                    <option value="" disabled selected>Escolha o Bloco</option>
                    <option value="A">Bloco A</option>
                    <option value="B">Bloco B</option>
                    <option value="C">Bloco C</option>
                    <option value="D">Bloco D</option>
                    <option value="E">Bloco E</option>
                    <option value="F">Bloco F</option>
                </select>
                <label class="active">Bloco / Torre</label>
            </div>
            
            <!-- Seletor de Mês e Navegação Temporal -->
            <div class="col s12 m4" style="margin-top: 0;">
                <label class="grey-text text-darken-2" style="font-size: 0.8rem; font-weight: bold; display: block; margin-bottom: 4px;">
                    <i class="material-icons tiny">date_range</i> MÊS DE ABRANGÊNCIA (ACELERADORES)
                </label>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <button type="button" class="btn-flat btn-small waves-effect grey lighten-3" id="btnMesAnterior" title="Mês Anterior">
                        <i class="material-icons">chevron_left</i>
                    </button>
                    <input type="month" id="mesAnoFiltro" value="<?php echo $mesAtualDefault; ?>" class="browser-default custom-month-input" style="height: 36px; padding: 0 10px; border: 1px solid #ccc; border-radius: 4px; flex: 1; font-weight: 500;">
                    <button type="button" class="btn-flat btn-small waves-effect grey lighten-3" id="btnProximoMes" title="Próximo Mês">
                        <i class="material-icons">chevron_right</i>
                    </button>
                    <button type="button" class="btn-flat btn-small waves-effect blue lighten-5 blue-text text-darken-3" id="btnMesAtual" title="Ir para o Mês Atual" style="font-weight: bold;">
                        Hoje
                    </button>
                </div>
            </div>

            <div class="col s12 m2 center-align" style="margin-top: 0;">
                <button class="btn waves-effect waves-light blue darken-2 style-btn-search" id="buscaHistoricoUnidade" style="width: 100%; height: 42px; line-height: 42px; border-radius: 6px;">
                    <i class="material-icons left">search</i> CARREGAR
                </button>
            </div>
        </div>
    </div>

    <!-- Área de Brief (Dashboard Estatística da Unidade KPI) -->
    <div id="unitBrief" class="row hide" style="margin-bottom: 20px;">
        <!-- Injetado dinamicamente via JS -->
    </div>

    <!-- Estado Inicial Vazio -->
    <div id="emptyState" class="row" style="margin-top: 40px;">
        <div class="col s12 center-align grey-text" style="padding: 50px 20px;">
            <i class="material-icons" style="font-size: 5rem; opacity: 0.25;">tune</i>
            <h5 style="font-weight: 300; margin-top: 10px;">Nenhuma unidade selecionada</h5>
            <p style="font-size: 0.95rem;">Informe a unidade, o bloco e clique em <b>Carregar</b> para visualizar o toolset completo.</p>
        </div>
    </div>

    <!-- Loader Ativo -->
    <div id="toolsetLoader" class="row hide" style="margin-top: 40px;">
        <div class="col s12 center-align">
            <div class="preloader-wrapper big active">
                <div class="spinner-layer spinner-blue-only">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
            </div>
            <p class="grey-text" style="margin-top: 15px; font-weight: 500;">Carregando aceleradores e histórico da unidade...</p>
        </div>
    </div>

    <!-- Painel de Accordion Colapsado (7 Aceleradores) -->
    <div id="toolsetContainer" class="row hide" style="margin-top: 10px;">
        <div class="col s12">
            <ul class="collapsible popout z-depth-1" id="toolsetCollapsible" style="border: none;">
                
                <!-- 0. Moradores da Unidade -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons cyan-text text-darken-2">people</i>
                            <b>Moradores da Unidade</b>
                            <small class="grey-text text-darken-1">(Cadastro VDS)</small>
                        </span>
                        <span class="badge cyan darken-2 white-text font-weight-bold" id="badgeCountMoradores">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoMoradores">
                            <!-- Cards dos Moradores -->
                        </div>
                    </div>
                </li>

                <!-- 0.1 Veículos da Unidade -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons blue-grey-text text-darken-2">directions_car</i>
                            <b>Veículos da Unidade</b>
                            <small class="grey-text text-darken-1">(Cadastro VDS)</small>
                        </span>
                        <span class="badge blue-grey darken-2 white-text font-weight-bold" id="badgeCountVeiculos">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoVeiculos">
                            <!-- Cards dos Veículos -->
                        </div>
                    </div>
                </li>

                <!-- 0.2 Visitantes & Prestadores da Portaria -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons purple-text text-darken-2">badge</i>
                            <b>Visitantes & Prestadores da Portaria</b>
                            <small class="grey-text text-darken-1">(Cadastro Portaria VDS)</small>
                        </span>
                        <span class="badge purple darken-2 white-text font-weight-bold" id="badgeCountVisitantes">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoVisitantes">
                            <!-- Cards dos Visitantes -->
                        </div>
                    </div>
                </li>



                <!-- 1. Notificações & Recursos (Histórico Completo) -->
                <li class="active">
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons red-text text-darken-1">warning</i>
                            <b>1. Notificações & Recursos da Unidade</b>
                            <small class="grey-text text-darken-1">(Histórico Completo)</small>
                        </span>
                        <span class="badge red white-text font-weight-bold" id="badgeCountNotificacoes">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoNotificacoes">
                            <!-- Cards de Notificação -->
                        </div>
                    </div>
                </li>


                <!-- 2. Encomendas / Correspondências -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons amber-text text-darken-3">local_shipping</i>
                            <b>2. Encomendas & Correspondências</b>
                            <small class="grey-text text-darken-1" id="labelMesEncomendas">(Mês Selecionado)</small>
                        </span>
                        <span class="badge amber darken-3 white-text font-weight-bold" id="badgeCountEncomendas">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoEncomendas">
                            <!-- Tabela/Cards de Entregas -->
                        </div>
                    </div>
                </li>

                <!-- 3. Autorizações de Acesso & Visitantes -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons teal-text text-darken-2">badge</i>
                            <b>3. Autorizações de Acesso & Convites</b>
                            <small class="grey-text text-darken-1" id="labelMesAutorizacoes">(Mês Selecionado)</small>
                        </span>
                        <span class="badge teal darken-1 white-text font-weight-bold" id="badgeCountAutorizacoes">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoAutorizacoes">
                            <!-- Lista de Visitantes/Prestadores -->
                        </div>
                    </div>
                </li>

                <!-- 4. Reservas de Área Comum -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons indigo-text text-darken-2">event_seat</i>
                            <b>4. Reservas de Área Comum</b>
                            <small class="grey-text text-darken-1" id="labelMesReservas">(Mês Selecionado)</small>
                        </span>
                        <span class="badge indigo darken-1 white-text font-weight-bold" id="badgeCountReservas">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoReservas">
                            <!-- Lista de Reservas -->
                        </div>
                    </div>
                </li>

                <!-- 5. Ocorrências de Própria Autoria -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons blue-text text-darken-2">rate_review</i>
                            <b>5. Ocorrências Registradas pela Unidade</b>
                            <small class="grey-text text-darken-1">(Própria Autoria)</small>
                        </span>
                        <span class="badge blue darken-2 white-text font-weight-bold" id="badgeCountOcorrenciasAutoria">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoOcorrenciasAutoria">
                            <!-- Lista de Ocorrências Autoria -->
                        </div>
                    </div>
                </li>

                <!-- 6. Ocorrências nas quais Sofreu Tag (Citada/Ré) -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons purple-text text-darken-2">sell</i>
                            <b>6. Ocorrências Envolvendo a Unidade</b>
                            <small class="grey-text text-darken-1">(Tags, Citações e Ré)</small>
                        </span>
                        <span class="badge purple darken-2 white-text font-weight-bold" id="badgeCountOcorrenciasTag">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoOcorrenciasTag">
                            <!-- Lista de Ocorrências Tag -->
                        </div>
                    </div>
                </li>

                <!-- 7. Boletos & Lançamentos Financeiros -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons green-text text-darken-2">receipt</i>
                            <b>7. Boletos & Lançamentos Financeiros</b>
                            <small class="grey-text text-darken-1" id="labelAnoBoletos">(Ano)</small>
                        </span>
                        <span class="badge green darken-2 white-text font-weight-bold" id="badgeCountBoletos">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoBoletos">
                            <!-- Lista de Boletos -->
                        </div>
                    </div>
                </li>

                <!-- 8. Liberações da Portaria (Caixa 9 VDS) -->
                <li>
                    <div class="collapsible-header flex-header-collapsible">
                        <span class="title-with-icon">
                            <i class="material-icons teal-text text-darken-2">meeting_room</i>
                            <b>8. Liberações da Portaria</b>
                            <small class="grey-text text-darken-1" id="labelMesLiberacoesPortaria">(Mês Selecionado)</small>
                        </span>
                        <span class="badge teal darken-2 white-text font-weight-bold" id="badgeCountLiberacoesPortaria">0</span>
                    </div>
                    <div class="collapsible-body white">
                        <div id="conteudoLiberacoesPortaria">
                            <!-- Lista de Liberações de Portaria -->
                        </div>
                    </div>
                </li>

            </ul>
        </div>
    </div>
</div>

<!-- Modal 1: Cadastrar/Atualizar Data de Ciência (Retirada da Notificação) -->
<div id="modalCienciaNotificacao" class="modal">
    <div class="modal-content">
        <h4><i class="material-icons left blue-text">event_available</i> Registrar Data de Ciência / Retirada</h4>
        <p class="grey-text">Informe a data em que o morador tomou ciência ou retirou a notificação <b id="lblNotificacaoVirtual"></b>.</p>
        
        <input type="hidden" id="virtualNotificacaoTarget">
        <div class="input-field" style="margin-top: 20px;">
            <input type="date" id="inputDataRetirada" class="validate">
            <label for="inputDataRetirada" class="active">Data de Retirada / Ciência</label>
        </div>
    </div>
    <div class="modal-footer">
        <button class="modal-close btn-flat waves-effect">Cancelar</button>
        <button class="btn waves-effect waves-light blue darken-2" id="btnSalvarCiencia">Salvar Data</button>
    </div>
</div>

<!-- Modal 2: Confirmar / Registrar Lançamento de Cobrança de Multa -->
<div id="modalCobrancaMulta" class="modal">
    <div class="modal-content">
        <h4><i class="material-icons left green-text">monetization_on</i> Confirmar Lançamento de Cobrança</h4>
        <p class="grey-text">Informe o valor e a data de vencimento do boleto referente à notificação <b id="lblCobrancaVirtual"></b>.</p>
        
        <input type="hidden" id="cobrancaNotificacaoTarget">
        <div class="row" style="margin-top: 20px;">
            <div class="input-field col s12 m6">
                <i class="material-icons prefix">attach_money</i>
                <input type="number" step="0.01" id="inputValorMulta" class="validate" placeholder="0.00">
                <label for="inputValorMulta" class="active">Valor da Multa (R$)</label>
            </div>
            <div class="input-field col s12 m6">
                <i class="material-icons prefix">event</i>
                <input type="date" id="inputVencimentoMulta" class="validate">
                <label for="inputVencimentoMulta" class="active">Data de Vencimento do Boleto</label>
            </div>
            <div class="input-field col s12 m6">
                <i class="material-icons prefix">event_available</i>
                <input type="date" id="inputPagamentoMulta" class="validate">
                <label for="inputPagamentoMulta" class="active">Data de Pagamento (opcional)</label>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="modal-close btn-flat waves-effect">Cancelar</button>
        <button class="btn waves-effect waves-light green darken-2" id="btnSalvarCobranca">Confirmar Lançamento</button>
    </div>
</div>

<!-- Modal 3: Inspeção Detalhada da Entrega / Foto Ampliada -->
<div id="modalDetalhesEntrega" class="modal">
    <div class="modal-content">
        <h4 style="display:flex; align-items:center; gap:8px;">
            <i class="material-icons amber-text text-darken-3">inventory_2</i> Detalhes da Encomenda
            <span id="iconeDebugModalEntrega" class="hide" style="margin-left:auto;">
                <i class="material-icons debug-terminal-icon tooltipped"
                   style="font-size:1.3rem; cursor:pointer; color:#1565c0;"
                   onclick="if(window.lastEntregaDebugData){window.abrirModalDebugJson('Dados brutos da Encomenda (API VDS)', window.lastEntregaDebugData);}else{M.toast({html:'Dados ainda não carregados.', classes:'orange rounded'});}"
                   data-tooltip="Inspecionar JSON bruto desta Encomenda" title="Inspecionar JSON bruto">terminal</i>
            </span>
        </h4>
        <div id="conteudoModalEntrega" class="center-align" style="padding: 10px 0;">
            <div class="preloader-wrapper active"><div class="spinner-layer spinner-blue-only"><div class="circle-clipper left"><div class="circle"></div></div></div></div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="modal-close btn waves-effect blue darken-2">Fechar</button>
    </div>
</div>

<!-- Modal Debug: Exibir JSON formatado de requisições/dados -->
<div id="modalDebugJson" class="modal modal-fixed-footer" style="max-width:900px; width:90%;">
    <div class="modal-content" style="padding-bottom:0;">
        <h5 style="display:flex; align-items:center; gap:8px; margin-top:0; margin-bottom:10px;">
            <i class="material-icons blue-grey-text" style="font-size:1.8rem;">code</i>
            <span id="debugJsonTitulo">Dados JSON</span>
            <span style="margin-left:auto;">
                <button type="button" class="btn-small btn-flat waves-effect blue lighten-5 blue-text text-darken-3"
                        onclick="window.copiarDebugJson();" title="Copiar JSON">
                    <i class="material-icons left tiny">content_copy</i> Copiar
                </button>
            </span>
        </h5>
        <div style="
            background:#263238;
            color:#eceff1;
            border-radius:8px;
            padding:14px 16px;
            max-height: calc(80vh - 180px);
            overflow:auto;
            border:1px solid #37474f;
        ">
            <pre id="debugJsonConteudo"
                 style="margin:0; font-family:Consolas, Monaco, 'Courier New', monospace; font-size:0.82rem; line-height:1.45; white-space:pre-wrap; word-break:break-word;">{ }</pre>
        </div>
    </div>
    <div class="modal-footer" style="background:#fafafa;">
        <button class="modal-close btn-flat waves-effect">Fechar</button>
    </div>
</div>

<!-- Modal Detalhes do Morador (dados ricos via /morador/{uuid}) -->
<div id="modalDetalhesMorador" class="modal modal-fixed-footer" style="max-width: 900px; width: 92%; max-height: 90%;">
    <div class="modal-content" style="padding-bottom: 0;">
        <h5 style="display:flex; align-items:center; gap:10px; margin-top:0; margin-bottom:10px;">
            <i class="material-icons cyan-text text-darken-2" style="font-size:2rem;">person</i>
            <span id="detalheMoradorTitulo">Detalhes do Morador</span>
            <span id="detalheMoradorStatusBadge" style="margin-left:8px;"></span>
            <span id="detalheMoradorIconeDebug" class="hide" style="margin-left:auto;">
                <i class="material-icons debug-terminal-icon tooltipped"
                   style="font-size:1.3rem; cursor:pointer; color:#1565c0;"
                   onclick="if(window.lastMoradorDebugData){window.abrirModalDebugJson('Dados completos do Morador (API VDS)', window.lastMoradorDebugData);}else{M.toast({html:'Dados ainda não carregados.', classes:'orange rounded'});}"
                   data-tooltip="Inspecionar JSON bruto deste Morador" title="Inspecionar JSON">terminal</i>
            </span>
        </h5>
        <div id="detalheMoradorLoading" class="center-align" style="padding: 30px 0;">
            <div class="preloader-wrapper active">
                <div class="spinner-layer spinner-blue-only">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
            </div>
            <p class="grey-text" style="margin-top:12px; font-weight:500;">Carregando detalhes do morador...</p>
        </div>
        <div id="detalheMoradorConteudo" class="hide">
            <div class="row" style="margin-bottom: 0;">
                <div class="col s12 m4 l3 center-align" style="padding-top: 8px;">
                    <div id="detalheMoradorFoto" style="margin-bottom: 10px;"></div>
                    <div id="detalheMoradorNome" style="font-weight: bold; font-size: 1.15rem; color: #263238;"></div>
                    <div id="detalheMoradorTipo" style="margin-top: 4px;"></div>
                    <div id="detalheMoradorMatricula" style="font-size:0.85rem; color:#607d8b; margin-top:6px;"></div>
                    <div id="detalheMoradorDtCadastro" style="font-size:0.82rem; color:#78909c; margin-top:3px;"></div>
                </div>
                <div class="col s12 m8 l9" style="padding-top: 8px;">
                    <div class="row" style="margin-bottom: 0;">
                        <div class="col s12 l6" style="margin-bottom: 10px;">
                            <div class="card-panel cyan lighten-5" style="border-radius: 8px; padding: 12px 14px; margin: 0;">
                                <h6 style="font-size: 0.85rem; font-weight: 700; margin: 0 0 8px 0; color:#006064; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="material-icons tiny" style="vertical-align: middle;">business</i> Vínculo com a Unidade
                                </h6>
                                <div id="detalheMoradorUnidade" style="font-size: 0.9rem; color:#263238; line-height: 1.5;"></div>
                            </div>
                        </div>
                        <div class="col s12 l6" style="margin-bottom: 10px;">
                            <div class="card-panel blue lighten-5" style="border-radius: 8px; padding: 12px 14px; margin: 0;">
                                <h6 style="font-size: 0.85rem; font-weight: 700; margin: 0 0 8px 0; color:#0d47a1; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="material-icons tiny" style="vertical-align: middle;">contacts</i> Dados Pessoais
                                </h6>
                                <div id="detalheMoradorPessoa" style="font-size: 0.9rem; color:#263238; line-height: 1.5;"></div>
                            </div>
                        </div>
                        <div class="col s12 l6" style="margin-bottom: 10px;">
                            <div class="card-panel purple lighten-5" style="border-radius: 8px; padding: 12px 14px; margin: 0;">
                                <h6 style="font-size: 0.85rem; font-weight: 700; margin: 0 0 8px 0; color:#4a148c; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="material-icons tiny" style="vertical-align: middle;">contact_phone</i> Contato
                                </h6>
                                <div id="detalheMoradorContato" style="font-size: 0.9rem; color:#263238; line-height: 1.5;"></div>
                            </div>
                        </div>
                        <div class="col s12 l6" style="margin-bottom: 10px;">
                            <div class="card-panel green lighten-5" style="border-radius: 8px; padding: 12px 14px; margin: 0;">
                                <h6 style="font-size: 0.85rem; font-weight: 700; margin: 0 0 8px 0; color:#1b5e20; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="material-icons tiny" style="vertical-align: middle;">location_on</i> Endereço
                                </h6>
                                <div id="detalheMoradorEndereco" style="font-size: 0.9rem; color:#263238; line-height: 1.5;"></div>
                            </div>
                        </div>
                        <div class="col s12" style="margin-bottom: 6px;">
                            <div class="card-panel grey lighten-4" style="border-radius: 8px; padding: 10px 14px; margin: 0;">
                                <div id="detalheMoradorAuditoria" style="font-size: 0.82rem; color:#455a64; display:flex; align-items:center; gap:10px; flex-wrap:wrap;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="detalheMoradorErro" class="hide" style="padding: 30px 0;">
            <div class="center-align red-text">
                <i class="material-icons" style="font-size: 3rem; opacity: 0.7;">error_outline</i>
                <p style="margin-top: 10px; font-weight: 500;" id="detalheMoradorMsgErro">Não foi possível carregar os detalhes do morador.</p>
            </div>
        </div>
    </div>
    <div class="modal-footer" style="background: #fafafa;">
        <button class="modal-close btn-flat waves-effect">Fechar</button>
    </div>
</div>

<!-- Modal 4: Extrair Multas / Sugestões do Boleto com Confirmação -->
<div id="modalSugestoesMultaBoleto" class="modal modal-fixed-footer" style="max-width: 850px; max-height: 85%;">
    <div class="modal-content">
        <h4 style="display:flex; align-items:center; gap:8px;">
            <i class="material-icons blue-text text-darken-2">find_in_page</i> Extração e Confirmação de Multas no Boleto
        </h4>
        <p class="grey-text" style="margin-bottom: 20px;">Análise da composição de cobrança da fatura Superlógica buscando ocorrências de penalidade disciplinar/regimento interno.</p>
        
        <div id="loadingSugestoesBoleto" class="center-align" style="padding: 40px 0;">
            <div class="preloader-wrapper active">
                <div class="spinner-layer spinner-blue-only">
                    <div class="circle-clipper left"><div class="circle"></div></div>
                    <div class="gap-patch"><div class="circle"></div></div>
                    <div class="circle-clipper right"><div class="circle"></div></div>
                </div>
            </div>
            <p class="grey-text" style="margin-top: 15px; font-weight: 500;">Analisando composição de cobrança da fatura...</p>
        </div>

        <div id="containerSugestoesBoleto" class="hide">
            <!-- Sugestões extraídas renderizadas aqui -->
        </div>
    </div>
    <div class="modal-footer">
        <button class="modal-close btn-flat waves-effect">Fechar</button>
    </div>
</div>


<style>
    .flex-header-collapsible {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        padding: 15px 20px !important;
        font-size: 1.05rem;
    }
    .title-with-icon {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .kpi-card-toolset {
        padding: 14px 16px;
        border-radius: 10px;
        color: white;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }
    .kpi-card-toolset:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.14);
    }
    .kpi-val {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1.1;
    }
    .kpi-lbl {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
        margin-top: 3px;
    }
    .badge-mini {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .card-notificacao-toolset {
        border-left: 5px solid #ccc;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .card-notificacao-toolset.MULTA { border-left-color: #f44336; }
    .card-notificacao-toolset.ADVERTENCIA { border-left-color: #ff9800; }
    .card-notificacao-toolset.RECURSO { border-left-color: #2196f3; }
    .parecer-manter { background-color: #ffebee !important; }
    .parecer-converter { background-color: #fff3e0 !important; }
    .parecer-revogar { background-color: #e8f5e9 !important; }
    .morador-card-preview-lupa {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -120%);
        opacity: 0;
        pointer-events: none;
        transition: all 0.25s ease-in-out;
        z-index: 5;
    }
    .card-morador-wrapper:hover .morador-card-preview-lupa {
        opacity: 1;
        transform: translate(-50%, -50%);
        pointer-events: auto;
    }
    .btn-lupa-preview {
        background: rgba(0, 96, 100, 0.92);
        color: white;
        border-radius: 999px;
        padding: 10px 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        font-size: 0.82rem;
        box-shadow: 0 4px 14px rgba(0, 77, 64, 0.3);
        cursor: pointer;
        user-select: none;
        border: none;
        transition: all 0.2s ease;
    }
    .btn-lupa-preview:hover {
        background: rgba(0, 77, 64, 1);
        box-shadow: 0 6px 18px rgba(0, 77, 64, 0.45);
        transform: scale(1.04);
    }
    .card-morador-wrapper {
        position: relative;
        overflow: visible;
    }
    .card-morador-wrapper .card-panel {
        transition: all 0.25s ease;
    }
    .card-morador-wrapper:hover .card-panel {
        transform: translateY(-3px);
        box-shadow: 0 8px 22px rgba(0,0,0,0.18) !important;
    }
    .morador-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .morador-status-ativo {
        background: #e8f5e9;
        color: #1b5e20;
        border: 1px solid #a5d6a7;
    }
    .morador-status-inativo {
        background: #ffebee;
        color: #b71c1c;
        border: 1px solid #ef9a9a;
    }
    .detalhe-morador-linha {
        padding: 2px 0;
    }
    .detalhe-morador-linha span.lbl {
        color: #546e7a;
        font-weight: 600;
        font-size: 0.78rem;
        margin-right: 4px;
    }
    .detalhe-morador-foto-wrap {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid #00acc1;
        margin: 0 auto 4px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e0f7fa;
    }
    .detalhe-morador-foto-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

<script>
    window.lastMoradorDebugData = null;
    window.__debugCacheMoradoresDetalhados = {};

    window.abrirDetalhesMorador = function (moradorUuid, fallbackObj) {
        if (!moradorUuid) {
            M.toast({ html: 'UUID do morador não informado.', classes: 'red rounded' });
            return;
        }

        var loading = document.getElementById('detalheMoradorLoading');
        var conteudo = document.getElementById('detalheMoradorConteudo');
        var erro = document.getElementById('detalheMoradorErro');
        var msgErro = document.getElementById('detalheMoradorMsgErro');

        if (loading) { loading.classList.remove('hide'); }
        if (conteudo) { conteudo.classList.add('hide'); }
        if (erro) { erro.classList.add('hide'); }

        if (window.isDebugUser && typeof window.isDebugUser === 'function' && window.isDebugUser()) {
            var iconeDebug = document.getElementById('detalheMoradorIconeDebug');
            if (iconeDebug) iconeDebug.classList.remove('hide');
        } else {
            var iconeDebug2 = document.getElementById('detalheMoradorIconeDebug');
            if (iconeDebug2) iconeDebug2.classList.add('hide');
        }

        var modal = document.getElementById('modalDetalhesMorador');
        if (modal && typeof M !== 'undefined' && M.Modal) {
            var inst = M.Modal.getInstance(modal);
            if (inst) inst.open();
        }

        $.get('metodo.php', { metodo: 'obterDetalhesMorador', uuid: moradorUuid }, function (res) {
            if (res && res.success && res.data) {
                var d = res.data;

                if (window.isDebugUser && typeof window.isDebugUser === 'function' && window.isDebugUser()) {
                    window.lastMoradorDebugData = d;
                    window.__debugCacheMoradoresDetalhados[moradorUuid] = d;
                    if (window.__debugCache) {
                        Object.keys(window.__debugCache).forEach(function (k) {
                            var obj = window.__debugCache[k];
                            if (obj && obj.uuid === moradorUuid) {
                                window.__debugCache[k + '_detalhado'] = d;
                            }
                        });
                    }
                }

                var nome = (d.pessoa && d.pessoa.nome) ? d.pessoa.nome : (fallbackObj && fallbackObj.nome ? fallbackObj.nome : 'Morador');
                var tituloEl = document.getElementById('detalheMoradorTitulo');
                if (tituloEl) tituloEl.textContent = nome;

                var statusAtivo = d.statusAtivo;
                if (typeof statusAtivo === 'undefined' || statusAtivo === null) {
                    var sRaw = d.status;
                    statusAtivo = (sRaw === 1 || sRaw === '1' || (typeof sRaw === 'string' && sRaw.trim() === '1'));
                }
                var statusEl = document.getElementById('detalheMoradorStatusBadge');
                if (statusEl) {
                    statusEl.innerHTML = statusAtivo
                        ? '<span class="morador-status-badge morador-status-ativo"><i class="material-icons tiny">check_circle</i> Ativo</span>'
                        : '<span class="morador-status-badge morador-status-inativo"><i class="material-icons tiny">block</i> Inativo</span>';
                }

                var fotoHtml = '';
                var fotoUrl = (d.pessoa && d.pessoa.fotoUrlCompleta) ? d.pessoa.fotoUrlCompleta : null;
                if (fotoUrl) {
                    fotoHtml = '<div class="detalhe-morador-foto-wrap"><img src="' + fotoUrl + '" alt="Foto do Morador" loading="lazy"></div>';
                } else {
                    fotoHtml = '<div class="detalhe-morador-foto-wrap"><i class="material-icons cyan-text text-darken-2" style="font-size:4rem;">account_circle</i></div>';
                }
                var elFoto = document.getElementById('detalheMoradorFoto');
                if (elFoto) elFoto.innerHTML = fotoHtml;

                var elNome = document.getElementById('detalheMoradorNome');
                if (elNome) elNome.textContent = nome;

                var tipoNome = (d.tipo && d.tipo.nome) ? d.tipo.nome : (fallbackObj && fallbackObj.tipo ? fallbackObj.tipo : 'Morador');
                var grupoNome = (d.tipo && d.tipo.grupo && d.tipo.grupo.nome) ? d.tipo.grupo.nome : null;
                var tipoHtml = '<span class="badge-mini cyan darken-1 white-text" style="font-size:0.78rem;">' + tipoNome + '</span>';
                if (grupoNome) {
                    tipoHtml += ' <span class="badge-mini cyan lighten-4 cyan-text text-darken-4" style="font-size:0.72rem;">' + grupoNome + '</span>';
                }
                var elTipo = document.getElementById('detalheMoradorTipo');
                if (elTipo) elTipo.innerHTML = tipoHtml;

                var matricula = d.matricula;
                var elMat = document.getElementById('detalheMoradorMatricula');
                if (elMat) {
                    if (matricula && String(matricula).trim() !== '') {
                        elMat.innerHTML = '<i class="material-icons tiny" style="vertical-align:middle;">confirmation_number</i> Matrícula: <b>' + String(matricula).trim() + '</b>';
                    } else {
                        elMat.textContent = '';
                    }
                }

                var dtCad = d.dthoraFormatada || null;
                var elDt = document.getElementById('detalheMoradorDtCadastro');
                if (elDt) {
                    elDt.innerHTML = dtCad
                        ? '<i class="material-icons tiny" style="vertical-align:middle;">event</i> Cadastrado em ' + dtCad
                        : '';
                }

                var uniHtml = '';
                if (d.unidade) {
                    var blocoNome = (d.unidade.bloco && d.unidade.bloco.nome) ? d.unidade.bloco.nome : null;
                    var uniNome = d.unidade.nome ? d.unidade.nome : null;
                    var uniInad = (d.unidade.inadimplente === true);
                    var uniLines = [];
                    if (uniNome) uniLines.push('<div class="detalhe-morador-linha"><span class="lbl">Unidade:</span>' + uniNome + '</div>');
                    if (blocoNome) uniLines.push('<div class="detalhe-morador-linha"><span class="lbl">Bloco:</span>' + blocoNome + '</div>');
                    uniLines.push('<div class="detalhe-morador-linha"><span class="lbl">Inadimplente:</span>' +
                        (uniInad
                            ? '<span style="color:#b71c1c; font-weight:700;">SIM</span>'
                            : '<span style="color:#1b5e20; font-weight:600;">Não</span>') +
                        '</div>');
                    if (d.tipo && typeof d.tipo.nivel !== 'undefined') {
                        uniLines.push('<div class="detalhe-morador-linha"><span class="lbl">Nível de Acesso:</span>' + d.tipo.nivel + '</div>');
                    }
                    uniHtml = uniLines.join('');
                }
                var elUni = document.getElementById('detalheMoradorUnidade');
                if (elUni) elUni.innerHTML = uniHtml || '<div style="color:#90a4ae;">Informações de vínculo não disponíveis.</div>';

                var pesHtml = '';
                if (d.pessoa) {
                    var p = d.pessoa;
                    var pesLines = [];
                    if (p.cpf) {
                        var cpfStr = String(p.cpf).replace(/\D/g, '');
                        if (cpfStr.length === 11) {
                            cpfStr = cpfStr.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                        } else {
                            cpfStr = p.cpf;
                        }
                        pesLines.push('<div class="detalhe-morador-linha"><span class="lbl">CPF:</span>' + cpfStr + '</div>');
                    }
                    if (p.rg) {
                        var rgTxt = p.rg + (p.rgEmissor ? ' / ' + p.rgEmissor : '');
                        pesLines.push('<div class="detalhe-morador-linha"><span class="lbl">RG:</span>' + rgTxt + '</div>');
                    }
                    if (p.dtNasc) {
                        pesLines.push('<div class="detalhe-morador-linha"><span class="lbl">Nascimento:</span>' + p.dtNasc + '</div>');
                    }
                    if (p.sexo && String(p.sexo).trim() !== '') {
                        pesLines.push('<div class="detalhe-morador-linha"><span class="lbl">Sexo:</span>' + p.sexo + '</div>');
                    }
                    if (typeof p.pendenciaDocs !== 'undefined') {
                        pesLines.push('<div class="detalhe-morador-linha"><span class="lbl">Pend. Documentos:</span>' +
                            (p.pendenciaDocs === true || p.pendenciaDocs === 'true' || p.pendenciaDocs === 1
                                ? '<span style="color:#ef6c00; font-weight:700;">SIM</span>'
                                : '<span style="color:#1b5e20; font-weight:600;">Não</span>') +
                            '</div>');
                    }
                    pesHtml = pesLines.join('');
                }
                var elPes = document.getElementById('detalheMoradorPessoa');
                if (elPes) elPes.innerHTML = pesHtml || '<div style="color:#90a4ae;">Dados pessoais não disponíveis.</div>';

                var contHtml = '';
                if (d.contato) {
                    var c = d.contato;
                    var contLines = [];
                    if (c.email1 && String(c.email1).trim() !== '') {
                        contLines.push('<div class="detalhe-morador-linha"><span class="lbl">E-mail:</span><a href="mailto:' + c.email1 + '" target="_blank" rel="noopener noreferrer" style="color:#4a148c;">' + c.email1 + '</a></div>');
                    }
                    if (c.email2 && String(c.email2).trim() !== '') {
                        contLines.push('<div class="detalhe-morador-linha"><span class="lbl">E-mail 2:</span><a href="mailto:' + c.email2 + '" target="_blank" rel="noopener noreferrer" style="color:#4a148c;">' + c.email2 + '</a></div>');
                    }
                    if (c.celular && String(c.celular).trim() !== '') {
                        var cel = c.celular;
                        var celLink = String(cel).replace(/\D/g, '');
                        contLines.push('<div class="detalhe-morador-linha"><span class="lbl">Celular:</span><a href="tel:' + celLink + '" style="color:#4a148c; font-weight:600;">' + cel + '</a></div>');
                    }
                    if (c.comercial && String(c.comercial).trim() !== '') {
                        var comLink = String(c.comercial).replace(/\D/g, '');
                        contLines.push('<div class="detalhe-morador-linha"><span class="lbl">Comercial:</span><a href="tel:' + comLink + '" style="color:#4a148c;">' + c.comercial + '</a></div>');
                    }
                    if (c.residencial && String(c.residencial).trim() !== '') {
                        var resLink = String(c.residencial).replace(/\D/g, '');
                        contLines.push('<div class="detalhe-morador-linha"><span class="lbl">Residencial:</span><a href="tel:' + resLink + '" style="color:#4a148c;">' + c.residencial + '</a></div>');
                    }
                    if (c.site && String(c.site).trim() !== '') {
                        contLines.push('<div class="detalhe-morador-linha"><span class="lbl">Site:</span><a href="' + c.site + '" target="_blank" rel="noopener noreferrer" style="color:#4a148c;">' + c.site + '</a></div>');
                    }
                    if (c.descricao && String(c.descricao).trim() !== '') {
                        contLines.push('<div class="detalhe-morador-linha"><span class="lbl">Obs:</span>' + c.descricao + '</div>');
                    }
                    contHtml = contLines.join('');
                }
                var elCont = document.getElementById('detalheMoradorContato');
                if (elCont) elCont.innerHTML = contHtml || '<div style="color:#90a4ae;">Contatos não informados.</div>';

                var endHtml = '';
                if (d.endereco) {
                    var e = d.endereco;
                    var endLines = [];
                    var logr = '';
                    if (e.logradouro) logr += e.logradouro;
                    if (e.numero) logr += (logr ? ', ' : '') + e.numero;
                    if (logr) endLines.push('<div class="detalhe-morador-linha"><span class="lbl">Logradouro:</span>' + logr + '</div>');
                    if (e.complemento && String(e.complemento).trim() !== '') {
                        endLines.push('<div class="detalhe-morador-linha"><span class="lbl">Complemento:</span>' + e.complemento + '</div>');
                    }
                    if (e.bairro) endLines.push('<div class="detalhe-morador-linha"><span class="lbl">Bairro:</span>' + e.bairro + '</div>');
                    if (e.cidade && e.cidade.nome) {
                        var est = e.cidade.estado ? ' / ' + e.cidade.estado : '';
                        endLines.push('<div class="detalhe-morador-linha"><span class="lbl">Cidade:</span>' + e.cidade.nome + est + '</div>');
                    }
                    if (e.cep) {
                        var cepStr = String(e.cep).replace(/\D/g, '');
                        if (cepStr.length === 8) cepStr = cepStr.replace(/(\d{5})(\d{3})/, '$1-$2');
                        else cepStr = e.cep;
                        endLines.push('<div class="detalhe-morador-linha"><span class="lbl">CEP:</span>' + cepStr + '</div>');
                    }
                    endHtml = endLines.join('');
                }
                var elEnd = document.getElementById('detalheMoradorEndereco');
                if (elEnd) elEnd.innerHTML = endHtml || '<div style="color:#90a4ae;">Endereço não disponível.</div>';

                var audLines = [];
                if (d.registradoPor) {
                    var rp = d.registradoPor;
                    var who = rp.nome || 'Sistema';
                    var fotoRp = rp.fotoUrlCompleta ? '<img src="' + rp.fotoUrlCompleta + '" style="width:22px; height:22px; border-radius:50%; vertical-align:middle; margin-right:4px; object-fit:cover;">' : '<i class="material-icons tiny" style="vertical-align:middle;">perm_identity</i>';
                    audLines.push('<span>' + fotoRp + ' Registrado por <b>' + who + '</b></span>');
                }
                if (typeof d.statusNum !== 'undefined' && d.statusNum !== null) {
                    audLines.push('<span style="padding: 2px 0;"><i class="material-icons tiny" style="vertical-align:middle;">flag</i> Código status: <b>' + d.statusNum + '</b></span>');
                }
                var elAud = document.getElementById('detalheMoradorAuditoria');
                if (elAud) elAud.innerHTML = audLines.join('<span style="opacity:0.4;">•</span>') || '';

                if (loading) loading.classList.add('hide');
                if (conteudo) conteudo.classList.remove('hide');
                if (erro) erro.classList.add('hide');
            } else {
                var mensagem = (res && res.message) ? res.message : 'Não foi possível carregar os detalhes do morador.';
                if (msgErro) msgErro.textContent = mensagem;
                if (loading) loading.classList.add('hide');
                if (conteudo) conteudo.classList.add('hide');
                if (erro) erro.classList.remove('hide');
            }
        }, 'json').fail(function () {
            if (msgErro) msgErro.textContent = 'Falha de comunicação ao carregar detalhes do morador.';
            if (loading) loading.classList.add('hide');
            if (conteudo) conteudo.classList.add('hide');
            if (erro) erro.classList.remove('hide');
        });
    };

    $(document).ready(function () {
        $('select').formSelect();
        $('.collapsible').collapsible({ accordion: false });
        $('.modal').modal();

        if (window.isDebugUser()) {
            var iconeEntrega = document.getElementById('iconeDebugModalEntrega');
            if (iconeEntrega) iconeEntrega.classList.remove('hide');

            if (typeof $('.tooltipped').tooltip === 'function') {
                $('.tooltipped').tooltip();
            }
        }
    });
</script>