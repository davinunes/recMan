# Raciocínio: Análise de Viabilidade e Proposta de Layout em Cards para Lista com Cobrança

- **Data**: 2026-08-19
- **Solicitante**: Usuário
- **Objetivo**: Avaliar viabilidade de migração da tela "Lista Com Cobrança" (`palco/planSoluc.php`) de tabela HTML/DataTable para layout em Cards Responsivos (foco em celular), e propor design/arquitetura.

---

## 1. Mapeamento da Situação Atual

1. **Localização**:
   - Menu: "Lista Com Cobrança" em `palco/usuarioLogado.php` (`index.php?pag=planilhaSolucoes`).
   - View: `palco/planSoluc.php`.
   - Script JS: `meu.js`.
   - Camada de Dados: `classes/repositorio.php` (`getNotificacoesByFilters()` e `getAllNotificacoes()`).

2. **Problemas Atuais no Mobile**:
   - A tabela possui 17 colunas (`Ações`, `Número`, `Ano`, `Unidade`, `Bloco`, `Data Email`, `Data Envio`, `Data Ocorrido`, `Data Ciência`, `Notificação`, `Status`, `Multa Cobrada`, `Valor`, `Data Venc.`, `Data Pag.`, `Recorreu?`, `Parecer`).
   - Requer scroll horizontal excessivo em telas menores que 1000px.
   - O acionamento de edição de multa depende de evento `dblclick` na célula da tabela, o que é incompatível ou muito ruim em dispositivos móveis (touch screens).
   - O `DataTable` atual apenas oferece busca estática e desativa paginação nativa em alguns casos.

3. **Interações Atuais a Preservar**:
   - Filtros de Ano, Status, Multa Cobrada, Tipo e Bloco.
   - Modal de inspeção de boletos VDS (`#modal-inspecionar-boletos`).
   - Modal de lançamento/edição de multa cobrada (`#modal-multa`).
   - Edição de Data de Retirada/Ciência (`metodo.php?metodo=atualizaDataRetiradaNotificacao`).
   - Identificação visual de multas já cobradas (`tr-multa-cobrada`).

---

## 2. Análise de Viabilidade

| Requisito | Viabilidade | Impacto |
|---|---|---|
| **Fonte de dados PHP** | 100% Viável | `getNotificacoesByFilters()` já retorna todos os campos necessários em array associativo. Nenhuma alteração estrutural no banco de dados necessária. |
| **Responsividade CSS** | 100% Viável | Grid responsivo com CSS moderno / Materialize Grid (`s12 m6 l4 xl3` ou CSS Grid flexível). |
| **Acessibilidade Mobile (Touch)** | Alto Ganho | Substituição de `dblclick` por botões de toque com tamanho mínimo adequado (44px) e `data-*` attributes explícitos. |
| **Busca Rápida Client-side** | 100% Viável | Criação de campo de busca instantânea em tempo real que filtra os cards por texto (número, bloco, apto, valor, status). |
| **Compatibilidade com Modais Existentes** | 100% Viável | Os modais `#modal-inspecionar-boletos` e `#modal-multa` continuam funcionando, recebendo os mesmos parâmetros. |

---

## 3. Estrutura Proposta para o Card

Cada card representará uma notificação/multa com as seguintes seções:
1. **Topo / Cabeçalho**:
   - Badge de Tipo de Notificação (ex: MULTA em vermelho, NOTIFICAÇÃO em azul).
   - Número/Ano (#123/2026) e Status (ex: ENVIADA, PENDENTE).
   - Destaque da Unidade: `Bloco [X] - Apt [Y]`.
2. **Corpo / Dados Financeiros e Prazos**:
   - Status de Cobrança:
     - Se Cobrado: Box verde suave com Valor (R$ 250,00), Data Vencimento e Pagamento.
     - Se Não Cobrado: Box âmbar com alerta "Cobrança não lançada".
   - Datas Chave: Ocorrido, Envio e Ciência/Retirada (com tag de dias decorridos).
   - Jurídico: Se há recurso cadastrado e parecer.
3. **Barra de Ações (Rodapé)**:
   - Botão "Inspecionar Boletos" (abre modal VDS).
   - Botão "Lançar / Editar Cobrança" (abre modal de edição).
   - Botão "Data Ciência" (edição rápida).
