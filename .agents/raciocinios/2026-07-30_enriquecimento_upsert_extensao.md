# Registro de Raciocínio: Integração do Endpoint Legado `upsert.php` com a API v8 da VDS

- **Data**: 2026-07-30
- **Tópico**: Adaptação do endpoint `ocorrenciasCondominioDigital/upsert.php` (utilizado pela extensão do navegador) para gravar o protocolo no campo correto e autocompletar os dados utilizando a API v8.

---

## 1. Problema Abordado

A extensão legada do navegador enviava requisições POST para `/ocorrenciasCondominioDigital/upsert.php` passando o número do protocolo no parâmetro `id`.
Anteriormente, o `upsert.php`:
- Gravava o protocolo apenas na coluna `id` do MySQL.
- Não preenchia a coluna `protocolo_vds`.
- Não populava o `uuid_remoto`, `oco_tipo` nem `dados_json` com a estrutura moderna da API v8.

---

## 2. Solução Implementada

1. **Garantia da Coluna `protocolo_vds`**:
   - `upsert.php` agora sempre grava e atualiza `protocolo_vds = (string)$id`.
   - Ao buscar uma ocorrência no banco, o `upsert.php` consulta por `WHERE protocolo_vds = ? OR id = ?`.

2. **Auto-Enriquecimento Transparente via API v8**:
   - Imediatamente após salvar/atualizar os dados básicos recebidos da extensão, `upsert.php` chama `vds_get_ocorrencia_detalhe($protoStr)`.
   - Se o token da VDS estiver ativo, o backend faz uma chamada de fundo para a API v8 (`GET /ocorrencia?Protocolo=...`), descobre o `uuid_remoto`, o `oco_tipo`, obtém todo o objeto `dados_json` e atualiza o banco local automaticamente.
   - Todo registro criado ou atualizado via extensão passa a ficar 100% sincronizado e interoperável com a nova API v8 da VDS.
