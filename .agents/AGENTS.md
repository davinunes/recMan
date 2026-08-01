# Regras Globais do Projeto (Agents Rules)

> **Compatibilidade**: Este arquivo e toda a estrutura `.agents/` são lidos tanto pelo **Trae** quanto pelo **Antigravity**. Todas as regras abaixo se aplicam a QUALQUER agente que esteja trabalhando no repositório `recMan`.

---

## 1. Princípios Fundamentais (NÃO NEGOCIÁVEIS)

### 1.1 Ambiente de Desenvolvimento Remoto
- **Desenvolvemos em servidores remotos**. Tratamos **somente o código-fonte** no repositório local.
- **A MENOS QUE SEJA ESPECIFICADO EXPLICITAMENTE NO CHAT**, **não tente rodar** compiladores, interpretadores, servidores de desenvolvimento ou ferramentas de build como: `maven`, `npm`, `php`, `composer`, `python`, `node`, `webpack`, `gulp`, `go build`, `cargo` etc.
- **Exceção**: Comandos estritamente de leitura como `cat`, `ls`, `find`, `grep`, `file` são permitidos. Comandos de movimentação de arquivos (`mv`, `cp`, `rm`, `mkdir`) também são permitidos para organizar a estrutura.

### 1.2 Idioma Padrão
- **Priorize escrever em Português (Brasil)** para:
  - Planos de implementação
  - Feedbacks e respostas no chat
  - Walkthroughs e taklins
  - Raciocínios de diagnóstico
  - Documentação técnica (SKILL.md, este AGENTS.md)
- **Exceção**: O código-fonte em si (variáveis, funções, classes) seguirá o padrão já estabelecido no projeto. Raciocínios podem ser em inglês caso o agente processou nesse idioma.

### 1.3 Faça, Não Adivinhe
- **NUNCA adivinhe** estrutura de banco de dados, assinatura de API, schema de tabela ou relacionamento entre entidades. SEMPRE consulte as skills de referência (veja seção 5) ou leia o código-fonte real antes de escrever código.
- **NUNCA invente** nomes de tabelas, colunas, endpoints ou variáveis. Leia o arquivo correspondente ou use a skill `consultar_estrutura_banco`.

---

## 2. Stack Tecnológica do Projeto `recMan`

| Camada            | Tecnologia                                                                 |
|-------------------|----------------------------------------------------------------------------|
| Backend           | **PHP 7.x+** (procedural, sem framework MVC)                              |
| Banco de Dados    | **MySQL / MariaDB** via extensão `mysqli`                                  |
| Frontend          | Templates PHP puros + **jQuery** + **DataTables** + **Material Icons** + CSS custom (`meu.css`, `meu.js`) |
| Autenticação      | Sessão PHP nativa (`session_start()` em `palco/usuarioLogado.php`)         |
| Integrações       | **Vida de Síndico (VDS) API v8**, **Supabase**, **Gmail API**, **Push Nativas** |
| Estilos           | Material Icons (ícone tipografia), CSS custom em `meu.css`                 |
| Templates/Views   | Pasta `palco/` — arquivos PHP que renderizam as telas principais           |
| Classes Core      | Pasta `classes/` — `database.php`, `repositorio.php`, `mail_helper.php`, `vds_*.php` |
| Migrations        | Pasta `migrates/` — scripts PHP (não usam framework de migração)           |

---

## 3. Registros de Raciocínio, Planos e Workflows

### 3.1 Raciocínios e Thinking Logs
- Para cada sessão de chat ou tarefa de diagnósticos/solução de problemas, salve as etapas de raciocínio em `.agents/raciocinios/YYYY-MM-DD_[topico].md`.
- O idioma do arquivo pode ser aquele em que o pensamento foi formulado (Português ou Inglês).

### 3.2 Planos de Implementação e Walkthroughs
- Salve planos de implementação em `.agents/planos_e_workflows/planos/YYYY-MM-DD_[topico].md`.
- Salve os walkthroughs (taklins / resumos de entrega) em `.agents/planos_e_workflows/walkthroughs/YYYY-MM-DD_[topico].md`.
- Salve workflows e diagramas operacionais em `.agents/planos_e_workflows/workflows/YYYY-MM-DD_[topico].md`.

---

## 4. Regras de Código e Convenções

### 4.1 Padrões do Projeto
- **Paradigma**: Procedural (não tente converter para POO ou introduzir frameworks). Existem classes na pasta `classes/` mas elas são wrappers utilitários, não um framework MVC.
- **Nomenclatura**: Siga o padrão existente — mistura de `snake_case` e `camelCase` dependendo da parte do sistema. **Não refatore nomes existentes**. Ao criar novo código, siga o padrão do arquivo onde está inserindo.
- **Estrutura de Pastas**:
  - Telas principais (`palco/dashboard.php`, `palco/detalheRecurso.php`, etc.) ficam em `palco/`.
  - Forms de edição/criação ficam em `forms/`.
  - Classes e helpers ficam em `classes/`.
  - Scripts de CLI e cron ficam na raiz (ex: `cron_vds_sync.php`).
  - APIs e endpoints AJAX ficam na raiz ou em `api/`.

### 4.2 Interação com Banco de Dados
- SEMPRE use a classe [`classes/database.php`](file:///e:/DEV/recMan/classes/database.php) para conexões. Não crie `new mysqli()` diretamente.
- Use a classe [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php) para queries de domínio frequentes. Evite duplicar SQL.
- **ANTES** de escrever qualquer query, invoque a skill `consultar_estrutura_banco` para confirmar nomes de tabelas, colunas e tipos.
- Para chaves estrangeiras, consulte [`list_fks.php`](file:///e:/DEV/recMan/list_fks.php).

### 4.3 Interação com a VDS (Integração API v8)
- SEMPRE use as classes em `classes/vds_*.php` ao invés de fazer requests cURL diretos:
  - [`classes/vds_auth_service.php`](file:///e:/DEV/recMan/classes/vds_auth_service.php) — tokens e autenticação
  - [`classes/vds_acesso_service.php`](file:///e:/DEV/recMan/classes/vds_acesso_service.php) — autorizações, QR codes, portaria
  - [`classes/vds_ocorrencia_service.php`](file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php) — ocorrências, comentários, anexos
- **ANTES** de integrar qualquer endpoint novo, consulte a skill `integração_api_vds_v8` em [`.agents/skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_api_v8/SKILL.md).

### 4.4 Segurança e Sanitização
- **NUNCA commitar tokens, senhas, PII (dados pessoais), JWT, chaves de API** em nenhum arquivo versionado.
- Use arquivos com extensão `.ignore` ou pastas listadas no `.gitignore` (ex: `storage/`) para testes locais com dados reais.
- Escapar TODAS as saídas HTML em contexto de view com `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
- A classe `database.php` usa prepared statements — **não quebre esse padrão** concatenando strings em SQL.
- Ao receber inputs via `$_POST`/`$_GET`, SEMPRE valide e sanitize:
  - Inteiros: `(int)$_GET['id']`
  - Strings: `trim($_POST['nome'])` + prepared statement
  - UUIDs: valide o formato ou use a tabela `vds_uuid_mapping`

### 4.5 Sessão e Autenticação
- Qualquer tela protegida deve **sempre** começar incluindo `palco/usuarioLogado.php` (ele faz `session_start()` e valida autenticação + permissões de conselheiro).
- **Não crie** gerenciamento de sessão paralelo.

---

## 5. Uso Obrigatório de Skills

As skills abaixo estão em `.agents/skills/` e devem ser **invocadas PROATIVAMENTE** pelo agente nas seguintes situações:

| Situação                                                                 | Skill a ser invocada                            | Localização da skill                                                              |
|--------------------------------------------------------------------------|-------------------------------------------------|----------------------------------------------------------------------------------|
| Iniciando tarefa complexa (3+ passos), planejando ou finalizando entrega | `gestao_raciocinios_planos`                     | [`.agents/skills/gestao_raciocinios_planos/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/gestao_raciocinios_planos/SKILL.md) |
| Escrevendo queries SQL, migrations, ou investigando estrutura de banco   | `consultar_estrutura_banco`                      | [`.agents/skills/consultar_estrutura_banco/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/consultar_estrutura_banco/SKILL.md) |
| Integrando qualquer endpoint da API VDS v8 (ocorrências, reservas, VDS)  | `integração_api_vds_v8` (pasta `vds_api_v8`)     | [`.agents/skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_api_v8/SKILL.md) |
| Trabalhando na tela de detalhe do recurso (painel lateral análise VDS)   | `recurso_accelerators`                           | [`.agents/skills/recurso_accelerators/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/recurso_accelerators/SKILL.md) |
| Implementando/modificando o componente de chat estilo WhatsApp           | `vds_chat_component`                             | [`.agents/skills/vds_chat_component/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_chat_component/SKILL.md) |
| Resolvendo UUIDs VDS ↔ IDs locais, cache de categorias                   | `vds_uuid_mapper`                                | [`.agents/skills/vds_uuid_mapper/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_uuid_mapper/SKILL.md) |
| Criando ou editando uma skill nova                                       | `skill_creation_guidelines`                      | [`.agents/skills/skill_creation_guidelines/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/skill_creation_guidelines/SKILL.md) |
| Iniciando em projeto novo ou sem `.agents/` (instalar regras + skills globais) | `inicializar_estrutura_agents`              | [`.agents/skills/inicializar_estrutura_agents/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/inicializar_estrutura_agents/SKILL.md) |

---

## 6. Segurança de Dados & LGPD (Complemento às Regras)

Conforme a skill `skill_creation_guidelines`:
1. Nomes de pessoas reais → substituir por `Fulano de Tal`, `Ciclano da Silva`, etc.
2. CPF/RG → substituir por `000.000.000-00`
3. Tokens JWT, senhas, API keys → substituir por `{{placeholder}}` ou `ANONYMOIZED_TOKEN`
4. Nomes de condomínios e endereços reais → `Residencial Exemplo`, `Bloco A - Apt 101`
5. Qualquer arquivo de payload/captura bruta real → extensão `.ignore` ou pasta `storage/` (ambas ignoradas no Git)
