# Raciocínio: Script de Atualização e Enriquecimento em Lote de Chamados Legados VDS

**Data:** 17/11/2026  
**Contexto:** Ocorrências no banco de dados local da VDS classificadas como "Outros / Diversos" ou migradas de scraping antigo sem `uuid_remoto` ou `oco_tipo` correto.  
**Solicitação:** Criar um script para roletar e atualizar em lote todas as ocorrências nessa situação, consultando a API v8 da VDS por protocolo e persistindo UUID remoto, tipo de ocorrência, bloco/unidade e `dados_json` para permitir levantamento estatístico fiel.

---

## 1. Diagnóstico do Problema

1. **Origem dos dados "Outros/Diversos":**
   - Registros criados no passado via scraping ou importações legadas possuem apenas `id` e `protocolo_vds`, mas:
     - `uuid_remoto` está nulo ou vazio (`NULL` ou `''`).
     - `oco_tipo` está `NULL`, `0` ou contém valor não mapeado nos 10 tipos oficiais da API v8.
     - `dados_json` está ausente ou incompleto.
2. **Mecanismo de Auto-recuperação existente:**
   - Em `classes/vds_ocorrencia_service.php` na função `vds_get_ocorrencia_detalhe($id)`, quando um conselheiro abre individualmente um chamado com `uuid_remoto` vazio, o sistema já faz a chamada:
     `GET /ocorrencia?Protocolo={protocolo}` na API v8 da VDS.
   - A resposta da VDS retorna `regs[0]` com:
     - `uuid`
     - `tipoId` / `ocoTipo` (ex: 115, 247, 86, etc.)
     - `statusNome`
     - `cargo` (ex: "Bloco A - 102")
     - `abertura` / `dtExibicao`
   - O sistema então executa o `UPDATE` na tabela `ocorrencias`.
3. **Necessidade do usuário:**
   - Fazer isso **em lote para todos os chamados nessa condição** de forma automatizada, resiliente e segura, sem precisar abrir um por um manualmente, para fins de levantamento de estatísticas fiéis.

---

## 2. Requisitos de Engenharia

1. **Filtro dos chamados a atualizar:**
   - Principal: `(uuid_remoto IS NULL OR uuid_remoto = '' OR oco_tipo IS NULL OR oco_tipo = 0 OR oco_tipo NOT IN (115,247,114,86,109,102,145,87,126,172) OR dados_json IS NULL)` e `id > 0`.
   - Dar opção para filtrar apenas `sem_uuid`, apenas `outros`, ou `todos`.
2. **Estratégia de Execução Segura (Anti-rate limit):**
   - A API da VDS não deve ser bombardeada simultaneamente. Um intervalo controlado (ex: 120ms a 200ms `usleep(150000)`) evita erro 429 ou bloqueio por DoS.
   - Suporte a limitação por lote (`limit=X`, default 100 ou customizável).
   - Suporte a retentativa automática ou pausa caso receba 401 (reautenticando o token).
3. **Duplo Modo de Execução:**
   - **CLI (Terminal/Cron):** `php cron_vds_enrich_legadas.php [--limit=100] [--filtro=outros]`
   - **Web (Navegador):** `cron_vds_enrich_legadas.php?limit=100` (com proteção por sessão de conselheiro logado OU secret_key do cron), exibindo saída em tempo real com `ob_flush()`, barra de progresso visual e resumo das estatísticas.
4. **Integração na Interface:**
   - No `livroDeOcorrencias.php`, adicionar botão prático no topo "Atualizar Legados (VDS)" que permite disparar a atualização de lote com feedback transparente.

---

## 4. Diagnóstico Avançado (Rodada 1): IDs Internos de 8 Dígitos vs Protocolos da VDS

Na 1ª rodada de execução, observou-se:
1. Registros com números de 6 dígitos (`254767`, `254681`, etc.) tiveram taxa de sucesso de ~100% via `GET /ocorrencia?Protocolo=...`.
2. Registros com números de 8 dígitos (`57023129`, `56980981`, etc.) retornaram "não localizado" com a busca por `Protocolo=`.
3. **Causa Raiz Identificada:** A VDS possui internamente:
   - `ocorrenciaId` (numérico, 8 dígitos, ex: `54860421`, `52075990`).
   - `protocolo` (6 dígitos, ex: `259564`).
   - `uuid` (UUID v4).
   No passado, durante a era de scraping, o sistema gravou o `ocorrenciaId` interno de 8 dígitos no campo `id` / `protocolo_vds`.
4. **Estratégia Híbrida Implementada:**
   - Para identificadores com 8+ dígitos:
     - Tentativa 1: `GET /ocorrencia/{id}` no path.
     - Tentativa 2: `GET /ocorrencia?Id={id}`.
     - Tentativa 3: `GET /ocorrencia?ocorrenciaId={id}`.
   - Ao localizar, o sistema não só recupera o UUID e a categoria, como também **corrige o campo `protocolo_vds`** com o número de protocolo real da VDS!
   - Para identificadores convencionais (≤ 7 dígitos): busca direta com `GET /ocorrencia?Protocolo={protocolo}`.

---

## 3. Decisão de Arquitetura

- Refatorar e fortalecer a função `vds_enrich_legacy_ocorrencias` em `classes/vds_ocorrencia_service.php` para aceitar parâmetros flexíveis (`$filtro`, `$limit`, `$delayMs`, `$callbackProgresso`).
- Criar o script autônomo [`cron_vds_enrich_legadas.php`](file:///d:/dev/github/recMan/cron_vds_enrich_legadas.php) na raiz do projeto.
- Integrar no cabeçalho de `livroDeOcorrencias.php` com atalho direto.
