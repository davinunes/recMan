# Registros de Raciocínio - Correção de Geração de PDF, Ajuste no JS Fato Gerador e Estudo de Modelos de Parecer

**Data**: 2026-09-20
**Tópico**: Falha na geração do PDF por caracteres especiais Unicode, bloqueio de quebra de linha no campo fato gerador e estudo de viabilidade para modelos de parecer full dinâmico.

---

## 1. Diagnóstico do Problema no PDF

### Contexto
Ao tentar gerar e salvar o parecer em `index.php?pag=emiteParecer&rec=339/2026`, o PDF não é gerado quando o parecer possui caracteres especiais tipográficos (como travessões `—`, meias-riscas `–`, aspas curvas `“` `”`, apóstrofe curva `’`, indicador ordinal `º`/`ª`, bullets `•`, etc.).

### Causa Raiz
1. A função `getParecerPdf($data)` em [`classes/pdfParecer.php`](file:///e:/DEV/recMan/classes/pdfParecer.php) envia o payload contendo os campos do parecer em JSON para `http://127.0.0.1:5000/gerar_pdf?base64=true` (localizado no repositório `E:\DEV\reportPDFpython`).
2. A API em Python ao processar esses caracteres em bibliotecas de geração de PDF (como ReportLab ou FPDF) sem configuração de fontes TrueType UTF-8 ou sem tratamento de fallback de encoding lança um erro (`UnicodeEncodeError` / `latin-1` codec exception), abortando a resposta e retornando erro 500 ou resposta inválida.

---

## 2. Ajuste do Campo Fato Gerador no JS (`meu.js`)

### Causa Raiz
No arquivo [`meu.js`](file:///e:/DEV/recMan/meu.js#L1295-L1303), a função intercepta o evento `keyup` no selector `.fato`:
```javascript
$(document).on('keyup', '.fato', function (event) {
    var entrada = $(this).val();
    entrada = entrada.replace(/[\r\n]+/g, ' ').replace(/\s+/g, ' ');
    $(this).val(entrada);
});
```
Isso remove instantaneamente qualquer quebra de linha (`\r\n`) enquanto o usuário digita (inclusive a tecla Enter).

### Solução
Remover o evento `keyup` e alterar a lógica para atuar apenas no evento `paste`. Ao colar, caso haja quebras excessivas pode-se higienizar, mas ao digitar manualmente a tecla Enter, a quebra de linha é mantida.

---

## 3. Estudo de Viabilidade de Modelos de Parecer (Full Dinâmico)

### Requisito
Permitir novos pareceres em modelo Full Dinâmico (onde o usuário digita seções livres como I. SÍNTESE, II. PRELIMINARES, III. MÉRITO, IV. DISPOSITIVO) sem afetar pareceres anteriores (metade estático, metade dinâmico).

### Solução Arquitetural
1. Adicionar campo `modelo` (ex: `'estatico'` ou `'full_dinamico'`) na tabela `parecer` com valor padrão `'estatico'` para manter compatibilidade com pareceres existentes.
2. Na tela de parecer e no formulário de edição, permitir selecionar o modelo desejado.
3. Enviar a propriedade `modelo` no payload para a API Python.
4. Na API Python, ajustar o gerador para renderizar o layout estruturado padrão se `modelo == 'estatico'` ou o layout livre/fluido se `modelo == 'full_dinamico'`.

