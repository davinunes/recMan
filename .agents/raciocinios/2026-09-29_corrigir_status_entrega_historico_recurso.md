# Raciocínio: Correção do Status de Entregas/Encomendas no Histórico e Recurso

## Contexto do Problema
O usuário identificou que na página de Histórico (`index.php?pag=historico`) e no detalhe do Recurso (`index.php?pag=recurso&rec=344/2026`), as entregas da portaria estavam aparecendo com Status **"Entregue"**, mesmo quando o morador ainda **não retirou** a encomenda.

Comparação de payloads reais da API VDS v8:
1. **Não Retirado / Pendente:**
   - `"retiradoPor": null`
   - `"retiradoMorador": false`
   - `"statusDetalhado": "Notificado"`
   - `"dtFim": null`
   - `"eventos"` terminam em status `"Not. morador"` (uuid "7").

2. **Retirado / Entregue:**
   - `"retiradoPor": { "nome": "..." }`
   - `"retiradoMorador": true`
   - `"statusDetalhado": "Entregue"`
   - `"dtFim": "18/09/2026 18:29:52"`
   - `"eventos"` terminam em status `"Ent. morador"` (uuid "8").

## Diagnóstico da Causa Raiz
1. Em `classes/vds_acesso_service.php` na função `vds_get_entregas_unidade`:
   - A linha 320 executava `$statusStr = vds_extract_string_value($ent['status'] ?? ($ent['situacao'] ?? null), 'Entregue');`. Como a API da VDS muitas vezes não traz o campo `status` direto ou ele não é uma string simples, caía no fallback hardcoded `'Entregue'`.
   - Não verificava os campos `retiradoMorador`, `statusDetalhado`, `dtFim`, `retiradoPor` ou os `eventos`.
2. Em `meu.js`:
   - Na função `window.renderToolsetEncomendas`, a renderização inicial dependia desse status incorreto e a busca assíncrona por `obterDetalhesEntrega` não atualizava a coluna de status da linha.
   - O modal de inspeção de entrega em `meu.js` lia `d.status` (que vinha nulo no detalhe da VDS, mostrando 'N/A' ou falhando).
3. Em `palco/detalheRecurso.php`:
   - A tabela do acelerador de entregas não possuía uma coluna explícita de status e o modal de inspeção não destacava se o morador havia ou não retirado a encomenda, sugerindo a data de chegada como data de retirada de forma ambígua quando `dtFim` era nulo.

## Solução Planejada
1. Criar helper robusto `vds_determinar_status_entrega($ent)` em `classes/vds_acesso_service.php`.
2. Incluir `statusDetalhado`, `retiradoMorador`, `dtFim` e `retiradoPor` no retorno de `vds_get_entregas_unidade` e `vds_get_entrega_detalhe`.
3. Ajustar `metodo.php` no cálculo de `entregasPendentes`.
4. Atualizar `meu.js` para renderizar o badge correto (Verde para Entregue, Âmbar para Notificado/Pendente) e atualizar dinamicamente no carregamento assíncrono.
5. Atualizar `palco/detalheRecurso.php` com coluna de status e avisos visuais claros no modal de inspeção.
