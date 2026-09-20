# Plano de Implementação - Correção de Geração de PDF, Ajuste Fato Gerador e Estudo de Modelos de Parecer

**Data**: 2026-09-20
**Objetivo**: Corrigir falha na geração do PDF ao conter caracteres especiais Unicode, remover trava de quebra de linha no campo fato gerador (restringindo ao evento paste) e apresentar o plano de viabilidade para pareceres em modelo Full Dinâmico sem impactar pareceres legados.

---

## 1. Alterações Propostas

### 1.1 Correção e Robustez na Geração do PDF (Python + PHP)

#### A. No serviço Python (`E:\DEV\reportPDFpython`)
- **Suporte a UTF-8 e Substituição de Fallback**:
  - Garantir que o payload JSON seja lido explicitamente com `request.get_json(force=True)`.
  - Registrar fontes TrueType Unicode (ex: `DejaVuSans`, `Helvetica` compatível) ou utilizar substituição preventiva de caracteres especiais que causam crash (`UnicodeEncodeError`) no ReportLab/FPDF:
    - Travessão `—` (`\u2014`) e Meia-risca `–` (`\u2013`) $\rightarrow$ `-`
    - Aspas inteligentes `“` `”` $\rightarrow$ `"`
    - Apóstrofe inteligente `’` $\rightarrow$ `'`
    - Bullets `•` $\rightarrow$ `*`
    - Reticências `…` $\rightarrow$ `...`
  - Incluir bloco `try...except` na função de geração de PDF para tratar erros de codificação graciosamente e logar exceções sem interromper a execução do servidor Python.

#### B. No PHP (`classes/pdfParecer.php`)
- Criar a função auxiliar `sanitizarTextoParaPdf($texto)` que realiza a higienização/substituição de caracteres Unicode problemáticos antes do envio do JSON para o cURL.
- Tratar a resposta da API cURL com verificação de código HTTP e fallback amigável caso a API retorne erro.

---

### 1.2 Ajuste do Campo Fato Gerador em JavaScript (`meu.js`)

#### No arquivo [`meu.js`](file:///e:/DEV/recMan/meu.js#L1295-L1303)
- Subtituir o listener do evento `keyup` no elemento `.fato`:
```javascript
// ANTES (removia quebras a cada tecla digitada):
$(document).on('keyup', '.fato', function (event) { ... });

// DEPOIS (remove quebras apenas no evento de colar texto - paste):
$(document).on('paste', '.fato', function (e) {
    var self = this;
    setTimeout(function () {
        var entrada = $(self).val();
        entrada = entrada.replace(/[\r\n]+/g, ' ').replace(/\s+/g, ' ');
        $(self).val(entrada);
    }, 100);
});
```
- Dessa forma, quando o usuário cola um texto, ele limpa quebras excessivas se desejado, mas ao digitar Enter manualmente para formatar o texto no textarea, as quebras de linha serão preservadas.

---

### 1.3 Estudo de Viabilidade: Suporte a Modelo Full Dinâmico de Parecer

#### Objetivos:
- Permitir pareceres com estrutura 100% dinâmica/livre (ex: títulos I, II, III, IV, V como pareceres jurídicos/administrativos complexos).
- **Zero Impacto**: Garantir 100% de retrocompatibilidade com os pareceres já gerados no modelo atual (metade estático / metade dinâmico).

#### Arquitetura Proposta:
1. **Banco de Dados**:
   - Adicionar coluna na tabela `parecer`:
     `ALTER TABLE conselho.parecer ADD COLUMN modelo VARCHAR(50) DEFAULT 'estatico';`
   - Todos os pareceres existentes mantêm o valor `'estatico'`.
2. **Interface / Formulário (`palco/emiteParecer.php`)**:
   - Adicionar seletor de Modelo ("Modelo Padrão Seccionado" vs "Modelo Full Dinâmico").
   - No Modelo Full Dinâmico, exibe campo com editor/textarea expansível para o texto do parecer na íntegra.
3. **Serviço Python (`E:\DEV\reportPDFpython`)**:
   - O payload JSON passa a receber `"modelo": "full_dinamico" | "estatico"`.
   - Se `modelo == "full_dinamico"`, o gerador renderiza o documento com fluxo contínuo de parágrafos/títulos, interpretando quebras de linha e títulos sem aplicar o cabeçalho fixo das 5 seções legadas.

---

## 2. Plano de Verificação Manual e Testes

1. Testar salvamento do parecer do Recurso `339/2026` contendo o texto de exemplo fornecido (com travessões, numerações romanas e quebras de linha).
2. Verificar a geração bem-sucedida do PDF base64 e sua exibição na tag `<embed>` do navegador.
3. Testar a digitação da tecla Enter no campo Fato Gerador e certificar-se de que a quebra de linha não é mais removida no `keyup`.
4. Testar o colar (Ctrl+V) no campo Fato Gerador.

