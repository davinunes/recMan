# Raciocínio de Arquitetura: Ações sem Reload por AJAX e Grupo de Ícones de Responsabilidade

- **Data**: 2026-07-31
- **Tópico**: Eliminação de reloads e skeleton UI nas ações de "Marcar Resolvido", "Marcar Lido" e alteração de "Responsabilidade", além da substituição do `<select>` por grupo de botões ícone interativos baseados no schema do banco (`sindico`, `sub`, `NULL`).

## 1. Análise da Responsabilidade no Banco de Dados
- **Schema Identificado**: O campo `responsabilidade` na tabela `ocorrencias` suporta os valores:
  - `'sindico'` (Síndico)
  - `'sub'` (Subsíndico)
  - `NULL` ou `''` (Não Atribuído / Pendente)
- **Substituição da UI**:
  - O antigo `<select>` genérico continha opções que não existiam no banco.
  - Substituiremos por um grupo de 3 botões ícone interativos:
    - **Não Atribuído** (`''`): Ícone `person_off`, destaque em cinza neutro.
    - **Síndico** (`'sindico'`): Ícone `gavel`, destaque em vermelho/crimson (`#dc3545`).
    - **Subsíndico** (`'sub'`): Ícone `badge`, destaque em roxo/púrpura (`#6f42c1`).

## 2. Eliminação do Skeleton UI e Reloads em Ações do Chat
- **Problema**: Atualmente, clicar em "Marcar Resolvido", "Marcar Lido" ou alterar a responsabilidade faz um POST com reload de página ou dispara o container de skeleton na conversa inteira.
- **Solução**:
  1. No PHP ([livroDeOcorrencias.php](file:///e:/DEV/recMan/livroDeOcorrencias.php)), quando `is_ajax=1` ou cabeçalho `XMLHttpRequest` for enviado, os handlers de ação processam a query no banco e retornam uma resposta JSON direta (`{ success: true, ... }`) sem renderizar a página.
  2. No JavaScript, a requisição é feita via `$.ajax`:
     - Durante o envio, apenas o botão clicado exibe uma animação sutil (spinner/opacidade), sem piscar a conversa ou disparar skeleton na tela.
     - Ao retornar com sucesso, o botão alterna suas cores, ícone e texto dinamicamente.
     - Os selos e descrições do item correspondente na barra lateral (ex: "✓ Resolvido", "Resp: SÍNDICO") são atualizados no DOM instantaneamente.
