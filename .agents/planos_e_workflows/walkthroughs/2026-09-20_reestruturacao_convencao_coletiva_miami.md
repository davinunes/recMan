# Walkthrough - Reestruturação da Convenção Coletiva, Anexo I e Integração no Painel Typst

- **Data**: 2026-09-20
- **Arquivos Envolvidos**:
  - [`convencao_coletiva/convencao_coletiva_miami_json.json`](file:///e:/DEV/recMan/convencao_coletiva/convencao_coletiva_miami_json.json)
  - [`palco/test_typst.php`](file:///e:/DEV/recMan/palco/test_typst.php)
  - [`typst_templates/regimento.typ`](file:///e:/DEV/recMan/typst_templates/regimento.typ)

## 1. O que foi realizado

1. **Reestruturação Geral do JSON**:
   - Adequação do schema para o padrão da skill [`regimento_notacao_busca`](file:///e:/DEV/recMan/.agents/skills/regimento_notacao_busca/SKILL.md) e `database.json`.
   - Organização principal sob as chaves `"titulo"`, `"artigos"` (para a Convenção de Condomínio) e `"anexo1"` (para o Regimento Interno).

2. **Descompactação ("Explosão") de Blocos de Texto**:
   - **Convenção de Condomínio (Artigos 1 ao 38)**:
     - Chaves alteradas de `"Art. 1º"` para números limpos (`"1"`, `"2"`, ..., `"38"`).
     - Extração de `paragrafos` (`"1"`, `"2"`, `"unico"`).
     - Extração de `alineas` (linhas ou blocos inline `a)`, `b)`, `c)`...).
     - Tratamento especial para o Artigo 6 (sub-itens 6.1 a 6.5, alíneas e parágrafos de garagem).
     - Tratamento especial para os Parágrafos do Artigo 19 (desdobramento das alíneas inline `a` a `n` no §3º e `a` a `d` no §4º).

3. **Adequação do Regimento Interno (`anexo1`)**:
   - Remoção do prefixo `"rcc "` de todas as chaves.
   - Aninhamento sob `"anexo1"` contendo `titulo` ("ANEXO I - REGIMENTO INTERNO DO CONDOMÍNIO") e `artigos` (1 a 10).
   - Explosão hierárquica completa dos sub-itens em nós `incisos`, `paragrafos` e `alineas`.

4. **Adaptação do Painel Typst (`palco/test_typst.php`) & Template (`typst_templates/regimento.typ`)**:
   - **`palco/test_typst.php`**: O Card 1 foi transformado para suportar seleção de documento normativo:
     - `Regimento Interno (33 Capítulos - database.json)`
     - `Convenção de Condomínio & Anexo I (convencao_coletiva_miami_json.json)`
   - **`typst_templates/regimento.typ`**:
     - Adicionada verificação segura para documentos sem a propriedade `capitulo` em cada artigo.
     - Adicionada rotina de renderização automática para o bloco `anexo1` (Regimento Interno vinculado à Convenção) com formatação visual e quebra de página dedicada.

---

## 2. Validação

- Verificação de sintaxe e código PHP em `palco/test_typst.php`.
- Teste de estruturação e renderização dos templates Typst.
