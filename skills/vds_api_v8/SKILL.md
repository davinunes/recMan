---
name: integração_api_vds_v8
description: Especificação técnica completa de endpoints REST da API v8 do Vida de Síndico (vds.app.br / apiv8.vds.app.br) para autenticação, ocorrências (com busca por protocolo), comentários/respostas, marcação de leitura (PUT /ocorrencia/leitura), portaria, eventos de acesso, entregas e boletos.
---

# Skill: Integração com API v8 (Vida de Síndico / apiv8.vds.app.br)

Esta skill fornece todas as diretrizes, endpoints, headers e payloads para comunicação direta backend-to-backend com a API v8 da plataforma Vida de Síndico.

## 1. Base URL e Autenticação

- **Base URL:** `https://apiv8.vds.app.br`
- **Origin Header:** `https://app1.vidadesindico.com.br`
- **Padrão de Autenticação:** Bearer Token JWT/JWE no header `Authorization`.

### Estratégia de Tokens
1. **Token do Usuário (Conselheiro):** Utilizado para a **Visão Prática** (consultar ocorrências não lidas com `Lida=0`) e marcação de leitura.
2. **Token do Sistema (Condomínio):** Utilizado para **Sincronizações Globais/Automáticas** (consultar todas as ocorrências com `Lida=9`).

---

## 2. Endpoints Mapeados por Módulo

### A. Ocorrências e Fale Com
- **Listar Não Lidos (Modo Prático - Token Usuário):** `GET /ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0`
- **Listar Todas / Sincronização Global (Token Sistema):** `GET /ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=desc&Caixa=0&Lida=9`
- **Buscar por Número do Protocolo:** `GET /ocorrencia?page=1&limit=20&sortBy=dtExibicao&order=desc&Lida=9&Protocolo={numeroProtocolo}&Caixa=0`
- **Detalhes da Ocorrência:** `GET /ocorrencia/{ocorrenciaUuid}`
  - Retorna a estrutura com array `eventos[]` contendo histórico completo de mensagens, fotos, remetente, cargo e anexos.
- **Publicar Resposta / Comentário em Ocorrência Existente:** `POST /ocorrencia/comentario`
  - Body Payload:
    ```json
    {
      "uuid": "{ocorrenciaUuid}",
      "mensagem": "Texto da resposta do Conselho",
      "ocorrenciaPaiId": 51970481
    }
    ```
- **Alternar Estado de Leitura (Toggle Lido/Não Lido):** `PUT /ocorrencia/leitura/{ocorrenciaUuid}`
  - Header: `Authorization: Bearer <token_usuario>`, `Content-Length: 0` (Corpo Vazio).
- **Marcar Ocorrência como Visualizada:** `PUT /ocorrencia/visualizar/{ocorrenciaUuid}`
  - Header: `Authorization: Bearer <token_usuario>`, `Content-Length: 0` (Corpo Vazio).
- **Ordens de Serviço:** `GET /ocorrencia/{ocorrenciaUuid}/ordem-servico`
- **Upload de Mídia / Anexos:** `POST /upload`
  - Body: `{"base64String": "data:image/png;base64,..."}`

### B. Eventos de Acesso e Portaria
- **Listar Acessos por Período:** `GET /evento_acesso?page=1&limit=21&sortBy=dthora&order=desc&dtInicio={YYYY-MM-DDTHH:mm}&dtFim={YYYY-MM-DDTHH:mm}`
- **Listar Acessos por Unidade:** `GET /evento_acesso?page=1&limit=21&sortBy=dthora&order=desc&dtInicio={dtInicio}&dtFim={dtFim}&unidade.bloco.uuid={blocoUuid}&unidade.uuid={unidadeUuid}`
- **Tipos de Eventos de Acesso:** `GET /evento_tipo`

### C. Entregas e Encomendas
- **Listar Entregas:** `GET /entrega?page=1&limit=21&sortBy=dthora&order=desc`
- **Listar Entregas por Unidade:** `GET /entrega?page=1&limit=21&sortBy=dthora&order=desc&Bloco.Uuid={blocoUuid}&Unidade.Uuid={unidadeUuid}`

### D. Estrutura Física
- **Listar Blocos:** `GET /bloco?Combo=True&IsAdmin=false`
- **Listar Unidades:** `GET /unidade?Combo=True&bloco.uuid={blocoUuid}`

### E. Financeiro e Boletos
- **Listar Boletos por Unidade:** `GET /boleto?page=1&limit=20&sortBy=status&order=asc&Ano={Ano}&Bloco.Uuid={blocoUuid}&Unidade.Uuid={unidadeUuid}`
