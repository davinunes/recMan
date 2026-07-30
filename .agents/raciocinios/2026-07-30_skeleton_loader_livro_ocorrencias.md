# Raciocínio & Diagnóstico: Skeleton Screen Loader para Livro de Ocorrências

**Data:** 30/07/2026  
**Projeto:** recMan  
**Componente:** `livroDeOcorrencias.php` / UI Performance Feedback

---

## 1. Problema Identificado
- A tela `index.php?pag=livroDeOcorrencias` apresenta tempo de carregamento perceptível ao abrir ou navegar entre ocorrências e visões (Prático x Analítico).
- A causa raiz é a chamada síncrona às APIs remotas do Vida de Síndico (`apiv8.vds.app.br`) para consultar não lidos ou detalhes de ocorrências.
- O navegador exibia uma tela congelada/branca sem feedback visual de progresso durante a requisição.

---

## 2. Solução Implementada

### A. Efeito Skeleton Screen Shimmer (Esqueleto Pulsante)
- Desenvolvida a estrutura `#vds-skeleton-chat-container` em HTML + CSS com animação `@keyframes vds-shimmer-pulse`.
- Simula a estrutura exata da interface final do chat estilo WhatsApp:
  - Cabeçalho shimmer (avatar + título + pílulas de ações).
  - Três balões de conversa shimmer (mensagem de morador, resposta do conselho e nota interna).
  - Caixa de envio de mensagem footer shimmer.

### B. Barra de Progresso Superior (Vercel / GitHub Style)
- Adicionado componente `#vds-top-loader` fixado no topo da tela (`top: 0`).
- Ao clicar em uma ocorrência na lista ou em filtros, a barra avança instantaneamente de 0% a 85% com brilho azul/púrpura.

### C. Acionamento Instantâneo no Cliente
- Criado o handler `triggerVdsSkeleton()` via jQuery.
- Ao clicar em `.item-oco`, o contêiner real `#chat-real-content` é ocultado imediatamente e o `#vds-skeleton-chat-container` é exibido em 0ms, eliminando a percepção de travamento.

---

## 3. Conclusão
A experiência de uso foi transformada de uma espera passiva com tela congelada para uma interface viva e responsiva estilo web app moderno (Next.js / WhatsApp Web).
