---
name: vds_uuid_mapper
description: Diretrizes e convenções para o mapeamento e cache local de UUIDs e Categorias remotas da API v8 Vida de Síndico na tabela local vds_uuid_mapping.
---

# Skill: Mapeamento e Cache Local de UUIDs e Categorias (VDS / Conselho)

Esta skill estabelece a convenção para resolver e salvar UUIDs, Blocos, Unidades e Categorias (`ocoTipo`) da API v8 Vida de Síndico na tabela local `vds_uuid_mapping`, acelerando a renderização e reduzindo requisições repetitivas.

## 1. Estrutura da Tabela `vds_uuid_mapping`

| Campo | Tipo | Descrição | Exemplo |
|---|---|---|---|
| `entidade_tipo` | `VARCHAR(50)` | Tipo da entidade VDS/Conselho | `'bloco'`, `'unidade'`, `'usuario'`, `'categoria_ocorrencia'` |
| `chave_local` | `VARCHAR(100)` | Chave local / ID / ocoTipo | `'A:102'`, `'ocoTipo_115'`, `'ocoTipo_247'` |
| `uuid_remoto` | `VARCHAR(100)` | UUID retornado pela API v8 | `'c3a1b2c4-...'` ou ID numérico `'115'` |
| `dados_extras_json` | `JSON` / `TEXT` | Metadados (nome, ícone, descrição, foto) | `{"nome": "Fale com o Conselho", "icone": "..."}` |

## 2. Categorias de Ocorrência Pré-carregadas (`ocoTipo`)

As seguintes categorias padrão serão semeadas em `vds_uuid_mapping` durante a migração:

1. `114` - **Livro de ocorrência**
2. `86` - **Fale com o Síndico**
3. `109` - **Fale com o Síndico de Bloco**
4. `102` - **Fale com a Administração**
5. `145` - **Fale com a Mensageria**
6. `87` - **Fale com a Portaria**
7. `126` - **Fale com a Supervisão**
8. `115` - **Fale com o Conselho**
9. `247` - **Monitoramento**
10. `172` - **Suporte ao Controle de Acesso**

*Se um novo `ocoTipo` for retornado nas consultas da VDS, o sistema o inserirá automaticamente via UPSERT na tabela `vds_uuid_mapping` com `entidade_tipo = 'categoria_ocorrencia'`.*
