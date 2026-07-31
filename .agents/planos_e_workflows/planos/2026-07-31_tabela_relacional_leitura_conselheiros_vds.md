# Plano de Implementação: Cache Relacional de Leitura por Conselheiro & Sincronização Assíncrona VDS

- **Data**: 2026-07-31
- **Objetivo**: Criar a tabela `ocorrencia_leitura_conselheiro`, migrar a Visão Prática para carregamento local instantâneo, adicionar controle de última sincronização no Cron (15 min) e implementar sincronização assíncrona via AJAX com Toasts de 10s e retry de 60s.

## Componentes a Serem Modificados/Criados

### 1. Tabela Relacional Local (`migrate_vds_integration.php` & `classes/vds_ocorrencia_service.php`)
- Tabela `ocorrencia_leitura_conselheiro`:
  ```sql
  CREATE TABLE IF NOT EXISTS ocorrencia_leitura_conselheiro (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      conselheiro_id INT NOT NULL,
      ocorrencia_id INT NOT NULL,
      uuid_remoto VARCHAR(100) DEFAULT NULL,
      lido TINYINT(1) DEFAULT 1,
      sincronizado_remoto TINYINT(1) DEFAULT 0,
      read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uk_conselheiro_ocorrencia (conselheiro_id, ocorrencia_id),
      KEY idx_conselheiro_lido (conselheiro_id, lido),
      KEY idx_sincronizado (sincronizado_remoto)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ```

### 2. Endpoint de Sincronização Assíncrona (`[NEW] vds_sync_async.php`)
- Criar endpoint leve para chamadas AJAX em segundo plano:
  - Executa a sincronização recente com a VDS sem bloquear a renderização HTML.
  - Grava a marcação da última sincronização (`ultima_sincronizacao_ocorrencias`).
  - Retorna JSON com contagem de novas ocorrências trazidas e status da sincronização.

### 3. Frontend com Carga Instantânea e Toast Notifier (`livroDeOcorrencias.php`)
- **Carregamento Instantâneo**: A página renderiza os chamados não lidos direto do banco local (`ocorrencias` + `ocorrencia_leitura_conselheiro`).
- **Disparo de AJAX de Fundo**: JS inicia a sincronização 1s após a página carregar.
- **Toasts Temporários (10s)**: Exibe feedback visual discreto quando houver novidades.
- **Loop de Retry (60s)**: Em caso de erro/timeout do servidor VDS, reprograma uma nova tentativa para daqui a 60 segundos sem afundar a performance da tela.

### 4. Cron a cada 15 min com Registro de Última Sincronização (`cron_vds_sync.php`)
- Controla a data/hora da última sincronização.
- Processa e descarrega a fila de marcações de leitura pendentes (`sincronizado_remoto = 0`).

## Plano de Verificação Manual
1. Abrir `index.php?pag=livroDeOcorrencias&visao=pratico`: verificar abertura imediata (< 50ms).
2. Observar o console do navegador e o Toast temporário (10s) indicando a sincronização assíncrona em background.
3. Testar o loop de retry de 60s em ambiente simulado de indisponibilidade VDS.
4. Executar `cron_vds_sync.php` e validar a sincronização dos status de leitura e registro da última data de sync.
