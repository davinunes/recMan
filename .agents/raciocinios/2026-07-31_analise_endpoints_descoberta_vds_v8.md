# Raciocínio: Análise e Descoberta de Endpoints Faltantes na API VDS v8

**Data:** 2026-07-31
**Objetivo:** Analisar a coleção Postman existente, identificar padrões de nomenclatura/estrutura e inferir endpoints de LEITURA prováveis que ainda não foram mapeados, com foco especial na lacuna "quem registrou o acesso" em visitas/eventos.

---

## 1. Inventário Consolidado: Endpoints de Leitura Já Mapeados

| Módulo | Endpoint GET | Descrição |
|---|---|---|
| **Auth/Usuário** | `/usuario/status` | Health check da sessão Bearer |
| | `/perfil` | Dados do perfil autenticado (usuario, condominio, papel) |
| | `/pessoa_documento_tipo` | Tipos de documento de identificação (CPF, RG, CNH) |
| **Ocorrências** | `/ocorrencia` | Lista paginada (filtros: Lida, Caixa, Protocolo, page, limit) |
| | `/ocorrencia/{uuid}` | Detalhes + histórico de eventos da ocorrência |
| **Portaria/Entregas** | `/evento_acesso` | Acessos (pedestre/veículo) por período + unidade |
| | `/entrega` | Entregas/correspondências por unidade |
| | `/entrega/{uuid}` | Detalhes da entrega (código, eventos, retiradoPor, anexos) |
| | `/portaria/visitante` | Visitantes/prestadores por DestinoUuid + DestinoTipo |
| | `/portaria/visitante/{uuid}` | Detalhes individuais do visitante |
| | `/portaria/visitante/{uuid}/validade` | Validade/bloqueio do visitante por tipo |
| **Autorizações Acesso** | `/autorizacao_acesso` | Lista paginada autorizações (filtros: Bloco, Unidade, dtIni/dtFim) |
| | `/autorizacao_acesso/{uuid}` | Detalhes da autorização |
| | `/autorizacao_acesso/convite/{chave}` | Busca por chave de convite social |
| | `/autorizacao_acesso/reserva/{uuid}` | Busca por UUID de reserva vinculada |
| | `/autorizacao_acesso/documento_pessoa` | Busca pessoa por documento em autorizações anteriores |
| | `/autorizacao_acesso/reserva_vaga` | Vagas disponíveis para reserva por tipo |
| | `/autorizacao_acesso/status` | Lista de status possíveis |
| | `/autorizacao_acesso/validade` | Valida autorização no momento atual |
| | `/autorizacao_acesso/convite/visitante` | Consulta convite por documento do visitante |
| | `/autorizacao_acesso/relatorio` | Relatório com filtros avançados |
| | `/autorizacao_acesso_convite` | Tipos de convite social disponíveis |
| | `/autorizacao_acesso_convite/quantidade` | Saldo/quantidade disponível de convites |
| | `/autorizacao_acesso_tipo` | Tipos de autorização de acesso |
| | `/autorizacao_acesso_tipo/periodo_limite` | Período limite por tipo + data inicial |
| | `/autorizacao_acesso_tipo/mensagem_periodo` | Mensagem informativa por tipo de visitante |
| **Estrutura Física** | `/bloco` | Lista blocos (?Combo=True&IsAdmin=false) |
| | `/unidade` | Unidades por bloco (?Combo=True&bloco.uuid=) |
| | `/morador` | Moradores por unidade (?Unidade.Uuid=&Combo=true) |
| | `/veiculo` | Veículos da unidade (?Unidade.Uuid=&order=asc) |
| | `/veiculo/{uuid}` | Detalhes completos do veículo |
| | `/obra` | Lista paginada de obras (20+ filtros query params) |
| **Financeiro** | `/boleto` | Boletos por ano/bloco/unidade (paginado) |
| **Reservas/Áreas** | `/area_comum` | Áreas comuns/recursos reserváveis |
| | `/area_comum_opcional` | Itens opcionais da área comum por período |
| | `/reserva_calendario` | Dias ocupados no calendário do recurso |
| | `/reserva_horario` | Horários disponíveis (tipoPeriodo=H) em data específica |
| | `/reserva_termo` | Termo de aceite preenchido (morador + recurso + data) |
| | `/reserva` | Lista reservas OU detalhe por ?Uuid= (mesmo endpoint) |
| | `/reserva_historico/{uuid}` | Histórico de auditoria da reserva |

---

## 2. Padrões Identificados na API (Heurísticas Fundamentais)

### 2.1 Nomenclatura e Estrutura de URL
```
[PADRÃO 1] Recursos principais no plural: /ocorrencias → NÃO (usa singular: /ocorrencia)
           → Todos os endpoints usam NOME SINGULAR: /entrega, /veiculo, /morador, /bloco, /unidade, /boleto, /obra, /reserva

[PADRÃO 2] Snake_case para nomes compostos:
           /evento_acesso, /autorizacao_acesso, /reserva_calendario, /reserva_horario,
           /reserva_termo, /reserva_historico, /area_comum, /area_comum_opcional

[PADRÃO 3] Sub-recursos (nível 2 e 3):
           Nível 2: /autorizacao_acesso/{acao} → /convite, /reserva, /documento_pessoa, /reserva_vaga, /status, /validade, /relatorio, /notifica_email, /lista
           Nível 3: /autorizacao_acesso/convite/{chave}, /portaria/visitante/{uuid}/validade

[PADRÃO 4] Detalhe individual via path param UUID:
           /{recurso}/{uuid}
           ✓ Implementado: /ocorrencia/{uuid}, /entrega/{uuid}, /veiculo/{uuid}, /autorizacao_acesso/{uuid}, /reserva_historico/{uuid}
           ✗ FALTANDO: /evento_acesso/{uuid}, /morador/{uuid}, /bloco/{uuid}, /unidade/{uuid}, /boleto/{uuid}, /obra/{uuid}, /portaria/{uuid}

[PADRÃO 5] Módulo "portaria" como namespace separado:
           /portaria/visitante (lista + detalhe + validade)
           → Espaço reservado para entidades operacionais de portaria
```

### 2.2 Query Params: Convenções
```
[PADRÃO 6] Filtros aninhados com notação de PONTO (case-insensitive em alguns):
           Unidade.Uuid, Bloco.Uuid, Condominio.Uuid, Recurso.Uuid, Morador.Uuid
           OBS: Em /evento_acesso usa "unidade.uuid" (minúsculo) — verificar se os dois funcionam

[PADRÃO 7] Paginação universal:
           ?page=N&limit=M&sortBy=campo&order=asc|desc

[PADRÃO 8] Filtros de data inconsistentes mas reconhecíveis:
           dtIni / dtFim       → autorizacao_acesso, obra, area_comum_opcional, reserva
           dtInicio / dtFim    → evento_acesso
           DtIni / DtFim       → relatórios, reserva
           Observação: Testar SEMPRE as 3 variações (dtIni, DtIni, dtInicio)

[PADRÃO 9] Combo=true para endpoints que alimentam dropdowns:
           ?Combo=True → bloco, unidade, morador
           Retorna payload reduzido (só uuid + nome normalmente)
```

### 2.3 Padrões de "Ação" em Sub-recursos
```
[PADRÃO 10] Ações comuns usadas em sub-recursos de /autorizacao_acesso:
           /relatorio     → Gera relatório exportável/filtrado
           /status        → Enumerar status possíveis (tabela de domínio)
           /validade      → Verificar vigência no momento
           /tipo          → (separado em outro endpoint top-level)
           /convite       → Operações específicas de convite social
           /qrcode        → Operações de QR (em outro endpoint: autorizacao_acesso_qrcode)
           /historico     → Histórico de auditoria (ex: reserva_historico)
```

---

## 3. Lacunas Identificadas e Hipóteses de Endpoints Faltantes

### 3.1 🔴 LACUNA CRÍTICA: "Quem registrou o acesso?" (email vs endpoint)
**Problema relatado:** O VDS envia email com informações de visitas/acessos contendo "QUEM REGISTROU" o acesso na portaria. Porém o endpoint `/evento_acesso` não retorna esse campo.

**Hipóteses (ordenadas por probabilidade):**

| Hipótese | Endpoint Provável | Raciocínio |
|---|---|---|
| **H1 (MAIS PROVÁVEL)** | `GET /evento_acesso/{uuid}` | A maioria dos recursos tem detalhe individual. `/evento_acesso` só tem endpoint de lista. O detalhe individual costuma trazer campos expandidos (operador/porteiro que registrou, autorização vinculada, observações da portaria) |
| **H2** | Campo existe na lista mas não óbvio | Rodar `/evento_acesso` com ?limit=1 e inspecionar TODOS os campos, inclusive aninhados tipo `operador`, `usuario`, `porteiro`, `registradoPor`, `criadoPor` |
| **H3** | `GET /portaria/operador` ou `/portaria/usuario` | Se quem registrou é uma entidade separada (funcionário/porteiro), a lista de operadores pode estar aqui; e no evento_acesso tem só o ID do operador |
| **H4** | `GET /funcionario` ou `/usuario` | Endpoints genéricos de usuários/funcionários do condomínio |
| **H5** | `/evento_acesso/relatorio` | Relatório traz mais campos que a lista padrão |

### 3.2 🟠 LACUNAS ALTAMENTE PROVÁVEIS (seguem padrão universal dos outros módulos)

| Categoria | Endpoints Faltantes (por padrão) | Justificativa |
|---|---|---|
| **Detalhe individual (GET /{recurso}/{uuid})** | `/evento_acesso/{uuid}` | ÚNICO recurso de lista principal SEM detalhe por UUID. Todos os outros tem (ocorrencia, entrega, veiculo, autorizacao_acesso) |
| | `/morador/{uuid}` | Morador só tem lista; padrão universal é ter detalhe individual |
| | `/bloco/{uuid}` | Bloco lista como Combo=true, deve ter detalhe completo |
| | `/unidade/{uuid}` | Unidade lista como Combo=true, deve ter detalhe completo |
| | `/boleto/{uuid}` | Boleto só tem lista, detalhe deve trazer PDF/code de barras/link de pagamento |
| | `/obra/{uuid}` | Obra só tem lista, detalhe individual deve existir |
| | `/area_comum/{uuid}` | Área comum só tem lista, detalhe com regras específicas |
| **Tabelas de domínio / Status** | `/evento_acesso/status` ou `/evento_acesso_tipo` | Todos os módulos tem "status" ou "tipo" (ex: autorizacao_acesso/status, autorizacao_acesso_tipo) |
| | `/entrega/status`, `/entrega/tipo` | Entrega tem ciclo de status; tipos de entrega (pacote, carta, encomenda) |
| | `/ocorrencia_tipo`, `/ocorrencia_status` | Tipos de ocorrência (Manutenção, Segurança, etc.) — filtro que pode existir |
| | `/morador_tipo`, `/morador_status` | Tipos: Proprietário, Locatário, Dependente |
| | `/veiculo_tipo`, `/veiculo_marca`, `/veiculo_modelo`, `/veiculo_cor` | Tabelas de domínio para cadastro de veículos |
| | `/obra_tipo`, `/obra_status` | Tipos e status de obras |
| | `/boleto_status` | Status: Pago, Em Aberto, Vencido |
| **Relatórios (padrão /autorizacao_acesso/relatorio)** | `/evento_acesso/relatorio` | Se existe relatório para autorizações, deve existir para acessos |
| | `/entrega/relatorio` | Relatório de entregas |
| | `/ocorrencia/relatorio` | Relatório de ocorrências/Fale Conosco |
| | `/reserva/relatorio` | Relatório de reservas de áreas comuns |
| | `/boleto/relatorio` | Extrato/relatório financeiro |
| | `/obra/relatorio` | Relatório de obras |
| **Histórico/Auditoria (padrão /reserva_historico)** | `/ocorrencia_historico/{uuid}` | Histórico de status/eventos da ocorrência (hoje pode estar no detalhe /ocorrencia/{uuid}) |
| | `/autorizacao_acesso_historico/{uuid}` | Histórico de USO da autorização (quantas vezes foi usada, quando, por quem) |
| | `/entrega_historico/{uuid}` | (Provavelmente já incluso em /entrega/{uuid}) |
| **Busca por documento (padrão /autorizacao_acesso/documento_pessoa)** | `/morador/documento_pessoa` | Buscar morador por CPF/RG — análogo ao que existe para autorizações |
| | `/veiculo/placa` ou `/veiculo/documento` | Buscar veículo por placa |
| | `/pessoa/busca` ou `/pessoa` | Busca genérica unificada de pessoas (morador + visitante + prestador) |

### 3.3 🟡 LACUNAS MODERADAS (conveniência para UI)

| Endpoint Provável | Justificativa |
|---|---|
| `/condominio` | Dados do condomínio. Hoje só vem dentro do `/perfil`. Deve ter endpoint próprio. |
| `/condominio/{uuid}` | Detalhes do condomínio |
| `/usuario` | Lista usuários cadastrados no sistema (funcionários, síndicos, conselheiros, operadores de portaria) |
| `/usuario/{uuid}` | Detalhe individual de usuário. **ESTE pode resolver a lacuna "quem registrou":** Se evento_acesso retorna um usuarioUuid operador, este endpoint traz os dados dele |
| `/funcionario` | Funcionários do condomínio (porteiro, zelador, etc.) — subset de /usuario |
| `/funcionario/{uuid}` | Detalhe de funcionário |
| `/portaria/usuario` ou `/portaria/operador` | Usuários com perfil de operador de portaria (específico do módulo) |
| `/portaria/movimento` | "Painel da portaria" — movimento em tempo real (hoje, talvez filtrando /evento_acesso com data=hoje) |
| `/anexo/{uuid}` | Detalhe de um anexo específico (hoje só tem POST para criar anexo, não GET para ler info) |
| `/portaria/visitante/tipo` | Tipos de visitante: Social, Prestador, Entregador, etc. |
| `/evento_acesso/tipo` | Tipos de evento: Entrada Pedestre, Saída Pedestre, Entrada Veículo, Saída Veículo |
| `/visita` ou `/visita_acesso` | Endpoint específico de "visita" (diferente de evento_acesso genérico) — se o email usa o termo "visita" |

### 3.4 🟢 Lacunas Menores (baixa probabilidade / pode não existir)

| Endpoint | Justificativa |
|---|---|
| `/visita/registrada_por` ou similar | Se o email usa termo "visita" específico, talvez tenha endpoint próprio |
| `/autorizacao_acesso/usos/{uuid}` | Histórico de uso da autorização |
| `/reserva/convite/{uuid}` | Se reservas também geram convites |
| `/boleto/pdf/{uuid}` ou `/boleto/pagamento/{uuid}` | Link para PDF ou pagamento |

---

## 4. Campos Específicos para Inspecionar no Retorno de `/evento_acesso`

Muitas vezes o dado existe mas está com nome não óbvio. Rodar um `/evento_acesso?limit=1&page=1` e procurar por:

```json
{
  // Campos a PROCURAR no retorno:
  "operador": {},         // Objeto do operador/porteiro
  "operadorUuid": "...",  // UUID referência
  "usuarioUuid": "...",   // UUID do usuário que registrou
  "criadoPor": "...",     // Padrão comum para auditoria
  "registradoPor": "...", // Nome literal
  "portariaUsuario": {},  // Objeto aninhado
  "porteiro": {},         // Nome comum
  "responsavel": {},      // Responsável pelo registro
  "autorizadoPor": {},    // Quem autorizou (se for o caso)
  
  // Dentro de objetos aninhados:
  "autorizacao": {
    "criadoPor": "...",    // Quem criou a autorização (morador)
    "confirmadoPor": "..." // Quem confirmou na portaria
  }
}
```

---

## 5. Resumo de Prioridade para Testes

**🔴 PRIORIDADE 0 (Resolver lacuna principal):**
1. `GET /evento_acesso/{uuid}` (detalhe individual)
2. Inspecionar retorno COMPLETO de `/evento_acesso?limit=1` (todos os campos)
3. `GET /usuario` e `GET /usuario/{uuid}`
4. `GET /portaria/operador` ou `GET /portaria/usuario`

**🟠 PRIORIDADE 1 (Muito prováveis, seguem padrões):**
5. `GET /evento_acesso/relatorio`
6. `GET /entrega/{uuid}` → VERIFICAR (já existe, mas confirmar se traz mais campos que a lista)
7. `GET /morador/{uuid}`, `/bloco/{uuid}`, `/unidade/{uuid}`, `/boleto/{uuid}`, `/obra/{uuid}`
8. Tabelas de domínio: `/evento_acesso_tipo`, `/entrega/status`, `/ocorrencia_tipo`

**🟡 PRIORIDADE 2 (Úteis se existirem):**
9. Outros relatórios: `/ocorrencia/relatorio`, `/reserva/relatorio`, `/entrega/relatorio`
10. `/condominio`, `/funcionario`, `/portaria/movimento`
11. Busca por documento: `/morador/documento_pessoa`, `/veiculo/placa`
