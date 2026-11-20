# Walkthrough / Taklin: Módulo de Auditoria de Chamados do Livro de Ocorrências

**Data:** 20/11/2026  
**Status:** Concluído com Sucesso  
**Projeto:** recMan (Conselho Consultivo e Fiscal)  

---

## 1. Escopo e Finalidade

Implementação do módulo **Auditoria de Ocorrências**, destinado a dotar o Conselho Consultivo e Fiscal de instrumento probatório, estatístico e documental sobre o cumprimento do prazo de até 7 dias estabelecido no Art. 12 do Regimento Interno para retorno e tomada de providências pelo Síndico e Subsíndicos.

A apuração é estritamente fática, neutra e probatória, pronta para embasar relatórios perante a Assembleia Geral.

---

## 2. Arquivos Criados e Modificados

| Arquivo | Tipo | Descrição |
|---|---|---|
| `migrates/migrate_auditoria_ocorrencias_v2.php` | Novo | Migração idempotente criando `auditoria_sessoes` e `auditoria_execucao_itens` e índices auxiliares na tabela `ocorrencias`. |
| `classes/vds_auditoria_service.php` | Novo | Motor de análise fática com segregação das 4 dimensões ortogonais, classificação determinística de participantes (Conselho isolado da gestão), contagem rigorosa de períodos de 24h e congelamento de snapshots imutáveis. |
| `palco/auditoriaOcorrencias.php` | Novo | Interface do painel de auditoria com cartões de indicadores gerais e de tempo, filtros multidimensionais, tabela com destaque para chamados com atraso $> 7$ dias, modal de snapshot de sessão e modal de linha do tempo de eventos. |
| `palco/exportar_auditoria.php` | Novo | Exportador completo em **CSV (Excel com UTF-8 BOM)**, **JSON estruturado** e **Relatório Executivo HTML para Impressão/PDF**. |
| `index.php` | Modificado | Inclusão da rota `auditoriaOcorrencias` no manipulador de requisições AJAX e no roteador principal. |
| `palco/tools.php` | Modificado | Adicionado atalho para o novo módulo no agrupador "Condomínio Digital (VDS API v8)". |
| `livroDeOcorrencias.php` | Modificado | Inclusão de botão de atalho no cabeçalho superior para transição direta ao painel de auditoria. |

---

## 3. As 4 Dimensões Ortogonais Implementadas

1. **Tempo Transcorrido:**
   - Até 7 dias
   - Atraso de 8 a 30 dias
   - Atraso de 31 a 90 dias
   - Atraso de 91 a 365 dias
   - Atraso superior a 365 dias
2. **Situação da Resposta (Art. 12):**
   - *Respondido no prazo:* Primeira manifestação administrativa em $\le 7$ dias completos ($604.800$ s).
   - *Respondido com atraso:* Primeira manifestação administrativa após 7 dias completos.
   - *Sem resposta registrada:* Prazo de 7 dias extrapolado sem manifestação administrativa.
   - *Em prazo regulamentar (< 7d):* Chamado recente aberto há menos de 7 dias, ainda aguardando manifestação (não é classificado como atraso).
   - *Dados insuficientes:* Ausência de histórico ou dados truncados.
3. **Situação do Chamado:**
   - Aberto
   - Em andamento
   - Encerrado
   - Não identificado
4. **Situação das Providências:**
   - *Providência identificável no histórico:* Registro de ato procedimental objetivo na API (encaminhamento formal tipo `79`, vínculo de Ordem de Serviço ou despacho executivo formal).
   - *Apenas manifestação registrada:* Houve mensagem textual da administração sem ato comprobatório formal.
   - *Nenhuma providência identificável:* Ausência de providências ou respostas.
   - *Não é possível determinar:* Histórico indisponível.

---

## 4. Preservação Histórica e Snapshots

- A funcionalidade permite ao Conselho realizar **Auditorias sob Demanda (Tempo Real)** e, a qualquer momento, acionar o botão **"Salvar Apuração Formal"**.
- Ao salvar:
  - É gerado um identificador único de execução (ex.: `AUD-20261120-113000`).
  - São congelados os metadados, a data de referência adotada, a data e hora da coleta, os filtros aplicados e os totais consolidados.
  - Cada chamado auditado tem seu estado e evidência bruta (`dados_originais_api_json`) preservados na tabela `auditoria_execucao_itens`.
  - Mesmo que um chamado seja fechado, respondido ou editado posteriormente no sistema da VDS, o snapshot congelado preserva a verdade fática da data da auditoria para apresentação na Assembleia.

---

## 5. Exportação e Entregáveis

- **CSV / Excel:** Contém todas as 28 colunas auditadas com delimitador `;` e marcador UTF-8 BOM, preservando os identificadores oficiais e as métricas calculadas.
- **JSON Estruturado:** Exporta os metadados, parâmetros de filtro, indicadores agregados e a lista de chamados com os dados originais.
- **Relatório Executivo:** Página estruturada pronta para impressão/PDF com fundamentação no Art. 12, cartões de indicadores, médias e tabela analítica.
