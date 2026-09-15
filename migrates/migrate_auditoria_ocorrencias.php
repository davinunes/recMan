<?php
require_once __DIR__ . "/../classes/repositorio.php";

echo "Iniciando migração para suporte a Auditoria / Pendência na tabela ocorrencias...\n";

$link = DBConnect();

// 1. Adicionar coluna auditoria (se não existir)
$sqlCol = "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS auditoria TINYINT(1) NOT NULL DEFAULT 0;";
if (@mysqli_query($link, $sqlCol)) {
    echo "[OK] Coluna `auditoria` verificada/adicionada com sucesso na tabela `ocorrencias`.\n";
} else {
    echo "[ERRO] Falha ao adicionar coluna `auditoria`: " . mysqli_error($link) . "\n";
}

// 2. Adicionar índice idx_ocorrencias_auditoria (se não existir)
$sqlIdx = "ALTER TABLE ocorrencias ADD INDEX IF NOT EXISTS idx_ocorrencias_auditoria (auditoria);";
if (@mysqli_query($link, $sqlIdx)) {
    echo "[OK] Índice `idx_ocorrencias_auditoria` verificado/adicionado com sucesso.\n";
} else {
    // Fallback caso a versão do MySQL/MariaDB não suporte IF NOT EXISTS em ADD INDEX
    $checkIdx = mysqli_query($link, "SHOW INDEX FROM ocorrencias WHERE Key_name = 'idx_ocorrencias_auditoria'");
    if ($checkIdx && mysqli_num_rows($checkIdx) == 0) {
        @mysqli_query($link, "ALTER TABLE ocorrencias ADD INDEX idx_ocorrencias_auditoria (auditoria);");
        echo "[OK] Índice `idx_ocorrencias_auditoria` criado via fallback.\n";
    } else {
        echo "[OK] Índice `idx_ocorrencias_auditoria` já existente.\n";
    }
}

DBClose($link);
echo "Migração concluída com sucesso!\n";
