# Raciocínio Complementar: Row-Cards Esticados & Clique Triplo para Ocultação

- **Data**: 2026-08-19
- **Solicitação**:
  1. Cards em formato esticado/horizontal em telas grandes (imitando linhas de tabelas modernas).
  2. Preservação/adaptação do clique triplo para ocultar cards com base no parecer/resultado e recalcular o total.

---

## 1. Row-Cards Esticados (Desktop) vs Cards Empilhados (Mobile)

- **Desktop (`min-width: 993px`)**:
  - Cada item ocupa a largura total (`col s12`).
  - Layout flex horizontal (`display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 16px;`).
  - Colunas proporcionais: Identificação (tipo, nº/ano, unidade, status), Financeiro (cobrança, valor, vencimento, pagamento), Datas (ocorrido, envio, ciência), Jurídico (recurso, parecer), Ações (Boletos VDS, Lançar/Editar Cobrança).
  - Visual limpo, semelhante a uma tabela moderna e espaçosa com cards encapsulados.

- **Mobile (`max-width: 992px`)**:
  - Adaptação automática via `@media` para disposição vertical (empilhada), garantindo toque fácil, botões de largura total e leitura confortável sem scroll horizontal.

---

## 2. Clique Triplo Inteligente

- Disparador: `.parecer, .click-triplo-target`.
- Ao receber 3 cliques dentro de 1 segundo:
  - Extrai o valor do parecer (ex: `DEFERIDO`, `INDEFERIDO`, etc.).
  - Filtra todos os `.card-cobranca-wrapper` visíveis correspondentes.
  - Executa animação de `fadeOut(300)` e remove os elementos.
  - Dispara `window.recalcularResumoCobranca()`, atualizando os totais de itens cobrados, pendentes e valor financeiro no topo da tela.
  - Exibe Toast notificando a quantidade de registros ocultados.
