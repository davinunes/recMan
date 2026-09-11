# Walkthrough: Remoção do Efeito de Zoom e Sanitização no Portal de Recursos

Todas as alterações solicitadas foram implementadas e validadas no código:

## 1. Remoção do Efeito de Zoom das Imagens (`meu.js`)
- **Código Legado Removido**:
  - Removido listener global de `wheel` que aplicava escala/zoom com scroll do mouse e travava o scroll da página.
  - Removido listener de captura de clique que impedia fechamento natural do visualizador.
  - Removidos handlers de arrasto (pan) com mouse (`mousedown`, `mousemove`, `mouseup`).
  - Removido toggle de escala 2.5x ao clicar na imagem ampliada.
  - Removidos gestos touch de pinch-to-zoom.
  - Removida injeção de botões flutuantes `#materialbox-controls`.
- **Novo Comportamento**:
  - Ao clicar na imagem, ela se expande proporcionalmente até o limite máximo permitido pelo elemento pai / viewport (`95vw × 92vh`).
  - Se a imagem tiver dimensões naturais menores que o espaço disponível, seu tamanho original é preservado sem pixelar ou esticar.
  - Ao clicar diretamente na imagem aberta ou no fundo escuro, ela fecha de forma fluida e retorna perfeitamente ao tamanho da miniatura.

## 2. Sanitização no Portal de Recursos

### Frontend (`portal/index.php`)
- **Bloqueio de Digitação**: No primeiro campo (Número da Notificação), o evento `@keydown` bloqueia a digitação da barra `/`.
- **Higienização de Conteúdo Colado**: Se o usuário colar algo contendo `/` (ex: `322/26` ou `322/2026`):
  - O método `sanitizarCamposNotificacao()` retém no primeiro campo exclusivamente a parte antes da primeira barra (`322`).
  - Caso colado o par completo (ex: `322/2026`), o ano de 4 dígitos é automaticamente aproveitado no campo do Ano.
- **Chamada Preventiva**: A sanitização é executada no `@input` e antes dos envios em `checarNotificacao()`, `enviarCodigo()`, `enviarRecursoFinal()` e `loginExisting()`.

### Backend (`portal/api.php`)
- **Sanitização Global e Preventiva**: Criada a função `sanitizarNumeroAnoNotificacao($numero, $ano)`.
- Se o campo número contiver barra (ex: `322/26`), o PHP separa por `/` e utiliza estritamente o primeiro trecho (`322`).
- O ano é filtrado com `preg_replace('/[^\d]/', '', $ano)` para manter somente dígitos numéricos.
- O `$numeroCompleto` fica garantido no formato `322/2026` em todos os fluxos e endpoints da API.
