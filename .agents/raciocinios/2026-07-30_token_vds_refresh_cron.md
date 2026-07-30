# Raciocínio & Diagnóstico: Renovação de Token VDS e Sincronização Cron

**Data:** 30/07/2026  
**Projeto:** recMan  
**Componente:** `vds_auth_service.php` / `cron_vds_sync.php`

---

## 1. Contexto e Problema Inicial
- O usuário relatou que os tokens VDS continuavam expirando no banco e o cron de sincronização automática (`cron_vds_sync.php`) falhava.
- O commit `4e153d2747be5cc157982f696d846b501d3adeb3` atualizou a coleção do Postman (`docs/vds_api_v8_postman_collection.json`) e adicionou a requisição `1.4 Renovar Token (Refresh)`.

---

## 2. Etapas de Pensamento e Diagnóstico

### Etapa 2.1: Análise da Coleção Postman (Item 1.4)
Ao examinar a dif do commit no Postman, identificamos que a chamada real feita pelo navegador para renovar a sessão era:
- **Método & Endpoint:** `POST https://apiv8.vds.app.br/login/refresh`
- **Body JSON:**
  ```json
  {
    "token": "{{bearerToken}}",
    "refreshToken": "{{refreshToken}}",
    "crypt": false
  }
  ```
- **Cabeçalhos:** `Content-Type: application/json`, `Origin: https://app1.vidadesindico.com.br`. Sem header `Authorization: Bearer`.

### Etapa 2.2: Comparação com o Código Existente em `vds_auth_service.php`
Ao analisar a função `vds_refresh_token()`, foram identificadas 3 divergências críticas:
1. **Body incorreto:** O código PHP antigo enviava apenas `{"refreshToken": "..."}`, omitindo o `token` atual e a chave `crypt: false`. Por isso a API da VDS rejeitava o refresh.
2. **Endpoints em loop:** O código tentava 3 URLs distintas (`/auth/refresh`, `/login/refresh`, `/token/refresh`) com o header `Authorization: Bearer`, o que diferia do comportamento real do app.
3. **Mecanismo de expiração reativo:** A função `vds_get_token()` só acionava a renovação se o campo `status` da tabela `vds_tokens` estivesse marcado como `'expirado'`. Como o cron roda a cada 15 minutos, se o token expirasse entre as execuções, as requisições falhavam antes do status mudar.

---

## 3. Decisão da Solução Técnica

1. **Ajustar `vds_refresh_token()`**:
   - Usar unicamente `POST /login/refresh`.
   - Montar o payload exato exigido: `json_encode(['token' => $bearerToken, 'refreshToken' => $refreshToken, 'crypt' => false])`.
   - Atualizar a tabela `vds_tokens` com o novo token, novo refresh token e novo `expires_at`.

2. **Renovação Proativa em `vds_get_token()`**:
   - Adicionar verificação baseada na coluna `expires_at`.
   - Se o token for expirar nos próximos **10 minutos**, executar o refresh proativamente antes que expire.

3. **Logs Detalhados em `cron_vds_sync.php`**:
   - Imprimir o status do token, data de expiração e se há `refresh_token` disponível para facilitar o rastreamento via log.

---

## 4. Conclusão
Com a requisição alinhada com o comportamento real capturado no Postman e com a renovação proativa implementada, os tokens deixam de expirar silenciosamente na rotina de cron.
