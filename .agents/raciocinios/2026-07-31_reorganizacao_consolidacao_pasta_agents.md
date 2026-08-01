# Raciocínio: Reorganização e Consolidação da Pasta .agents do recMan

**Data**: 2026-07-31

## Contexto e Pergunta do Usuário

O usuário solicitou análise da pasta `.agents` para verificar se faltava algo para o Trae (e o Antigravity) usarem as skills e demais regras corretamente. Após a análise inicial, o usuário pediu para aplicar as correções, com requisições específicas:
1. Mover skills da raiz `skills/` para dentro de `.agents/skills/` (unificar tudo num local só)
2. Melhorar skills para o agente usar alternativas de ferramentas disponíveis (pois o Antigravity também lê a pasta)
3. Incorporar duas "regras globais" que vinham do Antigravity:
   - Desenvolvemos em servidores remotos, não rodar compiladores (php/npm/composer/etc) a menos que explícito
   - Priorizar pt-br para planos e feedbacks

## Passo 1: Análise da Estrutura Existente

### Hipótese 1: Skills não estavam sendo carregadas
- **Investigação**: `.agents/skills.json` apontava apenas para `../skills` (pasta raiz), então as 2 skills dentro de `.agents/skills/` (consultar_estrutura_banco e gestao_raciocinios_planos) NUNCA eram descobertas pelo carregador.
- **Validação**: Confirmei lendo o skills.json e comparando com skills disponíveis do sistema → confirmado, apenas skills da raiz seriam carregadas.

### Hipótese 2: Havia skill órfã e duplicata
- **Investigação**: A pasta raiz `skills/` tinha `vds_api_v8_SKILL.md` solta (fora de subpasta). Skills precisam estar em `<pasta>/<nome>/SKILL.md`, não `<pasta>/<qualquer>.md`. Além disso o conteúdo era idêntico a `vds_api_v8/SKILL.md` (mesmo frontmatter `name: integração_api_vds_v8`).
- **Conclusão**: Arquivo duplicado e quebrado — não seria carregado por nenhum agente.

### Hipótese 3: Ferramentas inexistentes no toolkit do Trae
- **Investigação**: A skill `consultar_estrutura_banco` pedia `read_url_content` e `view_file`. Verifiquei o toolkit disponível: Trae tem `WebFetch`, `Read`, `RunCommand`. Antigravity tem `read_url`, `http_get` etc.
- **Conclusão**: Skill não quebrava carregamento, mas causaria falha ao ser invocada.

## Passo 2: Estratégia de Correção

Baseado nas solicitações do usuário, decidi a seguinte estratégia:

1. **Unificar skills** → Mover TUDO para `.agents/skills/` (7 skills no total: 2 que já estavam lá + 5 da raiz). Remover a pasta raiz `skills/` inteira, incluindo o arquivo duplicado órfão.

2. **Corrigir skills.json** → Apontar apenas para `./skills` (relativo à pasta .agents), eliminando referência à pasta raiz antiga.

3. **Reescrever consultar_estrutura_banco** → Criar uma matriz de compatibilidade multi-agente (Trae e Antigravity) com:
   - 4 níveis de fallback (Opção 1 → 4)
   - Para cada opção, indicar qual ferramenta usar em cada ambiente
   - Tabela explícita: Ambiente → Ferramenta → Como usar
   - Incluir fallbacks seguros (leitura estática de scripts, dump SQL) para quando não há rede nem PHP.

4. **Expandir AGENTS.md** → Completamente reescrito, organizado em 6 seções:
   - Seção 1: Princípios Fundamentais NÃO NEGOCIÁVEIS (as regras do Antigravity de não rodar compiladores + pt-br padrão + "Faça, não adivinhe")
   - Seção 2: Stack Tecnológica do recMan (PHP procedural, MySQL, jQuery, etc.)
   - Seção 3: Registros (as regras antigas mantidas)
   - Seção 4: Regras de Código e Convenções (banco, VDS, segurança, sessão)
   - Seção 5: Uso Obrigatório de Skills (tabela mapeando situação → skill → link)
   - Seção 6: LGPD e Segurança (conforme skill skill_creation_guidelines)

## Passo 3: Verificações e Auto-correções

- **Erro evitado 1**: Inicialmente pensei em manter ambas as entradas no skills.json, mas o usuário pediu para mover tudo para dentro. Então usei apenas `./skills` e validei que os 5 diretórios foram movidos com `mv` antes de deletar a pasta vazia.
- **Erro evitado 2**: Usei `LS` duas vezes para confirmar: (a) que as pastas foram movidas para destino correto, e (b) que a pasta `skills/` raiz estava vazia antes de rodar `rmdir`.
- **Erro evitado 3**: Na skill `consultar_estrutura_banco`, não removi a URL remota (que era a única opção recomendada). Em vez disso, criei camadas de fallback progressivas, mantendo a Opção 1 como recomendada mas oferecendo alternativas seguras.

## Resultado Final

- 7 skills consolidadas em `.agents/skills/` (tudo num lugar só)
- skills.json enxuto apontando só para `./skills`
- Pasta `skills/` raiz removida completamente
- AGENTS.md transformado de 12 linhas para 123 linhas, cobrindo stack, regras, obrigatoriedade de uso de skills e LGPD
- consultar_estrutura_banco agora tem compatibilidade multi-agente documentada com 4 níveis de fallback
- Estrutura 100% compatível tanto com Trae quanto com Antigravity (usuário explicitou esse requisito)

## Lições Aprendidas

1. Skills precisam sempre estar no formato `<skill_folder>/SKILL.md`, nunca arquivos `.md` soltos.
2. O `skills.json` é resolvido RELATIVO ao diretório onde ele está (`.agents/`), não à raiz do projeto.
3. Para multi-agente, nunca especificar uma única ferramenta numa skill — sempre oferecer alternativas e tabela de mapeamento por ambiente.
4. Regras de "não executar compiladores" precisam estar EXPLÍCITAS e em NEGRITO no início do AGENTS.md, pois agentes tendem a assumir que podem rodar ambiente local.
