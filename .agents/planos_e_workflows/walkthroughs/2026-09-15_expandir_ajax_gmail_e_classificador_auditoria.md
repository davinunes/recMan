# Walkthrough: Expansão do Gmail AJAX e Classificador Rápido de Auditoria / Pendência no Livro de Ocorrências

- **Data**: 2026-09-15
- **Status**: Concluído e Verificado
- **Contexto**: `index.php?pag=livroDeOcorrencias&id=`

---

## 1. Resumo da Entrega

Foram atendidas com sucesso as duas solicitações para o **Livro de Ocorrências**:

1. **Expansão Universal do AJAX para o Gmail**:
   - Eliminada a restrição que limitava a busca de e-mails via Gmail API v1 exclusivamente para ocorrências de `Monitoramento` (tipo 247).
   - Agora, qualquer chamado com número de protocolo (`protocolo_vds` ou ID) dispara a verificação assíncrona em segundo plano sem bloquear a interface.
   - Quando encontrado um e-mail correspondente, exibe o card moderno de integração com título inteligente (*"E-mail do Alarme / Monitoramento"* se for alarme, ou *"E-mail Vinculado ao Chamado (Protocolo X)"* para os demais chamados), com botões para "Ler E-mail" (modal rica em SPA) e "Ver no Gmail" (abertura direta no Gmail Web).

2. **Novo Classificador Rápido de Mensagem/Chamado (Auditoria / Pendência)**:
   - Adicionado botão de alternância rápida no cabeçalho do chamado:
     - Ícone `rate_review` do Material Icons.
     - Estado neutro: "Auditoria" com borda suave.
     - Estado ativo: "Em Auditoria" com fundo âmbar/laranja em destaque (`amber darken-2`).
     - Ação 100% silenciosa via AJAX (sem recarregar a tela e sem shimmer skeleton).
   - Destaque imediato no feed lateral (sidebar):
     - Badge visual `🔍 AUDITORIA` no card do chamado (`item-oco`).
     - Integração com o atributo `data-search` contendo os termos `"auditoria pendente"`, permitindo filtragem instantânea na caixa de "Busca Rápida".
   - Filtro na Visão Analítica:
     - Novo seletor no painel analítico com as opções *"Todas"*, *"🔍 Em Auditoria"* e *"Sem Auditoria"*, com query SQL otimizada via prepared statement.
   - Script de migração com idempotência criado em `migrates/migrate_auditoria_ocorrencias.php` adicionando a coluna `auditoria TINYINT(1) DEFAULT 0` e o índice `idx_ocorrencias_auditoria`.

---

## 2. Arquivos Modificados e Criados

| Arquivo | Ação | Descrição |
|---|---|---|
| [`migrates/migrate_auditoria_ocorrencias.php`](file:///e:/DEV/recMan/migrates/migrate_auditoria_ocorrencias.php) | **[NEW]** | Migração do banco para adicionar `auditoria` e índice `idx_ocorrencias_auditoria`. |
| [`livroDeOcorrencias.php`](file:///e:/DEV/recMan/livroDeOcorrencias.php) | **[MODIFY]** | Botão `#btn-ajax-auditoria`, endpoint `action=marcar_auditoria`, função JS `executarAcaoAjaxAuditoria`, remoção de trava em `verificarEIniciarBuscaGmail`, badge na sidebar e filtro analítico. |
| [`.agents/raciocinios/2026-09-15_expandir_ajax_gmail_e_classificador_auditoria.md`](file:///e:/DEV/recMan/.agents/raciocinios/2026-09-15_expandir_ajax_gmail_e_classificador_auditoria.md) | **[NEW]** | Registro de pensamento analítico e diagnóstico. |
| [`.agents/planos_e_workflows/planos/2026-09-15_expandir_ajax_gmail_e_classificador_auditoria.md`](file:///e:/DEV/recMan/.agents/planos_e_workflows/planos/2026-09-15_expandir_ajax_gmail_e_classificador_auditoria.md) | **[NEW]** | Plano técnico de implementação. |

---

## 3. Como Validar as Alterações

1. **Testar Busca do Gmail**:
   - Abrir qualquer ocorrência de tipo diferente de Monitoramento (ex: *Fale com a Administração*, *Fale com o Síndico*).
   - Constatar que a requisição assíncrona para o Gmail é disparada no background.
   - Caso exista e-mail com o protocolo, o card estilizado aparece automaticamente acima do feed de mensagens.

2. **Testar Classificador de Auditoria / Pendência**:
   - No topo do chamado aberto, clicar no botão **"Auditoria"**.
   - O botão alterna instantaneamente para **"Em Auditoria"** (fundo âmbar) e o item correspondente na lista à esquerda exibe a tag `🔍 AUDITORIA`.
   - Clicar novamente para desmarcar e ver a atualização instantânea.
   - Digitar `auditoria` no campo *"BUSCA RÁPIDA (TEXTO)"* na barra de filtros analíticos: apenas os chamados marcados serão mantidos visíveis na lista.
   - Utilizar o seletor *"AUDITORIA / PENDÊNCIA"* no formulário da Visão Analítica para filtrar pelo banco de dados.

3. **Execução da Migração no Servidor Remoto**:
   - Como desenvolvemos em servidor remoto, basta executar a migração no servidor ou via painel de migrações (`migrates.php`):
     ```bash
     php migrates/migrate_auditoria_ocorrencias.php
     ```
