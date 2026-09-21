# Thinking Log - Ajuste no Template Typst regimento.typ para Prólogo, Itens e Cabeçalho do Anexo I

- **Data**: 2026-09-21
- **Arquivo Alvo**: `typst_templates/regimento.typ`

## 1. Contexto e Necessidade
Após a atualização da estrutura JSON da Convenção Coletiva e seu Anexo I (`convencao_coletiva_miami_json.json`), identificamos a necessidade de atualizar o template Typst `regimento.typ` para suportar 3 novas construções:

1. **Suporte a Prólogo (`prologo`)**:
   - Renderizar o preâmbulo formal antecedente ao Capítulo I e Artigo 1 em uma caixa de destaque com visual elegante.

2. **Suporte a Itens dentro de Alíneas e Incisos (`itens`)**:
   - O Artigo 16 e outros dispositivos agora contêm sub-itens numerados (`"1"`, `"2"`, `"3"`) aninhados sob alíneas.
   - Criar a função helper `render-itens()` e invocá-la recursivamente em `render-alineas()` e `render-incisos()`.

3. **Suporte ao Cabeçalho do Anexo I (`cabecalho`)**:
   - Exibir o texto do cabeçalho oficial do Anexo I acima do título do Regimento Interno no início da seção correspondente.

## 2. Ações Planejadas
1. Alterar `typst_templates/regimento.typ`.
2. Registrar plano de implementação e walkthrough.
