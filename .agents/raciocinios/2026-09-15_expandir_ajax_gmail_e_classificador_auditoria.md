# Raciocínio Diagnóstico e Arquitetura: Expansão do Gmail AJAX e Classificador de Auditoria/Pendente no Livro de Ocorrências

- **Data**: 2026-09-15
- **Arquivo Alvo**: `livroDeOcorrencias.php`
- **Tópico**: Habilitação universal da verificação de e-mails via Gmail API v1 por protocolo e introdução do classificador rápido de chamados (Auditoria / Pendência) com suporte a filtros e busca.

---

## 1. Contexto e Objetivos

O usuário solicitou duas melhorias essenciais na tela do Livro de Ocorrências (`index.php?pag=livroDeOcorrencias&id=`):
1. **Expansão do AJAX do Gmail para todos os tipos de ocorrência**:
   - Atualmente, a busca de e-mails vinculados via Gmail API só era disparada se a ocorrência fosse do tipo `Monitoramento` (tipo 247).
   - O objetivo é liberar a verificação assíncrona para qualquer ocorrência que possua número de protocolo (`protocolo_vds` ou ID), exibindo o card informativo de e-mail associado quando localizado.
2. **Novo Classificador Rápido de Mensagem/Chamado (Auditoria / Anotação de Pendência)**:
   - Permitir marcar de forma rápida ocorrências onde alguma providência ou análise ficou pendente.
   - Ícone sugestivo (ex: auditoria `rate_review` ou anotação `edit_note`).
   - Ação rápida via AJAX (sem recarregar e sem skeleton piscar).
   - Destaque visual tanto no cabeçalho do chat quanto no card do chamado na sidebar esquerda.
   - Habilitação de filtros futuros (na barra analítica e na busca instantânea).

---

## 2. Análise do Fluxo do Gmail

### 2.1 Backend Atual
- Em `MailHelper::searchMessagesByProtocolo($protocolo)` (`classes/mail_helper.php`):
  - A rotina já é 100% genérica. Ela recebe qualquer string de protocolo, busca primeiro por `subject:"{protocolo}"` e depois faz fallback para a query geral com o protocolo.
  - O endpoint AJAX em `livroDeOcorrencias.php` (`action === 'buscar_gmail_protocolo'`) apenas encaminha `$protocolo` para o `MailHelper` e devolve o JSON com `found`, `subject`, `from`, `date`, `id`, `webLink`.
  
### 2.2 Frontend Atual e Trava Identificada
- Em `livroDeOcorrencias.php`:
  - Linha 173: `$isMonitoramento = ($tipoId === 247 || stripos($infoTipo['nome'] ?? '', 'Monitoramento') !== false);`
  - Linha 176: `#gmail-card-container` recebia `data-is-monitoramento="0|1"`.
  - Linha 2002-2005: `if (!protocolo || !isMonitoramento) return;`
- **Solução**:
  - Remover a barreira de `!isMonitoramento`. Qualquer chamado que tenha `protocolo` disparará a busca assíncrona discreta.
  - Se for localizado e-mail, o card será exibido com título adaptado:
    - Se for Monitoramento: *"E-mail do Alarme / Monitoramento"*
    - Se for outro tipo: *"E-mail Vinculado ao Chamado (Protocolo X)"*

---

## 3. Análise do Classificador Rápido (Auditoria / Pendência)

### 3.1 Modelo de Dados e Persistência
- Na tabela `ocorrencias`:
  - `responsabilidade` é do tipo `enum('sindico','sub') DEFAULT NULL`.
  - Um chamado pode ser de responsabilidade do Síndico ou Subsíndico e, simultaneamente, estar sob auditoria do Conselho ou com pendência.
  - Portanto, a melhor arquitetura relacional e desacoplada é criar uma coluna específica:
    `auditoria TINYINT(1) NOT NULL DEFAULT 0` com índice `idx_ocorrencias_auditoria`.
  - Será criado o script de migração `migrates/migrate_auditoria_ocorrencias.php`.

### 3.2 Interface com o Usuário (UI/UX)
- **Cabeçalho de Ações Rápidas (`chat-header`)**:
  - Adicionar botão de ação silenciosa via AJAX ao lado dos botões de responsabilidade / resolvido / lido.
  - Estado inativo: botão neutro claro, ícone `rate_review` cinza, rótulo "Auditoria", tooltip "Marcar chamado em Auditoria / Pendente".
  - Estado ativo: botão em destaque âmbar/laranja (`amber darken-2`), ícone contrastante, rótulo "Em Auditoria", tooltip "Chamado marcado em Auditoria / Pendente (Clique para desmarcar)".
- **Card da Sidebar (`item-oco`)**:
  - Quando `auditoria = 1`, adicionar um badge discreto e elegante: `🔍 AUDITORIA` (estilo tag amber clara), informando visualmente na lista quais chamados requerem atenção.
  - No atributo `data-search`, incluir `"auditoria pendente"`, permitindo filtragem imediata na caixa de texto "Busca Rápida".
- **Painel de Filtros Analíticos**:
  - Adicionar seletor `AUDITORIA / PENDÊNCIA` com opções:
    - *Todas* (padrão)
    - *Em Auditoria / Pendentes* (`auditoria = 1`)
    - *Sem Pendência* (`auditoria = 0`)
  - Atualizar a query SQL da visão analítica para filtrar por este campo quando preenchido.
