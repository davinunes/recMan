# Plano de Testes: Rodada 2 - Baseado em Resultados REAIS

**Data:** 2026-07-31
**Base URL:** `https://apiv8.vds.app.br`
**Headers:** `Authorization: Bearer {{bearerToken}}` + `Origin: https://app1.vidadesindico.com.br`

---

## 🏆 CONTEXTO: A lacuna principal JÁ FOI RESPONDIDA!

A informação "quem registrou o acesso" está em:
```
GET /ocorrencia?Caixa=1&tipoId=91&DtInicio=2026-07-01&DtFim=2026-07-31
```

| Campo | O que traz |
|---|---|
| `por` | NOME de quem registrou (ex: "Davi Nunes") |
| `cargo` | Cargo da pessoa (ex: "CONDOMÍNIO - GESTÃO", "Porteiro") |
| `foto` | Foto de quem registrou |
| `dePessoaId` | ID NUMÉRICO da pessoa (não UUID!) |
| `destino` | Unidade visitada (ex: "Bloco E - 104") |
| `tipoNome` | Tipo de visita (Prestador, Visitante, Entregador) |

**O foco desta rodada é:**
1. ✅ Aprender a usar melhor o `/ocorrencia?Caixa=1` (novos filtros, todos os tipos de visita)
2. ✅ Descobrir endpoints faltantes que referenciam IDs NUMÉRICOS (`pessoaId`, `tipoVisitante`, `dispositivoTipo`)
3. ✅ Descobrir todos os tipos de ocorrência (tipoId = 91 é só um!)

---

## 🔴 PRIORIDADE 0: Profundizar em `/ocorrencia?Caixa=1`

> Já temos a mina de ouro! Agora precisamos entender TODAS as suas possibilidades.

---

### Teste #0.1: Quais são TODOS os tipos de ocorrência de Controle de Acesso?

Você descobriu que `tipoId=91` = "Prestador de Serviços". Devem existir tipoIds para:
- Visitante Social
- Entregador/Correio
- Veículo (entrada/saída de carro)
- Mudança
- Obra/Reforma
- etc.

**Estratégia A: Listar Caixa=1 SEM filtro de tipo (apenas intervalo grande)**
```
GET {{baseUrl}}/ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=desc&Caixa=1&Lida=9&DtInicio=2026-07-01&DtFim=2026-07-31&Condominio.Uuid={{condominioUuid}}
```
- Mude `limit=100` ou mais se tiver muitos registros
- Navegue `page=2`, `page=3` etc.
- Colete **TODOS** os `tipoId` + `tipoNome` + `titulo` que aparecerem

**Estratégia B: Se tiver muitos tipos diferentes, fazer uma tabela:**
```
| tipoId | tipoNome | titulo | Exemplo observado |
|--------|----------|--------|-------------------|
| 91     | Prestador de Serviços | Controle de Acesso - Prestador de Serviços | Cadastrou prestador na portaria |
| ???    | ??? | ??? | Visitante social cadastrado |
| ???    | ??? | ??? | Entregador cadastrado |
```

---

### Teste #0.2: Novos filtros possíveis para /ocorrencia Caixa=1

Teste UM POR VEZ para ver se cada parâmetro funciona:

```
# Filtrar por QUEM registrou (dePessoaId)
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&dePessoaId=1117828
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&Pessoa.Id=1117828
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&de.PessoaId=1117828

# Filtrar por UNIDADE de destino
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&DestinoUuid={{unidadeUuid}}
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&Unidade.Uuid={{unidadeUuid}}
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&destino.uuid={{unidadeUuid}}

# Filtrar por TIPO de ocorrência
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&tipoId=91
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&Tipo=91
GET {{baseUrl}}/ocorrencia?Caixa=1&Lida=9&tipoId=115   (Fale com Conselho, se for válido em Caixa=1)
```

---

### Teste #0.3: Anexo com dados da visita - EXTRAIR DADOS DO VISITANTE

No detalhe da ocorrência 261493, o `listaAnexo[0]` tem a foto do visitante `f-7909848.jpg`.  
O número `7909848` é UUID do visitante! (você mesmo cadastrou esse visitante)

**Testar se o próprio detalhe da ocorrência tem mais referências ao visitante:**
```
GET {{baseUrl}}/ocorrencia/482bbb30-8888-4085-9012-6ef134d93584?condominioUuid={{condominioUuid}}
```
No retorno, procurar por TUDO que possa ser referência ao visitante:
- `visitanteUuid`, `visitante.id`, `pessoa`, `documento`, `empresa`
- Dentro de `eventos[0]` procurar por esses nomes também
- Verificar se o `mensagem` do evento tem conteúdo (no seu teste estava vazio "", mas cadastros reais podem ter)

---

## 🟠 PRIORIDADE 1: Endpoints para IDs NUMÉRICOS (não UUID!)

Dado importantíssimo: `dePessoaId: 1117828` e `visitante uuid: 7909848` são **NÚMEROS**, não UUIDs com traços! Vamos usá-los.

---

### Teste #1.1: Dados completos da PESSOA por ID numérico

**Mais provável (Pessoa é entidade central no VDS):**
```
GET {{baseUrl}}/pessoa/1117828
GET {{baseUrl}}/pessoa?id=1117828
GET {{baseUrl}}/pessoa?Pessoa.Id=1117828
GET {{baseUrl}}/pessoa?PessoaId=1117828
GET {{baseUrl}}/pessoa?Id=1117828
```

**Variações de rota:**
```
GET {{baseUrl}}/cond_pessoa/1117828
GET {{baseUrl}}/pessoa_fisica/1117828
GET {{baseUrl}}/usuario_pessoa/1117828
```

**Se não funcionar com 1117828, testar com o ID do visitante:**
```
GET {{baseUrl}}/pessoa/7909848
GET {{baseUrl}}/pessoa?id=7909848
```

**O que PROCURAR no retorno:**
- Nome completo, email, telefone, tipo de pessoa, morador vinculado, etc.
- Se esse endpoint funcionar, temos TODOS os dados de quem registrou (endereço, telefone, etc.)

---

### Teste #1.2: Tipos de Visitante (tipoVisitante.uuid: "91")

No POST /portaria/visitante, você usou `tipoVisitante: {uuid: "91"}` = Prestador.  
Deve haver endpoint com todos os tipos (Visitante Social, Entregador, etc.)

```
GET {{baseUrl}}/portaria/visitante_tipo
GET {{baseUrl}}/visitante_tipo
GET {{baseUrl}}/portaria/tipo_visitante
GET {{baseUrl}}/tipo_visitante
GET {{baseUrl}}/visitante/tipo
```

---

### Teste #1.3: Tipos de Dispositivo e Lista de Dispositivos

No POST foi usado `dispositivoTipo: 11`. E temos dispositivos retornados em `/portaria/visitante/{uuid}/dispositivo` com UUIDs "3493", "5095", "5096".

```
# Lista TODOS os dispositivos da portaria do condomínio
GET {{baseUrl}}/portaria/dispositivo
GET {{baseUrl}}/dispositivo
GET {{baseUrl}}/portaria/dispositivo?Condominio.Uuid={{condominioUuid}}
GET {{baseUrl}}/dispositivo?Condominio.Uuid={{condominioUuid}}

# Tipos de dispositivo (11 = qual?)
GET {{baseUrl}}/portaria/dispositivo_tipo
GET {{baseUrl}}/dispositivo_tipo
GET {{baseUrl}}/tipo_dispositivo
```

---

### Teste #1.4: Tipos de Ocorrência (Lista COMPLETA)

Se conseguirmos essa, acabamos de vez com a dúvida de todos os tipoIds possíveis!

```
# Variações (todas baseadas em padrões de outros endpoints)
GET {{baseUrl}}/ocorrencia_tipo
GET {{baseUrl}}/ocorrencia/tipo
GET {{baseUrl}}/tipo_ocorrencia
GET {{baseUrl}}/ocorrencia_tipo?Condominio.Uuid={{condominioUuid}}
GET {{baseUrl}}/ocorrencia_classificacao
GET {{baseUrl}}/ocorrencia_categoria
GET {{baseUrl}}/ocorrencia_categoria?Condominio.Uuid={{condominioUuid}}

# Combo (padrão bloco/unidade/morador)
GET {{baseUrl}}/ocorrencia_tipo?Combo=true
GET {{baseUrl}}/ocorrencia/tipo?Combo=true
```

---

## 🟡 PRIORIDADE 2: Explorar namespace `/portaria/` (muito produtivo!)

O namespace `/portaria` foi o que mais revelou endpoints novos.  
Vamos testar TODAS as possibilidades baseadas no que já funciona (`/portaria/destino`, `/portaria/visitante`).

---

### Teste #2.1: Usuários/Operadores da Portaria

Pela lógica, existem usuários com permissão de operar a portaria (porteiros, equipe administrativa).

```
GET {{baseUrl}}/portaria/usuario
GET {{baseUrl}}/portaria/operador
GET {{baseUrl}}/portaria/funcionario
GET {{baseUrl}}/portaria/equipe
```

Se algum funcionar, podemos associar diretamente ao campo `por` da ocorrência!

---

### Teste #2.2: Movimento da Portaria HOJE

Visualizações em tempo real são comuns em módulos de portaria.

```
# "Movimento" de hoje
GET {{baseUrl}}/portaria/movimento
GET {{baseUrl}}/portaria/movimento?Data=2026-07-31
GET {{baseUrl}}/portaria/movimento?DtInicio=2026-07-31T00:00&DtFim=2026-07-31T23:59

# Entradas do dia (sem cruzamento com ocorrências)
GET {{baseUrl}}/portaria/entrada
GET {{baseUrl}}/portaria/entrada?DestinoUuid={{unidadeUuid}}

# Registros do dia (lista direta de todos os cadastrados hoje)
GET {{baseUrl}}/portaria/registro
GET {{baseUrl}}/portaria/registro?dtIni=2026-07-31
```

---

### Teste #2.3: Detalhe individual de Destino

Funciona lista `/portaria/destino`. Tem detalhe individual?

```
GET {{baseUrl}}/portaria/destino/{{destinoUuid}}
# destinoUuid = pegue do endpoint de lista (ex: "d0eeaf24-2185-4e79-b0c9-b077f99a2b27")
```

---

### Teste #2.4: Dispositivo individual

Funciona `/portaria/visitante/{uuid}/dispositivo`. Tem endpoint direto para dispositivo?

```
GET {{baseUrl}}/portaria/dispositivo/3493      # "Entrada Principal P1"
GET {{baseUrl}}/portaria/dispositivo/5095      # "Catraca P2 Entrada"
```

---

## 🟢 PRIORIDADE 3: Testes menores (baseados em filtros novos)

---

### Teste #3.1: Filtros adicionais em /evento_acesso

Só para confirmar se algum filtro novo ajuda no cruzamento de dados:

```
GET {{baseUrl}}/evento_acesso?page=1&limit=3&sortBy=dthora&order=desc&dtInicio=2026-07-31T00:00&dtFim=2026-07-31T23:59&unidade.uuid={{unidadeUuid}}&destino.uuid={{unidadeUuid}}
```

---

### Teste #3.2: Endpoint `/pessoa/documento` (busca por CPF)

Variação de busca de pessoa por documento:
```
GET {{baseUrl}}/pessoa/documento?Documento=01063983185&DocumentoTipo=cpf
GET {{baseUrl}}/pessoa?Documento=01063983185
```

---

### Teste #3.3: `/funcionario` (lista de funcionários do condomínio)

Porteiros são funcionários normalmente:
```
GET {{baseUrl}}/funcionario?page=1&limit=50&sortBy=nome&order=asc
GET {{baseUrl}}/funcionario?Condominio.Uuid={{condominioUuid}}
```

---

## 📋 Check-list Execução (Rodada 2)

Marque conforme for testando:

| # | Endpoint / Teste | Método | Status | Observações |
|---|---|---|---|---|
| **0.1** | `/ocorrencia?Caixa=1` (sem filtro tipo, 100 regs) | GET | ⬜ | Coletar todos tipoId/tipoNome |
| **0.2a** | Filtrar Caixa=1 por `dePessoaId=1117828` | GET | ⬜ | Qual variação funciona? |
| **0.2b** | Filtrar Caixa=1 por `Unidade.Uuid` ou `DestinoUuid` | GET | ⬜ | Qual funciona? |
| **0.2c** | Filtrar Caixa=1 por `tipoId=91` | GET | ⬜ | |
| **0.3** | Detalhe ocorrência 261493 = procurar campo visitante | GET | ⬜ | Existe referência ao visitante dentro de eventos? |
| **1.1** | `/pessoa/1117828` e variações | GET | ⬜ | 🎯 Muito importante! Dados completos de quem registrou |
| **1.2** | `/portaria/visitante_tipo` e variações | GET | ⬜ | Lista: Prestador, Visitante Social, Entregador... |
| **1.3a** | `/portaria/dispositivo` | GET | ⬜ | Lista todas as catracas/entradas |
| **1.3b** | `/portaria/dispositivo_tipo` | GET | ⬜ | dispositivoTipo 11 = ? |
| **1.4** | `/ocorrencia_tipo` e variações | GET | ⬜ | 🎯 Todos tipoIds (91, 101, 115, etc.) |
| **2.1** | `/portaria/usuario` / operador / funcionario | GET | ⬜ | |
| **2.2** | `/portaria/movimento` e variações | GET | ⬜ | |
| **2.3** | `/portaria/destino/{uuid}` | GET | ⬜ | Detalhe individual |
| **2.4** | `/portaria/dispositivo/3493` | GET | ⬜ | Detalhe dispositivo |
| **3.1** | Filtros novos em evento_acesso | GET | ⬜ | |
| **3.2** | `/pessoa/documento` (busca CPF) | GET | ⬜ | |
| **3.3** | `/funcionario` | GET | ⬜ | Porteiros |

---

## 🎯 Resultado Esperado Após Rodada 2

Quando você terminar estes testes, teremos:

| Meta | Como? |
|---|---|
| **Lista COMPLETA de todos os tipoIds de ocorrência** | Teste #1.4, ou #0.1 se #1.4 não existir |
| **Dados completos de "quem registrou" (telefone/email/endereço)** | Teste #1.1 `/pessoa/{id}` |
| **Lista de tipos de visitante** | Teste #1.2 |
| **Lista de todas as catracas/dispositivos do condomínio** | Teste #1.3a |
| **Filtros úteis em Caixa=1** | Teste #0.2 (filtrar por unidade, por pessoa, etc.) |
| **Referência explícita de visitante na ocorrência** | Teste #0.3 (procurar campos no detalhe) |
| **Endpoints novos no namespace /portaria** | Testes #2.1 a #2.4 |
