# Raciocínio de Implementação: Renderização da Prévia do Parecer no Celular via PDF.js

**Data**: 2026-11-17  
**Contexto**: O usuário relatou que na tela `index.php?pag=emiteParecer&rec=...` o card `#previaPDF` não renderiza no celular (Android). Foi solicitada a viabilização da exibição direta na tela do dispositivo móvel.

---

## 1. Causa Raiz da Falha no Mobile

- No arquivo [`palco/emiteParecer.php`](file:///d:/dev/github/recMan/palco/emiteParecer.php#L171-L176):
  ```html
  <div class="card" id="previaPDF">
    <embed src="data:application/pdf;base64,<?php echo $parecerPdf; ?>" type="application/pdf" width="100%" height="600px">
  </div>
  ```
- **Limitações do Android / Chrome Mobile**:
  1. A tag `<embed>` depende de plugins nativos da plataforma. No Chrome de Desktop existe o plugin C++ `Chrome PDF Viewer` (PDFium), mas ele **não existe nas compilações do Chrome Mobile para Android**.
  2. Protocolos do tipo `data:application/pdf;base64` dentro de embeds/iframes são bloqueados por restrições de sandbox e política de segurança em webviews móveis.

---

## 2. Solução Implementada

1. **Adoção do Mozilla PDF.js (HTML5 Canvas)**:
   - Biblioteca padrão da indústria da Fundação Mozilla que decodifica o arquivo PDF na memória do cliente e o desenha em elementos `<canvas>`.
   - Suporte universal em 100% dos navegadores mobile (Android, iOS/Safari, Samsung Internet, Chrome, etc.).
   - Não depende de nenhum plugin do sistema operacional.
2. **Layout Responsivo e Experiência do Conselheiro**:
   - Barra superior com título e botão de destaque `[ Abrir em Tela Cheia ]` apontando para o endpoint [`ver_parecer.php`](file:///d:/dev/github/recMan/ver_parecer.php).
   - Container com fundo escuro simulando leitor profissional (`#525659`) e folhas brancas centralizadas com sombra suave (`box-shadow`), adaptando automaticamente a escala à largura da tela do celular.
