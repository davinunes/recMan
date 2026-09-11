# Raciocínio de Diagnóstico e Implementação

## Data: 10/09/2026
## Tópicos:
1. Remoção do efeito de zoom bugado nas imagens e ajuste responsivo para limites do elemento pai / viewport
2. Sanitização de notificações no Portal de Recursos (filtro frontend e backend para formato numero/ano)

---

## 1. Contexto e Diagnóstico

### 1.1 Zoom nas Imagens (`meu.js`)
- **Problema**: O sistema incorporou diversos manipuladores para tentar dar zoom em cima do plugin `materialbox` do Materialize CSS (wheel listener com `scale`, pan/drag com `mousedown`/`mousemove`, alternância para 2.5x ao clicar, pinch zoom em mobile, captura agressiva de cliques com `e.stopImmediatePropagation()` para barrar fechamento, botões flutuantes `#materialbox-controls`).
- **Sintomas**: Bloqueio indevido de cliques, scroll travado do documento, desorientação ao fechar a imagem, perda de controle de navegação e comportamento imprevisível em resoluções diferentes.
- **Solução Solicitada**:
  - Remover todo o efeito e artefatos de zoom interativo.
  - Ao clicar na imagem, alterar simplesmente as dimensões para o máximo possível até tocar a largura ou a altura do elemento pai (viewport/área do container visível), ou manter o tamanho original se este for menor (sem esticar imagens de baixa resolução desnecessariamente).
  - Permitir fechamento natural ao clicar na imagem ou fora dela.

### 1.2 Sanitização do Número de Notificação no Portal (`portal/index.php` e `portal/api.php`)
- **Problema**: Moradores estão digitando ou colando strings com barra no primeiro campo do formulário (ex: digitando `322/26` no campo "Número" e `2026` no campo "Ano"), resultando em números inconsistentes como `322/26/2026`.
- **Solução Solicitada**:
  - No frontend (`portal/index.php`):
    - Bloquear digitação da barra `/` no primeiro campo (`notificacaoStr`).
    - Ao colar ou alterar o valor, se houver `/`, remover automaticamente a barra e qualquer caractere posterior (ou preencher o ano automaticamente caso colado o par completo).
  - No backend (`portal/api.php`):
    - Sanitizar `$_POST['numero']` e `$_POST['ano']` em todos os endpoints (`check_notification`, `resend_existing`, `send_token`, `submit`, `login`).
    - Se `$numero` contiver `/`, descartar o que estiver após a barra e pegar apenas a primeira parte (`explode('/', $numero)[0]`).
    - Limpar `$ano` deixando apenas dígitos (`preg_replace('/[^\d]/', '', $ano)`).
    - Garantir que `$numeroCompleto` fique estritamente no padrão `[numero]/[ano]`, por exemplo `322/2026`.
