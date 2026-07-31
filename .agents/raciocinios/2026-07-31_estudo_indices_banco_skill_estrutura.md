# Estudo de Índices de Alta Performance & Skill de Estrutura do Banco

- **Data**: 2026-07-31
- **Tópico**: Criação da skill local `consultar_estrutura_banco` e análise aprofundada das queries do sistema para recomendação de índices SQL estratégicos.

## 1. Skill Local Criada (`consultar_estrutura_banco`)
- **Localização**: `.agents/skills/consultar_estrutura_banco/SKILL.md`
- **Descrição**: Orienta os assistentes a consultarem a estrutura atualizada do banco de dados em tempo real via cURL no arquivo `https://mini.davinunes.eti.br/estrutura.php` ou inspecionando o script local [estrutura.php](file:///e:/DEV/recMan/estrutura.php).
- **Utilidade**: Evita palpites sobre colunas, tipos de dados e chaves existentes ao planejar migrações ou refatorações de código.

## 2. Análise Aprofundada de Queries & Recomendações de Índices

### A. Tabela `ocorrencias`
- **Consultas Identificadas**:
  1. `SELECT id FROM ocorrencias WHERE id = ? OR protocolo_vds = ? OR uuid_remoto = ?` (Sincronizador VDS API em lote)
  2. `SELECT * FROM ocorrencias WHERE (resolvido IS NULL OR resolvido = 0) AND bloco = ? AND unidade = ? ... ORDER BY abertura DESC LIMIT 1000` (Visão Analítica / Filtros)
  3. `SELECT DISTINCT bloco FROM ocorrencias WHERE bloco IS NOT NULL AND bloco != '' ORDER BY bloco ASC`
- **Diagnóstico**: A busca por `uuid_remoto` e `protocolo_vds` sem índice causava *Table Scan* a cada sincronização.
- **Índices Criados**:
  - `idx_ocorrencias_uuid_remoto (uuid_remoto)`
  - `idx_ocorrencias_protocolo_vds (protocolo_vds)`
  - `idx_ocorrencias_bloco_unidade (bloco, unidade)`
  - `idx_ocorrencias_resolvido_abertura (resolvido, abertura DESC)`
  - `idx_ocorrencias_tipo_abertura (oco_tipo, abertura DESC)`
  - `idx_ocorrencias_resp (responsabilidade)`

### B. Tabela `ocorrencia_leitura_conselheiro`
- **Consultas Identificadas**:
  1. `SELECT lido FROM ocorrencia_leitura_conselheiro WHERE conselheiro_id = ? AND ocorrencia_id = ?`
  2. `SELECT ... WHERE conselheiro_id = ? AND sincronizado_remoto = 0` (Flushing de confirmações de leitura)
- **Índices Criados**:
  - `idx_leitura_conselheiro_lido (conselheiro_id, lido)`
  - `idx_leitura_sync_remoto (conselheiro_id, sincronizado_remoto)`

### C. Tabela `recurso`
- **Consultas Identificadas**:
  1. `SELECT * FROM recurso WHERE numero = ?`
  2. `SELECT * FROM recurso WHERE unidade = ? AND bloco = ?`
  3. `SELECT ... FROM recurso r WHERE ... ORDER BY r.data DESC`
- **Índices Criados**:
  - `idx_recurso_numero (numero)`
  - `idx_recurso_bloco_unidade (bloco, unidade)`
  - `idx_recurso_fase_data (fase, data DESC)`

### D. Tabelas `notificacoes`, `multas_cobradas` e `parecer`
- **Consultas Identificadas**:
  1. `SELECT * FROM notificacoes WHERE numero = ? AND ano = ?`
  2. `SELECT * FROM multas_cobradas WHERE numero = ? AND ano = ?`
  3. `SELECT * FROM parecer WHERE id = ? AND concluido = 1`
- **Índices Criados**:
  - `idx_notificacoes_num_ano (numero, ano)` na tabela `notificacoes`
  - `idx_notificacoes_torre_unidade (torre, unidade)` na tabela `notificacoes`
  - `idx_multas_num_ano (numero, ano)` na tabela `multas_cobradas`
  - `idx_parecer_id_concluido (id, concluido)` na tabela `parecer`

### E. Tabelas de Notas e Comentários
- **Índices Criados**:
  - `idx_notas_oco_created (ocorrencia_id, created_at ASC)` na tabela `ocorrencia_notas_internas`
  - `idx_comentarios_oco_dt (ocorrencia_id, dt_criacao ASC)` na tabela `ocorrencia_comentarios_vds`

## 3. Automação Idempotente
Criado o script [otimizar_indices_banco.php](file:///e:/DEV/recMan/otimizar_indices_banco.php) que verifica a existência de cada índice antes de aplicar o `ALTER TABLE`, garantindo execução segura no ambiente remoto.
