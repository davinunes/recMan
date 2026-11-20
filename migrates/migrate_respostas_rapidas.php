<?php
/**
 * Migração: Criação da tabela de Respostas Rápidas para o Livro de Ocorrências
 * 
 * Permite cadastrar respostas padronizadas compartilhadas com todos os conselheiros,
 * com suporte a interpolação de variáveis dinâmicas ({unidade}, {bloco}, {morador}, {protocolo}, {data}).
 *
 * Execução: php migrates/migrate_respostas_rapidas.php
 */
require_once __DIR__ . "/../classes/repositorio.php";

echo "Iniciando migração da tabela ocorrencias_respostas_rapidas...\n";

$link = DBConnect();
if (!$link) {
    echo "Erro ao conectar no banco.\n";
    exit(1);
}

// 1. Criação da tabela
$sqlTabela = "CREATE TABLE IF NOT EXISTS ocorrencias_respostas_rapidas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    categoria VARCHAR(80) NOT NULL DEFAULT 'Geral',
    texto TEXT NOT NULL,
    conselheiro_id INT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ativo (ativo),
    KEY idx_categoria (categoria),
    KEY idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($link, $sqlTabela)) {
    echo "[OK] Tabela ocorrencias_respostas_rapidas verificada/criada com sucesso.\n";
} else {
    echo "[ERRO] Falha ao criar tabela ocorrencias_respostas_rapidas: " . mysqli_error($link) . "\n";
}

// 2. Sementes padrão (se a tabela estiver vazia)
$checkVazio = mysqli_query($link, "SELECT COUNT(*) as total FROM ocorrencias_respostas_rapidas");
$rowVazio = mysqli_fetch_assoc($checkVazio);

if ($rowVazio && (int)$rowVazio['total'] === 0) {
    echo "Inserindo modelos padrão de respostas rápidas...\n";

    $modelos = [
        [
            'titulo' => 'Barulho fora do horário regulamentar',
            'categoria' => 'Convivência',
            'ordem' => 10,
            'texto' => "Prezado(a) morador(a) da unidade {bloco}-{unidade},\n\nRecebemos o registro referente ao protocolo {protocolo}. Esclarecemos que o regimento interno do condomínio estabelece silêncio e respeito à convivência, especialmente fora dos horários permitidos. O chamado foi registrado e as providências cabíveis de orientação e monitoramento estão sendo adotadas pela administração."
        ],
        [
            'titulo' => 'Solicitação de fotos/evidências complementares',
            'categoria' => 'Esclarecimento',
            'ordem' => 20,
            'texto' => "Prezado(a) condômino(a),\n\nPara que o Conselho e a Administração possam apurar adequadamente o ocorrido registrado no protocolo {protocolo}, solicitamos por gentileza o envio de mais informações e, se houver, fotos, vídeos ou identificação de data e horário exatos dos fatos narrados.\n\nAguardamos seu retorno para darmos prosseguimento."
        ],
        [
            'titulo' => 'Regras de Mudança e Cargas',
            'categoria' => 'Operacional',
            'ordem' => 30,
            'texto' => "Prezado(a) morador(a) da unidade {bloco}-{unidade},\n\nLembramos que todas as mudanças e transportes de grandes volumes devem ser previamente agendados junto à administração/portaria, respeitando os horários permitidos e a reserva do elevador de serviço devidamente protegido.\n\nQualquer dúvida, estamos à disposição."
        ],
        [
            'titulo' => 'Encaminhado ao Síndico / Administração',
            'categoria' => 'Encaminhamento',
            'ordem' => 40,
            'texto' => "Olá, agradecemos pelo seu contato.\n\nInformamos que a sua solicitação registrada no protocolo {protocolo} foi formalmente encaminhada ao Síndico e à equipe da Administração do condomínio para análise técnica e execução das providências necessárias."
        ],
        [
            'titulo' => 'Descarte de Lixo e Entulho',
            'categoria' => 'Áreas Comuns',
            'ordem' => 50,
            'texto' => "Prezado(a) morador(a),\n\nOrientamos que o descarte de resíduos domésticos e recicláveis deve ser realizado exclusivamente nas lixeiras designadas e em sacos bem vedados. É expressamente vedado o descarte de entulhos de obras ou móveis descartados nas áreas comuns sem autorização prévia da administração."
        ],
        [
            'titulo' => 'Ocorrência Finalizada / Providências Adotadas',
            'categoria' => 'Encerramento',
            'ordem' => 60,
            'texto' => "Prezado(a) morador(a),\n\nInformamos que as verificações pertinentes ao protocolo {protocolo} foram concluídas e as medidas cabíveis foram adotadas pela gestão condominial. Agradecemos pela sua colaboração na manutenção da ordem e boa convivência em nosso condomínio."
        ]
    ];

    $stmt = mysqli_prepare($link, "INSERT INTO ocorrencias_respostas_rapidas (titulo, categoria, texto, ordem, ativo) VALUES (?, ?, ?, ?, 1)");
    foreach ($modelos as $m) {
        mysqli_stmt_bind_param($stmt, "sssi", $m['titulo'], $m['categoria'], $m['texto'], $m['ordem']);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
    echo "[OK] " . count($modelos) . " modelos iniciais inseridos com sucesso!\n";
} else {
    echo "[OK] Tabela já continha registros. Nenhuma semente sobrescrita.\n";
}

DBClose($link);
echo "Migração de respostas rápidas concluída com sucesso.\n";
