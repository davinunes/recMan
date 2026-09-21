# Diagnóstico e Raciocínio: Atualização da Data de Pareceres Não Enviados

**Data**: 2026-09-21
**Tópico**: Atualização automática da data em pareceres de recursos ainda não enviados (`pag=emiteParecer`)

---

## 1. Contexto e Problema Relatado

Ao abrir a emissão de parecer (`index.php?pag=emiteParecer&rec=339/2026`), pareceres que foram salvos em rascunho em uma data anterior (ex: há alguns dias) mantêm a data da primeira criação (`parecer.data`). 
Se o conselheiro enviar o parecer hoje, o PDF e o e-mail saem com a data antiga do primeiro salvamento em vez da data atual de emissão/envio.

**Regra solicitada pelo usuário:**
- Pareceres ainda **não enviados** (`concluido != 1`): a data deve ser atualizada para a data corrente (`CURDATE()` / `date('Y-m-d')`).
- Pareceres **já enviados** (`concluido == 1`): a data deve ser **mantida intacta** (data em que foi efetivamente concluído/enviado).

---

## 2. Investigação do Código Existente

1. **`palco/emiteParecer.php`**:
   - `getParecer($_GET['rec'])` carrega a linha da tabela `conselho.parecer`.
   - `$parecerJaFoiEnviado = $parecer["concluido"] == 1 ? true : false;`
   - `$pdf['data_emissao'] = date('Y-m-d', strtotime($parecer['data']));`
   - Se o parecer não foi enviado (`!$parecerJaFoiEnviado`), `$parecer['data']` contém a data do primeiro `INSERT`, sem atualização posterior.

2. **`classes/repositorio.php` (`updateParecer($dados)`)**:
   - Quando o usuário edita o parecer (AJAX `metodo=editaParecer`), a função `updateParecer` atualiza os campos `resultado`, `assunto`, `notificacao`, `analise`, `conclusao` e `modelo`, mas **não atualiza a coluna `data`**.

3. **`api/recursos.php`**:
   - Monta a prévia do PDF com `$parecerRow['data']` sem checar se está concluído.

---

## 3. Solução Proposta

1. **`palco/emiteParecer.php`**:
   - Após obter `$parecer` e definir `$parecerJaFoiEnviado`:
   - Se `!$parecerJaFoiEnviado`:
     - Comparar `$parecer['data']` com a data atual (`date('Y-m-d')`).
     - Se diferente, executar um `UPDATE conselho.parecer SET data = CURDATE() WHERE id = '$id' AND (concluido IS NULL OR concluido = 0)`.
     - Atualizar a variável em memória `$parecer['data'] = date('Y-m-d')`.
   - Assim, a prévia do PDF e o e-mail em gerados refletem a data do dia atual de emissão.

2. **`classes/repositorio.php` (`updateParecer`)**:
   - Adicionar a atualização do campo `data` na query SQL de `updateParecer`:
     `data = IF(concluido = 1, data, CURDATE())`
   - Desta forma, salvamentos manuais em rascunho também renovam a data para o dia atual enquanto não enviado, sem alterar pareceres já finalizados.

3. **`api/recursos.php`**:
   - Garantir que se `concluido != 1`, `data_emissao` utilize a data atual `date('Y-m-d')`.

---

## 4. Impacto e Riscos

- **Risco**: Nenhum risco para pareceres finalizados (`concluido == 1`), pois a regra garante explicitamente a imutabilidade da data quando `concluido = 1`.
- **Efeito**: Soluciona o problema de pareceres rascunho saindo com datas defasadas no PDF e no e-mail de envio.
