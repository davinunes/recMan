# Raciocínio Diagnóstico: Ocorrências VDS Vinculadas não listando Tags de Recurso

**Data:** 2026-09-25  
**Contexto:** `index.php?pag=recurso&rec=352/2026`  
**Problema relatado:** Ocorrência com tag vinculada ao recurso (ex: `352/2026`) não estava sendo exibida na seção "Ocorrências Condomínio Digital Vinculadas" do recurso.

---

## 1. Análise da Causa Raiz

### A. Fluxo de Adição de Tags em Ocorrências
Quando um conselheiro adiciona uma tag no formato `352/2026` no componente de chat/livro de ocorrências (`livroDeOcorrencias.php`):
1. A função `vds_adicionar_tag_livre` detecta o padrão regex `/(\d+)\/(\d{2,4})/` e chama `vds_vincular_tag_recurso($ocorrenciaId, "352/2026")`.
2. `vds_vincular_tag_recurso`:
   - Insere na tabela `ocorrencia_unidade_tag` (`bloco = 'NOTIF'`, `unidade = '352/2026'`, `tipo_vinculo = 'notificacao'`).
   - Insere na tabela `ocorrencia_recurso_link` (`numero_recurso = '352/2026'`).
   - Resolve o `$recursoId` via `SELECT id FROM recurso WHERE numero = '352/2026'`.
   - **SE E SOMENTE SE** o recurso for encontrado, insere na tabela legado `recurso_ocorrencia` (`id_recurso`, `id_ocorrencia`).

### B. Gargalo no Carregamento da Tela do Recurso (`detalheRecurso.php`)
Na renderização de `index.php?pag=recurso&rec=352/2026`:
1. `detalheRecurso.php` chamava `$ocorrenciasVinculadas = getOcorrenciasVinculadas($esseRecurso)`.
2. A função `getOcorrenciasVinculadas($id_recurso)` executava estritamente:
   ```sql
   SELECT o.* 
   FROM recurso_ocorrencia ro
   JOIN ocorrencias o ON o.id = ro.id_ocorrencia
   WHERE ro.id_recurso = '$id_recurso'
   ```
3. **Falhas identificadas:**
   - Se a tag foi vinculada **antes** de criar o registro `recurso` no banco local, `$recursoId` era nulo e a tabela `recurso_ocorrencia` não foi preenchida.
   - `getOcorrenciasVinculadas` ignorava completamente as tabelas `ocorrencia_recurso_link` e `ocorrencia_unidade_tag`.
   - `getOcorrenciasVinculadas` não fazia comparação com variações do ano (ex: `352/2026` vs `352/26`).
   - Não havia auto-recuperação/auto-healing para gravar em `recurso_ocorrencia` vínculos descobertos pelas tags.

---

## 2. Solução Proposta

1. **Atualizar `getOcorrenciasVinculadas($id_recurso, $numero_recurso = null)` em `classes/repositorio.php`**:
   - Aceitar opcionalmente o `$numero_recurso` (ex: `'352/2026'`). Se omitido, buscar no banco pelo `$id_recurso`.
   - Gerar variações do ano (ex: `'352/2026'` e `'352/26'`).
   - Consultar ocorrências por **3 fontes de dados unificadas** (`recurso_ocorrencia`, `ocorrencia_recurso_link` e `ocorrencia_unidade_tag`).
   - Fazer auto-healing: inserir automaticamente registros faltantes em `recurso_ocorrencia`.

2. **Criar helper `getTagsOcorrencia($ocorrenciaId)` em `classes/repositorio.php`**:
   - Retornar todas as tags atreladas a uma ocorrência específica para renderização visual.

3. **Atualizar `vds_vincular_tag_recurso` em `classes/vds_ocorrencia_service.php`**:
   - Suportar busca do `$recursoId` comparando com o ano em 4 dígitos e 2 dígitos.

4. **Aprimorar `palco/detalheRecurso.php`**:
   - Passar `$result['numero']` para `getOcorrenciasVinculadas($esseRecurso, $result['numero'])`.
   - Renderizar badges/chips de tags da ocorrência na lista e incluir link direto para abrir no livro de ocorrências local (`index.php?pag=livroDeOcorrencias&id=...`).
