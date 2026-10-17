# Diagnóstico e Investigação: Importação de Notificações Magnacom / Supabase

**Data**: 2026-10-17  
**Contexto**: O usuário relatou que ao acessar `json.php?importar_magnacom=1`, aparentemente só estava importando notificações até a `179/2026`, embora no novo app da Magnacom já existam registros até a `407/2026`. O usuário enviou o comando cURL que o novo app usa para consultar o Supabase (`https://fnvrpqvbrtedaqkwzxvf.supabase.co/rest/v1/notificacoes?select=*&year=eq.2026`).

---

## 1. Causa Raiz Identificada

1. **Origem dos Dados em `json.php`**:
   - O arquivo [`json.php`](file:///e:/DEV/recMan/json.php) (linha 169) realizava a busca em um proxy de planilha do Google Sheets:
     `$url = 'https://opensheet.elk.sh/19g62-RqOPWkbzDcloTFB4D7wh4ft_iL-H_zi2ym9-CI/clone';`
   - Inspecionamos o conteúdo retornado por essa URL ao vivo.
   - Constatamos que a planilha Google Sheets parou de ser alimentada pela Magnacon em **20/05/2026**, exatamente na linha correspondente à notificação **179/26** (`APTO: 709, TORRE: F, MULTA, ESTACIONAMENTO IRREGULAR`).
   - A partir da notificação 180/26, as linhas da planilha estavam completamente vazias.

2. **Migração da Magnacom para o Supabase**:
   - A Magnacom abandonou a antiga planilha e migrou toda a operação de notificações e ocorrências para o **Supabase** (`https://fnvrpqvbrtedaqkwzxvf.supabase.co`).
   - Na tabela `notificacoes` do Supabase, os cadastros continuaram sendo realizados e já atingiram a notificação **407/2026**.
   - O projeto recMan já possui classes e helpers para comunicar com o Supabase em [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php) (`getMagnacomConfig()`, `getValidMagnacomToken()`), além da tela [`palco/configuracoes_magnacom.php`](file:///e:/DEV/recMan/palco/configuracoes_magnacom.php) e do script [`magnacom-sistema/sync_supabase.php`](file:///e:/DEV/recMan/magnacom-sistema/sync_supabase.php).

3. **Desconexão entre o Link do Menu e o Supabase**:
   - No menu Ferramentas ([`palco/tools.php`](file:///e:/DEV/recMan/palco/tools.php)), o botão *"Importar Planilha de Multas"* ainda apontava para `json.php?importar_magnacom=1`, que mantinha a chamada à planilha antiga em vez do Supabase.

4. **Pontos de Atenção na API do Supabase (PostgREST)**:
   - O PostgREST possui um limite padrão de 1.000 registros por requisição (`max-rows`).
   - Se uma consulta trouxer anos anteriores juntos sem paginação, pode haver corte caso o total supere 1.000.
   - A autenticação via usuário e senha direto na web foi restringida por Cloudflare Turnstile, mas o recMan possui suporte a JWT / Refresh Token em `getValidMagnacomToken()`.

---

## 2. Solução Proposta

1. **Refatorar [`json.php`](file:///e:/DEV/recMan/json.php)**:
   - Substituir a leitura legada da planilha `opensheet.elk.sh` pela consulta à API REST do Supabase (`/rest/v1/notificacoes`).
   - Utilizar a infraestrutura de credenciais e tokens centralizada (`getMagnacomConfig()`, `getValidMagnacomToken()`).
   - Implementar paginação em lote (ex: `limit=1000&offset=...`) e suporte ao filtro de ano (ex: `?ano=2026` ou padrão ano corrente/todos).
   - Mapear os campos da tabela remota (`numero`, `year`, `numero_ordem`, `torre`, `apto`, `tipo`, `infracao`, `tipificacao`, `confeccao`, `envio`, `data_ocorrido`, `resultado`, `cobrado_realizado`, `parcelamento`, `recebimento_fisico`, `conf_leitura_digital`).
   - Gerar feedback visual moderno com resumo completo de importação (de 1 até 407/2026+), exibindo totais de notificações e datas de ciência/retirada sincronizadas.

2. **Aprimorar [`magnacom-sistema/sync_supabase.php`](file:///e:/DEV/recMan/magnacom-sistema/sync_supabase.php)**:
   - Atualizar a autenticação para usar `getValidMagnacomToken()` com fallback de refresh token e login direto.
   - Adicionar paginação resiliente (`limit` e `offset`) para evitar truncamento em 1.000 registros.

3. **Atualizar [`palco/tools.php`](file:///e:/DEV/recMan/palco/tools.php)**:
   - Atualizar o rótulo da ferramenta para *"Sincronizar Notificações (Magnacom / Supabase)"*.
   - Incluir o card para *"Configurações Magnacom & Tokens"* caso ainda não esteja listado.
