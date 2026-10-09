# Raciocínio: Notificação Push - Formato Número/Ano, Novo Logo do Conselho e Ampliação de Prévia

**Data**: 2026-10-08  
**Contexto**: O usuário solicitou 3 melhorias pontuais no envio de notificações push de "Novo Comentário":
1. Exibir o recurso no formato `numero/ano` em vez do ID interno do banco de dados (`$id_recurso`).
2. Substituir o ícone padrão antigo ("figurinha" `storage/icons/conselho-toon.webp`) pelo novo logotipo oficial do Conselho que já está sendo utilizado no sistema e nos favicons (`favicon/logo-conselho/256-256.png` / `storage/icons/logo-conselho.png`).
3. Ampliar a quantidade de caracteres na prévia da mensagem do push (que antes era truncada em apenas 100 caracteres).

---

## 1. Diagnóstico do Código Atual

### 1.1 Exibição de ID vs Número/Ano
- No arquivo `classes/repositorio.php`, função `upsertComentario($dados)`:
  ```php
  $resumo_comentario = substr($dados['messageText'], 0, 100) . (strlen($dados['messageText']) > 100 ? "..." : "");

  $titulo = "Novo Comentário";
  $mensagem = "$nome_usuario comentou no recurso $id_recurso:\n$resumo_comentario";
  $url = "/index.php?pag=recurso&rec=" . urlencode($id_recurso);

  sendPushBackground($titulo, $mensagem, $url);
  ```
- O valor `$id_recurso` é o ID autoincrement numérico da tabela `recurso` (ex: `54`).
- A coluna `recurso.numero` guarda o identificador oficial do condomínio no formato `numero/ano` (ex: `045/2026` ou `54/2026`).
- Além de o usuário ver o ID em vez de número/ano, o link `$url = "/index.php?pag=recurso&rec=" . urlencode($id_recurso)` passava o ID numérico, enquanto `detalheRecurso.php` busca por `where r.numero = '{$_GET['rec']}'`.
- **Solução**: Buscar o recurso via `getRecursoById($id_recurso)`. Se existir `$recurso['numero']`, utilizar `$recurso['numero']` tanto no texto quanto no link URL e opcionalmente no título (`Novo Comentário ($numeroFormatado)`).

### 1.2 Ícone do Push ("Figurinha")
- Em `classes/push_helper.php` linha 44:
  ```php
  if (!$icon) {
      $icon = 'https://mini.davinunes.eti.br/storage/icons/conselho-toon.webp';
  }
  ```
- Este arquivo apontava para a figurinha cartoon `conselho-toon.webp`.
- O novo logo do conselho foi adotado recentemente (2026-10-07) e está presente em `favicon/logo-conselho/256-256.png` e `android-chrome-192x192.png`.
- Criamos a cópia `storage/icons/logo-conselho.png` garantindo que o caminho em `storage/icons/` continue válido e aponte para o logotipo de alta fidelidade do Conselho.
- Atualizaremos o default em `push_helper.php` para `https://mini.davinunes.eti.br/storage/icons/logo-conselho.png`.
- No `sw.js`, atualizaremos o fallback de ícone para o mesmo logotipo.

### 1.3 Ampliação de Caracteres na Prévia
- Antes: `substr($dados['messageText'], 0, 100)`:
  - Curto demais (apenas 100 caracteres).
  - Pode quebrar caracteres multibyte (UTF-8) com `substr`.
- A Web Push API suporta até 4KB no payload total.
- Ampliaremos o limite para **280 caracteres** utilizando `mb_substr(trim(...), 0, 280, 'UTF-8')`.
- 280 caracteres permite pré-visualizar praticamente 2 a 3 parágrafos curtos na gaveta de notificações do Android/iOS/Windows sem estourar nenhum limite de push ou layout do sistema operacional.
- Também melhoramos a mensagem de novo comentário vinda do Portal do Condômino (`portal/api.php`), incluindo o resumo do que o condômino escreveu.
