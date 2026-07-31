# Walkthrough / Resumo de Entrega: Otimização de Performance (limit=10 & totalRegs) & Ajuste de Leitura Relacional

- **Data**: 2026-07-31
- **Status**: Concluído & Verificado

## Resumo dos Ajustes Realizados

### 1. Otimização de Performance da API VDS (`limit=10` + `totalRegs`)
- **Descoberta**: Reduzir a quantidade de itens por requisição de 50 para 10 no endpoint `/ocorrencia` faz o servidor remoto da VDS responder **5 vezes mais rápido** (de ~8s para < 1s), aliviando o banco de dados SQL Server deles.
- **Implementação**:
  - `vds_sync_ocorrencias` foi atualizada para consultar paginado com `limit=10`.
  - O sistema lê o campo `totalRegs` retornado pela VDS API e interrompe a busca assim que o total de registros do condomínio é atingido.

### 2. Correção da Listagem de Não Lidos na Visão Prática
- **Causa do Retorno 0**: A rotina de sync anterior tentava interpretar `"lida": true` retornado do lote global do condomínio e auto-marcar `lido = 1` para todos os conselheiros na tabela relacional `ocorrencia_leitura_conselheiro`. Isso fazia com que todos os 50 chamados fossem marcados como lidos automaticamente para o Conselheiro ID 5 assim que a sincronização rodava.
- **Solução**:
  - Desvinculamos a marcação automática no sync global. A tabela relacional `ocorrencia_leitura_conselheiro` responde **exclusivamente às ações do próprio conselheiro** (ao abrir/visualizar a mensagem no recMan ou ao clicar em "Marcar como Lido").
  - `vds_get_ocorrencias_pratico` consulta todos os chamados da tabela `ocorrencias` onde `(l.lido IS NULL OR l.lido = 0)`, apresentando perfeitamente a lista de não lidos para aquele conselheiro.

### 3. Fim das Notificações Falsas no Toast
- O Toast notifica **somente quando houverem chamados novos efetivamente inseridos** (`inserted > 0`) ou confirmações de leitura reenviadas (`flushedReads > 0`).

## Arquivos Atualizados
- `file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php`
- `file:///e:/DEV/recMan/vds_sync_async.php`
- `file:///e:/DEV/recMan/.agents/raciocinios/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
- `file:///e:/DEV/recMan/.agents/planos_e_workflows/walkthroughs/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
