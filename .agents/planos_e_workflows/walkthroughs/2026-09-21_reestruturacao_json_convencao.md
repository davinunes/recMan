# Walkthrough - Reestruturação Completa da Convenção e das 10 Seções do Anexo 1 (Miami Beach)

- **Data**: 2026-09-21
- **Arquivos Modificados**:
  - `convencao_coletiva/convencao_coletiva_miami_json.json`
  - `typst_templates/regimento.typ`

## Resumo das Modificações Realizadas

### 1. Convenção Coletiva e 10 Seções do Anexo 1 (`convencao_coletiva_miami_json.json`)
- **Prólogo e Capítulos da Convenção**:
  - Adicionada a propriedade `"prologo"` com o preâmbulo do art. 9º da Lei 4.591/64.
  - Definidos os 10 Capítulos da Convenção em `"capitulos"`.
  - Associada a chave `"capitulo": N` nos 38 artigos.
- **Artigos 6, 16 e 19**:
  - Artigo 6: aviso `[Tabelas de Localização das Vagas Omitidas]`, parágrafos de 6.4 e estremação completa em 6.5.
  - Artigo 16: quóruns estruturados sob a chave `"itens"`.
  - Artigo 19: alíneas de `a` a `n` (Síndico) e `a` a `d` (Subsíndico).
- **Anexo 1 (Regimento Interno em 10 Seções)**:
  - Adicionado o dicionário `"secoes"` com os 10 capítulos oficiais:
    1. `1 - Disposições Gerais`
    2. `2 - Horário`
    3. `3 - Uso das Coisas Comuns`
    4. `4 - Empregados`
    5. `5 - Uso privativo do condômino e/ou morador`
    6. `6 - Taxa de Condomínio`
    7. `7 - Mudanças`
    8. `8 - Penalidades`
    9. `9 - Disposições Finais`
    10. `10 - Foto`
  - Reorganizada a árvore hierárquica sob `anexo1.itens` com herança decimal.

### 2. Template Typst (`typst_templates/regimento.typ`)
- **Renderização Decimal Composta Herdada (`render-anexo1-itens`)**:
  - Desenvolvida a função recursiva `#let render-anexo1-itens(itens-dict, prefix: "", indent_left: 6pt, text_color: rgb("334155"))`.
  - Acumula e exibe automaticamente notações como `5.3.5`, `3.2.14` e `1.2.1`.
  - Delega para `render-alineas()` quando houver alíneas (`a)`, `b)`...).
- **Suporte a Prólogo e Cabeçalho do Anexo I**:
  - Bloco de Preâmbulo renderizado em caixa itálica no início do documento.
  - Cabeçalho oficial do Anexo I renderizado em destaque acima do título do Regimento.

## Validação Realizada
- Validação estática de sintaxe Typst e JSON executada com êxito.
