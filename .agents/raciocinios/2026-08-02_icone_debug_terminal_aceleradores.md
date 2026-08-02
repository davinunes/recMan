# Raciocínio: Ícone de Terminal (Debug JSON) nos Aceleradores do Toolset

Data: 2026-08-02
Tópico: Adicionar capacidade de inspecionar o JSON bruto recebido pela API VDS diretamente na interface dos aceleradores, visível apenas para o usuário administrador (ID=5).

---

## 1. Objetivo

O usuário precisa aprofundar análise nos aceleradores (moradores, veículos, visitantes/prestadores, encomendas) e nas modais de detalhes. A solução deve:
- Exibir um ícone `terminal` (Material Icons) em cada item dos aceleradores listados.
- Ao clicar no ícone, abrir uma modal que mostra o **JSON formatado e indented** do objeto bruto recebido.
- O mesmo ícone deve aparecer também dentro das **modais de detalhes** (ex: modal de Encomenda), caso existam.
- **Restrição de segurança**: O ícone e toda a funcionalidade de debug só devem ser visíveis/acessíveis para o usuário com ID = 5.

---

## 2. Descobertas Iniciais (Análise da Base)

### 2.1 Estrutura da página `index.php?pag=historico`
- A página principal é montada em `index.php`, que inclui `palco/historico.php` quando `pag=historico`.
- O ID do usuário logado fica em `$_SESSION['user_id']` (ver [index.php](file:///e:/DEV/recMan/index.php#L30-L38)), atribuído à variável `$esseUsuario`.
- Todos os dados do toolset são carregados via **1 requisição AJAX** para `metodo.php?metodo=toolsetUnidade`, cuja resposta é armazenada em `window.globalToolsetResponse` no callback de sucesso (ver [meu.js](file:///e:/DEV/recMan/meu.js#L1585-L1623)).

### 2.2 Renderização dos Aceleradores
Cada tipo de dado tem seu próprio renderizador em `meu.js`:
- **Moradores**: `window.renderToolsetMoradores(list)` → cards HTML em `#conteudoMoradores`.
- **Veículos**: `window.renderToolsetVeiculos(list, vagas)` → cards HTML em `#conteudoVeiculos`.
- **Visitantes/Prestadores**: `window.renderToolsetVisitantes(list)` → cards em `#conteudoVisitantes`.
- **Encomendas**: `window.renderToolsetEncomendas(list)` → **tabela** HTML em `#conteudoEncomendas` (com coluna "Ação" que tem o botão "Ver").
- Cada renderizador recebe o **array de objetos brutos** (`list`) retornado pela API. Isso é perfeito: temos acesso a todos os campos sem precisar fazer novas requisições.

### 2.3 Modais de Detalhes Existentes
- **modalDetalhesEntrega** (em [historico.php](file:///e:/DEV/recMan/palco/historico.php#L406-L425)): Modal aberta ao clicar em "Ver" na linha da encomenda. Os dados são carregados via `$.get(metodo.php?metodo=obterDetalhesEntrega&uuid=...)` (handler em [meu.js](file:///e:/DEV/recMan/meu.js#L2410-L2443)).
- Os demais aceleradores (moradores, veículos, visitantes) **não possuem modais de detalhes atualmente**. A restrição do usuário ("quando houver") nos permite apenas implementar para a modal de Encomenda neste momento.

---

## 3. Decisões de Implementação (Trade-offs)

### 3.1 Onde injetar a variável de controle de permissão?
**Hipótese A**: Injetar apenas no JS, sem validação PHP (fraca).
**Hipótese B**: Validar no PHP (criar `$isDebugUser = $_SESSION['user_id'] === 5`) e injetar a flag `window.RECMAN_IS_DEBUG_USER` + ID no script do cabeçalho de `palco/historico.php`.
→ **Escolha B**: É a mais segura, pois a flag de debug vem do servidor (não pode ser manipulada apenas via console para habilitar, embora o frontend sempre deva ser considerado inseguro; mas para um recurso de debug interno é suficiente). Mesmo que alguém force `window.RECMAN_IS_DEBUG_USER=true`, ele só veria ícones que chamam funções que exibem dados que o próprio usuário já recebeu via API.

### 3.2 Como passar o objeto JSON do item para o onclick?
Problema: No HTML do template string, não podemos simplesmente interpolar o objeto JS diretamente no `onclick=""` sem serializar, e serializar traz riscos de escaping de aspas.

**Hipótese A**: Serializar com `JSON.stringify(m)` e fazer escaping manual.
**Hipótese B**: Usar um cache global em `window.__debugCache = { chave: obj }` e passar apenas a **chave** como string no `onclick`.
→ **Escolha B**: Muito mais limpo e evita problemas de sintaxe/escaping. Cada item recebe uma chave única tipo `m_16909..._0` (prefixo + timestamp + índice) e o onclick recupera o objeto via `window.__debugCache['chave']`.

### 3.3 Estratégia para Encomendas (2 níveis de detalhe)
Encomendas têm 2 níveis:
1. Dado resumido do array `entregas` da resposta toolsetUnidade (lista inicial).
2. Dado completo do método `obterDetalhesEntrega` (fetch em 2º plano + modal).

**Decisão**:
- Armazenar **ambos** em `window.__debugCache`:
  - Chave `ent_uuid_{uuid}` → atualizada sempre que chega dado completo (tanto no fetch em background quanto na abertura da modal).
  - Chave `ent_{ts}_{idx}` → fallback com o dado resumido.
- No click do ícone: tenta primeiro a versão completa (por UUID), senão usa o resumido.
- Para a **modal de detalhes**, salvo em `window.lastEntregaDebugData = d` no callback de sucesso do `$.get`, pois é o dado mais completo disponível ali.

### 3.4 Design da Modal de Debug
- Usar Materialize CSS `modal modal-fixed-footer` (estilo consistente com o resto).
- Cabeçalho com `material-icons code` + título dinâmico (ex: "Morador — Fulano") + botão **Copiar** (usa `navigator.clipboard` com fallback `execCommand('copy')`).
- Área de conteúdo: `<div>` com fundo escuro (`#263238`) estilo editor de código, `<pre>` com monoespaçado, `white-space: pre-wrap` para quebrar linhas, scroll com altura máxima de `80vh - 180px`.

---

## 4. Pontos de Atenção / Riscos Mitigados

1. **Performance do tooltip**: Os ícones usam `.tooltipped` do Materialize. Como os cards são renderizados dinamicamente, chamamos `$('.tooltipped').tooltip()` com `setTimeout` (50-80ms) após cada função de renderização **apenas se for usuário debug**.
2. **Conflito de Position Relative**: Para ícones flutuantes no canto dos cards, adicionei `position:relative` explicitamente no estilo inline do `card-panel` (garante que o `position:absolute; top:8px; right:8px;` do ícone fique contido no card e não vaze para fora).
3. **Escaping de aspas no nome do título**: Usamos `.replace(/'/g, '&#39;')` no nome do item quando interpolamos dentro do atributo `onclick="window.abrirModalDebugJson('Nome ...', ...)"` — evita que aspas simples quebrem o atributo.
4. **Re-inicialização de tooltips**: Em `$(document).ready` do historico.php, também forçamos a inicialização (porque a página pode ser carregada diretamente com dados já prontos — embora hoje os dados sempre venham depois do click em "CARREGAR").
5. **Fallback debug icons**: `window.htmlIconeDebugTerminal` foi criada como helper, mas na prática implementei inline para cada acelerador (para maior controle do posicionamento específico card vs tabela vs modal). A função fica como helper reutilizável para o futuro.

---

## 5. Validação Mental (Antes de Escrever Código)

- ✅ Ao logar com usuário ID ≠ 5: nenhum ícone aparece. Nenhuma função extra é executada (tooltip re-init é pulado).
- ✅ Ao logar com usuário ID = 5:
  - Clica em "CARREGAR" → AJAX retorna → cards são renderizados.
  - Cada card de morador tem `terminal` no top-right. Clica → abre modal com JSON do morador, formatado.
  - Cada card de veículo: idem.
  - Cada card de visitante/prestador: idem.
  - Cada linha da tabela encomendas: coluna Ação tem botão "Ver" + ícone `terminal` ao lado.
  - Clica em "Ver" encomenda: abre modalDetalhesEntrega. No cabeçalho, ao lado direito do título, aparece o ícone terminal. Clica → abre a modal de debug com o JSON completo da entrega (mesmos dados do fetch de detalhes).
  - Clica no botão Copiar dentro da modal debug → copia JSON para clipboard, mostra toast verde.
- ✅ Fechar modal com X/Cancelar funciona.
- ✅ JSON não tem dados truncados (usa o objeto bruto do array de resposta).

---

## 6. Conclusão

A estratégia de cache global `window.__debugCache` + injeção de flag via PHP + modal estilizada resolve todos os requisitos do usuário com mínima invasão, sem adicionar dependências, e respeita a restrição de visibilidade por ID de usuário.
