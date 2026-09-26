# Diagnóstico: Correção na Edição de Diligência (`editaDiligencia`)

## Data: 2026-09-26

## Contexto do Problema
Após corrigir a edição de comentários, identificamos que a edição de diligências (`editaDiligencia`) apresentava um problema similar de atualização de tela:

1. **Atraso com `setTimeout` desnecessário**:
   No arquivo `meu.js`, ao clicar em `#updateDiligence`, a requisição AJAX continha um `setTimeout(() => window.location.reload(), 1000)`. Como o modal possuía a classe Materialize `modal-close`, o modal fechava imediatamente mas a página esperava 1 segundo para recarregar, deixando o conteúdo antigo na tela e passando a impressão de que a alteração não foi salva.

2. **Falta de garantia nos valores do `FormData`**:
   O `FormData` dependia exclusivamente da serialização do form sem forçar explicitamente `formData.set('id_diligencia', idValue)` e `formData.set('messageText', textValue)`. Se os campos não tivessem sincronizado no DOM antes da instância do `FormData`, a requisição enviava dados vazios.

3. **Incompatibilidade de formato de resposta (`metodo.php`)**:
   No backend (`metodo.php`), `editaDiligencia` retornava texto puro `"ok"` ou mensagens de erro. Se houvesse qualquer espaçamento no PHP, a validação `responseData.trim() === "ok"` no frontend falhava, disparando o Toast de erro sem recarregar a tela.

## Solução Adotada

1. **Ajustes no Frontend (`meu.js`)**:
   - Forçar a gravação de `id_diligencia` e `messageText` no `FormData`.
   - Adicionar o recálculo automático de altura do textarea ao abrir a modal (`.editDiligence`).
   - Definir `dataType: 'json'` no `$.ajax` e validar tanto formato objeto `{success: true}` quanto string `"ok"`.
   - Remover o delay de 1000ms e recarregar a página imediatamente (`window.location.reload()`).

2. **Ajustes no Backend (`metodo.php`)**:
   - Adicionada validação dos parâmetros obrigatórios (`id_diligencia` e `messageText`).
   - Padronizado o retorno para JSON `header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => true]);`.
