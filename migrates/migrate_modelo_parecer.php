<?php
/**
 * Migração: Adicionar coluna 'modelo' na tabela conselho.parecer
 *
 * Permite selecionar entre 'estatico' (modelo seccionado legado) e 'full_dinamico' (modelo corrido livre).
 *
 * Uso via navegador ou CLI: php migrates/migrate_modelo_parecer.php
 */
require_once __DIR__ . "/../classes/database.php";

$link = DBConnect();
if (!$link) {
    echo "Erro ao conectar no banco.\n";
    exit(1);
}

// Verifica se a coluna já existe
$checkSql = "SHOW COLUMNS FROM conselho.parecer LIKE 'modelo'";
$resCheck = mysqli_query($link, $checkSql);

if ($resCheck && mysqli_num_rows($resCheck) > 0) {
    echo "[OK] A coluna 'modelo' ja existe na tabela conselho.parecer.\n";
} else {
    $sql = "ALTER TABLE conselho.parecer ADD COLUMN modelo VARCHAR(50) NOT NULL DEFAULT 'estatico'";
    if (mysqli_query($link, $sql)) {
        echo "[OK] Coluna 'modelo' adicionada com sucesso na tabela conselho.parecer.\n";
    } else {
        echo "[ERRO] Falha ao adicionar coluna 'modelo': " . mysqli_error($link) . "\n";
    }
}

DBClose($link);
echo "Migracao de modelo de parecer concluida.\n";
