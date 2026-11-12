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


---

## 2. Ajuste Complementar: O mistério dos delimitadores revelado

Ao testar com o texto real do usuário, identificamos exatamente a causa do `error: unclosed delimiter`:
1. **O Cifrão Monetário `R$`**: No Typst, o caractere `$` delimita o modo matemático. Como o texto tinha 3 valores em reais (`R$ 48.125,00`, `R$ 1.650.000,00` e `R$ 68.750,00`), o terceiro `$` abriu o modo matemático e nunca fechou, gerando o `unclosed delimiter`. Tratamento: `R$` agora é escapado automaticamente para `R\$`.
2. **Marcador de Lista com Asterisco (`* `)**: O Markdown usa `* ` para listas, mas no Typst isso abre negrito. Tratamento: linhas com `* ` no início agora viram listas válidas do Typst com hífen `- `.
3. **Colisão de Sintaxe em `)[1]` e `)[6][12]`**: No Typst, parênteses seguidos de colchetes `(...) [...]` tentam indexar uma tupla/código. Tratamento: referências soltas entre colchetes agora são escapadas como `\[1\]`, `\[4\]`, `\[6\]`.
4. **Escapes de Markdown (`1\.`)**: Limpos para `1.`.
5. **Títulos com asterisco `=== *...*`**: Limpos para `=== ...`.
