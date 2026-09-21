# Walkthrough - Reestruturação da Convenção Coletiva e Anexo I (Regimento)

- **Data**: 2026-09-20
- **Arquivo Alvo**: [`convencao_coletiva/convencao_coletiva_miami_json.json`](file:///e:/DEV/recMan/convencao_coletiva/convencao_coletiva_miami_json.json)

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
   - Explosão hierárquica completa dos sub-itens (ex: `1.2.a`, `3.2.15`, `5.2.17.a`, `5.3.1.a`) em nós `incisos`, `paragrafos` e `alineas`, garantindo 100% de compatibilidade com a mecânica de consulta de notações do backend PHP (`trecho.php`) e skill `regimento_notacao_busca`.

---

## 2. Validação

- Leitura e verificação do JSON final em `convencao_coletiva/convencao_coletiva_miami_json.json`.
- Confirmação de integridade textual e ausência de perda de caracteres ou itens normativos.
