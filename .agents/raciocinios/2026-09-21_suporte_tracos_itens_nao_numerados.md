# Thinking Log - Suporte a Itens Não Numerados (Traços / Bullets) no JSON e Typst

- **Data**: 2026-09-21
- **Arquivos Alvo**: 
  - `convencao_coletiva/convencao_coletiva_miami_json.json`
  - `typst_templates/regimento.typ`

## 1. Contexto e Diagnóstico
O usuário indicou a necessidade de suportar itens não numerados exibidos por traços (`-`) ou bullets no Anexo 1. Especificamente:
- No item `5.1` ("Geral"): o conteúdo não é numerado como `5.1.1`, devendo ser exibido com traço (`-`).
- No item `5.3.1`: os 3 itens de recomendações de segurança (fechamento de portão, portas principais, acompanhamento de visitas) são enumerados por traços (`-`) e não por alíneas (`a`, `b`, `c`).

## 2. Solução Proposta
1. **No JSON (`convencao_coletiva_miami_json.json`)**:
   - Introduzir a sub-chave `"tracos"` nos nós desejados (como `5.1` e `5.3.1`).
2. **No Template Typst (`regimento.typ`)**:
   - Criar a função helper `#let render-tracos(tracos-dict, indent_left: 24pt, text_color: rgb("334155"))`.
   - Adicionar a verificação de `"tracos"` em `render-anexo1-itens()`, `render-alineas()` e `render-incisos()`, formatando com o marcador de traço (`- `).

## 3. Ações Planejadas
1. Atualizar `typst_templates/regimento.typ`.
2. Atualizar `convencao_coletiva/convencao_coletiva_miami_json.json`.
3. Atualizar o plano de implementação e walkthrough.
