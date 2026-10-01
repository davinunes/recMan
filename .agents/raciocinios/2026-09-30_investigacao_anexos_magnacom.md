# Investigação e Diagnóstico: Anexos da Notificação (Sistema Magnacom / Supabase)

**Data**: 2026-09-30  
**Contexto**: O usuário relatou que na tela de detalhes do recurso (`index.php?pag=recurso&rec=344/2026` / `palco/detalheRecurso.php`) os anexos de notificações vindos da Magnacom / Supabase não estavam sendo carregados.

## Hipóteses Investigadas
1. **Falha nos scripts da pasta `magnacom-sistema/`**: Investigado `magnacom-sistema/get_attachments.php` e `sync_single.php`. Ambas as rotas utilizavam a sessão PHP e token JWT Supabase.
2. **Remoção ou desativação acidental nos últimos commits**: Verificado o histórico de commits do repositório (`git log -p -S "btnSyncSupabase"`).

## Causa Raiz Identificada
No arquivo [`palco/detalheRecurso.php`](file:///e:/DEV/recMan/palco/detalheRecurso.php):
- Na linha 1552, o botão HTML `<button id="btnSyncSupabase" data-rec="...">` estava **comentado no código PHP** (`// echo '<button ...'`).
- O script JavaScript responsável por chamar a API via AJAX (`get_attachments.php`) dependia estritamente da presença do atributo `data-rec` daquele botão:
  ```javascript
  var rec = $('#btnSyncSupabase').attr('data-rec');
  if (rec) {
      $.ajax({ url: 'magnacom-sistema/get_attachments.php', ... });
  }
  ```
- Como o elemento `#btnSyncSupabase` não existia no DOM, `rec` resultava em `undefined`, a condição `if (rec)` retornava `false` e a requisição AJAX para buscar os anexos remotos no Supabase/Magnacom **nunca era disparada**.

## Solução Aplicada
1. **Descomentado e atualizado o botão `#btnSyncSupabase`**:
   - Reativado o botão "Sincronizar Supabase" na barra de ações de `detalheRecurso.php`.
2. **Proteção e Fallback no JavaScript**:
   - Adicionado fallback em JS para extrair o número do recurso diretamente via PHP caso o elemento não exista no DOM por qualquer motivo futuramente:
     ```javascript
     var rec = $('#btnSyncSupabase').attr('data-rec') || '<?php echo htmlspecialchars($result['numero'], ENT_QUOTES, 'UTF-8'); ?>';
     ```
