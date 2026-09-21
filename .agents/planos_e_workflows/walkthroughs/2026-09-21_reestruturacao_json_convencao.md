# Walkthrough - Suporte a Itens Não Numerados (Traços) no JSON e Typst

- **Data**: 2026-09-21
- **Arquivos Modificados**:
  - `convencao_coletiva/convencao_coletiva_miami_json.json`
  - `typst_templates/regimento.typ`

## Resumo das Modificações Realizadas

### 1. Template Typst (`typst_templates/regimento.typ`)
- **Nova Função `render-tracos`**:
  - Implementada a função `#let render-tracos(tracos-dict, indent_left: 24pt, text_color: rgb("334155"))` para formatar itens não numerados com o marcador de traço (`- `).
- **Integração**:
  - Conectada a `render-anexo1-itens()`, `render-alineas()`, `render-incisos()` e ao laço principal de artigos.

### 2. JSON da Convenção Coletiva (`convencao_coletiva_miami_json.json`)
- **Item 5.1 ("Geral")**:
  - Configurado para utilizar a sub-chave `"tracos"` no conteúdo de destinação residencial, evitando numeração decimal desnecessária.
- **Item 5.3.1 (Cuidados de Segurança)**:
  - Substituída a sub-chave `"alineas"` por `"tracos"` para exibir os 3 itens de cuidados com o marcador de traço (`- `).

## Validação Realizada
- Validação estática de sintaxe Typst e JSON executada com sucesso.
