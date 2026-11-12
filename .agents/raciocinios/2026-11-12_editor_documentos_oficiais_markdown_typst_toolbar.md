# Raciocínio: Suporte a Markdown, Botões de Formatação e Notas de Rodapé no Editor Typst de Documentos Oficiais

- **Data**: 2026-11-12
- **Contexto**: `index.php?pag=documentosOficiais`
- **Problema**: Ao colar conteúdo copiado em Markdown (com `###`, `**negrito**`, `[1]`, `[4]`, `---`), o compilador Typst quebra na chamada `eval(conteudo_typst, mode: "markup")` acusando erro de compilação: `error: unclosed delimiter` ou `error: expected identifier`.
- **Necessidades do Usuário**:
  1. Suporte e facilidade para colar textos em Markdown sem quebrar a compilação do Typst.
  2. Botões de formatação (toolbar) atuando sobre o texto selecionado (negrito, itálico, maiúsculas, etc.).
  3. Notas de rodapé: verificação de implementação no Typst (número sobrescrito no termo + descrição numerada no rodapé da página) e facilitador na interface.

---

## 1. Diagnóstico da Sintaxe Typst vs. Markdown

| Elemento | Markdown | Typst Markup | Motivo do Erro no Typst Atual |
| :--- | :--- | :--- | :--- |
| **Título H1** | `# Título` | `= Título` | `#` no Typst é escape para código/funções (`#identificador`). |
| **Título H2** | `## Título` | `== Título` | Dois `#` é sintaxe inválida no Typst. |
| **Título H3** | `### Título` | `=== Título` | Três `#` quebram o parser com `expected identifier`. |
| **Negrito** | `**texto**` | `*texto*` | `**` abre e fecha o strong imediatamente vazio, desbalanceando delimitadores (`unclosed delimiter`). |
| **Itálico** | `*texto*` ou `_texto_` | `_texto_` | `*texto*` no Typst é negrito, enquanto `_texto_` é itálico. |
| **Linha Horizontal** | `---` | `#line(length: 100%, stroke: 0.5pt + stroke_card)` | `---` no Typst gera em-dash (`—`), não linha divisória. |
| **Notas de Rodapé** | `[^1]` e `[^1]: Nota` ou referências `[4]` | `#footnote[Texto da nota]` | No Typst, `#footnote[...]` é nativo e posiciona o número sobrescrito no termo e a nota no rodapé da mesma página. |
| **Colchetes** | `[1]`, `[4]` | `[1]`, `[4]` (content blocks) ou `#footnote[...]` | Colchetes soltos podem desbalancear se não forem fechados ou se colidirem com outras marcações. |

---

## 2. Solução Proposta

### 2.1 Barra de Ferramentas (Toolbar) no Editor
Adicionar uma toolbar com botões intuitivos sobre o textarea `#editorSplitCode`:
- **B** (Negrito): Envolve seleção em `*...*`
- **I** (Itálico): Envolve seleção em `_..._`
- **U** (Sublinhado): Envolve seleção em `#underline[...]`
- **Aa** (Maiúsculas/Minúsculas): Converte seleção para MAIÚSCULAS
- **=** (H1), **==** (H2), **===** (H3): Aplica nível de título no início da linha
- **•** (Lista Marcador `- `), **1.** (Lista Numerada `+ `)
- **Citação**: `#pad(left: 15pt)[_..._]`
- **Nota de Rodapé (`[¹]`)**: Insere `#footnote[Texto da nota]`
- **Linha Divisória**: `#line(length: 100%, stroke: 0.5pt + luma(180))`
- **Quebra de Página**: `#pagebreak()`

### 2.2 Conversor Inteligente Markdown ➔ Typst
- Botão "Converter Markdown para Typst" e atalho de colagem inteligente.
- Parser regex em JavaScript para transformar:
  - `###` -> `===`
  - `##` -> `==`
  - `#` -> `=`
  - `**texto**` -> `*texto*`
  - `---` -> linha horizontal
  - `[^1]: nota` e referências `[^1]` -> `#footnote[nota]`
  - Referências no formato `[1]...` no final casadas com `[1]` no corpo.

### 2.3 Resiliência no Backend (`TypstPdfService.php`)
- Função `markdownToTypstMarkup($conteudo)` como camada de segurança transparente: se o usuário salvar ou digitar Markdown sem converter, o backend detecta e converte automaticamente antes de repassar ao `eval(conteudo_typst, mode: "markup")`, prevenindo qualquer erro de compilação.
