# Raciocínio de Diagnóstico e Implementação: Substituição do Subdomínio `recurso.` por `home.` nos Push Notifications

## Contexto e Problema Identificado
Quando um morador/condômino acessa o portal público para apresentar um recurso, enviar comentários ou novos anexos, a requisição HTTP chega ao servidor com o cabeçalho `Host` apontando para o subdomínio público `recurso.` (por exemplo, `recurso.davinunes.eti.br`).

Ao protocolar a defesa ou interagir, o portal (`portal/api.php`) gerava URLs absolutas de notificação push utilizando `$_SERVER['HTTP_HOST']`. Como consequência:
1. As notificações push e seus botões de ação ("Abrir Defesa", "Ver Comentário", "Ver Anexos") continham links como:
   `https://recurso.davinunes.eti.br/index.php?pag=recurso&rec=446/2026`
2. No entanto, o subdomínio `recurso.` é de uso exclusivo do portal do condômino e não possui o painel interno de julgamento e deliberação dos conselheiros.
3. Ao clicar no push no celular/navegador, os conselheiros eram levados à página incorreta e não conseguiam abrir o recurso diretamente no painel administrativo (`home.`).

## Regra Solicitada pelo Usuário
- Se o início/host da URL for `recurso.`, substituir o prefixo por `home.`.
- Caso contrário (se for `mini.`, `home.`, `localhost` ou outro), manter intacto o início original da URL.

## Estratégia de Solução
Adotamos uma abordagem de **defesa em profundidade** em dois níveis:

1. **Na origem (`portal/api.php`)**:
   - Criação da função `resolverHostPushConselheiro($host = null)`.
   - Se o host iniciar com `recurso.`, substitui `^recurso\.` por `home.`.
   - Aplicação em todos os pontos de notificação:
     - `action == 'submit'` (novo recurso protocolado)
     - `action == 'add_comentario'` (novo comentário do condômino)
     - `action == 'add_attachments'` (novos anexos do condômino)
   - Padronização do `basePathUrl` para evitar caminhos quebrados quando em subdiretórios.

2. **Na central de despacho (`classes/push_helper.php`)**:
   - No loop de envio de notificações para os conselheiros, aplicamos uma normalização tanto na `$finalUrl` quanto nas `$act['url']`:
     ```php
     if (preg_match('#^https?://recurso\.#i', $finalUrl)) {
         $finalUrl = preg_replace('#^(https?://)recurso\.#i', '${1}home.', $finalUrl);
     } elseif (stripos($finalUrl, 'recurso.') === 0) {
         $finalUrl = preg_replace('/^recurso\./i', 'home.', $finalUrl);
     }
     ```
   - Isso garante que mesmo se qualquer rotina ou chamada externa fornecer uma URL começando com `recurso.`, o push enviado aos dispositivos dos conselheiros sempre apontará para `home.`.
