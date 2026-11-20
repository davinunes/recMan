# Raciocínio de Diagnóstico: Investigação da Parada de Envio de Notificações Push

**Data**: 2026-11-16  
**Contexto**: O usuário relatou que, após a correção recente de vulnerabilidade (disparo acidental de push por bot), o sistema parou de emitir notificações push para votos, comentários e protocolos de novos recursos. O usuário suspeitou que a validação de usuário logado implementada na correção estivesse bloqueando a chamada interna do servidor, que atua sem sessão de usuário.

---

## 1. Rastreamento e Correlação Histórica

Investigamos o histórico recente de commits do Git e localizamos a intervenção de segurança realizada em 2026-11-03:
- **Commit `1f6e7a3`**: `sec: secure push debug endpoint and ignore local VAPID keys`
- **Arquivo de diagnóstico anterior**: `.agents/raciocinios/2026-11-03_investigacao_push_teste_corpo.md`

### O que o commit `1f6e7a3` realizou:
1. Adicionou autenticação por sessão (`empty($_SESSION['user_id'])`) e trava por método POST em `push_debug.php`.
2. Para evitar o versionamento de segredos no repositório, executou `git rm --cached classes/vapid.json`, adicionou `classes/vapid.json` ao `.gitignore` e criou o template `classes/vapid.json.example`.

---

## 2. Análise da Suspeita do Usuário vs Realidade Técnica

### Suspeita do Usuário:
> *"Acredito que a solução adotada, de validar ususario logado, tenha afetado a emissão de push pelo servidor, haja vista que o servidor não está autenticado que aciona o endpoint"*

### Constatação Técnica:
1. **O endpoint acionado pelo servidor NÃO é `push_debug.php`**:
   - Votos, comentários e novos recursos acionam o endpoint interno `classes/api_push_cli.php` via cURL local.
   - O arquivo `classes/api_push_cli.php` **não** valida sessão nem exige usuário logado. Ele valida unicamente se o IP de origem é `127.0.0.1` ou `::1`.
   - Portanto, a validação de usuário logado em `push_debug.php` **não** bloqueou o servidor diretamente.

2. **A Causa Raiz Real: Exclusão do arquivo `classes/vapid.json`**:
   - Quando o commit `1f6e7a3` foi baixado no servidor de produção via `git pull`, o Git interpretou a deleção do arquivo versionado e **apagou fisicamente** o arquivo `classes/vapid.json` do disco do servidor.
   - Tanto `classes/push_helper.php` (função `sendPushNotification`), quanto `api_push.php` (ação `get_vapid`) e `push_debug.php` possuem a seguinte checagem estrita:
     ```php
     $vapidPath = __DIR__ . '/vapid.json';
     if (!file_exists($vapidPath)) {
         error_log("Vapid file does not exist at $vapidPath");
         return false; // Interrompe o envio sem lançar exceção visível
     }
     ```
   - Como `classes/vapid.json` deixou de existir no disco, **todas as tentativas de envio de push falhavam silenciosamente** antes mesmo de contatar os servidores de push (Google FCM / Apple WebPush).

3. **Inconsistência Adicional Detectada em `upsertVoto` (`classes/repositorio.php`)**:
   - O cadastro de votos ainda executava uma chamada manual legada de cURL (`curl -s -H "Host: $domain" -d "$postData" http://127.0.0.1/classes/api_push_cli.php`) em vez de utilizar o helper centralizado `sendPushBackground()`.
   - Essa chamada legada não incluía o link de redirecionamento (`$url`) nem tratava subdiretórios (`$basePath`).

---

## 3. Plano de Correção e Solução Definitiva

1. **Fallback Automático e Auto-Recuperação de Chaves VAPID (`classes/push_helper.php`)**:
   - Criar uma função auxiliar `getVapidConfig()` que tenta ler `classes/vapid.json`.
   - Se o arquivo não existir (ou contiver placeholders vazios), carregar as chaves VAPID padrão oficiais do recMan e persistir automaticamente o arquivo `classes/vapid.json` no disco.
   - Isso garante que o sistema se auto-recupere imediatamente em qualquer ambiente ou após futuros `git pull`, sem derrubar as notificações push.
2. **Atualização em `api_push.php` e `push_debug.php`**:
   - Fazer ambos consumirem a função resiliente `getVapidConfig()`.
3. **Padronização do Envio de Push em Votos (`classes/repositorio.php`)**:
   - Atualizar `upsertVoto()` para invocar `sendPushBackground($titulo, $mensagem, $url)` exatamente como `upsertComentario()` e `novoRecurso()`.
