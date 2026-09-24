# Guia Prático de Integração: Módulo de Chamados & Ocorrências da VDS (API v8)

Este documento foi criado para servir como um **guia didático e conceitual** de como funciona a integração backend com a plataforma **Vida de Síndico (VDS)** (`apiv8.vds.app.br`).

Se você está desenvolvendo em qualquer linguagem (JavaScript/Node.js, Python, Go, PHP, C# ou Java), este guia explica o **fluxo real**, os motivos de cada chamada, a ordem das etapas, os atributos obrigatórios/opcionais e as armadilhas comuns.

---

## 🧭 Visão Geral da Arquitetura

A VDS expõe uma API RESTful moderna baseada em HTTP JSON, porém possui algumas particularidades importantes:

1. **Host Base:** `https://apiv8.vds.app.br`
2. **Header de Origem Obrigatório:** Todo request precisa incluir `Origin: https://app1.vidadesindico.com.br` (caso contrário a API pode rejeitar com CORS ou HTTP 403).
3. **Autenticação:** Baseada em `Authorization: Bearer <TOKEN_JWT>`.

```
           ┌─────────────────────────────────────────┐
           │        Seu Backend / Aplicação          │
           └────────────────────┬────────────────────┘
                                │
             1. Header: Authorization: Bearer <token>
             2. Header: Origin: https://app1.vidadesindico.com.br
             3. Header: Content-Type: application/json
                                │
                                ▼
           ┌─────────────────────────────────────────┐
           │        API VDS (apiv8.vds.app.br)       │
           └─────────────────────────────────────────┘
```

---

## 🔑 1. Autenticação & Estratégia de Tokens

Para conversar com a VDS, você precisa de um **Bearer Token**. No ecossistema do condomínio, existem dois tipos de tokens com propósitos bem diferentes:

### A. Token de Usuário (Corpo Diretivo ou Morador)
* **Quando usar:** Quando a ação é tomada em nome de uma pessoa física na interface (ex: ler chamados do conselho, encaminhar um chamado, enviar um comentário, marcar lido/não lido).
* **Vantagem:** Respeita as permissões do usuário logado na VDS.

### B. Token do Sistema (Condomínio / Robô)
* **Quando usar:** Para rotinas automatizadas em background (sincronização global, scripts agendados / crons).
* **Atributo chave do condomínio:** Toda operação global utiliza o `condominioUuid` (ex: `7AD5A317-7494-429C-9A9C-BBA69BA1A6BD`).

> 💡 **Tratamento de Token Expirado (HTTP 401):**  
> Tokens VDS expiram. Se qualquer requisição retornar `HTTP 401 Unauthorized`, sua aplicação deve marcar o token local como expirado, efetuar o refresh (`POST /login/refresh`) ou novo login (`POST /login`), e repetir a requisição.

---

## 📬 2. Leitura & Sincronização de Chamados

Na VDS, uma ocorrência possui dois estados principais de visualização: **Lida (`Lida=1`)** e **Não Lida (`Lida=0`)**. Além disso, `Caixa=0` indica a caixa principal de entrada.

### 2.1 Listar Chamados Não Lidos (Modo Prático / Atendimento)
Se a sua UI quer mostrar apenas o que exige atenção (pendentes de leitura pelo usuário):

* **Método & Endpoint:** `GET /ocorrencia`
* **Query Parameters:**
  - `page`: Número da página (começa em `1`)
  - `limit`: Quantidade de registros por página (ex: `20` ou `50`)
  - `sortBy`: Campo de ordenação (use `dtExibicao`)
  - `order`: Sentido da ordenação (`asc` para mais antigos primeiro ou `desc` para os recentes)
  - `Lida`: `0` (Filtra **apenas não lidos**)
  - `Caixa`: `0` (Caixa de entrada)

```http
GET /ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0 HTTP/1.1
Host: apiv8.vidadesindico.com.br
Authorization: Bearer <TOKEN>
Origin: https://app1.vidadesindico.com.br
```

### 2.2 Sincronização Global de Ocorrências (Modo Histórico / Sistema)
Para baixar todas as ocorrências do condomínio (lidas e não lidas):

* **Método & Endpoint:** `GET /ocorrencia`
* **Query Parameters:**
  - `page`: `1`
  - `limit`: `50`
  - `sortBy`: `dtExibicao`
  - `order`: `desc`
  - `Caixa`: `0`
  - `Lida`: `9` (O valor `9` na VDS funciona como curinga para trazer **TODAS**)
  - `Condominio.Uuid`: `{condominioUuid}` (Opcional, para limitar ao condomínio)

### 2.3 Buscar Chamado por Número de Protocolo
Se o morador ou usuário informou um protocolo exato (ex: `006763`):

* **Método & Endpoint:** `GET /ocorrencia?Protocolo=006763&Lida=9&Caixa=0&sortBy=dtExibicao&order=desc&page=1&limit=20`

### 2.4 Consultar Detalhes Completos de uma Ocorrência
Para abrir a conversa inteira, dados do morador, unidade e histórico de mensagens:

* **Método & Endpoint:** `GET /ocorrencia/{ocorrenciaUuid}`
* **Parâmetro de Path:** `{ocorrenciaUuid}` (UUID da ocorrência, ex: `5a72d216-79a4-485a-868d-97e4c0ee785d`)

---

## 👁️ 3. Controle de Leitura Remota (Lido / Não Lido)

Quando um usuário lê um chamado no seu sistema e você deseja atualizar o status remoto na VDS:

### Alternar Leitura (Toggle Lido / Não Lido)
* **Método & Endpoint:** `PUT /ocorrencia/leitura/{ocorrenciaUuid}`
* **Descrição:** Se o chamado estava Não Lido (`0`), ele passa a ser Lido (`1`). Se já estava Lido, volta para Não Lido.
* **Retorno:** Status HTTP `200 OK` com confirmação em JSON.

### Marcar como Visualizado
* **Método & Endpoint:** `PUT /ocorrencia/visualizar/{ocorrenciaUuid}`

---

## 💬 4. Envio de Mensagens e Anexos (O Fluxo das 3 Etapas)

Na VDS, **você NÃO envia o arquivo junto com o comentário em uma única chamada multipart/form-data**. Existe um fluxo em 3 etapas sequenciais que precisa ser respeitado rigorosamente:

```
┌─────────────────┐       ┌───────────────────────────────┐       ┌──────────────────────────────┐
│  Etapa 1: UPLOAD │  ──►  │ Etapa 2: CRIAR COMENTÁRIO     │  ──►  │ Etapa 3: VINCULAR ANEXO      │
│  POST /upload   │       │ POST /ocorrencia/comentario   │       │ POST /anexo                  │
└─────────────────┘       └───────────────────────────────┘       └──────────────────────────────┘
 (Gera arquivo temp)       (Gera ID numérico do evento)            (Junta o temp com o ID evento)
```

### Etapa 1: Upload Temporário do Arquivo (Staging)
Converte o arquivo em Base64 e envia para o diretório temporário do servidor da VDS.

* **Endpoint:** `POST /upload`
* **Body (JSON):**
  ```json
  {
    "base64String": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ..."
  }
  ```
* **Retorno da API:**
  ```json
  {
    "success": true,
    "message": "Arquivo salvo com sucesso",
    "url": "app\\dados\\tmp\\1d55b4af-a8d7-4b20-a4a0-d807f1dc7e6f.jpeg"
  }
  ```
* **O que guardar:** Extraia apenas o nome do arquivo gerado no temporário (`1d55b4af-a8d7-4b20-a4a0-d807f1dc7e6f.jpeg`).

### Etapa 2: Publicar o Comentário na Ocorrência
Cria a nova mensagem no histórico do chamado.

* **Endpoint:** `POST /ocorrencia/comentario?condominioUuid={condominioUuid}`
* **Body (JSON):**
  ```json
  {
    "uuid": "5a72d216-79a4-485a-868d-97e4c0ee785d",
    "mensagem": "Prezados, verifiquei a situação no local e tirei esta foto.",
    "ocorrenciaPaiId": 51970481
  }
  ```
  - `uuid` (obrigatório): UUID da ocorrência principal.
  - `mensagem` (obrigatório): Texto do comentário.
  - `ocorrenciaPaiId` (opcional): ID numérico da mensagem raiz.
* **Retorno da API:**
  ```json
  {
    "ocorrenciaId": 52075990
  }
  ```
* **O que guardar:** O `ocorrenciaId` retornado (neste exemplo, `52075990`). Ele representa o **ID numérico do evento/comentário que acabou de ser criado**.

### Etapa 3: Vincular o Anexo Temporário ao Comentário Criado
Faz a ligação entre o arquivo enviado na Etapa 1 e a mensagem criada na Etapa 2.

* **Endpoint:** `POST /anexo`
* **Body (JSON):**
  ```json
  {
    "anexoCaminho": "1d55b4af-a8d7-4b20-a4a0-d807f1dc7e6f.jpeg*foto_vazamento.jpeg",
    "tipoId": "35",
    "destinoUuid": "52075990",
    "cortarQuadrado": true
  }
  ```
* **Explicação dos Atributos:**
  - `anexoCaminho` (obrigatório): Sintaxe exata `{nome_arquivo_temporario_etapa_1}*{nome_original_do_arquivo}`.
  - `tipoId` (obrigatório): Identificador do tipo de anexo (`"35"` é o padrão para anexos gerais em ocorrências).
  - `destinoUuid` (obrigatório): **ATENÇÃO!** Deve ser a string do ID numérico `ocorrenciaId` retornado na Etapa 2 (ex: `"52075990"`), e **NÃO** o UUID da ocorrência!
  - `cortarQuadrado` (opcional): Boolean indicando se gera thumbnail quadrada.

---

## ↪️ 5. Encaminhamento de Chamados (Grupos & Funcionários)

Um chamado na VDS pode ser reencaminhado para um **Grupo/Setor** (ex: Manutenção, Limpeza, Portaria) ou diretamente para um **Funcionário específico** (ex: Zelador, Administrador).

### Etapa A: Descobrir para quem é possível encaminhar

#### 1. Listar Grupos / Setores Elegíveis
* **Endpoint:** `GET /ocorrencia/encaminhar/grupos?condominioUuid={condominioUuid}&ocorrenciaUuid={ocorrenciaUuid}`
* **Retorno da API:** Array de grupos disponíveis para este chamado.
  ```json
  [
    {
      "ocoTipo": 114,
      "nome": "Livro de ocorrência",
      "icone": "/app/images/ocorrencia_n/Anotacao_Sugestao.png",
      "descricao": "Escreva no livro de ocorrências do condomínio."
    },
    {
      "ocoTipo": 188,
      "nome": "Ordem de serviço",
      "icone": "/app/images/ocorrencia_n/Anotacao_Anotacao.png",
      "descricao": "Acompanhamento de ordens de serviço."
    }
  ]
  ```
  - `ocoTipo`: É o ID numérico do grupo (guarde este ID para encaminhar).

#### 2. Listar Funcionários Elegíveis
* **Endpoint:** `GET /ocorrencia/encaminhar/funcionarios?condominioUuid={condominioUuid}&ocorrenciaUuid={ocorrenciaUuid}`
* **Retorno da API:**
  ```json
  {
    "totalRegs": 168,
    "page": 1,
    "limit": 100000,
    "regs": [
      {
        "uuid": "3f3a8cfd-4908-481a-8be7-77fa98c3b2be",
        "nome": "Adalmevir Carneiro da Silva",
        "foto": "/app/dados/cond/1441/foto/PESSOA/f-1144952.jpg",
        "tipo": {
          "uuid": "90190158-916B-4FEC-9C26-580A0E333F30",
          "nome": "Zelador",
          "grupo": { "uuid": "11", "nome": "Funcionários", "tipoGrupo": "FUN" }
        }
      }
    ]
  }
  ```
  - `uuid`: UUID string do funcionário (guarde para o encaminhamento individual).

---

### Etapa B: Executar o Encaminhamento

* **Endpoint:** `POST /ocorrencia/encaminhar`

#### Opção 1: Encaminhar para um Grupo (`destinoTipo: "G"`)
```json
{
  "uuid": "5a72d216-79a4-485a-868d-97e4c0ee785d",
  "condominioUuid": "7AD5A317-7494-429C-9A9C-BBA69BA1A6BD",
  "tipo": "E",
  "destinoTipo": "G",
  "destinoId": 114,
  "dataPrevista": null,
  "comentario": "Prezados, encaminhando demanda para o setor responsável."
}
```

#### Opção 2: Encaminhar para um Funcionário (`destinoTipo: "F"`)
```json
{
  "uuid": "5a72d216-79a4-485a-868d-97e4c0ee785d",
  "condominioUuid": "7AD5A317-7494-429C-9A9C-BBA69BA1A6BD",
  "tipo": "E",
  "destinoTipo": "F",
  "destinoId": 0,
  "destinoUuid": "3f3a8cfd-4908-481a-8be7-77fa98c3b2be",
  "dataPrevista": "2026-10-05",
  "comentario": "Favor verificar vazamento no Bloco B."
}
```

#### Atributos do Payload de Encaminhamento:
| Atributo | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `uuid` | String | **Sim** | UUID da ocorrência que será encaminhada |
| `condominioUuid` | String | **Sim** | UUID do condomínio |
| `tipo` | String | **Sim** | Sempre `"E"` (indica operação de Encaminhar) |
| `destinoTipo` | String | **Sim** | `"G"` para Grupo / Setor ou `"F"` para Funcionário |
| `destinoId` | Int | **Sim** | Se `destinoTipo="G"`, passe o ID `ocoTipo` numérico do grupo (ex: `114`). Se `destinoTipo="F"`, passe `0`. |
| `destinoUuid` | String | Condicional | Obrigatório apenas se `destinoTipo="F"`. Passe o UUID do funcionário. |
| `comentario` | String | *Opcional* | Texto com instrução ou motivo do encaminhamento (ou `null`) |
| `dataPrevista` | String | *Opcional* | Data estimada de conclusão no formato `YYYY-MM-DD` (ou `null`) |

* **Retorno de Sucesso:**
  ```json
  {
    "ocorrenciaId": 54860421,
    "msg": "Encaminhado com sucesso!"
  }
  ```

---

## 🏷️ 6. Tipos, Status & Fechamento de Chamados

Na VDS, você pode alterar a **Classificação**, a **Prioridade** e o **Status** de um chamado (por exemplo, mudar de "Aberto" para "Fechado").

### 6.1 Listar Subtipos de Classificação
* **Endpoint:** `GET /ocorrencia_tipo?Ativo=true&OcoTipo={grupoId}&OcoPai={grupoId}`
* **Retorno:** Array com opções de classificação e seus nomes completos.

### 6.2 Listar Status Permitidos para um Grupo
Antes de alterar o status, consulte quais opções a VDS aceita para aquele grupo de chamado:

* **Endpoint:** `GET /ocorrencia/status?grupo={grupoId}` (ex: `grupo=188`)
* **Retorno:**
  ```json
  [
    { "statusId": 23, "nome": "Aberto" },
    { "statusId": 26, "nome": "Aguardando material" },
    { "statusId": 24, "nome": "Em andamento" },
    { "statusId": 25, "nome": "Fechado" }
  ]
  ```

---

### 6.3 Alterar Status ou Fechar o Chamado Remoto

Para concluir ou reclassificar um chamado na VDS:

* **Método & Endpoint:** `PUT /ocorrencia/{ocorrenciaUuid}/classificacao?condominioUuid={condominioUuid}`
* **Body (JSON):**
  ```json
  {
    "classificacaoTipo": 230,
    "statusId": 25,
    "prioridade": 0
  }
  ```

#### 📌 Dica Didática Importante (Campos Opcionais):
Nenhum dos 3 campos do JSON é estritamente obrigatório. Se você deseja **apenas fechar o chamado**, sem alterar a classificação existente nem a prioridade:

```json
{
  "classificacaoTipo": null,
  "statusId": 25,
  "prioridade": null
}
```

* **Retorno da API:** `true` (Booleano indicando sucesso).

---

## 📋 Tabela Resumo dos Endpoints

| Funcionalidade | Método | Endpoint |
|---|---|---|
| **Listar Não Lidos** | `GET` | `/ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0` |
| **Listar Todos (Sync)** | `GET` | `/ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=desc&Caixa=0&Lida=9` |
| **Buscar por Protocolo** | `GET` | `/ocorrencia?Protocolo={protocolo}&Lida=9&Caixa=0` |
| **Detalhes do Chamado** | `GET` | `/ocorrencia/{ocorrenciaUuid}` |
| **Toggle Lido / Não Lido** | `PUT` | `/ocorrencia/leitura/{ocorrenciaUuid}` |
| **Upload Temporário** | `POST` | `/upload` |
| **Criar Comentário** | `POST` | `/ocorrencia/comentario?condominioUuid={condominioUuid}` |
| **Vincular Anexo** | `POST` | `/anexo` |
| **Listar Grupos Elegíveis**| `GET` | `/ocorrencia/encaminhar/grupos?condominioUuid={}&ocorrenciaUuid={}` |
| **Listar Funcionários** | `GET` | `/ocorrencia/encaminhar/funcionarios?condominioUuid={}&ocorrenciaUuid={}` |
| **Encaminhar Chamado** | `POST` | `/ocorrencia/encaminhar` |
| **Listar Status Possíveis**| `GET` | `/ocorrencia/status?grupo={grupoId}` |
| **Classificar / Fechar** | `PUT` | `/ocorrencia/{ocorrenciaUuid}/classificacao?condominioUuid={condominioUuid}` |

---

## 🛡️ Checklist para o Desenvolvedor

- [ ] Certifique-se de incluir sempre o header `Origin: https://app1.vidadesindico.com.br`.
- [ ] Ao enviar arquivos, siga estritamente a sequência: **1. Upload -> 2. Comentário -> 3. Anexo**.
- [ ] No `POST /anexo`, passe no campo `destinoUuid` o ID numérico da etapa 2 (`ocorrenciaId`), e não o UUID da ocorrência.
- [ ] Para encaminhar para um **Grupo**, defina `destinoTipo="G"` e passe o `destinoId` numérico (`destinoUuid` fica ausente).
- [ ] Para encaminhar para um **Funcionário**, defina `destinoTipo="F"`, `destinoId=0` e passe o `destinoUuid`.
- [ ] Trate respostas HTTP 401 renovando o token Bearer automaticamente.
