# Plano de Implementação: Tags AJAX no Livro de Ocorrências

**Data:** 2026-08-01
**Tela:** `index.php?pag=livroDeOcorrencias` (visões prática e analítica)
**Relacionado:** `palco/detalheRecurso.php` (Vincular Livro, preservado como legado)

## Objetivo

Converter o mecanismo de tags (adicionar + remover) para AJAX silencioso, corrigir o bug que impede a adição de tags e implementar remoção com confirmação. Tags vinculam a ocorrência a uma **unidade** (input `B1108`) ou a um **recurso/notificação** (input `123/2026`).

## Diagnóstico (causa raiz)

`ocorrencia_unidade_tag.tipo_vinculo` é `ENUM('autora','citada','reclamada')`, mas o código insere `'unidade'`, `'notificacao'`, `'recurso'`, `'tag'`. Em modo estrito do MySQL o INSERT falha → tag nunca é criada.

## Arquivos Envolvidos

| Arquivo | Ação |
|---|---|
| `migrates/migrate_vds_integration.php` | MODIFY COLUMN para ampliar ENUM (idempotente) |
| `classes/vds_ocorrencia_service.php` | Corrigir `vds_adicionar_tag_livre`, criar `vds_remover_tag`, melhorar `vds_vincular_unidade_tag` (dedup) |
| `classes/repositorio.php` | Melhorar `buscarOcorrenciaDigital` p/ `protocolo_vds` + `linkRecursoOcorrencia` limpar vínculo ao remover |
| `livroDeOcorrencias.php` | Handlers AJAX de add/remove tag + UI (badges com id, botão ×, hover, right-click) |
| `meu.js` / `metodo.php` (opcional/legado) | Vínculo de livro no detalheRecurso mais fluido |

## Etapas

### 1. Migração de banco (`migrates/migrate_vds_integration.php` + novo script `migrates/migrate_tags_ajuda.php`)
- `ALTER TABLE ocorrencia_unidade_tag MODIFY tipo_vinculo ENUM('autora','citada','reclamada','unidade','notificacao','recurso','tag') DEFAULT 'citada'`.
- Criar índice único opcional `(ocorrencia_id, bloco, unidade)` para evitar duplicatas (com dedup prévio).

### 2. Camada de serviço (`classes/vds_ocorrencia_service.php`)
- **`vds_adicionar_tag_livre($ocorrenciaId, $tagInput)`**:
  1. Regex `numero/numero` primeiro → resolve `recurso` por `numero = numero_ano_virtual`.
     - Grava `ocorrencia_unidade_tag` (bloco `NOTIF`, unidade `123/2026`, tipo `notificacao`).
     - Grava `recurso_ocorrencia` (id_recurso ↔ id_ocorrencia) e `ocorrencia_recurso_link` (numero_recurso).
  2. Regex unidade `(Letra?)(número)` → grava tag bloco/unidade (tipo `unidade`).
  3. Fallback: tag livre (tipo `tag`).
  4. Idempotente: se já existe tag idêntica para a ocorrência, não duplica (retorna `already_exists`).
  - Retorno: `{success, tag: {...}, message}`.
- **`vds_remover_tag($tagId, $ocorrenciaId)`**:
  - DELETE na `ocorrencia_unidade_tag`.
  - Se bloco == `NOTIF`, remove também o vínculo em `ocorrencia_recurso_link` e `recurso_ocorrencia` (recurso com `numero = unidade`).
  - Retorno: `{success, tag_id, message}`.
- **`vds_vincular_unidade_tag`**: adicionar checagem de duplicado (ou chamar com `INSERT ... ON DUPLICATE`).

### 3. Handlers POST em `livroDeOcorrencias.php`
- `adicionar_tag_livre`: adicionar resposta JSON com `is_ajax` (mesmo padrão dos botões existentes).
- Novo `remover_tag`: lê `tag_id` + `ocorrencia_id`, chama `vds_remover_tag`, responde JSON.

### 4. Frontend em `livroDeOcorrencias.php`
- Substituir o `<form>` de tag por handler jQuery `submit` com `preventDefault`/`stopPropagation` (para não disparar o skeleton global).
- Funções JS:
  - `executarAcaoAjaxAdicionarTag(ocorrenciaId)` → POST, no success chama `renderTagBadge(tag)` e anexa ao `#tags-container`, limpa o input.
  - `renderTagBadge(tag)` → espelha os 3 tipos de badge do PHP (NOTIF / TAG / unidade).
  - `removerTag(tagId, ocorrenciaId)` → `confirm()` → POST `remover_tag` → `fadeOut`/remove do DOM.
  - Badges com `data-tag-id`; botão `×` no hover (CSS) + `contextmenu` (right-click) também dispara confirmação de remoção.
- Manter o conteúdo do formulário visualmente (input + botão "Tag").

### 5. Legado: `palco/detalheRecurso.php` + `meu.js` (preservar protocolo)
- `buscarOcorrenciaDigital($termo)`: aceitar também `protocolo_vds` como busca.
- `metodo.php` `buscarOcorrencia`/`vincularOcorrencia`: retornar JSON estruturado `{success, message}` e, no front, atualizar a lista de ocorrências vinculadas sem reload total (o vínculo já funciona; garantir que a auto-tag não quebre por causa do ENUM corrigido).

## Plano de Teste

1. **Schema:** rodar migração em staging; confirmar `SHOW CREATE TABLE ocorrencia_unidade_tag` com novo ENUM.
2. **Unidade:** na tela, digitar `B1108` → badge azul "🏢 Bloco B - Apt 1108" via AJAX, sem reload; conferir linha na tabela.
3. **Recurso:** digitar `123/2026` (recurso existente) → badge laranja "📋 Notificação 123/2026"; conferir `recurso_ocorrencia` e `ocorrencia_recurso_link`.
4. **Duplicado:** repetir a mesma tag → não duplicar (mensagem "já vinculada").
5. **Remoção:** hover mostra ×; clicar confirma e a badge some via AJAX; right-click também remove.
6. **Regressão:** responsabilidade, resolvido e lido continuam funcionando via AJAX.
7. **Legado:** na tela do recurso, "Vincular Livro" por protocolo continua funcionando.

## Perguntas em Aberto

- Confirmar se `ocorrencia_recurso_link.notificacao_id` deve receber o `numero` da notificação ou apenas `numero_recurso`.
- Preferência visual de remoção: apenas hover-×, apenas right-click, ou ambos (ambos é o planejado).
