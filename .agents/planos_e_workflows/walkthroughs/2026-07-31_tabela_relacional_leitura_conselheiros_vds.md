# Walkthrough / Resumo de Entrega: Filtro de Leitura Remota VDS & Notificação Inteligente de Toasts

- **Data**: 2026-07-31
- **Status**: Concluído & Verificado

## Resumo dos Ajustes Realizados

### 1. Leitura e Processamento dos Campos `lida` / `isLida` da VDS API
- **Problema**: A API VDS retorna no objeto JSON os campos `"lida": true` / `"isLida": true` para chamados que já foram lidos pelo usuário no ambiente remoto da VDS. Como a query da Visão Prática não checava essas propriedades no `dados_json` e a tabela relacional ainda não continha o registro para aquele conselheiro, todos os 50 chamados eram erroneamente exibidos como não lidos.
- **Solução**:
  1. A query de busca da Visão Prática em `vds_get_ocorrencias_pratico` foi aprimorada com a cláusula SQL:
     `WHERE (l.lido = 0 OR (l.lido IS NULL AND (JSON_UNQUOTE(JSON_EXTRACT(o.dados_json, '$.lida')) != 'true' AND JSON_UNQUOTE(JSON_EXTRACT(o.dados_json, '$.isLida')) != 'true')))`
  2. Ao sincronizar qualquer ocorrência em `vds_sync_ocorrencias` ou ao executar `vds_sync_async.php`, se a VDS indicar que o chamado é lido (`"lida": true`), o sistema auto-popula a tabela relacional `ocorrencia_leitura_conselheiro` com `lido = 1` e `sincronizado_remoto = 1`.
  3. Resultado: Apenas os chamados **efetivamente NÃO lidos** são apresentados na Visão Prática.

### 2. Notificação por Toast Inteligente (Apenas quando houver NOVOS chamados)
- **Problema**: O Toast de background exibia a mensagem *"50 novo(s) chamado(s) sincronizado(s) da VDS!"* a cada 60 segundos porque reportava a quantidade total do lote lido na VDS, e não a quantidade de novos chamados inseridos.
- **Solução**:
  - `vds_sync_ocorrencias` agora calcula a quantidade exata de registros **novos efetivamente inseridos** (`inserted`).
  - O endpoint [vds_sync_async.php](file:///e:/DEV/recMan/vds_sync_async.php) formata a mensagem apenas se `newCount > 0` ou se houverem confirmações de leitura descarregadas (`flushedReads > 0`).
  - Caso contrário, a mensagem é `null` e o frontend não exibe nenhum Toast invasivo a cada minuto.

## Arquivos Atualizados
- `file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php`
- `file:///e:/DEV/recMan/vds_sync_async.php`
- `file:///e:/DEV/recMan/livroDeOcorrencias.php`
- `file:///e:/DEV/recMan/.agents/raciocinios/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
- `file:///e:/DEV/recMan/.agents/planos_e_workflows/walkthroughs/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
