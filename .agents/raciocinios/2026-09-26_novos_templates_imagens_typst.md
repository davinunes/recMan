# Diagnóstico e Raciocínio: Novos Templates com Imagens no Typst CLI

## Data: 2026-09-26

## Requisitos do Usuário
O usuário forneceu duas novas imagens em `addons/api-pdf/`:
1. `lay-top-fist-page-1.png`: Imagem para o topo da 1ª página, sem bordas/margens superiores (encostada nos vértices superiores da página).
2. `lay-body-all-pages-1.png`: Imagem de marca d'água A4 para cobrir toda a página em todas as páginas, posicionada atrás do texto sem borda.

Solicitou a criação de duas novas variantes/templates visuais tanto para o **Parecer** quanto para o **Regimento Interno**, sem modificar os estilos existentes:
- Para o **Parecer**: base em `editorial` (Verde Esmeralda).
- Para o **Regimento**: base em `modern` (Moderno).

## Implementação Efetuada

1. **Cópia e Sincronização Automática das Imagens**:
   - As imagens `lay-top-fist-page-1.png` e `lay-body-all-pages-1.png` foram copiadas para a pasta `typst_templates/`.
   - Atualizado o servidor Python (`py/typst_server.py`) na função `prepare_banner_image` para garantir a sincronização automática dessas imagens no ambiente de produção remoto.

2. **Novas Variantes em `typst_templates/parecer.typ`**:
   - `top_header`: Base Verde Esmeralda (`#065f46`), renderizando a imagem `lay-top-fist-page-1.png` no vértice `(0,0)` apenas na 1ª página via `#place(top + left, dx: -2cm, dy: -2.5cm)[#image("lay-top-fist-page-1.png", width: 210mm)]`.
   - `watermark_a4`: Base Verde Esmeralda (`#065f46`), renderizando a imagem `lay-body-all-pages-1.png` no fundo de todas as páginas em A4 completo via `#place(top + left, dx: -2cm, dy: -2.5cm)[#image("lay-body-all-pages-1.png", width: 210mm, height: 297mm)]`.

3. **Novas Variantes em `typst_templates/regimento.typ`**:
   - `top_header`: Base Estilo Moderno (`#0f172a`), renderizando `lay-top-fist-page-1.png` no topo da 1ª página.
   - `watermark_a4`: Base Estilo Moderno (`#0f172a`), renderizando `lay-body-all-pages-1.png` como marca d'água em formato A4 em todas as páginas.

4. **Atualização dos Painéis e Seletores**:
   - Atualizados `palco/test_typst.php` e `palco/configuracoes_pdf.php` para listar e permitir selecionar as opções `top_header` e `watermark_a4`.
