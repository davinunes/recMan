# Plano de Implementação: Renderização da Prévia do Parecer no Mobile com PDF.js

**Data**: 2026-11-17  
**Status**: Pronto para Execução  
**Objetivo**: Substituir o `<embed>` legado de `palco/emiteParecer.php` por um visualizador responsivo baseado em Mozilla PDF.js e HTML5 Canvas, adicionando atalho para o endpoint `ver_parecer.php`.

---

## 1. Alterações Propostas

### 1.1 `palco/emiteParecer.php`
- Localizar `#previaPDF`.
- Substituir o `<embed src="data:application/pdf;base64,...">` por:
  - Cabeçalho com título e botão `[ Abrir em Tela Cheia ]` para `ver_parecer.php?rec=...`.
  - Container responsivo para injeção de `<canvas>` por página.
  - Script PDF.js carregado via CDN Cloudflare (`pdf.min.js`).
  - Código JS que converte a string Base64 `$parecerPdf` em `Uint8Array`, decodifica o documento e renderiza todas as páginas com escala fluida para celular e desktop.

---

## 2. Critérios de Sucesso
- No celular (Android / iOS), o parecer aparece renderizado página a página com total legibilidade.
- O botão "Abrir em Tela Cheia" abre o PDF inline diretamente na aba sem pedir para baixar.
- A experiência no desktop permanece intacta e visualmente mais profissional.
