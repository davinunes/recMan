# Walkthrough: Ícone de Terminal (Debug JSON) nos Aceleradores do Toolset

Data: 2026-08-02
Autor: Davi (Usuário recMan)
Objetivo: Capacitar usuário ID=5 a inspecionar JSON bruto de itens nos aceleradores (Moradores, Veículos, Visitantes/Prestadores, Encomendas) e em modais de detalhe.

---

## 1. Resumo da Implementação

Foram alterados 2 arquivos do projeto:
- [palco/historico.php](file:///e:/DEV/recMan/palco/historico.php): Adicionados scripts de inicialização, variáveis de permissão, modal de debug JSON e ícone na modal de entrega.
- [meu.js](file:///e:/DEV/recMan/meu.js): Modificados os 4 renderizadores de aceleradores (moradores, veículos, visitantes, encomendas) e o handler de detalhes de entrega para incluir ícones e armazenar dados em cache.

---

## 2. Arquivo 1: `palco/historico.php`

### 2.1 Bloco de Script Inicial (antes do container HTML)
- Linhas 1-81: Foram adicionadas:
  - Variáveis PHP: `$userIdDebug` (ID do usuário da sessão) e `$isDebugUser` (booleano se é ID=5).
  - Variáveis JS globais:
    - `window.RECMAN_USER_ID`: ID numérico do usuário logado.
    - `window.RECMAN_IS_DEBUG_USER`: Booleano que define se UI de debug é mostrada.
    - `window.lastEntregaDebugData`: Cache do último objeto de entrega aberto na modal de detalhes.
  - Funções utilitárias globais:
    - `window.isDebugUser()`: Retorna flag de debug.
    - `window.abrirModalDebugJson(titulo, obj)`: Serializa obj com `JSON.stringify(obj, null, 2)` e abre a modal.
    - `window.copiarDebugJson()` / `fallbackCopiar()`: Copia o conteúdo do `<pre>` para a área de transferência com fallback.
    - `window.htmlIconeDebugTerminal(onClickFnStr, titulo)`: Helper reutilizável que gera o HTML do ícone `<i class="material-icons">terminal</i>` apenas se for usuário debug.

### 2.2 Nova Modal `modalDebugJson`
- Linhas 427-456: Modal de debug com:
  - Rodapé fixo (`modal-fixed-footer`).
  - Largura máxima 900px (90% da tela).
  - Cabeçalho com ícone `code`, título dinâmico (`#debugJsonTitulo`) e botão **Copiar**.
  - Área de conteúdo com fundo escuro `#263238` e `<pre id="debugJsonConteudo">` com fonte monoespaçada (Consolas/Monaco/Courier New).
  - Máxima altura de 80vh - 180px com overflow:auto.

### 2.3 Ícone na ModalDetalhesEntrega
- Linhas 406-425: No `<h4>` do título foi adicionado um `<span id="iconeDebugModalEntrega" class="hide">` contendo o ícone terminal.
- A classe `hide` é removida dinamicamente no `$(document).ready` se for usuário debug.
- Ao clicar, verifica `window.lastEntregaDebugData` e chama `abrirModalDebugJson`.

### 2.4 Ajuste no `$(document).ready`
- Linhas 544-559: Adicionado bloco `if (window.isDebugUser())` que:
  - Remove classe `hide` do ícone na modal de entrega.
  - Re-inicializa tooltips (`.tooltipped()`).

---

## 3. Arquivo 2: `meu.js`

### 3.1 `window.renderToolsetMoradores` (linhas ~1703-1748)
- ForEach modificado para `(m, idx)` com índice.
- Para cada morador:
  - Cria entrada em `window.__debugCache['m_' + Date.now() + '_' + idx] = m`.
  - Variável `iconeDebugMorador` com ícone flutuante `position:absolute; top:8px; right:8px;`.
  - Estilo do `card-panel` ganha `position:relative`.
  - Ícone é concatenado antes de `avatarEl`.
- Final da função: `setTimeout` para re-inicializar `.tooltipped()` se debug.

### 3.2 `window.renderToolsetVeiculos` (linhas ~1750-1855)
- ForEach modificado para `(v, idx)`.
- Mesmo padrão: cache `dKey = 'v_' + ...`, ícone `iconeDebugVeiculo`.
- `cardStyle` modificado para incluir `position:relative;` em ambos (ativo e inativo).
- Ícone adicionado como primeiro filho do card-panel.
- Re-init de tooltip no final.

### 3.3 `window.renderToolsetVisitantes` (linhas ~1857-1905)
- ForEach modificado para `(v, idx)`.
- Cache com prefixo `vis_`.
- `card-panel` ganha `position:relative;`.
- Ícone adicionado.
- Re-init de tooltip no final.

### 3.4 `window.renderToolsetEncomendas` (linhas ~1986-2107)
- ForEach modificado para `(e, idx)`.
- Cache com **duas chaves**:
  - `ent_{ts}_{idx}`: fallback.
  - `ent_uuid_{uuid}`: atualizada depois com dados completos.
- Ícone adicionado no `<td>` da coluna Ação, ao lado direito do botão "Ver".
- No fetch em segundo plano de `obterDetalhesEntrega`: se debug, atualiza `window.__debugCache['ent_uuid_' + uuid] = d` (dado completo).
- Re-init de tooltip no final.

### 3.5 Handler `btn-inspect-entrega` (linhas ~2410-2443)
- No callback de sucesso do `$.get`:
  - Se debug: salva `window.lastEntregaDebugData = d` e atualiza cache por UUID.
  - Isso garante que o ícone na `modalDetalhesEntrega` sempre tenha o dado mais completo disponível.

---

## 4. Funcionalidades e Como Usar

### 4.1 Pré-requisito
Usuário logado deve ter **ID = 5** (valor hardcoded em `$isDebugUser = ($userIdDebug === 5)` no [historico.php](file:///e:/DEV/recMan/palco/historico.php#L5)).

### 4.2 Pontos onde o ícone aparece
| Tela / Localização | Como acionar | O que é exibido |
|---|---|---|
| Card de Morador | Clique no ícone azul `terminal` no canto superior direito do card | JSON bruto do objeto morador (array `moradores[]` da resposta toolsetUnidade) |
| Card de Veículo | Clique no ícone no canto superior direito do card | JSON bruto do objeto veículo |
| Card de Visitante/Prestador | Clique no ícone no canto superior direito do card | JSON bruto do visitante/prestador |
| Linha da tabela Encomendas | Clique no ícone ao lado do botão "Ver" | JSON da encomenda. Após o fetch em 2º plano, passa a ser o dado completo (com fotoUrlCompleta, identificador etc.) |
| **Modal de Detalhes da Encomenda** | Clique no ícone `terminal` no canto superior direito do título da modal | JSON completo retornado pelo método `obterDetalhesEntrega` (mais detalhado) |

### 4.3 Usando a Modal de Debug
- **Botão "Copiar"**: Clica e copia todo o JSON formatado para a área de transferência. Mostra toast de sucesso/erro.
- **Scroll**: Se o JSON for muito grande, a área interna tem barra de rolagem independente.
- **Fechar**: Clique no botão "Fechar" no rodapé da modal ou fora da área da modal (padrão Materialize).

---

## 5. Exemplos de Uso Prático

Cenário: O usuário com ID=5 quer investigar por que um veículo não está aparecendo com o tipo correto.

1. Acessa `index.php?pag=historico`, informa Unidade, Bloco, mês, clica em **CARREGAR**.
2. Abre o acelerador "Veículos da Unidade".
3. Encontra o veículo com exibição incorreta.
4. Clica no ícone `terminal` no canto do card.
5. É aberta a modal com título "Veículo — ABC1234" mostrando todos os campos do objeto:
   ```json
   {
     "placa": "ABC1234",
     "tipo": "Moto",
     "marca": "Honda",
     "modelo": "CB 500",
     "cor": "Vermelha",
     "ativo": 1,
     "foto": "https://..."
   }
   ```
6. Verifica que `tipo: "Moto"` está correto e o problema está na lógica de exibição (ou vice-versa).
7. Clica em **Copiar** para colar o JSON em um documento de análise.

---

## 6. Segurança e Restrições

- **Hardcoded ID=5**: Apenas usuário com `$_SESSION['user_id'] === 5` visualiza os ícones. Usuários com outros IDs **não veem nada** e nenhuma função extra é executada.
- **Dados exibidos são os mesmos da API**: A modal de debug não acessa endpoints adicionais nem exibe dados que o usuário não tenha recebido via AJAX normal. É apenas uma view formatada do payload que o navegador já tem.
- **Não há validação de permissão server-side extra**: Como a funcionalidade é de debug do front e não altera dados, não foi necessário adicionar camada extra. Se no futuro for exibir dados sensíveis que o usuário normal não poderia ver, a flag `$isDebugUser` deve ser usada também no lado do servidor para filtrar o payload.

---

## 7. Pontos de Extensão (Futuro)

1. **Adicionar ícones nos outros aceleradores** (Notificações, Autorizações, Reservas, Ocorrências, Boletos): O padrão é idêntico — basta adicionar o bloco de cache + ícone no forEach correspondente e re-init do tooltip.
2. **Adicionar ícone no nível do acelerador (header)**: Para visualizar o array completo (`window.globalToolsetResponse.moradores` etc.). Poderia ser um ícone ao lado do badge de contagem no header do collapsible.
3. **Visualizar diff/resposta completa**: Adicionar um botão que exibe a resposta inteira de `window.globalToolsetResponse` (todos os aceleradores juntos).
4. **Download do JSON**: Botão para baixar arquivo `.json` com `Blob` e `URL.createObjectURL`.
5. **Syntax highlighting**: Integrar biblioteca tipo `highlight.js` ou `prism.js` para colorir o JSON (hoje está com fundo escuro, mas sem cores por token).

---

## 8. Evidências e Verificação Rápida

Para testar sem executar o servidor:

1. **Validar que variáveis existem**: No DevTools Console da página `?pag=historico`, digite `window.RECMAN_USER_ID` e `window.RECMAN_IS_DEBUG_USER` — devem retornar, respectivamente, o ID do usuário logado e `true` se for ID=5.
2. **Validar que função utilitária funciona**: Execute `window.abrirModalDebugJson('Teste', {foo: 'bar', arr: [1,2,3]})` no console — deve abrir a modal com esse JSON.
3. **Verificar se cache é criado**: Carregue o toolset e execute `Object.keys(window.__debugCache)` no console — deve ter arrays `m_*`, `v_*`, `vis_*`, `ent_*` não vazios.

---

## 9. Changelog de Linhas Alteradas

| Arquivo | Trecho | Alteração |
|---|---|---|
| palco/historico.php | Topo (antes do div.container) | Adicionados PHP ($userIdDebug, $isDebugUser) + `<script>` com 5 funções utilitárias + 2 globais |
| palco/historico.php | ModalDetalhesEntrega | Adicionado `<span id="iconeDebugModalEntrega">` no h4 |
| palco/historico.php | Após modalDetalhesEntrega | Nova modal `modalDebugJson` inserida |
| palco/historico.php | Bloco `<script> $(document).ready` | Adicionado bloco `if(window.isDebugUser())` para init de ícone na modal e tooltips |
| meu.js | renderToolsetMoradores | ForEach com idx + cache + ícone + position:relative + re-init tooltip |
| meu.js | renderToolsetVeiculos | Idem + cardStyle atualizado com position |
| meu.js | renderToolsetVisitantes | Idem |
| meu.js | renderToolsetEncomendas | ForEach com idx + cache duplo (fallback e uuid) + ícone na coluna Ação + atualização do fetch em 2º plano para atualizar cache + re-init tooltip |
| meu.js | Handler btn-inspect-entrega (linha ~2421) | Salva lastEntregaDebugData e atualiza cache por uuid |
