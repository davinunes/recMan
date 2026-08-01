---
name: consultar_estrutura_banco
description: Consulta e analisa a estrutura DDL atualizada do banco de dados MySQL do recMan, permitindo inspecionar colunas, tipos de dados, chaves primárias e índices existentes sem depender de palpites. Oferece múltiplas estratégias: fetch HTTP remoto (WebFetch/cURL), leitura do script local tools/estrutura.php ou consulta direta via list_fks.php.
---

# Skill: Consultar Estrutura Atualizada do Banco de Dados (`consultar_estrutura_banco`)

Esta skill orienta o assistente no processo de inspeção e verificação da estrutura DDL atualizada das tabelas do banco de dados do projeto `recMan`.

## Quando Utilizar
- Sempre que for necessário verificar colunas, tipos de dados, chaves primárias (`PRIMARY KEY`), chaves estrangeiras ou índices (`KEY`) de qualquer tabela do banco.
- Antes de elaborar novos planos de migração SQL (`ALTER TABLE`, `CREATE INDEX`).
- Ao investigar erros de query MySQL, divergências de colunas ou otimização de performance de consultas.

## Procedimento de Execução (escolha a opção mais adequada ao ambiente)

### Opção 1: Leitura via Fetch HTTP (Servidor Remoto Staging/Produção) — Recomendada
Use a ferramenta de fetch de URL disponível no seu agente para ler a estrutura gerada em tempo real:

- **URL**: `https://mini.davinunes.eti.br/estrutura.php`

**Ferramentas possíveis (por ambiente):**
| Ambiente       | Ferramenta a usar                  | Como utilizar                                                            |
|----------------|------------------------------------|--------------------------------------------------------------------------|
| Trae           | `WebFetch`                         | Chamar `WebFetch` passando a URL acima. O retorno já virá estruturado.   |
| Antigravity    | `read_url` ou `http_get` nativo    | Invocar a função de leitura de URL com a mesma URL.                      |
| Genérico       | `RunCommand` (cURL)                | `curl -s "https://mini.davinunes.eti.br/estrutura.php"`                  |

### Opção 2: Execução do Script Local (se houver PHP configurado no ambiente)
Se o ambiente suportar execução de PHP e o banco local estiver configurado, execute o script que gera o DDL em tempo real:

- **Caminho do script**: `e:\DEV\recMan\tools\estrutura.php`
- **Caminho alternativo (antigo)**: `e:\DEV\recMan\estrutura.php`

**Exemplo com RunCommand:**
```bash
cd /e/DEV/recMan && php tools/estrutura.php
```

### Opção 3: Leitura Estática do Código Fonte do Script (Fallbacks seguros)
Se **nenhuma** das opções acima estiver disponível (ambiente sem rede, sem PHP), ouça o próprio código do arquivo PHP para entender a estrutura:

1. **Primeiro**: Ler [`tools/estrutura.php`](file:///e:/DEV/recMan/tools/estrutura.php) para descobrir as tabelas e os campos que ele consulta.
2. **Depois**: Ler [`list_fks.php`](file:///e:/DEV/recMan/list_fks.php) para mapeamento de chaves estrangeiras existentes.
3. **Complemento**: Ler a classe [`classes/database.php`](file:///e:/DEV/recMan/classes/database.php) e [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php) para entender os padrões de query e relações.

### Opção 4: Fallback de Última Instância — Arquivos SQL de Migração
Use apenas se as opções 1, 2 e 3 forem insuficientes:
- Ler [`storage/migrations_20260329.sql`](file:///e:/DEV/recMan/storage/migrations_20260329.sql) (último dump schema versionado).
- Ler scripts na pasta [`migrates/`](file:///e:/DEV/recMan/migrates/) para entender evoluções recentes de schema.

## Boas Práticas ao Analisar a Estrutura
1. **Nunca Adivinhar Schemas**: Verifique sempre o DDL exato retornado antes de escrever queries complexas com `JOIN` ou filtros `WHERE`.
2. **Conferência de Índices Exibidos**:
   - Identifique colunas frequentemente filtradas que não possuem chave (`KEY`).
   - Verifique se os índices existentes cobrem queries compostas (ex: `bloco + unidade` ou `numero + ano`).
3. **Preservação de Dados**: Ao propor alterações DDL, prefira comandos idempotentes com `IF NOT EXISTS` ou verificações preventivas em scripts de migração.
4. **Prioridade de Execução**: Sempre tente as opções na ordem (1 → 2 → 3 → 4). A Opção 1 (fetch remoto) é a mais confiável por refletir a estrutura em produção/staging real.
5. **Compatibilidade Multi-Agente**: Esta skill foi projetada para funcionar tanto com Trae quanto com Antigravity (e qualquer agente leia a pasta `.agents/`). Se uma ferramenta de uma coluna não existir, use a alternativa da mesma linha.
