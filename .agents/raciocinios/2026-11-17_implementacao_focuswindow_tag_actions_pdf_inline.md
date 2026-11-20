# Raciocínio de Implementação: focusWindow, tag (Agrupamento), actions e Visualização de PDF Inline

**Data**: 2026-11-17  
**Contexto**: O usuário solicitou a evolução da infraestrutura de Web Push no recMan implementando três capacidades avançadas da Web Notifications API:
1. `focusWindow`: Reutilização inteligente de abas abertas, evitando a proliferação de abas duplicadas no celular.
2. `tag` e `renotify`: Agrupamento inteligente de notificações por recurso, evitando poluição visual na gaveta de notificações do Android.
3. `actions`: Botões de ação rápida na própria notificação (ex: "Abrir Recurso" e "Ver Parecer (PDF)").
4. Exibição de PDF inline: Ajuste da entrega de PDFs para visualização imediata no navegador sem forçar download via `Content-Disposition: inline`.

---

## 1. Análise Técnica dos Componentes

### 1.1 `focusWindow` no Service Worker (`sw.js`)
- **Comportamento anterior**: `notificationclick` executava incondicionalmente `clients.openWindow(url)`. A cada push clicado, o navegador mobile abria uma nova aba.
- **Nova lógica**:
  - `clients.matchAll({ type: 'window', includeUncontrolled: true })` busca janelas/abas da mesma origem (`location.origin`).
  - Se encontrar uma aba aberta, executa `client.focus()` e `client.navigate(targetUrl)`.
  - Se nenhuma aba estiver aberta, faz o fallback para `clients.openWindow(targetUrl)`.

### 1.2 `tag` e `renotify` (Agrupamento por Recurso)
- Atribui ao payload uma tag estruturada: `tag: 'rec-' + sanitize(numeroRecurso)`.
- Atribui `renotify: true`.
- Resultado no Android: Notificações do mesmo recurso (votos subsequentes, novos comentários) atualizam o mesmo card de notificação em vez de criar 5 ou 6 cards empilhados.

### 1.3 `actions` (Botões de Ação)
- Ações definidas no push:
  - Notificação de Parecer:
    - Ação `abrir_recurso`: Rótulo "Abrir Recurso" -> Redireciona para `/index.php?pag=recurso&rec=...`.
    - Ação `ver_parecer`: Rótulo "Ver Parecer (PDF)" -> Redireciona para `/ver_parecer.php?rec=...`.
  - Notificação de Comentário:
    - Ação `abrir_recurso`: Rótulo "Ver Comentário".
  - Notificação de Voto:
    - Ação `abrir_recurso`: Rótulo "Ver Recurso".
- No `sw.js`, o evento `notificationclick` inspeciona `event.action`:
  - Se o usuário tocar no botão da action correspondente, navega para a URL da ação.
  - Se tocar no corpo do card, navega para a URL principal do recurso.

### 1.4 Visualização do PDF Inline no Android e Navegadores
- **A questão do cabeçalho**:
  - `Content-Disposition: attachment; filename="..."`: Força o navegador a salvar o arquivo na pasta de Downloads.
  - `Content-Disposition: inline; filename="..."`: Indica que o navegador deve renderizar o PDF diretamente na aba/janela atual.
- **Implementação**:
  - Criar o endpoint dedicado [`ver_parecer.php`](file:///d:/dev/github/recMan/ver_parecer.php) que gera/recupera o parecer e entrega o fluxo com `Content-Type: application/pdf` e `Content-Disposition: inline`.
  - Atualizar [`portal/api.php`](file:///d:/dev/github/recMan/portal/api.php) na ação `download_parecer` para `Content-Disposition: inline`.
