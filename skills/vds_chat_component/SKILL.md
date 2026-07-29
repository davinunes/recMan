---
name: vds_chat_component
description: Diretrizes de UI/UX e componentes de layout para renderização de ocorrências em formato de Chat estilo WhatsApp, notas internas com publicação em 2 fatores e badges visuais por categoria de chamado (ocoTipo).
---

# Skill: Layout de Chat Estilo WhatsApp & Badges de Categoria

Esta skill define os padrões de design, estrutura HTML/CSS e lógica JS para exibir o histórico de mensagens de ocorrências vindas da API v8 Vida de Síndico.

## 1. Mapeamento Visual de Categorias de Ocorrência (`ocoTipo`)

Cada ocorrência possui uma categoria (`ocoTipo`). As categorias conhecidas devem ser renderizadas com badges coloridos no feed e no cabeçalho do Chat:

| ocoTipo | Nome da Categoria | Estilo do Badge / Cor |
|---|---|---|
| **115** | Fale com o Conselho | 🟣 Purple (`#6f42c1`) |
| **247** | Monitoramento | 🟠 Orange (`#fd7e14`) |
| **114** | Livro de ocorrência | 🔵 Blue (`#0d6efd`) |
| **86** | Fale com o Síndico | 🔴 Red / Crimson (`#dc3545`) |
| **109** | Fale com o Síndico de Bloco | 🔴 Dark Red (`#b02a37`) |
| **102** | Fale com a Administração | 🟢 Teal / Green (`#20c997`) |
| **145** | Fale com a Mensageria | 🟡 Yellow / Amber (`#ffc107`) |
| **87** | Fale com a Portaria | 🟤 Brown (`#795548`) |
| **126** | Fale com a Supervisão | 🔷 Cyan (`#0dcaf0`) |
| **172** | Suporte ao Controle de Acesso | ⚙️ Dark Gray (`#495057`) |
| *Outros* | Categoria Genérica | ⚪ Neutral Gray (`#6c757d`) |

*Nota: Se um novo `ocoTipo` for retornado pela VDS, o sistema o anotará localmente em `vds_uuid_mapping` com `entidade_tipo = 'categoria_ocorrencia'` e renderizará o badge cinza neutro padrão.*

## 2. Regras Visuais e Alinhamento de Balões no Chat

- **Esquerda (Autor / Solicitante / Morador):**
  - **Fundo do Balão:** Verde claro suave (`#E7FFDB`) ou cinza neutro.
  - **Avatar:** URL da foto do morador (`.../MORADOR/p-{ID}.jpg`).
  - **Cabeçalho:** Nome do Morador + Bloco / Unidade.

- **Centro / Notas Internas do Conselho (1º Fator - Padrão):**
  - **Fundo do Balão:** Amarelo pastel ou Azul claro destacado com aviso "Nota Interna do Conselho".
  - **Visibilidade:** Salva **apenas no banco do Conselho**. Morador não visualiza.
  - **Ação:** Botão **"Publicar no Sistema Remoto (VDS)"** (2º Fator).

- **Direita / Mensagens Publicadas no Remoto:**
  - **Fundo do Balão:** Azul escuro/Verde WhatsApp (`#DCF8C6`).
  - **Avatar:** URL da foto do conselheiro/pessoa (`.../PESSOA/f-{ID}.jpg`).
  - **Status:** Ícone de confirmação de publicação remota.
