# Plano de Implementação - Agregação por Maior Número de Votos no Gráfico de Pareceres

- **Data**: 2026-10-07
- **Módulo**: Dashboard (`palco/dashboard.php`) & Repositório (`classes/repositorio.php`)

## Objetivos
Refatorar a função `getEstatisticas($mes, $ano)` em `classes/repositorio.php` para agrupar a distribuição de pareceres pela opção de voto com maior número de votos (ou fallback padronizado), evitando fatias duplicadas/fragmentadas no gráfico de pizza do Dashboard.

## Alterações Propostas

### `classes/repositorio.php`
- Atualizar a função `getEstatisticas($mes = null, $ano = null)`:
  1. Buscar pareceres no período (`YEAR(p.data) = '$ano'` e opcional `MONTH(p.data) = '$mes'`), relacionando com `conselho.recurso r ON r.numero = p.id`.
  2. Consultar os votos na tabela `conselho.votos` para os recursos encontrados e determinar o voto majoritário (modo 'manter', 'revogar' ou 'converter').
  3. Caso não haja votos na tabela para aquele recurso, aplicar expressão regular/busca por substring nos campos `p.conclusao` e `p.resultado` para identificar se a intenção foi "REVOGAR", "CONVERTER" ou "MANTER".
  4. Retornar o array no formato esperado por `palco/dashboard.php` e `palco/estatisticas.php`:
     - `conclusao`: "MANTER", "REVOGAR", ou "CONVERTER EM ADVERTÊNCIA" (ou a string legada devidamente padronizada)
     - `total_pareceres`: contagem consolidada
     - `lista_ids`: IDs dos pareceres associados

## Plano de Teste / Validação
- Verificar se o retorno de `getEstatisticas($mes, $ano)` agrupa corretamente os pareceres em fatias limpas.
- Confirmar que pareceres sem votos usam o fallback inteligente baseado no texto sem quebrar.
- Validar se `palco/dashboard.php` e `palco/estatisticas.php` renderizam os gráficos sem erros JS ou PHP.
