# Walkthrough - Implementação de Novos Templates Typst com Imagens de Topo e Marca d'Água

## Data: 2026-09-26

## Resumo das Modificações

Adicionadas duas novas variantes de layout visual para geração de PDFs com Typst CLI:

1. **Variante `top_header` ("Novo Topo 1ª Pág")**:
   - Utiliza a imagem `addons/api-pdf/lay-top-fist-page-1.png`.
   - Posicionada exatamente nos vértices superiores da 1ª página (sem bordas/margens no topo).
   - Estilo visual base no Parecer: **Editorial / Verde Esmeralda**.
   - Estilo visual base no Regimento: **Moderno**.

2. **Variante `watermark_a4` ("Nova Marca d'Água A4 Full")**:
   - Utiliza a imagem `addons/api-pdf/lay-body-all-pages-1.png`.
   - Posicionada como fundo de página em formato A4 integral (`210mm x 297mm`) atrás de todo o texto em todas as páginas.
   - Estilo visual base no Parecer: **Editorial / Verde Esmeralda**.
   - Estilo visual base no Regimento: **Moderno**.

---

## Arquivos Modificados / Criados

- [`typst_templates/parecer.typ`](file:///e:/DEV/recMan/typst_templates/parecer.typ): Adicionadas regras para `top_header` e `watermark_a4`.
- [`typst_templates/regimento.typ`](file:///e:/DEV/recMan/typst_templates/regimento.typ): Adicionadas regras para `top_header` e `watermark_a4`.
- [`py/typst_server.py`](file:///e:/DEV/recMan/py/typst_server.py): Atualizada sincronização de imagens em `typst_templates/`.
- [`palco/test_typst.php`](file:///e:/DEV/recMan/palco/test_typst.php): Adicionadas as novas variantes no painel de testes.
- [`palco/configuracoes_pdf.php`](file:///e:/DEV/recMan/palco/configuracoes_pdf.php): Adicionadas as novas opções no seletor de template padrão.
- [`.agents/raciocinios/2026-09-26_novos_templates_imagens_typst.md`](file:///e:/DEV/recMan/.agents/raciocinios/2026-09-26_novos_templates_imagens_typst.md): Raciocínio técnico.

---

## Como Validar no Ambiente de Testes
1. Acesse `palco/test_typst.php`.
2. No menu de variantes, selecione **Novo Topo 1ª Pág (lay-top-fist-page-1.png)** ou **Nova Marca d'Água A4 Full (lay-body-all-pages-1.png)**.
3. Teste gerando tanto o **Regimento Interno** quanto um **Parecer**.
4. Verifique que:
   - Na opção de topo, a imagem fica encostada nas bordas superiores da 1ª página.
   - Na opção marca d'água, o fundo A4 preenche toda a extensão da página por trás do conteúdo.
