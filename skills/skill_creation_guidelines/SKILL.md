---
name: skill_creation_guidelines
description: Orientações, boas práticas e protocolos de segurança para a criação, edição e manutenção de skills no projeto recMan, garantindo privacidade, anonimização de PII (dados pessoais e tokens) e convenções de gitignore.
---

# Skill: Diretrizes para Criação e Manutenção de Skills

Esta skill especifica os protocolos obrigatórios de segurança, governança de dados e boas práticas de estruturação que **devem ser seguidos por todos os agentes e desenvolvedores** ao criar ou atualizar skills no repositório.

---

## 1. Proteção de Dados Sensíveis e PII (LGPD & Git Security)

> [!IMPORTANT]
> **REGRA DE OURO:** Nenhuma skill, documentação ou exemplo versionado no Git pode conter PII (Informações de Identificação Pessoal), credenciais reais ou tokens de acesso.

### A. Anonimização Obrigatória em Documentos e Payloads
- **Nomes de Pessoas:** Substituir qualquer nome real por nomes fictícios genéricos (ex: `Fulano de Tal`, `Ciclano da Silva`, `Beltrana de Tal`, `Nome do Morador`, `Nome do Funcionário`).
- **Documentos Pessoais (CPF / RG):** Substituir por marcadores zerados (ex: `000.000.000-00`, `00000000000`).
- **Tokens & Credenciais:** Substituir tokens JWT/JWE, senhas e chaves de API por placeholders limpos (ex: `Bearer {{bearerToken}}`, `eyJhbGci...ANONYMOIZED_TOKEN...`, `SEU_USUARIO`, `SUA_SENHA`).
- **Nomes de Condomínios e Endereços:** Usar termos genéricos como `Residencial Exemplo`, `Condomínio Teste` ou `Bloco A - Apt 101`.

---

## 2. Regra para Capturas de Dados Brutos e Arquivos de Teste

Caso seja necessário salvar payloads brutos de rede, respostas reais de cURL, traces da API ou despejos de banco de dados para auxiliar no desenvolvimento:

1. **Inclusão Obrigatória no `.gitignore`:**
   - O arquivo ou diretório de capturas brutoras deve constar explicitamente no `.gitignore` da raiz do projeto.
   - Alternativamente, utilizar a extensão ou sufixo `.ignore` (ex: `captura_payload_real.json.ignore`, `dump_local.sql.ignore`) para assegurar que não seja rastreado pelo Git.
2. **Localização Recomendada para Testes Locais:**
   - Salvar em diretórios ignorados como `storage/`, `tmp/` ou na pasta de scratch da sessão do agente.

---

## 3. Estrutura Padrão de uma Skill (`SKILL.md`)

Toda nova skill deve ser criada em `skills/<nome_da_skill>/SKILL.md` contendo:

1. **Cabeçalho Frontmatter (YAML):**
   ```yaml
   ---
   name: nome_da_skill
   description: Descrição sucinta contendo as palavras-chave principais e a finalidade exata da skill.
   ---
   ```
2. **Corpo do Documento (Markdown):**
   - Idioma: **Português (Brasil)**.
   - Manter o documento focado, objetivo e direto ao ponto (idealmente abaixo de 500 linhas).
   - Se a skill exigir schemas extensos ou tabelas auxiliares massivas, criar subpastas como `references/`, `examples/` ou `resources/`.

---

## 4. Checklist para Publicação de Nova Skill

- [ ] Contém o frontmatter YAML com `name` e `description` validados?
- [ ] Todos os exemplos de JSON, cURL e payloads usam dados 100% fictícios?
- [ ] Nenhum token real, hash de senha ou e-mail de usuário consta no arquivo?
- [ ] Se houver arquivos de dados brutos para teste offline, estão protegidos no `.gitignore` ou com extensão `.ignore`?
