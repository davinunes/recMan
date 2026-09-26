# Diagnóstico: Erro "unclosed delimiter" ao colar texto no Typst

## Problema Relatado
Ao colar um parecer/texto longo no Redator Typst Code do Documento Oficial (`eval(conteudo_typst, mode: "markup")`), o Typst apresentou o seguinte erro:
```
Erro no Typst (código 1): error: unclosed delimiter
    ┌─ typst_templates/documento_oficial.typ:132:7
    │
132 │   eval(conteudo_typst, mode: "markup")
    │        ^^^^^^^^^^^^^^
```

## Causa Raiz
Dois fatores combinados provocaram a falha de compilação:

1. **Conflito de Sintaxe no Texto Colado (`*` e `[` `]`)**:
   - No texto fornecido pelo usuário, a expressão `(Cota * S ) + Somatório( Cota * 1,3 * D )` contém asteriscos `*` no meio do texto sem estar em modo matemático `$ ... $`.
   - Como `eval(..., mode: "markup")` interpreta asteriscos como delimitadores de texto em negrito (`*texto*`), o Typst agrupou os asteriscos criando um bloco de negrito contendo ` S ) + Somatório( Cota `. Isso resultou em parênteses de fechamento `)` e abertura `(` sem os seus pares correspondentes dentro daquele nó de marcação.
   - Além disso, a fórmula estava envolvida por colchetes `[ ... ]` no início de linha, que no Typst abrem blocos de conteúdo (content blocks).

2. **Substituição de Aspas Inteligentes em `sanitizarTexto`**:
   - No PHP (`classes/typstPdfService.php`), o método `sanitizarTexto` estava substituindo aspas duplas inteligentes (`“` e `”`) por aspas simples retas de ASCII (`"`).
   - No Typst, a aspa dupla ASCII `"` é o delimitador de string de código (`"string"`). Ao converter aspas tipográficas em aspas de código ASCII, o PHP fazia o Typst interpretar parágrafos inteiros de texto como literais de string não fechados (`unclosed string`), corrompendo a estrutura de blocos e delimitadores.

## Solução Aplicada
1. Ajustada a função `sanitizarTexto` em `classes/typstPdfService.php` para manter as aspas inteligentes (`“` / `”`) e os bullets intactos, pois o Typst possui suporte nativo a UTF-8 e renderiza aspas tipográficas perfeitamente sem convertê-las para delimitadores de código.
2. Formatado o texto do parecer para a sintaxe nativa e limpa do Typst (utilizando `= Heading`, `*negrito*`, `#quote(block: true)[...]` e equações matemáticas `$ ... $`).
