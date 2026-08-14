# Raciocínio: Análise dos Resultados REAIS dos Testes

**Data:** 2026-07-31
**Origem:** Arquivo `e:\DEV\recMan\docs\inspect\testes_endpoints` - resultados de testes efetivamente executados pelo usuário no Postman/cURL.

---

## 1. Endpoints que REALMENTE funcionaram (novos descobertos)

| Endpoint | Método | Descoberta |
|---|---|---|
| `/portaria/destino?Condominio.Uuid=` | GET | ✅ **NOVO** - Lista destinos (unidades + status: Ocupado/Vazio) + flag `facialAtiva` do reconhecimento facial |
| `/portaria/visitante/{uuid}/dispositivo` | GET | ✅ **NOVO** - Lista CATRACAS/DISPOSITIVOS aos quais o visitante teve acesso cadastrado (ex: Catraca P2 Entrada, Entrada Principal P1) |
| `/portaria/visitante` | POST | ✅ **NOVO** - Cadastrar/atualizar visitante DIRETO NA PORTARIA, sem passar por autorização de acesso prévia! (com `destinoUuid`, `validade`, `dispositivoTipo: 11`, `tipoVisitante.uuid`) |
| `/ocorrencia?...&Caixa=1` | GET | ✅ **🏆 DESCOBERTA CRÍTICA** - `Caixa=1` = CAIXA DE SAÍDA / ENVIADOS (o que antes era só Caixa=0). **Aqui está a informação "quem registrou".** |
| `/ocorrencia/{uuid}/ordem-servico?condominioUuid=` | GET | ✅ **NOVO** - Ordem de serviço associada à ocorrência (retorna `ordens[]` e `proximoProtocolo`) |
| `/ocorrencia/visualizar/{uuid}?condominioUuid=` | PUT | ✅ **Variação conhecida** - Exige `condominioUuid` como query param |
| `/ocorrencia/{uuid}?condominioUuid=` | GET | ✅ **Variação conhecida** - Também aceita `condominioUuid` como query param |

---

## 2. 🏆 RESPOSTA PARA A LACUNA PRINCIPAL: "QUEM REGISTROU O ACESSO?"

### 💡 HIPÓTESE CONFIRMADA A PARTIR DOS DADOS REAIS:

**A informação NÃO ESTÁ em `/evento_acesso`**. Ela está na **OCORRÊNCIA** que o sistema cria automaticamente na **CAIXA=1 (Caixa de Saída/Enviados)** no momento do registro na portaria!

**Prova irrefutável a partir dos seus testes:**

1. **Você** simulou ser um porteiro e fez `POST /portaria/visitante` cadastrando "Edinis Alves" (Prestador).
2. O sistema automaticamente gerou uma ocorrência na **Caixa=1 (enviados)** com protocolo 261493:
   ```json
   {
     "tipoId": 91,
     "titulo": "Controle de Acesso - Prestador de Serviços",
     "tipoNome": "Prestador de Serviços",
     "por": "Davi Nunes",                       // ← 🏆 QUEM REGISTROU!
     "cargo": "CONDOMÍNIO - GESTÃO",            // ← Cargo de QUEM registrou
     "foto": "/app/dados/cond/1441/foto/PESSOA/G-1117828.jpeg", // ← Foto de quem registrou
     "dePessoaId": 1117828,                      // ← ID numérico da PESSOA que registrou
     "destino": "Bloco E - 104"                  // ← Unidade de destino do visitante
   }
   ```
3. No detalhe da ocorrência, dentro de `eventos[]`, a informação se repete:
   ```json
   {
     "por": "Davi Nunes",
     "cargo": "Conselheiro",
     "foto": "/app/dados/cond/1441/foto/PESSOA/g-1117828.jpeg",  // Foto de quem registrou
     "listaAnexo": [
       {
         "nome": "Foto",
         "url": "/app/dados/visitante/2026/4/f-7909848.jpg"      // ← FOTO DO VISITANTE!
       }
     ],
     "destino": "Bloco E - 104"
   }
   ```

### 🧩 Como fica o fluxo completo agora:

**Cenário: Um porteiro cadastra um visitante na portaria**
```
1. Porteiro usa o VDS → POST /portaria/visitante → registra visitante
2. VDS cria automaticamente uma OCORRÊNCIA em Caixa=1 (enviados) com:
   → tipoId: 91 (Prestador) ou outro tipo (Visitante Social, Entregador)
   → campo "por" = NOME do porteiro que efetuou o cadastro
   → campo "cargo" = "Porteiro" ou similar
   → campo "foto" = foto do porteiro
   → "destino" = unidade visitada
   → Anexo = foto do visitante tirada no momento
3. VDS envia o EMAIL para o morador = essa mesma ocorrência formatada como email
4. (Posteriormente) Quando o visitante passa na catraca → /evento_acesso registra a passagem física
   → O /evento_acesso NÃO tem "quem cadastrou" porque isso já está na ocorrência acima
```

### 🎯 Como nós conseguimos essa informação (estratégia completa):

Não é 1 endpoint só. É a **COMBINAÇÃO de dados de 2 endpoints**:

| Passo | Endpoint | Uso |
|---|---|---|
| 1 | `GET /ocorrencia?Caixa=1&tipoId=91&DtInicio=&DtFim=` | Lista TODOS os registros de visitantes/prestadores cadastrados na portaria, com o nome de QUEM registrou (`por`), data, e unidade de destino. |
| 2 | `GET /evento_acesso?dtInicio=&dtFim=` | Lista passagens FÍSICAS na catraca (horário exato, foto do visitante na entrada, tipo de acesso: pedestre/veículo) |
| 3 | **Cruzamento** | Cruzar os dois por: (a) NOME do visitante, (b) DATA/HORA próximas, (c) UNIDADE de destino. |

Ou ainda mais simples: **Se o objetivo é só saber "quem registrou" e não necessariamente a passagem na catraca**, só precisamos do endpoint `/ocorrencia?Caixa=1` com filtros adequados! 🎉

---

## 3. Novos Campos/Filtros Descobertos em `/ocorrencia`

### Query params novos para /ocorrencia (antes não sabíamos):
```
Caixa=1        → 0 = Entrada (inbox), 1 = Saída / Enviados
Tipo=          → Filtrar por tipoId (ex: 91 = Prestador)
DtInicio=      → Data de início (formato YYYY-MM-DD)
DtFim=         → Data de fim (formato YYYY-MM-DD)
Condominio.Uuid= → Filtro por condomínio
```

### Campos novos no retorno da LISTA (Caixa=0 ou Caixa=1):
```json
{
  "dePessoaId": 1117828,    // ID NUMÉRICO (não UUID!) da pessoa QUE ENVIOU/registrou
  "paraPessoaId": 1117828,  // ID NUMÉRICO da pessoa destinatária
  "por": "Davi Nunes",      // NOME de quem registrou/enviou
  "cargo": "CONDOMÍNIO - GESTÃO", // Cargo da pessoa
  "destino": "Bloco E - 104",     // Unidade de destino (no caso de visita)
  "condNome": "Residencial Top Life Miami Beach",
  "executantes": []
}
```

### Campos novos no DETALHE `/ocorrencia/{uuid}`:
```json
{
  "permiteClassificar": true,
  "permiteComentarios": true,
  "ocorrenciaPaiId": 52137515,
  "permiteEncaminhar": true,
  "isLida": false,
  "isTarefa": false,
  "podeMarcarLeitura": true,
  "dtIni": "2026-07-31T23:23:13.283"
}
```

---

## 4. Outros Dados Muito Valiosos

### 4.1 POST /portaria/visitante - Body completo revelado:
```json
{
  "uuid": "7909848",                       // ← UUID do visitante (NUMÉRICO! não é UUID padrão)
  "nome": "Edinis Alves",
  "foto": "/app/dados/visitante/2026/4/f-7909848.jpg",
  "documento": "01063983185",
  "documentoTipo": {"uuid": "cpf"},
  "estado": "DF",
  "empresa": "SHALON MARCENARIA",
  "tipoVisitante": {"uuid": "91"},          // ← 91 = Prestador
  "destinoUuid": "ec283937-fb6b-4df0-b049-92918396f657",
  "destinoTipo": "UNIDADE",
  "validade": "2026-07-31T23:59:59",        // ← Até quando vale o acesso
  "dispositivoTipo": 11,                    // ← Tipo de dispositivo da portaria (11 = ?)
  "autorizacaoAcessoUuid": null             // ← NULL = registro DIRETO, sem autorização prévia
  // ...mais campos
}
```
**OBS IMPORTANTE:** `uuid` de visitante é NUMÉRICO (`"8045781"`, `"7909848"`), não um UUID padrão com traços!

### 4.2 Novos Tipos de Ocorrência descobertos:
| tipoId | Nome |
|---|---|
| 91 | Controle de Acesso - Prestador de Serviços |
| 101 | Fale com a Manutenção |
| 115 | Fale com o Conselho |

Devem existir MUITOS outros (Visitante Social, Entregador, Mudança, Obra, etc.)

### 4.3 /portaria/visitante/{uuid}/dispositivo:
```json
[
  {"uuid": "3493", "nome": "Entrada Principal P1", "status.uuid": "2"},
  {"uuid": "5095", "nome": "Catraca P2 Entrada", "status.uuid": "2"},
  {"uuid": "5096", "nome": "Catraca P2 Saída", "status.uuid": "2"}
]
```
O visitante teve acesso liberado em 3 dispositivos. status.uuid=2 = "Sucesso"

### 4.4 /portaria/destino:
```json
{
  "facialAtiva": true,   // ← Reconhecimento facial está ATIVO!
  "destino": [
    {"uuid": "...", "nome": "Bloco A - 1001", "status": "Ocupado", "tipo": "UNIDADE"}
  ]
}
```

---

## 5. Novas Hipóteses (baseadas em padrões dos DADOS REAIS):

### 5.1 Tipos de Visitante e Dispositivos
Existem `tipoVisitante: uuid: "91"` e `dispositivoTipo: 11` no POST.  
⇒ Endpoints prováveis (faltando):
- `GET /portaria/visitante_tipo` ou `GET /visitante_tipo`
- `GET /portaria/dispositivo` ou `GET /dispositivo`
- `GET /portaria/dispositivo_tipo`

### 5.2 TipoId de Ocorrências
Vimos `tipoId: 91, 101, 115`. Precisamos da lista completa.  
⇒ Endpoint provável:
- `GET /ocorrencia_tipo`
- `GET /ocorrencia/tipo`
- `GET /tipo_ocorrencia`

### 5.3 Consulta por `pessoaId` NUMÉRICO
Vimos `dePessoaId: 1117828` (NÚMERO, não UUID!).  
⇒ Endpoints prováveis para buscar dados completos da pessoa:
- `GET /pessoa/1117828`
- `GET /pessoa?id=1117828`
- `GET /pessoa?Pessoa.Id=1117828`
- `GET /pessoa?PessoaId=1117828`
- `GET /cond_pessoa/1117828`

### 5.4 Mais filtros para /ocorrencia Caixa=1
A lista agora tem `destino`, `por`, `tipoId`. Podemos testar filtros:
- `?Pessoa.Uuid=` ou `?dePessoaId=` → Filtrar ocorrências por quem registrou
- `?Destino=` ou `?DestinoUuid=` → Filtrar por unidade de destino
- `?Visitante.Uuid=` → Filtrar por visitante específico (se tiver esse campo no retorno)

### 5.5 Endpoints `/portaria` adicionais
Existem `/portaria/destino` e `/portaria/visitante`. Seguindo o padrão:
- `GET /portaria/operador` ou `GET /portaria/usuario`
- `GET /portaria/movimento` (painel de hoje)
- `GET /portaria/entrada` (registros de entrada de HOJE)

### 5.6 `dePessoaId` e `paraPessoaId`
Se pudermos consultar `/pessoa/{id}`, teremos:
- Quem cadastrou o visitante (dePessoaId)
- O destinatário da ocorrência (paraPessoaId)

---

## 6. O que a gente tem agora vs o que faltava antes:

| Informação | Antes (?) | Agora (confirmado!) |
|---|---|---|
| Quem registrou a visita | ❌ NÃO tinha | ✅ **TEM!** Campo `por` em `/ocorrencia?Caixa=1` |
| Cargo de quem registrou | ❌ NÃO | ✅ `cargo` em /ocorrencia |
| Foto de quem registrou | ❌ NÃO | ✅ `foto` em /ocorrencia |
| Unidade visitada | ❌ Parcial | ✅ `destino` em /ocorrencia |
| Foto do visitante no momento | ❌ NÃO | ✅ `listaAnexo[0].url` em eventos[] |
| Data/hora do cadastro | ❌ Parcial | ✅ `dtExibicao` / `dthora` |
| Tipo de visitante | ❌ Parcial | ✅ `tipoNome` / `tipoId` |
| Caixa de enviados | ❌ NÃO sabia | ✅ `Caixa=1` |
| Ordem de serviço por ocorrência | ❌ NÃO | ✅ `/ordem-servico` |
| Lista de dispositivos do visitante | ❌ NÃO | ✅ `/portaria/visitante/{uuid}/dispositivo` |
| Destinos disponíveis na portaria | ❌ NÃO | ✅ `/portaria/destino` |
| Reconhecimento facial ativo | ❌ NÃO | ✅ `facialAtiva` em /portaria/destino |
