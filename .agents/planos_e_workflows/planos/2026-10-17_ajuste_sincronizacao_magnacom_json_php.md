# Plano de Implementação: Sincronização de Notificações Magnacom / Supabase em `json.php`

**Data**: 2026-10-17  
**Alvo**: [`json.php`](file:///e:/DEV/recMan/json.php), [`magnacom-sistema/sync_supabase.php`](file:///e:/DEV/recMan/magnacom-sistema/sync_supabase.php) e [`palco/tools.php`](file:///e:/DEV/recMan/palco/tools.php)

---

## 1. Objetivo
Permitir que o fluxo de importação da Magnacom (`json.php?importar_magnacom=1`) deixe de consultar a planilha Google Sheets legada (que parou em 179/2026) e passe a consumir diretamente a API REST do Supabase (`/rest/v1/notificacoes`), importando todas as notificações até 407/2026 e posteriores, com suporte a token JWT renovável, paginação e feedback visual detalhado.

---

## 2. Etapas de Execução

### Etapa 1: Refatoração de `json.php`
- Incluir `classes/repositorio.php`.
- Integrar funções `getMagnacomConfig()` e `getValidMagnacomToken()`.
- Obter parâmetros opcionais via `$_GET` (`ano`, ex: `ano=2026` ou padrão todos/>=2025).
- Realizar requisições cURL paginadas ao endpoint `https://fnvrpqvbrtedaqkwzxvf.supabase.co/rest/v1/notificacoes?select=*` com `limit=1000&offset=X` e ordenação `order=id.asc`.
- Fazer o parse e normalização de cada registro:
  - Extrair `numero` e `ano` a partir de `row['numero']` ou `row['year']` / `row['numero_ordem']`.
  - Normalizar `torre` e `unidade` (apto).
  - Normalizar tipo de penalidade (MULTA, ADVERTENCIA).
  - Mapear tipificação para notação de artigo do regimento (`parseTipificacaoToNotacao`).
  - Mapear datas (`confeccao`, `envio`, `data_ocorrido`).
  - Mapear cobrança e parcelamento (`cobrado_realizado`, `parcelamento`).
  - Executar `upsertNotificacao($linha)`.
  - Mapear recebimento físico / leitura digital (`recebimento_fisico`, `conf_leitura_digital`) e executar `upsertDatasDeRetirada($dados)`.
- Renderizar uma interface HTML moderna com Materialize CSS informando o progresso, total de registros recebidos, intervalo de notificações processadas (ex: 1/2026 até 407/2026), quantidade de notificações e de datas de retirada salvas com sucesso.

### Etapa 2: Aprimoramento de `magnacom-sistema/sync_supabase.php`
- Atualizar a obtenção de token para priorizar `getValidMagnacomToken()` (com suporte ao token manual e refresh token salvo no banco).
- Adicionar paginação resiliente com `limit` e `offset`.

### Etapa 3: Atualização de `palco/tools.php`
- Renomear o botão "Importar Planilha de Multas" para "Sincronizar Notificações (Magnacom / Supabase)".
- Adicionar card para "Configurações Magnacom & Supabase" (`index.php?pag=configuracoes_magnacom`).
