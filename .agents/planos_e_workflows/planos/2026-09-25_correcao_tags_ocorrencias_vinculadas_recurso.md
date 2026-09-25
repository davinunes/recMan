# Plano de Implementação: Vínculo Multidirecional de Ocorrências e Tags por Recurso

**Data:** 2026-09-25  
**Módulo:** Recurso vs Ocorrências (VDS / Livro de Ocorrências)  
**Objetivo:** Garantir que qualquer ocorrência vinculada por tag (ex: `352/2026`, `NOTIF`, `ocorrencia_recurso_link` ou `recurso_ocorrencia`) seja listada na tela do recurso (`index.php?pag=recurso&rec=352/2026`), exibindo suas tags associadas.

---

## Modificações Propostas

### 1. `classes/repositorio.php`
- Expandir a função `getOcorrenciasVinculadas($id_recurso, $numero_recurso = null)`:
  - Resolver o número do recurso via query caso `$numero_recurso` não seja informado.
  - Tratar variações de ano de 4 dígitos (`2026`) e 2 dígitos (`26`).
  - Executar query unificada buscando por `ro.id_recurso`, `l.numero_recurso` e `t.unidade` com `tipo_vinculo IN ('notificacao', 'recurso')` ou `bloco = 'NOTIF'`.
  - Executar auto-healing gravando os registros encontrados em `recurso_ocorrencia`.
- Adicionar função helper `getTagsOcorrencia($ocorrenciaId)` para recuperar tags de `ocorrencia_unidade_tag`.

### 2. `classes/vds_ocorrencia_service.php`
- Em `vds_vincular_tag_recurso($ocorrenciaId, $numeroRecurso)`:
  - Buscar `$recursoId` na tabela `recurso` aceitando tanto a versão de 4 dígitos quanto a de 2 dígitos do ano.

### 3. `palco/detalheRecurso.php`
- Atualizar a chamada: `$ocorrenciasVinculadas = getOcorrenciasVinculadas($esseRecurso, $result['numero']);`
- Renderizar badges com as tags da ocorrência e disponibilizar atalhos para abrir o chat local do livro de ocorrências (`index.php?pag=livroDeOcorrencias&id=...`).

---

## Verificação
- Revisar a compatibilidade da query SQL e prepared statements.
- Garantir ididempotência e inexistência de duplicações na listagem.
