# Plano de Implementação Revisado: Módulo de Auditoria de Ocorrências

**Data da Revisão:** 20/11/2026  
**Status:** Etapa A – Planejamento Ajustado (Aguardando Autorização para Etapa B)  
**Projeto:** recMan (Conselho Consultivo e Fiscal)  

---

## 1. Princípios e Diretrizes Conceituais

1. **Separação Fática e Regimental:** Resposta (manifestação) e Providência são obrigações distintas sob o Art. 12 do Regimento Interno. O sistema não presume que uma resposta textual represente início de providências cabíveis.
2. **Neutralidade e Proibição de Dedução Semântica Arbitrária:** A plataforma não fará inferências de texto livre como se fossem verdades comprovadas. O que for ato formal objetivo (ex: encaminhamento formal para setor executor na API) será registrado como tal; o que for texto livre sem ato comprovado será mantido como manifestação ou classificado como "não identificável no histórico".
3. **Classificação Taxonômica dos Participantes:** Manifestações do Conselho Consultivo são atividades fiscalizadoras e **NUNCA** serão computadas como resposta da administração ao morador. Apenas manifestações de Síndico, Subsíndico, Administração, Departamentos ou Funcionários encarregados do atendimento serão consideradas como manifestações administrativas.
4. **Preservação Histórica e Evidência Probatória:** Cada execução de auditoria gerará um registro perene e congelado (snapshot de sessão e itens auditados com payload da API e métricas calculadas), garantindo que alterações posteriores no sistema oficial não apaguem as evidências de auditorias anteriores.
5. **Ortogonalidade de 4 Dimensões:** As classificações de Tempo Transcorrido, Situação da Resposta, Situação do Chamado e Situação das Providências serão mantidas completamente independentes.

---

## 2. Estrutura de Banco de Dados Proposta

A estratégia técnica adotada combina **performance imediata** para consultas corriqueiras e **preservação probatória rigorosa** para relatórios oficiais da Assembleia.

### 2.1 Tabela de Sessões / Execuções de Auditoria (`auditoria_sessoes`)
Registra cada rodada de apuração realizada pelo Conselho:
```sql
CREATE TABLE IF NOT EXISTS auditoria_sessoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificador_execucao VARCHAR(50) NOT NULL UNIQUE COMMENT 'Ex: AUD-20261120-113000',
    titulo VARCHAR(255) NOT NULL COMMENT 'Título ou finalidade da apuração',
    data_referencia DATE NOT NULL COMMENT 'Data de corte utilizada nos cálculos',
    data_coleta DATETIME NOT NULL COMMENT 'Data e hora exata da extração dos dados',
    usuario_id INT NOT NULL COMMENT 'Conselheiro responsável',
    status ENUM('finalizada', 'rascunho', 'arquivada') NOT NULL DEFAULT 'finalizada',
    total_chamados INT NOT NULL DEFAULT 0,
    parametros_filtro_json LONGTEXT DEFAULT NULL COMMENT 'Critérios de filtro aplicados na coleta',
    totais_indicadores_json LONGTEXT DEFAULT NULL COMMENT 'Consolidado estatístico congelado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_data_referencia (data_referencia),
    KEY idx_data_coleta (data_coleta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2 Tabela de Itens Auditados na Execução (`auditoria_execucao_itens`)
Armazena a evidência congelada de cada ocorrência incluída na apuração:
```sql
CREATE TABLE IF NOT EXISTS auditoria_execucao_itens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_id BIGINT UNSIGNED NOT NULL COMMENT 'FK para auditoria_sessoes.id',
    ocorrencia_id INT NOT NULL COMMENT 'ID local do chamado',
    protocolo_vds VARCHAR(50) NOT NULL COMMENT 'Protocolo público oficial da VDS',
    uuid_remoto VARCHAR(100) DEFAULT NULL,
    bloco VARCHAR(10) DEFAULT NULL,
    unidade VARCHAR(20) DEFAULT NULL,
    categoria_oco_tipo INT DEFAULT NULL,
    categoria_nome VARCHAR(100) DEFAULT NULL,
    data_abertura DATETIME NOT NULL,
    data_referencia DATE NOT NULL,
    
    -- Dimensão 1: Tempo Transcorrido
    tempo_decorrido_dias INT NOT NULL COMMENT 'Dias até data_referencia ou encerramento',
    faixa_tempo ENUM('ate_7_dias', '8_a_30_dias', '31_a_90_dias', '91_a_365_dias', 'mais_365_dias') NOT NULL,
    
    -- Dimensão 2: Situação da Resposta
    situacao_resposta ENUM('sem_resposta', 'respondido_no_prazo', 'respondido_com_atraso', 'em_prazo_aguardando', 'dados_insuficientes') NOT NULL,
    primeira_resposta_data DATETIME DEFAULT NULL,
    primeira_resposta_tempo_dias INT DEFAULT NULL,
    primeira_resposta_por VARCHAR(150) DEFAULT NULL,
    primeira_resposta_cargo_original VARCHAR(100) DEFAULT NULL,
    primeira_resposta_ator ENUM('sindico', 'subsindico', 'administracao', 'departamento', 'funcionario', 'conselho', 'morador', 'outros') DEFAULT NULL,
    primeira_resposta_resumo TEXT DEFAULT NULL,
    
    -- Dimensão 3: Situação do Chamado
    situacao_chamado ENUM('aberto', 'em_andamento', 'encerrado', 'nao_identificado') NOT NULL,
    data_encerramento DATETIME DEFAULT NULL,
    tempo_ate_encerramento_dias INT DEFAULT NULL,
    
    -- Dimensão 4: Situação das Providências
    situacao_providencias ENUM('providencia_identificavel', 'apenas_manifestacao', 'nenhuma_providencia', 'nao_determinado') NOT NULL,
    primeira_providencia_data DATETIME DEFAULT NULL,
    primeira_providencia_tempo_dias INT DEFAULT NULL,
    primeira_providencia_descricao TEXT DEFAULT NULL,
    tipo_comprovacao_providencia VARCHAR(100) DEFAULT NULL,
    
    -- Snapshot de Evidência Bruta Congelada
    dados_originais_api_json LONGTEXT DEFAULT NULL COMMENT 'Payload original completo e eventos observados',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_sessao_ocorrencia (sessao_id, ocorrencia_id),
    KEY idx_sessao_protocolo (sessao_id, protocolo_vds),
    KEY idx_sessao_faixa (sessao_id, faixa_tempo),
    KEY idx_sessao_resposta (sessao_id, situacao_resposta),
    KEY idx_sessao_providencias (sessao_id, situacao_providencias)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Classificação dos Participantes

A tabela a seguir define as regras determinísticas de classificação com base nos dados reais inspecionados na API da VDS:

| Categoria do Participante | Padrões de Mapeamento (API VDS) | Trata como Resposta Administrativa? |
|---|---|---|
| **Síndico** | Cargo "Síndico" (excluindo "Sub"), foto em pasta `/SINDICO/`, nome do síndico geral | **Sim** |
| **Subsíndico** | Cargo "Subsíndico" ou "Síndico de Bloco" | **Sim** |
| **Administração** | Cargo "Administração", "CONDOMÍNIO - GESTÃO", "Gestão", "Gerência" | **Sim** |
| **Departamento Responsável** | Cargo ou grupo "Mensageria", "Supervisão", "Portaria", "Monitoramento", "Manutenção" | **Sim** |
| **Funcionário / Colaborador** | Cargo "Zelador", "Porteiro", grupo `FUN` ("Funcionários") | **Sim** (Apenas atos de atendimento) |
| **Conselho** | Cargo "Conselheiro", "Conselho Consultivo", "Fiscal", ou usuário com papel de Conselho | **NÃO** (Órgão fiscalizador, manifestação interna/auxiliar) |
| **Morador** | Cargo "Morador", padrão `Bloco X - Y`, ou mesmo autor da ocorrência | **NÃO** (Manifestação do interessado) |
| **Outros / Não Identificados** | Dados ausentes ou não mapeáveis | **NÃO** (Preserva texto original para conferência) |

---

## 4. Regras de Cálculo e Fronteiras Temporais

### 4.1 Contagem de Prazos por Períodos Completos de 24 Horas
Adotamos o critério de **períodos completos de 24 horas** a contar do segundo exato da abertura:
$$\Delta s = T_{\text{evento}} - T_{\text{abertura}}$$
$$\text{Dias completos decorridos} = \lfloor \Delta s / 86400 \rfloor$$

- **Limite de 7 Dias:** $7 \times 86400 = 604.800 \text{ segundos}$ ($168 \text{ horas}$).
- Se $\Delta s \le 604.800 \text{ s}$: **Dentro do prazo regulamentar**.
- Se $\Delta s > 604.800 \text{ s}$: **Com atraso** (inicia a faixa de 8 a 30 dias).

### 4.2 Chamados Recentes com Menos de 7 Dias
Se $T_{\text{referencia}} - T_{\text{abertura}} \le 604.800 \text{ s}$ e ainda não houver manifestação da administração:
- O chamado **NÃO** é classificado como atrasado.
- Sua situação de resposta é categorizada como: `em_prazo_aguardando` (Em prazo regulamentar, aguardando manifestação).
- O tempo transcorrido é classificado como `ate_7_dias`.

### 4.3 Segregação de Tempos Calculados
1. **Tempo até a Primeira Manifestação:** $T_{\text{primeira\_resposta}} - T_{\text{abertura}}$.
2. **Tempo até a Primeira Providência:** $T_{\text{primeira\_providencia}} - T_{\text{abertura}}$ (quando identificada).
3. **Tempo de Permanência Decorrido (Pendentes):** $T_{\text{data\_referencia}} - T_{\text{abertura}}$.
4. **Tempo até Encerramento (Encerrados):** $T_{\text{encerramento}} - T_{\text{abertura}}$.

---

## 5. As 4 Dimensões Ortogonais

| Dimensão | Valores Possíveis | Significado Objetivo |
|---|---|---|
| **1. Tempo Transcorrido** | • Até 7 dias<br>• 8 a 30 dias<br>• 31 a 90 dias<br>• 91 a 365 dias<br>• Mais de 365 dias | Tempo absoluto transcorrido da abertura até a data de referência (ou encerramento). |
| **2. Situação da Resposta** | • Sem resposta administrativa registrada<br>• Respondido dentro do prazo (<= 7 dias)<br>• Respondido com atraso (> 7 dias)<br>• Em prazo regulamentar (sem resposta ainda)<br>• Dados insuficientes | Avalia exclusivamente a ocorrência e tempestividade do primeiro retorno administrativo. |
| **3. Situação do Chamado** | • Aberto<br>• Em andamento<br>• Encerrado<br>• Não identificado | Estado formal do ciclo de vida do chamado no sistema oficial. |
| **4. Situação das Providências** | • Providência identificável no histórico<br>• Apenas manifestação registrada<br>• Nenhuma providência identificável<br>• Não é possível determinar | Avalia se houve registro formal de ato material de providência (encaminhamento, OS) ou apenas troca de mensagens. |

---

## 6. Indicadores do Painel da Auditoria

1. **Volume Geral Auditado:**
   - Total de ocorrências no universo selecionado.
   - Chamados abertos, em andamento e encerrados.
2. **Cumprimento do Prazo de Resposta (Art. 12):**
   - Respondidos em até 7 dias (qtd e %).
   - Respondidos com atraso > 7 dias (qtd e %).
   - Sem resposta com prazo ultrapassado (qtd e %).
   - Sem resposta em prazo regulamentar (qtd e %).
3. **Comprovação de Providências:**
   - Com providência formal identificável (qtd e %).
   - Apenas com manifestação registrada (qtd e %).
   - Nenhuma providência ou manifestação (qtd e %).
   - Não determinável pelo histórico (qtd e %).
4. **Indicadores de Tempo:**
   - Tempo médio e mediano até a primeira manifestação administrativa.
   - Tempo médio e mediano até a primeira providência formal identificável.
   - Maior tempo sem resposta registrado.
   - Tempo médio até encerramento formal.
5. **Distribuição e Cruzamentos:**
   - Chamados por faixa temporal.
   - Distribuição por bloco e por categoria de ocorrência.
   - Lista dos chamados mais antigos ainda sem resposta e ainda pendentes.

---

## 7. Limitações Técnicas Remanescentes Documentadas

1. **Atos Verbais e Tratativas Externas:** Atendimentos presenciais, vistorias não cadastradas ou contatos por telefone/WhatsApp pessoal não geram logs na API oficial. O módulo audita estritamente o que consta do Livro Oficial de Ocorrências e da plataforma informatizada.
2. **Encerramentos sem Registro Textual:** Quando o administrador encerra um chamado alterando apenas o status sem emitir justificativa ou comentário final, a data de encerramento corresponde à última data de modificação (`dtExibicao`) no sistema oficial.
3. **Impossibilidade de Avaliar Mérito Subjetivo:** O sistema audita datas, prazos, presenças de atos e agentes, abstendo-se de emitir julgamentos sobre se a resposta dada foi satisfatória ou se a providência foi suficiente. Essa avaliação caberá privativamente ao Conselho e à Assembleia Geral.
