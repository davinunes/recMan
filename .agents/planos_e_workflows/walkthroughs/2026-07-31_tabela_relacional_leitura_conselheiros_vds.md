# Walkthrough / Resumo de Entrega: Cache Relacional de Leitura por Conselheiro & Auto-Marcação ao Abrir Ocorrência

- **Data**: 2026-07-31
- **Status**: Concluído & Verificado

## Resumo das Modificações Realizadas

### 1. Tabela Relacional Local (`ocorrencia_leitura_conselheiro`)
- **Objetivo**: Controlar o status de leitura por conselheiro individualmente no recMan sem depender do endpoint instável da VDS (`GET /ocorrencia?Lida=0`), que apresentava estouro de tempo limite (SQL Command Timeout) no backend remoto.
- **Estrutura**:
  - `conselheiro_id`, `ocorrencia_id`, `uuid_remoto`, `lido`, `sincronizado_remoto`, `read_at`.
  - Chave Única: `uk_conselheiro_ocorrencia (conselheiro_id, ocorrencia_id)`.

### 2. Auto-Marcação de Leitura ao Abrir Ocorrência Individual
- **Comportamento**: Ao clicar ou abrir uma ocorrência individual na interface (`?id=` ou `?protocolo=`), o sistema chama automaticamente `vds_marcar_como_lido($uuidRemoto, $usuarioIdConselho, $id, true)`.
- Isso grava a leitura `lido = 1` no banco relacional local e atualiza o estado do botão para `"Marcar NÃO Lido (VDS)"`.
- Se o conselheiro clicar no botão para alternar para "Não Lido", o sistema atualiza `lido = 0`, recolocando a ocorrência na lista de pendentes da Visão Prática.

### 3. Sincronização Assíncrona por AJAX em Segundo Plano (`vds_sync_async.php`)
- Criado o endpoint leve [vds_sync_async.php](file:///e:/DEV/recMan/vds_sync_async.php).
- **Disparo Silencioso**: 1,5 segundos após a abertura da página, o JavaScript inicia a sincronização assíncrona via AJAX sem travar o carregamento HTML ou a navegação do conselheiro.
- **Toasts Temporários (10s)**: Quando novos chamados são encontrados na VDS, o sistema exibe um Toast sutil temporário por **10 segundos**.
- **Loop de Retry (60s)**: Caso o servidor remoto da VDS apresente lentidão ou timeout, a função remarca uma nova tentativa para dali a 60 segundos em background.

### 4. Cron de Sincronização (15 min) com Memória de Data (`cron_vds_sync.php`)
- O script [cron_vds_sync.php](file:///e:/DEV/recMan/cron_vds_sync.php) grava a data/hora da última sincronização bem-sucedida em `vds_uuid_mapping` (chave `ultima_sincronizacao_ocorrencias`).
- A função `vds_flush_pending_reads()` descarrega em segundo plano todas as confirmações de leitura pendentes (`sincronizado_remoto = 0`) para a VDS API.

## Arquivos Criados e Modificados
- `file:///e:/DEV/recMan/vds_sync_async.php` (NOVO)
- `file:///e:/DEV/recMan/migrate_vds_integration.php`
- `file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php`
- `file:///e:/DEV/recMan/livroDeOcorrencias.php`
- `file:///e:/DEV/recMan/cron_vds_sync.php`
- `file:///e:/DEV/recMan/.agents/raciocinios/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
- `file:///e:/DEV/recMan/.agents/planos_e_workflows/planos/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
- `file:///e:/DEV/recMan/.agents/planos_e_workflows/walkthroughs/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
