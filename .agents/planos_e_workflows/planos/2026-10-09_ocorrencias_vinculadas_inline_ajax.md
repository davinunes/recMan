# Plano: Expansão Inline via AJAX das Ocorrências Vinculadas no Recurso

## Contexto
No arquivo `palco/detalheRecurso.php`, a seção "Ocorrências Condomínio Digital Vinculadas" lista chamados vinculados ao recurso. Em vez de navegar para outra página ou abrir em nova guia, o clique no botão deve expandir o conteúdo completo da ocorrência via AJAX diretamente abaixo do item da collection.

---

## Passos de Implementação

1. **Ajuste na View (`palco/detalheRecurso.php`)**:
   - Modificar a renderização de cada ocorrência em `$ocorrenciasVinculadas`:
     - O item principal terá o botão com clique AJAX: `toggleOcorrenciaInline(id, this)`.
     - Ícone indicativo dinâmico (`chat` / `expand_more` vs `expand_less`).
     - Adicionar um botão discreto de apoio para "Abrir em nova aba" (`target="_blank"`) no Livro de Ocorrências (`index.php?pag=livroDeOcorrencias&id={id}`).
     - Incluir container de expansão `<div class="ocorrencia-inline-chat-container" id="oco-inline-{id}" style="display:none;"></div>` logo abaixo das informações do item.
   - Adicionar estilos CSS dedicados para o chat inline (`.ocorrencia-inline-chat`, balões `.msg-bubble`, `.msg-left`, `.msg-right`, `.msg-internal`, `.chat-body`, `.badge-tipo`, etc.).
   - Implementar a função JavaScript `toggleOcorrenciaInline(id, btn)`:
     - Verifica se já possui conteúdo carregado.
     - Se não tiver, exibe feedback de carregamento (spinner/skeleton) e dispara `$.ajax` para `index.php?pag=livroDeOcorrencias&is_ajax=1&action=carregar_detalhe&id={id}`.
     - Injeta o HTML retornado, ajusta seletores e inicializa visualizador de imagens (`initMaterialboxed`).
     - Altera estado do botão entre "Ver Chat" e "Recolher".

2. **Garantir Suporte a Submissão de Nota Interna e Ações Inline**:
   - Caso o conselheiro decida adicionar uma nota interna pelo formulário inline, as funções `submeterNotaInternaAjax` e handlers devem redirecionar a submissão para `index.php?pag=livroDeOcorrencias&is_ajax=1` e recarregar o container inline atualizado.

3. **Validação & Testes**:
   - Verificar sintaxe PHP.
   - Revisar links e comportamento visual.
