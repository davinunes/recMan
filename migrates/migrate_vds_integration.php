<?php
require_once __DIR__ . "/classes/repositorio.php";

echo "Iniciando migração de banco de dados para integração VDS (API v8)...\n";

$link = DBConnect();

// 1. Evolução da tabela legada `ocorrencias`
$alterOcorrencias = [
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS uuid_remoto VARCHAR(100) DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS protocolo_vds VARCHAR(50) DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS oco_tipo INT DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS dados_json LONGTEXT DEFAULT NULL;",
    "ALTER TABLE ocorrencias ADD INDEX IF NOT EXISTS idx_uuid_remoto (uuid_remoto);",
    "ALTER TABLE ocorrencias ADD INDEX IF NOT EXISTS idx_protocolo_vds (protocolo_vds);"
];

foreach ($alterOcorrencias as $sql) {
    @mysqli_query($link, $sql);
}

// 1.1 Backfill do protocolo_vds para registros legados onde o id local equivalia ao número do protocolo
@mysqli_query($link, "UPDATE ocorrencias SET protocolo_vds = CAST(id AS CHAR) WHERE (protocolo_vds IS NULL OR protocolo_vds = '') AND id > 0;");

// 2. Tabela de Mapeamento/Cache Local de UUIDs e Categorias (vds_uuid_mapping)
$sqlMapping = "CREATE TABLE IF NOT EXISTS vds_uuid_mapping (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entidade_tipo VARCHAR(50) NOT NULL COMMENT 'bloco, unidade, usuario, categoria_ocorrencia, condominio',
    chave_local VARCHAR(100) NOT NULL COMMENT 'Chave local: A:102, conselheiro_1, ocoTipo_115, etc',
    uuid_remoto VARCHAR(100) NOT NULL,
    dados_extras_json LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_entidade_chave (entidade_tipo, chave_local),
    KEY idx_uuid_remoto (uuid_remoto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

mysqli_query($link, $sqlMapping);

// Semeadura inicial dos 10 ocoTipo conhecidos da VDS
$categoriasIniciais = [
    ['114', 'Livro de ocorrência', '/app/images/ocorrencia_n/Anotacao_Sugestao.png', 'Escreva no livro de ocorrências do condomínio.'],
    ['86', 'Fale com o Síndico', '/app/images/ocorrencia_n/Anotacao_Anotacao.png', 'Utilize este canal para falar com o síndico ou administração'],
    ['109', 'Fale com o Síndico de Bloco', '/app/images/ocorrencia_n/Anotacao_Anotacao.png', 'Utilize este canal para falar com o síndico de seu bloco'],
    ['102', 'Fale com a Administração', '/app/images/ocorrencia_n/Anotacao_Anotacao.png', 'Utilize este canal para falar com a área da administração'],
    ['145', 'Fale com a Mensageria', '/app/images/ocorrencia_n/Anotacao_Anotacao.png', 'Utilize este canal para falar com o setor de mensageria'],
    ['87', 'Fale com a portaria', '/app/images/ocorrencia_n/Anotacao_Anotacao.png', 'Deixe um recado para a portaria do condomínio'],
    ['126', 'Fale com a Supervisão', '/app/images/ocorrencia_n/Anotacao_Anotacao.png', 'Utilize este canal para falar com a supervisão'],
    ['115', 'Fale com o Conselho', '/app/images/ocorrencia_n/Anotacao_Sugestao.png', 'Utilize este canal para falar com o conselho'],
    ['247', 'Monitoramento', '/app/images/ocorrencia_n/Anotacao_Anotacao.png', 'Utilize este canal para falar com a equipe de monitoramento'],
    ['172', 'Suporte ao Controle de Acesso', '/app/images/ocorrencia_n/Anotacao_Anotacao.png', 'Reportar dificuldades com controle de acesso de pedestre ou veicular']
];

$stmtCat = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto, dados_extras_json) VALUES ('categoria_ocorrencia', ?, ?, ?) ON DUPLICATE KEY UPDATE dados_extras_json = VALUES(dados_extras_json)");

foreach ($categoriasIniciais as $cat) {
    $chave = "ocoTipo_" . $cat[0];
    $uuid = $cat[0];
    $json = json_encode([
        'ocoTipo' => (int)$cat[0],
        'nome' => $cat[1],
        'icone' => $cat[2],
        'descricao' => $cat[3]
    ], JSON_UNESCAPED_UNICODE);

    mysqli_stmt_bind_param($stmtCat, "sss", $chave, $uuid, $json);
    mysqli_stmt_execute($stmtCat);
}
mysqli_stmt_close($stmtCat);

// 3. Tabela de Gestão de Tokens (vds_tokens - Condomínio & Ultra-Login de Conselheiros)
$sqlTokens = "CREATE TABLE IF NOT EXISTS vds_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('condominio', 'conselheiro') NOT NULL DEFAULT 'conselheiro',
    usuario_id_conselho INT DEFAULT NULL COMMENT 'ID do usuário no conselho',
    vds_username VARCHAR(100) DEFAULT NULL,
    vds_user_uuid VARCHAR(100) DEFAULT NULL,
    bearer_token TEXT NOT NULL,
    refresh_token TEXT DEFAULT NULL,
    status ENUM('ativo', 'expirado', 'erro') DEFAULT 'ativo',
    expires_at DATETIME DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tipo_usuario (tipo, usuario_id_conselho)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

mysqli_query($link, $sqlTokens);
@mysqli_query($link, "ALTER TABLE vds_tokens ADD COLUMN IF NOT EXISTS refresh_token TEXT DEFAULT NULL;");

// 3b. Tabela de Controle Relacional de Leitura por Conselheiro (ocorrencia_leitura_conselheiro)
$sqlLeitura = "CREATE TABLE IF NOT EXISTS ocorrencia_leitura_conselheiro (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conselheiro_id INT NOT NULL,
    ocorrencia_id INT NOT NULL,
    uuid_remoto VARCHAR(100) DEFAULT NULL,
    lido TINYINT(1) DEFAULT 1,
    sincronizado_remoto TINYINT(1) DEFAULT 0,
    read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_conselheiro_ocorrencia (conselheiro_id, ocorrencia_id),
    KEY idx_conselheiro_lido (conselheiro_id, lido),
    KEY idx_sincronizado (sincronizado_remoto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($link, $sqlLeitura);

// 4. Tabela de Notas Internas do Conselho (ocorrencia_notas_internas)
$sqlNotas = "CREATE TABLE IF NOT EXISTS ocorrencia_notas_internas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL COMMENT 'FK ou ID da ocorrencia no conselho',
    protocolo_vds VARCHAR(50) DEFAULT NULL,
    conselheiro_id INT DEFAULT NULL,
    conselheiro_nome VARCHAR(100) DEFAULT NULL,
    texto TEXT NOT NULL,
    anexo_caminho TEXT DEFAULT NULL,
    enviado_remoto TINYINT(1) DEFAULT 0 COMMENT '0=Apenas Interno, 1=Publicado no Remoto',
    data_envio_remoto DATETIME DEFAULT NULL,
    vds_evento_uuid VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ocorrencia (ocorrencia_id),
    KEY idx_protocolo (protocolo_vds)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

mysqli_query($link, $sqlNotas);

// 5. Tabela de Tags de Unidades para Chamados (ocorrencia_unidade_tag)
$sqlTags = "CREATE TABLE IF NOT EXISTS ocorrencia_unidade_tag (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    protocolo_vds VARCHAR(50) DEFAULT NULL,
    bloco VARCHAR(50) NOT NULL,
    unidade VARCHAR(50) NOT NULL,
    tipo_vinculo ENUM('autora', 'citada', 'reclamada') DEFAULT 'citada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bloco_unidade (bloco, unidade),
    KEY idx_ocorrencia (ocorrencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

mysqli_query($link, $sqlTags);

// 6. Tabela de Vínculos entre Ocorrência e Recursos/Notificações (ocorrencia_recurso_link)
$sqlLinks = "CREATE TABLE IF NOT EXISTS ocorrencia_recurso_link (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    protocolo_vds VARCHAR(50) DEFAULT NULL,
    numero_recurso VARCHAR(100) DEFAULT NULL COMMENT 'Ex: 01.23 ou 14/2026',
    notificacao_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_recurso (numero_recurso),
    KEY idx_ocorrencia (ocorrencia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

mysqli_query($link, $sqlLinks);

DBClose($link);
echo "Migração do banco de dados concluída com sucesso!\n";
