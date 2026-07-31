# Plano de Implementação: Isolamento de Tokens Multi-Conselheiro e Otimização de Performance VDS

- **Data**: 2026-07-31
- **Objetivo**: Corrigir o vazamento/cruzamento de tokens entre conselheiros no Ultra-Login, evitar falso positivo de login no `configVds.php`, tratar a expiração HTTP 401 com auto-refresh e eliminar a lentidão nas chamadas de ocorrências.

## Componentes a Serem Modificados

### 1. `classes/vds_auth_service.php`
- **Refatorar `vds_get_token($usuarioIdConselho = null, $allowCondominioFallback = true)`**:
  - Garantir busca estrita por `usuario_id_conselho` para conselheiros.
  - Retornar apenas token de condomínio em caso de fallback permitido.
  - Eliminar o Passo 3 (fallback para qualquer token aleatório na tabela).
  - Tentar auto-refresh se `status === 'expirado'` e houver `refresh_token`.
- **Adicionar Timeouts cURL**:
  - Incluir `CURLOPT_TIMEOUT => 8` e `CURLOPT_CONNECTTIMEOUT => 4` em `vds_get_anon_token`, `vds_authenticate`, `vds_refresh_token` e `vds_check_token_status`.

### 2. `classes/vds_ocorrencia_service.php`
- **Tratamento de Token Expirado (HTTP 401)**:
  - Ao receber HTTP 401 em `vds_get_ocorrencias_pratico` e `vds_get_ocorrencia_detalhe`, acionar `vds_mark_token_expired($token)`.
  - Tentar re-obter token (que tentará `vds_refresh_token`) antes de falhar.
- **Adicionar Timeouts cURL**:
  - Incluir `CURLOPT_TIMEOUT => 8` e `CURLOPT_CONNECTTIMEOUT => 4` em `vds_sync_ocorrencias`, `vds_get_ocorrencia_detalhe`, `vds_get_ocorrencias_pratico` e `vds_marcar_como_lido`.

### 3. `forms/configVds.php`
- **Ajustar Checagem de Status**:
  - Chamar `vds_get_token($usuarioIdConselho, false)` para evitar que o condomínio ou outro conselheiro mascare a ausência de Ultra-Login do conselheiro atual.
  - Exibir o ID do conselheiro em sessão na interface para conferência.

## Plano de Verificação Manual
1. Testar consulta com conselheiro sem Ultra-Login ativo: verificar se exibe **Pendente de Ativação** em `configVds.php`.
2. Testar consulta com conselheiro com Ultra-Login ativo: verificar se exibe **Ultra-Login Ativo** sem interferir nos demais conselheiros.
3. Simular token expirado no DB: verificar se o auto-refresh substitui o token sem travar a interface.
4. Carregar tela de Ocorrências (`visao=pratico`): medir resposta rápida sem estagnamento em cURL.
