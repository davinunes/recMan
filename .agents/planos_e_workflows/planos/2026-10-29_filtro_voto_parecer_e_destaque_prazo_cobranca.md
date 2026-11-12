# Plano de Implementação: Filtro por Resultado do Parecer (Tipo de Voto) e Suporte Visual a Prazos de Cobrança

**Data**: 2026-10-29  
**Arquivos Envolvidos**:
- `classes/repositorio.php`
- `palco/planSoluc.php`

---

## 1. Ajustes em `classes/repositorio.php`

1. **Atualizar `getAllNotificacoes()` e `getNotificacoesByFilters()`**:
   - Adicionar subquery para capturar `resultado_voto` apurado:
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
   - Adicionar cálculo de dias desde o envio:
     ```sql
     ,DATEDIFF(CURRENT_DATE(), n.data_envio) AS dias_desde_envio
     ```
2. **Adicionar suporte ao parâmetro `$voto_parecer = 'padrao'` em `getNotificacoesByFilters`**:
   - Se for `'padrao'` ou `'sem_resultado_ou_manter'`:
     Filtra `WHERE (...resultado_voto IS NULL OR ...resultado_voto = 'manter')`.
   - Se for `'manter'`, `'revogar'`, `'converter'`:
     Filtra `WHERE (...resultado_voto = '$voto')`.
   - Se for `'sem_resultado'`:
     Filtra `WHERE (...resultado_voto IS NULL)`.
   - Se for `'todos'`:
     Não aplica filtro de voto.

---

## 2. Ajustes em `palco/planSoluc.php`

1. **Adicionar Campo Select no Formulário de Filtros**:
   - `<label for="parecer_voto">Resultado Parecer (Voto):</label>`
   - Opções:
     - `padrao`: "Padrão (Sem resultado ou Manter)" [Selecionado por default]
     - `todos`: "Todos os resultados"
     - `sem_resultado`: "Sem Resultado"
     - `manter`: "Manter"
     - `revogar`: "Revogar"
     - `converter`: "Converter"
2. **Atualização da Chamada dos Filtros no PHP**:
   - Capturar `$_GET['parecer_voto']` (padrão `'padrao'`).
   - Passar para `getNotificacoesByFilters`.
3. **Cálculo da Regra de Prazos de Cobrança**:
   - `$estaNoPrazo = (empty($item['dia_retirada']) && $diasDesdeEnvio !== null && $diasDesdeEnvio < 31) || (!empty($item['dia_retirada']) && $diferencaDias !== null && $diferencaDias < 7);`
4. **Renderização Visual nos Cards**:
   - Se `$estaNoPrazo`:
     - Exibir ícone `schedule` e badge de destaque visual "No Prazo (faltam X dias)".
   - Se `!$temMulta && !$estaNoPrazo`:
     - Exibir ícone `priority_high` / `notification_important` e badge "Deveria estar sendo cobrada".
   - Na coluna jurídica:
     - Exibir o voto apurado com badge (`MANTER`, `REVOGAR`, `CONVERTER` ou `Sem votos`).
5. **Busca Rápida e Resumo de Estatísticas**:
   - Atualizar contadores de estatísticas com total no prazo vs prontas para cobrança.

