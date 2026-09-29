# Walkthrough: Correção do Status de Entregas da VDS no Histórico e Recurso

## Resumo das Alterações
Corrigido o problema onde encomendas/entregas da portaria na API Vida de Síndico (VDS) eram incorretamente marcadas como "Entregue" no **Histórico da Unidade** (`index.php?pag=historico`) e no **Detalhe do Recurso** (`index.php?pag=recurso&rec=344/2026`), mesmo quando o morador havia sido apenas notificado e a correspondência ainda não havia sido retirada.

---

## Detalhamento das Alterações Realizadas

### 1. Backend: Determinação de Status Inteligente (`classes/vds_acesso_service.php`)
- **Nova função `vds_determinar_status_entrega($ent)`**:
  - Verifica os campos `retiradoMorador` (`true`/`false`), `retiradoPor` (`object` ou `null`) e `dtFim` (data de retirada ou `null`).
  - Se retirado pelo morador: define status como `"Entregue"`.
  - Se **não retirado**: utiliza o valor real de `statusDetalhado` (ex: `"Notificado"`, `"Recebido"`, `"Encaminhado"`), inspeciona os `eventos` da portaria ou a flag `notificado`.
  - Removeu o antigo fallback hardcoded `'Entregue'`.
- **Ajustes em `vds_get_entregas_unidade` e `vds_get_entrega_detalhe`**:
  - Retornam agora a estrutura completa com `status`, `statusDetalhado`, `retiradoMorador`, `retiradoPor` e `dtFim`.

### 2. Contadores do Toolset (`metodo.php`)
- No cálculo do Toolset da Unidade (`metodo.php`), o contador de entregas pendentes (`entregasPendentes`) passa a identificar perfeitamente todas as entregas não retiradas (`retiradoMorador === false` ou status diferente de entregue/retirado).

### 3. Frontend & Modal do Histórico (`meu.js`)
- **Tabela de Encomendas**:
  - Status renderizado com badge âmbar para `"Notificado"` / `"Pendente"` e verde apenas para `"Entregue"` / `"Retirado"`.
  - O carregamento assíncrono em segundo plano agora atualiza a coluna `.col-status` dinamicamente ao receber a resposta da API VDS.
- **Modal de Inspeção de Encomenda (`modalDetalhesEntrega`)**:
  - Exibe card destacado informando se a encomenda foi retirada (com data/hora e nome do retirante) ou se ainda está aguardando retirada pelo morador na portaria.
  - Exibe o histórico de eventos da portaria (`d.eventos`).

### 4. Acelerador de Entregas no Julgamento de Recursos (`palco/detalheRecurso.php`)
- Adicionada coluna **Status** na tabela de encomendas do acelerador lateral com badge visual colorido.
- Atualização assíncrona da coluna de status em segundo plano.
- No modal de inspeção e formulário de sugestão de ciência:
  - Destaca o status da correspondência.
  - Se ainda não foi retirada pelo morador, exibe alerta informativo: *"⚠️ Encomenda registrada na portaria em X, mas ainda NÃO retirada pelo morador (Status: Notificado)."*
