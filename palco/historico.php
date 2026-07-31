<?php
require_once __DIR__ . "/../classes/repositorio.php";
$mesAtualDefault = date('Y-m');
?>

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
        </h4>
        <div id="conteudoModalEntrega" class="center-align" style="padding: 10px 0;">
            <div class="preloader-wrapper active"><div class="spinner-layer spinner-blue-only"><div class="circle-clipper left"><div class="circle"></div></div></div></div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="modal-close btn waves-effect blue darken-2">Fechar</button>
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
</style>

<script>
    $(document).ready(function () {
        $('select').formSelect();
        $('.collapsible').collapsible({ accordion: false });
        $('.modal').modal();
    });
</script>