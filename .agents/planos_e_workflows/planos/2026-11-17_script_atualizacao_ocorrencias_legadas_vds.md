# Plano: Script de Atualização em Lote de Ocorrências Legadas (VDS API v8)

**Data:** 17/11/2026  
**Status:** Pronto para Execução  
**Arquivos:**
- `classes/vds_ocorrencia_service.php` (ampliação da função `vds_enrich_legacy_ocorrencias` e helper de busca rápida)
- `cron_vds_enrich_legadas.php` (novo script autônomo CLI e Web com streaming de progresso)
- `livroDeOcorrencias.php` (integração do botão de atalho/modal no topo da tela)

---

## Etapas do Plano

### Etapa 1: Camada de Serviço (`classes/vds_ocorrencia_service.php`)
1. Atualizar e aprimorar a função `vds_enrich_legacy_ocorrencias`:
   - Parâmetros: `$modoFiltro = 'outros'`, `$limit = 100`, `$delayMs = 150`, `$usuarioIdConselho = null`, `$callbackProgresso = null`.
   - Consulta SQL inteligente:
     - `outros`: `(uuid_remoto IS NULL OR uuid_remoto = '' OR oco_tipo IS NULL OR oco_tipo = 0 OR oco_tipo NOT IN (115,247,114,86,109,102,145,87,126,172) OR dados_json IS NULL)`
     - `sem_uuid`: `(uuid_remoto IS NULL OR uuid_remoto = '')`
     - `todos`: todos os chamados existentes.
   - Para cada ocorrência:
     - Consulta rápida via `GET /ocorrencia?Protocolo={protocolo}&limit=1`
     - Atualiza `uuid_remoto`, `protocolo_vds`, `oco_tipo`, `bloco`, `unidade`, `status`, `dados_json`
     - Invoca `$callbackProgresso` com o status do registro.
     - Aplica `usleep($delayMs * 1000)`.

### Etapa 2: Script Autônomo (`cron_vds_enrich_legadas.php`)
1. Suporte a CLI e Web:
   - CLI: lê `--limit=`, `--filtro=`, `--delay=`.
   - Web: aceita parâmetros via `$_GET` e valida autenticação (sessão de conselheiro logado ou `secret_key=vds_cron_sync_sec_2026`).
2. Interface Web com Streaming em Tempo Real:
   - Layout elegante com cabeçalho, barra de progresso, console em monospace estilo terminal preto/verde, e resumo com cartões de estatísticas (Atualizados com sucesso, Não encontrados na VDS, Tempo total decorrido).
   - Uso de `ob_implicit_flush(true)` e `flush()` para o usuário ver cada registro atualizando ao vivo.

### Etapa 3: Interface no Livro de Ocorrências (`livroDeOcorrencias.php`)
1. Adicionar botão "Atualizar Legados" no cabeçalho superior (ao lado de Sincronizar Agora).
2. Adicionar modal explicativo que permite ao usuário escolher o lote (ex: 50, 100, 200 ou Todos) e abrir a roleta de enriquecimento com 1 clique.

### Etapa 4: Validação e Registro
1. Testar e validar código e regras do projeto.
2. Salvar walkthrough em `.agents/planos_e_workflows/walkthroughs/2026-11-17_script_atualizacao_ocorrencias_legadas_vds.md`.
