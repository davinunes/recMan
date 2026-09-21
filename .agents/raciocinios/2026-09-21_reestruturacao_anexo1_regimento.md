# Thinking Log - Reestruturação do Anexo 1 (Regimento Interno) no JSON e Template Typst

- **Data**: 2026-09-21
- **Arquivos Alvo**: 
  - `convencao_coletiva/convencao_coletiva_miami_json.json`
  - `typst_templates/regimento.typ`

## 1. Diagnóstico e Contexto
O usuário solicitou a reformulação do Anexo 1 (Regimento Interno) para alinhar a estrutura a 10 Seções/Capítulos e utilizar numeração decimal composta herdada (ex: `5.3.5`), preservando a hierarquia JSON e a legibilidade no template Typst.

### As 10 Seções Oficiais do Anexo 1:
1. Disposições Gerais
2. Horário
3. Uso das Coisas Comuns
4. Empregados
5. Uso privativo do condômino e/ou morador
6. Taxa de Condomínio
7. Mudanças
8. Penalidades
9. Disposições Finais
10. Foto

### Regras de Formatação e Layout:
1. **Primeiro Dígito**: Atua como numerador da Seção/Capítulo (`1` a `10`). Todos os itens abaixo herdam este prefixo.
2. **Segundo e Terceiro Dígitos**: Indicam a sub-seção e o item numérico específico (ex: `5.3.5`).
3. **Alíneas (Letras)**: Quando o nó contiver alíneas, são exibidas com letras (`a)`, `b)`...).
4. **Listas não numeradas / Bullets**: Itens simples dentro de nós (como em `5.3.1`) são exibidos sem prefixo decimal extra, utilizando marcação de item/bullet.

## 2. Estratégia de Ajuste no JSON
- No nó `anexo1`:
  - Adicionar o dicionário `"secoes"` com os 10 capítulos (1 a 10).
  - Estruturar a árvore sob `"itens"`, onde a raiz contém as 10 seções (`"1"` a `"10"`), e cada nó pode conter recursivamente `"itens"`, `"alineas"` e `"texto"`.

## 3. Estratégia de Ajuste no Template Typst (`regimento.typ`)
- Criar a função recursiva `#let render-anexo1-itens(itens-dict, prefix: "", indent_left: 12pt, text_color: rgb("334155"))`.
- A função acumula o prefixo numérico decimal (`prefix + "." + key`, resultando em `1.1`, `5.3.5`, etc.).
- Ao encontrar alíneas, delega para `render-alineas()`.

## 4. Ações Planejadas
1. Atualizar o arquivo `convencao_coletiva/convencao_coletiva_miami_json.json`.
2. Atualizar o arquivo `typst_templates/regimento.typ`.
3. Registrar plano de implementação e walkthrough.
