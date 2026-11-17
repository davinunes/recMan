# Plano de Implementação: Linkar Ocorrências Duplicadas e Indicadores de Delay no Livro de Ocorrências VDS

**Data:** 17/11/2026  
**Status:** Pronto para Execução  
**Arquivos Envolvidos:**
- `migrates/migrate_ocorrencias_duplicadas.php` (novo)
- `classes/vds_ocorrencia_service.php` (atualização de funções core)
- `livroDeOcorrencias.php` (atualização de UI, endpoints AJAX e scripts)

---

## Etapas do Plano

### Etapa 1: Estrutura de Banco de Dados e Migração
1. Criar `migrates/migrate_ocorrencias_duplicadas.php` com DDL idempotente:
   - Tabela `ocorrencia_duplicadas` com campos `id`, `ocorrencia_id_principal`, `ocorrencia_id_duplicada`, `motivo`, `criado_por`, `created_at`.
   - Índices para busca rápida e chave única composta evitando duplicatas do mesmo vínculo.
2. Adicionar função `vds_ensure_duplicadas_table_exists($link)` em `classes/vds_ocorrencia_service.php`.

### Etapa 2: Métodos de Negócio em `classes/vds_ocorrencia_service.php`
1. `vds_calcular_delays_ocorrencia($local, $eventosRemotos = [], $notasInternas = [])`:
   - Determinar timestamp de abertura e timestamp de última interação (remota VDS, notas internas locais ou dtExibicao).
   - Se não resolvido: calcular dias desde abertura e dias desde última interação.
   - Se resolvido: calcular apenas o tempo total de resolução (abertura até última interação).
   - Retornar estrutura com valores numéricos, textos amigáveis formatados e classes de severidade de cor.
2. `vds_linkar_ocorrencias_duplicadas($ocorrenciaIdAtual, $alvoProtocoloOuId, $usuarioId = null, $motivo = 'Pedido duplicado', $ehDuplicada = true)`:
   - Resolver a ocorrência alvo por ID ou Protocolo VDS (buscando na API VDS e persistindo se necessário).
   - Validar que as duas ocorrências são distintas.
   - Definir se a atual é a duplicada (apontando para a alvo como principal) ou vice-versa.
   - Inserir na tabela `ocorrencia_duplicadas`.
3. `vds_desvincular_ocorrencia_duplicada($linkId, $ocorrenciaId = null)`:
   - Remover o registro de duplicidade com segurança.
4. `vds_get_duplicidades_ocorrencia($ocorrenciaId, $linkParam = null)`:
   - Buscar vínculos onde a ocorrência é principal OU duplicada.
   - Retornar metadados da outra ponta (protocolo, bloco, unidade, status, data).
5. Integrar em `vds_get_ocorrencia_detalhe`:
   - Preencher chaves `'duplicidades'` e `'delays'` no retorno.

### Etapa 3: Interface e Endpoints em `livroDeOcorrencias.php`
1. **Endpoints AJAX (POST/REQUEST)**:
   - `action=linkar_duplicidade`: recebe `ocorrencia_id`, `protocolo_alvo`, `tipo_relacao` ('atual_duplicada' ou 'atual_principal'), executa vínculo e retorna JSON.
   - `action=desvincular_duplicidade`: recebe `link_id` e `ocorrencia_id`, remove vínculo e retorna JSON.
   - Suporte no input de tag livre (`adicionar_tag_livre`): se informado `dup:123456`, vincula automaticamente como duplicidade.
2. **Exibição do Sinalizador de Delay**:
   - No Header do Chat: bloco visual moderno com badges coloridos para tempo de abertura e última interação (ou tempo de resolução quando resolvido).
   - Na Sidebar (cards de ocorrências): badge compacto de delay para leitura rápida na lista.
3. **Exibição de Duplicidades**:
   - Card/Banner de alerta no topo do chat (laranja para quando for duplicada, azul para quando possuir duplicadas vinculadas).
   - Botão para navegar diretamente para a ocorrência vinculada (`selecionarOcorrencia(...)`).
   - Botão para desvincular com confirmação amigável.
   - Botão no header do chat: "Linkar Duplicado", acionando modal para digitar o protocolo/ID.
   - Modal de Duplicidade (Materialize CSS) com campos de protocolo, seleção de direção (duplicada desta / esta é a duplicada) e botão salvar AJAX.
   - Badge `🔗 DUP` no card da sidebar quando a ocorrência possuir vínculo.

### Etapa 4: Validação e Testes
1. Validar sintaxe PHP em todos os arquivos modificados.
2. Verificar compatibilidade com chamados em Visão Prática e Analítica.
3. Gerar Walkthrough com evidências e guia de uso.
