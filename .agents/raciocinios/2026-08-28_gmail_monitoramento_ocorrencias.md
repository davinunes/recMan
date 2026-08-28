# Raciocínio: Integração Gmail com Livro de Ocorrências VDS (Monitoramento)

## 1. Contexto e Necessidade
Nas ocorrências do tipo **Monitoramento** (ID 247) no `livroDeOcorrencias.php`, os chamados frequentemente têm correspondência direta com disparos de alarme ou e-mails de monitoramento enviados para a conta Gmail do conselho.
O usuário solicitou:
1. Buscar em background (assíncrono) e-mails no Gmail cujo assunto contenha o número do protocolo da ocorrência.
2. Injetar na tela um pequeno card discreto e elegante com o assunto, remetente e data do e-mail.
3. Link direto para o Gmail web no formato já consagrado no sistema (`https://mail.google.com/mail/#inbox/{mailId}`).
4. Possibilidade de ler o conteúdo completo do e-mail diretamente em um **Modal** no sistema ao clicar no card.

## 2. Análise Técnica e Componentes

### 2.1 Autenticação e Escopo Gmail
- O sistema já possui tokens OAuth salvos em banco e renovação automática via `verificarToken()`.
- O escopo autorizado é `https://www.googleapis.com/auth/gmail.modify`, que permite leitura total (`messages.list` e `messages.get`).

### 2.2 Métodos a Adicionar em `MailHelper` (`classes/mail_helper.php`)
1. `searchMessagesByProtocolo($protocolo)`:
   - Executa `GET https://gmail.googleapis.com/gmail/v1/users/me/messages?q=subject:"{protocolo}"` (com fallback para busca geral `q="{protocolo}"` caso a busca restrita retorne 0).
   - Para o primeiro resultado encontrado, obtém os metadados com `format=metadata&metadataHeaders=Subject&metadataHeaders=From&metadataHeaders=Date`.
   - Retorna: `id`, `threadId`, `subject`, `from`, `date`, `snippet`, `mailLink`.
2. `getMessageBody($messageId)`:
   - Executa `GET https://gmail.googleapis.com/gmail/v1/users/me/messages/{messageId}?format=full`.
   - Decodifica recursivamente as partes (`text/html` ou `text/plain`) tratando base64 URL-safe (`base64_decode(strtr($data, '-_', '+/'))`).
   - Retorna o conteúdo sanitizado e estruturado para exibição segura em modal.

### 2.3 Endpoints / Ações AJAX
- Implementar as ações AJAX no próprio `livroDeOcorrencias.php` (ou handler dedicado) aproveitando a estrutura unificada de ações:
  - `action: 'buscar_gmail_protocolo'`: recebe `protocolo`, retorna JSON com os dados do e-mail.
  - `action: 'obter_conteudo_email_gmail'`: recebe `message_id`, retorna o corpo do e-mail em HTML.

### 2.4 Interface do Usuário (UI/UX)
- No cabeçalho/detalhes da ocorrência de Monitoramento, inserir um contêiner alvo `<div id="gmail-monitoramento-container" data-protocolo="...">`.
- Via JavaScript:
  - Ao carregar uma ocorrência de Monitoramento, faz a busca silenciosa em background (sem travar nem bloquear o usuário).
  - Se encontrar: exibe um card com animação suave, ícone do Gmail, assunto, data, remetente, botão "Ver no Gmail" e botão "Ler E-mail (Modal)".
  - Ao clicar em "Ler E-mail", abre o Modal Materialize com preloader e renderiza o HTML do e-mail com segurança.

## 3. Próximos Passos
- Gerar o plano de implementação completo.
- Solicitar aprovação do usuário antes de realizar alterações no código.
