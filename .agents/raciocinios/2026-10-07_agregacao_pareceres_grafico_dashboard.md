# Raciocínio de Diagnóstico - Agregação por Votos no Gráfico de Pareceres do Dashboard

- **Data**: 2026-10-07
- **Tópico**: Agregação por maior número de votos no gráfico de distribuição de pareceres em `palco/dashboard.php` e `classes/repositorio.php`.

## 1. Problema Identificado
No painel do sistema (`index.php?pag=dashboard`), o gráfico "Distribuição de Pareceres" (renderizado via Highcharts em `chartPareceres`) agrega os dados fazendo `GROUP BY conclusao` diretamente na tabela `conselho.parecer`.

Devido à redação ser por vezes editada manualmente pelos conselheiros no momento da confecção do parecer (ex: "MANTER", "Manter a Penalidade", "Revogar a Multa", "CONVERTER EM ADVERTÊNCIA", etc.), o agrupamento textual direto gera diversas fatias fragmentadas na pizza para a mesma decisão final.

## 2. Hipótese e Solução Proposta
A regra de negócio do recMan estabelece que os recursos passam por votação dos conselheiros na tabela `conselho.votos` (opções: `manter`, `revogar`, `converter`).

A solução consiste em atualizar a função `getEstatisticas($mes, $ano)` em `classes/repositorio.php`:
1. Obter todos os pareceres do período selecionado (`$ano` e `$mes`).
2. Para cada parecer, consultar a opção de voto vencedora (maior número de votos na tabela `conselho.votos` para o recurso correspondente).
3. Mapear a opção vencedora para a categoria padronizada:
   - `manter` -> `MANTER`
   - `revogar` -> `REVOGAR`
   - `converter` -> `CONVERTER EM ADVERTÊNCIA`
4. Como fallback (caso o parecer seja antigo ou não possua votos registrados em `conselho.votos`), analisar o texto dos campos `conclusao` / `resultado` para inferir a categoria (procurando termos como "REVOGAR", "CONVERTER", "MANTER").
5. Agrupar os totais por categoria padronizada para retorno ao Highcharts.

## 3. Impacto e Compatibilidade
- **`palco/dashboard.php`**: Consome `$estatisticasPareceres = getEstatisticas($mes, $ano)` e itera nos campos `conclusao` e `total_pareceres`. A alteração na função manterá a mesma estrutura de array, garantindo 100% de compatibilidade.
- **`palco/estatisticas.php`**: Também utiliza `getEstatisticas($mes, $ano)` e se beneficiará da consolidação das fatias do gráfico.
