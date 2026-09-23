# Raciocínio: Mapeamento de Novos Endpoints de Chamados VDS (API v8)

**Data:** 2026-09-23  
**Tópico:** Mapeamento de endpoints backend para encaminhar ocorrências, listar funcionários/grupos, consultar tipos/status e alterar status/fechar chamado na VDS API v8.

---

## 1. Contexto & Pedido do Usuário
O usuário forneceu os comandos `cURL` reais capturados na inspeção da rede do sistema Vida de Síndico (VDS `apiv8.vds.app.br`) contendo 6 novos endpoints:
1. `GET /ocorrencia/encaminhar/funcionarios?condominioUuid={}&ocorrenciaUuid={}`
2. `GET /ocorrencia/encaminhar/grupos?condominioUuid={}&ocorrenciaUuid={}`
3. `POST /ocorrencia/encaminhar` (encaminhar para Grupo `destinoTipo: 'G'` ou Funcionário `destinoTipo: 'F'`)
4. `GET /ocorrencia_tipo?Ativo=true&OcoTipo={}&OcoPai={}`
5. `GET /ocorrencia/status?grupo={}`
6. `PUT /ocorrencia/{ocorrenciaUuid}/classificacao?condominioUuid={}` (alteração de status / fechar chamado)

**Diretriz do Usuário:** Implementar as funções no backend (`classes/vds_ocorrencia_service.php`), documentar na skill `vds_api_v8` e **NÃO criar UI no frontend** nesta etapa.

---

## 2. Análise dos Endpoints & Schemas

### 2.1 Listar Funcionários para Encaminhamento
- **URL:** `GET https://apiv8.vds.app.br/ocorrencia/encaminhar/funcionarios`
- **Query Params:** `condominioUuid` (String), `ocorrenciaUuid` (String)
- **Headers:** `Authorization: Bearer <token>`, `Origin: https://app1.vidadesindico.com.br`
- **Retorno Esperado:**
  ```json
  {
    "totalRegs": 168,
    "page": 1,
    "limit": 100000,
    "regs": [
      {
        "uuid": "1e428f2c-67d4-4a9a-ad5d-71ff971aac40",
        "nome": "Adalmevir Carneiro da Silva",
        "foto": "/app/dados/cond/1441/foto/PESSOA/f-1144952.jpg",
        "tipo": {
          "uuid": "90190158-916B-4FEC-9C26-580A0E333F30",
          "nome": "Zelador",
          "nivel": 40,
          "grupo": { "uuid": "11", "nome": "Funcionários", "tipoGrupo": "FUN" }
        }
      }
    ]
  }
  ```

### 2.2 Listar Grupos para Encaminhamento
- **URL:** `GET https://apiv8.vds.app.br/ocorrencia/encaminhar/grupos`
- **Query Params:** `condominioUuid` (String), `ocorrenciaUuid` (String)
- **Headers:** `Authorization: Bearer <token>`, `Origin: https://app1.vidadesindico.com.br`
- **Retorno Esperado:** Array de objetos
  ```json
  [
    {
      "ocoTipo": 114,
      "nome": "Livro de ocorrência",
      "icone": "/app/images/ocorrencia_n/Anotacao_Sugestao.png",
      "descricao": "Escreva no livro de ocorrências do condomínio."
    }
  ]
  ```

### 2.3 Encaminhar Ocorrência (Grupo ou Funcionário)
- **URL:** `POST https://apiv8.vds.app.br/ocorrencia/encaminhar`
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`, `Origin: https://app1.vidadesindico.com.br`
- **Payload (Grupo):**
  ```json
  {
    "uuid": "5a72d216-79a4-485a-868d-97e4c0ee785d",
    "condominioUuid": "7AD5A317-7494-429C-9A9C-BBA69BA1A6BD",
    "tipo": "E",
    "destinoTipo": "G",
    "destinoId": 114,
    "dataPrevista": null,
    "comentario": "Prezados..."
  }
  ```
- **Payload (Funcionário):**
  ```json
  {
    "uuid": "d68adfd7-6193-4558-8c2b-8f745e583398",
    "condominioUuid": "7AD5A317-7494-429C-9A9C-BBA69BA1A6BD",
    "tipo": "E",
    "destinoTipo": "F",
    "destinoId": 0,
    "destinoUuid": "3f3a8cfd-4908-481a-8be7-77fa98c3b2be",
    "dataPrevista": null,
    "comentario": null
  }
  ```
- **Retorno Esperado:** `{"ocorrenciaId": 54860421, "msg": "Encaminhado com sucesso!"}`

### 2.4 Listar Tipos de Ocorrência (Categorias/Classificação)
- **URL:** `GET https://apiv8.vds.app.br/ocorrencia_tipo`
- **Query Params:** `Ativo=true`, `OcoTipo={grupoId}`, `OcoPai={grupoId}`
- **Retorno Esperado:** Array `[ { "nomeCompleto": "...", "logo": "...", "ocoNivelUm": 188, "ocoNivelUmNome": "...", "uuid": "236", "nome": "Acompanhamento" } ]`

### 2.5 Listar Status Possíveis de um Grupo
- **URL:** `GET https://apiv8.vds.app.br/ocorrencia/status`
- **Query Params:** `grupo={grupoId}`
- **Retorno Esperado:** Array `[ { "statusId": 23, "nome": "Aberto" }, { "statusId": 25, "nome": "Fechado" } ]`

### 2.6 Classificar Ocorrência / Alterar Status (Fechar Chamado)
- **URL:** `PUT https://apiv8.vds.app.br/ocorrencia/{ocorrenciaUuid}/classificacao?condominioUuid={condominioUuid}`
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`, `Origin: https://app1.vidadesindico.com.br`
- **Payload:**
  ```json
  {
    "classificacaoTipo": 230,
    "statusId": 25,
    "prioridade": 0
  }
  ```
- **Observação importante:** Campos `classificacaoTipo` e `prioridade` podem ser `null` se apenas o status estiver sendo atualizado (ex: fechar chamado com `statusId = 25`).
- **Retorno Esperado:** `true`

---

## 3. Decisões de Arquitetura & Implementação Backend
- Adicionar helper `vds_get_default_condominio_uuid()` para garantir o fallback do UUID do condomínio quando não for explicitamente fornecido.
- Adicionar as 6 novas funções com tratamento de erro (status HTTP, expiração de token com `vds_mark_token_expired`, fallback para token do sistema se token do conselheiro falhar) em `classes/vds_ocorrencia_service.php`.
- Atualizar a skill `.agents/skills/vds_api_v8/SKILL.md` adicionando a especificação detalhada dos 6 endpoints na seção "B. Ocorrências, Fale Com & Fluxo de Anexos em 3 Etapas".
