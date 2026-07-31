<?php
require_once __DIR__ . "/classes/repositorio.php";

echo "Iniciando atualizacao da foreign key...\n";

// DROP existing foreign key constraint
$sqlDrop = "ALTER TABLE conselho.recurso_anexos DROP FOREIGN KEY fk_anexos_recurso;";
if(DBExecute($sqlDrop)) {
    echo "Constraint fk_anexos_recurso removida com sucesso.\n";
} else {
    echo "Erro ao remover fk_anexos_recurso ou ela nao existe.\n";
}

// ADD foreign key with ON UPDATE CASCADE
$sqlAdd = "ALTER TABLE conselho.recurso_anexos ADD CONSTRAINT fk_anexos_recurso FOREIGN KEY (numero_recurso) REFERENCES conselho.recurso(numero) ON DELETE CASCADE ON UPDATE CASCADE;";
if(DBExecute($sqlAdd)) {
    echo "Constraint fk_anexos_recurso recriada com ON UPDATE CASCADE.\n";
} else {
    echo "Erro ao recriar fk_anexos_recurso.\n";
}

?>
