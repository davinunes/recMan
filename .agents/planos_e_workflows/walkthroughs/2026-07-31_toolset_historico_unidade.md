# Walkthrough - Documentação do Endpoint de Veículos & Acelerador de Veículos da Unidade

## Atualizações Entregues

### 📄 1. Documentação Técnica da API VDS v8 (Skill & Postman)
- **Endpoint Registrado**: `GET /veiculo?Unidade.Uuid={unidadeUuid}&order=asc`
- **Inclusão na Documentação**: Adicionado na seção **E. Estrutura Física, Moradores, Veículos & Obras** da skill [`skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/skills/vds_api_v8/SKILL.md).

---

### 🚗 2. Backend & Integração de Veículos (`vds_get_veiculos_unidade`)
- **Nova Função Backend**: Criada em [vds_acesso_service.php](file:///e:/DEV/recMan/classes/vds_acesso_service.php) para consultar a API v8 e extrair:
  - `placa` (com estilo padrão de placa automotiva)
  - `marca`, `modelo`, `cor`
  - `tipo` (Automóvel, Motocicleta, etc.)
  - `proprietario` (se vinculado)
  - `observacao` (se informada)
  - `foto` (com resolução de URL completa `https://app.vidadesindico.com.br`)

---

### 🚗 3. Acelerador de Veículos nas Telas
1. **Toolset Operacional da Unidade (`index.php?pag=historico`)**:
   - Card KPI na Dashboard da Unidade (*Veículos Cadastrados*).
   - Seção colapsável dedicada `Veículos da Unidade` com badge dinâmico de contagem.
   - Cards responsivos com destaque visual para a placa, ícones contextuais (carro `directions_car` vs moto `two_wheeler`) e foto.
2. **Tela de Análise de Recurso (`detalheRecurso.php`)**:
   - Nova aba `Veículos da Unidade (N)` adicionada ao painel de aceleradores de análise da defesa.
