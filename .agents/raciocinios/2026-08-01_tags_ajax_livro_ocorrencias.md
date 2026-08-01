# Raciocínio: Refatoração do Mecanismo de Tags para AJAX no Livro de Ocorrências

**Data:** 2026-08-01
**Tela:** `index.php?pag=livroDeOcorrencias` (visão prática e analítica)
**Tarefa:** Converter o mecanismo de tags (adicionar/remover) para requisições AJAX sem reload, corrigir o bug que impede a adição de tags e implementar remoção de tags.

---

## 1. Contexto / Estado Atual

A tela `livroDeOcorrencias.php` já teve os botões de ação refatorados para AJAX silencioso (sem reload e sem skeleton):

- `atualizar_responsabilidade` → `executarAcaoAjaxResponsabilidade()`
- `marcar_resolvido` → `executarAcaoAjaxResolvido()`
- `marcar_como_lido` → `executarAcaoAjaxLido()`

Todos seguem o mesmo padrão: POST para `index.php?pag=livroDeOcorrencias` com `is_ajax=1`, resposta JSON `{success, action, ...}`, e atualização do DOM via jQuery no callback `success`.

**O mecanismo de tags NÃO foi refatorado:**
- O form de tag (linhas ~789–797) ainda é um submit de página inteira.
- O handler POST `adicionar_tag_livre` (linhas 140–150) não trata `is_ajax`, ou seja, uma chamada AJAX receberia HTML inteiro de volta em vez de JSON.

## 2. Causa Raiz do "Não Está Funcionando"

A estrutura real do banco (obtida via `https://mini.davinunes.eti.br/estrutura.php`):

```sql
CREATE TABLE `ocorrencia_unidade_tag` (
  ...
  `tipo_vinculo` enum('autora','citada','reclamada') DEFAULT 'citada',
  ...
) ENGINE=InnoDB AUTO_INCREMENT=3
```

Porém o código insere valores fora desse ENUM:

- `vds_adicionar_tag_livre()` → `vds_vincular_unidade_tag(..., 'notificacao')`, `'unidade'`, `'tag'`
- `linkRecursoOcorrencia()` (repositorio.php) → `vds_vincular_unidade_tag(..., 'recurso')`

**Nenhum desses valores pertence ao ENUM.** Em modo estrito do MySQL (`STRICT_TRANS_TABLES`, padrão), o `INSERT` falha com "Incorrect enum value" e a tag nunca é gravada — `mysqli_stmt_execute` retorna `false` e `vds_vincular_unidade_tag` devolve `['success' => false]`. Por isso "não está funcionando".

Confirmação indireta: `AUTO_INCREMENT=3` na tabela (apenas 2 registros, provavelmente criados por fluxos que usam `autora`/`citada`/`reclamada`).

## 3. Semântica das Tags (Especificação do Usuário)

- **Unidade:** input com "número com letra" (ex: `B1108`, `1108`, `A-102`) → vincula a ocorrência à unidade.
- **Recurso/Notificação:** input `numero/numero` (ex: `123/2026`, `45/26`) → vincula a ocorrência ao recurso, cujo id composto (`recurso.numero`) é o mesmo `numero_ano_virtual` da notificação respectiva.
- **Remoção:** confirmar clicando com botão direito na tag OU botão de excluir (×) que aparece no hover.

## 4. Mapeamento de Tabelas

| Tabela | Uso atual | Papel no novo fluxo |
|---|---|---|
| `ocorrencia_unidade_tag` | Display de tags + consulta `vds_get_chamados_unidade` | Manter como tag de exibição. Corrigir ENUM. |
| `recurso_ocorrencia` | Vínculo legado `id_recurso` ↔ `id_ocorrencia` (lido por `getOcorrenciasVinculadas` e `vds_get_chamados_unidade`) | Escrever aqui no caso "recurso" para o vínculo aparecer na tela do recurso. |
| `ocorrencia_recurso_link` | Nunca usada (criada em migração) | Preenchê-la com `numero_recurso` para completude/legado. |
| `notificacoes` / `recurso` | Domínio | Resolver existência do recurso por `numero = numero_ano_virtual`. |

## 5. Decisões de Design

1. **Corrigir o schema** com migração idempotente: converter `tipo_vinculo` para suportar os novos valores (`unidade`, `notificacao`, `recurso`, `tag`) mantendo os legados (`autora`, `citada`, `reclamada`).
2. **Serviço:** `vds_adicionar_tag_livre()` passa a:
   - Detectar `numero/numero` ANTES de unidade (regra de precedência).
   - No caso recurso: inserir tag NOTIF + gravar vínculo em `recurso_ocorrencia` e `ocorrencia_recurso_link` (se o recurso existir).
   - No caso unidade: inserir tag de bloco/unidade.
   - Ser idempotente (evitar duplicação de tags idênticas).
3. **Novo serviço:** `vds_remover_tag($tagId, $ocorrenciaId)` → DELETE na tag; se for NOTIF, remove também o vínculo em `recurso_ocorrencia`/`ocorrencia_recurso_link`.
4. **Frontend:** handlers AJAX `adicionar_tag_livre` e `remover_tag` com resposta JSON (mesmo padrão dos botões já refatorados).
5. **UI:** badges com `data-tag-id`; botão × no hover (com `confirm()`) + clique direito como alternativa; nova tag adicionada via JS sem reload.
6. **Legado detalheRecurso (secundário):** manter busca por protocolo/ID, mas melhorar `buscarOcorrenciaDigital` para aceitar `protocolo_vds` e tornar o vínculo fluido (JSON + refresh parcial).

## 6. Riscos / Observações

- Alterar ENUM exige migração em produção (idempotente, com `MODIFY COLUMN`).
- A ordem de detecção do regex é crítica: `numero/numero` (recurso) deve ser testado antes do padrão de unidade, senão `123/2026` pode virar "unidade Z/2026".
- O `vds_get_ocorrencia_detalhe()` já carrega `tagsUnidades`; nada a mudar na leitura.
- `is_ajax` já é usado pelos outros handlers; seguir o mesmo contrato.
