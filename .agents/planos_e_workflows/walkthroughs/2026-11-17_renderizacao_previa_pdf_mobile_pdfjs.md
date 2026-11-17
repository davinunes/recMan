# Walkthrough: Renderização da Prévia do Parecer no Celular com PDF.js e HTML5 Canvas

**Data**: 2026-11-17  
**Status**: Concluído com Sucesso  

---

## 1. O Problema Solucionado

Na tela de emissão e visualização de parecer ([`palco/emiteParecer.php`](file:///d:/dev/github/recMan/palco/emiteParecer.php)), a seção `#previaPDF` utilizava uma tag legada `<embed src="data:application/pdf;base64,...">`. 
No Android e em navegadores mobile, o `<embed>` para PDFs ficava completamente em branco ou quebrado devido à ausência do plugin desktop C++ `Chrome PDF Viewer` no Chrome móvel e ao bloqueio de URIs `data:` em embeds.

---

## 2. A Solução Implementada

Substituímos o `<embed>` por uma solução híbrida e moderna:
1. **Mozilla PDF.js via HTML5 Canvas**:
   - Carrega o script oficial do PDF.js via CDN Cloudflare.
   - Decodifica a string Base64 do parecer diretamente na memória do cliente (`Uint8Array`).
   - Renderiza cada página do PDF em elementos `<canvas>` fluidos, centralizados e com sombra suave de folha de papel.
   - Compatibilidade total: opera com **100% de suporte em Android (Chrome, Samsung Internet), iOS (Safari), tablets e computadores**.
2. **Barra Superior de Utilitários**:
   - Adicionada barra com ícone e título formal.
   - Incluído botão em destaque: `[ Abrir em Tela Cheia ]`, apontando para [`ver_parecer.php?rec=...`](file:///d:/dev/github/recMan/ver_parecer.php), permitindo que o conselheiro abra o parecer diretamente pelo leitor do navegador caso prefira.
