# Plano de Implementação: Atualização de Data para Pareceres Não Enviados

**Data**: 2026-09-21
**Solicitação**: Atualizar a data dos pareceres ainda não enviados para a data atual, preservando a data original de pareceres já enviados.

---

## 1. Descrição do Problema
Atualmente, quando um parecer é criado/salvo como rascunho em uma data, a coluna `data` na tabela `conselho.parecer` armazena o dia do primeiro salvamento. Caso o parecer seja visualizado ou enviado dias depois, o sistema continua utilizando a data inicial.

## 2. Mudanças Propostas

### 2.1 [palco/emiteParecer.php](file:///e:/DEV/recMan/palco/emiteParecer.php)
- Após carregar o parecer via `getParecer($_GET['rec'])`:
  - Verificar se `$parecerJaFoiEnviado` é falso (`concluido != 1`).
  - Se falso e a data registrada (`$parecer['data']`) for anterior/diferente de hoje (`date('Y-m-d')`):
    - Executar `UPDATE conselho.parecer SET data = CURDATE() WHERE id = '$idParecer' AND (concluido IS NULL OR concluido = 0)`.
    - Atualizar a variável em memória `$parecer['data'] = date('Y-m-d')`.

### 2.2 [classes/repositorio.php](file:///e:/DEV/recMan/classes/repositorio.php)
- Na função `updateParecer($dados)`:
  - Incluir a atualização da coluna `data` na SQL:
    `data = IF(concluido = 1, data, CURDATE())`
  - Garante que salvamentos via formulário de edição atualizam a data caso o parecer ainda não esteja concluído.

### 2.3 [api/recursos.php](file:///e:/DEV/recMan/api/recursos.php)
- Ao montar a estrutura de `$pdfData`:
  - Garantir que se `$parecerRow['concluido'] != 1`, a `data_emissao` seja a data corrente (`date('Y-m-d')`).

---

## 3. Plano de Verificação

### 3.1 Verificação Manual / Revisão de Código
- Confirmar que a lógica verifica rigorosamente a condição `concluido == 1` antes de preservar a data.
- Garantir que a sintaxe SQL `IF(concluido = 1, data, CURDATE())` e a cláusula `WHERE (concluido IS NULL OR concluido = 0)` protejam pareceres já enviados contra alterações acidentais de data.
