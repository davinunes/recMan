# Walkthrough: Tags AJAX no Livro de Ocorrências

**Data:** 2026-08-01
**Tela:** `index.php?pag=livroDeOcorrencias` (visões prática e analítica)
**Bug corrigido:** adição de tags falhava (ENUM inválido em modo estrito do MySQL).
**Funcionalidade nova:** adicionar/remover tags via AJAX sem reload, com confirmação (hover-× e clique direito).

## Alterações Efetuadas

### 1. `migrates/migrate_vds_integration.php`
- `tipo_vinculo` da tabela `ocorrencia_unidade_tag` ampliado para
  `ENUM('autora','citada','reclamada','unidade','notificacao','recurso','tag')`.

### 2. `migrates/migrate_tags_fix.php` (NOVO)
- Script idempotente que aplica o `MODIFY COLUMN` acima em bancos existentes.
- **Executar no servidor:** `php migrates/migrate_tags_fix.php`.

### 3. `classes/vds_ocorrencia_service.php`
- `vds_vincular_unidade_tag()`: agora idempotente (evita tags duplicadas) e retorna
  `{success, already_exists, tag_id, tag}`.
- `vds_adicionar_tag_livre()`:
  - Precedência: `numero/numero` → **recurso/notificação** (antes do padrão de unidade).
  - Unidade aceita letra antes OU depois do número: `B1108`, `1108`, `B-102`,
    `a1010`, `1010a`, `a 1010`, `1010 a`.
- `vds_vincular_tag_recurso()` (NOVO): grava tag NOTIF de exibição + vínculo funcional em
  `recurso_ocorrencia` (quando o recurso existe) e `ocorrencia_recurso_link`.
- `vds_remover_tag()` (NOVO): remove a tag; se era NOTIF, remove também os vínculos
  em `recurso_ocorrencia` e `ocorrencia_recurso_link`.

### 4. `livroDeOcorrencias.php`
- Handlers POST `adicionar_tag_livre` e `remover_tag` com resposta JSON (`is_ajax=1`).
- Form de tag convertido para input + botão `type=button` (sem submit de página),
  evitando conflito com o skeleton global.
- Badges com `data-tag-id`, botão **×** no hover e **clique direito** com confirmação.
- Exibição compacta: `🏢 A907` (antes "Bloco A - Apt 907").
- Funções JS: `renderTagBadge`, `executarAcaoAjaxAdicionarTag`, `removerTag`.

## Evidências / Verificação

- Estrutura real do banco consultada via `https://mini.davinunes.eti.br/estrutura.php`
  confirmou o ENUM original restrito e o `AUTO_INCREMENT=3` (apenas 2 registros).
- Revisão manual do fluxo AJAX: `index.php` roteia `is_ajax=1` → inclui a página →
  handler ecoa JSON e sai. Mesmo contrato dos botões já refatorados.

## Como Validar (no servidor)

1. Rodar `php migrates/migrate_tags_fix.php`.
2. Abrir `index.php?pag=livroDeOcorrencias&visao=pratico` e selecionar uma ocorrência.
3. Digitar `B1108` → badge `🏢 B1108` via AJAX (sem reload). Repetir → mensagem "já vinculado".
4. Digitar `123/2026` (recurso existente) → badge `📋 Notificação 123/2026` + vínculos gravados.
5. Hover na tag → clicar no × → confirmar → tag some via AJAX. Right-click também remove.
6. Conferir que os botões de responsabilidade/resolvido/lido continuam funcionando.

## Observações

- O caso "recurso" grava também em `recurso_ocorrencia`/`ocorrencia_recurso_link`, então o
  vínculo passa a aparecer na tela de análise do recurso (preservando o legado por protocolo).
- Não executado localmente (ambiente sem PHP) — validar no servidor.
