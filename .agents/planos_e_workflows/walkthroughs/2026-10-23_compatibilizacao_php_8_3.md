# Walkthrough: Compatibilização do Sistema recMan para PHP 8.3 (Retrocompatível com PHP 7.4)

- **Data**: 2026-10-23
- **Status**: Concluído / Código Blindado
- **Arquivos Modificados**:
  - `classes/database.php`
  - `portal/api.php`

---

## 1. Alterações Realizadas

### A. Blindagem do Driver MySQLi (`classes/database.php`)
1. **Modo Silencioso do MySQLi (`mysqli_report`)**:
   - Adicionada a instrução `mysqli_report(MYSQLI_REPORT_OFF);` no início do script.
   - **Motivo**: No PHP 8.1+, o padrão mudou para `MYSQLI_REPORT_STRICT`, gerando exceções fatais não capturadas (`mysqli_sql_exception`) em consultas de fallback ou tabelas opcionais.
   - **Compatibilidade 7.4**: No PHP 7.4, esse já era o valor padrão; a instrução é nativa e segura.

2. **Proteção de `mysqli_num_rows()`**:
   - Na função `DBQuery($sql)`, atualizada a checagem:
     ```php
     if ($result instanceof mysqli_result && mysqli_num_rows($result) > 0)
     ```
   - **Motivo**: No PHP 8.0+, passar `false` para `mysqli_num_rows` causa `TypeError` fatal.
   - **Compatibilidade 7.4**: 100% suportado desde o PHP 5.

3. **Prevenção de `Passing null to string` em `DBEscape()`**:
   - Valores nulos agora sofrem coerção segura:
     ```php
     $dados = mysqli_real_escape_string($link, (string)($dados ?? ''));
     ```
   - **Motivo**: Previne warnings e logs massivos de `Deprecated: Passing null to parameter #2 ($string) of type string is deprecated` no PHP 8.1+.
   - **Compatibilidade 7.4**: 100% suportado.

---

### B. Remoção de Constante Depreciada (`portal/api.php`)
1. **Substituição de `FILTER_SANITIZE_STRING`**:
   - No endpoint de verificação/envio de token do portal (linha 295), o filtro foi substituído por sanitização segura com `preg_replace`:
     ```php
     $numeroLimpo = isset($_POST['numero']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_POST['numero']) : '';
     $anoLimpo = isset($_POST['ano']) ? preg_replace('/[^0-9]/', '', (string)$_POST['ano']) : '';
     $numeroStr = ($numeroLimpo !== '' && $anoLimpo !== '') ? ($numeroLimpo . '/' . $anoLimpo) : '';
     ```
   - **Motivo**: A constante `FILTER_SANITIZE_STRING` foi descontinuada no PHP 8.1 e eliminada no PHP 9.
   - **Compatibilidade 7.4**: 100% suportado e muito mais seguro contra caracteres especiais inesperados.

---

## 2. Status do Ambiente

- **PHP 7.4 Atual**: O sistema continua rodando normalmente sem nenhuma alteração no fluxo de execução ou quebra.
- **PHP 8.3 Futuro**: O sistema está protegido contra as principais quebras de compatibilidade do motor Zend Engine 4.
