# Raciocínio: Correção da Discrepância de Delays no Modo Analítico (Sidebar vs Chat)

**Data:** 17/11/2026  
**Problema:** Na Visão Analítica do Livro de Ocorrências (`livroDeOcorrencias.php`), o card da lista lateral (sidebar) exibia `⏱️ 21d • 💬 21d` (indicando 21 dias sem interação), enquanto o cabeçalho do Chat da mesma ocorrência aberta exibia `⏱️ Aberto há 21 dias | 💬 Última interação há 1 dia`. O usuário observou ainda que na Visão Prática os delays funcionam corretamente.

---

## 1. Análise de Causa Raiz

### Por que funcionava na Visão Prática?
Na Visão Prática (`vds_get_ocorrencias_pratico`), as ocorrências são obtidas diretamente do endpoint `GET /ocorrencia?Lida=0` da API v8 da VDS.
Nesse retorno da VDS, cada item já traz o campo `dtExibicao`, que corresponde exatamente ao **timestamp do evento/mensagem mais recente**. Por isso, a função `vds_calcular_delays_ocorrencia` encontrava `dtExibicao` dentro de `dados_json` com data de ontem e calculava `💬 1d`.

### Por que falhou na Visão Analítica (Sidebar)?
1. **Dados locais estáticos:** No Modo Analítico, os cards são renderizados a partir do banco local MySQL (`SELECT * FROM ocorrencias`).
2. **Ausência de eventos na listagem em massa:** A listagem da sidebar possui dezenas ou centenas de registros. Não é viável (e causaria lentidão crítica) fazer dezenas de chamadas HTTP para `/ocorrencia/{uuid}` para buscar os eventos remotos de cada linha da lista.
3. **Falta de persistência do timestamp de interação:**
   - A tabela `ocorrencias` possui a coluna nativa `data_ultima_mensagem DATETIME`, porém:
     - `vds_calcular_delays_ocorrencia` **não** consultava `$local['data_ultima_mensagem']`.
     - Ao acessar o chat de um chamado, `vds_get_ocorrencia_detalhe` chamava a API remota da VDS, encontrava os eventos reais com mensagem de ontem, calculava o delay correto para o cabeçalho do chat, mas **não persistia de volta** esse timestamp atualizado no banco local (`data_ultima_mensagem` ou `dados_json['dtExibicao']`).
     - Em chamados sincronizados ou salvos antigamente, `dados_json` mantinha o `dtExibicao` do dia da abertura ou criação do registro, ficando `tsUltimaInteracao == tsAbertura` (21 dias).
4. **Sem sincronização em tempo real na UI (Front-end):**
   - Ao clicar no card ou carregar via AJAX `carregar_detalhe`, a resposta JSON trazia o objeto `delays` atualizado do chat, mas o JavaScript de `selecionarOcorrencia` não atualizava o badge de delay do card correspondente no DOM da sidebar.

---

## 2. Estratégia de Solução

1. **Camada de Serviço (`vds_ocorrencia_service.php`):**
   - Em `vds_calcular_delays_ocorrencia`:
     - Consultar `$local['data_ultima_mensagem']` e considerá-la na determinação de `$tsUltimaInteracao`.
     - Consultar `$dadosJson['dtExibicao']` e `$dadosJson['dthora']`.
     - Considerar notas internas do conselho pré-carregadas em lote (`$local['max_nota_interna']`).
   - Em `vds_get_ocorrencia_detalhe`:
     - Assim que os eventos remotos e notas forem consolidados e `$delays` for calculado, caso `$tsUltimaInteracao` seja mais recente do que o registrado, atualizar silenciosamente no banco:
       `UPDATE ocorrencias SET data_ultima_mensagem = ? WHERE id = ?`.
     - Atualizar também o array local retornado.
   - Em `vds_persist_item_local` e `vds_atualizar_ocorrencia_por_protocolo`:
     - Parsear `dtExibicao` da VDS e gravar em `data_ultima_mensagem` tanto no `INSERT` quanto no `UPDATE`.

2. **Camada de Visão (`livroDeOcorrencias.php`):**
   - Na Visão Analítica (SSR):
     - Executar query única e leve para notas internas: `SELECT ocorrencia_id, MAX(created_at) as max_nota FROM ocorrencia_notas_internas GROUP BY ocorrencia_id`, injetando `max_nota_interna` nas linhas da sidebar.
     - Se `$detalheSel` já tiver sido calculado para a ocorrência selecionada inicial, repassar seus delays calculados diretamente para o card correspondente na sidebar, garantindo paridade instantânea no HTML inicial.
   - No Front-end (JavaScript):
     - No retorno de `selecionarOcorrencia` (AJAX `carregar_detalhe`), atualizar imediatamente o HTML e classes de `#item-oco-${ocorrenciaId} .badge-delay-sidebar` com `res.delays`.
