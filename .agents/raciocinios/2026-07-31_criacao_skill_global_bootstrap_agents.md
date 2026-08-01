# Raciocínio: Criação da Skill Global de Bootstrap `inicializar_estrutura_agents`

**Data**: 2026-07-31
**Projeto**: recMan (referência para o padrão `.agents/`)

## Contexto e Pergunta do Usuário

Após ter reorganizado a pasta `.agents/` do projeto recMan (unificando todas as 7 skills, reescrevendo AGENTS.md com regras robustas), o usuário pediu:

> "gere uma skill global no trae para que todo projeto receba uma cópia das skills globais. seguindo o mesmo modelo, pasta .agents, etc..."

## Interpretação da Solicitação

Hipótese inicial: O usuário quer uma skill de "bootstrap" / scaffolding que o agente (no caso o Trae, mas também compatível com Antigravity) possa usar em QUALQUER outro projeto para:

1. Criar a estrutura `.agents/` completa
2. Instalar um "pacote global" com as skills que são ÚTEIS EM QUALQUER PROJETO (não as específicas do recMan como VDS API etc.)
3. Instalar um modelo de AGENTS.md com placeholders para customização
4. Instalar skills.json padrão apontando para `./skills`
5. Respeitar o padrão de estrutura aprendido no recMan

## Qual o conjunto de "Skills Globais Genéricas"?

**Decisão**: Nem toda skill do recMan é global. Separar:

| Skill recMan | É Global? | Justificativa |
|-------------|----------|---------------|
| `gestao_raciocinios_planos` | ✅ SIM | Útil em QUALQUER projeto para documentar planos, raciocínios e walkthroughs |
| `skill_creation_guidelines` | ✅ SIM | Útil em QUALQUER projeto para criar novas skills com segurança (LGPD, PII, estrutura) |
| `consultar_estrutura_banco` | ❌ NÃO | Específica do MySQL do recMan (URL específica mini.davinunes.eti.br, script estrutura.php específico). Outros projetos usam outros bancos. Cada projeto pode criar sua própria skill desse tipo. |
| `recurso_accelerators` | ❌ NÃO | Muito específico do recMan (tela detalheRecurso.php, conselho fiscal, VDS) |
| `integração_api_vds_v8` | ❌ NÃO | API específica da VDS, sem uso fora desse contexto |
| `vds_chat_component` | ❌ NÃO | Específico do componente chat VDS WhatsApp |
| `vds_uuid_mapper` | ❌ NÃO | Específico do mapeamento UUID ↔ ID VDS |

**Conclusão**: Pacote Global = 2 skills genéricas (`gestao_raciocinios_planos` + `skill_creation_guidelines`), mas com versões "genéricas" (retirar menções específicas do recMan como VDS e mini.davinunes).

## Estrutura da Skill Bootstrap

A skill de bootstrap em si precisa conter:

1. **Quando usar** (gatilhos: projeto novo, projeto sem .agents/, solicitação de configuração de AI)
2. **Passo a passo** bem explícito (8 passos), porque o agente pode estar num contexto totalmente diferente e não saber a ordem correta:
   - Passo 1: Confirmar raiz do projeto (NÃO CRIAR EM SUBPASTA)
   - Passo 2: Verificar existência prévia (não sobrescrever sem perguntar!)
   - Passo 3: Criar árvore de diretórios (mkdir)
   - Passo 4: Gravar skills.json (`./skills` apenas, padrão consolidado)
   - Passo 5: Gravar AGENTS.md modelo com placeholders `{{NOME_DO_PROJETO}}`, etc.
   - Passo 6: Instalar 2 skills globais genéricas com conteúdo completo embutido no template
   - Passo 7: Verificar .gitignore com 3 estratégias de versionamento
   - Passo 8: Criar raciocínio e walkthrough do PRÓPRIO bootstrap (meta!)
3. **Templates completos EMBUTIDOS** dentro da própria skill — ou seja, o agente não precisa sair do SKILL.md para procurar conteúdo. Tem o texto completo de cada arquivo dentro de blocos de código ` ```markdown ... ``` ` para o agente copiar/colar.
4. **Checklist de conclusão** para o agente validar que não esqueceu nada.

## Detalhes Importantes Decididos

### 1. Templates com placeholders explícitos
No AGENTS.md modelo, coloquei `{{NOME_DO_PROJETO}}`, `{{DESCREVA_AQUI:...}}` com avisos de preenchimento. Incluí também uma "Seção 7 — Pós-Bootstrap: Checklists de Customização Obrigatória" com caixas de seleção para lembrar o agente de personalizar.

### 2. Compatibilidade Multi-Agente (Trae + Antigravity)
Nas versões genéricas das skills globais:
- Adicionei menção à compatibilidade nos `description` do frontmatter.
- Na `skill_creation_guidelines` genérica adicionei a seção "3.3 Compatibilidade Multi-Agente" explicando como oferecer alternativas de ferramentas por ambiente.

### 3. Tratamento de conflito (Passo 2)
Isso era crucial: se o agente já tem `.agents/`, o agente **não pode sobrescrever**. Precisa perguntar (a) reset total, (b) merge incremental ou (c) cancelar. Sempre que houver risco de perda de dados, o agente deve pedir confirmação.

### 4. 3 estratégias de .gitignore
Diferentes projetos têm políticas diferentes de versionamento:
- A: tudo versionado (recomendado)
- B: versiona skills/regras, não raciocínios pessoais
- C: nada versionado

### 5. Meta-registro: o bootstrap documenta a si mesmo
Passo 8 cria raciocínio + walkthrough do próprio bootstrap. Isso ajuda a criar trilha desde o primeiro dia.

## Refinamentos no AGENTS.md do recMan

Adicionei uma linha na tabela "Uso Obrigatório de Skills" (seção 5) para linkar a nova skill:

```
| Iniciando em projeto novo ou sem .agents/ (instalar regras + skills globais) | inicializar_estrutura_agents | arquivo SKILL.md |
```

## Erros Evitados

1. **Erro de escopo**: Não incluí as 5 skills específicas do recMan (VDS etc.) no pacote global — isso seria inútil e prejudicial em projetos não-relacionados.
2. **Erro de contexto**: Não escrevi versões "copia e cola" específicas do recMan. Criei versões "genéricas" adaptáveis, com placeholders.
3. **Erro de segurança**: Não coloquei PII nem URLs do recMan nas versões globais genéricas.
4. **Erro de UX**: A skill bootstrap tem 8 passos NA ORDEM, com conteúdo completo embutido — o agente não precisa procurar nada fora do próprio SKILL.md.
