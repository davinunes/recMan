---
name: inicializar_estrutura_agents
description: Skill global de bootstrap para inicializar a estrutura .agents/ em QUALQUER projeto novo ou existente, instalando uma cópia das skills globais genéricas (gestao_raciocinios_planos, skill_creation_guidelines), AGENTS.md modelo e skills.json padronizado. Segue o mesmo padrão do projeto recMan. Compatível com Trae e Antigravity.
---

# Skill: Inicializar Estrutura `.agents/` em Projeto Novo/Existente

> **Objetivo**: Esta skill contém TODO o procedimento + templates prontos para o agente criar uma estrutura `.agents/` 100% funcional em QUALQUER projeto, instalando um "pacote global" de regras e skills base. Compatível com **Trae** e **Antigravity**.

---

## 1. Quando Utilizar Esta Skill

Invoque SEMPRE que:
- Você for trabalhar em um projeto que **ainda não possui pasta `.agents/`** na raiz.
- O usuário pedir para "configurar regras do agente", "organizar o projeto para AI" ou similar.
- Você identificar que o projeto está sem rastreabilidade de raciocínios/walkthroughs.
- Um projeto existente tem skills espalhadas em múltiplos locais e precisa ser consolidado (como fizemos no recMan).

---

## 2. Estrutura Final que Será Criada

A seguinte estrutura é criada **na raiz do projeto alvo** (chamaremos de `<RAIZ_DO_PROJETO>/`):

```
<RAIZ_DO_PROJETO>/
└── .agents/
    ├── AGENTS.md                  # Regras globais do agente (modelo a customizar)
    ├── skills.json                # Apontando para ./skills
    ├── raciocinios/              # (vazia, primeiro raciocínio = este bootstrap)
    ├── planos_e_workflows/
    │   ├── planos/               # (vazia)
    │   ├── walkthroughs/          # (vazia, primeiro walkthrough = este bootstrap)
    │   └── workflows/             # (vazia)
    └── skills/                    # Pacote Global de Skills copiado para o projeto
        ├── gestao_raciocinios_planos/
        │   └── SKILL.md
        └── skill_creation_guidelines/
            └── SKILL.md
```

---

## 3. Procedimento Passo a Passo para o Agente

> **IMPORTANTE**: Execute os passos NA ORDEM. Não pule etapas.

### Passo 1: Confirmar a Raiz do Projeto

1. Liste o diretório atual com `LS` ou `pwd`.
2. Confirme que você está **na raiz do projeto** (onde ficam `.gitignore`, `package.json`, `composer.json`, `README.md`, etc.).
3. Se houver qualquer dúvida sobre qual é a raiz, **pergunte ao usuário** antes de prosseguir. **Nunca crie `.agents/` na pasta errada.**

---

### Passo 2: Verificar Existência Prévia

Verifique se a pasta `.agents/` **já existe**:

```bash
ls -la <RAIZ_DO_PROJETO>/.agents
```

- **Se não existir**: Prosseguir normalmente.
- **Se existir**: NÃO sobrescreva sem perguntar. Liste o conteúdo atual da pasta para o usuário e pergunte:
  - (a) "Deseja **sobrescrever tudo** e resetar para o padrão global?"
  - (b) "Deseja **mesclar** (adicionar as skills globais faltantes sem tocar no que já existe)?"
  - (c) "Deseja **cancelar**?"

---

### Passo 3: Criar a Árvore de Diretórios

Execute os comandos de criação de pasta. Exemplo para bash/PowerShell:

```bash
cd <RAIZ_DO_PROJETO>
mkdir -p .agents/raciocinios
mkdir -p .agents/planos_e_workflows/planos
mkdir -p .agents/planos_e_workflows/walkthroughs
mkdir -p .agents/planos_e_workflows/workflows
mkdir -p .agents/skills/gestao_raciocinios_planos
mkdir -p .agents/skills/skill_creation_guidelines
```

---

### Passo 4: Gravar o Arquivo `skills.json`

Crie **exatamente** este conteúdo em `<RAIZ_DO_PROJETO>/.agents/skills.json`:

```json
{
  "entries": [
    { "path": "./skills" }
  ]
}
```

> **Por que somente `./skills`?** Porque todas as skills ficam **DENTRO** de `.agents/skills/`. Não apontamos para pastas externas (evita o problema que tivemos no recMan com skills espalhadas).

---

### Passo 5: Gravar o `AGENTS.md` Modelo

Crie o arquivo `<RAIZ_DO_PROJETO>/.agents/AGENTS.md` usando o **TEMPLATE BASE** abaixo. **Antes de gravar**, substitua os placeholders `{{...}}` pelos valores reais do projeto:

```markdown
# Regras Globais do Projeto (Agents Rules)

> **Compatibilidade**: Este arquivo e toda a estrutura `.agents/` são lidos tanto pelo **Trae** quanto pelo **Antigravity**. Todas as regras abaixo se aplicam a QUALQUER agente trabalhando em `{{NOME_DO_PROJETO}}`.

---

## 1. Princípios Fundamentais (NÃO NEGOCIÁVEIS)

### 1.1 Ambiente de Desenvolvimento
- **{{DESCREVA_AQUI: Desenvolvemos em servidores remotos? Ou local?}}**
- **Padrão**: A MENOS QUE SEJA ESPECIFICADO EXPLICITAMENTE NO CHAT, **não tente rodar** compiladores, interpretadores, servidores de desenvolvimento ou ferramentas de build como: `maven`, `npm`, `php`, `composer`, `python`, `node`, `webpack`, `gulp`, `go build`, `cargo` etc.
- **Exceção permitida**: Comandos estritamente de leitura (`cat`, `ls`, `find`, `grep`, `file`) e organização (`mv`, `cp`, `rm`, `mkdir`).

### 1.2 Idioma Padrão
- **Priorize escrever em Português (Brasil)** para planos, feedbacks, walkthroughs, raciocínios e documentação técnica (SKILL.md e este AGENTS.md).
- Exceção: código-fonte segue padrão já existente no projeto.

### 1.3 Faça, Não Adivinhe
- **NUNCA adivinhe** estrutura de banco, assinatura de API, schema ou relacionamentos. SEMPRE leia o código-fonte real ou use a skill de inspeção do projeto (se houver) antes de escrever código.
- **NUNCA invente** nomes de tabelas, colunas, endpoints ou variáveis. Leia o arquivo correspondente.

---

## 2. Stack Tecnológica do Projeto `{{NOME_DO_PROJETO}}`

> ⚠️ **PREENCHA AQUI A STACK REAL DO PROJETO APÓS O BOOTSTRAP**.
> Exemplo de preenchimento (remova este aviso após editar):

| Camada         | Tecnologia                                                |
|----------------|-----------------------------------------------------------|
| Backend        | {{EX: Node.js 20 + Express}}                              |
| Banco          | {{EX: PostgreSQL 16 via Prisma ORM}}                      |
| Frontend       | {{EX: React 18 + TypeScript + Vite + Tailwind}}           |
| Autenticação   | {{EX: JWT via Auth0}}                                     |
| CI/CD          | {{EX: GitHub Actions}}                                    |
| Deploy         | {{EX: Vercel / AWS ECS}}                                  |
| Padrão Código  | {{EX: ESLint + Prettier}}                                 |

---

## 3. Registros de Raciocínio, Planos e Workflows

### 3.1 Raciocínios e Thinking Logs
- Para cada sessão/tarefa complexa, salve em `.agents/raciocinios/YYYY-MM-DD_[topico].md`.
- Idioma do arquivo pode ser aquele em que o pensamento foi formulado.

### 3.2 Planos de Implementação e Walkthroughs
- Planos → `.agents/planos_e_workflows/planos/YYYY-MM-DD_[topico].md`.
- Walkthroughs (taklins/resumos de entrega) → `.agents/planos_e_workflows/walkthroughs/YYYY-MM-DD_[topico].md`.
- Workflows e diagramas Mermaid → `.agents/planos_e_workflows/workflows/YYYY-MM-DD_[topico].md`.

---

## 4. Regras de Código e Convenções

> ⚠️ **ESTA SEÇÃO DEVE SER PERSONALIZADA PARA CADA PROJETO APÓS O BOOTSTRAP**.
> Adicione aqui: padrões de nomenclatura, estrutura de pastas, classes padrão, estilo de código, regras de teste, etc.

### 4.1 Regras Gerais
- Siga o estilo de código já existente no projeto. Não refatore nomes sem necessidade.
- **NUNCA commitar tokens, senhas, PII (dados pessoais), JWT, chaves de API**. Use arquivos `.env` e variáveis de ambiente.
- **SEMPRE** use prepared statements ou query builder (nunca concatene strings em SQL).

### 4.2 Estrutura de Pastas Padrão
```
{{EXEMPLIFICAR:
src/
├── components/    # Componentes React
├── pages/         # Rotas/páginas
├── lib/           # Helpers e clientes DB/API
└── types/         # Tipos TypeScript
}}
```

---

## 5. Uso Obrigatório de Skills

As skills abaixo estão em `.agents/skills/` e devem ser invocadas PROATIVAMENTE:

| Situação | Skill | Arquivo |
|----------|-------|---------|
| Iniciando tarefa complexa (3+ passos), planejando ou finalizando entrega | `gestao_raciocinios_planos` | `.agents/skills/gestao_raciocinios_planos/SKILL.md` |
| Criando ou editando uma skill nova | `skill_creation_guidelines` | `.agents/skills/skill_creation_guidelines/SKILL.md` |

> 💡 **Dica**: Conforme o projeto evolui, adicione novas linhas nesta tabela para skills específicas do projeto (ex: skill de estrutura de banco, skill de integração com API específica, etc.).

---

## 6. Segurança de Dados e PII

Conforme a skill `skill_creation_guidelines`:
1. Nomes de pessoas reais → `Fulano de Tal`, `Ciclano da Silva`.
2. Documentos (CPF/RG) → `000.000.000-00`.
3. Tokens, senhas, API keys → `{{placeholder}}` ou `ANONYMOIZED_TOKEN`.
4. Endereços/nomes de clientes reais → genéricos (ex: `Empresa Exemplo`, `Cliente A`).
5. Qualquer arquivo de payload/captura bruta real → extensão `.ignore` ou pasta listada no `.gitignore`.

---

## 7. Pós-Bootstrap: Checklists de Customização Obrigatória

Após rodar este bootstrap, **CONCLUA estas customizações antes de usar**:

- [ ] Seção 1.1: Confirmar se o projeto usa ambiente remoto ou local.
- [ ] Seção 2: Preencher a tabela de Stack Tecnológica do projeto.
- [ ] Seção 4: Personalizar Regras de Código e Estrutura de Pastas com as convenções reais do projeto.
- [ ] `.gitignore` da raiz: Verificar se `.agents/` (ou subpastas) deve ser versionada. Recomendado: versionar skills e AGENTS.md. Decidir sobre a pasta `raciocinios/` (muitas vezes são históricos pessoais).
```

---

### Passo 6: Instalar as 2 Skills Globais Genéricas

#### 6a. Skill `gestao_raciocinios_planos` (Versão Global Genérica)

Crie `<RAIZ_DO_PROJETO>/.agents/skills/gestao_raciocinios_planos/SKILL.md`:

```markdown
---
name: gestao_raciocinios_planos
description: Orienta e padroniza o salvamento de etapas de raciocínio (thinking), planos de implementação, walkthroughs/taklins e workflows dentro da estrutura local .agents/ do projeto. Compatível com Trae e Antigravity.
---

# Gestão de Raciocínios, Planos e Workflows Localmente

Esta skill define as diretrizes para que todo raciocínio analítico, planejamento de implementação e resumo de entrega (walkthrough/taklins) seja documentado e versionado localmente na estrutura `.agents/` do repositório.

## Estrutura de Pastas no Projeto

Todo projeto em que trabalharmos deve conter a seguinte estrutura dentro de `.agents/`:

```
.agents/
├── AGENTS.md                  # Diretrizes e regras locais do agente
├── raciocinios/              # Histórico das etapas de pensamento (thinking logs) para estudo futuro
│   └── YYYY-MM-DD_[topico_ou_task].md
├── planos_e_workflows/        # Documentação técnica do projeto
│   ├── planos/               # Planos de implementação detalhados
│   ├── walkthroughs/          # Resumos de entregas, modificações e taklins
│   └── workflows/             # Fluxogramas Mermaid e processos operacionais
└── skills/                   # Skills do projeto
    └── gestao_raciocinios_planos/
        └── SKILL.md
```

## Regras de Execução para o Agente

### 1. Etapas de Raciocínio (`.agents/raciocinios/`)
- **Quando gerar**: Ao final de cada sessão/tarefa relevante ou durante diagnósticos complexos.
- **Nome do arquivo**: `.agents/raciocinios/YYYY-MM-DD_[nome_da_tarefa].md`
- **Conteúdo**: Fluxo de pensamento do agente, hipóteses investigadas, causa raiz e justificativa da solução.
- **Idioma**: Pode ser salvo no idioma em que o raciocínio foi processado.

### 2. Planos de Implementação (`.agents/planos_e_workflows/planos/`)
- **Quando gerar**: Sempre que um plano for elaborado em Planning Mode ou para tarefas de grande porte.
- **Nome do arquivo**: `.agents/planos_e_workflows/planos/YYYY-MM-DD_[funcionalidade].md`
- **Conteúdo**: Objetivos, dependências, arquivos modificados/criados, plano de teste e perguntas abertas.

### 3. Walkthroughs / Taklins (`.agents/planos_e_workflows/walkthroughs/`)
- **Quando gerar**: Após a conclusão e verificação de uma funcionalidade ou correção de bug.
- **Nome do arquivo**: `.agents/planos_e_workflows/walkthroughs/YYYY-MM-DD_[funcionalidade].md`
- **Conteúdo**: Alterações efetuadas, evidências de testes e instrução de uso/validação.

### 4. Workflows (`.agents/planos_e_workflows/workflows/`)
- **Quando gerar**: Ao desenhar integrações, arquiteturas de sistemas ou fluxos de dados/API.
- **Nome do arquivo**: `.agents/planos_e_workflows/workflows/YYYY-MM-DD_[nome_do_fluxo].md`
- **Conteúdo**: Diagramas Mermaid e etapas sequenciais de execução.

## Impacto de Armazenamento

Os arquivos gerados são texto puro em Markdown (`.md`). Tamanho típico **5 KB a 50 KB** cada. Dezenas de sessões ocupam apenas **poucos Megabytes (MB)**, viabilizando versionamento via Git.
```

---

#### 6b. Skill `skill_creation_guidelines` (Versão Global Genérica)

Crie `<RAIZ_DO_PROJETO>/.agents/skills/skill_creation_guidelines/SKILL.md`:

```markdown
---
name: skill_creation_guidelines
description: Orientações, boas práticas e protocolos de segurança para a criação, edição e manutenção de skills em QUALQUER projeto, garantindo privacidade, anonimização de PII (dados pessoais e tokens), convenções de gitignore e estrutura padrão SKILL.md. Compatível com Trae e Antigravity.
---

# Skill: Diretrizes para Criação e Manutenção de Skills (Versão Global)

Esta skill especifica os protocolos obrigatórios de segurança, governança de dados e boas práticas de estruturação que **devem ser seguidos por todos os agentes e desenvolvedores** ao criar ou atualizar skills em QUALQUER repositório.

---

## 1. Proteção de Dados Sensíveis e PII (LGPD & Git Security)

> [!IMPORTANT]
> **REGRA DE OURO:** Nenhuma skill, documentação ou exemplo versionado no Git pode conter PII (Informações de Identificação Pessoal), credenciais reais ou tokens de acesso.

### A. Anonimização Obrigatória em Documentos e Payloads
- **Nomes de Pessoas:** Substitua por nomes fictícios genéricos (ex: `Fulano de Tal`, `Ciclano da Silva`, `Beltrana de Tal`, `Nome do Usuário`, `Nome do Funcionário`).
- **Documentos Pessoais (CPF / RG):** Substitua por marcadores zerados (ex: `000.000.000-00`, `00000000000`).
- **Tokens & Credenciais:** Substitua tokens JWT/JWE, senhas e chaves de API por placeholders limpos (ex: `Bearer {{bearerToken}}`, `{{ANONYMOIZED_TOKEN}}`, `{{SEU_USUARIO}}`, `{{SUA_SENHA}}`).
- **Nomes de Empresas, Clientes e Endereços:** Use termos genéricos como `Empresa Exemplo`, `Cliente A`, `Projeto Teste` ou `Endereço Genérico, 123`.

---

## 2. Regra para Capturas de Dados Brutos e Arquivos de Teste

Caso seja necessário salvar payloads brutos de rede, respostas reais de cURL, traces da API ou despejos de banco de dados:

1. **Inclusão Obrigatória no `.gitignore`:**
   - O arquivo ou diretório de capturas brutas deve constar explicitamente no `.gitignore` da raiz do projeto.
   - Alternativa: utilizar a extensão ou sufixo `.ignore` (ex: `captura_payload_real.json.ignore`, `dump_local.sql.ignore`) para assegurar que não será rastreado pelo Git.
2. **Localização Recomendada para Testes Locais:**
   - Salve em diretórios ignorados como `storage/`, `tmp/`, `.cache/` ou na pasta de scratch da sessão do agente.

---

## 3. Estrutura Padrão de uma Skill (`SKILL.md`)

Toda nova skill deve ser criada em `.agents/skills/<nome_da_skill>/SKILL.md` contendo:

1. **Cabeçalho Frontmatter (YAML):**
   ```yaml
   ---
   name: nome_da_skill
   description: Descrição sucinta contendo as palavras-chave principais e a finalidade exata da skill.
   ---
   ```
2. **Corpo do Documento (Markdown):**
   - Idioma: **Português (Brasil)** (salvo convenção específica do projeto).
   - Manter o documento focado, objetivo e direto ao ponto (idealmente abaixo de 500 linhas).
   - Se a skill exigir schemas extensos ou tabelas auxiliares massivas, crie subpastas como `references/`, `examples/` ou `resources/` DENTRO da pasta da skill.
3. **Compatibilidade Multi-Agente:**
   - Sempre que uma skill mencionar ferramentas específicas de um agente (ex: `WebFetch` do Trae), ofereça alternativas para outros ambientes (ex: `read_url` do Antigravity, `RunCommand + cURL` genérico).
   - Use tabelas de mapeamento: `Ambiente | Ferramenta | Como usar`.

---

## 4. Checklist para Publicação de Nova Skill

- [ ] Contém o frontmatter YAML com `name` e `description` validados?
- [ ] Todos os exemplos de JSON, cURL e payloads usam dados 100% fictícios?
- [ ] Nenhum token real, hash de senha ou e-mail de usuário consta no arquivo?
- [ ] Se houver arquivos de dados brutos para teste offline, estão protegidos no `.gitignore` ou com extensão `.ignore`?
- [ ] A skill está localizada em `.agents/skills/<nome>/SKILL.md` (e não em outra pasta)?
- [ ] Menciona alternativas de ferramentas para compatibilidade Trae + Antigravity?

---

## 5. Convenção de Nomes para Skills

- Use **`snake_case`** no nome da pasta e no frontmatter `name:` (ex: `integracao_stripe`, `analise_logs_nginx`).
- Evite acentos e caracteres especiais no `name:` do frontmatter. Use-os apenas na descrição e no conteúdo.
- O nome deve refletir o que a skill FAZ, não o assunto. Ex:
  - ✅ Bom: `consultar_estrutura_banco` (ação + objeto)
  - ❌ Ruim: `banco_de_dados` (assunto genérico)
```

---

### Passo 7: Verificar `.gitignore` do Projeto

1. **Leia** o arquivo `.gitignore` na raiz do projeto (se não existir, pergunte ao usuário se deseja criar).
2. **Discuta com o usuário** a seguinte estratégia de versionamento (exemplos):

   **Estratégia A (Recomendada — tudo versionado):**
   ```
   # Nenhuma regra para .agents no gitignore.
   # Tudo é versionado: skills, AGENTS.md, raciocinios, walkthroughs, etc.
   ```

   **Estratégia B (Skills versionadas / Raciocínios privados):**
   ```
   # Versiona skills e regras, mas nao versiona raciocinios individuais
   .agents/raciocinios/
   ```

   **Estratégia C (Nada versionado):**
   ```
   .agents/
   ```

3. **Aplique** a estratégia escolhida.

---

### Passo 8: Criar Raciocínio e Walkthrough Desta Inicialização

Após concluir todos os passos anteriores, registre a própria inicialização:

1. Crie **`.agents/raciocinios/YYYY-MM-DD_bootstrap_estrutura_agents.md`** com o fluxo de pensamento de qual estratégia foi usada, personalizações feitas, etc.
2. Crie **`.agents/planos_e_workflows/walkthroughs/YYYY-MM-DD_bootstrap_estrutura_agents.md`** com resumo do que foi criado, checklists de validação e passos de customização pendentes.

---

## 4. Checklists de Conclusão do Bootstrap

Marque TODOS os itens antes de declarar o projeto pronto:

- [ ] Pasta `.agents/` foi criada na RAIZ do projeto (não em subpasta)?
- [ ] Estrutura de subpastas (`raciocinios/`, `planos_e_workflows/planos/`, `walkthroughs/`, `workflows/`, `skills/`) foi criada?
- [ ] `skills.json` aponta apenas para `./skills`?
- [ ] `AGENTS.md` foi criado com os placeholders `{{NOME_DO_PROJETO}}` etc. substituídos?
- [ ] Versão global genérica de `gestao_raciocinios_planos` instalada?
- [ ] Versão global genérica de `skill_creation_guidelines` instalada?
- [ ] `.gitignore` foi verificado e estratégia de versionamento definida com o usuário?
- [ ] Foram criados raciocínio e walkthrough desta inicialização?
- [ ] Seções 1.1, 2, 4 do AGENTS.md foram personalizadas para o projeto real (ou checklist pendente foi comunicado ao usuário)?
