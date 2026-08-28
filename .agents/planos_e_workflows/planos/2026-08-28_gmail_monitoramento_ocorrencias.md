# Plano: Busca e Visualização de E-mails do Gmail para Ocorrências de Monitoramento

## 1. Visão Geral
Implementar busca automática em background de e-mails no Gmail por número de protocolo para ocorrências da categoria **Monitoramento** no Livro de Ocorrências VDS (`livroDeOcorrencias.php`), exibindo um mini-card com assunto/remetente/data, link direto para a caixa de entrada no Gmail (`https://mail.google.com/mail/#inbox/{id}`) e um visualizador do conteúdo do e-mail em Modal.

---

## 2. Componentes e Arquivos a Modificar / Criar

### 2.1 Backend Core: `classes/mail_helper.php`
- Adicionar método `MailHelper::searchMessagesByProtocolo($protocolo)`:
  - Busca mensagens via Gmail API (`messages.list`) com query de assunto (`q=subject:"{protocolo}"`).
  - Obtém metadados (`Subject`, `From`, `Date`, `snippet`) do e-mail encontrado.
- Adicionar método `MailHelper::getMessageContent($messageId)`:
  - Recupera a mensagem completa (`messages.get?format=full`).
  - Parser recursivo para payload multipart (`text/html` e `text/plain`), decodificando Base64 URL-safe.

### 2.2 Endpoint / Ações AJAX: `livroDeOcorrencias.php`
- Adicionar tratamento para as ações AJAX:
  1. `action=buscar_gmail_protocolo`: recebe `protocolo`, chama `MailHelper::searchMessagesByProtocolo` e retorna JSON.
  2. `action=obter_conteudo_email_gmail`: recebe `message_id`, chama `MailHelper::getMessageContent` e retorna HTML/JSON.

### 2.3 Visualização no Frontend: `livroDeOcorrencias.php`
- Na função `vds_render_chat_detalhe_conteudo`:
  - Se for ocorrência do tipo Monitoramento (ou com protocolo presente), renderizar o contêiner discreto `#gmail-card-container` com atributos `data-protocolo`.
- No JavaScript:
  - Função assíncrona `carregarCardGmailProtocolo(protocolo)`:
    - Faz requisição AJAX em background.
    - Se encontrar e-mail, renderiza o mini-card estilizado (Material Icons, cores harmônicas, assunto, remetente, data).
    - Botão 1: Abrir no Gmail em nova aba (`https://mail.google.com/mail/#inbox/{id}`).
    - Botão 2: "Ler E-mail", que abre o Modal Materialize `#modal-visualizar-email-gmail` e carrega o conteúdo via AJAX.
- Modal HTML:
  - Criar o elemento `<div id="modal-visualizar-email-gmail" class="modal modal-fixed-footer">` no final do arquivo com suporte a iframe sandbox ou container estilizado com rolagem para visualização limpa e segura do e-mail.

---

## 3. Plano de Verificação
- Testar chamada com protocolo existente e não existente para garantir que requisições assíncronas não quebrem nem causem atrasos no carregamento do chat.
- Validar a formatação de links no padrão `https://mail.google.com/mail/#inbox/{mailId}`.
- Validar abertura e renderização do corpo do e-mail no modal.
