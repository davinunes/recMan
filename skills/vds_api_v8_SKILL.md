---
name: integração_api_vds_v8
description: Especificação técnica completa de endpoints REST da API v8 do Vida de Síndico (vds.app.br / apiv8.vds.app.br) para autenticação, perfis, ocorrências (com busca por protocolo, comentários e fluxo de 3 etapas para anexar arquivos via POST /upload, POST /ocorrencia/comentario e POST /anexo), portaria, eventos de acesso, autorizações de acesso (CRUD, QR Codes, convites sociais, aprovação e relatórios), obras, entregas, boletos e reservas.
---

# Skill: Integração com API v8 (Vida de Síndico / apiv8.vds.app.br)

Esta skill fornece todas as diretrizes, endpoints, headers, payloads e especificações da documentação OpenAPI v8 (Swagger oficial + inspeções de rede) para comunicação direta backend-to-backend com a plataforma Vida de Síndico.

## 1. Base URL e Autenticação

- **Base URL:** `https://apiv8.vds.app.br`
- **Swagger JSON:** `https://apiv8.vds.app.br/swagger/public/swagger.json`
- **Origin Header:** `https://app1.vidadesindico.com.br`
- **Padrão de Autenticação:** Bearer Token JWT/JWE no header `Authorization`.

### Estratégia de Tokens
1. **Token do Usuário (Conselheiro / Morador):** Utilizado para a **Visão Prática** (consultar ocorrências não lidas com `Lida=0`), marcação de leitura, criação de reservas, autorizações de acesso e postagem de comentários/anexos em ocorrências.
2. **Token do Sistema (Condomínio):** Utilizado para **Sincronizações Globais/Automáticas** (consultar todas as ocorrências com `Lida=9`).

---

## 2. Endpoints Mapeados por Módulo

### A. Autenticação, Usuário & Perfil
- **Obter Token Anônimo (Boot Captcha):** `POST /auth/anon` (Body Schema: `AnonTokenRequest` - `{ captchaToken, condominioUuid }`)
- **Login de Usuário:** `POST /login` (Body Schema: `LoginForm` - `{ username, password, app, crypt }`)
- **Renovar Token (Refresh):** `POST /login/refresh` (Body Schema: `LoginRefresh` - `{ token, refreshToken, condominioId, crypt }`)
- **Dados do Perfil Autenticado:** `GET /perfil` (Retorna `usuario`, `condominio` e `papel`)
- **Tipos de Documento de Identificação:** `GET /pessoa_documento_tipo?Condominio.Uuid={condominioUuid}`

### B. Ocorrências, Fale Com & Fluxo de Anexos em 3 Etapas
- **Listar Não Lidos (Modo Prático - Token Usuário):** `GET /ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0`
- **Listar Todas / Sincronização Global (Token Sistema):** `GET /ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=desc&Caixa=0&Lida=9`
- **Buscar por Número do Protocolo:** `GET /ocorrencia?page=1&limit=20&sortBy=dtExibicao&order=desc&Lida=9&Protocolo={numeroProtocolo}&Caixa=0`
- **Detalhes da Ocorrência:** `GET /ocorrencia/{ocorrenciaUuid}`
- **Alternar Estado de Leitura (Toggle Lido/Não Lido):** `PUT /ocorrencia/leitura/{ocorrenciaUuid}`
- **Marcar Ocorrência como Visualizada:** `PUT /ocorrencia/visualizar/{ocorrenciaUuid}`

#### 📌 Fluxo Completo de Anexo de Arquivos em Ocorrências (3 Etapas Sequenciais):

1. **Passo 1: Upload Temporário (Staging):** `POST /upload`
   - **Body:** `{"base64String": "data:image/jpeg;base64,..."}`
   - **Response de Sucesso:**
     ```json
     {
       "success": true,
       "message": "Arquivo salvo com sucesso",
       "url": "app\\dados\\tmp\\1d55b4af-a8d7-4b20-a4a0-d807f1dc7e6f.jpeg"
     }
     ```
   - Extrair o nome do arquivo gerado no diretório temporário: `1d55b4af-a8d7-4b20-a4a0-d807f1dc7e6f.jpeg`.

2. **Passo 2: Publicar o Comentário/Evento na Ocorrência:** `POST /ocorrencia/comentario?condominioUuid={condominioUuid}`
   - **Body:**
     ```json
     {
       "uuid": "{ocorrenciaUuid}",
       "mensagem": "Texto do comentário ou resposta",
       "ocorrenciaPaiId": 51970481
     }
     ```
   - **Response de Sucesso:**
     ```json
     {
       "ocorrenciaId": 52075990
     }
     ```
   - Capturar o `ocorrenciaId` numérico gerado para o novo evento de comentário (`52075990`).

3. **Passo 3: Efetuar a Vinculação do Anexo:** `POST /anexo`
   - **Body:**
     ```json
     {
       "anexoCaminho": "1d55b4af-a8d7-4b20-a4a0-d807f1dc7e6f.jpeg*WhatsApp Image 2026-07-30 at 13.00.47.jpeg",
       "tipoId": "35",
       "destinoUuid": "52075990",
       "cortarQuadrado": true
     }
     ```
   - **Regra do Campo `anexoCaminho`**: Formato `{nome_temp_passo1}*{nome_original_arquivo}`.
   - **Regra do Campo `destinoUuid`**: Deve ser a string do ID numérico `ocorrenciaId` capturado no Passo 2.
   - **Response de Sucesso:** `{"message": "Anexos salvo com sucesso!"}`

### C. Gestão Completa de Autorização de Acesso, QR Codes & Convites Sociais
- **Listar Autorizações (Paginado):** `GET /autorizacao_acesso?page=1&limit=20&sortBy=nome&order=asc&Bloco.Uuid={blocoUuid}&Unidade.Uuid={unidadeUuid}&dtIni={YYYY-MM-DD}&dtFim={YYYY-MM-DD}`
- **Criar Autorização de Acesso:** `POST /autorizacao_acesso` (Body Schema: `AutorizacaoAcessoForm`)
- **Obter Detalhes da Autorização por UUID:** `GET /autorizacao_acesso/{uuid}`
- **Atualizar Autorização de Acesso:** `PUT /autorizacao_acesso/{uuid}`
- **Excluir Autorização de Acesso:** `DELETE /autorizacao_acesso/{uuid}`
- **Obter Autorização por Chave de Convite:** `GET /autorizacao_acesso/convite/{chave}`
- **Obter Autorização por UUID da Reserva:** `GET /autorizacao_acesso/reserva/{uuid}`
- **Listar Tipos de Convite Social Disponíveis:** `GET /autorizacao_acesso_convite`
- **Buscar Pessoa por Documento:** `GET /autorizacao_acesso/documento_pessoa`
- **Listar Vagas Disponíveis para Reserva por Tipo:** `GET /autorizacao_acesso/reserva_vaga?Tipo={tipoInt}`
- **Listar Status Possíveis de Autorização:** `GET /autorizacao_acesso/status`
- **Remover Pessoa da Lista de Autorização:** `DELETE /autorizacao_acesso/lista/{uuid}`
- **Validar Autorização de Acesso:** `GET /autorizacao_acesso/validade`
- **Salvar Visitante Convidado (Criar/Atualizar):** `POST /autorizacao_acesso/convite` (Body Schema: `ConviteAutorizacaoAcessoForm`)
- **Consultar Convite por Documento do Visitante:** `GET /autorizacao_acesso/convite/visitante`
- **Confirmar Uso de Convite Social (Portaria):** `PUT /autorizacao_acesso/{uuid}/confirmar_convite`
- **Gerar Relatório de Autorizações:** `GET /autorizacao_acesso/relatorio`
- **Listar Tipos de Autorização:** `GET /autorizacao_acesso_tipo`
- **Obter Período Limite por Tipo:** `GET /autorizacao_acesso_tipo/periodo_limite`
- **Obter Mensagem Informativa de Período:** `GET /autorizacao_acesso_tipo/mensagem_periodo`
- **Gerar QR Code de Convite:** `POST /autorizacao_acesso_qrcode/gerar`
- **Gerar Convite Social:** `POST /autorizacao_acesso_convite/gerar`
- **Revogar QR Code:** `POST /autorizacao_acesso_qrcode/revogar` (Body Schema: `QRCodeAutorizacaoAcessoForm`)
- **Revogar Convite Social:** `POST /autorizacao_acesso_convite/revogar` (Body Schema: `QRCodeAutorizacaoAcessoForm`)
- **Aprovar Convite Social Pendente:** `POST /autorizacao_acesso_convite/aprovar`
- **Aprovar QR Code Pendente:** `POST /autorizacao_acesso_qrcode/aprovar`
- **Notificar Detalhes por E-mail:** `POST /autorizacao_acesso/notifica_email` (Body Schema: `AutorizacaoAcessoNotificaEmailForm`)
- **Saldo de Convites Sociais Disponíveis:** `GET /autorizacao_acesso_convite/quantidade`

### D. Eventos de Acesso & Entregas
- **Listar Acessos por Período e Unidade:** `GET /evento_acesso?page=1&limit=21&sortBy=dthora&order=desc&dtInicio={dtInicio}&dtFim={dtFim}&unidade.uuid={unidadeUuid}`
- **Listar Entregas por Unidade:** `GET /entrega?page=1&limit=21&sortBy=dthora&order=desc&Unidade.Uuid={unidadeUuid}`
- **Detalhes da Entrega:** `GET /entrega/{uuid}`

### E. Estrutura Física, Moradores, Veículos & Obras
- **Listar Blocos:** `GET /bloco?Combo=True&IsAdmin=false`
- **Listar Unidades por Bloco:** `GET /unidade?Combo=True&bloco.uuid={blocoUuid}`
- **Listar Moradores por Unidade:** `GET /morador?Unidade.Uuid={unidadeUuid}&Combo=true`
- **Listar Veículos da Unidade:** `GET /veiculo?Unidade.Uuid={unidadeUuid}&order=asc`
- **Listar Obras da Unidade/Bloco:** `GET /obra?Unidade.Uuid={unidadeUuid}&Bloco.Uuid={blocoUuid}`


### F. Financeiro & Boletos
- **Listar Boletos por Unidade:** `GET /boleto?page=1&limit=20&sortBy=status&order=asc&Ano={Ano}&Bloco.Uuid={blocoUuid}&Unidade.Uuid={unidadeUuid}`

### G. Áreas Comuns, Reservas & Fila de Espera
- **Listar Áreas Comuns (Recursos):** `GET /area_comum?Bloco.Uuid={blocoUuid}&Condominio.Uuid={condominioUuid}&Convite=False&Ativo=True&AgrupadorMenu=1`
- **Consultar Calendário de Ocupação:** `GET /reserva_calendario?recursoUuid={recursoUuid}`
- **Consultar Horários Disponíveis:** `GET /reserva_horario?Recurso.Uuid={recursoUuid}&tipoPeriodo=H&data={dtReserva}`
- **Consultar Termo de Aceite:** `GET /reserva_termo?Morador.Uuid={moradorUuid}&Recurso.Uuid={recursoUuid}&DtReserva={dtReserva}`
- **Consultar Opcionais da Área Comum:** `GET /area_comum_opcional?DtIni={dtReserva}&DtFim={dtReserva}&Recurso.Uuid={recursoUuid}`
- **Listar Reservas (Filtros por Recurso, Data e Status):** `GET /reserva?Recurso.Uuid={recursoUuid}&DtIni={dtIni}&DtFim={dtFim}&Agendamento=false&ReservaCalendario=true`
- **Consultar Histórico da Reserva:** `GET /reserva_historico/{uuid}`
- **Realizar Reserva ou Entrar na Fila de Espera (POST Único):** `POST /reserva`
