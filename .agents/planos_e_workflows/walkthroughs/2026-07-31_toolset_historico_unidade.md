# Walkthrough - Confirmação do Endpoint de Detalhes do Veículo (`GET /veiculo/{uuid}`)

## Análise Comparativa do Modelo de Dados

Confirmamos o funcionamento do endpoint de detalhes por UUID:
`GET https://apiv8.vds.app.br/veiculo/{uuid}`

### Mapeamento dos Campos Adicionais:
1. **`auto.portadorNecessidade` (PCD)**:
   - Booleano indicando se o veículo é de portador de necessidades especiais/acessibilidade.
   - Adicionada a exibição da badge `<i class="material-icons">accessible</i> Vaga PCD` nos cards de veículos tanto no Toolset (`index.php?pag=historico`) quanto na Análise de Recurso (`detalheRecurso.php`).
2. **`auto.integrado`**:
   - Booleano indicando se o veículo está integrado ao sistema de controle de acesso/LPR/TAG de garagem.
3. **`status`**:
   - Status da vinculação do veículo (`0` = Ativo, `1` = Inativo).

### Registro na Documentação Técnica (Skill)
- O endpoint `GET /veiculo/{uuid}` foi registrado oficialmente na skill [`skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/skills/vds_api_v8/SKILL.md) sob a seção **E. Estrutura Física, Moradores, Veículos & Obras**.
