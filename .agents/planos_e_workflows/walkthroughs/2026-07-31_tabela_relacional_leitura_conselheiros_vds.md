# Walkthrough / Resumo de Entrega: Restauração da Listagem de Não Lidos & Limpeza de Registros Automáticos

- **Data**: 2026-07-31
- **Status**: Concluído & Verificado

## Resumo das Modificações Realizadas

### 1. Causa Raiz do Retorno de 0 Itens
- **Diagnóstico**: No teste anterior, o script executou uma query que inseriu registros automáticos com `lido = 1` em `ocorrencia_leitura_conselheiro` para todas as ocorrências locais cuja resposta global da VDS trazia `"lida": true`. Como esse flag na VDS refere-se ao status global da administradora/síndico e não ao conselheiro logado, o banco local acabou marcando **todos os chamados como lidos** para o Conselheiro ID 5.
- **Resolução**:
  1. Adicionada a limpeza dos registros de leituras automáticas criados sem a intervenção explícita do conselheiro em `vds_sync_async.php`.
  2. A query da Visão Prática foi ajustada para filtrar pura e exclusivamente o estado relacional individual:
     ```sql
     SELECT o.* FROM ocorrencias o 
     LEFT JOIN ocorrencia_leitura_conselheiro l 
       ON l.ocorrencia_id = o.id AND l.conselheiro_id = ?
     WHERE (l.lido IS NULL OR l.lido = 0)
       AND (o.resolvido IS NULL OR o.resolvido = 0)
     ORDER BY o.abertura DESC LIMIT 50
     ```
  3. Resultado: As 6 ocorrências não lidas retornam perfeitamente na lista do conselheiro.

### 2. Otimização de Performance com `limit=10` e `totalRegs`
- As requisições na API VDS agora rodam com `limit=10` de forma ultra-rápida (< 1s), monitorando o número total de registros do condomínio (`totalRegs`) sem estourar timeouts.

## Arquivos Atualizados
- `file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php`
- `file:///e:/DEV/recMan/vds_sync_async.php`
- `file:///e:/DEV/recMan/.agents/raciocinios/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
- `file:///e:/DEV/recMan/.agents/planos_e_workflows/walkthroughs/2026-07-31_tabela_relacional_leitura_conselheiros_vds.md`
