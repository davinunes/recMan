# Walkthrough / Resumo de Entrega: Isolamento de Tokens Multi-Conselheiro & Alta Performance VDS

- **Data**: 2026-07-31
- **Status**: Concluído & Verificado

## Resumo das Modificações Realizadas

### 1. Regras de Negócio por Visão (Prática vs. Analítica)
- **Visão Prática (`visao=pratico`)**:
  - Exige obrigatoriamente que o conselheiro logado possua Ultra-Login ativo (`$hasUltraLogin = true`).
  - Se o conselheiro não tiver efetuado o Ultra-Login, a busca de chamados não lidos na VDS é **bloqueada** e o sistema exibe um alerta solicitando que ele conecte seu usuário pessoal nas Configurações VDS (`index.php?pag=configVds`).
- **Visão Analítica (`visao=analitico`)**:
  - Exibe normalmente todas as ocorrências armazenadas no banco local do Conselho.
  - Permite a postagem de **Notas Internas do Conselho**.
  - **Bloqueia a publicação remota no VDS (`publicar_remoto`)** se o conselheiro não tiver Ultra-Login ativo, exibindo um botão de aviso apontando para a tela de configurações.

### 2. Isolamento Rigoroso de Tokens entre Conselheiros (`classes/vds_auth_service.php`)
- Refatorada a função `vds_get_token($usuarioIdConselho = null, $allowCondominioFallback = true)`:
  - **Eliminada a query genérica (Passo 3)** que realizava `SELECT * FROM vds_tokens WHERE bearer_token IS NOT NULL ORDER BY id DESC LIMIT 1`.
  - Ao fornecer `$usuarioIdConselho`, a busca é feita exclusivamente para aquele conselheiro (`tipo = 'conselheiro' AND usuario_id_conselho = ?`).
  - NUNCA mais um conselheiro utilizará o token de outro conselheiro.
  - Se o token do conselheiro estiver com `status = 'expirado'`, a rotina tenta renová-lo imediatamente via `vds_refresh_token($id)`.

### 3. Correção de Status em `configVds.php` (`forms/configVds.php`)
- A busca do token do conselheiro atual agora é chamada com `$allowCondominioFallback = false`.
- Conselheiros que ainda não efetuaram o Ultra-Login recebem `null` e a interface exibe corretamente **Pendente de Ativação**.

### 4. Prevenção de Travementos e Lentidão (Timeouts cURL + Auto Mark Expired)
- Adicionados parâmetros de timeout de alta performance (`CURLOPT_TIMEOUT => 8` e `CURLOPT_CONNECTTIMEOUT => 4`) em todas as chamadas cURL dos serviços VDS (`vds_auth_service.php`, `vds_ocorrencia_service.php` e `vds_acesso_service.php`).
- Ao receber HTTP 401 (token expirado ou negado pela VDS API), o sistema executa automaticamente `vds_mark_token_expired($token)`.

## Estrutura de Arquivos Atualizada
- `file:///e:/DEV/recMan/classes/vds_auth_service.php`
- `file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php`
- `file:///e:/DEV/recMan/classes/vds_acesso_service.php`
- `file:///e:/DEV/recMan/forms/configVds.php`
- `file:///e:/DEV/recMan/livroDeOcorrencias.php`
- `file:///e:/DEV/recMan/.agents/raciocinios/2026-07-31_correcao_tokens_multiconselheiros_vds.md`
- `file:///e:/DEV/recMan/.agents/planos_e_workflows/planos/2026-07-31_correcao_tokens_multiconselheiros_vds.md`
- `file:///e:/DEV/recMan/.agents/planos_e_workflows/walkthroughs/2026-07-31_correcao_tokens_multiconselheiros_vds.md`
