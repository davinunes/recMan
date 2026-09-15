# Plano de Implementação: Carregamento em Segundo Plano da VDS e Otimização Mobile do Histórico

**Data**: 14/09/2026  
**Tela de Impacto**: `index.php?pag=historico` (Toolset Operacional por Unidade)  
**Objetivos**:
1. Eliminar o bloqueio de tela causado pelas consultas lentas à API VDS, entregando imediatamente os dados locais do banco (Notificações, Multas, Recursos e Vagas) e carregando os dados da VDS em segundo plano de forma assíncrona.
2. Reformular a organização visual e responsiva do card de filtros no mobile, corrigindo o visual "embolado" e proporcionando uma experiência ergonômica em telas curtas.

---

## 1. Modificações no Backend (`metodo.php`)

- Atualizar o manipulador `case "toolsetUnidade":` (e suportar aliases como `toolsetUnidadeLocal` e `toolsetUnidadeVds`):
  - Iniciar a sessão e invocar `session_write_close()` imediatamente após capturar o `$_SESSION['user_id']`, liberando a trava de sessão do PHP.
  - Implementar o parâmetro `$modo`:
    - `modo=local`: executa `getNotificacoes($unidade, $torre)` e `getEstacionamento($torre, $unidade)`, calcula estatísticas locais (`totalNotificacoes`, `totalMultas`, `totalAdvertencias`, `totalRecursos`, `recursosMantidos`, etc., e `totalVagas`) e responde em JSON imediatamente (< 30ms).
    - `modo=vds`: executa as consultas remotas na API VDS (entregas, autorizações, reservas, chamados/ocorrências, boletos, moradores, inadimplência, veículos, visitantes, liberações de portaria, acessos) e devolve o payload estruturado com seus respectivos contadores.
    - `modo=todos`: executa ambos em sequência (compatibilidade legada).

---

## 2. Modificações no Frontend (`meu.js`)

- Refatorar a função `executarBuscaHistorico(unidade, bloco, mesAno)`:
  - Adicionar controle de cancelamento de requisições pendentes (`abort()`).
  - Disparar requisição dos dados locais (`metodo=toolsetUnidade&modo=local`).
  - Ao receber o retorno local:
    - Ocultar o loader global de tela inteira (`#toolsetLoader`) e o `#emptyState`.
    - Exibir o container de toolset (`#toolsetContainer`) e os cards de KPI (`#unitBrief`).
    - Renderizar imediatamente o histórico de Notificações (`renderToolsetNotificacoes`) na seção 1 (que já vem aberta por padrão).
    - Renderizar as vagas locais de garagem na seção de Veículos (`renderToolsetVeiculos([], res.vagas)`).
    - Injetar skeletons/spinners de carregamento assíncrono em todas as seções remotas da VDS e colocar ícones de sincronização nos respectivos badges.
    - Renderizar os KPIs locais imediatos no `#unitBrief` e placeholders nos KPIs que dependem da VDS.
  - Disparar em segundo plano a requisição `metodo=toolsetUnidade&modo=vds`.
  - Ao receber o retorno da VDS:
    - Mesclar dados e renderizar Moradores, Veículos (mantendo vagas locais), Visitantes, Encomendas, Autorizações, Acessos, Reservas, Ocorrências e Boletos.
    - Atualizar a dashboard de KPIs com os totais completos e o alerta de inadimplência (se aplicável).
    - Em caso de falha de conexão com a VDS: exibir banner amigável com opção de tentar novamente nas seções da VDS, mantendo os dados locais 100% disponíveis.
- Otimização para troca de Mês:
  - Ao alterar o seletor de mês com a unidade já carregada, disparar apenas o recarregamento dos dados VDS dependentes do mês, sem recarregar ou esvaziar os dados de notificações locais.

---

## 3. Modificações no Layout e Responsividade Mobile (`palco/historico.php`)

- **Remoção do Bug de CSS**: Retirar a classe `valign-wrapper` da linha do card de filtros.
- **Reorganização dos Componentes**:
  - Em telas grandes (Desktop): alinhamento horizontal limpo e harmônico com base alinhada (`align-items: flex-end`).
  - Em telas pequenas (Mobile < 768px):
    - Unidade e Bloco posicionados lado a lado (2 colunas de 50% de largura), poupando altura de tela.
    - Campo "OU Vaga" logo abaixo, com indicação visual clara de alternativa.
    - Controles de Mês de Abrangência ocupando a largura total, com botões amplos e touch-friendly (`<`, `input[type=month]`, `>`, `Hoje`).
    - Botão "CARREGAR" em bloco com largura total (100%), 42px de altura e feedback tátil ao toque.
  - Ajuste do cabeçalho da página para que o botão "Busca Rápida VDS" não quebre de forma estranha em dispositivos móveis.
- Adicionar estilos para os estados de carregamento em segundo plano (skeletons e badges giratórios).

---

## 4. Plano de Verificação

- **Verificação de Performance / Resposta Imediata**:
  - Validar se a chamada local responde com a estrutura esperada de notificações e vagas.
  - Confirmar que a chamada VDS em segundo plano popula as seções subsequentes sem congelar a UI.
- **Verificação de Layout Mobile**:
  - Inspecionar a estrutura do HTML e as regras de media query para `max-width: 768px` e `max-width: 600px`.
  - Garantir que campos de formulário, labels e botões do seletor de mês não se sobreponham.
