# Plano de Implementação - Transformação do Histórico em Toolset por Unidade

Este plano estabelece a reformulação completa da tela de Histórico (`index.php?pag=historico`) para ser uma central de inteligência e toolset operacional baseado na unidade selecionada.

## 1. Visão Geral das Mudanças

A tela passará a contar com:
1. **Controles de Seleção Temporal Dinâmicos**: Navegação por mês (Mês Atual por padrão, botões de retroceder/avançar mês e seletor `input type="month"`).
2. **Dashboard Estatística da Unidade**: Cards KPI superiores exibindo métricas de notificações, recursos, entregas, autorizações, reservas, ocorrências e boletos.
3. **Painel Accordion Colapsado de Aceleradores**:
   - **Notificações & Recursos**: Ações para Acessar Recurso, Cadastrar Data de Ciência/Retirada e Confirmar Lançamento de Cobrança.
   - **Encomendas**: Foto do pacote, código de rastreio, destinatário, status e data.
   - **Autorizações de Acesso**: Visitantes, fotos, documentos, datas e convites/QR codes.
   - **Reservas de Área Comum**: Espaço reservado, data/horário, status e taxas.
   - **Ocorrências de Autoria**: Chamados abertos pela própria unidade com atalho para chat.
   - **Ocorrências com Tag (Citada/Ré)**: Chamados onde a unidade sofreu menção ou tag.
   - **Boletos**: Boletos do período com 2ª via Superlógica e extrator de multas.

## 2. Arquitetura de Arquivos Afetados

- [palco/historico.php](file:///e:/DEV/recMan/palco/historico.php): Reestruturação do layout HTML/CSS com Materialize Collapsible, Dashboard KPI, seletores de mês e modais de ação rápida.
- [classes/vds_acesso_service.php](file:///e:/DEV/recMan/classes/vds_acesso_service.php): Adição da função `vds_get_reservas_unidade` para integração com `GET /reserva` da VDS v8.
- [metodo.php](file:///e:/DEV/recMan/metodo.php): Adição do método `toolsetUnidade` agregando todas as consultas aceleradas da unidade em formato JSON otimizado.
- [meu.js](file:///e:/DEV/recMan/meu.js): Atualização dos handlers da busca de histórico para manipular os aceleradores colapsados, alterar mês e disparar ações contextuais via AJAX.

## 3. Plano de Verificação

1. **Seleção de Unidade e Mês**: Testar com unidades e alternar entre meses do ano para verificar atualização dinâmica de todos os aceleradores.
2. **Ações Contextuais**:
   - Testar o cadastro de data de ciência de notificação.
   - Testar a confirmação de lançamento de cobrança de multa.
   - Verificar link de recurso e direcionamento para `detalheRecurso.php`.
   - Testar inspeção de foto de entregas e boletos.
