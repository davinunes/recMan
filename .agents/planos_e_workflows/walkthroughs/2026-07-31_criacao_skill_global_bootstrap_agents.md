# Walkthrough: Criação da Skill Global de Bootstrap `inicializar_estrutura_agents`

**Data**: 2026-07-31
**Autor**: Agente Trae
**Status**: ✅ Entregue

---

## Resumo

Criação de uma **skill global de bootstrap** chamada `inicializar_estrutura_agents` que permite ao Trae (e Antigravity) inicializar a estrutura `.agents/` em QUALQUER projeto novo ou existente, instalando regras globais, pacote de skills genéricas, modelos de AGENTS.md e configuração padrão `skills.json`.

A skill foi instalada no projeto `recMan` (como referência de padrão), em [`.agents/skills/inicializar_estrutura_agents/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/inicializar_estrutura_agents/SKILL.md).

---

## Artefatos Criados

### ✅ 1. Skill Principal (Bootstrap)

**Arquivo**: [`.agents/skills/inicializar_estrutura_agents/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/inicializar_estrutura_agents/SKILL.md) (~480 linhas)

**Estrutura da skill**:

| Seção | Conteúdo |
|-------|---------|
| Frontmatter YAML | `name: inicializar_estrutura_agents` + descrição multi-agente (Trae + Antigravity) |
| 1. Quando Utilizar | 4 gatilhos: projeto sem .agents/, configuração de regras AI, falta de rastreabilidade, projeto com skills espalhadas |
| 2. Estrutura Final Criada | Árvore .agents/ completa com 8 subdiretórios e arquivos |
| 3. Procedimento Passo a Passo (8 passos) | Ver abaixo |
| 4. Checklist de Conclusão | 10 itens obrigatórios |

**8 Passos da Skill Bootstrap**:

| Passo | Título | Ação Detalhada |
|-------|--------|----------------|
| 1 | Confirmar raiz do projeto | `LS` + validar presença de arquivos raiz (README, .gitignore, package.json). Erro em subpasta. |
| 2 | Verificar existência prévia | Checar `.agents/` pré-existente. **Não sobrescrever sem perguntar**. Oferece (a) reset, (b) merge, (c) cancelar. |
| 3 | Criar árvore de diretórios | `mkdir -p` para todas as subpastas (raciocinios, planos, walkthroughs, workflows, 2 subpastas de skills). |
| 4 | Gravar `skills.json` | Padrão `./skills` (apenas). Consolidação total. |
| 5 | Gravar `AGENTS.md` modelo | Template completo com placeholders + seção "7. Pós-Bootstrap: Customização Obrigatória" com caixas de seleção. |
| 6 | Instalar pacote global de skills (2) | Templates completos e genéricos embutidos no próprio passo 6 para copiar/colar. |
| 7 | Verificar `.gitignore` | 3 estratégias apresentadas (A: tudo versionado, B: skills + AGENTS.md versionado, C: nada). Escolha com o usuário. |
| 8 | Meta-registro do bootstrap | Criar raciocínio e walkthrough da própria inicialização. |

---

### ✅ 2. Pacote Global de Skills Genéricas (2 skills instaláveis)

Dentro do **Passo 6 da skill bootstrap** estão os templates **completos e genéricos** (sem referências ao recMan) de:

#### Skill Global 1: `gestao_raciocinios_planos` (Genérica)
- 4 tipos de registro: Raciocínios, Planos, Walkthroughs, Workflows
- Convenções de nomenclatura de arquivo
- Modelo de estrutura de pastas .agents/ completa
- Compatibilidade declarada Trae + Antigravity
- Explicação sobre impacto de armazenamento (baixo)

#### Skill Global 2: `skill_creation_guidelines` (Genérica)
- Regra de Ouro LGPD: Não commit de PII
- Anonimização obrigatória: Nomes → Fulano, CPF → 000, Tokens → placeholder, clientes → genéricos
- Capturas brutas: .ignore ou storage/
- Estrutura padrão SKILL.md: Frontmatter YAML obrigatório + corpo Markdown em pt-BR
- **Seção nova exclusiva da versão global**: 3.3 Compatibilidade Multi-Agente (oferecer alternativas de ferramentas por ambiente: Trae vs Antigravity vs Genérico)
- Checklist de publicação: 6 itens obrigatórios
- Convenção de nomes: `snake_case`, nome = ação + objeto

---

### ✅ 3. Modelo de AGENTS.md (com Placeholders)

Dentro do **Passo 5** da skill bootstrap, modelo completo com 7 seções prontas para customizar:

| Seção Modelo | Placeholders Destacados |
|-------------|-------------------------|
| 1. Princípios Fundamentais | `{{DESCREVA_AQUI: Ambiente remoto ou local}}` + regra universal de não rodar compiladores + pt-br |
| 2. Stack Tecnológica | Tabela vazia + exemplo genérico comentado para preencher |
| 3. Registros | Igual recMan (sem alterações) |
| 4. Regras de Código | Aviso ⚠️ + exemplo de estrutura de pastas com `{{EXEMPLIFICAR:}}` |
| 5. Uso Obrigatório de Skills | 2 linhas padrão (gestao + creation_guidelines) + dica para adicionar skills específicas depois |
| 6. Segurança e PII | Igual recMan (5 itens) |
| 7. Pós-Bootstrap | **Seção exclusiva**: 4 caixas de seleção para customização obrigatória pendente |

---

## Modificações em Arquivos Existentes

### ✅ AGENTS.md do recMan (Tabela de Skills Obrigatórias)

**Linha 112 adicionada** em [`.agents/AGENTS.md`](file:///e:/DEV/recMan/.agents/AGENTS.md#L112):

```
| Iniciando em projeto novo ou sem .agents/ (instalar regras + skills globais) | inicializar_estrutura_agents | [.agents/skills/inicializar_estrutura_agents/SKILL.md] |
```

---

## Registros da Sessão Criados

| Tipo | Arquivo |
|------|---------|
| Raciocínio | [`.agents/raciocinios/2026-07-31_criacao_skill_global_bootstrap_agents.md`](file:///e:/DEV/recMan/.agents/raciocinios/2026-07-31_criacao_skill_global_bootstrap_agents.md) |
| Walkthrough (este arquivo) | [`.agents/planos_e_workflows/walkthroughs/2026-07-31_criacao_skill_global_bootstrap_agents.md`](file:///e:/DEV/recMan/.agents/planos_e_workflows/walkthroughs/2026-07-31_criacao_skill_global_bootstrap_agents.md) |

---

## Validação Final (10/10 Checklists Concluídos)

- [x] Frontmatter YAML válido (name + description) na nova skill
- [x] Idioma Português Brasil para todo o conteúdo
- [x] **Nenhuma** referência a PII, URLs do recMan, ou dados sensíveis nas versões genéricas
- [x] Referências explícitas de compatibilidade Trae + Antigravity
- [x] Procedimento passo a passo (8 passos) sem pular etapas
- [x] Templates completos de cada arquivo (não apenas esqueletos)
- [x] Tratamento de conflito se .agents/ já existe (sem sobrescrever)
- [x] .gitignore estratégias apresentadas ao usuário (3 opções)
- [x] Meta-registro do próprio bootstrap incluído (Passo 8)
- [x] Skill adicionada à tabela de uso obrigatório do AGENTS.md do recMan

---

## Como Usar em Qualquer Projeto Novo

1. Abrir projeto novo/inexistente no Trae (ou Antigravity)
2. Invocar a skill `inicializar_estrutura_agents` (se estiver disponível) ou copiar o conteúdo de [.agents/skills/inicializar_estrutura_agents/SKILL.md](file:///e:/DEV/recMan/.agents/skills/inicializar_estrutura_agents/SKILL.md) como referência
3. Seguir os 8 passos do procedimento (o agente executa).
4. **Importante**: Concluir a customização da Seção 7 do AGENTS.md (stack tecnológica, regras de código, ambiente de desenvolvimento).
5. **Opcional**: Criar skills adicionais específicas do projeto em `.agents/skills/<nome>/SKILL.md` (usando `skill_creation_guidelines` global já instalada).
