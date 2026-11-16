# Plano de Implementação: Push de Parecer Publicado

**Data**: 2026-11-16  
**Status**: Pronto para Execução  
**Objetivo**: Integrar o disparo de notificação push em segundo plano na publicação/finalização de pareceres oficiais.

---

## 1. Alterações Propostas

### 1.1 `classes/repositorio.php`
- Localizar a função `finalizaParecer($dados)`.
- Imediatamente após o `COMMIT` bem-sucedido:
  - Recuperar `$userId` de `$dados['userId'] ?? ($_SESSION['user_id'] ?? null)`.
  - Obter os dados do usuário via `getUsuariosById($userId)`.
  - Extrair o primeiro nome do conselheiro.
  - Montar o título: `"Parecer Publicado ($id)"`.
  - Montar o corpo: `"$primeiroNome publicou o parecer $id"`.
  - Montar a URL de redirecionamento: `"/index.php?pag=recurso&rec=" . urlencode($id)`.
  - Chamar `sendPushBackground($titulo, $mensagem, $url)`.
  - Envolver em bloco `try/catch` para garantir que qualquer eventualidade no push não comprometa o retorno de sucesso da finalização.

---

## 2. Critérios de Sucesso
- A publicação de parecer executa o envio do push assíncrono.
- O clique no push leva diretamente para o recurso em questão.
- O formato do texto segue rigorosamente a convenção do sistema.
