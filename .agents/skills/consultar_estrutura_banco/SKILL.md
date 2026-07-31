---
name: consultar_estrutura_banco
description: Consulta e analisa a estrutura DDL atualizada do banco de dados MySQL do recMan diretamente via cURL na URL https://mini.davinunes.eti.br/estrutura.php (ou script local equivalente), permitindo inspecionar colunas, tipos de dados, chaves primárias e índices existentes sem depender de palpites.
---

# Skill: Consultar Estrutura Atualizada do Banco de Dados (`consultar_estrutura_banco`)

Esta skill orienta o assistente no processo de inspeção e verificação da estrutura DDL atualizada das tabelas do banco de dados do projeto `recMan`.

## Quando Utilizar
- Sempre que for necessário verificar colunas, tipos de dados, chaves primárias (`PRIMARY KEY`), chaves estrangeiras ou índices (`KEY`) de qualquer tabela do banco.
- Antes de elaborar novos planos de migração SQL (`ALTER TABLE`, `CREATE INDEX`).
- Ao investigar erros de query MySQL, divergências de colunas ou otimização de performance de consultas.

## Procedimento de Execução

### Opção 1: Leitura via Requisição cURL / HTTP (Servidor Remoto Staging/Produção)
Utilize a ferramenta `read_url_content` para ler a estrutura gerada em tempo real:
- **URL**: `https://mini.davinunes.eti.br/estrutura.php`

```json
{
  "Url": "https://mini.davinunes.eti.br/estrutura.php",
  "toolAction": "Reading database structure DDL",
  "toolSummary": "Fetch database DDL via HTTP"
}
```

### Opção 2: Inspeção do Script Local
Caso a requisição remota não esteja acessível, utilize a ferramenta `view_file` no arquivo local [estrutura.php](file:///e:/DEV/recMan/estrutura.php) ou execute a inspeção localmente:
- **Caminho**: `e:\DEV\recMan\estrutura.php`

## Boas Práticas ao Analisar a Estrutura
1. **Nunca Adivinhar Schemas**: Verifique sempre o DDL exato retornado antes de escrever queries complexas com `JOIN` ou filtros `WHERE`.
2. **Conferência de Índices Exibidos**:
   - Identifique colunas frequentemente filtradas que não possuem chave (`KEY`).
   - Verifique se os índices existentes cobrem queries compostas (ex: `bloco + unidade` ou `numero + ano`).
3. **Preservação de Dados**: Ao propor alterações DDL, prefira comandos idempotentes com `IF NOT EXISTS` ou verificações preventivas em scripts de migração.
