# Plano: Remoção do Efeito de Zoom em Imagens e Sanitização no Portal de Recursos

Este plano atende aos dois objetivos solicitados:
1. **Remoção do efeito de zoom das imagens**, ajustando o clique para apenas redimensionar a imagem até tocar a largura ou altura do elemento pai/viewport (ou manter o tamanho original se for menor), permitindo fechar com clique simples.
2. **Sanitização de número e ano no Portal de Recursos**, impedindo a digitação/envio de múltiplos separadores (como `322/26/2026`) tanto no frontend quanto no backend.

---

## Proposta de Alterações

### 1. `meu.js` - Visualizador de Imagens Limpo e Fluido
- **Remover completamente:**
  - Listener global de `wheel` com escala `scale(1..5)` e `e.preventDefault()`.
  - Listener de `scroll` travando `e.stopImmediatePropagation()`.
  - Listener de `click` em fase de captura (`capturing phase`) que bloqueava cliques na imagem ou overlay.
  - Barra de ferramentas flutuante `#materialbox-controls` com botões `add`, `remove`, `crop_free`, `close`.
  - Manipuladores de mouse para arrastar imagem com zoom (`mousedown`, `mousemove`, `mouseup`).
  - Handler de alternância de zoom 2.5x ao clicar na imagem ativa.
  - Manipuladores de touch pinch-to-zoom (`touchstart`, `touchmove`, `touchend`).
- **Comportamento refinado de `initMaterialboxed()`:**
  - Ao clicar na imagem (`onOpenStart` / `onOpenEnd`):
    - Calcular as dimensões máximas permitidas pelo elemento pai / viewport (ex: `window.innerWidth * 0.95` e `window.innerHeight * 0.92`).
    - Obter dimensões naturais (`el.naturalWidth` e `el.naturalHeight`).
    - Se a imagem for menor que o espaço disponível, manter seu tamanho original (sem esticar imagens pequenas).
    - Se for maior, redimensionar proporcionalmente até tocar a largura ou a altura máxima permitida.
    - Centralizar a imagem na tela e aplicar cursor para fechar (`cursor: zoom-out` ou `pointer`).
  - Ao clicar na imagem aberta ou no fundo escuro:
    - Fechar suavemente sem travas, retornando a miniatura ao estado inicial.
  - Ao fechar (`onCloseStart`):
    - Restaurar propriedades inline e garantir fluidez.

---

### 2. `portal/index.php` - Filtro no Primeiro Campo (Frontend)
- Nos inputs de número da notificação (`etapa 1` e `etapa 7`):
  - Adicionar `@keydown` impedindo a digitação do caractere `/`.
  - No `@input`, caso o usuário cole texto contendo `/` (ex: `322/26` ou `322/2026`):
    - Se contiver barra, separar e reter no primeiro campo apenas o que estiver antes da barra: `notificacaoStr = notificacaoStr.split('/')[0].trim()`.
    - Se o usuário colou o par completo (ex: `322/2026`) e o campo do ano estiver vazio ou com o ano padrão, preencher `anoStr` com a parte posterior se for um ano válido de 4 dígitos.
  - Criar o método `sanitizarNumero()` no Alpine.js para garantir que `notificacaoStr` nunca guarde barra nem caracteres inválidos.

---

### 3. `portal/api.php` - Sanitização Segura no Servidor (Backend)
- Criar a função auxiliar `sanitizarNumeroAno($numero, $ano)`:
  - Se `$numero` contiver `/` (ex: `322/26`), aplicar `explode('/', $numero)[0]` para isolar estritamente o número antes da barra.
  - Sanitizar `$ano` para manter apenas dígitos (`preg_replace('/[^\d]/', '', $ano)`).
  - Aplicar essa sanitização em todas as ações que recebem `numero` e `ano`:
    - `check_notification`
    - `resend_existing`
    - `send_token`
    - `submit`
    - `login`
    - `sincronizarNotificacaoSupabase`
  - Garantir que qualquer combinação como `numero = 322/26` e `ano = 2026` seja limpa no servidor para `numero = 322`, `ano = 2026` e `numeroCompleto = 322/2026`.
