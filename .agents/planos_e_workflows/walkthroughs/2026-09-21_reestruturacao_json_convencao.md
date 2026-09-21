# Walkthrough - Padronização Semântica de Listas Não Numeradas (`lista`) no JSON e Typst

- **Data**: 2026-09-21
- **Arquivos Modificados**:
  - `convencao_coletiva/convencao_coletiva_miami_json.json`
  - `typst_templates/regimento.typ`

## Resumo das Modificações Realizadas

### 1. Refatoração Semântica do JSON (`convencao_coletiva_miami_json.json`)
- Adotada a sub-chave **`"lista"`** para identificar nós contendo listas não numeradas (substituindo o nome específico de marcador).
- Aplicada a sub-chave `"lista"` no item `5.1` ("Geral") e no item `5.3.1` (cuidados de segurança).

### 2. Template Typst (`typst_templates/regimento.typ`)
- Implementada a função `#let render-lista(lista-dict, indent_left: 24pt, text_color: rgb("334155"), marker: "- ")`.
- A função aceita o parâmetro opcional `marker`, desvinculando a estrutura de dados do símbolo visual (permitindo usar traço `-`, bullet `•`, etc.).
- Mantida a retrocompatibilidade com a chave `"tracos"`.

## Validação Realizada
- Validação estática de sintaxe Typst e JSON executada com êxito.
