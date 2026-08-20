# Plano de Implementação: Migração da Lista com Cobrança para Cards Responsivos

- **Data**: 2026-08-19
- **Status**: Proposta em Análise
- **Arquivos Envolvidos**:
  - `palco/planSoluc.php` (View principal)
  - `meu.js` (Manipuladores de eventos JS)
  - `meu.css` (Estilos específicos dos cards e responsividade)

---

## 1. Objetivos

1. Substituir a tabela densa de 17 colunas por uma interface moderna em **Cards Responsivos**.
2. Otimizar a experiência em **dispositivos móveis (smartphones)**, eliminando barras de rolagem horizontal desnecessárias.
3. Substituir interações baseadas em `dblclick` por botões de toque dedicados e com feedback tátil.
4. Manter 100% da retrocompatibilidade com os modais de **Inspeção de Boletos VDS** e **Lançamento de Cobrança / Multa**.
5. Oferecer um campo de busca rápida em tempo real (Instant Search) nos cards.

---

## 2. Mockup Estrutural do Card (Visual Proposal)

```
┌─────────────────────────────────────────────────────────────┐
│ [MULTA] #102/2026                 [ENVIADA]                 │
│ 🏢 Bloco B - Apt 304                                        │
├─────────────────────────────────────────────────────────────┤
│ 💰 Status de Cobrança:                                      │
│    [ ✓ Cobrado ] R$ 250,00                                  │
│    Vencimento: 10/05/2026 | Pagamento: 12/05/2026           │
│                                                             │
│ 📅 Prazos e Datas:                                          │
│    • Ocorrido: 15/04/2026   • Envio: 20/04/2026             │
│    • Ciência: 22/04/2026 [Dentro do prazo]                  │
│                                                             │
│ ⚖️ Recurso / Parecer:                                       │
│    • Recurso: Sim           • Parecer: INDEFERIDO           │
├─────────────────────────────────────────────────────────────┤
│ [ 🧾 Inspecionar Boletos ]   [ 💲 Editar Cobrança ]        │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Detalhamento Técnico das Alterações

### A. Modificações em `palco/planSoluc.php`
- Manter o painel de filtros superiores com colapso/expansão no mobile.
- Adicionar contador de resultados e input de busca rápida em tempo real.
- Renderizar a listagem dentro de um container grid (`row` com `col s12 m6 l4 xl3`).
- Cada card conterá atributos `data-id`, `data-numero`, `data-ano`, `data-unidade`, `data-bloco`, `data-valor`, `data-vencimento`, `data-pagamento`, `data-multa`.
- Classes visuais com cores temáticas:
  - Borda ou cabeçalho dourado/verde para itens com multa já cobrada.
  - Badges coloridos para status e tipos de penalidade.

### B. Modificações em `meu.js`
- Desacoplar os eventos do `modal-multa` da posição de `td:eq(...)` da tabela.
- Criar evento `$(document).on('click', '.btn-editar-cobranca-card', ...)` lendo diretamente os atributos `data-*` do card.
- Adaptar o evento de remoção visual ou atualização do card após salvar a multa com sucesso.
- Implementar busca instantânea por digitação nos cards:
  ```javascript
  $('#busca-rapida-cards').on('keyup', function() {
      var termo = $(this).val().toLowerCase();
      $('.card-cobranca-item').each(function() {
          var texto = $(this).text().toLowerCase();
          $(this).toggle(texto.indexOf(termo) > -1);
      });
  });
  ```

### C. Estilos em `meu.css`
- Estilização de cards com cantos arredondados (`border-radius: 12px`), sombras suaves (`box-shadow`), tipografia harmoniosa e estados hover.
- Otimização para telas pequenas com padding adequado e botões com largura total ou agrupados.

---

## 4. Plano de Verificação

1. **Testes de Renderização Mobile**:
   - Validar viewport < 480px (telas de celulares comuns).
   - Validar viewport 768px (tablets).
   - Validar viewport > 1200px (desktop).
2. **Testes Funcionais**:
   - Acionamento do botão `Inspecionar Boletos` e carregamento de faturas via API VDS.
   - Abertura do modal de cobrança com dados pré-preenchidos.
   - Salvamento de cobrança via AJAX e atualização visual em tela.
   - Filtros do formulário PHP e busca rápida instantânea.
