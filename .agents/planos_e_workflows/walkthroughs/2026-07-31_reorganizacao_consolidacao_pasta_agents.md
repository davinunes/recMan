# Walkthrough: Reorganização e Consolidação da Pasta .agents do recMan

**Data**: 2026-07-31
**Autor**: Agente Trae
**Status**: ✅ Entregue

---

## Resumo da Entrega

Reorganização completa da pasta `.agents/` para plena compatibilidade com Trae e Antigravity, unificação centralizada de todas as skills num único local, e redação de regras globais robustas.

---

## Alterações Efetuadas

### 🔴 1. Unificação Centralizada das Skills (tudo em `.agents/skills/`)

**Antes**: Skills estavam espalhadas em 2 locais
- `.agents/skills/` → 2 skills (consultar_estrutura_banco, gestao_raciocinios_planos)
- `skills/` (raiz) → 5 skills em subpastas + 1 arquivo duplicado órfão

**Depois**: Todas as 7 skills centralizadas em `.agents/skills/`

Skills movidas da raiz → `.agents/skills/`:
1. `recurso_accelerators/`
2. `skill_creation_guidelines/`
3. `vds_api_v8/`
4. `vds_chat_component/`
5. `vds_uuid_mapper/`

Skills que já estavam no local (mantidas):
6. `consultar_estrutura_banco/`
7. `gestao_raciocinios_planos/`

Arquivos removidos:
- ❌ `skills/vds_api_v8_SKILL.md` → duplicata órfã (conteúdo idêntico a `vds_api_v8/SKILL.md`), formato inválido (fora de subpasta)
- ❌ Pasta `skills/` raiz vazia (removida após mover tudo)

---

### 🔴 2. Correção do Arquivo `skills.json`

**Arquivo**: [`.agents/skills.json`](file:///e:/DEV/recMan/.agents/skills.json)

**Antes** (apontava para pasta raiz externa — skills locais não carregavam):
```json
{ "entries": [ { "path": "../skills" } ] }
```

**Depois** (aponta apenas para `./skills` interna, tudo centralizado):
```json
{
  "entries": [
    { "path": "./skills" }
  ]
}
```

---

### 🔴 3. Reescrita Completa da Skill `consultar_estrutura_banco`

**Arquivo**: [`.agents/skills/consultar_estrutura_banco/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/consultar_estrutura_banco/SKILL.md)

**Melhorias aplicadas**:
- ✅ Removidas referências a ferramentas inexistentes (`read_url_content`, `view_file`)
- ✅ **4 níveis de fallback progressivos** (Opção 1 → 2 → 3 → 4)
- ✅ Tabela de compatibilidade multi-agente documentada:

| Ambiente       | Ferramenta para fetch URL         |
|----------------|------------------------------------|
| Trae           | `WebFetch` (nativo)                |
| Antigravity    | `read_url` ou `http_get` nativo    |
| Genérico       | `RunCommand` com cURL              |

- ✅ **Fallbacks seguros** para quando não há rede nem PHP:
  - Leitura estática dos scripts PHP (`tools/estrutura.php`, `list_fks.php`, `classes/database.php`, `classes/repositorio.php`)
  - Último recurso: dump SQL versionado em `storage/migrations_20260329.sql` e scripts `migrates/`
- ✅ Frontmatter YAML `description` atualizado para refletir multi-estratégia
- ✅ Item 5 nas Boas Práticas explicitamente menciona compatibilidade Trae + Antigravity

---

### 🔴 4. AGENTS.md Expandido (de 12 para 123 linhas)

**Arquivo**: [`.agents/AGENTS.md`](file:///e:/DEV/recMan/.agents/AGENTS.md)

**6 seções novas adicionadas**:

| # | Seção | Conteúdo-chave |
|---|-------|-----------------|
| 1 | **Princípios Fundamentais (NÃO NEGOCIÁVEIS)** | ✅ Não rodar compiladores/interpretadores (php, npm, composer, maven, python, node, webpack...) a menos que EXPLÍCITO no chat. Exceções: apenas comandos de leitura (cat, ls, grep, find) e organização (mv, cp, rm, mkdir). ✅ Português Brasil padrão para planos, feedbacks, walkthroughs, documentação. ✅ "Faça, não adivinhe" — nunca inventar schema, colunas ou endpoints. |
| 2 | **Stack Tecnológica** | Tabela com Backend PHP procedural, MySQL mysqli, Frontend jQuery+DataTables+Material Icons, Sessão PHP, Integrações VDS API v8 / Supabase / Gmail / Push, Pastas palco/, classes/, forms/, migrates/. |
| 3 | **Registros** (mantidos do original) | Raciocínios em `.agents/raciocinios/YYYY-MM-DD_topico.md`, Planos em `.agents/planos_e_workflows/planos/`, Walkthroughs em `walkthroughs/`, Workflows em `workflows/`. |
| 4 | **Regras de Código e Convenções** | Paradigma Procedural proibindo introduzir MVC/POO. Padrão de pastas. Banco: SEMPRE via `classes/database.php` e `classes/repositorio.php` + chamar skill `consultar_estrutura_banco` ANTES de escrever SQL. VDS: SEMPRE usar `classes/vds_*.php`. Segurança: PII nunca commitado, prepared statements, `htmlspecialchars()`, inputs sanitizados. Sessão: SEMPRE incluir `palco/usuarioLogado.php` em telas protegidas. |
| 5 | **Uso Obrigatório de Skills** | Tabela mapeando 7 situações → skill correspondente → link direto para o SKILL.md. Inclui gestao_raciocinios_planos, consultar_estrutura_banco, integração_api_vds_v8, recurso_accelerators, vds_chat_component, vds_uuid_mapper, skill_creation_guidelines. |
| 6 | **LGPD e Segurança** | Conforme skill_creation_guidelines: anonimização de nomes (Fulano de Tal), CPF (000.000.000-00), tokens, endereços reais. Payloads brutos devem ter extensão `.ignore` ou ficar em `storage/`. |

Aviso no cabeçalho: `> Compatibilidade: Este arquivo e toda a estrutura .agents/ são lidos tanto pelo Trae quanto pelo Antigravity.`

---

## Arquivos Afetados

| Tipo | Arquivo | Ação |
|------|---------|------|
| 📝 Editado | [`.agents/skills.json`](file:///e:/DEV/recMan/.agents/skills.json) | Caminho atualizado para `./skills` |
| 📝 Editado | [`.agents/skills/consultar_estrutura_banco/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/consultar_estrutura_banco/SKILL.md) | Reescrito para compatibilidade Trae + Antigravity com 4 fallbacks |
| 📝 Editado | [`.agents/AGENTS.md`](file:///e:/DEV/recMan/.agents/AGENTS.md) | Expandido de 12 → 123 linhas |
| 📦 Movidos (5) | `skills/recurso_accelerators/`, `skills/skill_creation_guidelines/`, `skills/vds_api_v8/`, `skills/vds_chat_component/`, `skills/vds_uuid_mapper/` | Para `.agents/skills/` |
| 🗑️ Deletado | `skills/vds_api_v8_SKILL.md` | Duplicata órfã em formato inválido |
| 🗑️ Deletado | Pasta `skills/` (raiz) | Removida após esvaziar |
| ✨ Criado | [`.agents/raciocinios/2026-07-31_reorganizacao_consolidacao_pasta_agents.md`](file:///e:/DEV/recMan/.agents/raciocinios/2026-07-31_reorganizacao_consolidacao_pasta_agents.md) | Raciocínio desta sessão |
| ✨ Criado | Este walkthrough | Resumo da entrega |

---

## Validação

| Item Verificado | Status |
|-----------------|--------|
| Todas as 7 skills estão em `.agents/skills/` | ✅ |
| `skills.json` aponta apenas para `./skills` (relativo) | ✅ |
| Pasta `skills/` raiz não existe mais | ✅ |
| `AGENTS.md` contém proibição explícita de executar PHP/npm/composer/etc | ✅ |
| `AGENTS.md` contém regra de idioma pt-br para planos e feedbacks | ✅ |
| Skill `consultar_estrutura_banco` não referencia ferramentas inexistentes | ✅ |
| Tabela de mapeamento Trae vs Antigravity documentada na skill | ✅ |
| 4 fallbacks de segurança na skill de banco (fetch remoto → PHP local → leitura estática → dump SQL) | ✅ |
| Compatibilidade Trae + Antigravity declarada explicitamente no cabeçalho do AGENTS.md e na skill | ✅ |
| Raciocínio e walkthrough gravados na estrutura `.agents/` | ✅ |

---

## Como Usar (Próximas Sessões)

1. **Para o Trae**: Reinicie ou abra um novo chat. O AGENTS.md será lido automaticamente como contexto global. As 7 skills serão descobertas via `skills.json` apontando para `./skills`.
2. **Para o Antigravity**: A pasta `.agents/` já é o padrão reconhecido. Mesmo AGENTS.md e mesmos SKILL.md serão carregados. As tabelas de mapeamento de ferramentas na skill `consultar_estrutura_banco` indicam alternativas nativas para cada ambiente.
