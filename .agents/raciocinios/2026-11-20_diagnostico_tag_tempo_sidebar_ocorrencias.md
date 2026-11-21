# Diagnóstico Técnico: Tag de Tempo Ausente na Sidebar do Livro de Ocorrências

**Data:** 20/11/2026  
**Contexto:** Ao acessar `index.php?pag=livroDeOcorrencias&id=57972301`, a tag de tempo (badge de delay) na lista à esquerda (sidebar) em alguns chamados não aparece. Ao clicar no chamado, a tag é renderizada, mas ao atualizar a página (F5), ela volta a ficar sem a marcação de tempo.

---

## 1. Causa Raiz Identificada

A análise do ciclo de vida da tela (`livroDeOcorrencias.php` e `vds_sync_async.php`) revelou duas causas que atuam em conjunto:

### Causa A: Paginação Assíncrona no Frontend (`livroDeOcorrencias.php` vs `vds_sync_async.php`)
1. Na **Visão Prática** (padrão quando não especificado `visao=analitico`), o PHP carrega via SSR apenas os primeiros 10 itens da VDS (`vds_get_ocorrencias_pratico($usuarioIdConselho, 10, 1)`).
2. Para esses 10 primeiros itens renderizados no PHP (SSR), a tag `<span class="badge-delay-sidebar">` é preenchida normalmente via `vds_calcular_delays_ocorrencia($oco)`.
3. Contudo, logo após o carregamento da página, o JavaScript inicia a busca assíncrona das páginas subsequentes (`carregarProximaPaginaPratico()` chamando `vds_sync_async.php?page=2`).
4. O backend `vds_sync_async.php` retornava os itens da VDS **sem** calcular a chave `delays` para cada item.
5. No JavaScript, a função `injetarNovosItensDOM(data.items)` injetava os novos cards da lista com o badge vazio e oculto:
   ```html
   <span class="badge-delay-sidebar" style="display:none;"></span>
   ```
6. Consequentemente, **todos** os chamados carregados da página 2 em diante apareciam na lista sem nenhuma tag de tempo visível.

### Causa B: Ausência de Inserção da Ocorrência Selecionada na Sidebar SSR
1. Quando o usuário acessa diretamente uma URL com ID específico (ex: `index.php?pag=livroDeOcorrencias&id=57972301`), `$selId = 57972301`.
2. Se este ID pertencer à página 2 ou posterior da VDS, ele **não constava** nos 10 primeiros itens retornados no SSR.
3. No código PHP anterior, caso `$protoFiltro` estivesse ativo, havia um `array_unshift($ocorrencias, ...)` (que mesmo assim não inseria em `$ocorrenciasAgrupadas`), mas para `$_GET['id']` **não havia nenhuma inserção**.
4. Dessa forma, o PHP não renderizava o card do chamado `57972301` na sidebar durante o SSR (apenas abria o chat no painel principal).
5. Segundos depois, o AJAX progressivo da página 2 trazia o chamado `57972301` e o injetava via `injetarNovosItensDOM` com `display:none` e sem a classe `.active`.
6. Quando o usuário clicava no chamado, a função `selecionarOcorrencia(57972301)` disparava uma requisição AJAX para `action: 'carregar_detalhe'`. O retorno continha `res.delays`, e o JS sobrescrevia o estilo do badge para visível e preenchia o texto.
7. Ao atualizar a tela (F5), o SSR recarregava sem o item na sidebar; o AJAX reinjetava com `display:none`, fazendo a tag sumir novamente.

---

## 2. Solução Implementada

1. **Paridade no Backend AJAX (`vds_sync_async.php`)**:
   - Adicionar o cálculo de `delays` via `vds_calcular_delays_ocorrencia($it)` para cada item antes de serializar o JSON.
   
2. **Garantia de Presença e Delays no SSR (`livroDeOcorrencias.php`)**:
   - Se um chamado estiver selecionado (`$detalheSel`), verificar se ele já está presente em `$ocorrencias` e em `$ocorrenciasAgrupadas`. Se não estiver, incluí-lo imediatamente no início de seu respectivo grupo com classe `active` e com seus delays mapeados em `$mapaDelaysPrecalculados`.
   
3. **Renderização dos Delays no Frontend (`livroDeOcorrencias.php`)**:
   - Ajustar `injetarNovosItensDOM(items)` para renderizar a tag `badge-delay-sidebar` com as classes, cores, títulos e texto descritivo quando `oco.delays` estiver presente.
   - Sincronizar a classe `active` caso o item injetado seja a ocorrência atualmente selecionada.
