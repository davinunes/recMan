# Walkthrough: Restauração e Resiliência das Notificações Push

**Data**: 2026-11-16  
**Status**: Concluído com Sucesso  

---

## 1. O Problema Investigado

O usuário relatou a interrupção do envio de notificações push no sistema para votos, comentários e protocolos de novos recursos após uma correção recente contra bots maliciosos. O usuário levantou a hipótese de que a validação de usuário logado (adicionada para conter o bot) pudesse estar bloqueando as chamadas internas do próprio servidor por falta de sessão autenticada.

---

## 2. Diagnóstico Técnico Detalhado

1. **Separação de Endpoints**:
   - O endpoint acessado pelo bot era [`push_debug.php`](file:///d:/dev/github/recMan/push_debug.php), um arquivo legado de diagnóstico que enviava push imediatamente no método GET para todas as inscrições. Ele foi devidamente protegido com validação de sessão e método POST.
   - O endpoint acionado pelo servidor para disparar pushs em produção é [`classes/api_push_cli.php`](file:///d:/dev/github/recMan/classes/api_push_cli.php) (chamado via cURL local `127.0.0.1`). Esse arquivo **não** exige sessão de usuário e nunca exigiu; sua trava de segurança é checar se o IP de origem é `127.0.0.1` ou `::1`.

2. **Causa Raiz Identificada**:
   - No commit `1f6e7a3` (03/11/2026), com o intuito de proteger segredos de VAPID no Git, o arquivo `classes/vapid.json` foi removido do versionamento (`git rm --cached`) e colocado no `.gitignore`.
   - Ao rodar `git pull` no servidor remoto de produção, o Git executou a exclusão e **removeu fisicamente** o arquivo `classes/vapid.json`.
   - Como `classes/push_helper.php` fazia a verificação estrita `if (!file_exists($vapidPath)) return false;`, todos os disparos subsequentes (votos, comentários e recursos) foram abortados silenciosamente no backend.
   - Além disso, `api_push.php?action=get_vapid` passou a retornar erro, impedindo novas inscrições de navegadores.

3. **Inconsistência em Votos**:
   - `upsertVoto()` em `classes/repositorio.php` utilizava uma chamada manual legada de cURL direto, sem usar o helper centralizado `sendPushBackground()`, ficando vulnerável a caminhos de URL incorretos e sem link de redirecionamento para o recurso.

---

## 3. Modificações Realizadas

### 3.1 [`classes/push_helper.php`](file:///d:/dev/github/recMan/classes/push_helper.php)
- Implementada a função `getVapidConfig()`:
  - Tenta ler `classes/vapid.json`.
  - Se o arquivo não existir ou estiver com placeholders vazios, utiliza as credenciais VAPID oficiais do recMan (compatíveis com os registros já gravados no banco de dados) e restaura o arquivo no disco via `file_put_contents`.
  - Garante resiliência permanente: o sistema se auto-recupera mesmo após deploys ou exclusões acidentais.
- Atualizado `sendPushNotification()` para obter as credenciais via `getVapidConfig()`.

### 3.2 [`api_push.php`](file:///d:/dev/github/recMan/api_push.php)
- Ação `get_vapid` atualizada para utilizar `getVapidConfig()`, assegurando entrega da chave pública para o frontend solicitar permissão aos conselheiros.

### 3.3 [`push_debug.php`](file:///d:/dev/github/recMan/push_debug.php)
- Teste controlado de diagnóstico atualizado para validar `getVapidConfig()`.

### 3.4 [`classes/repositorio.php`](file:///d:/dev/github/recMan/classes/repositorio.php)
- Função `upsertVoto()` padronizada: chamada manual legada de cURL substituída pela função centralizada `sendPushBackground($titulo, $mensagem, $url)`, incluindo o link direto para o recurso (`/index.php?pag=recurso&rec=...`).

### 3.5 [`classes/vapid.json`](file:///d:/dev/github/recMan/classes/vapid.json)
- Arquivo restaurado localmente com as chaves oficiais (ignorado pelo Git via `.gitignore`).
