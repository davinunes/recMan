# Walkthrough: Correção no Carregamento de Anexos do Supabase (Magnacom)

**Data**: 2026-09-30  
**Arquivo Modificado**: [`palco/detalheRecurso.php`](file:///e:/DEV/recMan/palco/detalheRecurso.php)

---

## Alterações Realizadas

### `palco/detalheRecurso.php`
- **Reativação do Botão de Sincronização**: Descomentada a linha PHP que insere o botão `#btnSyncSupabase` no rodapé da página de detalhes do recurso.
- **Robustez no Carregamento via AJAX**: Alterada a declaração da variável `rec` na consulta aos anexos do Supabase para ter fallback automático com a variável `$result['numero']`:
  ```javascript
  var rec = $('#btnSyncSupabase').attr('data-rec') || '<?php echo htmlspecialchars($result['numero'], ENT_QUOTES, 'UTF-8'); ?>';
  ```

---

## Como Validar no Ambiente
1. Acesse o recurso desejado (ex: `index.php?pag=recurso&rec=344/2026`).
2. Verifique se a seção **"Anexos da Notificação (Sistema Magnacom)"** é exibida automaticamente quando a notificação possuir arquivos vinculados no Supabase.
3. Verifique a presença do botão **"Sincronizar Supabase"** junto aos botões de ação e teste o clique para atualizar dados e anexos da notificação.
