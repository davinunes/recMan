<?php
require_once __DIR__ . "/../classes/repositorio.php";

echo "Iniciando migração de Auditoria de Ocorrências (Sessões, Preservação Histórica e Ortogonalidade)...\n";

$link = DBConnect();

// 1. Tabela de Sessões de Auditoria (Metadados e Totais Congelados da Apuração)
$sqlSessoes = "CREATE TABLE IF NOT EXISTS auditoria_sessoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificador_execucao VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identificador único (ex: AUD-20261120-113000)',
    titulo VARCHAR(255) NOT NULL COMMENT 'Título ou finalidade da apuração formal',
    data_referencia DATE NOT NULL COMMENT 'Data de corte selecionada para os cálculos de prazos',
    data_coleta DATETIME NOT NULL COMMENT 'Data e hora exata da extração probatória',
    usuario_id INT NOT NULL COMMENT 'ID do conselheiro responsável pela execução',
    status ENUM('finalizada', 'rascunho', 'arquivada') NOT NULL DEFAULT 'finalizada',
    total_chamados INT NOT NULL DEFAULT 0,
    parametros_filtro_json LONGTEXT DEFAULT NULL COMMENT 'Critérios e filtros aplicados no momento da apuração',
    totais_indicadores_json LONGTEXT DEFAULT NULL COMMENT 'Snapshot consolidado de todos os indicadores calculados',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_data_ref (data_referencia),
    KEY idx_auditoria_data_coleta (data_coleta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (@mysqli_query($link, $sqlSessoes)) {
    echo "[OK] Tabela `auditoria_sessoes` criada/verificada com sucesso.\n";
} else {
    echo "[ERRO] Falha ao criar `auditoria_sessoes`: " . mysqli_error($link) . "\n";
}

// 2. Tabela de Itens Auditados na Sessão (Preservação Histórica Imutável dos Chamados e Evidências)
$sqlItens = "CREATE TABLE IF NOT EXISTS auditoria_execucao_itens (
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
    
    -- Snapshot de Evidência Bruta Congelada da API
    dados_originais_api_json LONGTEXT DEFAULT NULL COMMENT 'Payload bruto congelado e lista de eventos observados',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_item_sessao (sessao_id),
    KEY idx_item_sessao_oco (sessao_id, ocorrencia_id),
    KEY idx_item_sessao_proto (sessao_id, protocolo_vds),
    KEY idx_item_sessao_faixa (sessao_id, faixa_tempo),
    KEY idx_item_sessao_resp (sessao_id, situacao_resposta),
    KEY idx_item_sessao_chamado (sessao_id, situacao_chamado),
    KEY idx_item_sessao_prov (sessao_id, situacao_providencias)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (@mysqli_query($link, $sqlItens)) {
    echo "[OK] Tabela `auditoria_execucao_itens` criada/verificada com sucesso.\n";
} else {
    echo "[ERRO] Falha ao criar `auditoria_execucao_itens`: " . mysqli_error($link) . "\n";
}

// 3. Otimização na tabela `ocorrencias` para filtros rápidos e classificação deliberada do Conselho
$alterOcorrencias = [
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS auditoria_status_resposta ENUM('sem_resposta', 'respondido_no_prazo', 'respondido_com_atraso', 'em_prazo_aguardando', 'dados_insuficientes') DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS primeira_resposta_data DATETIME DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS primeira_resposta_ator VARCHAR(50) DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS primeira_providencia_data DATETIME DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS situacao_providencias ENUM('providencia_identificavel', 'apenas_manifestacao', 'nenhuma_providencia', 'nao_determinado') DEFAULT 'nao_determinado';",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS conselho_classificacao ENUM('finalidade_atingida', 'nao_atingiu_finalidade', 'em_auditoria', 'improcedente', 'nao_avaliado') DEFAULT 'nao_avaliado';",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS conselho_providencias ENUM('providencia_comprovada', 'apenas_manifestacao_protelatoria', 'sem_providencia', 'nao_avaliado') DEFAULT 'nao_avaliado';",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS conselho_parecer_auditoria TEXT DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS conselho_auditoria_por VARCHAR(150) DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS conselho_auditoria_em DATETIME DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD INDEX IF NOT EXISTS idx_auditoria_status_resp (auditoria_status_resposta);",
    "ALTER TABLE ocorrencias ADD INDEX IF NOT EXISTS idx_auditoria_prov (situacao_providencias);",
    "ALTER TABLE ocorrencias ADD INDEX IF NOT EXISTS idx_conselho_classificacao (conselho_classificacao);"
];

foreach ($alterOcorrencias as $sql) {
    @mysqli_query($link, $sql);
}

echo "[OK] Atualização de índices auxiliares na tabela `ocorrencias` concluída.\n";

DBClose($link);
echo "Migração de Auditoria concluída com sucesso!\n";
