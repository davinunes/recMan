# Raciocínio de Diagnóstico e Resolução: Isolamento de Tokens Multi-Conselheiro e Perda de Performance VDS

- **Data**: 2026-07-31
- **Tópico**: Correção de cruzamento de tokens de Ultra-Login entre conselheiros na VDS API v8 e otimização de lentidão/tratamento de expiração.

## 1. Problema Relatado
Após a autenticação de um segundo conselheiro via Ultra-Login (ex: `mcjmariana@yahoo.com.br` com `ConselheiroID 23`), observou-se:
1. As telas de ocorrências ficaram extremamente lentas.
2. Na página `index.php?pag=configVds`, ao logar com o 2º conselheiro (ou conselheiro sem token), o sistema indicava que o Ultra-Login estava **ativo**, revelando que o sistema não estava filtrando os tokens cruzando com o ID do conselheiro logado.
3. O Console de Diagnóstico & Debug apontou: `Token Encontrado: NÃO (Ausente no vds_tokens)` e `Erro ao consultar chamados não lidos na VDS`.

## 2. Causa Raiz Identificada

### A. Fallback Indiscriminado em `vds_get_token()` (`classes/vds_auth_service.php`)
A função `vds_get_token($usuarioIdConselho)` executava três passos de consulta SQL:
- **Passo 1**: Busca por `usuario_id_conselho = ?` caso fornecido.
- **Passo 2**: Busca por `tipo = 'condominio'` caso `$usuarioIdConselho` fosse `null`.
- **Passo 3 (Falha Crítica)**: `SELECT * FROM vds_tokens WHERE bearer_token IS NOT NULL ORDER BY id DESC LIMIT 1`.

Quando o 2º conselheiro (ou qualquer conselheiro sem registro no `vds_tokens`) acessava o sistema:
- O Passo 1 não encontrava nenhum registro para aquele `usuario_id_conselho`.
- O Passo 2 era ignorado por ser `$usuarioIdConselho` não-nulo.
- O Passo 3 entrava em ação e selecionava **o último token gravado na tabela** (que pertencia a outro conselheiro, ex: `davi.nunes` com `ConselheiroID 5`).

Conquências:
- O 2º conselheiro recebia o token do 1º conselheiro.
- Em `configVds.php`, a validação do token retornado (do 1º conselheiro) respondia como ativo, exibindo erroneamente "Ultra-Login Ativo" para o 2º conselheiro.
- Requisições na API VDS em nome do 2º conselheiro usavam o token do 1º conselheiro, violando o isolamento de sessão.

### B. Ausência de Timeouts cURL e Tratamento Inadequado de HTTP 401
- As chamadas HTTP via `curl_exec` em `vds_auth_service.php` e `vds_ocorrencia_service.php` não definiam `CURLOPT_TIMEOUT` nem `CURLOPT_CONNECTTIMEOUT`.
- Quando um token expirava na VDS (HTTP 401), a função `vds_get_ocorrencias_pratico` apenas retornava a mensagem de erro sem marcar o token local como `expirado` nem acionar a rotina de auto-renovação (`vds_refresh_token`).
- Isso fazia com que a cada refresh da página, múltiplas chamadas HTTP cURL lentas e sem timeout fossem disparadas em sequência, bloqueando o PHP e gerando lentidão extrema.

## 3. Solução Proposta

1. **Eliminação do Fallback Indiscriminado**:
   - Ajustar `vds_get_token($usuarioIdConselho = null, $allowCondominioFallback = true)`:
     - Se fornecido `$usuarioIdConselho`, busca estritamente o token do conselheiro.
     - Se não encontrar e `$allowCondominioFallback` for `true`, busca o token geral do condomínio (`tipo = 'condominio'`).
     - **Eliminar totalmente** o Passo 3 genérico. NENHUM conselheiro jamais receberá o token de outro conselheiro.
2. **Ajuste em `configVds.php`**:
   - Passar `$allowCondominioFallback = false` ao checar o status do conselheiro logado (`vds_get_token($usuarioIdConselho, false)`). Se ele não tiver token próprio, retornará `null` e a tela exibirá **Pendente de Ativação**.
3. **Auto-Renewal e Tratamento de Expirados**:
   - Ao receber HTTP 401 em chamadas da API VDS, marcar automaticamente o token como `expirado` via `vds_mark_token_expired($token)`.
   - Na próxima chamada a `vds_get_token`, o sistema detecta `status = 'expirado'`, tenta a renovação via `vds_refresh_token($id)` e reativa o token automaticamente se houver `refresh_token` válido.
4. **Timeouts cURL de Alta Performance**:
   - Adicionar `CURLOPT_TIMEOUT => 8` e `CURLOPT_CONNECTTIMEOUT => 4` em todos os handlers de cURL dos serviços da VDS.
