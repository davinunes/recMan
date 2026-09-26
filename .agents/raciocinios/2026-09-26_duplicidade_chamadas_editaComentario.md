# Diagnóstico: Duplicidade de Chamadas AJAX no `editaComentario`

## Data: 2026-09-26

## Contexto do Problema
Ao editar um comentário na página de detalhe do recurso (`detalheRecurso.php`), duas requisições AJAX eram disparadas para `metodo.php?metodo=editaComentario`:
1. **1ª Chamada**: `Content-Type: application/x-www-form-urlencoded` enviando `id_comentario` e `comentario`. Retornava erro MariaDB: `update conselho.mensagem set texto = '' where id = and id_usuario = 5` (com `id` vazio).
2. **2ª Chamada**: `Content-Type: multipart/form-data` enviando `id_mensagem`, `messageText` e `anexos[]`. Retornava `{"success":true}`.

Apesar do sucesso da 2ª chamada no servidor, a interface não recarregava nem exibia a mensagem editada.

## Causa Raiz

1. **Event Listeners Duplicados em `meu.js`**:
   - **Bloco Legado (linhas ~584-634)**: Registrava ouvintes em `.editComment` e `#updateComment`. A leitura de `message_id` do `#messageTextComment` falhava (retornava vazio `""`), gerando a requisição com `id` em branco e causando o erro de sintaxe SQL no MariaDB.
   - **Bloco Atual (linhas ~716-815)**: Registrava novos ouvintes em `.editComment` e `#updateComment`, usando `FormData`, suporte a uploads de anexos e gravando corretamente o ID em `#editMessageId`.

2. **Tratamento Incorreto do Retorno no JS (Bloco Atual)**:
   - No backend (`metodo.php`), `editaComentario` responde com JSON: `json_encode(['success' => true])`.
   - Na linha 802 de `meu.js`, o callback `success` validava a resposta com:
     `if (responseData.trim() === "ok")`
   - Como `responseData` era o objeto JSON `{success: true}` (ou string JSON), a comparação com `"ok"` resultava em `false` (ou gerava exceção por `responseData.trim` não existir).
   - Com isso, o JS caía no bloco `else`, exibia toast de erro e **não chamava** `window.location.reload()`.

## Solução Adotada

1. **Remoção do Bloco Legado (`meu.js`)**:
   - Remover as linhas 584-635 contendo os ouvintes duplicados antigos de `.editComment` e `#updateComment`.

2. **Ajuste e Robustez no Handler Principal (`meu.js`)**:
   - Adicionar o ajuste de altura de textarea (`ajustarAlturaTextarea`) no abrir da modal.
   - Configurar `dataType: 'json'` no `$.ajax`.
   - Validar a resposta com `responseData && responseData.success === true` (com fallback para `"ok"`).
   - Chamar `window.location.reload()` quando bem-sucedido.

3. **Validação Defensiva no Backend (`metodo.php`)**:
   - Garantir que `editaComentario` valide se `id_mensagem` e `messageText` estão preenchidos antes de tentar executar a query de update.
