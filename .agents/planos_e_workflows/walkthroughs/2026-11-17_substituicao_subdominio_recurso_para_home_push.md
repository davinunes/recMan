# Walkthrough: Substituição de Subdomínio `recurso.` por `home.` nos Push Notifications

## Objetivo
Garantir que todas as notificações push (e ações interativas) disparadas quando um condômino apresenta recurso ou envia mensagens no portal público direcionem os conselheiros para o subdomínio administrativo `home.`, preservando os hosts originais em qualquer outro cenário (`mini.`, `home.`, etc.).

---

## Modificações Realizadas

### 1. `portal/api.php`
- Adicionada a função `resolverHostPushConselheiro($host = null)` que inspeciona o host recebido na requisição e substitui o prefixo `recurso.` por `home.`, mantendo qualquer outro host inalterado.
- Atualizados os três disparos de push:
  - **Novo Recurso** (`action == 'submit'`): URL principal e action "Abrir Defesa".
  - **Novo Comentário** (`action == 'add_comentario'`): URL principal e action "Ver Comentário".
  - **Novos Anexos** (`action == 'add_attachments'`): URL principal e action "Ver Anexos".
- Uniformizado o cálculo de `$basePathUrl` para respeitar o caminho base da aplicação.

### 2. `classes/push_helper.php`
- Implementada normalização preventiva na montagem de `$finalUrl` e em cada `$actUrl`:
  - Se a URL iniciar com `https://recurso.` ou `http://recurso.`, converte para o correspondente com `home.`.
  - Se iniciar diretamente com `recurso.`, troca para `home.`.
  - Preserva intactas as URLs que não começam com `recurso.`.

---

## Verificação e Integridade
- Verificado via `git diff` que as alterações foram cirúrgicas e sem efeitos colaterais.
- A integridade dos scripts foi preservada sem caracteres BOM.
