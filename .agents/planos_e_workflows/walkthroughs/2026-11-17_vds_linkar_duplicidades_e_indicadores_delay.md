# Walkthrough: Linkar Ocorrências Duplicadas e Indicadores de Delay no Livro de Ocorrências VDS

**Data:** 17/11/2026  
**Status:** Concluído com Sucesso  
**Módulos Envolvidos:**
- [`migrates/migrate_ocorrencias_duplicadas.php`](file:///d:/dev/github/recMan/migrates/migrate_ocorrencias_duplicadas.php)
- [`classes/vds_ocorrencia_service.php`](file:///d:/dev/github/recMan/classes/vds_ocorrencia_service.php)
- [`livroDeOcorrencias.php`](file:///d:/dev/github/recMan/livroDeOcorrencias.php)

---

## 1. Visão Geral das Entregas

Foram implementadas as duas funcionalidades solicitadas para o Livro de Ocorrências (`index.php?pag=livroDeOcorrencias`):

1. **Capacidade de Linkar Ocorrências (Duplicidades):**
   - Criação da estrutura de dados `ocorrencia_duplicadas` com suporte a chave única de pares e índices relacionais.
   - Detecção bidirecional completa: ao acessar uma ocorrência duplicada ou a ocorrência principal original, o sistema identifica e exibe o vínculo.
   - Exibição de cards de alerta em destaque no topo do chat (laranja para chamado marcado como cópia; azul para chamado que possui duplicatas vinculadas).
   - Botão para navegar diretamente para a outra ocorrência (`selecionarOcorrencia(...)` sem recarregar a tela) e botão para desvincular via AJAX com 1 clique.
   - Botão prático "Linkar Duplicado" no cabeçalho do chat, abrindo modal intuitivo para informar o protocolo ou ID do outro chamado.
   - Suporte inteligente no campo de tags livres: ao digitar `dup:259564`, `duplicada:259564` ou `#259564`, o vínculo é criado imediatamente.
   - Badge visual `🔗 DUP` no feed lateral da sidebar para rápida identificação de duplicidades na listagem.

2. **Sinalizadores de Delay no Atendimento:**
   - **Chamado Aberto / Pendente:**
     - `⏱️ Aberto há X dias` (contados a partir da data de abertura até a data atual).
     - `💬 Última interação há Y dias` (contados a partir do evento mais recente — mensagens da VDS ou notas internas — até hoje).
     - Cores de severidade dinâmicas (Verde: < 3 dias; Laranja: 3 a 6 dias; Vermelho: ≥ 7 dias sem resposta).
   - **Chamado Marcado como Resolvido:**
     - Mantém **apenas um indicador consolidado**: tempo decorrido da abertura até a última interação (ex: `✓ Resolvido em 4 dias`).
   - Visibilidade em dois níveis:
     - No **Cabeçalho do Chat**: badges completos com tooltips informativos e data/hora detalhada.
     - Na **Sidebar (Lista Lateral)**: badges compactos nos cards (`⏱️ 12d • 💬 2d` ou `🏁 4d`), permitindo triagem instantânea.

---

## 2. Detalhes Técnicos Implementados

### 2.1 Banco de Dados e Migração
Arquivo [`migrates/migrate_ocorrencias_duplicadas.php`](file:///d:/dev/github/recMan/migrates/migrate_ocorrencias_duplicadas.php):
```sql
CREATE TABLE IF NOT EXISTS ocorrencia_duplicadas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id_principal INT NOT NULL COMMENT 'ID da ocorrência principal / de referência',
    ocorrencia_id_duplicada INT NOT NULL COMMENT 'ID da ocorrência duplicada',
    motivo VARCHAR(255) DEFAULT 'Pedido duplicado',
    criado_por INT DEFAULT NULL COMMENT 'ID do conselheiro que realizou o vínculo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_duplicidade (ocorrencia_id_principal, ocorrencia_id_duplicada),
    KEY idx_principal (ocorrencia_id_principal),
    KEY idx_duplicada (ocorrencia_id_duplicada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
> **Nota de Resiliência:** A função `vds_ensure_duplicadas_table_exists($link)` foi incluída em `classes/vds_ocorrencia_service.php` para garantir a criação automática da tabela no ambiente remoto sem necessidade de execução manual de comandos.

### 2.2 Camada de Serviços (`classes/vds_ocorrencia_service.php`)
- **`vds_parse_datetime_to_timestamp($dtStr)`**: Utilitário robusto que normaliza strings de datas brasileiras (`d/m/Y H:i:s`), ISO 8601 e SQL para timestamps Unix.
- **`vds_calcular_delays_ocorrencia($local, $eventosRemotos, $notasInternas)`**:
  - Consolida abertura e data da última interação entre VDS remoto e notas locais do conselho.
  - Se resolvido, calcula tempo total de atendimento (`ultimaInteracao - abertura`).
  - Se aberto, calcula dias desde abertura e dias desde a última resposta com faixas de severidade (`normal`, `alerta`, `critico`).
- **`vds_get_duplicidades_ocorrencia($ocorrenciaId, $linkParam)`**:
  - Retorna metadados da outra ponta (`protocolo_vds`, `bloco`, `unidade`, `status`, `resolvido`, `motivo`) e sinalizadores `eh_duplicada` e `possui_duplicadas`.
- **`vds_linkar_ocorrencias_duplicadas($ocorrenciaIdAtual, $alvoProtocoloOuId, $usuarioId, $tipoRelacao, $motivo)`**:
  - Resolve ocorrência alvo por ID ou por protocolo VDS (fazendo busca na VDS e persistência caso ainda não exista no banco local).
  - Impede auto-vínculo e duplicatas do mesmo par.
- **`vds_desvincular_ocorrencia_duplicada($linkId, $ocorrenciaId)`**:
  - Remove com segurança o vínculo relacional.
- **`vds_get_ocorrencia_detalhe`**:
  - Preenchimento automático das chaves `duplicidades` e `delays`.
- **`vds_adicionar_tag_livre`**:
  - Reconhece padrões `dup:259564`, `duplicada:259564` ou `#259564` para vincular duplicidades via campo de tags.

### 2.3 Interface do Usuário (`livroDeOcorrencias.php`)
1. **Ações AJAX no Backend**:
   - `action=linkar_duplicidade`: vincula ocorrências via modal ou tag e retorna JSON.
   - `action=desvincular_duplicidade`: desfaz vínculo e retorna confirmação JSON.
2. **Chat Header**:
   - Adicionado botão `Linkar Duplicado` (ícone `content_copy` em tom laranja).
   - Metadados de Delay integrados na área de abertura do chamado:
     - Se resolvido: badge verde com tempo de conclusão da abertura até a última resposta.
     - Se aberto: badge azul com dias de abertura + badge dinâmico (verde/laranja/vermelho) com dias desde a última interação.
3. **Banner de Duplicidade no Chat**:
   - Alerta visual de alta visibilidade com botão direto para navegar até o outro chamado e botão para desvincular.
4. **Modal Materialize `#modal-linkar-duplicidade`**:
   - Permite digitar o protocolo/ID da outra ocorrência, selecionar a direção da duplicidade e salvar sem reload.
5. **Feed Lateral (Sidebar)**:
   - Consulta otimizada do mapa de duplicidades em lote.
   - Badge `🔗 DUP` no topo do card e badge compacto de delay no rodapé (`⏱️ Xd • 💬 Yd` ou `🏁 Xd`).

---

## 3. Como Utilizar as Novas Funcionalidades

1. **Visualizar Delays no Atendimento:**
   - Acesse `index.php?pag=livroDeOcorrencias`.
   - Na lista lateral, veja rapidamente os indicadores nos cards:
     - Chamados abertos mostram há quantos dias foram abertos (`⏱️`) e há quantos dias não recebem interação (`💬`).
     - Chamados resolvidos exibem a bandeira `🏁` com os dias totais da abertura até a resolução.
   - Ao clicar em uma ocorrência, o cabeçalho do chat detalha as datas exatas e o tempo decorrido.

2. **Linkar Chamado Duplicado:**
   - No cabeçalho da ocorrência, clique em **"Linkar Duplicado"** (ou digite `dup:NÚMERO` no campo de tags).
   - Informe o número do Protocolo VDS ou o ID local do outro chamado.
   - Escolha se o chamado atual é a duplicata ou o original e confirme.
   - O chat exibirá imediatamente o banner de duplicidade e a sidebar exibirá o badge `🔗 DUP`.

3. **Navegar e Desvincular:**
   - No banner de alerta de duplicidade, clique em **"Ver Chamado Original"** (ou **"Ver Chamado Duplicado"**) para carregar instantaneamente o outro chamado no chat.
   - Para remover a duplicidade, clique no botão **"Desvincular"** no mesmo banner.

---

## 4. Paridade e Sincronização de Delays (Modo Analítico vs Modo Prático)

Foi identificado que, na Visão Analítica, a lista da sidebar lia exclusivamente do MySQL local sem os eventos remotos da VDS em tempo real (para evitar dezenas de requisições HTTP síncronas que degradariam a performance). Isso causava uma discrepância visual temporária entre a lista (`⏱️ 21d • 💬 21d`) e o chat aberto (`Aberto há 21 dias | Última interação há 1 dia`).

Para resolver isso em definitivo com alta performance e sem sobrecarregar a API:
1. **Persistência Automática em `vds_get_ocorrencia_detalhe`:** Ao carregar os eventos remotos de um chamado, o serviço agora persiste automaticamente a data do evento mais recente na coluna nativa `data_ultima_mensagem` da tabela `ocorrencias`.
2. **Leitura de `data_ultima_mensagem`:** A função `vds_calcular_delays_ocorrencia` passou a considerar prioritariamente o campo `data_ultima_mensagem` e o campo `dtExibicao` do JSON local.
3. **Pré-carregamento em Lote de Notas Internas:** Na Visão Analítica, as datas das notas internas mais recentes são agregadas em uma única query leve (`SELECT ocorrencia_id, MAX(created_at)`) e aplicadas a todos os itens da sidebar.
4. **Sincronização Imediata no SSR e no JavaScript:**
   - O item inicial ativo no servidor já nasce com os delays calculados do chat repassados para o card da sidebar.
   - No retorno do AJAX `carregar_detalhe`, o JavaScript atualiza instantaneamente o badge `#item-oco-ID .badge-delay-sidebar` no DOM, garantindo 100% de paridade entre sidebar e chat.
