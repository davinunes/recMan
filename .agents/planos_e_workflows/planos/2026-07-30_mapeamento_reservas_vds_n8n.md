# Plano de Implementação: Mapeamento de Reservas VDS e Automação de Reserva Competitiva no n8n

- **Data**: 2026-07-30
- **Objetivo**: Mapear os endpoints de Reserva da API v8 Vida de Síndico no Postman e criar uma sugestão de workflow JSON para n8n focado em reservas competitivas via Cron.

---

## 1. Visão Geral do Plano

A demanda será executada estritamente em **duas fases sequenciais**, conforme solicitado:

### Fase 1: Mapear e Documentar no Postman
- Atualizar o arquivo [vds_api_v8_postman_collection.json](file:///e:/DEV/recMan/docs/vds_api_v8_postman_collection.json) adicionando a pasta `"7. Áreas Comuns & Reservas"`.
- Adicionar requisições completas para:
  1. `7.1 Listar Áreas Comuns (Recursos Reserváveis)` (`GET /area_comum`)
  2. `7.2 Consultar Calendário de Ocupação da Área Comum` (`GET /reserva_calendario`)
  3. `7.3 Consultar Horários Disponíveis` (`GET /reserva_horario`)
  4. `7.4 Consultar Termo de Uso / Aceite da Reserva` (`GET /reserva_termo`)
  5. `7.5 Consultar Opcionais da Área Comum` (`GET /area_comum_opcional`)
  6. `7.6 Consultar Minhas Reservas / Fila de Espera` (`GET /reserva`)
  7. `7.7 Consultar Detalhes e Histórico de Reserva por UUID` (`GET /reserva?Uuid=...` & `GET /reserva_historico/...`)
  8. `7.8 Realizar Reserva / Entrar na Fila de Espera` (`POST /reserva`)
- Adicionar exemplos de resposta (Response Mocks/Examples) no Postman para o endpoint `POST /reserva`:
  - Resposta de Sucesso: **Reserva aprovada**
  - Resposta de Sucesso: **Fila de espera**
  - Resposta de Falha/Erro: **Data superior ao limite de dias**

### Fase 2: Elaborar o Fluxo n8n (`docs/vds_reserva_competitiva_n8n.json`)
- Criar o arquivo JSON de workflow do n8n contendo:
  - **Cron Trigger**: Disparo programado (ex: diariamente à 00:00:01).
  - **Edit Fields (Variables)**: Nó de variáveis de configuração manual (`baseUrl`, `username`, `password`, `condominioUuid`, `blocoUuid`, `unidadeUuid`, `moradorUuid`, `recursoUuid`, `diasAntecedencia`, `tipoPeriodo`, `horarioIni`, `horarioFim`).
  - **Code / Date Calculation**: Nó em JS para calcular a data alvo (`hoje + diasAntecedencia`) no formato `DD/MM/YYYY` (ex: `30/08/2026`).
  - **Auth Step**: Requisições de Boot (`POST /auth/anon`) e Login (`POST /login`) para obter o `bearerToken`.
  - **Reservation Execution Step**: Requisição `POST /reserva` enviando o payload correspondente.
  - **Switch / Router**: Verificação do status retornado (`Reserva aprovada`, `Fila de espera`, ou Erro) com braços de saída para log/notificação.

---

## 2. Arquivos Modificados / Criados

- `[MODIFY]` [vds_api_v8_postman_collection.json](file:///e:/DEV/recMan/docs/vds_api_v8_postman_collection.json)
- `[NEW]` [vds_reserva_competitiva_n8n.json](file:///e:/DEV/recMan/docs/vds_reserva_competitiva_n8n.json)
- `[NEW]` [.agents/raciocinios/2026-07-30_mapeamento_reservas_vds_n8n.md](file:///e:/DEV/recMan/.agents/raciocinios/2026-07-30_mapeamento_reservas_vds_n8n.md)
- `[NEW]` [.agents/planos_e_workflows/planos/2026-07-30_mapeamento_reservas_vds_n8n.md](file:///e:/DEV/recMan/.agents/planos_e_workflows/planos/2026-07-30_mapeamento_reservas_vds_n8n.md)

---

## 3. Plano de Verificação

### Verificação da Fase 1 (Postman Collection)
- Validar a sintaxe JSON do arquivo [vds_api_v8_postman_collection.json](file:///e:/DEV/recMan/docs/vds_api_v8_postman_collection.json).
- Confirmar que a pasta `"7. Áreas Comuns & Reservas"` segue rigorosamente a estrutura, sintaxe de variáveis `{{bearerToken}}`, `{{baseUrl}}` e exemplos de resposta de outros itens da coleção.

### Verificação da Fase 2 (Fluxo n8n)
- Validar o formato JSON schema do n8n para garantir que possa ser importado diretamente no n8n sem erros de parsing.
- Verificar a existência e integridade dos nós: Trigger, Set Variables, Code (Data Alvo), Auth Anon, Login, Post Reserva, e IF/Switch de verificação de status.
