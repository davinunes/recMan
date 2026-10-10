# Raciocínio: Expansão Inline via AJAX das Ocorrências Vinculadas no Recurso

Data: 09/10/2026  
Contexto: `index.php?pag=recurso&rec=370/2026` -> Seção "Ocorrências Condomínio Digital Vinculadas" em `palco/detalheRecurso.php`.

## 1. Problema Identificado
Atualmente, na listagem de ocorrências vinculadas ao recurso:
- O botão "Chat Local" possui um link direto `<a href="index.php?pag=livroDeOcorrencias&id=...">`.
- Ao clicar, o sistema recarrega a página ou tenta navegar para a tela inteira do Livro de Ocorrências, disparando loaders globais e tirando o conselheiro do contexto do julgamento do recurso.
- O usuário solicitou que, no clique, em vez de abrir em outra guia / navegar para outra página, seja disparada uma requisição AJAX para carregar o conteúdo da ocorrência e expandi-lo diretamente abaixo do item da collection.

## 2. Diagnóstico Técnico
- Já existe um endpoint AJAX em `livroDeOcorrencias.php` roteado por `index.php`:
  `GET index.php?pag=livroDeOcorrencias&is_ajax=1&action=carregar_detalhe&id={ID}`
  Este endpoint retorna um JSON contendo:
  ```json
  {
    "success": true,
    "ocorrencia_id": 123,
    "html": "<div class=\"chat-header\">...</div><div class=\"chat-body\">...</div><div class=\"chat-footer\">...</div>",
    "local": { ... }
  }
  ```
- O HTML retornado utiliza classes de estilo como `.chat-header`, `.chat-body`, `.msg-bubble`, `.msg-left`, `.msg-right`, `.msg-internal`, `.badge-tipo`, etc., que atualmente estão em bloco `<style>` restrito ao `livroDeOcorrencias.php`.
- Para que o conteúdo renderize adequadamente dentro de `detalheRecurso.php`:
  1. O container de expansão deve ficar logo abaixo do cabeçalho do item da collection (`collection-item`).
  2. Precisamos dos estilos CSS compatíveis (em `meu.css` ou na própria view) com adaptação para exibição inline:
     - Altura máxima com barra de rolagem suave (`max-height: 500px; overflow-y: auto;`).
     - Background amigável estilo chat do WhatsApp (`#efeae2`).
     - Ocultação de elementos mobile desnecessários no inline (ex: botão de voltar à lista do mobile).
  3. Comportamento de alternância (Toggle):
     - Primeiro clique: exibe spinner de carregamento, faz a requisição AJAX, injeta o HTML e abre com animação suave (`slideDown`).
     - Cliques subsequentes: alterna entre recolhido (`slideUp`) e expandido (`slideDown`) instantaneamente, mantendo o conteúdo em cache.
     - Botão secundário de conveniência: permitir abrir em nova guia (`target="_blank"`) no Livro de Ocorrências com ícone `open_in_new`.
  4. Suporte a zoom de anexos/imagens via `.materialboxed` / `initMaterialboxed()`.
