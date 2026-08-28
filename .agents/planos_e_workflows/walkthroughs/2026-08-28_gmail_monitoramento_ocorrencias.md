# Walkthrough: Busca de E-mails do Gmail para Ocorrências de Monitoramento e Visualização em Modal

Implementação da busca automática e assíncrona de e-mails no Gmail por número de protocolo para ocorrências da categoria **Monitoramento** no Livro de Ocorrências VDS (`livroDeOcorrencias.php`), com exibição de mini-card informativo, link direto para a caixa de entrada (`https://mail.google.com/mail/#inbox/{id}`) e leitor de e-mail integrado em Modal.

---

## 1. Modificações Realizadas

### 1.1 Backend Core (`classes/mail_helper.php`)
- **`MailHelper::searchMessagesByProtocolo($protocolo)`**:
  - Busca mensagens no Gmail pela query `subject:"{protocolo}"` (com fallback para busca ampla pelo termo `{protocolo}`).
  - Extrai metadados essenciais (`Subject`, `From`, `Date`, `snippet`) via `messages.get?format=metadata`.
  - Constrói o link direto: `https://mail.google.com/mail/#inbox/{message_id}`.
- **`MailHelper::getMessageContent($messageId)`**:
  - Obtém a mensagem completa (`format=full`).
  - Decodifica recursivamente partes `text/html` e `text/plain` em Base64 URL-safe.
  - Retorna cabeçalhos formatados e corpo pronto para exibição segura.

### 1.2 Handlers AJAX (`livroDeOcorrencias.php`)
- `action=buscar_gmail_protocolo`: Retorna JSON com os dados do e-mail encontrado para o protocolo solicitado.
- `action=obter_conteudo_email_gmail`: Retorna o corpo completo e dados do e-mail para exibição no modal.

### 1.3 Interface e Interação (`livroDeOcorrencias.php`)
- **Container Dinâmico**: Inserido na view de chat `#gmail-card-container` com `data-protocolo` e `data-is-monitoramento`.
- **Busca em Background**:
  - Disparada via `verificarEIniciarBuscaGmail()` tanto no carregamento inicial da página quanto na seleção assíncrona de itens (`selecionarOcorrencia`).
  - Não bloqueia a interface nem o carregamento de mensagens.
- **Mini-Card do Gmail**:
  - Exibe ícone do Gmail, etiqueta de alarme, assunto do e-mail, remetente e data.
  - Botão **"Ver no Gmail"**: abre diretamente a mensagem na caixa de entrada em nova aba.
  - Botão **"Ler E-mail"**: abre o modal Materialize.
- **Modal Integrado**:
  - `#modal-visualizar-email-gmail`: modal responsivo com spinner de carregamento, cabeçalho detalhado e visualização do corpo do e-mail com scroll.

---

## 2. Validação e Testes
- **Compatibilidade do Link**: Utiliza exatamente a rota solicitada `https://mail.google.com/mail/#inbox/{message_id}`.
- **Resiliência**: Tratamento com fallbacks caso o e-mail não possua corpo HTML, se o token estiver ausente/expirado, ou caso o protocolo não tenha mensagem correspondente (o card permanece oculto sem causar ruído visual).
