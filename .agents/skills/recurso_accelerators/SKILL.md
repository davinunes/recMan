---
name: recurso_accelerators
description: Padrões de agregação de contexto e consulta rápida de dados da VDS (eventos de acesso, visitas, entregas e chamados) na tela de julgamento de recursos (detalheRecurso.php).
---

# Skill: Aceleradores Contextuais de Análise de Recurso

Esta skill descreve como montar e apresentar o painel de suporte à decisão do conselheiro na tela [detalheRecurso.php](file:///e:/DEV/recMan/palco/detalheRecurso.php).

## 1. Agregação de Provas e Contexto

Ao analisar uma notificação/multa objeto de recurso, o Conselho precisa correlacionar a data da infração alegada com o histórico real do condomínio:

1. **Filtro de Período Rápido:**
   - Intervalo padrão: `dtInicio = DATA_OCORRIDO - 12h` até `dtFim = DATA_OCORRIDO + 12h`.
2. **Histórico de Acessos (`GET /evento_acesso`):**
   - Cruzar entradas e saídas de pedestres e veículos vinculadas à `unidade` e `bloco` no momento exato da infração.
3. **Visitantes e Prestadores:**
   - Exibir foto e nome dos visitantes liberados pela portaria naquele dia.
4. **Chamados Relacionados (Tags de Unidade):**
   - Exibir ocorrências onde a unidade é autora, ré ou citada (`ocorrencia_unidade_tag`).
5. **Entregas e Encomendas:**
   - Entregas recebidas/retiradas no período para a unidade.
6. **Auditoria Financeira & Detecção de Multa/RI em Boletos (`GET /boleto`):**
   - Buscar os boletos da unidade (`Ano`, `Bloco.Uuid`, `Unidade.Uuid`).
   - Verificar se o boleto do mês/ano correspondente à notificação possui lançamento de penalidade disciplinar.
   - Seguir o link `urlSegundaVia` (Superlógica) para efetuar o scraping em HTML (`-FaturaHtml-flSegundaVia`) ou extrair texto do PDF buscando ocorrência de "Multa", "Infração" ou "Regimento Interno/RI", destacando o valor e o status de pagamento (Liquidado / Em Aberto).

## 2. Apresentação em Abas / Widgets

O painel deve ser integrado na lateral ou rodapé de `detalheRecurso.php` com carregamento assíncrono via AJAX/Fetch para não desacelerar a renderização da página principal.
