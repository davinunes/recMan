# Registro de Raciocínio - Correção do Retorno JSON nas Requisições AJAX do Livro de Ocorrências

**Data:** 2026-07-31  
**Tópico:** Eliminação de HTML parasita em respostas JSON AJAX (`atualizar_responsabilidade`, `marcar_resolvido`, `marcar_como_lido`)

---

## 1. Contexto e Diagnóstico

### Sintoma
Ao clicar nos botões de **Responsabilidade**, **Resolver Local** ou **Marcar Lido** na tela `index.php?pag=livroDeOcorrencias`, as requisições AJAX falhavam no frontend (o callback `success` do jQuery não era chamado e caía no callback `error`). Ao inspecionar a resposta HTTP, notou-se que o JSON da resposta vinha precedido por blocos HTML (`<head>`, `<meta>`, scripts, folha de estilo e `<main>`).

### Causa Raiz
No arquivo `index.php`, as tags HTML do cabeçalho global (`<head>...</head>`, barra de navegação superior `usuarioLogado.php` e a tag `<main>`) eram renderizadas de forma incondicional antes do bloco `switch ($pag)`, onde o arquivo `livroDeOcorrencias.php` era incluído. 

Quando `livroDeOcorrencias.php` processava o AJAX e executava `echo json_encode(...); exit;`, a saída de buffer do PHP já continha todo o HTML emitido previamente pelo `index.php`. O jQuery, ao tentar realizar o `JSON.parse` com `dataType: 'json'`, rejeitava o payload contendo tags HTML no início.

---

## 2. Solução Aplicada

1. **Intercepção de Requisições AJAX no Topo de `index.php`**:
   - Reestruturou-se a ordem do `index.php` iniciando imediatamente a tag `<?php` no topo da linha 1.
   - Adicionou-se uma verificação prévia no topo: `if (!empty($_REQUEST['is_ajax']))`.
   - Quando `is_ajax` for detectado, valida-se a sessão do usuário e inclui-se diretamente a página solicitada (ex: `livroDeOcorrencias.php`) sem emitir nenhuma tag HTML, nem incluir a navbar `usuarioLogado.php`.

2. **Limpeza de Buffer Defensiva em `livroDeOcorrencias.php`**:
   - Adicionou-se o comando `if (ob_get_length()) ob_clean();` imediatamente antes de emitir a header `Content-Type: application/json` e o `echo json_encode(...)` nos handlers de:
     - `atualizar_responsabilidade`
     - `marcar_resolvido`
     - `marcar_como_lido`

---

## 3. Resultado Esperado

As chamadas AJAX enviadas por `executarAcaoAjaxResponsabilidade`, `executarAcaoAjaxResolvido` e `executarAcaoAjaxLido` retornam um payload JSON 100% limpo com Content-Type `application/json; charset=utf-8`. O jQuery consegue interpretar a resposta como objeto JSON nativo, acionando o callback `success` para atualizar dinamicamente badges, ícones, estados e feedback visual em tempo real sem erros no console.
