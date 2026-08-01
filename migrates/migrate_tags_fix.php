<?php
/**
 * Migração: Correção das Tags de Ocorrências (ocorrencia_unidade_tag)
 *
 * O ENUM `tipo_vinculo` original só aceitava ('autora','citada','reclamada'),
 * mas o código de tags livres insere ('unidade','notificacao','recurso','tag').
 * Em MySQL com modo estrito o INSERT falhava e nenhuma tag era criada.
 *
 * Uso: php migrates/migrate_tags_fix.php
 */
require_once __DIR__ . "/../classes/database.php";

$link = DBConnect();
if (!$link) {
    echo "Erro ao conectar no banco.\n";
    exit(1);
}

// 1. Ampliar o ENUM de tipo_vinculo mantendo os valores legados
$sql = "ALTER TABLE ocorrencia_unidade_tag
        MODIFY COLUMN tipo_vinculo ENUM('autora','citada','reclamada','unidade','notificacao','recurso','tag')
        DEFAULT 'citada'";
if (mysqli_query($link, $sql)) {
    echo "[OK] tipo_vinculo ampliado.\n";
} else {
    echo "[ERRO] Falha ao alterar tipo_vinculo: " . mysqli_error($link) . "\n";
}

DBClose($link);
echo "Migração de tags concluída.\n";
