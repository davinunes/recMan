# Raciocínio de Diagnóstico e Implementação: Eventos de Acesso na Tela de Histórico

**Data:** 17/08/2026  
**Autor:** Antigravity  
**Contexto:** O usuário notou que os Aceleradores VDS na tela de Recurso (`detalheRecurso.php`) possuem os "Eventos de Acesso" (`evento_acesso`), mas essa funcionalidade não foi incluída no Toolset da tela de Histórico (`historico.php`). Foi solicitada a inclusão dessa seção também no Histórico, respeitando o filtro de data/mês da Dashboard.

---

## 1. Análise da Situação Atual

1. **Tela de Recurso (`palco/detalheRecurso.php`):**
   - Usa `ajax_aceleradores.php?action=acessos` passando `dtInicio` e `dtFim` em torno da data do ocorrido.
   - O backend invoca `vds_get_eventos_acesso($bloco, $unidade, $dtInicio, $dtFim, $usuarioId)`.
   - Renderiza tabela com hora, foto, pessoa/perfil, tipo de evento (módulo, dispositivo, receptor, sentido) e botão para inspecionar em modal.

2. **Tela de Histórico (`palco/historico.php`):**
   - Possui um painel central unificado (Toolset da Unidade) controlado por `#buscaHistoricoUnidade`.
   - O filtro de período é o `#mesAnoFiltro` (Mês/Ano, ex: `2026-08`).
   - O frontend chama `metodo.php?metodo=toolsetUnidade&unidade=...&bloco=...&mesAno=...`.
   - O endpoint `toolsetUnidade` consulta entregas, autorizações, reservas, ocorrências, boletos, moradores, veículos e liberações de portaria, mas **não consulta os eventos de acesso**.

---

## 2. Decisões de Design e Implementação

1. **Backend (`metodo.php`):**
   - No `case "toolsetUnidade"`:
     - Definir período do mês: `$dtInicioAcesso = "{$mesAno}-01T00:00"` e `$dtFimAcesso = date("Y-m-t\T23:59", strtotime("{$mesAno}-01"))`.
     - Chamar `vds_get_eventos_acesso($torre, $unidade, $dtInicioAcesso, $dtFimAcesso, $usuarioId)`.
     - Incluir `'acessos' => $acessos` no JSON retornado.
     - Adicionar `'totalAcessos' => count($acessos)` nas estatísticas da KPI brief.

2. **View (`palco/historico.php`):**
   - Adicionar novo item collapsible no accordion:
     - Título: **Eventos de Acesso & Entradas/Saídas**
     - Subtítulo dinâmico: `(Mês Selecionado)` com `#labelMesAcessos`
     - Badge: `#badgeCountAcessos`
     - Conteúdo: `#conteudoAcessos`
     - Ícone: `fingerprint` (cor deep-purple)
   - Adicionar modal específico para inspeção rica de evento de acesso `#modalDetalhesAcesso` com suporte a foto de alta resolução, dados do leitor, sentido e botão debug para inspecionar JSON bruto.

3. **Frontend (`meu.js`):**
   - Em `executarBuscaHistorico`:
     - Atualizar label do mês: `$('#labelMesAcessos').text('(' + mesExtenso + ')');`
     - Chamar `window.renderToolsetAcessos(res.acessos || []);`
   - Em `renderToolsetDashboard`:
     - Adicionar card de KPI para Eventos de Acesso com contador e clique vinculador `focusToolsetSection`.
   - Implementar `window.renderToolsetAcessos`:
     - Tabela responsiva com data/hora, foto/avatar do morador/visitante, tipo de evento, sentido, dispositivo/módulo e botão "Inspecionar".
     - Suporte a cache e debug terminal para usuários autorizados.
   - Implementar `window.abrirModalDetalhesAcesso`:
     - Exibe modal detalhado e formatado.

4. **Compatibilidade e Integridade:**
   - Garantir que todos os índices de `focusToolsetSection` permaneçam sincronizados com a ordem exata dos `<li>` no accordion.
   - Seguir as regras de não executar interpretadores/compiladores locais e manter conformidade estrita com o padrão visual do projeto recMan.
