# Registro de Raciocínio: Enriquecimento da Coleção Postman via Swagger OpenAPI v8

- **Data**: 2026-07-30
- **Tópico**: Mapeamento da documentação pública Swagger (`https://apiv8.vds.app.br/swagger/public/swagger.json`) e super-enriquecimento da coleção Postman (`docs/vds_api_v8_postman_collection.json`).

---

## 1. Contexto e Descoberta

Através da consulta à URL pública do Swagger da API v8 do Vida de Síndico (`/swagger/public/swagger.json`), obtivemos a especificação OpenAPI 3.0.1 oficial do sistema, contendo **39 endpoints** e **60 schemas (modelos DTO/Form)**.

A coleção Postman existente contava com 30 requisições, onde várias rotas (como Ocorrências, Entregas, Boletos) foram mapeadas via inspeção de dev tools no navegador, enquanto endpoints cruciais de Gestão de Autorização de Acesso (CRUD de convidados, geração/revogação/aprovação de QR Codes e convites sociais, relatórios, obras e perfil do usuário) ainda não constavam na coleção.

---

## 2. Comparativo Swagger x Postman Atual

### 2.1 Coincidências e Enriquecimento das Rotas Existentes
9 rotas já existiam na coleção e serão enriquecidas com descrições oficiais, parâmetros de query detalhados, schemas JSON completos no corpo e exemplos:
1. `POST /auth/anon` -> Schema `AnonTokenRequest` (`captchaToken`, `condominioUuid`).
2. `POST /login` -> Schema `LoginForm` (`username`, `password`, `app`, `crypt`).
3. `POST /login/refresh` -> Schema `LoginRefresh` (`token`, `refreshToken`, `condominioId`, `crypt`).
4. `GET /bloco` -> Parâmetros oficiais (`Nome`, `Uuid`, `Condominio.Uuid`, `ControleAcesso`, `Combo`, `Tipo`).
5. `GET /unidade` -> Parâmetros oficiais (`Condominio.Uuid`, `Combo`, `ControleAcesso`, `IncluirAreasComuns`, `Uuid`, `Nome`, `Bloco.Uuid`).
6. `GET /morador` -> Parâmetros oficiais (`Status`, `Unidade.Uuid`, `Unidade.Nome`, `Unidade.Bloco.Uuid`, `Pessoa.Nome`, `Pessoa.Uuid`, `Combo`, `Entrega`, `AtzCadastral`, `page`, `limit`, `sortBy`, `order`).
7. `GET /area_comum` -> Parâmetros oficiais (`Bloco.Uuid`, `Condominio.Uuid`, `Convite`, `Atativo`, `AgrupadorMenu`).
8. `GET /reserva` -> Parâmetros oficiais (`DtIni`, `DtFim`, `Unidade.Uuid`, `Status`, `ReservaCalendario`, `Agendamento`, `Emprestimo`, `OcultarInterditado`, `Recurso.Uuid`, `EmprestimoStatus`, `Ativo`, `page`, `limit`).
9. `GET /autorizacao_acesso` -> Parâmetros oficiais (`page`, `limit`, `sortBy`, `order`, `Bloco.Uuid`, `Unidade.Uuid`, `dtIni`, `dtFim`).

### 2.2 Rotas Exclusivas do Swagger (30 Novos Endpoints)
Adicionaremos novos subgrupos na coleção Postman para cobrir 100% da API OpenAPI pública:
- **Perfil & Usuário**:
  - `GET /perfil` (Perfil do Usuário Autenticado, Condomínio e Papel)
  - `GET /pessoa_documento_tipo` (Tipos de documento de identificação cadastrados)
- **Gestão de Obras**:
  - `GET /obra` (Listagem paginada de obras de unidades)
- **Gestão Completa de Autorização de Acesso & Convites (27 rotas)**:
  - `POST /autorizacao_acesso` (Criar Autorização de Acesso com schema `AutorizacaoAcessoForm`)
  - `GET /autorizacao_acesso/{uuid}` (Detalhes da autorização)
  - `PUT /autorizacao_acesso/{uuid}` (Atualizar autorização)
  - `DELETE /autorizacao_acesso/{uuid}` (Excluir autorização)
  - `GET /autorizacao_acesso/convite/{chave}` (Obter por chave do convite)
  - `GET /autorizacao_acesso/reserva/{uuid}` (Obter por reserva)
  - `GET /autorizacao_acesso_convite` (Tipos de convites sociais)
  - `GET /autorizacao_acesso/documento_pessoa` (Buscar pessoa por documento)
  - `GET /autorizacao_acesso/reserva_vaga` (Vagas disponíveis)
  - `GET /autorizacao_acesso/status` (Status disponíveis)
  - `DELETE /autorizacao_acesso/lista/{uuid}` (Remover visitante da lista)
  - `GET /autorizacao_acesso/validade` (Verificar validade)
  - `POST /autorizacao_acesso/convite` (Cadastrar visitante no convite)
  - `GET /autorizacao_acesso/convite/visitante` (Consultar convite por documento)
  - `PUT /autorizacao_acesso/{uuid}/confirmar_convite` (Confirmar uso do convite)
  - `GET /autorizacao_acesso/relatorio` (Relatórios em PDF/Grid)
  - `GET /autorizacao_acesso_tipo` (Tipos de acesso)
  - `GET /autorizacao_acesso_tipo/periodo_limite` (Período limite)
  - `GET /autorizacao_acesso_tipo/mensagem_periodo` (Mensagem informativa)
  - `POST /autorizacao_acesso_qrcode/gerar` (Gerar QR Code)
  - `POST /autorizacao_acesso_convite/gerar` (Gerar convite social)
  - `POST /autorizacao_acesso_qrcode/revogar` (Revogar QR Code)
  - `POST /autorizacao_acesso_convite/revogar` (Revogar convite social)
  - `POST /autorizacao_acesso_convite/aprovar` (Aprovar convite)
  - `POST /autorizacao_acesso_qrcode/aprovar` (Aprovar QR Code)
  - `POST /autorizacao_acesso/notifica_email` (Enviar comprovante por e-mail)
  - `GET /autorizacao_acesso_convite/quantidade` (Saldo de convites sociais disponíveis)

---

## 3. Estratégia de Organização da Coleção Postman

A coleção será organizada estruturadamente em **7 pastas principais**:
1. `1. Autenticação & Sessão` (mantendo os scripts de teste automático de captura de `anonToken` e `bearerToken`).
2. `2. Usuário, Perfil & Documentos` (Perfil do usuário, tipos de documentos).
3. `3. Ocorrências e Fale Com` (Rotas híbridas inspecionadas via navegador + upload).
4. `4. Portaria, Entregas & Eventos de Acesso` (Eventos, entregas, autorizações).
5. `5. Autorizações de Acesso, Convites & QR Codes` (CRUD completo de convites, QR codes, relatórios, aprovações).
6. `6. Estrutura Física, Obras & Unidades` (Blocos, unidades, moradores, obras).
7. `7. Financeiro, Reservas & Áreas Comuns` (Boletos, áreas comuns, calendários, reservas, termos, adicionais).

---

## 4. Próximos Passos
1. Criar o script em Python que constrói a nova versão super-enriquecida do arquivo `vds_api_v8_postman_collection.json`.
2. Atualizar a skill de documentação (`skills/vds_api_v8/SKILL.md`) para registrar os novos endpoints e schemas.
3. Gerar o walkthrough e relatório de entrega.
