# Raciocínio de Diagnóstico: Investigação de Notificação Push Desconhecida ("Teste" / "Corpo")

**Data**: 2026-11-03  
**Contexto**: O usuário recebeu 2 notificações push simultâneas às 14:48 do domínio `mini.davinunes.eti.br` com título "Teste" e corpo "Corpo", acompanhadas do logotipo oficial do Conselho Fiscal, sem ter realizado nenhuma ação no sistema. Foi solicitada a investigação de possíveis testes automatizados, vazamento de tokens (GitHub) ou exploração via CVEs do PHP.

---

## 1. Localização no Código-Fonte

Realizada busca textual em todo o repositório por `['title' => 'Teste', 'body' => 'Corpo']` e referências a "Corpo" em rotinas de push.

- **Resultado**: Foi localizado um único ponto em todo o projeto:
  - Arquivo: [`push_debug.php`](file:///e:/DEV/recMan/push_debug.php#L58)
  - Linha 58:
    ```php
    $payload = json_encode(['title' => 'Teste', 'body' => 'Corpo', 'url' => '/']);
    ```

---

## 2. Análise de Vulnerabilidade e Causa Raiz em `push_debug.php`

1. **Ausência de Autenticação / Controle de Acesso**:
   - `push_debug.php` inicia com `session_start()`, porém **não verifica** se o usuário está logado (`$_SESSION['user_id']`), nem valida chave secreta (token de query string ou IP).
2. **Execução Automática no GET**:
   - Assim que o arquivo recebe qualquer requisição HTTP GET (`https://mini.davinunes.eti.br/push_debug.php`), ele consulta **todas** as inscrições ativas da tabela `push_subscriptions`:
     ```php
     $res = DBExecute('SELECT * FROM push_subscriptions');
     $subs = mysqli_fetch_all($res, MYSQLI_ASSOC);
     ```
   - Em seguida, itera sobre cada uma e envia o push:
     ```php
     foreach ($subs as $sub) {
         $subscription = \Minishlink\WebPush\Subscription::create([...]);
         $report = $webPush->sendOneNotification($subscription, $payload);
     }
     ```
3. **Vazamento Adicional de Dados (Information Disclosure)**:
   - O arquivo faz `print_r($subs)` diretamente no HTML de saída, expondo publicamente os dados de todos os dispositivos cadastrados (`endpoint`, `p256dh`, `auth`, `user_id`).

---

## 3. Por que foram recebidos DOIS pushes às 14:48?

Duas hipóteses técnicas (que podem ocorrer isoladas ou combinadas):
1. **Disparo duplo no banco de dados**: Caso o dispositivo do usuário possua dois registros ativos na tabela `push_subscriptions` (ex: subscrições duplicadas após atualização de navegador ou reinstalação do PWA). Um único acesso a `push_debug.php` dispara para cada linha retornada.
2. **Duas requisições HTTP recebidas**: Scanners de diretórios (web crawlers, bots de busca, bots de vulnerabilidades como Shodan/Censys/dirsearch/feroxbuster), ou recarregamento acidental de aba (F5 duplo ou pré-carregamento de links por navegadores mobile).

---

## 4. Análise de Tokens, GitHub e CVEs do PHP

1. **Tokens no Repositório (VAPID Keys)**:
   - O arquivo [`classes/vapid.json`](file:///e:/DEV/recMan/classes/vapid.json) está commitado no repositório Git com a chave privada (`privateKey`) e pública (`publicKey`).
   - Se o repositório for público, qualquer pessoa pode ver a chave VAPID privada. Contudo, para enviar um push direto ao Google FCM/Apple WebPush sem o servidor do recMan, o atacante precisaria do `endpoint` e chaves ECDH do celular do usuário.
   - Porém, como `push_debug.php` estava desprotegido, qualquer atacante ou bot simplesmente acessando a URL pública obteve a execução do push pelo próprio servidor e a listagem de todos os endpoints cadastrados.
2. **Testes Automatizados**:
   - Nenhum cron interno (como `cron_vds_sync.php`) referencia `push_debug.php`.
   - Caso o usuário utilize Uptime Monitor externo (UptimeRobot, Pingdom, etc.), é preciso verificar se a URL monitorada não foi apontada por engano para `push_debug.php`.
3. **Exploração de CVEs do PHP**:
   - Não foi necessário RCE nem exploração de CVE do interpretador PHP (como CVE-2024-4577 de CGI Argument Injection). Trata-se de um endpoint de depuração deixado exposto diretamente na raiz pública (`/push_debug.php`).

---

## 5. Recomendações Imediatas

1. **Remover ou restringir `push_debug.php`**: Exigir autenticação de conselheiro / admin ou deletar o arquivo do servidor de produção.
2. **Rotacionar o par de chaves VAPID (`classes/vapid.json`)**: Gerar novo par caso o repo seja público ou se terceiros acessaram `push_debug.php` e leram o dump de `$subs`.
3. **Verificar os logs de acesso do servidor web**: Checar em `/var/log/nginx/` (ou Apache/Caddy) as requisições recebidas para `/push_debug.php` às 14:48 para identificar o IP e o `User-Agent`.

---

## 6. Ações Implementadas (2026-11-03)

1. **Autenticação e Proteção em `push_debug.php`**:
   - Inclusão de trava de sessão `empty($_SESSION['user_id'])` retornando HTTP 403 para acessos não autenticados.
   - O disparo de push não ocorre mais via GET automático. Agora exige clique intencional em formulário POST (`disparar_teste`).
   - Omitidos os segredos brutos (`p256dh` e `auth`) da listagem na tela, exibindo apenas resumo do endpoint e IDs para fins de diagnóstico.
2. **Ignorar Segredos no Git**:
   - `classes/vapid.json` desvinculado do versionamento via `git rm --cached classes/vapid.json`.
   - Adicionadas regras no `.gitignore` raiz e `classes/.gitignore` para `classes/vapid.json` e `classes/key.php`.
   - Criado template `classes/vapid.json.example` com placeholders seguros.


