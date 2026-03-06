<?php
require_once __DIR__ . "/classes/repositorio.php";

DBExecute("ALTER TABLE recurso ADD COLUMN token VARCHAR(50) DEFAULT NULL;");

$sqlAnexos = "CREATE TABLE IF NOT EXISTS recurso_anexos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_recurso VARCHAR(100) NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo TEXT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY fk_anexos (numero_recurso),
    CONSTRAINT fk_anexos_recurso FOREIGN KEY (numero_recurso) REFERENCES recurso (numero) ON DELETE CASCADE
)";
DBExecute($sqlAnexos);

echo "Migration done.";
