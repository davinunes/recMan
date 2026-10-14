# Raciocínio Diagnóstico e Arquitetura: Transição da Autenticação Magnacom

**Data**: 2026-10-14  
**Contexto**: O usuário relatou que a Magnacom alterou a URL de login do sistema deles e implementou proteção contra bots com Cloudflare Turnstile/Captcha (exigindo envio de `email`, `password`, `nonce` e `captcha_token`), mas que a API de consulta de recursos continua apontando diretamente para o Supabase. O usuário propôs criar uma interface na tela de Ferramentas (`/tools`) para permitir:
1. Deixar de depender do arquivo estático `.env` e salvar credenciais no banco (`config_sistema`).
2. Colar um objeto JSON com tokens obtidos externamente:
   ```json
   {
       "access_token": "eyJhLg",
       "refresh_token": "7c6kobnpgt72",
       "grant": "eyJpfWw"
   }
   ```
3. Testar se o login direto com usuário e senha ainda funciona sem captcha diretamente no endpoint nativo do Supabase (`/auth/v1/token?grant_type=password`).
4. Esclarecer o conceito técnico de `nonce`.

---

## 1. O que é "Nonce" neste contexto?

"Nonce" significa **"Number used ONCE"** (número utilizado uma única vez). No fluxo com Cloudflare Turnstile / Captcha:
1. O backend ou o script do Turnstile gera um identificador único, aleatório e efêmero (`nonce`), associando-o à sessão do navegador ou formulário aberto.
2. Quando o usuário resolve o desafio (ou o Turnstile resolve de forma transparente e passiva), a Cloudflare assina a solução associando o `captcha_token` àquele `nonce` específico.
3. Ao enviar `{ email, password, nonce, captcha_token }`, o servidor de login valida se:
   - O `nonce` é válido, foi gerado recentemente e **ainda não foi consumido**.
   - O `captcha_token` confere com o desafio daquele `nonce`.
   - Imediatamente após a validação, o `nonce` é destruído no servidor, impedindo **ataques de repetição (Replay Attacks)** — ou seja, ninguém pode capturar os parâmetros na rede e reenviar os mesmos tokens para logar novamente.

---

## 2. Hipótese Técnica: Supabase Nativo vs. Proxy/Frontend da Magnacom

Existem dois cenários prováveis na infraestrutura da Magnacom:
1. **Cenário A (Proxy Customizado no Frontend)**: A Magnacom criou uma nova tela de login em um backend próprio (ex: Node/Next.js/Cloudflare Workers) que exige o captcha Turnstile para só então emitir ou encaminhar a requisição. Se o projeto Supabase por baixo dos panos **não** ativou a proteção estrita de captcha no Supabase Auth Dashboard, a chamada direta via cURL para `https://[project-ref].supabase.co/auth/v1/token?grant_type=password` continuará funcionando perfeitamente sem captcha!
2. **Cenário B (Captcha ativado no Supabase Auth)**: Se o Supabase Auth do projeto estiver com verificação de captcha Cloudflare ativada, a requisição direta sem o captcha retornará erro HTTP 400 (`captcha_failed` / `captcha verification failed`). Nesse caso, a alternativa viável para automações é utilizar os tokens manuais (`access_token`, `refresh_token`, `grant`) obtidos via DevTools no navegador, e utilizar o endpoint `/auth/v1/token?grant_type=refresh_token`, pois o Supabase normalmente dispensa verificação de captcha em requisições de renovação de refresh token!

---

## 3. Decisão de Design e Implementação

1. **Persistência no Banco (`config_sistema`)**:
   - Chaves: `magnacom_supabase_url`, `magnacom_supabase_anon_key`, `magnacom_supabase_email`, `magnacom_supabase_password`, `magnacom_access_token`, `magnacom_refresh_token`, `magnacom_grant`, `magnacom_tokens_json`, `magnacom_token_updated_at`.
   - Se os valores no banco estiverem vazios, lê o arquivo `.env` como fallback de segurança.
2. **Interface em `palco/configuracoes_magnacom.php`**:
   - Card em `palco/tools.php` com ícone `cloud_sync` / `vpn_key`.
   - Rota no roteador `index.php`.
   - Formulário para credenciais base + botão "Testar Login Direto".
   - Textarea inteligente para colar o JSON dos tokens manuais, com parsing e salvamento imediato.
   - Diagnósticos: Teste de Login com Senha, Teste de Renovação via Refresh Token e Teste de Leitura da tabela `notificacoes`.
3. **Compatibilidade dos Consumidores**:
   - Centralizar a resolução de token em `classes/repositorio.php` para que `sync_single.php`, `get_attachments.php`, `sync_supabase.php` e `portal/api.php` usem o token persistido no banco com fallback transparente.
