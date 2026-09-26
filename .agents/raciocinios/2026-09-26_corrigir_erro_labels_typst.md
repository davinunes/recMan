# Diagnóstico: Erro "label does not exist" no Typst em ptd-conselho.txt

## Problema Relatado
Ao colar o conteúdo do arquivo `ptd-conselho.txt` no Redator Typst, o compilador retornou os seguintes erros/avisos:
```
error: label `` does not exist in the document
    ┌─ typst_templates/documento_oficial.typ:132:7
132 │   eval(conteudo_typst, mode: "markup")

warning: content labelled multiple times
    = hint: only the last label is used, the rest are ignored
```

## Causa Raiz
No Typst, uma **rótulo/etiqueta (label)** (ex: `<cc-art-7-d>`) é sempre vinculada ao **elemento imediatamente anterior**.

No arquivo `ptd-conselho.txt`, vários títulos agrupavam múltiplos rótulos na mesma linha de título:
- `=== Art. 7º <cc-art-7-d> <cc-art-7-g>`
- `=== Art. 21 <cc-art-21-a> <cc-art-21-f> <cc-art-21-g>`
- `=== Art. 6º <ri-art-6-vi> <ri-art-6-x> <ri-art-6-xvi>`
- `=== Art. 9º <ri-art-9-i> <ri-art-9-iii>`
- `=== Art. 7º <lgpd-art-7-ii> <lgpd-art-7-ix>`

Quando múltiplos rótulos são colocados sequencialmente no mesmo título, o Typst atrela apenas o **último rótulo** àquele título e **descarta todos os rótulos anteriores** (conforme avisado em `content labelled multiple times: only the last label is used`).

Como o rótulo `<cc-art-7-d>` foi descartado pelo Typst por estar antes de `<cc-art-7-g>`, quando o código chamou a função `#cc("7.d", <cc-art-7-d>)` (que executa `link(<cc-art-7-d>)`), o Typst não encontrou o rótulo no documento e abortou com `error: label <cc-art-7-d> does not exist in the document`.

## Solução
Cada rótulo deve ser associado ao seu respectivo parágrafo, inciso ou subitem individual no texto (ou em blocos separados), garantindo que todos os rótulos existam e sejam alvos válidos para a função `link(...)`.
