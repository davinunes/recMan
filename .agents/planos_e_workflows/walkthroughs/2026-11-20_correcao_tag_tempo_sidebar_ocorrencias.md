# Walkthrough: Correção da Tag de Tempo na Sidebar do Livro de Ocorrências

**Data:** 20/11/2026  
**Status:** Concluído com sucesso  

---

## O que foi corrigido

Ao carregar `index.php?pag=livroDeOcorrencias&id=57972301`, a tag de tempo (badge de atraso/delay) não aparecia em certos chamados da lista lateral à esquerda (sidebar); ela só aparecia após o usuário clicar no chamado, mas voltava a sumir se a página fosse recarregada (F5).

### 1. Paridade de Delays na Paginação Progressiva (`vds_sync_async.php`)
- **Antes**: Ao buscar as páginas adicionais da VDS (página 2 em diante), o endpoint retornava os itens brutos sem o campo `delays`.
- **Depois**: Cada item agora recebe `vds_calcular_delays_ocorrencia($it)` antes de serializar no JSON.

### 2. Inserção Imediata da Ocorrência Selecionada no SSR (`livroDeOcorrencias.php`)
- **Antes**: Se o chamado passado por ID na URL (`?pag=livroDeOcorrencias&id=57972301`) estivesse além da página 1 (10 primeiros registros), o PHP não o incluía em `$ocorrencias` nem em `$ocorrenciasAgrupadas`, fazendo com que ele não aparecesse na sidebar gerada pelo servidor.
- **Depois**: Caso `$detalheSel` esteja ativo, seu registro é inserido imediatamente no array geral e na sua categoria em `$ocorrenciasAgrupadas` com seu delay pré-calculado em `$mapaDelaysPrecalculados`, renderizando no HTML inicial com classe `.active` e com a tag de tempo correspondente.

### 3. Renderização Dinâmica no Frontend (`livroDeOcorrencias.php`)
- **Antes**: A função `injetarNovosItensDOM(items)` gerava a tag com `style="display:none;"` e vazia.
- **Depois**: A função agora renderiza a tag com as classes, cores, títulos e texto amigável (dias da abertura e última resposta ou tempo de resolução), mantendo também a marcação `.active` se o item for o selecionado.
