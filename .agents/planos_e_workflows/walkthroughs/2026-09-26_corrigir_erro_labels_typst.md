# Walkthrough: Correção de Rótulos (Labels) no Typst em ptd-conselho.txt

## Resumo da Modificação
Ao colar o conteúdo do arquivo `ptd-conselho.txt`, o compilador Typst abortou devido ao uso de múltiplos rótulos atrelados na mesma linha de cabeçalho (ex: `=== Art. 7º <cc-art-7-d> <cc-art-7-g>`).

### Como Funciona a Regra de Rótulos no Typst
No Typst, um rótulo `<label>` é associado ao elemento imediatamente anterior. Quando múltiplos rótulos são colocados em sequência no mesmo elemento, apenas o **último rótulo é retido**, descartando os anteriores. Como os rótulos anteriores deixavam de existir no documento, a função `link(<label>)` falhava ao tentar criar o hiperlink.

### Ajuste Efetuado
Os rótulos foram distribuídos diretamente para os itens e incisos individuais aos quais pertencem:
- `*(d)* <cc-art-7-d>`
- `*(g)* <cc-art-7-g>`
- `*(a)* <cc-art-21-a>`
- `*(f)* <cc-art-21-f>`
- `*(g)* <cc-art-21-g>`
- `*(VI)* <ri-art-6-vi>`
- `*(X)* <ri-art-6-x>`
- `*(XVI)* <ri-art-6-xvi>`
- `*(I)* <ri-art-9-i>`
- `*(III)* <ri-art-9-iii>`
- `*(II)* <lgpd-art-7-ii>`
- `*(IX)* <lgpd-art-7-ix>`

O arquivo [`docs/inspect/ptd-conselho.txt`](file:///e:/DEV/recMan/docs/inspect/ptd-conselho.txt) foi atualizado com essa correção.
