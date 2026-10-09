# Walkthrough: Ajustes na Notificação Push de Novo Comentário

**Data**: 2026-10-08  
**Status**: Concluído  

---

## 🛠️ Alterações Efetuadas

### 1. Formato Número/Ano no Lugar do ID do Recurso
- **Arquivo**: [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php)
  - Na função `upsertComentario($dados)`, antes o push referenciava o ID autoincrement numérico do banco (`$id_recurso`), resultando em notificações como `Conselheiro comentou no recurso 54:` e o link `rec=54`.
  - Agora, o sistema consulta `getRecursoById($id_recurso)` e utiliza `$numeroRecurso = $recurso['numero'] ?? $id_recurso`, que traz o formato oficial `numero/ano` (ex: `045/2026`).
  - O título da notificação passou a incluir o identificador: `Novo Comentário ($numeroRecurso)`.
  - O corpo da notificação exibe: `$nome_usuario comentou no recurso $numeroRecurso:\n$resumo_comentario`.
  - A URL do push agora redireciona com o parâmetro oficial correto: `/index.php?pag=recurso&rec=` com `urlencode($numeroRecurso)`.

### 2. Substituição da Figurinha pelo Novo Logo Oficial do Conselho
- **Arquivos**:
  - [`storage/icons/logo-conselho.png`](file:///e:/DEV/recMan/storage/icons/logo-conselho.png): criado a partir de `favicon/logo-conselho/256-256.png`.
  - [`classes/push_helper.php`](file:///e:/DEV/recMan/classes/push_helper.php):
    - O ícone padrão passou de `conselho-toon.webp` para `https://mini.davinunes.eti.br/storage/icons/logo-conselho.png`.
    - Função `sendPushBackground` recebeu parâmetro opcional `$icon = null` para possibilitar personalização quando necessário.
  - [`classes/api_push_cli.php`](file:///e:/DEV/recMan/classes/api_push_cli.php):
    - Recebe o parâmetro `icon` via POST e repassa para `sendPushNotification`.
  - [`sw.js`](file:///e:/DEV/recMan/sw.js):
    - Atualizado o fallback de `icon` para o logo oficial e adicionado fallback de `badge`.

### 3. Ampliação da Quantidade de Caracteres na Prévia
- **Arquivo**: [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php)
  - O limite anterior de apenas 100 caracteres com `substr` foi ampliado para **280 caracteres** com `mb_substr` seguro para UTF-8 e emojis, adicionando reticências (`...`) caso ultrapasse o limite.
- **Arquivo**: [`portal/api.php`](file:///e:/DEV/recMan/portal/api.php)
  - Quando o morador/condômino adiciona comentário no portal, a notificação push para os conselheiros agora também inclui uma prévia de até **280 caracteres** do comentário digitado (`O condômino adicionou ao recurso [numero/ano]: [resumo]`), em vez do texto fixo genérico anterior.

---

## 🔍 Como Validar
1. Acesse o sistema recMan e abra a tela de um recurso existente.
2. Poste um comentário de teste longo (com mais de 100 caracteres).
3. Ao receber o push:
   - Verifique que o título e a mensagem exibem o número no formato oficial `numero/ano` (ex: `045/2026`).
   - Verifique que o ícone exibido na notificação do sistema operacional é a nova marca oficial do Conselho.
   - Verifique que a prévia da mensagem exibe o texto expandido (até 280 caracteres).
   - Ao tocar/clicar na notificação, certifique-se de que a tela do recurso abre perfeitamente.
