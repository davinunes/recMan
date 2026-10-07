# Raciocínio: Ajuste de Zoom de Imagens (Estilo Feed Inline)

- **Data**: 2026-10-06
- **Assunto**: Substituição do efeito de zoom bugado do Materialbox por expansão inline "modo feed" ao clicar na imagem

## Contexto e Diagnóstico do Problema

1. O sistema vinha utilizando o componente `M.Materialbox` do Materialize CSS (`initMaterialboxed` em `meu.js`).
2. O Materialbox do Materialize clona/envolve a imagem em wrappers (`.material-placeholder`), cria uma camada de overlay na página, bloqueia o scroll do `body` e força o elemento a `position: fixed` com cálculos de coordenadas absolutas baseadas na tela (`vh`, `vw`, `newW`, `newH`).
3. Em layouts complexos com flexbox (`flex-wrap`), CSS Grid, cards ou modais de chat (como em `detalheRecurso.php` e `livroDeOcorrencias.php`), esses cálculos de coordenadas frequentemente quebravam:
   - A imagem ficava desalinhada ou saía da tela;
   - O scroll travava ou voltava para posições incorretas;
   - As imagens ficavam cortadas dentro de wrappers com `overflow: hidden`.

## Requisito do Usuário

> "o efeito de zoom ao clicar em imagens ainda tá bugando. Vamos ajustar para a imagem simplesmente crescer dentro da div onde já está ocupando a largura maxima disponivel, sem preder a proporção, alterando de estiilo lado a lado para estilo feed. Clicando novamente volta ao normal."

### Pontos-Chave:
1. **Dentro da div onde já está**: Sem popup, sem modal, sem overlay fixed fora da hierarquia visual.
2. **Largura máxima disponível sem perder a proporção**: `width: 100%`, `max-width: 100%`, `height: auto`, `object-fit: contain`.
3. **De estilo lado a lado para estilo feed**: Quando miniaturas estão lado a lado (em flex-wrap ou CSS grid), ao clicar, a imagem/card expande ocupando 100% da linha horizontal (estilo feed), empurrando os demais itens.
4. **Clicando novamente volta ao normal**: Toggle do estado, retornando às dimensões de miniatura originais e ao layout lado a lado.
5. **Compatibilidade total**: Manter compatibilidade com a classe `.materialboxed` já presente nas views e oferecer suporte à classe `.img-feed-toggle`.

## Estratégia de Implementação

1. **CSS (`meu.css`)**:
   - Classes `.img-feed-toggle`, `.materialboxed` com cursor `zoom-in` e transição suave.
   - Classe `.img-feed-expanded` para a imagem com `cursor: zoom-out !important`, `width: 100% !important`, `max-width: 100% !important`, `height: auto !important`, `max-height: none !important`, `object-fit: contain !important`.
   - Classe `.img-feed-parent-expanded` para a div pai / item flex (`flex-basis: 100% !important; width: 100% !important; max-width: 100% !important; height: auto !important; max-height: none !important;`).
   - Classe `.img-feed-grid-expanded` para cartões em CSS Grid (`grid-column: 1 / -1 !important; width: 100% !important;`).

2. **JavaScript (`meu.js`)**:
   - Refatorar `initMaterialboxed` para inicializar a nova funcionalidade e remover instâncias do antigo Materialbox.
   - Implementar `toggleImageFeed(img)` para alternar as classes no clique.
   - Interceptar cliques em `.materialboxed, .img-feed-toggle` bloqueando o antigo plugin do Materialize.
   - Adicionar listener para tecla ESC fechar imagens expandidas.

3. **Views (`detalheRecurso.php` e `livroDeOcorrencias.php`)**:
   - Adicionar classes auxiliares semânticas nos wrappers das imagens de anexos do recurso, diligências, comentários e chat de ocorrências.
