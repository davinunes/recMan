# Raciocínio: Filtro por Resultado do Parecer (Tipo de Voto) e Suporte Visual a Prazos de Cobrança de Multas

**Data**: 2026-10-29  
**Arquivo de Origem**: `palco/planSoluc.php`, `classes/repositorio.php`  
**Objetivo**:
1. Implementar filtro por resultado do parecer baseado no **tipo de voto** (`votos.voto`: `manter`, `revogar`, `converter` ou sem resultado/voto) em vez do texto livre (`parecer.conclusao` / `parecer.resultado`).
2. Configurar o valor padrão da listagem para já vir filtrado por: **Sem resultado OU Manter**.
3. Destacar visualmente com ícones e badges as notificações que **ainda estão no prazo para cobrar**:
   - Sem data de ciência, mas enviada há menos de 31 dias (`DATEDIFF(CURDATE(), data_envio) < 31`).
   - Com data de ciência menor que 7 dias atrás (`DATEDIFF(CURDATE(), dia_retirada) < 7`).
4. Fornecer suporte visual imediato para identificar com precisão quais multas **já deveriam estar sendo cobradas** vs. quais **ainda estão no prazo**.

---

## 1. Mapeamento do Banco de Dados

Conforme verificado em `storage/backup_conselho_20260329_103406_schema.sql` e `classes/repositorio.php`:
- Tabela `notificacoes`:
  - `numero`, `ano`, `torre`, `unidade`, `data_envio` (DATE), `numero_ano_virtual` (VIRTUAL `CONCAT(numero, '/', ano)`).
- Tabela `DatasDeRetirada`:
  - `virtual` (`CONCAT(notificacao, '/', ano)`), `dia_retirada` (DATE).
- Tabela `recurso`:
  - `id` (PK), `numero` (corresponde a `n.numero_ano_virtual`).
- Tabela `votos`:
  - `id_recurso` (FK `recurso.id`), `voto` ENUM(`'manter'`, `'revogar'`, `'converter'`).
- Tabela `multas_cobradas`:
  - `numero`, `ano`, `valor`, `data_vencimento`, `data_pagamento`.

---

## 2. Apuração do Resultado do Voto

Ao contrário do campo textual de `parecer` (`conclusao` ou `resultado`), o tipo do voto do parecer é obtido pela apuração dos votos dos conselheiros vinculados ao recurso da notificação:
```sql
(
    SELECT LOWER(v.voto)
    FROM recurso r
    JOIN votos v ON v.id_recurso = r.id
    WHERE r.numero = n.numero_ano_virtual
    GROUP BY LOWER(v.voto)
    ORDER BY COUNT(*) DESC, v.id DESC
    LIMIT 1
) AS resultado_voto
```
Possíveis valores resultantes:
- `'manter'`: voto predominante foi Manter a penalidade.
- `'revogar'`: voto predominante foi Revogar a penalidade.
- `'converter'`: voto predominante foi Converter em Advertência.
- `NULL` (ou `'sem_resultado'`): não há votos registrados (ou recurso inexistente).

### Regra do Filtro Padrão
Para cobrança de multas, as penalidades que devem ser cobradas são aquelas que:
- Não têm resultado/voto de recurso (`resultado_voto IS NULL`); OU
- Tiveram recurso julgado com manutenção da multa (`resultado_voto = 'manter'`).
Penalidades com resultado `revogar` ou `converter` não devem ser cobradas. Por isso, a visualização padrão filtra `(sem resultado OU manter)`.

---

## 3. Lógica de Prazos de Cobrança

1. **Notificação no Prazo**:
   - Condição 1: `empty($item['dia_retirada']) && $diasDesdeEnvio !== null && $diasDesdeEnvio < 31`
   - Condição 2: `!empty($item['dia_retirada']) && $diferencaDias !== null && $diferencaDias < 7`
   - Se verdadeira: `$estaNoPrazo = true`.
     - Exibição de ícone `schedule` / `hourglass_top`, badge azul/ciano "No Prazo".
2. **Notificação Fora do Prazo e Não Cobrada**:
   - `!$temMulta && !$estaNoPrazo`
   - Esta notificação **deveria estar sendo cobrada**.
   - Exibição de destaque com ícone `warning` / `priority_high`, badge "Deveria ser cobrada" / "Pronta para Cobrança".

