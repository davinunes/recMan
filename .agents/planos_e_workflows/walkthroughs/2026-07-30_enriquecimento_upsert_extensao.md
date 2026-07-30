# Walkthrough: Adaptação do Endpoint Legado `upsert.php` com Auto-Enriquecimento VDS API v8

- **Data**: 2026-07-30
- **Status**: Concluído com Sucesso

---

## 1. Modificações Efetuadas

- **Arquivo**: [ocorrenciasCondominioDigital/upsert.php](file:///e:/DEV/recMan/ocorrenciasCondominioDigital/upsert.php)
- **Mudanças**:
  1. Inclusão das funções do serviço da VDS (`vds_ocorrencia_service.php`).
  2. Garantia de salvamento do parâmetro `id` enviado pela extensão no campo correto `protocolo_vds`.
  3. Consulta local atualizada para casar tanto por `protocolo_vds` quanto por `id` legado.
  4. **Auto-Enriquecimento API v8**: Após o salvamento dos dados da extensão, o script dispara `vds_get_ocorrencia_detalhe($protoStr)`, trazendo automaticamente o `uuid_remoto`, `oco_tipo` e a estrutura completa de `dados_json` da VDS API v8.

---

## 2. Benefícios

- **Interoperabilidade Total**: Ocorrências gravadas via extensão do navegador passam instantaneamente a ter todos os campos da nova API v8 preenchidos.
- **Zero Duplicidade**: Não há criação de registros conflitantes nem divergências de IDs entre o método legado e o método moderno.
