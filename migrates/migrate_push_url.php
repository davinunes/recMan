<?php
require_once "classes/repositorio.php";

$sql = "ALTER TABLE push_subscriptions ADD COLUMN base_url VARCHAR(255) DEFAULT NULL";
if (DBExecute($sql)) {
    echo "Tabela push_subscriptions atualizada com sucesso!\n";
} else {
    echo "Erro ao atualizar tabela (ou coluna já existe).\n";
}
