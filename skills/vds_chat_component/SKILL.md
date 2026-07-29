---
name: vds_chat_component
description: Diretrizes de UI/UX e componentes de layout para renderização de ocorrências em formato de Chat estilo WhatsApp, com suporte às Visões Prática (VDS Não Lidos) e Analítica (Banco Local), marcação de leitura remota (PUT /ocorrencia/leitura), botão toggle de leitura e tags inteligentes.
---

# Skill: Layout de Chat Estilo WhatsApp & Componente de Ocorrências

Esta skill define os padrões de design, estrutura HTML/CSS e lógica JS/PHP para exibir o histórico de mensagens de ocorrências vindas da API v8 Vida de Síndico.

## 1. Visões do Sistema (Prática vs Analítica)

- **Visão Prática (`visao=pratico` - PADRÃO):**
  - Consulta chamados não lidos diretamente na VDS (`GET /ocorrencia?Lida=0`).
  - Cada chamado exibido é persistido/atualizado automaticamente no banco local.
  - Ao marcar como lido, o chamado é atualizado via `PUT /ocorrencia/leitura/{uuid}` e **removido dinamicamente da lista de não lidos**, proporcionando uma navegação ultra-fluida.

- **Visão Analítica (`visao=analitico`):**
  - Consulta o banco de dados local `ocorrencias`.
  - Exibe por padrão chamados não resolvidos (`resolvido IS NULL OR resolvido = 0`).

---

## 2. Ações Rápidas no Cabeçalho do Chat

1. **Responsabilidade Dropdown:** Permite atribuir rapidamente o chamado a `Conselho`, `Síndico`, `Subsíndico`, `Administradora`, `Operacional` ou `Jurídico`.
2. **Botão Toggle Resolvido (Local):** Alterna o status local entre Resolvido e Aberto (`resolvido = 1` ou `0`).
3. **Botão Toggle Leitura (VDS Remoto):**
   - Se **Não Lido**: Exibe `Marcar Lido (VDS)` (botão `teal` / ícone `mark_email_read`).
   - Se **Lido**: Exibe `Marcar NÃO Lido (VDS)` (botão `orange` / ícone `mark_email_unread`).

---

## 3. Tagging Inteligente (Input Único)

Substitui seletores complexos por um campo de texto único `<input name="tag_input" />`:
- **Padrão Unidade** (ex: `B1108`, `Bl. A 102`, `1108`): auto-detecta e gera badge `🏢 Bloco B - Apt 1108`.
- **Padrão Notificação / Recurso** (ex: `123/2026`, `45/26`): auto-detecta e gera badge `📋 Notificação 123/2026`.
- **Tag Genérica** (ex: `Vazamento`): gera badge `🏷️ Vazamento`.

---

## 4. Regras Visuais e Resposta em 2 Fatores

- **Notas Internas (1º Fator):**
  - Salva localmente em `ocorrencia_notas_internas` com `enviado_remoto = 0`.
  - Exibida no balão pastel com o botão **"Publicar no Remoto (VDS)"**.

- **Publicação Remota (2º Fator):**
  - Dispara `POST /ocorrencia/comentario` na VDS.
  - Grava o ID do comentário VDS em `vds_evento_uuid`.
  - A nota sai do bloco interno e é exibida no feed remoto com a anotação visual `Publicado por [Conselheiro] (Conselho)`.
