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
- **Obter Detalhes da Entrega por UUID:** `GET /entrega/{uuid}`
- **Marcar Ocorrência/Entrega como Visualizada:** `PUT /ocorrencia/visualizar/{uuid}`
- **Estrutura do Payload de Retorno do Detalhe (`GET /entrega/{uuid}`):**
  ```json
  {
    "uuid": "f362a58e-cfc7-446b-bc38-4b82d9d3d426",
    "protocolo": "259972",
    "identificador": "TBR403931961",
    "descricao": "Prezado ..., encontra-se na sala da correspondência ...",
    "foto": "/app/dados/cond/1441/ocorrencia/13f230c0-6004-4c17-8864-efbde0232bff.jpg",
    "dthora": "29/07/2026 17:36:32",
    "dtFim": "29/07/2026 18:21:47",
    "status": true,
    "tipo": { "uuid": "33", "nome": "Entrega", "nomeCompleto": "Entrega" },
    "destinoNome": "Bloco A - 1306",
    "unidade": { "uuid": "7b006c67-...", "nome": "1306", "bloco": { "uuid": "f0a6b46d-...", "nome": "Bloco A" } },
    "registradoPor": { "uuid": "ae295eaf-...", "nome": "Nome do Funcionário", "foto": "/app/dados/cond/1441/foto/PESSOA/p-1144363.jpeg" },
    "retiradoPor": { "uuid": "2f42d2a2-...", "nome": "Nome de Quem Retirou", "foto": "/app/dados/cond/1441/foto/PESSOA/f-1140167.jpg" },
    "anexos": [
      { "uuid": "15880747", "nome": "Foto_x.jpg", "arquivoNome": "13f230c0-...jpg", "url": "/app/dados/cond/1441/ocorrencia/...", "dthora": "29/07/2026 17:36:39" }
    ],
    "eventos": [
      { "uuid": "30041966", "status": { "uuid": "5", "nome": "Recebido" }, "registradoPor": { ... } },
      { "uuid": "30041993", "status": { "uuid": "3", "nome": "Encaminhado" }, "registradoPor": { ... } },
      { "uuid": "30042062", "status": { "uuid": "7", "nome": "Not. morador" }, "registradoPor": { ... } },
      { "uuid": "30045468", "status": { "uuid": "8", "nome": "Ent. morador" }, "registradoPor": { ... }, "retiradoPor": { ... } }
    ]
  }
  ```

### D. Estrutura Física
- **Listar Blocos:** `GET /bloco?Combo=True&IsAdmin=false`
- **Listar Unidades:** `GET /unidade?Combo=True&bloco.uuid={blocoUuid}`

### E. Financeiro e Boletos
- **Listar Boletos por Unidade:** `GET /boleto?page=1&limit=20&sortBy=status&order=asc&Ano={Ano}&Bloco.Uuid={blocoUuid}&Unidade.Uuid={unidadeUuid}`
  - **Estrutura do Payload de Retorno:**
    ```json
    {
      "totalRegs": 8,
      "page": 1,
      "limit": 0,
      "regs": [
        {
          "uuid": "49096814",
          "nomeSacado": "NOME DO MORADOR",
          "dtVencimento": "2026-01-10",
          "valor": 552.57,
          "status": "Liquidado",
          "descricao": "Taxa Condominial",
          "msgReserva": null,
          "urlSegundaVia": "https://solucoesdf.superlogica.net/clients/areadocondomino/publico/cobranca/c/49096814-6f020be4485ef84d6a7685ebd9aa4162128592de-200-FaturaHtml-flSegundaVia",
          "tipo": "Unidade",
          "dtReferencia": "2026-01-10",
          "fonte": "SL"
        }
      ]
    }
    ```

#### Pipeline de Auditoria e Detecção de Multa / Regimento Interno (RI) em Faturas:
Para verificar se uma infração/multa ou penalidade do Regimento Interno foi efetivamente cobrada no boleto da unidade:

1. **Nível 1 (Análise Primária no JSON):**
   - Inspecionar os campos `descricao` e `msgReserva` no objeto do boleto.
   - Caso contenha palavras-chave (`Multa`, `Infração`, `Regimento Interno`, `RI`, `Advertência`, `Penalidade`), sinalizar diretamente como cobrança disciplinar.

2. **Nível 2 (Inspecionar Espelho HTML - Superlógica `FaturaHtml`):**
   - O campo `urlSegundaVia` fornece o link público tokenizado do Superlógica (`-FaturaHtml-flSegundaVia`).
   - Efetuar uma requisição `HTTP GET` pública na `urlSegundaVia` (sem necessity do header `Authorization` da VDS).
   - Parsear a resposta HTML e analisar os itens discriminados da fatura (ex: tabela de composição do boleto).
   - Regex de busca por termos disciplinares: `/(multa|infra[çc][ãa]o|regimento\s*interno|\bri\b|penalidade|artigo)/i`.

3. **Nível 3 (Parsing de PDF - Fallback):**
   - Se a `urlSegundaVia` redirecionar para um arquivo PDF ou se a requisição retornar `Content-Type: application/pdf`, capturar o stream binário.
   - Processar a extração de texto do PDF via biblioteca de parser (`smalot/pdfparser` ou `pdftotext`).
   - Aplicar a mesma busca por regex nos itens da fatura discriminada para extrair o valor e a justificativa da multa lançada.
