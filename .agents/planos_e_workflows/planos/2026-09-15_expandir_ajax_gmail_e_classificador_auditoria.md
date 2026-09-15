# Plano de Implementação: Expansão do Gmail AJAX e Classificador de Auditoria/Pendência no Livro de Ocorrências

- **Data**: 2026-09-15
- **Status**: Proposto / Aguardando Aprovação
- **Contexto**: `index.php?pag=livroDeOcorrencias&id=`

---

## 1. Visão Geral do Problema e Solução

### 1.1 Expansão do Gmail AJAX
Atualmente o AJAX de busca de e-mails no Gmail é acionado apenas para ocorrências classificadas como `Monitoramento` (tipo 247). O objetivo é expandir essa checagem para **todos os tipos de ocorrência** que possuam um protocolo (`protocolo_vds` ou ID), mantendo a busca em segundo plano e exibindo o card estilizado com ações de visualização em modal e abertura direta no Gmail Web.

### 1.2 Classificador Rápido da Mensagem / Chamado (Auditoria / Pendente)
Criar um classificador rápido acionável com um clique no cabeçalho do chamado:
- Ícone sugestivo de auditoria/anotação (`rate_review` do Material Icons).
- Persistência no banco de dados na tabela `ocorrencias` através do campo `auditoria TINYINT(1) DEFAULT 0`.
- Execução silenciosa via AJAX (sem recarregar e sem skeleton shimmer).
- Destaque visual na lista lateral (sidebar feed) com badge `🔍 AUDITORIA` para identificar facilmente os chamados pendentes.
- Filtro analítico dedicado ("Todas", "Em Auditoria / Pendentes", "Sem Pendência") e integração com a busca rápida em tempo real.

---

## 2. Modificações Propostas

### 2.1 Banco de Dados & Migração
#### [NEW] [`migrates/migrate_auditoria_ocorrencias.php`](file:///e:/DEV/recMan/migrates/migrate_auditoria_ocorrencias.php)
- Script de migração que adiciona com idempotência a coluna `auditoria TINYINT(1) NOT NULL DEFAULT 0` e o índice `idx_ocorrencias_auditoria` na tabela `ocorrencias`.

---

### 2.2 Livro de Ocorrências
#### [MODIFY] [`livroDeOcorrencias.php`](file:///e:/DEV/recMan/livroDeOcorrencias.php)

1. **Expansão do Gmail AJAX**:
   - Ajustar a checagem no container `#gmail-card-container` para receber `data-protocolo` independentemente do tipo.
   - Na função JavaScript `verificarEIniciarBuscaGmail()`, remover a trava `!isMonitoramento`, disparando para qualquer chamado com protocolo.
   - Ajustar o título dinâmico do card retornado: se for Monitoramento exibe *"E-mail do Alarme / Monitoramento"*; para os demais tipos exibe *"E-mail Vinculado ao Chamado (Protocolo X)"*.

2. **Botão de Ação Rápida no Cabeçalho do Chat**:
   - Adicionar o botão `#btn-ajax-auditoria` na barra de ações rápidas (`chat-header`), exibindo visualmente se o chamado está ou não sob auditoria/pendência.
   - Adicionar a função JavaScript `executarAcaoAjaxAuditoria(ocorrenciaId, novoVal)` que faz a chamada assíncrona POST `action: 'marcar_auditoria'`, atualiza o botão na hora e aplica/remove a badge no card da sidebar (`#item-oco-` + id).

3. **Endpoint AJAX de Auditoria no Backend**:
   - Adicionar tratamento para `action === 'marcar_auditoria'`:
     - Validação dos parâmetros `ocorrencia_id` e `auditoria_val`.
     - Execução via prepared statement: `UPDATE ocorrencias SET auditoria = ? WHERE id = ?`.
     - Retorno JSON estruturado `{ success: true, action: 'marcar_auditoria', ocorrencia_id: ..., auditoria: ... }`.

4. **Feed Lateral (Sidebar) & Injeção Dinâmica**:
   - No loop PHP de renderização dos itens da sidebar (`item-oco`), incluir a badge visual `🔍 AUDITORIA` quando `$oco['auditoria'] == 1`.
   - Adicionar os termos `"auditoria pendente"` no atributo `data-search` do elemento quando ativo, permitindo que a busca rápida do cliente filtre instantaneamente.
   - Atualizar a função JS `injetarNovosItensDOM` para incluir o markup da badge caso chegue uma ocorrência com `auditoria == 1`.

5. **Painel de Filtros Analíticos**:
   - Adicionar o campo `AUDITORIA` no formulário de filtros analíticos:
     - *Todas*
     - *Em Auditoria / Pendentes*
     - *Sem Auditoria*
   - No backend, incluir o filtro correspondente na cláusula SQL `WHERE`.

---

## 3. Plano de Verificação

### Verificação Manual
1. **Verificação do AJAX Gmail**:
   - Abrir ocorrência de outro tipo (ex: *Fale com a Administração*, *Fale com o Síndico*, *Fale com o Conselho*).
   - Constatar o disparo da requisição AJAX `action=buscar_gmail_protocolo` no console de rede.
   - Se houver e-mail com o protocolo, verificar a renderização do card e o funcionamento do botão "Ler E-mail" (modal) e "Ver no Gmail".
2. **Verificação do Classificador de Auditoria**:
   - Clicar no botão "Auditoria" no topo de uma ocorrência.
   - Verificar que a ação executa instantaneamente via AJAX, alterando o botão para estilo ativo ("Em Auditoria") e exibindo a badge `🔍 AUDITORIA` no item correspondente da lista lateral.
   - Clicar novamente e verificar a remoção da marcação.
   - Na Visão Analítica, selecionar o filtro "Em Auditoria / Pendentes" e verificar que apenas os chamados marcados são listados.
   - Na caixa de busca rápida por texto, digitar "auditoria" e verificar o filtro instantâneo na lista.
