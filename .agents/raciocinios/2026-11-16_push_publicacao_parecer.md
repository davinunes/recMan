# Raciocínio de Implementação: Envio de Push na Publicação de Parecer

**Data**: 2026-11-16  
**Contexto**: O usuário solicitou que, ao publicar um parecer no sistema, seja disparada uma notificação push para os conselheiros nos moldes das notificações existentes (ex: *"Davi publicou o parecer 200/2026"*).

---

## 1. Localização do Ponto de Publicação

Analisado o ciclo de vida da confecção e publicação de pareceres no recMan:
1. No frontend ([`meu.js`](file:///d:/dev/github/recMan/meu.js#L430-L465)), ao concluir a emissão e envio por e-mail via `gmail/sendMailParecer.php`, a função recebe o `mailId` e executa a chamada AJAX para `metodo.php?metodo=finalizaParecer`.
2. Em [`metodo.php`](file:///d:/dev/github/recMan/metodo.php#L855-L863), a ação `finalizaParecer` injeta `$_SESSION['user_id']` em `$dados['userId']` e delega para `finalizaParecer($dados)`.
3. Em [`classes/repositorio.php`](file:///d:/dev/github/recMan/classes/repositorio.php#L168-L200), a função `finalizaParecer($dados)`:
   - Atualiza a tabela `conselho.parecer`: `mailId = '$rid', concluido = 1, quemFinalizou = $userId WHERE id = '$id'`.
   - Atualiza a tabela `conselho.recurso`: `fase = 5 WHERE numero = '$id'`.
   - Executa o `COMMIT` da transação.

---

## 2. Estrutura da Notificação Push

- **Gatilho**: Imediatamente após a confirmação do `COMMIT` em `finalizaParecer($dados)`.
- **Destinatários**: Todos os conselheiros inscritos em `push_subscriptions` (comportamento padrão do helper quando `$userIds` é nulo).
- **Dados do Conselheiro**:
  - Obtido via `getUsuariosById($userId)`.
  - Extraído o primeiro nome: `explode(' ', trim($usuario['nome'] ?? 'Conselheiro'))[0]`.
- **Payload**:
  - **Título**: `"Parecer Publicado ($id)"`
  - **Mensagem**: `"$primeiroNome publicou o parecer $id"` (ex: *"Davi publicou o parecer 200/2026"*).
  - **URL**: `"/index.php?pag=recurso&rec=" . urlencode($id)`
  - **Ícone**: Herda o ícone padrão de alta resolução `storage/icons/logo-conselho.png` via `sendPushBackground()`.
- **Mecanismo**: `sendPushBackground($titulo, $mensagem, $url)` (execução assíncrona desacoplada da resposta HTTP do usuário).
