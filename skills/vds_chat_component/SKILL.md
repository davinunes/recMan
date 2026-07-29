---
name: vds_chat_component
description: Diretrizes de UI/UX e componentes de layout para renderização de ocorrências em formato de Chat estilo WhatsApp (mensagens do autor à esquerda, respostas da administração à direita, avatares, fotos e anexos).
---

# Skill: Layout de Chat Estilo WhatsApp para Ocorrências

Esta skill define os padrões de design, estrutura HTML/CSS e lógica JS para exibir o histórico de mensagens de ocorrências vindas da API v8 Vida de Síndico.

## 1. Regras Visuais e Alinhamento

- **Esquerda (Autor / Solicitante / Morador):**
  - **Fundo do Balão:** Verde claro suave (`#E7FFDB` no light mode / `#005C4B` no dark mode) ou cinza neutro.
  - **Avatar:** URL da foto do morador (`https://app.vidadesindico.com.br/app/dados/cond/{COND_ID}/foto/MORADOR/p-{ID}.jpg`).
  - **Cabeçalho:** Nome do Morador + Bloco / Unidade em destaque.
  - **Data/Hora:** Formatado em `DD/MM/AAAA HH:mm` no canto inferior direito do balão.

- **Direita (Conselho / Administração):**
  - **Fundo do Balão:** Azul/Verde escuro destacado (`#DCF8C6` ou `#202C33`).
  - **Avatar:** URL da foto da pessoa (`https://app.vidadesindico.com.br/app/dados/cond/{COND_ID}/foto/PESSOA/f-{ID}.jpg`).
  - **Cabeçalho:** Nome do Conselheiro / Administrador + Cargo (ex: "Conselheiro", "Síndico").
  - **Identificação da Autoria:** Garante transparência sobre qual conselheiro enviou a resposta.

## 2. Anexos e Mídias

- **Imagens:** Renderizadas com thumbnail e suporte a zoom/modal ao clicar.
- **Documentos/PDFs:** Ícone de arquivo + nome do anexo + botão de download.
- **Upload na Resposta:** Campo de entrada com ícone de clipe de papel que dispara o envio para `POST /upload` na API v8 antes do envio da mensagem.
