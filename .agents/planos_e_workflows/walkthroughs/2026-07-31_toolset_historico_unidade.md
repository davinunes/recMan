# Walkthrough - Toolset Operacional por Unidade (`index.php?pag=historico`)

## Resumo das Alterações Realizadas

Transformamos a tela de Histórico (`index.php?pag=historico`) em um **Toolset Operacional completo baseado na Unidade selecionada**, agregando 7 aceleradores contextuais da API v8 Vida de Síndico e do banco local.

### 1. Atualizações no Rastreio de Encomendas
- **Código de Rastreio / Identificador**: Aplicado o mesmo método assíncrono de busca por UUID utilizado na tela de detalhe de recurso (`detalheRecurso.php`). Cada linha da tabela de encomendas busca em segundo plano via `metodo.php?metodo=obterDetalhesEntrega&uuid=...` para obter e formatar o `identificador` (`<i class="material-icons tiny">qr_code</i> ...`) e a foto ampliada.

### 2. Extração de Multas em Boletos com Modal de Confirmação
- **Modal de Extração e Confirmação (`#modalSugestoesMultaBoleto`)**: Ao clicar em *Extrair Multas* no acelerador de boletos, é aberto o modal de análise.
- **Análise Dupla (Backend + DOMParser Client-Side)**: Realiza a leitura e extração de ocorrências de penalidade disciplinar/regimento interno na fatura Superlógica tanto pela API backend quanto pelo parser do DOM.
- **Confirmação de Lançamento**: Exibe os cards das multas detectadas com o valor extrato e botão **[ Confirmar Lançamento ]**. Ao confirmar, chama `upsertMultaCobrada`, altera o badge para `✓ Lançamento Confirmado` e recarrega os dados da unidade.

### 3. Seletor Temporal por Mês
- Adicionado seletor de mês (`input type="month"`) com valor padrão no mês corrente (`YYYY-MM`).
- Adicionados botões de navegação rápida: `<` (Mês Anterior), `>` (Próximo Mês) e `Hoje` (Mês Atual).
- Ao mudar de mês ou clicar nos botões, os aceleradores do período são recarregados dinamicamente sem atualizar a página.

### 4. Dashboard Estatística KPI da Unidade
- Exibe cards KPI responsivos no topo:
  - 🚨 **Notificações**: Contagem total do histórico (com quebra de Multas vs Advertências).
  - ⚖️ **Recursos**: Total em aberto, Mantidos, Revogados e Convertidos.
  - 📦 **Encomendas**: Entregas do mês e quantidade pendente.
  - 🎟️ **Autorizações de Acesso**: Visitantes e prestadores autorizados no mês.
  - 📅 **Reservas**: Agendamentos de áreas comuns no mês.
  - 💬 **Ocorrências**: Chamados de própria autoria vs chamados citados por tag.
  - 💳 **Boletos**: Total de boletos no ano e quantidade em aberto.
- Clique nos cards KPI rola a página suavemente até o acelerador correspondente e o expande.

### 5. Painel Accordion de 7 Aceleradores Colapsados
1. **Notificações & Recursos**:
   - Exibe o **histórico completo** da unidade (sem restrição de mês), inicializado colapsado.
   - Ações contextuais:
     - 👁️ **Acessar Recurso**: Link direto para `index.php?pag=recurso&rec=X/YY`.
     - 📅 **Data de Ciência / Retirada**: Modal para cadastrar ou atualizar o campo `dia_retirada`.
     - 💳 **Confirmar Cobrança de Multa**: Modal para cadastrar ou vincular o lançamento financeiro da notificação.
2. **Encomendas & Correspondências**:
   - Exibe foto do pacote, código de rastreio, descrição, destinatário, data/hora e badge de status (Entregue / Pendente).
   - Busca em segundo plano por UUID para exibição imediata do código de rastreio.
3. **Autorizações de Acesso & Convites**:
   - Exibe foto do visitante/prestador, nome, documento, morador responsável, vigência (início a fim) e status.
4. **Reservas de Área Comum**:
   - Exibe espaço/recurso (Salão de Festas, Churrasqueira, etc.), data da reserva, horário, status e taxa.
5. **Ocorrências Registradas pela Unidade (Própria Autoria)**:
   - Exibe protocolo, assunto, mensagem e botão com link direto para o chat de ocorrência (`index.php?pag=livroDeOcorrencias&prot=...`).
6. **Ocorrências Envolvendo a Unidade (Tags, Citações e Ré)**:
   - Exibe protocolo, autor original, vínculo (Ré, Citada), assunto e botão para o chat.
7. **Boletos & Lançamentos Financeiros**:
   - Exibe vencimento, referência, valor total, status de pagamento (Liquidado / Em Aberto), link da 2ª via Superlógica e extrator rápido de multas com modal de confirmação.
