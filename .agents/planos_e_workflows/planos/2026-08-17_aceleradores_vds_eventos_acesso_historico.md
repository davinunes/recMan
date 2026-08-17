# Plano de Implementação: Eventos de Acesso no Histórico por Unidade

**Data:** 17/08/2026  
**Status:** Proposto / Aguardando Aprovação  
**Objetivo:** Incluir o acelerador VDS "Eventos de Acesso" na tela `palco/historico.php`, filtrando pelo mês de abrangência selecionado na dashboard.

---

## 1. Contexto & Diagnóstico
Na tela de detalhe de recurso (`detalheRecurso.php`), o conselheiro já conta com a visualização dos registros de eventos de acesso (entradas, saídas, portões, catracas, veículos e pedestres). Na tela de histórico por unidade (`historico.php`), esse acelerador não estava presente no Toolset.

## 2. Alterações Propostas

### 2.1 Backend (`metodo.php`)
- No `case "toolsetUnidade"`:
  - Definir período ISO do mês selecionado:
    ```php
    $dtInicioAcesso = "{$mesAno}-01T00:00";
    $dtFimAcesso = date("Y-m-t\T23:59", strtotime("{$mesAno}-01"));
    $usuarioId = $_SESSION['user_id'] ?? null;
    $acessos = vds_get_eventos_acesso($torre, $unidade, $dtInicioAcesso, $dtFimAcesso, $usuarioId);
    ```
  - Incluir `'acessos' => $acessos` no retorno JSON.
  - Adicionar `'totalAcessos' => count($acessos)` nas estatísticas da KPI brief.

### 2.2 View (`palco/historico.php`)
- Adicionar o item no accordion collapsible `#toolsetCollapsible`:
  - Seção: **Eventos de Acesso & Entradas/Saídas**
  - Subtítulo: `(Mês Selecionado)` com `#labelMesAcessos`
  - Badge de contagem: `#badgeCountAcessos`
  - Container de renderização: `#conteudoAcessos`
  - Ícone: `fingerprint` (cor deep-purple)
- Adicionar modal `#modalDetalhesAcesso` para inspeção do evento (foto, pessoa, perfil, ponto de acesso, sentido, dispositivo, data/hora e observações).

### 2.3 Frontend (`meu.js`)
- Em `executarBuscaHistorico`:
  - Atualizar `#labelMesAcessos` com o mês em formato legível.
  - Disparar `window.renderToolsetAcessos(res.acessos || [])`.
- Em `renderToolsetDashboard`:
  - Adicionar o card de KPI para Eventos de Acesso com contador e foco na seção do accordion.
- Criar `window.renderToolsetAcessos(list)`:
  - Renderizar tabela responsiva com data/hora, foto/avatar, pessoa, perfil, tipo de evento, dispositivo e botão "Inspecionar".
- Criar `window.abrirModalDetalhesAcesso(data)`:
  - Exibir o modal com detalhes do evento de acesso.
- Sincronizar os índices de `window.focusToolsetSection`.
