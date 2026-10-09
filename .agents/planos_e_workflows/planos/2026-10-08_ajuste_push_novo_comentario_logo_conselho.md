# Plano de Implementação: Notificação Push com Número/Ano, Logo do Conselho e Prévia Ampliada

**Data**: 2026-10-08  
**Objetivo**: Ajustar as notificações push de novos comentários no recMan para exibir o recurso no formato `numero/ano`, trocar a imagem do push pela marca oficial do Conselho e ampliar a quantidade de caracteres da prévia da mensagem.

---

## 📋 Escopo de Alterações

### 1. `classes/repositorio.php` (`upsertComentario`)
- Recuperar o registro do recurso pelo ID (`getRecursoById($id_recurso)`).
- Extrair o formato oficial `numero/ano` (`$numeroRecurso = $recurso['numero'] ?? $id_recurso`).
- Atualizar o título e o corpo da mensagem:
  - Título: `Novo Comentário ($numeroRecurso)`
  - Corpo: `$nome_usuario comentou no recurso $numeroRecurso:\n$resumo_comentario`
  - URL de destino: `/index.php?pag=recurso&rec=` com `$numeroRecurso`.
- Ampliar a prévia do comentário de 100 caracteres para **280 caracteres** com `mb_substr` (UTF-8 seguro).

### 2. `portal/api.php` (`action == 'add_comment'`)
- Ao receber comentário do condômino pelo portal, adicionar a prévia do texto enviado (até 280 caracteres) em vez da mensagem genérica fixa.

### 3. `classes/push_helper.php` e `sw.js`
- Atualizar o ícone padrão (`$icon`) em `classes/push_helper.php` de `conselho-toon.webp` para `https://mini.davinunes.eti.br/storage/icons/logo-conselho.png`.
- Adicionar suporte a parâmetro opcional `$icon` em `sendPushBackground` e `classes/api_push_cli.php`.
- Atualizar o fallback de ícone em `sw.js` para o logo oficial.

---

## 🔍 Validação
- Verificar compatibilidade com chamadas existentes de `upsertComentario`.
- Validar se o arquivo `storage/icons/logo-conselho.png` existe e está íntegro.
- Revisar a formatação da URL e payload do push.
