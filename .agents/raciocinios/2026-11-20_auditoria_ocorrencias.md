# Raciocínio de Diagnóstico e Ajustes Conceituais: Módulo de Auditoria de Chamados

**Data:** 20/11/2026  
**Projeto:** recMan (Conselho Consultivo e Fiscal)  
**Objetivo:** Incorporar rigor conceitual e metodológico pré-implementação conforme determinações do Conselho:
1. Segregação estrita entre resposta e providência.
2. Taxonomia e classificação precisa dos participantes (isolando manifestações do Conselho de respostas da administração).
3. Preservação histórica e probatória das execuções da auditoria (snapshots imutáveis).
4. Ortogonalidade das 4 dimensões de análise.
5. Regras de cálculo temporal exatas nas fronteiras dos 7 dias regimentais.

---

## 1. Segregação entre Resposta e Providência (Art. 12 do Regimento Interno)

### 1.1 Fundamento Regulamentar
O Art. 12 do Regimento Interno estabelece expressamente duas obrigações concomitantes:
1. Dar retorno (manifestar-se formalmente perante o solicitante).
2. Iniciar a tomada das providências cabíveis.

### 1.2 Impossibilidade de Classificação Semântica Automática de "Providência"
- Uma resposta do tipo *"Recebido, estamos verificando"* ou *"Ciente"* é apenas uma manifestação informativa ou de reconhecimento. Não constitui início comprovado de providências.
- A API da VDS não possui um campo estruturado `providencia_tomada: true/false`.
- **Diretriz Mandatória:** O sistema **NÃO** utilizará heurísticas frouxas ou algoritmos de dedução semântica para classificar texto livre como se fosse comprovação documental de providência.
- **Critérios Objetivos de Identificação de Providências:**
  - *Providência Identificável no Histórico:* Ocorre quando a API oficial registra atos formais objetivos de movimentação, tais como:
    - Eventos de encaminhamento formal a funcionários ou setores executores (ex: `tipo = 79` na API VDS com `destinoTipo = 'F'` ou `'G'`).
    - Despachos com vinculação de Ordem de Serviço, agendamento explícito com número de OS ou envio formal a fornecedor homologado.
  - *Apenas Manifestação Registrada:* Mensagens textuais trocadas pelos gestores sem comprovação fática ou registro de ato procedimental.
  - *Nenhuma Providência Identificável:* Ausência de qualquer ato ou resposta.
  - *Não é Possível Determinar:* Histórico truncado ou dados insuficientes na API.

---

## 2. Taxonomia e Classificação dos Participantes

### 2.1 Análise dos Perfis Encontrados na API da VDS
A inspeção nos payloads reais (`docs/mocks/retornos dos endpoints`, `docs/inspect/chamados-vds.md`, `livroDeOcorrencias.php`) demonstrou que a API v8 da VDS utiliza:
- Campo `cargo` no nó raiz e em cada evento de `eventos[]`.
- Campo `por` com o nome da pessoa.
- Diretórios de avatares: `/app/dados/cond/1441/foto/SINDICO/...`, `/PESSOA/...`, `/MORADOR/...`.
- Strings encontradas: `"Conselheiro"`, `"CONDOMÍNIO - GESTÃO"`, `"Zelador"`, `"Síndico"`, `"Subsíndico"`, `"Bloco B - 1108"`, `"Morador"`, `"Funcionários"`.

### 2.2 Tabela de Classificação Institucional de Atores

| Categoria do Participante | Critérios de Mapeamento (API VDS) | Contabiliza como Resposta da Administração? |
|---|---|---|
| **Síndico** | `cargo` contendo "Síndico" (sem "Sub"), foto em pasta `/SINDICO/`, nome do Síndico Geral cadastrado | **SIM** (Manifestação direta do responsável regimental) |
| **Subsíndico** | `cargo` contendo "Subsíndico" ou "Síndico de Bloco" | **SIM** (Manifestação do representante do bloco) |
| **Administração** | `cargo` contendo "Administração", "CONDOMÍNIO - GESTÃO", "Gestão", "Gerência" | **SIM** (Manifestação institucional da gestão) |
| **Departamento Responsável** | `cargo` ou grupo contendo "Mensageria", "Supervisão", "Portaria", "Monitoramento", "Manutenção" | **SIM** (Manifestação do setor especializado) |
| **Funcionário / Colaborador** | `cargo` contendo "Zelador", "Porteiro", "Auxiliar", grupo de funcionários (`FUN`) | **SIM** (Apenas quando for ato de despacho/atendimento) |
| **Conselho Consultivo e Fiscal** | `cargo` contendo "Conselheiro", "Conselho", "Fiscal", ou usuário identificado como membro do Conselho | **NÃO** (O Conselho é órgão fiscalizador; sua atuação não representa resposta da administração ao morador) |
| **Morador / Reclamante** | `cargo` contendo "Morador", padrão `Bloco X - Y`, ou autor do chamado | **NÃO** (Manifestação do solicitante) |
| **Outros / Não Identificados** | Vazio, nulo ou descrição não enquadrada | **NÃO** (Requer conferência documental) |

---

## 3. Preservação Histórica e Probatória (Snapshots de Execuções)

### 3.1 O Problema da Mutabilidade
Um chamado auditado hoje com atraso de 40 dias pode ser fechado amanhã pelo síndico. Se o sistema apenas mantivesse colunas dinâmicas em `ocorrencias`, a apuração histórica seria desfeita, inviabilizando a apresentação formal em Assembleia Geral.

### 3.2 Arquitetura de Execuções e Snapshots
Propomos a criação de duas tabelas dedicadas:
1. `auditoria_sessoes` (Metadados da apuração, data de referência utilizada, filtros aplicados, data e hora da coleta, identificador único).
2. `auditoria_execucao_itens` (Snapshot imutável de cada chamado no exato momento daquela coleta: payload original da API, métricas calculadas, data da primeira resposta, tempos, classificações nas 4 dimensões).

Isso garante:
- Auditabilidade retrospectiva.
- Comparabilidade entre apurações sucessivas (ex: "Evolução do passivo de chamados entre Maio e Novembro").
- Imutabilidade das provas coligidas.

---

## 4. Ortogonalidade das 4 Dimensões

As 4 dimensões devem ser avaliadas de forma estritamente independente:
1. **Tempo Transcorrido:** Baseado estritamente no tempo decorrido desde a abertura (até 7 dias; 8 a 30 dias; 31 a 90 dias; 91 a 365 dias; > 365 dias).
2. **Situação da Resposta:** Baseado estritamente na existência e tempestividade da primeira manifestação administrativa (sem resposta; respondido no prazo; respondido com atraso; dados insuficientes).
3. **Situação do Chamado:** Baseado no status formal de ciclo de vida (aberto; em andamento; encerrado; não identificado).
4. **Situação das Providências:** Baseado na evidência de atos práticos de resolução (providência identificável; apenas manifestação registrada; nenhuma providência identificável; não é possível determinar).

---

## 5. Regras de Cálculo e Fronteira de 7 Dias

### 5.1 Critério Temporal Adotado
- **Cálculo por Períodos Completos de 24 Horas:**
  - $1 \text{ dia} = 86.400 \text{ segundos}$.
  - $7 \text{ dias} = 7 \times 86.400 = 604.800 \text{ segundos}$ ($168 \text{ horas}$).
- Se $\Delta s \le 604.800 \text{ s}$: **Dentro do prazo de 7 dias**.
- Se $\Delta s > 604.800 \text{ s}$: **Atraso** (inicia-se a contagem a partir de 8 dias).

### 5.2 Tratamento de Chamados Recentes
- Se $(\text{DataRef} - \text{Abertura}) \le 604.800 \text{ s}$ e não há manifestação:
  - O chamado **NÃO** pode ser classificado como atrasado.
  - Situação da resposta: `Sem resposta administrativa (em prazo regulamentar)`.

---

## 6. Fluxo de Inspeção, Julgamento e Deliberação do Conselho

### 6.1 Problema de UX Identificado
- No modelo anterior, a interface de auditoria apenas permitia visualizar passivamente o histórico ou redirecionava para o livro de ocorrências legado onde havia apenas um checkbox binário (`resolvido = 1`).
- Isso fragmentava o trabalho do auditor e não permitia registrar a qualificação formal exigida para a prestação de contas na Assembleia Geral.

### 6.2 Solução Implementada: Painel Integrado de Deliberação na Modal de Inspeção
- Ao clicar em **"Inspecionar"** na tabela de auditoria:
  1. A modal carrega a linha do tempo cronológica com destaque para autores (Síndico, Subsíndico, Gestão, Conselho, Morador) e identificação de encaminhamentos internos (que **não** são providências resolutivas).
  2. O auditor dispõe imediatamente do **Painel de Deliberação do Conselho**:
     - **Julgamento da Finalidade:** `Finalidade Atingida / Solucionado no Mundo Real`, `Não Atingiu a Finalidade / Irresolvido`, `Em Diligência / Auditoria Aberta`, `Improcedente / Sem Objeto`.
     - **Avaliação das Providências:** `Providência Efetiva Comprovada`, `Apenas Manifestação Protelatória`, `Nenhuma Providência Identificável`.
     - **Parecer Fundamentado:** Campo de notas e fundamentação probatória para citação direta no relatório da Assembleia.
  3. Ao salvar, a API AJAX atualiza os dados locais, registra uma nota interna comprobatória e reflete o badge correspondente na tabela em tempo real sem necessidade de recarregar a página.
