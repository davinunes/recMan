# Plano de Testes Postman: Descoberta de Endpoints de Leitura VDS v8

**Data:** 2026-07-31
**Foco:** Somente endpoints de LEITURA (GET). Prioridade máxima: descobrir "quem registrou o acesso" em eventos/visitas.
**Base URL:** `https://apiv8.vds.app.br`
**Headers obrigatórios:**
- `Authorization: Bearer {{bearerToken}}`
- `Origin: https://app1.vidadesindico.com.br`

---

## 🔴 PRIORIDADE 0: Resolver a lacuna "quem registrou o acesso"

> Execute esses testes PRIMEIRO. O objetivo é encontrar qual endpoint retorna o operador/porteiro/usuário que efetuou o registro na portaria.

---

### Teste #0.1: Detalhe individual do evento de acesso (MAIS PROVÁVEL)

**Hipótese:** `/evento_acesso` tem apenas endpoint de lista. Como todos os outros recursos (ocorrencia, entrega, veiculo, autorizacao_acesso) têm `GET /{recurso}/{uuid}`, o detalhe individual de evento_acesso deve trazer campos expandidos incluindo "quem registrou".

**Request:**
```
GET {{baseUrl}}/evento_acesso/{{eventoAcessoUuid}}
```

**Como obter `eventoAcessoUuid`:**
1. Rode primeiro: `GET {{baseUrl}}/evento_acesso?page=1&limit=3&sortBy=dthora&order=desc&unidade.uuid={{unidadeUuid}}`
2. Copie qualquer `uuid` da lista (o primeiro item é o evento mais recente)
3. Use esse UUID no request acima

**O que PROCURAR no retorno:**
- Campos: `operador`, `operadorUuid`, `usuarioUuid`, `criadoPor`, `registradoPor`, `portariaUsuario`, `porteiro`, `responsavel`, `autorizadoPor`
- Objetos aninhados completos com dados do funcionário: nome, cargo, uuid

**Critério de sucesso:** Retorna HTTP 200 e o JSON tem dados expandidos além da lista (qualquer campo novo além dos da listagem já é vitória).

---

### Teste #0.2: Inspecionar retorno COMPLETO da listagem (campos escondidos)

**Hipótese:** O campo "quem registrou" JÁ existe na listagem mas está sendo ignorado por nome não óbvio.

**Request:**
```
GET {{baseUrl}}/evento_acesso?page=1&limit=1&sortBy=dthora&order=desc&unidade.uuid={{unidadeUuid}}
```

**No Postman:**
1. Vá para a aba "Body" da resposta
2. Copie TODO o JSON do primeiro item do array `data[0]` ou items[0]
3. Cole em um editor JSON e PROCURE por estas palavras-chave (Ctrl+F):
   - operador, usuario, criado, registrado, portaria, porteiro, responsavel, autorizado, Por
   - Procure também por UUIDs que você reconheça (de usuários que você sabe que são funcionários)

---

### Teste #0.3: Listar usuários do condomínio (operadores/porteiros)

**Hipótese:** Se evento_acesso retorna apenas um `usuarioUuid` operador, precisamos do endpoint `/usuario` para buscar os dados dele.

**Request A (lista genérica):**
```
GET {{baseUrl}}/usuario
```

**Request B (com filtros de página):**
```
GET {{baseUrl}}/usuario?page=1&limit=50&sortBy=nome&order=asc
```

**Request C (combo reduzido):**
```
GET {{baseUrl}}/usuario?Combo=true
```

**Request D (detalhe individual - se A funcionar):**
```
GET {{baseUrl}}/usuario/{{usuarioUuidQualquer}}
```

**O que esperar:** Lista de usuários do condomínio (conselheiros, síndico, funcionários, operadores de portaria, etc.)

---

### Teste #0.4: Endpoint específico de módulo portaria (operadores)

**Hipótese:** O namespace `/portaria` tem sub-recursos específicos de operadores/usuários da portaria (como tem `/portaria/visitante`).

**Requests para testar (todos GET):**
```
GET {{baseUrl}}/portaria/operador
GET {{baseUrl}}/portaria/usuario
GET {{baseUrl}}/portaria/funcionario
GET {{baseUrl}}/portaria/movimento
GET {{baseUrl}}/portaria/movimento?dtIni=2026-07-30&dtFim=2026-07-31
```

---

### Teste #0.5: Endpoint de funcionários

**Hipótese:** Existe entidade "funcionario" separada de usuário.

```
GET {{baseUrl}}/funcionario
GET {{baseUrl}}/funcionario?Combo=true
GET {{baseUrl}}/funcionario?page=1&limit=50&sortBy=nome&order=asc
```

---

### Teste #0.6: Relatório de eventos de acesso

**Hipótese:** Igual ao `/autorizacao_acesso/relatorio`. O relatório retorna MAIS campos que a listagem normal.

```
GET {{baseUrl}}/evento_acesso/relatorio?DtIni=2026-07-29&DtFim=2026-07-31&Unidade.Uuid={{unidadeUuid}}
```

**Variações para testar (dtIni vs DtIni vs dtInicio):**
```
GET {{baseUrl}}/evento_acesso/relatorio?dtIni=2026-07-29&dtFim=2026-07-31
GET {{baseUrl}}/evento_acesso/relatorio?dtInicio=2026-07-29&dtFim=2026-07-31
```

---

## 🟠 PRIORIDADE 1: Endpoints altamente prováveis (seguem padrão)

> Testar depois que a Prioridade 0 estiver completa. Todos seguem padrão UNIVERSAL dos outros módulos.

---

### 1.1 Detalhes individuais (GET /{recurso}/{uuid})

Para CADA um, primeiro pegue um UUID do endpoint de lista, depois teste o detalhe:

| Endpoint a testar | Como pegar UUID válido |
|---|---|
| `GET /morador/{{moradorUuid}}` | Do `/perfil` → `usuario.moradorUuid` OU do `/morador?Unidade.Uuid=` pegue um uuid |
| `GET /bloco/{{blocoUuid}}` | Do `/perfil` → `usuario.blocos[0]` OU `/bloco?Combo=True` |
| `GET /unidade/{{unidadeUuid}}` | Do `/perfil` → `usuario.unidades[0]` OU `/unidade?Combo=True&bloco.uuid=` |
| `GET /boleto/{{boletoUuid}}` | `/boleto?page=1&limit=1&Unidade.Uuid=` → pegue uuid |
| `GET /obra/{{obraUuid}}` | `/obra?page=1&limit=1` → pegue uuid |
| `GET /area_comum/{{areaComumUuid}}` | `/area_comum?Bloco.Uuid=` → pegue uuid |
| `GET /condominio/{{condominioUuid}}` | Do `/perfil` → `condominio.uuid` |

---

### 1.2 Tabelas de domínio / Status / Tipos

Padrão: todo módulo tem endpoint de status/tipo. Teste TODOS esses:

```
# Tipos e status de evento_acesso
GET {{baseUrl}}/evento_acesso_tipo
GET {{baseUrl}}/evento_acesso/status

# Tipos e status de entrega
GET {{baseUrl}}/entrega_tipo
GET {{baseUrl}}/entrega/status

# Tipos e status de ocorrência
GET {{baseUrl}}/ocorrencia_tipo
GET {{baseUrl}}/ocorrencia_status
GET {{baseUrl}}/ocorrencia_categoria
GET {{baseUrl}}/ocorrencia/classificacao

# Tipos e status de morador
GET {{baseUrl}}/morador_tipo
GET {{baseUrl}}/morador_status

# Tabelas de domínio de veículo
GET {{baseUrl}}/veiculo_tipo
GET {{baseUrl}}/veiculo_marca
GET {{baseUrl}}/veiculo_modelo
GET {{baseUrl}}/veiculo_cor

# Tipos e status de obra
GET {{baseUrl}}/obra_tipo
GET {{baseUrl}}/obra_status
GET {{baseUrl}}/obra_empresa

# Status de boleto
GET {{baseUrl}}/boleto_status

# Tipos de visitante
GET {{baseUrl}}/portaria/visitante_tipo
GET {{baseUrl}}/visitante_tipo
```

---

### 1.3 Outros Relatórios

Padrão: `/autorizacao_acesso/relatorio` existe. Teste relatórios para outros módulos:

```
GET {{baseUrl}}/entrega/relatorio?DtIni=2026-07-01&DtFim=2026-07-31&Unidade.Uuid={{unidadeUuid}}
GET {{baseUrl}}/ocorrencia/relatorio?DtIni=2026-07-01&DtFim=2026-07-31
GET {{baseUrl}}/reserva/relatorio?DtIni=2026-07-01&DtFim=2026-07-31
GET {{baseUrl}}/boleto/relatorio?Ano=2026&Bloco.Uuid={{blocoUuid}}
GET {{baseUrl}}/obra/relatorio?Bloco.Uuid={{blocoUuid}}
```

---

### 1.4 Históricos de Auditoria

Padrão: `/reserva_historico/{uuid}` existe. Teste para outros módulos:

```
# Histórico da ocorrência (pode já estar no detalhe /ocorrencia/{uuid})
GET {{baseUrl}}/ocorrencia_historico/{{ocorrenciaUuid}}

# Histórico de USO da autorização (quando a autorização foi usada na portaria)
GET {{baseUrl}}/autorizacao_acesso_historico/{{autorizacaoUuid}}
```

---

## 🟡 PRIORIDADE 2: Endpoints úteis / conveniência

---

### 2.1 Dados do condomínio (endpoint próprio)

```
GET {{baseUrl}}/condominio
GET {{baseUrl}}/condominio/{{condominioUuid}}   # UUID do /perfil → condominio.uuid
```

---

### 2.2 Busca por documento (padrão /autorizacao_acesso/documento_pessoa)

```
# Buscar morador por CPF/RG
GET {{baseUrl}}/morador/documento_pessoa?DocumentoTipo=cpf&Documento=12345678909
GET {{baseUrl}}/morador/documento?Documento=12345678909

# Buscar veículo por placa
GET {{baseUrl}}/veiculo/placa?Placa=ABC1D23
GET {{baseUrl}}/veiculo/placa?placa=ABC1D23

# Busca de PESSOA genérica (unifica morador + visitante + prestador)
GET {{baseUrl}}/pessoa
GET {{baseUrl}}/pessoa?Documento=12345678909
GET {{baseUrl}}/pessoa/busca?nome=joao
```

---

### 2.3 Anexos (detalhe por GET, já que só temos POST)

```
GET {{baseUrl}}/anexo/{{anexoUuid}}
```

---

### 2.4 Endpoint específico "visita" (se a palavra "visita" é usada no email)

Se o VDS envia email usando o termo "visita" (não "evento_acesso"), pode haver endpoint próprio:

```
GET {{baseUrl}}/visita?Unidade.Uuid={{unidadeUuid}}
GET {{baseUrl}}/visita?dtIni=2026-07-29&dtFim=2026-07-31
GET {{baseUrl}}/visita/{{visitaUuid}}
GET {{baseUrl}}/visita_acesso
```

---

### 2.5 Confirmação de uso de autorização (detalhe expandido)

Quando uma autorização é usada, podem existir mais dados:

```
GET {{baseUrl}}/autorizacao_acesso/usos/{{autorizacaoUuid}}
GET {{baseUrl}}/autorizacao_acesso/historico_uso/{{autorizacaoUuid}}
```

---

## 📋 Check-list de Execução

Copie esta tabela no Postman ou Excel para marcar os testes:

| # | Endpoint | Status | Observações | Campos retornados relevantes |
|---|---|---|---|---|
| 0.1 | `/evento_acesso/{uuid}` | ⬜ Pendente | | |
| 0.2 | Inspecionar `/evento_acesso?limit=1` | ⬜ Pendente | | |
| 0.3 | `/usuario` | ⬜ Pendente | | |
| 0.4a | `/portaria/operador` | ⬜ Pendente | | |
| 0.4b | `/portaria/usuario` | ⬜ Pendente | | |
| 0.4c | `/portaria/funcionario` | ⬜ Pendente | | |
| 0.4d | `/portaria/movimento` | ⬜ Pendente | | |
| 0.5 | `/funcionario` | ⬜ Pendente | | |
| 0.6 | `/evento_acesso/relatorio` | ⬜ Pendente | | |
| 1.1a | `/morador/{uuid}` | ⬜ Pendente | | |
| 1.1b | `/bloco/{uuid}` | ⬜ Pendente | | |
| 1.1c | `/unidade/{uuid}` | ⬜ Pendente | | |
| 1.1d | `/boleto/{uuid}` | ⬜ Pendente | | |
| 1.1e | `/obra/{uuid}` | ⬜ Pendente | | |
| 1.1f | `/area_comum/{uuid}` | ⬜ Pendente | | |
| 1.2 | (todos os _tipo / status) | ⬜ Pendente | | |
| 1.3 | (todos os /relatorio) | ⬜ Pendente | | |
| 1.4 | (todos os _historico) | ⬜ Pendente | | |
| 2.x | (prioridade 2) | ⬜ Pendente | | |

**Legenda Status:**
- ✅ 200 OK / retornou dados
- ❌ 404 / não existe
- 🟡 200 OK mas sem dados úteis
- ⚠️ 401/403 / permissão (endpoint existe mas não para seu perfil)

---

## 💡 Dicas Técnicas para os Testes

### Tratamento de Erros
- **HTTP 404:** Endpoint realmente não existe. Pule para o próximo.
- **HTTP 401:** Bearer Token expirou → rode o refresh do token.
- **HTTP 400:** Algum query param inválido. Tente remover os filtros gradualmente até ficar só page=1&limit=1.
- **HTTP 403:** Endpoint existe mas requer perfil de ADMIN ou porteiro. Testar com token de síndico se disponível.

### Variações de Case a Testar
Para query params que dão erro, sempre teste variações de case:
```
/unidade.Uuid=  vs  /unidade.uuid=  vs  /Unidade.Uuid=
/dtIni=  vs  /DtIni=  vs  /dtInicio=
```

### Variáveis de ambiente úteis no Postman
Adicione essas variáveis se não tiver:
- `eventoAcessoUuid`: UUID de um evento (pegue da listagem)
- `autorizacaoUuid`: UUID de uma autorização
- `moradorUuid`: Vem do endpoint `/perfil` automaticamente
- `condominioUuid`: Vem do endpoint `/perfil` automaticamente
- `dataInicioTeste`: ex: `2026-07-01`
- `dataFimTeste`: ex: `2026-07-31`

---

## 🎯 Resultado Esperado

Após executar os testes da **Prioridade 0**, pelo menos UM deles deve responder a pergunta:
> "Qual usuário/funcionário/porteiro registrou este acesso na portaria?"

Cenários possíveis:
1. **Cenário A (melhor):** `/evento_acesso/{uuid}` retorna o campo com dados completos.
2. **Cenário B:** `/evento_acesso` lista já tem o campo mas estava sendo ignorado (nome não óbvio).
3. **Cenário C:** `/evento_acesso` retorna só um `usuarioUuid`, e `/usuario/{uuid}` traz os dados completos do operador.
4. **Cenário D:** `/evento_acesso/relatorio` traz o campo completo.
5. **Cenário E (ruim):** Nenhum GET traz. Nesse caso precisaremos de engenharia reversa mais profunda (inspecionar quando a própria UI do VDS mostra o nome do operador).
