# Plano de Implementação: Backend para Novos Endpoints VDS (Encaminhamento, Status & Fechamento de Chamados)

Mapeamento e implementação dos 6 novos endpoints REST da API v8 Vida de Síndico (`apiv8.vds.app.br`) no backend PHP do repositório `recMan`. Por solicitação do usuário, **esta etapa focará 100% nas funções auxiliares e serviços do backend**, sem alterações de UI no frontend.

## User Review Required

> [!NOTE]
> A implementação é puramente no backend PHP (em `classes/vds_ocorrencia_service.php` e documentação na skill `.agents/skills/vds_api_v8/SKILL.md`). Nenhuma alteração visual ou de tela será realizada nesta fase.

## Proposed Changes

### Documentation & Skills

#### [MODIFY] [SKILL.md](file:///e:/DEV/recMan/.agents/skills/vds_api_v8/SKILL.md)
- Adicionar os 6 novos endpoints na seção **B. Ocorrências, Fale Com & Fluxo de Anexos em 3 Etapas**:
  1. `GET /ocorrencia/encaminhar/funcionarios`
  2. `GET /ocorrencia/encaminhar/grupos`
  3. `POST /ocorrencia/encaminhar`
  4. `GET /ocorrencia_tipo`
  5. `GET /ocorrencia/status`
  6. `PUT /ocorrencia/{ocorrenciaUuid}/classificacao`

---

### Backend Core Services

#### [MODIFY] [vds_ocorrencia_service.php](file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php)
Implementar as seguintes funções helper no serviço de ocorrências:
1. `vds_get_default_condominio_uuid($fallbackUuid = null)`: Obter UUID padrão do condomínio a partir de `vds_uuid_mapping` ou constante.
2. `vds_get_encaminhar_funcionarios($ocorrenciaUuid, $condominioUuid = null, $usuarioIdConselho = null)`: Retorna lista paginada de funcionários elegíveis.
3. `vds_get_encaminhar_grupos($ocorrenciaUuid, $condominioUuid = null, $usuarioIdConselho = null)`: Retorna lista de grupos/departamentos elegíveis.
4. `vds_encaminhar_ocorrencia($ocorrenciaUuid, $destinoTipo, $destinoIdOrUuid, $comentario = null, $dataPrevista = null, $condominioUuid = null, $usuarioIdConselho = null)`: Realiza o encaminhamento do chamado para Grupo (`destinoTipo = 'G'`) ou Funcionário (`destinoTipo = 'F'`).
5. `vds_get_ocorrencia_tipos_classificacao($ocoTipo = null, $ocoPai = null, $usuarioIdConselho = null)`: Consulta tipos de classificação ativos (`GET /ocorrencia_tipo`).
6. `vds_get_ocorrencia_status_possiveis($grupoId, $usuarioIdConselho = null)`: Consulta os status permitidos (Aberto, Em andamento, Fechado, etc.) para um determinado grupo.
7. `vds_alterar_status_classificacao($ocorrenciaUuid, $statusId, $classificacaoTipo = null, $prioridade = null, $condominioUuid = null, $usuarioIdConselho = null)`: Altera a classificação/status da ocorrência remoto (útil para fechar chamados com `statusId = 25`).

---

### Local Agents Record

#### [NEW] [2026-09-23_mapeamento_novos_endpoints_vds_chamados.md](file:///e:/DEV/recMan/.agents/planos_e_workflows/planos/2026-09-23_mapeamento_novos_endpoints_vds_chamados.md)
- Cópia persistente do plano em `.agents/planos_e_workflows/planos/`.

## Verification Plan

### Manual Verification
- Inspecionar a sintaxe do arquivo PHP para garantir que todas as funções utilizem os padrões estabelecidos (`vds_get_token()`, cURL seguro, tratamento de HTTP 401 e json_decode sanitizado).
- Verificar que o arquivo `SKILL.md` foi atualizado com precisão refletindo os payloads e cURLs fornecidos.
