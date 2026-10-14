# Walkthrough: Configurações Magnacom e Autenticação Supabase

**Data**: 2026-10-14  
**Status**: Concluído e Disponível para Teste

---

## 1. Resumo das Modificações

Implementamos a infraestrutura completa para gerenciar as credenciais da **Magnacom/Supabase** diretamente pelo banco de dados (`config_sistema`) e pela interface web em Ferramentas (`/tools`), eliminando a dependência do arquivo `.env` e viabilizando o contorno do novo Captcha da Cloudflare Turnstile através de tokens manuais e diagnósticos em tempo real.

---

## 2. Arquivos Criados e Modificados

| Arquivo | Descrição das Modificações |
| :--- | :--- |
| [`classes/repositorio.php`](file:///d:/dev/recMan/classes/repositorio.php) | Adicionadas as funções utilitárias `getMagnacomConfig()`, `salvarTokensMagnacom()`, `renovarTokenMagnacomViaRefresh()` e `getValidMagnacomToken()`, com resolução em cascata (Banco `config_sistema` -> Fallback `.env`). |
| [`metodo.php`](file:///d:/dev/recMan/metodo.php) | Adicionados os endpoints AJAX: `salvarConfigMagnacom`, `salvarTokensMagnacomJson`, `testarLoginMagnacomDirect`, `testarRefreshTokenMagnacom` e `testarConsultaNotificacaoMagnacom`. |
| [`palco/configuracoes_magnacom.php`](file:///d:/dev/recMan/palco/configuracoes_magnacom.php) | Nova tela completa de gerenciamento: formulário de credenciais base, teste de login direto, formulário para colar JSON de tokens, renovação via refresh token e diagnóstico de leitura da API. |
| [`palco/tools.php`](file:///d:/dev/recMan/palco/tools.php) | Adicionado o card **"Configurações Magnacom"** na seção *Sincronização*. |
| [`index.php`](file:///d:/dev/recMan/index.php) | Adicionada a rota `pag=configuracoes_magnacom`. |
| [`magnacom-sistema/sync_single.php`](file:///d:/dev/recMan/magnacom-sistema/sync_single.php) | Atualizado para usar o token e configurações centralizadas, com retry automático via refresh token se der HTTP 401. |
| [`magnacom-sistema/get_attachments.php`](file:///d:/dev/recMan/magnacom-sistema/get_attachments.php) | Atualizado para carregar configurações do banco e resolver tokens automaticamente. |
| [`magnacom-sistema/sync_supabase.php`](file:///d:/dev/recMan/magnacom-sistema/sync_supabase.php) | Atualizado para consultar o banco e validar tokens via `getValidMagnacomToken()`. |
| [`portal/api.php`](file:///d:/dev/recMan/portal/api.php) | Função `sincronizarNotificacaoSupabase` simplificada para usar `getMagnacomConfig()` e `getValidMagnacomToken()`. |

---

## 3. Instruções de Teste e Uso

1. **Acessar a Nova Tela**:
   - No menu superior ou lateral, acesse **Ferramentas** (`index.php?pag=tools`).
   - Na categoria **Sincronização**, clique no novo card âmbar: **"Configurações Magnacom"** (ou acesse diretamente `index.php?pag=configuracoes_magnacom`).

2. **Testar se o Login Direto ainda Funciona**:
   - Preencha (ou confira) a URL do Supabase, Anon Key, E-mail e Senha.
   - Clique em **"Testar Login Direto"**.
   - O painel exibirá em tempo real o código HTTP, a latência em milissegundos e se o Supabase autorizou a conexão direta ou se exigiu captcha.
   - Se funcionar, com a opção *"Salvar tokens automaticamente..."* marcada, o novo `access_token` e `refresh_token` já são salvos no banco!

3. **Colar Tokens Manuais do JSON (Se houver Captcha)**:
   - Se o login direto falhar devido ao Turnstile da Cloudflare, autentique-se no navegador (DevTools > Network) e copie o JSON de resposta:
     ```json
     {
         "access_token": "eyJhLg...",
         "refresh_token": "7c6kobnpgt72...",
         "grant": "eyJpfWw..."
     }
     ```
   - Cole na caixa de texto **"Objeto JSON com Tokens"** e clique em **"Salvar Tokens do JSON"**.
   - O sistema extrairá e persistirá os tokens imediatamente.

4. **Validar a Leitura na API**:
   - Clique no botão **"Testar Leitura da API (1 Notificação)"**.
   - O sistema fará um teste real na tabela `notificacoes` do Supabase para confirmar se o token está ativo e autorizado.
