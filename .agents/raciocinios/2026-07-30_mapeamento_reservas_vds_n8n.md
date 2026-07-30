# Registro de Raciocínio: Mapeamento de Reservas VDS e Sugestão de Fluxo n8n

- **Data**: 2026-07-30
- **Tópico**: Mapeamento dos endpoints de Reserva da API v8 da Vida de Síndico (VDS), atualização da coleção do Postman e criação de template de workflow n8n para reserva competitiva automatizada.

---

## 1. Contexto e Objetivos

O usuário forneceu logs de inspeção HTTP de requisições de reserva (`urls-reservas.txt`) e seus respectivos retornos de API (`responses.txt`).
O objetivo é:
1. Analisar e identificar o fluxo mínimo e completo de consulta e execução de reservas/fila de espera na API v8 da VDS.
2. Complementar a coleção Postman existente (`docs/vds_api_v8_postman_collection.json`) com os novos endpoints e suas respectivas variações de resposta (Reserva Aprovada, Fila de Espera, Erro por limite de data, etc.).
3. Criar uma sugestão de fluxo para n8n (`docs/vds_reserva_competitiva_n8n.json`) focado na execução automatizada via Cron de reservas competitivas (com antecedência de N dias, ex: 30 dias), lidando com a tentativa de reserva e fallback para entrada na fila de espera caso o espaço já esteja reservado.

---

## 2. Análise Técnica dos Logs de Requisições e Respostas

### 2.1. Endpoints Identificados no Fluxo de Reservas

1. **`GET /area_comum`**:
   - **Query Params**: `Bloco.Uuid`, `Condominio.Uuid`, `Convite`, `Ativo`, `AgrupadorMenu`.
   - **Objetivo**: Listar as áreas comuns disponíveis para reserva (ex: Churrasqueiras, Espaço Gourmet, Espaço Zen, Home Cinema, Salões de Festa), com suas regras de negócio (`periodo`: "D" ou "H", `permitirFila`, `documento` termo de aceite, etc.).

2. **`GET /reserva_calendario`**:
   - **Query Params**: `recursoUuid`.
   - **Objetivo**: Retornar os dias indisponíveis/ocupados no calendário para um determinado recurso.

3. **`GET /reserva_horario`**:
   - **Query Params**: `Recurso.Uuid`, `tipoPeriodo=H`, `data`.
   - **Objetivo**: Consultar os horários de início/fim disponíveis para recursos do tipo por hora ("H").

4. **`GET /reserva_termo`**:
   - **Query Params**: `Morador.Uuid`, `Recurso.Uuid`, `DtReserva`.
   - **Objetivo**: Retornar o termo de uso/aceite preenchido dinamicamente com dados do morador e unidade para a reserva.

5. **`GET /area_comum_opcional`**:
   - **Query Params**: `DtIni`, `DtFim`, `Recurso.Uuid`.
   - **Objetivo**: Listar itens opcionais associados ao recurso para aquela data/período.

6. **`GET /reserva` (Consulta)**:
   - **Query Params Variação A**: `page`, `limit`, `sortBy`, `order`, `dtIni` -> Lista reservas do usuário.
   - **Query Params Variação B**: `Uuid`, `Agendamento`, `Emprestimo` -> Detalha uma reserva específica.
   - **Query Params Variação C**: `DtIni`, `DtFim`, `Recurso.Uuid`, `limit`, `ReservaCalendario`, `OcultarInterditado`, `Ativo` -> Verifica se o recurso já tem reservas/fila na data solicitada.

7. **`POST /reserva` (Criação de Reserva / Fila de Espera)**:
   - **Payload JSON**:
     ```json
     {
       "morador": { "uuid": "..." },
       "unidade": {
         "uuid": "...",
         "bloco": { "uuid": "..." }
       },
       "recurso": { "uuid": "..." },
       "dtIni": "30/08/2026",
       "dtFim": "30/08/2026",
       "termoUso": true,
       "permissao": false,
       "isencaoTaxaReserva": false,
       "taxaLimpeza": false
     }
     ```
   - **Retornos de Resposta**:
     - *Status Reserva Aprovada*: `{ "message": "Registrada com status: Reserva aprovada", "registro": { "uuid": "...", "status": { "uuid": "2", "nome": "Reserva aprovada" } } }`
     - *Status Fila de Espera*: `{ "message": "Registrada com status: Fila de espera", "registro": { "uuid": "...", "status": { "uuid": "5", "nome": "Fila de espera" } } }`
     - *Erro de Regra de Negócio (ex: Limite de antecedência)*: `{ "status": false, "message": "Data de reserva não pode ser superior a: 29/08/2026" }`

---

## 3. Planejamento da Divisão da Demanda (Duas Fases)

### Fase 1: Mapear e Documentar no Postman (`vds_api_v8_postman_collection.json`)
- Adicionar uma nova seção no JSON: `"7. Áreas Comuns & Reservas"`.
- Incluir as requisições organizadas e documentadas com `pm.collectionVariables` e `response examples` representativos para cada cenário (Reserva Aprovada, Fila de Espera, Erro de Limite).

### Fase 2: Elaboração do JSON para n8n (`docs/vds_reserva_competitiva_n8n.json`)
- Estruturar um workflow n8n interoperável em JSON contendo:
  1. **Schedule Trigger (Cron)**: Execução automática configurável (ex: diariamente a meia-noite).
  2. **Set Configuration Variables**: Nó de parâmetros editáveis pelo usuário (`baseUrl`, `username`, `password`, `condominioUuid`, `blocoUuid`, `unidadeUuid`, `moradorUuid`, `recursoUuid`, `diasAntecedencia`, `tipoPeriodo`, `horarioIni`, `horarioFim`).
  3. **Date Calculation Code/Set Node**: Cálculo dinâmico da data alvo em formato `DD/MM/YYYY` (hoje + `diasAntecedencia`).
  4. **HTTP Request Node - Boot Auth (`/auth/anon`)**: Obtenção do token temporário.
  5. **HTTP Request Node - User Login (`/login`)**: Autenticação e extração do `bearerToken`.
  6. **HTTP Request Node - Make Reservation (`POST /reserva`)**: Chamada de disparo da reserva.
  7. **Switch/IF Node**: Verificação do status retornado (`Reserva aprovada`, `Fila de espera` ou Erro) e rotas de notificação/log.

---

## 4. Próximos Passos
- Apresentar o plano detalhado no `implementation_plan.md` e no arquivo `.agents/planos_e_workflows/planos/2026-07-30_mapeamento_reservas_vds_n8n.md`.
- Solicitar aprovação do usuário para dar início à Fase 1 e Fase 2.
