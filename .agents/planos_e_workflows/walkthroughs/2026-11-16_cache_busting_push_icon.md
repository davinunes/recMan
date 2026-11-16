# Walkthrough: Quebra de Cache (Cache Busting) para Notificações Push e Normativos

**Data**: 2026-11-16  
**Status**: Concluído com Sucesso  

---

## 1. O Problema Investigado

Após a realização do `git pull` com sucesso no servidor de produção (confirmado pelo log do SSH com `storage/icons/logo-conselho.png 321333 bytes`), o dispositivo móvel (Android) continuava exibindo a imagem antiga ao receber pushs de teste.

---

## 2. Causa Raiz

1. **Cache Local do Chrome/Android para Notificações**:
   - O Chrome no Android armazena em cache interno persistente qualquer recurso de imagem estática carregada sob a URL `https://mini.davinunes.eti.br/storage/icons/logo-conselho.png`.
   - Ao receber o push, se a URL da imagem for idêntica, o sistema operacional reutiliza a imagem gravada no armazenamento interno do celular em vez de requisitar novamente ao servidor.
2. **Omissão de Ícone em `push_debug.php`**:
   - O payload do teste de diagnóstico em `push_debug.php` não enviava o atributo `icon`. Isso fazia com que o Service Worker caísse no fallback estático `sw.js` (que também apontava para a URL pura sem query string).

---

## 3. Ações Implementadas

1. **Cache Busting Automático em [`classes/push_helper.php`](file:///d:/dev/github/recMan/classes/push_helper.php)**:
   - Adicionada geração dinâmica de query string com base no timestamp real do arquivo (`filemtime`):
     `https://mini.davinunes.eti.br/storage/icons/logo-conselho.png?v={timestamp}`.
   - Qualquer substituição futura da imagem no disco invalida automaticamente o cache de todos os dispositivos móveis.
2. **Atualização do Service Worker ([`sw.js`](file:///d:/dev/github/recMan/sw.js))**:
   - Atualizado para versão `v2.2` com fallback versionado `?v=20261116`.
3. **Inclusão do Ícone Versionado em [`push_debug.php`](file:///d:/dev/github/recMan/push_debug.php)**:
   - O teste manual passa a enviar explicitamente o atributo `icon` com a query string de versionamento.
4. **Cache Busting em [`portal/normativos.php`](file:///d:/dev/github/recMan/portal/normativos.php)**:
   - Adicionado `?v=2` na imagem e nos favicons da página.
