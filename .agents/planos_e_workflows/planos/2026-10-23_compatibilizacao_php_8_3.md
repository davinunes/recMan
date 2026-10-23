# Plano de Migração e Compatibilização: PHP 7.4 -> PHP 8.3

- **Status**: Planejado / Em Análise
- **Data**: 2026-10-23
- **Responsável**: Agente recMan
- **Ambiente Alvo**: PHP 8.3 (Servidor Remoto Linux / Nginx / PHP-FPM)

---

## 1. Visão Geral e Motivação
O PHP 7.4 atingiu o End-Of-Life (EOL) oficial, deixando de receber correções de vulnerabilidades críticas (CVEs). A atualização para o PHP 8.3 garante:
1. **Segurança**: Eliminação das CVEs conhecidas do PHP 7.4 (incluindo falhas de buffer overflow, memory leak em openssl/libxml e bypasses de descompressão).
2. **Performance**: Otimizações do motor Zend Engine 4, melhorias de OPcache e JIT (Just-In-Time Compiler).
3. **Estabilidade**: Manutenção ativa de pacotes do Composer e novas versões de bibliotecas.

---

## 2. Diagnóstico de Compatibilidade

| Item Avaliado | Situação no PHP 7.4 | Situação no PHP 8.3 | Risco / Gravidade | Ação Necessária |
| :--- | :--- | :--- | :--- | :--- |
| **`mysqli_report` padrão** | Silencioso (`MYSQLI_REPORT_OFF`). Erros retornavam `false`. | Estrito (`MYSQLI_REPORT_STRICT`). Dispara `mysqli_sql_exception`. | **CRÍTICO** | Adicionar `mysqli_report(MYSQLI_REPORT_OFF);` em `classes/database.php`. |
| **`mysqli_num_rows(false)`** | Retornava `null` com Warning simples. | Dispara `TypeError` (Fatal Error). | **ALTO** | Proteger chamadas com `instanceof mysqli_result` em `DBQuery()`. |
| **Passagem de `null` para strings** | Convertido silenciosamente para `""`. | Emite aviso `Deprecated`. | **MÉDIO** | Cast explícito `(string)($dados ?? '')` em `DBEscape()` e views. |
| **`FILTER_SANITIZE_STRING`** | Sanitizava strings simples. | Depreciado no 8.1; removido no 9. | **BAIXO** | Ajustar linha 296 de `portal/api.php`. |
| **Operador `@` em Fatal Errors** | Silenciava quase tudo (exceto parse error). | Não silencia Fatal Errors e Exceções. | **MÉDIO** | Depende da correção de `mysqli_report`. |
| **Dependências Composer** | Travadas no PHP 7.4 via `platform`. | Todas as libs instaladas já suportam PHP 8.x. | **BAIXO** | Atualizar `"platform": {"php": "8.3.0"}` no `composer.json`. |
| **Funções Removidas (`each`, `create_function`)** | Nenhuma utilizada no código-fonte PHP próprio. | N/A | **NENHUM** | Nenhuma ação necessária. |
| **Palavra Reservada `match`** | Não conflitante (nenhuma função chamada `match`). | N/A | **NENHUM** | Nenhuma ação necessária. |

---

## 3. Plano de Ação Passo a Passo

### Passo 1: Blindagem de Banco de Dados (`classes/database.php`)
1. Inserir `mysqli_report(MYSQLI_REPORT_OFF);` logo após a abertura da tag `<?php`.
2. Em `DBEscape($dados)`:
   - Fazer cast para `(string)($dados ?? '')` evitando que valores nulos gerem depreciação no `mysqli_real_escape_string`.
3. Em `DBQuery($sql)`:
   - Verificar se `$result instanceof mysqli_result` antes de chamar `mysqli_num_rows($result)`.

### Passo 2: Ajuste no Portal de Recursos (`portal/api.php`)
1. Na linha 296, substituir `filter_var(..., FILTER_SANITIZE_STRING)` por sanitização com regex alfanumérico seguro ou cast limpo.

### Passo 3: Atualização do `composer.json`
1. Atualizar o bloco de configuração:
   ```json
   "config": {
       "platform-check": false,
       "platform": {
           "php": "8.3.0"
       }
   }
   ```

### Passo 4: Configuração de Módulos e `php.ini` no Servidor Remoto
Garantir a instalação dos seguintes pacotes no Debian/Ubuntu do servidor remoto:
```bash
sudo apt install php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl
```

Recomendações para o arquivo `/etc/php/8.3/fpm/php.ini`:
```ini
display_errors = Off
display_startup_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
date.timezone = America/Sao_Paulo
```

---

## 4. Validação e Testes Recomendados
1. **Autenticação**:
   - Login no sistema (`palco/usuarioLogado.php`);
   - Token VDS v8 (`classes/vds_auth_service.php`).
2. **Consultas e Relatórios**:
   - Listagem de ocorrências (`livroDeOcorrencias.php`);
   - Tela de detalhe de recurso (`palco/detalheRecurso.php`);
   - Módulo de Documentos Oficiais (`palco/documentosOficiais.php`).
3. **Geração de PDFs**:
   - Teste de geração Typst na porta 5050 (`palco/test_typst.php`).
4. **Notificações Push e E-mail**:
   - Disparo de e-mail via Gmail API (`classes/mail_helper.php`);
   - Disparo de push (`classes/push_helper.php`).
