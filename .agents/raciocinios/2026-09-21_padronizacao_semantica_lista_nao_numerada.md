# Thinking Log - Padronização Semântica de Listas Não Numeradas (`lista`) no JSON e Typst

- **Data**: 2026-09-21
- **Arquivos Alvo**:
  - `convencao_coletiva/convencao_coletiva_miami_json.json`
  - `typst_templates/regimento.typ`

## 1. Diagnóstico e Refinamento Arquitetural
Substituir o termo específico `"tracos"` pelo termo semântico **`"lista"`** (lista não numerada) na estrutura de dados do JSON.
Isso desacopla a representação de dados do marcador gráfico, permitindo que cada template/renderizador (Typst, HTML, PDF) decida como exibir os marcadores (traço `-`, bullet `•`, etc.) dinamicamente.

## 2. Ações Planejadas
1. **No JSON (`convencao_coletiva_miami_json.json`)**:
   - Substituir a sub-chave `"tracos"` por `"lista"` nos nós de listas não numeradas (ex: `5.1` e `5.3.1`).
2. **No Template Typst (`regimento.typ`)**:
   - Renomear a função helper para `#let render-lista(lista-dict, indent_left: 24pt, text_color: rgb("334155"), marker: "- ")`.
   - Adicionar o parâmetro opcional `marker` para controle de variante tipográfica.
   - Atualizar a integração nos renderizadores recursivos.
