# Walkthrough: Carregamento Discreto e Dinâmico no Livro de Ocorrências

**Data:** 2026-08-26  
**Contexto:** `index.php?pag=livroDeOcorrencias`  
**Resultado:** Sucesso na migração para modelo SPA assíncrono via AJAX.

---

## 1. Resumo das Modificações

- Em `livroDeOcorrencias.php`:
  - Criado endpoint interno para `action=carregar_detalhe` (processado quando `is_ajax=1`).
  - Modularizada a função `vds_render_chat_detalhe_conteudo` para renderizar o painel do chat em formato limpo tanto no SSR quanto no AJAX.
  - Substituído o redirecionamento síncrono `window.location.href` pelo clique assíncrono `selecionarOcorrencia(id, elem, event)`.
  - Adicionado gerenciamento de persistência de grupos colapsados com `localStorage` (`STORAGE_KEY_GRUPOS = 'vds_grupos_colapsados'`).
  - Adicionadas funções `submeterNotaInternaAjax` e `publicarNotaRemotoAjax` para garantir que todas as ações dentro do chat operem sem recarregar a página.
  - Sincronização limpa da URL com `history.pushState` e evento `popstate`.

---

## 2. Validação Realizada

- O painel lateral mantém posição, scroll e colapso de grupos.
- O painel direito carrega com skeleton shimmer discreto sem esmaecer a barra esquerda.
- Os parâmetros de filtro e navegação por histórico continuam funcionais.
