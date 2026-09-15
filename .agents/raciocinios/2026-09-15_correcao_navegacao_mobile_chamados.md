# Diagnóstico & Correção: Navegação Mobile no Livro de Ocorrências

- **Data**: 2026-09-15
- **Arquivo**: `livroDeOcorrencias.php`
- **Sintoma Relatado**: No celular, ao tocar em um chamado da lista, ele não abria. Ao puxar para baixo (reload da página), a tela recarregava com o chamado aberto. E ao tocar na seta de voltar, não voltava para a lista de ocorrências.

---

## 1. Causa Raiz Identificada

No arquivo `livroDeOcorrencias.php`, o bloco de CSS para telas `<= 992px` continha declarações estáticas com `!important` geradas diretamente pelo PHP com base no carregamento inicial da página:

```css
@media (max-width: 992px) {
    .sidebar-feed {
        display: <?= ($selId || $detalheSel) ? 'none' : 'block' ?> !important;
        width: 100% !important;
    }
    .chat-container {
        display: <?= ($selId || $detalheSel) ? 'flex' : 'none' ?> !important;
        width: 100% !important;
    }
}
```

### O que ocorria no fluxo:
1. Ao acessar no celular sem `id` na URL, `$selId` era `null`.
2. A folha de estilo gerava `.chat-container { display: none !important; }` e `.sidebar-feed { display: block !important; }`.
3. Quando o usuário tocava num chamado na lista:
   - A função `selecionarOcorrencia` executava normalmente via AJAX e atualizava a URL com `pushState`.
   - O JavaScript tentava exibir o chat com `$('.chat-container').css('display', 'flex')`.
   - **Porém**, na especificação CSS (W3C), regras em stylesheet com `!important` sobrepõem estilos inline comuns sem `!important`. Logo, o navegador mantinha o chat oculto e a lista visível.
4. Quando o usuário dava refresh (puxar para baixo):
   - A página recarregava com o `id` da URL. Agora `$selId` existia no PHP, gerando `.chat-container { display: flex !important; }` e `.sidebar-feed { display: none !important; }`.
5. Agora com o chat aberto, ao tocar na seta voltar (`voltarParaListaMobile`):
   - O JavaScript tentava `$('.chat-container').hide()`, mas o CSS com `!important` mantinha o chat visível e impedia a lista de reaparecer.

---

## 2. Solução Implementada

1. **Classes de Estado Dinâmico**:
   - Criada a classe `.vds-main-row` com estados `.show-list` e `.show-chat`.
   - No CSS:
     ```css
     @media (max-width: 992px) {
         .vds-main-row.show-list .sidebar-feed { display: block !important; }
         .vds-main-row.show-list .chat-container { display: none !important; }
         .vds-main-row.show-chat .sidebar-feed { display: none !important; }
         .vds-main-row.show-chat .chat-container { display: flex !important; }
     }
     ```
2. **Alternância Instantânea no JavaScript**:
   - Em `selecionarOcorrencia`: alterna para `.show-chat` e faz `window.scrollTo(0, 0)`.
   - Em `voltarParaListaMobile`: alterna para `.show-list`, faz `window.scrollTo(0, 0)` e limpa o parâmetro `id` da URL via `pushState` limpo.
3. **Suporte ao Botão Voltar Físico / Gestos Mobile**:
   - Tratamento no evento `popstate`: ao voltar pelo botão físico do celular para a listagem (sem `id`), o estado volta para `.show-list` automaticamente.
4. **Ergonomia Touch**:
   - Botão da seta de voltar ampliado para 36px com borda suave, facilitando o toque do polegar em smartphones.
