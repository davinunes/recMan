# Walkthrough: Filtro por Voto do Parecer e Destaque Visual de Prazos de Cobrança

**Data**: 2026-10-29  
**Objetivo Concluído**: Implementação do filtro por resultado do parecer apurado pelo **tipo de voto** (e não texto livre), com pré-filtro padrão para "Sem resultado ou Manter", acompanhado de suporte visual inteligente para notificações com prazo ativo vs. notificações que já deveriam estar sendo cobradas.

---

## Modificações Realizadas

### 1. Camada de Dados (`classes/repositorio.php`)
- **Apuração do Voto Predominante**:
  Adicionada a subquery de voto estruturado à consulta de notificações (`getAllNotificacoes` e `getNotificacoesByFilters`):
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
- **Contagem de Dias desde o Envio**:
  Adicionado `DATEDIFF(CURRENT_DATE(), n.data_envio) AS dias_desde_envio` para o cálculo preciso de prazos de entrega/ciência.
- **Novo Parâmetro de Filtro `$voto_parecer = 'padrao'`**:
  - `padrao`: Filtra onde `resultado_voto IS NULL OR resultado_voto = 'manter'`.
  - `todos`: Exibe todos os registros sem restrição de votos.
  - `sem_resultado`: Filtra exclusivamente registros onde `resultado_voto IS NULL`.
  - `manter`, `revogar`, `converter`: Filtra pelo voto específico.

---

### 2. Interface e UX (`palco/planSoluc.php`)
- **Novo Select de Filtro**:
  - Campo "Resultado Parecer (Voto)" posicionado de forma harmônica na barra de filtros.
  - Valor padrão: `padrao` ("Padrão (Sem resultado ou Manter)").
- **Destaque Visual de Notificações com Prazo Ativo**:
  - **Sem data de ciência mas enviada há menos de 31 dias**: sinalizada com badge azul e ícone de relógio: `No Prazo (Xd/31d)` com tooltip detalhado.
  - **Com data de ciência retirada menor que 7 dias atrás**: sinalizada com badge azul: `No Prazo (Xd/7d)` indicando o prazo recursal em curso.
  - Borda lateral do card azul (`card-status-no-prazo`) e bloco financeiro com status `⏳ No Prazo`.
- **Destaque Visual de Notificações Prontas para Cobrança (Deveriam estar Cobradas)**:
  - Notificações não cobradas cujo prazo expirou recebem a tag chamativa `⚠️ Cobrar Multa`, borda laranja/vermelha (`card-status-deve-cobrar`) e status financeiro `Cobrar Já`.
- **Coluna Jurídica Estruturada**:
  - Exibição de chips de votos: `Manter` (vermelho), `Revogar` (verde), `Converter` (âmbar) ou `Sem voto` (cinza), preservando o texto do parecer com suporte ao clique triplo.
- **Resumo Dinâmico e Busca Rápida**:
  - Contadores no cabeçalho exibindo em tempo real: `Total`, `Cobrados`, `Pendentes (⏳ X no prazo | ⚠️ Y cobrar já)`.
  - Atributos `data-no-prazo`, `data-voto` e indexação de busca para filtragem instantânea via caixa de busca.

