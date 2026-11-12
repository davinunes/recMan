# Walkthrough: Barra de Ferramentas Typst, Varinha Mágica Markdown e Notas de Rodapé

- **Data**: 2026-11-12
- **Módulo**: Documentos Oficiais do Conselho (`index.php?pag=documentosOficiais`)
- **Arquivos modificados**:
  - `palco/documentosOficiais.php`
  - `classes/typstPdfService.php`

---

## 1. O que foi implementado

### 1.1 Barra de Ferramentas Rica (Toolbar Typst)
Adicionada barra ergonômica e moderna sobre o editor de código (`#editorSplitCode`), com suporte à seleção de texto e atalhos rápidos:
- **`B` Negrito**: Envolve seleção em `*texto*` (sintaxe válida do Typst).
- **`I` Itálico**: Envolve seleção em `_texto_`.
- **`U` Sublinhado**: Envolve seleção em `#underline[texto]`.
- **`Aa` Maiúsculas/Minúsculas**: Alterna ou converte seleção para `CAIXA ALTA`.
- **`H1`, `H2`, `H3`**: Formata ou insere títulos nos níveis `= `, `== `, `=== `.
- **`•` Marcador e `1.` Numerada**: Formata listas com `- ` e `+ `.
- **`“ ”` Citação**: Envolve seleção em `#pad(left: 15pt)[_texto_]`.
- **`—` Linha Divisória**: Insere régua horizontal `#line(...)`.
- **`📄` Quebra de Página**: Insere `#pagebreak()`.

### 1.2 Inserção Dinâmica de Nota de Rodapé (`#footnote`)
- Botão **`[¹] Nota Rodapé`** com cor verde destacada.
- Ao clicar (com o cursor na posição desejada ou termo selecionado), abre o modal rápido `#modalInserirFootnote`.
- O usuário digita o texto da nota explicativa e clica em **"Inserir no Texto"**.
- O sistema insere automaticamente `#footnote[Texto da nota]` no local do cursor, disparando a recompilação ao vivo.
- O Typst posiciona automaticamente o **número sobrescrito no termo** e a **nota descritiva numerada no rodapé da respectiva página**.

### 1.3 Varinha Mágica (Markdown ➔ Typst)
- Botão com ícone `auto_awesome` em destaque roxo na toolbar.
- **Atuação inteligente**:
  - Se houver texto selecionado, atua e converte **apenas o trecho selecionado**.
  - Se nenhum texto estiver selecionado, converte o **documento inteiro**.
- Converte de forma precisa:
  - Títulos `#`, `##`, `###` ➔ `=`, `==`, `===`
  - Negritos `**texto**` ➔ `*texto*`
  - Divisórias `---` e `***` ➔ `#line(...)`
  - Links `[nome](url)` ➔ `#link("url")[nome]`
  - Citações `> texto` ➔ `#pad(left: 15pt)[_texto_]`
  - Notas de rodapé `[^1]` e `[^1]: descrição` ➔ `#footnote[descrição]`
  - Referências numéricas de fim de texto `[1] descrição` ➔ integradas no corpo como `#footnote[descrição]`.

### 1.4 Blindagem Preventiva no Backend (`classes/typstPdfService.php`)
- Criado o método `TypstPdfService::markdownToTypstMarkup($conteudo)`.
- Mesmo se o usuário esquecer de usar a varinha mágica e enviar Markdown puro, o backend sanitiza e converte preventivamente a sintaxe antes de encaminhar ao compilador Typst, impedindo a ocorrência de erros fatais de compilação como `error: unclosed delimiter` ou `error: expected identifier`.
