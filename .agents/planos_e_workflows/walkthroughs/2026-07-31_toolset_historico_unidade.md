# Walkthrough - Mapeamento dos Endpoints de Portaria e Visitantes (VDS v8)

## Alterações Realizadas

### 🚪 Endpoints de Portaria e Visitantes Mapeados
1. **Listar Visitantes por Destino (Unidade)**:
   - `GET /portaria/visitante?DestinoUuid={unidadeUuid}&DestinoTipo=UNIDADE`
   - Retorna lista de visitantes e prestadores vinculados à unidade (UUID, Foto, Nome, Documento CPF/RG/UF e Tipo).
2. **Obter Detalhes do Visitante por UUID**:
   - `GET /portaria/visitante/{visitanteUuid}`
   - Retorna detalhes cadastrais completos (dados pessoais, empresa, veículo associado, telefone, e-mail, observações).
3. **Consultar Validade e Status de Bloqueio do Visitante**:
   - `GET /portaria/visitante/{visitanteUuid}/validade?tipo={tipoUuid}`
   - Retorna a data/hora limite de validade e o booleano `bloqueado`.

### Registro na Documentação Técnica (Skill)
- Endpoints adicionados na especificação técnica [`skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/skills/vds_api_v8/SKILL.md) sob o grupo **C. Gestão Completa de Autorização de Acesso, Portaria, QR Codes & Convites Sociais**.
