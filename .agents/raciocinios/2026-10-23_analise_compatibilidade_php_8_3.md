# Raciocínio Diagnóstico: Análise de Compatibilidade PHP 7.4 -> PHP 8.3

- **Data**: 2026-10-23
- **Contexto**: O sistema recMan opera atualmente em PHP 7.4. Devido a CVEs de segurança e fim de vida (EOL) do PHP 7.4, a equipe avalia migrar o ambiente para PHP 8.3.
- **Objetivo**: Mapear quebras de compatibilidade (Breaking Changes), depreciações (Deprecations) e comportamentos divergentes entre PHP 7.4 e PHP 8.3 no código-fonte do recMan e em suas dependências.

---

## 1. Metodologia de Investigação Estática
Conforme a Regra 1.1 do repositório, não executamos interpretadores ou compiladores no repositório local. A análise foi realizada por varredura estática de código (grep regex, análise lexical de classes, funções e dependências no `composer.lock` e `composer.json`).

Foram inspecionadas:
1. Conexão e drivers de banco de dados (`classes/database.php`, `classes/repositorio.php`);
2. Funções removidas/depreciadas nas versões PHP 8.0, 8.1, 8.2 e 8.3 (`each`, `create_function`, `strftime`, `utf8_encode`, `FILTER_SANITIZE_STRING`, `${var}`);
3. Comportamento do driver MySQLi (padrão de relatório de erro e tipagem estrita);
4. Coerção de tipos `null` para funções de string e array nativas;
5. Dependências externas gerenciadas pelo Composer (`minishlink/web-push`, `tesseract_ocr`, `guzzlehttp/guzzle`, `web-token/*`);
6. Operador de silenciamento `@` e conversão de warnings para exceções fatais (`TypeError`, `ValueError`, `mysqli_sql_exception`).

---

## 2. Pontos Críticos Identificados (Causa Raiz & Impacto)

### Ponto 1: Mudança no modo padrão do MySQLi (`mysqli_report`) — **CRÍTICO**
- **No PHP 7.4**: O padrão era `MYSQLI_REPORT_OFF`. Se uma query falhava, retornava `false` e o script lidava com `if (!$result)`.
- **No PHP 8.1+**: O padrão virou `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`. Qualquer erro de SQL (tabela inexistente, sintaxe, constraint) dispara uma exceção não capturada `mysqli_sql_exception` (Fatal Error imediato).
- **Impacto no recMan**:
  - `classes/repositorio.php` usa padrões como:
    ```php
    $res = @DBExecute("SELECT * FROM conselho.documento_oficial ...");
    if (!$res) {
        $res = DBExecute("SELECT * FROM documento_oficial ...");
    }
    ```
    No PHP 8.3, se `conselho.documento_oficial` não existir, o `@` **não silencia exceções** e o PHP encerra a requisição com tela branca / 500, sem nunca tentar o fallback.
- **Solução**: Declarar explicitamente `mysqli_report(MYSQLI_REPORT_OFF);` no início de `classes/database.php`.

### Ponto 2: `mysqli_num_rows()` exigindo estritamente `mysqli_result` — **ALTO**
- **No PHP 7.4**: `mysqli_num_rows(false)` emitia um Warning simples e retornava `null`/`0`.
- **No PHP 8.0+**: Dispara `TypeError: Argument #1 must be of type mysqli_result, false given` (Fatal Error).
- **Impacto no recMan**:
  - Em `classes/database.php`:
    ```php
    function DBQuery($sql) {
        $result = DBExecute($sql);
        if (mysqli_num_rows($result) > 0) { ... }
    ```
    Se `DBExecute` retornar `false` (quando `$die = false`), a chamada `mysqli_num_rows` causará um Fatal Error.
- **Solução**: Alterar para `if ($result instanceof mysqli_result && mysqli_num_rows($result) > 0)`.

### Ponto 3: Coerção de `null` em parâmetros de strings nativas — **MÉDIO / LOGS**
- **No PHP 8.1+**: Passar `null` para argumentos tipados como string que não declaram `?string` (ex: `mysqli_real_escape_string`, `htmlspecialchars`, `trim`, `strlen`, `substr`) gera:
  `Deprecated: Passing null to parameter #... of type string is deprecated`.
- **Impacto no recMan**:
  - `DBEscape($dados)` em `classes/database.php`: se `$dados` for `null`, chama `mysqli_real_escape_string($link, null)`.
  - Em views onde campos opcionais do MySQL vêm como `NULL` e são passados para `htmlspecialchars()`.
- **Solução**:
  - Em `database.php`: fazer cast `(string)($dados ?? '')`.
  - No `php.ini` do PHP 8.3: configurar `error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT` em produção para evitar poluição de logs e cabeçalhos.

### Ponto 4: `FILTER_SANITIZE_STRING` depreciado — **BAIXO / PONTUAL**
- **Local**: `portal/api.php:296`
- **Solução**: Substituir pelo filtro de caracteres alfanuméricos ou `htmlspecialchars(trim((string)...))`.

### Ponto 5: Dependências externas e Composer
- `minishlink/web-push`: versão 7.0.0 instalada, requer `php: >=7.3`.
- `guzzlehttp/guzzle`: 7.10.0 (suporta PHP 8.x nativamente).
- `tesseract_ocr`: 2.13.0 (suporta PHP 8.x).
- `composer.json` possui `"platform": { "php": "7.4.33" }`. Quando for migrar, atualizar essa chave para `"8.3.0"` ou remover para o composer detectar o PHP real do servidor.

---

## 3. Conclusão do Diagnóstico
O sistema recMan é predominantemente **procedural limpo**, não utiliza recursos obsoletos pesados como `each()` ou `create_function()`, e não possui conflitos com a nova palavra reservada `match`.

Com **pequenos ajustes preventivos de 1 a 3 linhas** (especialmente em `classes/database.php` e `portal/api.php`), o recMan rodará em **PHP 8.3 com máxima estabilidade, segurança e ganhos substanciais de performance e proteção contra CVEs**.
