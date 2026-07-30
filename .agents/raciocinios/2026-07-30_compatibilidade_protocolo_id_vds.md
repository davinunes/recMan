# Registro de Raciocínio: Coexistência de IDs Legados (Protocolo Webscraping) e IDs API v8 (VDS)

- **Data**: 2026-07-30
- **Tópico**: Diagnóstico e resolução da ambiguidade/colisão entre IDs locais legados (que armazenavam o número do protocolo VDS) e os novos IDs internos da VDS API v8.

---

## 1. Causa Raiz do Conflito Identificado

1. **Sistema Legado (Webscraping)**:
   - Não havia API RESTful estruturada; a raspagem HTML identificava a ocorrência apenas pelo **Número do Protocolo** (ex: `259564`).
   - O número de protocolo foi salvo diretamente na coluna `id` da tabela MySQL `ocorrencias`. A coluna `protocolo_vds` permanecia `NULL`.

2. **Sistema Atual (API v8 REST)**:
   - A VDS API v8 retorna dois valores distintos:
     - `ocorrenciaId` / `id`: ID numérico sequencial da VDS (ex: `260248`).
     - `protocolo`: Número do protocolo amigável (ex: `"259564"`).
     - `uuid`: Hash UUID remoto (ex: `"6c1da1e7..."`).
   - A sincronização salvava `ocorrenciaId` (`260248`) no campo `id` e `protocolo` (`"259564"`) no campo `protocolo_vds`.

3. **Impacto**:
   - Se uma ocorrência antiga tinha `id = 259564` (protocolo) e `protocolo_vds = NULL`, ao sincronizar via API v8 (onde `id = 260248` e `protocolo = 259564`), a busca `WHERE id = 260248 OR protocolo_vds = '259564'` não achava a linha legada.
   - Isso gerava **duplicidade de registros** para o mesmo chamado.

---

## 2. Solução Aplicada (Coexistência Transparente e Sem Conflitos)

1. **Auto-Backfill Legado**:
   - `UPDATE ocorrencias SET protocolo_vds = CAST(id AS CHAR) WHERE (protocolo_vds IS NULL OR protocolo_vds = '') AND id > 0;`
   - O campo `protocolo_vds` passa a estar 100% preenchido para todas as ocorrências históricas.

2. **Ajuste na Busca de Sincronização (`vds_ocorrencia_service.php`)**:
   - A busca local no sync foi ajustada para:
     `SELECT id FROM ocorrencias WHERE id = ? OR protocolo_vds = ? OR uuid_remoto = ? OR id = ? LIMIT 1`
   - Binda o `id` da VDS e o `protocolo` como inteiro para casar com o `id` legado.
   - Se encontra a linha legada (com `id = 259564`), atualiza aquela mesma linha em vez de criar um duplicado, atribuindo o `uuid_remoto` e o `dados_json` da VDS API v8.

3. **Rotina de Enriquecimento de Legados**:
   - Criada a função `vds_enrich_legacy_ocorrencias()`, que varre o banco local e busca na VDS API v8 todos os dados faltantes dos chamados legados.
