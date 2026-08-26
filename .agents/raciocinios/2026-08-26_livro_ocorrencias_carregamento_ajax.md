# Raciocínio de Diagnóstico e Implementação: Carregamento Assíncrono do Livro de Ocorrências

**Data:** 2026-08-26  
**Arquivo Principal:** `livroDeOcorrencias.php`

---

## 1. Problema e Diagnóstico
- **Sintoma:** Ao clicar em um chamado da lista lateral esquerda (`.item-oco`), a tela inteira sofria reload (`window.location.href`), os grupos colapsados voltavam a se abrir sozinhos e havia um efeito de piscamento na página.
- **Causa Raiz:** O evento de clique nos itens da lista chamava um redirecionamento HTTP GET direto para a mesma página com o parâmetro `&id=...`. Isso reinicializava o DOM e reconstruía toda a árvore HTML da lista, perdendo o estado mantido pelo jQuery em memória.

---

## 2. Decisão de Arquitetura
- **Abordagem SPA/AJAX**: Transformar a seleção de chamados em uma requisição assíncrona (`action=carregar_detalhe&is_ajax=1`), que responde com o HTML processado do chat.
- **Modularização de Renderização**: Criar a função utilitária `vds_render_chat_detalhe_conteudo` para evitar duplicação entre o carregamento inicial (SSR) e as requisições AJAX.
- **Isolamento de Efeitos Visuais**: Fazer o Skeleton Shimmer atuar estritamente sobre a coluna da direita (`#chat-real-content`), mantendo a coluna lateral esquerda (`.sidebar-feed`) 100% estática e estável.
- **Persistência de Estado (UX)**: Salvar os IDs de grupos colapsados em `localStorage` para que a preferência do conselheiro seja respeitada inclusive ao recarregar a página.
- **Histórico do Navegador**: Usar `history.pushState` e `popstate` para manter links compartilháveis e suporte aos botões de voltar/avançar.
