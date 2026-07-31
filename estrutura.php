<?php
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . "/classes/database.php";

$link = DBConnect();
if (!$link) {
    echo "-- Erro de conexão com o banco de dados.";
    exit;
}

echo "-- ========================================================\n";
echo "-- DUMP DA ESTRUTURA DO BANCO DE DADOS RECMAN (SEM DADOS)\n";
echo "-- Gerado em: " . date('Y-m-d H:i:s') . "\n";
echo "-- ========================================================\n\n";

$resTables = mysqli_query($link, "SHOW TABLES");
if ($resTables) {
    while ($row = mysqli_fetch_row($resTables)) {
        $tableName = $row[0];
        echo "-- --------------------------------------------------------\n";
        echo "-- Estrutura da tabela `{$tableName}`\n";
        echo "-- --------------------------------------------------------\n";
        echo "DROP TABLE IF EXISTS `{$tableName}`;\n";

        $resCreate = mysqli_query($link, "SHOW CREATE TABLE `{$tableName}`");
        if ($resCreate && $rowCreate = mysqli_fetch_assoc($resCreate)) {
            $createTableSql = $rowCreate['Create Table'] ?? ($rowCreate['Create View'] ?? '');
            echo $createTableSql . ";\n\n";
        }
    }
}

DBClose($link);
