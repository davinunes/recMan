# Walkthrough: Migração e Sincronização de Notificações Magnacom / Supabase em `json.php`

**Data**: 2026-10-17  
**Status**: Concluído com Sucesso

---

## 1. Motivação e Diagnóstico

O usuário relatou que ao acessar `json.php?importar_magnacom=1`, o feedback indicava que as notificações só estavam sendo importadas até a `179/2026`, enquanto no novo aplicativo da administradora Magnacom já constavam registros até a `407/2026`.

### Causa Raiz
- O arquivo [`json.php`](file:///e:/DEV/recMan/json.php) ainda realizava requisição para um proxy de planilha do Google Sheets (`https://opensheet.elk.sh/19g62-RqOPWkbzDcloTFB4D7wh4ft_iL-H_zi2ym9-CI/clone`).
- A inspeção em tempo real revelou que a Magnacom parou de preencher essa planilha antiga em **20/05/2026**, exatamente na notificação `179/26`. Todas as linhas subsequentes da planilha estavam em branco.
- A Magnacom migrou para o backend **Supabase** (`https://fnvrpqvbrtedaqkwzxvf.supabase.co`), onde a tabela `notificacoes` continuou a receber todos os lançamentos normais até a `407/2026` e adiante.

---

## 2. Alterações Realizadas

### 2.1 Refatoração de [`json.php`](file:///e:/DEV/recMan/json.php)
1. **Conexão Direta ao Supabase**:
   - Substituída a chamada da planilha legada pela API REST do Supabase (`/rest/v1/notificacoes?select=*`).
   - Integrado com `getMagnacomConfig()` e `getValidMagnacomToken()` para obtenção transparente de credenciais e tokens JWT salvos no banco.
2. **Paginação em Lote**:
   - Adicionada paginação resiliente (`limit=1000&offset=X&order=id.asc`) para contornar o limite padrão de linhas do PostgREST e garantir que todos os registros (inclusive centenas de notificações de 2026 e 2025) sejam lidos sem truncar.
3. **Mapeamento Completo de Campos**:
   - Número e ano (`row['numero']`, `row['year']`, `row['numero_ordem']`).
   - Torre e Unidade sanitizados (`row['torre']`, `row['apto']`).
   - Tipificação convertida para notação do regimento via `parseTipificacaoToNotacao()`.
   - Datas de confecção, envio e ocorrido.
   - Status do parecer / penalidade e cobrança.
   - Sincronização automática das datas de ciência/retirada (`upsertDatasDeRetirada()`) a partir de `recebimento_fisico` ou `conf_leitura_digital`.
4. **Interface e Feedback Visual**:
   - Criada tela informativa moderna com cards estatísticos: Total Consultado, Notificações Salvas, Ciências/Retiradas Salvas e Último Registro (ex: `407/2026`).
   - Suporte a filtros via URL (ex: `json.php?importar_magnacom=1&ano=2026` ou padrão `>=2025`).

### 2.2 Centralização de `parseTipificacaoToNotacao()` em [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php)
- Função disponibilizada no escopo global para reaproveitamento tanto em `json.php`, `sync_supabase.php` e `sync_single.php`.

### 2.3 Paginação em [`magnacom-sistema/sync_supabase.php`](file:///e:/DEV/recMan/magnacom-sistema/sync_supabase.php)
- Aprimorada a função `getSupabaseNotifications()` para realizar paginação em lote de 1.000 em 1.000 registros, tornando as execuções de cron ou CLI imunes ao limite de linhas do Supabase.

### 2.4 Atualização de [`palco/tools.php`](file:///e:/DEV/recMan/palco/tools.php)
- Atualizado o rótulo do menu de "Importar Planilha de Multas" para *"Sincronizar Notificações (Magnacom)"*.
- Mantido atalho para *"Configurações Magnacom"* para ajuste de tokens e credenciais.

---

## 3. Como Utilizar

- **Sincronização Completa (Padrão 2025 e 2026)**:
  Acesse `json.php?importar_magnacom=1` ou clique no botão **Sincronizar Notificações (Magnacom)** no painel de ferramentas (`palco/tools.php`).
- **Sincronizar estritamente o ano de 2026**:
  Acesse `json.php?importar_magnacom=1&ano=2026`.
- **Gerenciar Tokens JWT do Supabase**:
  Acesse `index.php?pag=configuracoes_magnacom` para colar tokens novos ou testar renovação via refresh token.
