# Plano de Implementação: Configurações Magnacom e Autenticação Supabase

**Data**: 2026-10-14  
**Status**: Em Execução  
**Objetivo**: Permitir a configuração e persistência das credenciais e tokens do sistema Magnacom/Supabase no banco de dados (`config_sistema`), fornecendo interface em Ferramentas (`/tools`), formulário para colar JSON de tokens manuais (`access_token`, `refresh_token`, `grant`), testes de diagnóstico (login direto com usuário/senha, renovação via refresh token e consulta de notificação) e fallback inteligente para o `.env`.

---

## 1. Motivação e Contexto

A administradora Magnacom está atualizando o sistema de login, adicionando proteção de bot da Cloudflare (Turnstile) que exige `nonce` e `captcha_token`.
No entanto, os endpoints de dados continuam apontando diretamente para a API REST do Supabase (`/rest/v1/notificacoes`, `/storage/v1/object/sign/...`).

Para contornar o bloqueio de login sem quebrar a sincronização no recMan:
1. Permitiremos testar se o login direto com usuário/senha (`grant_type=password`) ainda funciona diretamente na URL base do Supabase (bypassando a nova página web com captcha da Magnacom).
2. Criaremos uma tela em Ferramentas para colar o JSON completo de tokens obtidos externamente:
   ```json
   {
       "access_token": "...",
       "refresh_token": "...",
       "grant": "..."
   }
   ```
3. Salvamos esses dados na tabela `config_sistema` do MySQL local, eliminando a dependência obrigatória do arquivo `magnacom-sistema/.env`.
4. Atualizamos os scripts do recMan para priorizarem o banco de dados e utilizarem o token colado, com renovação automática via `refresh_token` se disponível.

---

## 2. Arquitetura e Componentes

### 2.1 Banco de Dados (`config_sistema`)
Chaves a serem criadas/utilizadas:
- `magnacom_supabase_url`: URL do Supabase (ex: `https://...supabase.co`)
- `magnacom_supabase_anon_key`: Chave pública/anon
- `magnacom_supabase_email`: E-mail de serviço
- `magnacom_supabase_password`: Senha do usuário de serviço
- `magnacom_access_token`: JWT access_token
- `magnacom_refresh_token`: Token para renovação
- `magnacom_grant`: Token de grant / contexto
- `magnacom_tokens_json`: Armazenamento do JSON bruto colado para conferência
- `magnacom_token_updated_at`: Data e hora da última gravação/renovação do token

### 2.2 Helper Centralizado (`classes/repositorio.php`)
- `getMagnacomConfig()`: Retorna as configurações com fallback automático para `magnacom-sistema/.env`.
- `getValidMagnacomToken()`: Resolve um token válido (usando o salvo no banco, renovando com `refresh_token` ou tentando login direto com `email`/`password`).
- `salvarTokensMagnacom($accessToken, $refreshToken, $grant, $rawJson)`: Atualiza as chaves na tabela `config_sistema`.

### 2.3 Rota e View (`palco/configuracoes_magnacom.php`)
- Card no menu Ferramentas (`palco/tools.php`).
- Rota no `index.php?pag=configuracoes_magnacom`.
- Seção 1: Credenciais do Supabase (URL, Anon Key, Email, Senha) + Teste de Login Direto com Diagnóstico detalhado.
- Seção 2: Importação de Tokens via JSON (`access_token`, `refresh_token`, `grant`) com formatação automática e exibição dos valores decodificados (expiração do JWT).
- Seção 3: Diagnóstico de Conexão (Testar renovação com refresh token e Testar busca de 1 notificação no Supabase).
- Seção 4: Painel explicativo sobre Nonce, Cloudflare Turnstile e fluxo de expiração.

### 2.4 Endpoints AJAX (`metodo.php`)
- `metodo=salvarConfigMagnacom`: Salva os campos individuais e o JSON de tokens.
- `metodo=testarLoginMagnacomDirect`: Executa cURL para `/auth/v1/token?grant_type=password` e retorna métricas completas (HTTP status, tempo, headers, corpo/erro).
- `metodo=testarRefreshTokenMagnacom`: Executa cURL para `/auth/v1/token?grant_type=refresh_token`.
- `metodo=testarConsultaNotificacaoMagnacom`: Testa a leitura de 1 registro em `/rest/v1/notificacoes`.

### 2.5 Atualização dos Scripts de Consumo
- `magnacom-sistema/sync_single.php`
- `magnacom-sistema/get_attachments.php`
- `magnacom-sistema/sync_supabase.php`
- `portal/api.php`

---

## 3. Plano de Testes
1. Validar que a tela abre perfeitamente via `index.php?pag=configuracoes_magnacom`.
2. Validar que o botão no `palco/tools.php` direciona corretamente.
3. Testar a colagem de um JSON de teste com validação sintática.
4. Testar o disparo do teste de login direto e captura de resposta detalhada.
5. Garantir que o fallback para `.env` funcione quando os campos no banco estiverem vazios.
