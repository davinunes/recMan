# Plano de Implementação: Carregamento Discreto e Dinâmico do Chat no Livro de Ocorrências

**Data:** 2026-08-26  
**Contexto:** `index.php?pag=livroDeOcorrencias`  
**Objetivo:** Eliminar o reload e o piscamento geral da tela ao clicar em chamados na lista da esquerda, carregando apenas a coluna do chat via AJAX e preservando os grupos colapsados.

---

## 1. Problema Identificado

No arquivo `livroDeOcorrencias.php`:
- Cada item da lista na barra lateral esquerda possuía um evento `onclick` inline que fazia redirecionamento via `window.location.href`.
- Esse redirecionamento provocava recarregamento completo da página (Full Page Reload), descolapsando todas as categorias previamente recolhidas pelo conselheiro e recarregando o DOM inteiro.
- Havia efeito de piscamento na tela.

---

## 2. Solução Técnica

1. **Endpoint AJAX Interno**:
   - Adicionar tratamento para `action=carregar_detalhe` (com `is_ajax=1` ou `action=carregar_detalhe`).
   - Retornar o bloco do chat renderizado em formato JSON/HTML.
   - Suportar também envio de notas internas e publicação remota via AJAX sem reload.

2. **Renderização Modular do Chat**:
   - Isolar a geração HTML do chat em uma função reutilizável para servir tanto ao carregamento inicial (SSR) quanto às chamadas AJAX.

3. **Gerenciamento do Frontend no Cliente (JS)**:
   - Trocar `window.location.href` por chamada JS assíncrona `carregarDetalheOcorrencia(id, elem, pushState)`.
   - Modificar apenas a classe `.active` do item na sidebar sem recriar os elementos.
   - Atualizar a URL via `history.pushState` e responder a `popstate`.
   - Exibir o skeleton shimmer apenas no container `#chat-real-content` / `#vds-skeleton-chat-container`, sem esmaecer ou piscar a sidebar.
   - Persistir o estado recolhido/expandido dos grupos em `localStorage` para manter a organização visual intacta.
