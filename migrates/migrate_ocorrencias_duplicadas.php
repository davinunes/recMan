<?php
require_once __DIR__ . "/../classes/repositorio.php";

echo "Iniciando migração para suporte a Ocorrências Duplicadas / Vínculos de Duplicidade...\n";

$link = DBConnect();

$sqlDuplicadas = "CREATE TABLE IF NOT EXISTS ocorrencia_duplicadas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id_principal INT NOT NULL COMMENT 'ID da ocorrência principal / de referência',
    ocorrencia_id_duplicada INT NOT NULL COMMENT 'ID da ocorrência duplicada',
    motivo VARCHAR(255) DEFAULT 'Pedido duplicado',
    criado_por INT DEFAULT NULL COMMENT 'ID do conselheiro que realizou o vínculo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_duplicidade (ocorrencia_id_principal, ocorrencia_id_duplicada),
    KEY idx_principal (ocorrencia_id_principal),
    KEY idx_duplicada (ocorrencia_id_duplicada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($link, $sqlDuplicadas)) {
    echo "[OK] Tabela `ocorrencia_duplicadas` verificada/criada com sucesso.\n";
} else {
    echo "[ERRO] Falha ao criar tabela `ocorrencia_duplicadas`: " . mysqli_error($link) . "\n";
}

DBClose($link);
echo "Migração concluída com sucesso!\n";
