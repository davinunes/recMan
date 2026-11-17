# Raciocínio de Implementação: Linkar Ocorrências Duplicadas e Indicadores de Delay no Livro de Ocorrências VDS

**Data:** 17/11/2026  
**Contexto:** Livro de Ocorrências da VDS (`livroDeOcorrencias.php` / `vds_ocorrencia_service.php`)  
**Objetivo:** Atender a dois requisitos solicitados pelo usuário:
1. Capacidade de linkar ocorrências (pedidos duplicados), permitindo identificar visualmente no sistema que uma ocorrência se trata de uma duplicidade, qual é a principal/duplicada e navegar entre elas.
2. Sinalizador de delay no atendimento:
   - Chamados abertos/pendentes: dias desde a abertura até hoje E dias desde a última interação até hoje.
   - Chamados resolvidos: indicador único da abertura até a última interação (tempo total de resolução).

---

## 1. Análise e Diagnóstico da Arquitetura Atual

### 1.1 Livro de Ocorrências (`livroDeOcorrencias.php`)
- A tela possui duas visões:
  - **Prática**: Consulta ocorrências não lidas na API da VDS (`vds_get_ocorrencias_pratico`), persistindo/enriquecendo localmente.
  - **Analítica**: Consulta a tabela local `ocorrencias` via SQL com múltiplos filtros (bloco, unidade, status, tipo, responsabilidade, auditoria).
- Ao selecionar uma ocorrência (seja na carga inicial ou via AJAX `action=carregar_detalhe`), `vds_get_ocorrencia_detalhe` retorna:
  - `local`: Dados da ocorrência na tabela `ocorrencias`.
  - `notasInternas`: Notas salvas em `ocorrencia_notas_internas`.
  - `tagsUnidades`: Vínculos com blocos/unidades, notificações ou tags livres em `ocorrencia_unidade_tag`.
  - `remoteData`: Dados detalhados remotos da VDS (`/ocorrencia/{uuid}`), com os `eventos` de conversa entre morador e condomínio.
- Ações como atribuir responsabilidade, marcar resolvido, auditoria e adicionar tags operam via AJAX sem recarregar a tela inteira.

### 1.2 Gestão de Duplicidade
- Atualmente, não existia nenhuma tabela ou mecanismo para relacionar uma ocorrência a outra como "duplicada".
- Requisitos para a solução:
  - Tabela `ocorrencia_duplicadas`:
    - `id` (PK)
    - `ocorrencia_id_principal` (INT, FK lógica de ocorrencias.id)
    - `ocorrencia_id_duplicada` (INT, FK lógica de ocorrencias.id)
    - `motivo` (VARCHAR, ex: "Pedido duplicado")
    - `criado_por` (INT, conselheiro logado)
    - `created_at` (TIMESTAMP)
    - `UNIQUE KEY uk_duplicidade (ocorrencia_id_principal, ocorrencia_id_duplicada)`
  - Bidirecionalidade informativa:
    - Se a ocorrência aberta for a `duplicada`, o conselheiro deve ver claramente: "⚠️ Ocorrência marcada como DUPLICADA de #Protocolo (Bloco X - Apt Y)".
    - Se a ocorrência aberta for a `principal`, deve ver: "ℹ️ Possui ocorrência duplicada vinculada: #Protocolo (Bloco X - Apt Y)".
    - Botão para navegar diretamente para a outra ocorrência (via `selecionarOcorrencia(id)` no chat AJAX ou link direto).
    - Botão para desvincular duplicidade com 1 clique (AJAX).
    - Botão no cabeçalho do chat ("Linkar Duplicado") com modal intuitivo, permitindo buscar por protocolo ou ID local.
    - Reconhecimento automático no input de tag caso seja digitado `dup:259564` ou `duplicada:259564`.
    - Na lista lateral da sidebar, exibir badge discreto `🔗 DUP` para destacar ocorrências duplicadas.

### 1.3 Cálculo de Delays no Atendimento
- **Marco de Abertura**: Data de criação do chamado (`local['abertura']` ou dados_json / evento 1).
- **Marco de Última Interação**: Maior data entre:
  - Data de abertura
  - Datas de todos os eventos remotos da VDS (`dtHora`, `dthora`, `data`)
  - Datas de notas internas do conselho (`created_at`)
  - Campo `dtExibicao` salvo em `dados_json` (que na VDS reflete a última movimentação)
- **Lógica dos Indicadores**:
  1. **Se Aberto / Pendente (`!$isResolvido`)**:
     - *Dias desde a abertura até hoje*: `floor((now - abertura) / 86400)` dias.
     - *Dias desde a última interação até hoje*: `floor((now - ultimaInteracao) / 86400)` dias.
     - Badges com cores dinâmicas para sinalizar a urgência (verde: <3 dias; laranja: 3-6 dias; vermelho: >=7 dias sem resposta).
  2. **Se Marcado como Resolvido (`$isResolvido`)**:
     - Mantém **apenas um indicador** da abertura até a última interação:
       `floor((ultimaInteracao - abertura) / 86400)` dias (ex: "Resolvido em X dias").
     - Se resolvido no mesmo dia: "Resolvido no mesmo dia" (<1 dia).
- Exibição:
  - No cabeçalho do chat, junto aos metadados de abertura.
  - No card de cada ocorrência na sidebar esquerda (ex: `⏱️ 12d • 💬 2d` ou se resolvido `✓ 3d`), permitindo visão analítica instantânea do conselheiro.

---

## 2. Decisões de Implementação
1. Criar `migrates/migrate_ocorrencias_duplicadas.php` com o schema DDL.
2. Adicionar função `vds_ensure_duplicadas_table_exists($link)` em `classes/vds_ocorrencia_service.php` para garantir execução transparente no ambiente remoto sem necessidade de rodar migration manual.
3. Adicionar funções de serviço:
   - `vds_linkar_ocorrencias_duplicadas($origemId, $alvoProtocoloOuId, $usuarioId, $motivo)`
   - `vds_desvincular_ocorrencia_duplicada($linkId, $ocorrenciaId)`
   - `vds_get_duplicidades_ocorrencia($ocorrenciaId, $linkParam)`
   - `vds_calcular_delays_ocorrencia($local, $eventosRemotos = [], $notasInternas = [])`
4. Atualizar `vds_get_ocorrencia_detalhe` para incluir `duplicidades` e `delays`.
5. Atualizar `livroDeOcorrencias.php`:
   - Endpoints AJAX para `linkar_duplicidade` e `desvincular_duplicidade`.
   - Renderização dos cards de alerta de duplicidade no topo do chat.
   - Renderização dos sinalizadores visuais de delay no header do chat e na sidebar.
   - Modal e script JS para linkar duplicidades sem recarregar a tela.
