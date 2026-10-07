# Walkthrough: Ajuste do Zoom de Imagens (Estilo Feed Inline)

- **Data**: 2026-10-06
- **Status**: Concluído com sucesso
- **Tópico**: Substituição do Materialbox por expansão inline de imagens estilo feed com alternância no clique

---

## 1. Visão Geral das Alterações

Substituímos o antigo plugin de zoom modal `M.Materialbox` (que frequentemente apresentava comportamentos anômalos de coordenadas na viewport, travamento de rolagem e cortes de imagem em contêineres flex/grid) por uma solução inline nativa e responsiva:

- **Expansão no próprio contêiner ("Estilo Feed")**: Ao clicar na imagem, ela cresce suavemente preenchendo 100% da largura disponível no seu próprio contêiner, mantendo sua proporção original intacta (`object-fit: contain`).
- **Transição de "Lado a Lado" para "Feed"**:
  - Em listas com miniaturas lado a lado (`flex-wrap`), o item selecionado expande sua largura (`flex-basis: 100%`), ocupando uma linha inteira e empurrando os demais itens para baixo, comportando-se como um feed.
  - Em grades CSS (`display: grid`), o cartão do anexo se estende por todas as colunas da grade (`grid-column: 1 / -1`).
  - No chat de ocorrências, a imagem expande até a largura máxima da bolha de mensagem.
- **Alternância com novo clique**: Clicando novamente na imagem, ela retorna instantaneamente para o tamanho de miniatura e para a disposição lado a lado original.
- **Acessibilidade**: Suporte à tecla `ESC` para recolher qualquer imagem expandida.

---

## 2. Arquivos Modificados

1. [meu.css](file:///e:/DEV/recMan/meu.css):
   - Adicionadas regras para `.img-feed-toggle`, `.materialboxed`, `.img-feed-expanded`, `.img-feed-parent-expanded` e `.img-feed-grid-expanded`.
   - Cursores dinâmicos (`zoom-in` em miniatura, `zoom-out` quando expandida).
   - Transições suaves para dimensões, sombra e cantos arredondados.

2. [meu.js](file:///e:/DEV/recMan/meu.js):
   - Implementada a função `toggleImageFeed(imgEl)` para gerenciar a expansão e o recolhimento no clique.
   - Refatorada `initMaterialboxed()` para desativar instâncias do Materialize e registrar classes e eventos do modo feed.
   - Adicionado listener global de clique com delegação e suporte ao atalho de teclado `Escape`.

3. [livroDeOcorrencias.php](file:///e:/DEV/recMan/livroDeOcorrencias.php):
   - Unificada a imagem de anexo em notas internas para o padrão responsivo com suporte ao zoom feed.
   - Ajustada a chamada pós-AJAX do chat para utilizar o novo comportamento.

---

## 3. Como Validar

1. Acesse qualquer tela com anexos de imagens (ex: [palco/detalheRecurso.php](file:///e:/DEV/recMan/palco/detalheRecurso.php) nas seções de anexos do condômino, diligências ou comentários; ou [livroDeOcorrencias.php](file:///e:/DEV/recMan/livroDeOcorrencias.php) no chat de ocorrências).
2. Passe o mouse sobre uma miniatura: o cursor exibirá `zoom-in`.
3. Clique na imagem:
   - A imagem cresce sem sair da página ou abrir overlay escuro;
   - Ela ocupa a largura máxima disponível da div;
   - Se estiver ao lado de outras miniaturas, o contêiner se expande para o estilo feed ocupando a linha inteira;
   - A proporção da imagem é mantida sem distorção;
   - O cursor passa para `zoom-out`.
4. Clique novamente na imagem ou pressione a tecla `ESC`:
   - A imagem e a div retornam para o tamanho normal de miniatura lado a lado.
