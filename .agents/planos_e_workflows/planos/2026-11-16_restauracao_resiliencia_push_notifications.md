# Plano de Implementação: Restauração e Resiliência do Motor de Notificações Push

**Data**: 2026-11-16  
**Status**: Pronto para Execução  
**Objetivo**: Restaurar imediatamente o envio de notificações push (votos, comentários e novos recursos) e blindar o sistema contra exclusões acidentais do arquivo `vapid.json`, unificando também a rotina de envio de votos.

---

## 1. Alterações Propostas

### 1.1 `classes/push_helper.php`
- Implementar a função `getVapidConfig()`:
  - Verifica se `classes/vapid.json` existe e possui `publicKey` e `privateKey` preenchidas sem placeholders.
  - Caso não exista ou esteja corrompido, utiliza as chaves de produção registradas no recMan e auto-recria o arquivo `classes/vapid.json` com `file_put_contents`.
- Atualizar a função `sendPushNotification()` para obter as credenciais via `getVapidConfig()`.
- Garantir que a chave local `classes/vapid.json` seja restaurada localmente no disco.

### 1.2 `api_push.php`
- Na ação `get_vapid`:
  - Incluir `classes/push_helper.php` e usar `getVapidConfig()` para obter a chave pública.
  - Desta forma, o frontend receberá a chave pública sem falhas caso o arquivo tenha sido deletado pelo Git.

### 1.3 `push_debug.php`
- Atualizar o teste de diagnóstico para verificar `getVapidConfig()`.

### 1.4 `classes/repositorio.php`
- Em `upsertVoto()` (linha ~1323):
  - Substituir o comando cURL manual duplicado por `sendPushBackground($titulo, $mensagem, $url)`.
  - Construir `$url = "/index.php?pag=recurso&rec=" . urlencode($numero_recurso);`.

---

## 2. Critérios de Sucesso e Verificação
- `classes/vapid.json` restaurado no disco.
- Execução de `getVapidConfig()` retorna chaves válidas.
- Disparo de votos, comentários e novos recursos chama a infraestrutura com as credenciais corretas.
- Documentar walkthrough da entrega.
