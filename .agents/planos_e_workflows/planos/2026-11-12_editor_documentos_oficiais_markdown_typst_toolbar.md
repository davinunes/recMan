# Plano de Implementação - Toolbar Typst, Conversor Markdown (Varinha Mágica) e Notas de Rodapé

- **Data**: 2026-11-12
- **Módulo**: Documentos Oficiais do Conselho (`palco/documentosOficiais.php`)
- **Backend**: `classes/typstPdfService.php`

---

## 1. Objetivos

1. **Barra de Ferramentas Rica (Toolbar Typst)** no editor split de documentos oficiais (`palco/documentosOficiais.php`):
   - Atuação sobre texto selecionado ou na posição do cursor em `#editorSplitCode`.
   - Botões:
     - Negrito: `*texto*`
     - Itálico: `_texto_`
     - Sublinhado: `#underline[texto]`
     - Maiúsculas / Minúsculas: converte seleção para `CAIXA ALTA` ou alterna.
     - Títulos: Nível 1 (`= `), Nível 2 (`== `), Nível 3 (`=== `).
     - Listas: Itens (`- `) e Numerada (`+ `).
     - Citação Jurídica com recuo: `#pad(left: 15pt)[_..._]`.
     - Nota de Rodapé (`#footnote[...]`): botão `[¹]` com modal rápido / prompt para inserir nota no rodapé da página.
     - Linha Divisória: `#line(length: 100%, stroke: 0.5pt + stroke_card)`.
     - Quebra de Página: `#pagebreak()`.
2. **Varinha Mágica (Conversor Markdown ➔ Typst)**:
   - Botão com ícone `auto_awesome` / `auto_fix_high`.
   - Se houver texto selecionado, atua sobre a seleção. Se não houver, atua sobre o documento inteiro.
   - Conversões tratadas:
     - Títulos `#`, `##`, `###`, `####`, `#####` ➔ `=`, `==`, `===`, `====`, `=====`
     - Negrito `**texto**` ➔ `*texto*`
     - Itálico `*texto*` (quando em formato md) ou `_texto_` ➔ `_texto_`
     - Linhas divisórias `---` ou `***` ➔ `#line(length: 100%, stroke: 0.5pt + stroke_card)`
     - Links `[rótulo](url)` ➔ `#link("url")[rótulo]`
     - Citações `> texto` ➔ `#pad(left: 15pt)[_texto_]`
     - Notas de rodapé no formato Markdown:
       - `[^1]: Descrição da nota` combinado com `[^1]` no texto ➔ transforma a chamada em `#footnote[Descrição da nota]` e remove a lista no final!
       - Referências numéricas finais como `[1] Descrição` ou `[4] Descrição` casadas com `[1]`, `[4]` ➔ convertidas em `#footnote[Descrição]`.
3. **Proteção Automática no Backend (`classes/typstPdfService.php`)**:
   - Criar método auxiliar `markdownToTypstMarkup($conteudo)` para sanitizar e converter preventivamente qualquer Markdown residual antes de enviar ao Typst, impedindo que o compilador quebre com `unclosed delimiter` ou `expected identifier`.

---

## 2. Etapas de Execução

1. Atualizar `classes/typstPdfService.php` adicionando `markdownToTypstMarkup($texto)` e aplicando no `gerarDocumentoOficial`.
2. Atualizar `palco/documentosOficiais.php`:
   - Adicionar estilos modernos para a toolbar (`.typst-toolbar`).
   - Inserir a toolbar HTML logo acima do textarea `#editorSplitCode`.
   - Criar modal de inserção de nota de rodapé (`#modalInserirFootnote`).
   - Implementar funções JavaScript para manipulação do textarea (inserção de tags em volta da seleção, conversão de maiúsculas, inserção de títulos/listas).
   - Implementar a função da **Varinha Mágica** (`converterMarkdownParaTypst`) operando na seleção ou no texto completo, disparando o live preview automaticamente.
3. Testar a consistência do código e registrar o walkthrough.
