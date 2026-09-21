<?php
/**
 * Migração: Adicionar configurações de motor de PDF (Typst vs Legado) na tabela config_sistema
 * 
 * Uso via navegador ou CLI: php migrates/migrate_config_pdf_typst.php
 */
require_once __DIR__ . "/../classes/database.php";

$link = DBConnect();
if (!$link) {
    echo "[ERRO] Falha ao conectar no banco de dados.\n";
    exit(1);
}

// 1. Garante que a tabela config_sistema existe
$sqlTable = "CREATE TABLE IF NOT EXISTS config_sistema (
    chave VARCHAR(100) NOT NULL PRIMARY KEY,
    valor TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($link, $sqlTable)) {
    echo "[OK] Tabela 'config_sistema' verificada/criada com sucesso.\n";
} else {
    echo "[ERRO] Falha ao criar tabela 'config_sistema': " . mysqli_error($link) . "\n";
}

// 2. Valores padrão das configurações
$configsPadrao = [
    'pdf_engine'          => 'typst',
    'pdf_typst_template' => 'modern',
    'pdf_exibir_banner'   => '1'
];

foreach ($configsPadrao as $chave => $valorPadrao) {
    $cEscaped = mysqli_real_escape_string($link, $chave);
    $vEscaped = mysqli_real_escape_string($link, $valorPadrao);

    // Insere se não existir
    $sqlInsert = "INSERT IGNORE INTO config_sistema (chave, valor) VALUES ('$cEscaped', '$vEscaped')";
    if (mysqli_query($link, $sqlInsert)) {
        echo "[OK] Configuração '$chave' inicializada (padrão: '$valorPadrao').\n";
    } else {
        echo "[ERRO] Falha ao inserir configuração '$chave': " . mysqli_error($link) . "\n";
    }
}

DBClose($link);
echo "Migração de configurações de PDF concluída com sucesso.\n";
