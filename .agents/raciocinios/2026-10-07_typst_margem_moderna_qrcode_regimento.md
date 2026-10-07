# Raciocínio: Correção do Layout Margem Moderna e QR Code com Link na Capa do Regimento / Convenção

**Data:** 07/10/2026  
**Contexto:** `palco/test_typst.php?action=test_regimento`, `typst_templates/regimento.typ`, `py/typst_server.py`

---

## 1. Diagnóstico do Problema 1: Margem Moderna Sem Capa e Sem Imagem de Fundo
- **Causa Raiz 1 (Fundo):** No arquivo `typst_templates/regimento.typ`, o bloco `page(background: context { ... })` possuía apenas condições para `watermark_a4` (`lay-body-all-pages-1.png`) e `top_header` (`lay-top-fist-page-1.png`). A variante `margem_moderna`, que em `parecer.typ` utiliza `lay-body-all-pages-2.png`, não constava no `background` do template do regimento.
- **Causa Raiz 2 (Capa):** A estrutura de capas do `regimento.typ` possuía blocos `if/else if` para `top_header`, `watermark_a4`, `modern`, `corporate`, `minimal`, `juridico`, `dark`, `editorial` e `classic`. A opção `margem_moderna` não era capturada por nenhuma dessas ramificações, resultando na ausência total de capa e exibição direta do sumário.
- **Cores da Variante:** As cores institucionais de `margem_moderna` não estavam associadas ao tom Verde Esmeralda (`#065f46`), o que foi devidamente alinhado.

---

## 2. Diagnóstico e Arquitetura do Problema 2: Campo URL e QR Code na Capa
- **Objetivo do Usuário:** Disponibilizar na capa do Regimento Interno e da Convenção de Condomínio um link clicável e um QR Code apontando para a via original registrada em cartório.
- **Desafios e Soluções:**
  1. **Dependências Externas no Typst e Python:** No servidor remoto, o Typst roda em sandbox local `--root ROOT_DIR` e pode não dispor de bibliotecas pip como `qrcode` ou `pillow`, nem pacotes remotos do Typst Universe.
  2. **Geração Vetorial SVG Pura (`py/qrcodegen.py`):** Foi incluída a implementação standalone de zero dependência do Project Nayuki (MIT) em `py/qrcodegen.py`. Ela gera SVGs puros e perfeitos em milissegundos sem necessidade de pacotes C ou pip.
  3. **Integração no Microserviço (`py/typst_server.py`):** Ao receber `url_cartorio` no payload JSON, o servidor gera `qr_<hash>.svg` dentro de `typst_templates/`, passa o caminho para o Typst e deleta o arquivo temporário no bloco `finally:`.
  4. **Template Typst (`regimento.typ`):** Criação da função auxiliar `render-bloco-cartorio(url, texto, qr_img)`, que embute o link `#link(url)[...]` com sublinhado e estilização da variante, seguida da renderização vetorial `#image(qr_img, width: 2.7cm)` e legenda de escaneamento. O bloco foi inserido na nova capa de `margem_moderna` e em todas as demais capas quando a URL for fornecida.
  5. **Interface Web (`palco/test_typst.php`):**
     - Adicionados inputs para URL e texto do link no Card 1.
     - Pré-carregamento automático dos valores salvos nos JSONs (`regimento/database.json` e `convencao_coletiva_miami_json.json`).
     - Alternância dinâmica no frontend via `atualizarDocConfig()` ao mudar o select entre Regimento e Convenção.
     - Persistência automática das URLs nos respectivos arquivos JSON quando atualizadas pelo usuário.
