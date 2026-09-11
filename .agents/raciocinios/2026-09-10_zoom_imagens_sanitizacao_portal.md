# Raciocínio de Diagnóstico e Implementação

## Data: 10/09/2026
## Tópicos:
1. Remoção do efeito de zoom bugado nas imagens e ajuste responsivo para limites do elemento pai / viewport
2. Sanitização de notificações no Portal de Recursos (filtro frontend e backend para formato numero/ano)
3. Correção do clique da imagem (conflito do evento de borbulhamento) e flexibilização da digitação no Portal

---

## 1. Contexto e Diagnóstico

### 1.1 Zoom nas Imagens (`meu.js`)
- **Problema Inicial**: O sistema incorporou diversos manipuladores para tentar dar zoom em cima do plugin `materialbox` do Materialize CSS (wheel listener com `scale`, pan/drag com `mousedown`/`mousemove`, alternância para 2.5x ao clicar, pinch zoom em mobile, captura agressiva de cliques com `e.stopImmediatePropagation()` para barrar fechamento, botões flutuantes `#materialbox-controls`).
- **Problema de "Não Acontece Nada ao Clicar"**:
  - Havia sido adicionado o ouvinte delegado:
    `$(document).on('click', '.materialboxed.active', function(e) { instance.close(); });`
  - Ao clicar na imagem para abri-la, o Materialize adicionava a classe `active` e o clique borbulhava (`event bubbling`) até `document`.
  - O seletor `.materialboxed.active` coincidia no mesmo clique inicial e invocava `instance.close()`, fechando a imagem instantaneamente no mesmo milissegundo de abertura.
- **Solução Implementada**:
  - Removido o ouvinte delegado em `document`.
  - O ouvinte de fechamento foi transferido exclusivamente para o evento `onOpenEnd`, usando `$img.off('click.mbClose').one('click.mbClose', ...)`. Assim ele só escuta o próximo clique após a imagem já estar aberta.
  - Imagem dimensionada até o limite da área visível (`95vw x 92vh`) ou mantendo o tamanho natural caso seja menor, sem deformar nem esticar imagens de baixa resolução.
  - O fechamento restaura estilos originais da miniatura e destrava o scroll da página.
  - Atualizadas as chamadas em [`palco/detalheRecurso.php`](file:///e:/DEV/recMan/palco/detalheRecurso.php#L1607) e [`livroDeOcorrencias.php`](file:///e:/DEV/recMan/livroDeOcorrencias.php#L1540) para usar `initMaterialboxed()`.

### 1.2 Sanitização do Número de Notificação no Portal (`portal/index.php` e `portal/api.php`)
- **Problema Inicial**: Condôminos digitavam ou colavam `322/26` no campo Número e `2026` no campo Ano, gerando `322/26/2026`.
- **Ajuste Solicitado pelo Usuário**:
  - O usuário deve poder digitar a barra `/` livremente no campo (sem bloqueios com `@keydown` ou cortes durante a digitação que poderiam juntar os números como `32226`).
  - Apenas no momento do envio (`submit`, `checarNotificacao`, `login`, etc.) removemos a barra e qualquer conteúdo à direita no primeiro campo, retendo apenas `322`.
  - No backend (`portal/api.php`), a função `sanitizarNumeroAnoNotificacao()` higieniza as entradas POST globalmente, descartando a barra e o conteúdo à direita no número e mantendo apenas dígitos no ano.
