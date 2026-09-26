# Walkthrough: Correção do Botão Visualizar na Listagem de Documentos Oficiais

## Resumo das Alterações
Ao clicar no botão **Visualizar PDF** (`.btnPreviewDoc`) na tabela de documentos oficiais (`index.php?pag=documentosOficiais`), o ID do documento era enviado ao backend, porém o endpoint `previewDocumentoOficial` em `metodo.php` não consultava os dados do documento no banco de dados. Como resultado, o compilador Typst recebia apenas um array vazio com a chave `id`, gerando um PDF sem o título, conteúdo ou numeração do documento oficial selecionado.

## Modificações Realizadas

### 1. `metodo.php`
- No case `"previewDocumentoOficial"`, adicionada a verificação `if (!empty($dados['id']))`.
- Quando o ID está presente, é feita a busca no banco via `getDocumentoOficialById($dados['id'])`.
- Os dados retornados do banco (`$docDb`) são mesclados com `$dados` (`array_merge($docDb, $dados)`), garantindo que todos os campos (título, ementa, relator, tipo, número, ano, conteúdo, variante_global, etc.) sejam injetados para a compilação do Typst.

### 2. `palco/documentosOficiais.php`
- Adicionado atributo `data-docid` aos botões de ação da tabela.
- Atualizado o handler do evento click de `.btnPreviewDoc` para capturar `docid` / `data-docid` via `$(this).closest('.btnPreviewDoc')`, prevenindo qualquer problema de alvo do evento (event target).

## Como Testar
1. Acesse `index.php?pag=documentosOficiais`.
2. Clique no ícone do olho (Visualizar) de qualquer documento da lista.
3. O modal com o iframe do PDF será exibido contendo os dados reais correspondentes ao documento selecionado (título, número/ano, relator, conteúdo e variante visual).
