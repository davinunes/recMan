# Diagnóstico: Botão Visualizar em documentosOficiais.php

## Problema Identificado
Ao clicar no botão **Visualizar** (`.btnPreviewDoc`) na listagem de Documentos Oficiais (`index.php?pag=documentosOficiais`), o PDF gerado não exibia os dados do documento selecionado, comportando-se como se o ID estivesse errado ou não estivesse sendo considerado.

## Causa Raiz
No JavaScript de `palco/documentosOficiais.php`, a função de clique `.btnPreviewDoc` enviava apenas o objeto `{ id: id }` via requisição POST para `metodo.php?metodo=previewDocumentoOficial`.

No backend `metodo.php` no caso `previewDocumentoOficial`:
```php
$dados = $_POST;
$res = TypstPdfService::gerarDocumentoOficial($dados, true);
```
O código pegava `$dados = $_POST` (que só continha `['id' => '...']`) e repassava diretamente para o serviço Typst (`TypstPdfService::gerarDocumentoOficial`), **sem antes buscar no banco de dados** a linha do documento oficial correspondente aquele ID (título, ementa, relator, tipo, número, ano, conteúdo, variante_global, etc.).

Em contrapartida, o botão **Editar** (`.btnEditarDoc`) chamava `metodo.php?metodo=getDocumentoOficialById`, que faz a consulta `getDocumentoOficialById($id)` no banco de dados e retorna todos os campos.

## Solução Aplicada
1. Em `metodo.php`, no case `previewDocumentoOficial`:
   - Verificar se `$dados['id']` está presente.
   - Caso positivo, consultar o banco de dados via `getDocumentoOficialById($dados['id'])`.
   - Se o registro for encontrado, realizar o merge dos dados do banco (`$docDb`) com `$dados` (`$_POST`), garantindo que campos não enviados no POST da tabela sejam preenchidos com os dados reais salvos no banco.

2. Em `palco/documentosOficiais.php`:
   - Garantir resiliência na captura do atributo `docid` no JS de `.btnPreviewDoc`.
