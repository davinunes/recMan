---
name: vds_uuid_mapper
description: Diretrizes e convenções para o mapeamento e cache local de UUIDs remotos da API v8 Vida de Síndico no banco de dados do Conselho (tabela vds_uuid_mapping).
---

# Skill: Mapeamento e Cache Local de UUIDs (VDS / Conselho)

Esta skill estabelece a convenção para resolver e salvar UUIDs da API v8 Vida de Síndico na tabela local `vds_uuid_mapping`, otimizando chamadas de API e acelerando buscas por chave composta (`bloco:unidade`) ou ID local.

## 1. Estrutura da Tabela `vds_uuid_mapping`

| Campo | Tipo | Descrição | Exemplo |
|---|---|---|---|
| `entidade_tipo` | `VARCHAR(50)` | Tipo da entidade VDS/Conselho | `'bloco'`, `'unidade'`, `'usuario'`, `'condominio'` |
| `chave_local` | `VARCHAR(100)` | Chave local / composta | `'A:102'`, `'conselheiro_3'`, `'A'` |
| `uuid_remoto` | `VARCHAR(100)` | UUID retornado pela API v8 VDS | `'c3a1b2c4-...'` |
| `dados_extras_json` | `JSON` / `TEXT` | Informações de perfil/complementares | `{"nome": "João", "foto": "..."}` |

## 2. Estratégia de Lookup e Fallback

1. **Consulta Primária (Cache Local):**
   ```sql
   SELECT uuid_remoto, dados_extras_json 
   FROM vds_uuid_mapping 
   WHERE entidade_tipo = ? AND chave_local = ?
   ```
2. **Fallback (Consulta API VDS se não encontrado):**
   - Para `unidade`: Executar `GET /unidade?Combo=True&bloco.uuid={blocoUuid}`.
   - Encontrar a unidade correspondente e salvar com `UPSERT` em `vds_uuid_mapping`.
3. **Reutilização Direct-Access:** Na próxima requisição, a busca será $O(1)$ direto no banco do Conselho.
