# Plano de Implementação: focusWindow, tag (Agrupamento), actions e PDF Inline

**Data**: 2026-11-17  
**Status**: Pronto para Execução  
**Objetivo**: Implementar reutilização de abas (`focusWindow`), agrupamento inteligente (`tag`), botões de ação na notificação (`actions`) e endpoint de PDF direto inline (`ver_parecer.php`).

---

## 1. Arquivos Afetados

1. **`ver_parecer.php` (Novo arquivo)**:
   - Endpoint leve que recebe `rec=...`, gera ou recupera o PDF compilado do parecer e o serve com `Content-Disposition: inline`.
2. **`portal/api.php`**:
   - Ajustar `action == 'download_parecer'` para usar `Content-Disposition: inline` como padrão, permitindo que o navegador abra diretamente.
3. **`classes/push_helper.php`**:
   - Atualizar `sendPushNotification()` e `sendPushBackground()` para suportar `$tag` e `$actions`.
4. **`classes/api_push_cli.php`**:
   - Receber `tag` e `actions` via POST e repassar para `sendPushNotification()`.
5. **`classes/repositorio.php`**:
   - Em `finalizaParecer()`: Enviar push com `tag` e `actions` (`abrir_recurso` e `ver_parecer`).
   - Em `upsertComentario()`, `upsertVoto()` e `novoRecurso()`: Enviar push com `tag` e action `abrir_recurso`.
6. **`sw.js`**:
   - No evento `push`: Mapear `options.tag`, `options.renotify` e `options.actions`.
   - No evento `notificationclick`: Implementar lógica `focusWindow` com `clients.matchAll()` e roteamento por `event.action`.

---

## 2. Critérios de Verificação
- Push com action exibe botões no Android/Desktop.
- Ao clicar em "Ver Parecer (PDF)", abre a visualização inline do PDF sem forçar download.
- Ao clicar no push com o sistema já aberto em uma aba, foca na aba existente em vez de abrir nova aba.
- Múltiplas notificações do mesmo recurso são agrupadas sob a mesma `tag`.
