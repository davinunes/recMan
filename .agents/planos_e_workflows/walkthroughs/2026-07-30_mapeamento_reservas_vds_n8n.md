# Walkthrough: Mapeamento de Reservas VDS e Automação de Reserva Competitiva no n8n

- **Data**: 2026-07-30
- **Status**: Concluído com Sucesso

---

## 1. Resumo das Alterações Efetuadas

### Fase 1: Mapeamento e Documentação no Postman
- **Arquivo Editado**: [vds_api_v8_postman_collection.json](file:///e:/DEV/recMan/docs/vds_api_v8_postman_collection.json)
- **Modificações**:
  - Adicionado a pasta `"6. Áreas Comuns & Reservas"` contendo os 10 endpoints do fluxo:
    1. `6.1 Listar Áreas Comuns (Recursos Reserváveis)` (`GET /area_comum`)
    2. `6.2 Consultar Calendário de Ocupação da Área Comum` (`GET /reserva_calendario`)
    3. `6.3 Consultar Horários Disponíveis (Período por Hora)` (`GET /reserva_horario`)
    4. `6.4 Consultar Termo de Aceite / Uso da Reserva` (`GET /reserva_termo`)
    5. `6.5 Consultar Opcionais da Área Comum` (`GET /area_comum_opcional`)
    6. `6.6 Consultar Minhas Reservas / Fila de Espera` (`GET /reserva`)
    7. `6.7 Consultar Ocupação / Reservas de um Recurso em Data Específica` (`GET /reserva`)
    8. `6.8 Consultar Detalhes da Reserva por UUID` (`GET /reserva?Uuid=...`)
    9. `6.9 Consultar Histórico da Reserva por UUID` (`GET /reserva_historico/...`)
    10. `6.10 Realizar Reserva ou Entrar na Fila de Espera` (`POST /reserva`)
  - Adicionados múltiplos exemplos de resposta (Mock Responses) para `POST /reserva`:
    - `Reserva Aprovada (Sucesso)` (HTTP 200)
    - `Entrou na Fila de Espera` (HTTP 200)
    - `Erro - Antecedência de Data Excedida` (HTTP 200)
  - Variáveis de coleção incluídas: `condominioUuid`, `moradorUuid`, `recursoUuid`, `reservaUuid`, `dtReserva`, `dtIni`.

### Fase 2: Elaboração do Fluxo Automatizado n8n
- **Arquivo Criado**: [vds_reserva_competitiva_n8n.json](file:///e:/DEV/recMan/docs/vds_reserva_competitiva_n8n.json)
- **Funcionalidades do Workflow**:
  1. **Schedule Trigger**: Disparo diário automático via Cron (`1 0 0 * * *`).
  2. **Configuração de Variáveis**: Nó com parâmetros editáveis pelo morador (`username`, `password`, `blocoUuid`, `unidadeUuid`, `moradorUuid`, `recursoUuid`, `diasAntecedencia`, etc.).
  3. **Calcular Data Alvo**: Script JavaScript nativo no n8n que calcula dinamicamente a data alvo (`Hoje + N dias`, ex: 30 dias) e formata `dtIni` e `dtFim` para o formato esperado pela VDS (`DD/MM/YYYY` ou `DD/MM/YYYY HH:mm`).
  4. **Autenticação em 2 Etapas**: `POST /auth/anon` para token anônimo e `POST /login` para extração do `Bearer Token`.
  5. **Tentativa de Reserva**: Disparo do payload para `POST /reserva`.
  6. **Avaliação de Status & Roteamento**: Roteamento baseado no nó Switch do n8n separando as saídas em `Reserva Aprovada`, `Fila de Espera` e `Erro / Falha`.

---

## 2. Validação e Verificação

- **Sintaxe JSON**: Ambas as estruturas de arquivo foram compiladas e validadas via script Python sem erros.
- **Importação no Postman**: Coleção pronta para importação no Postman (v2.1.0 schema).
- **Importação no n8n**: Workflow compatível com n8n (v1.x+).

---

## 3. Como Utilizar o Fluxo no n8n

1. Abrir o n8n e selecionar **Import from File**.
2. Selecionar o arquivo `docs/vds_reserva_competitiva_n8n.json`.
3. Abrir o nó **Configuração de Variáveis (UUIDs e Regras)**.
4. Preencher as chaves `username`, `password`, `blocoUuid`, `unidadeUuid`, `moradorUuid`, `recursoUuid` e a regra de `diasAntecedencia` (ex: 30).
5. Ativar o workflow para rodar via Cron a meia-noite (00:00:01).
