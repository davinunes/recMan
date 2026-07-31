# Raciocínio de Arquitetura: Tabela Relacional de Leitura por Conselheiro & Cache Local VDS (com Sincronização Assíncrona AJAX e Cron)

- **Data**: 2026-07-31
- **Tópico**: Criação de controle relacional local de leitura, sincronização em segundo plano via Cron (15 min) com histórico da última sincronização, e atualização assíncrona por AJAX na interface com Toasts temporários (10s) e retries em loop de 60s.

## 1. Contexto & Evidência Técnica
Durante o teste em produção e Postman no endpoint `GET /ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0`, a API VDS retornou HTTP 500 com exceção do SQL Server remoto por estouro de timeout.

Isso comprova de forma definitiva que o banco de dados remoto da VDS (SQL Server) possui uma query não otimizada para a consulta de não lidos (`Lida=0`), estourando o timeout do próprio backend .NET/Entity Framework deles.

## 2. Nova Arquitetura de Alta Performance e Fluidez

Para garantir **carregamento instantâneo (resposta em < 0,01s)** e navegação fluida sem travamentos:

### A. Tabela Relacional Local (`ocorrencia_leitura_conselheiro`)
- `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `conselheiro_id` INT NOT NULL (ID do usuário no recMan)
- `ocorrencia_id` INT NOT NULL (ID da ocorrência na tabela local `ocorrencias`)
- `uuid_remoto` VARCHAR(100) DEFAULT NULL
- `lido` TINYINT(1) DEFAULT 1 (1 = Lido pelo conselheiro, 0 = Não lido)
- `sincronizado_remoto` TINYINT(1) DEFAULT 0 (1 = enviado para a VDS via `PUT /ocorrencia/leitura/{uuid}`, 0 = pendente)
- `read_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- Chave Única: `uk_conselheiro_ocorrencia (conselheiro_id, ocorrencia_id)`

### B. Visão Prática Baseada em Cache Local (`visao=pratico`)
- A Visão Prática lê diretamente do banco local `ocorrencias` unindo com `ocorrencia_leitura_conselheiro` para ocultar os chamados lidos pelo conselheiro logado.
- **Resultado**: Carregamento da página **instantâneo**, sem requisição síncrona de rede bloqueando o PHP.

### C. Sincronização Assíncrona por AJAX na Interface (`vds_sync_async.php`)
- **Carregamento Inicial Zero-Wait**: A página do `livroDeOcorrencias.php` é entregue de imediato com os dados locais.
- **Background AJAX Sync**: 1 segundo após o carregamento, um script JavaScript envia requisição assíncrona para `vds_sync_async.php`.
- **Feedbacks em Toasts Temporários**: Se o AJAX encontrar novidades da VDS, um Toast estilizado e temporário (exibido por **10 segundos**) notifica o conselheiro sem interromper a navegação.
- **Loop de Retry (60s)**: Se a chamada AJAX falhar devido a indisponibilidade ou timeout do servidor VDS, o sistema aguarda **60 segundos em background** antes de tentar novamente, mantendo a experiência do usuário 100% fluida.

### D. Cron a cada 15 min com Histórico de Última Sincronização (`cron_vds_sync.php`)
- Registra a data/hora da última sincronização bem-sucedida em `vds_uuid_mapping` (chave `ultima_sincronizacao_ocorrencias`).
- Filtra ocorrências recentes/de hoje.
- Processa e descarrega a fila de confirmações de leitura pendentes (`sincronizado_remoto = 0`).
