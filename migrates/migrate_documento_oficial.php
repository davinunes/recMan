<?php
/**
 * Migração: Criar tabela documento_oficial para Pareceres Opinativos, Orientações Técnicas e Normas
 * 
 * Uso via navegador ou CLI: php migrates/migrate_documento_oficial.php
 */
require_once __DIR__ . "/../classes/database.php";

$link = DBConnect();
if (!$link) {
    echo "[ERRO] Falha ao conectar no banco de dados.\n";
    exit(1);
}

// 1. Criar tabela documento_oficial no banco conselho
$sqlTable = "CREATE TABLE IF NOT EXISTS `conselho`.`documento_oficial` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero` INT NOT NULL,
  `ano` INT NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `ementa` TEXT NULL,
  `conteudo` LONGTEXT NOT NULL,
  `modo_editor` VARCHAR(20) DEFAULT 'visual',
  `modo_template` VARCHAR(20) DEFAULT 'global',
  `template_especifico` LONGTEXT NULL,
  `variante_global` VARCHAR(50) DEFAULT 'top_header',
  `relator` VARCHAR(150) NULL,
  `id_usuario` INT NOT NULL,
  `data_emissao` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tipo_ano` (`tipo`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($link, $sqlTable)) {
    echo "[OK] Tabela 'conselho.documento_oficial' verificada/criada com sucesso.\n";
} else {
    // Tenta fallback sem prefixar o schema conselho
    $sqlFallback = str_replace('`conselho`.', '', $sqlTable);
    if (mysqli_query($link, $sqlFallback)) {
        echo "[OK] Tabela 'documento_oficial' criada com sucesso.\n";
    } else {
        echo "[ERRO] Falha ao criar tabela 'documento_oficial': " . mysqli_error($link) . "\n";
    }
}

DBClose($link);
echo "Migração do módulo de Documentos Oficiais concluída com sucesso.\n";
