<?php
/**
 * Script de Otimização de Performance e Criação de Índices do Banco de Dados recMan
 * Executa a criação idempotente de índices em colunas estratégicas do sistema.
 */
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . "/classes/database.php";

$link = DBConnect();
if (!$link) {
    echo "Erro ao conectar com o banco de dados.\n";
    exit;
}

echo "=========================================================\n";
echo "INICIANDO OTIMIZAÇÃO E CRIAÇÃO DE ÍNDICES NO BANCO RECMAN\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "=========================================================\n\n";

/**
 * Função auxiliar para adicionar índice apenas se ele ainda não existir na tabela
 */
function adicionarIndiceSeNaoExistir($link, $tabela, $nomeIndice, $colunasSql) {
    $resCheck = mysqli_query($link, "SHOW INDEX FROM `{$tabela}` WHERE Key_name = '{$nomeIndice}'");
    if ($resCheck && mysqli_num_rows($resCheck) > 0) {
        echo "[-] Índice `{$nomeIndice}` já existe na tabela `{$tabela}`. Ignorado.\n";
    } else {
        $sqlAlter = "ALTER TABLE `{$tabela}` ADD INDEX `{$nomeIndice}` ({$colunasSql})";
        if (@mysqli_query($link, $sqlAlter)) {
            echo "[+] SUCESSO: Criado índice `{$nomeIndice}` na tabela `{$tabela}` ({$colunasSql}).\n";
        } else {
            $err = mysqli_error($link);
            echo "[!] ERRO ao criar índice `{$nomeIndice}` na tabela `{$tabela}`: {$err}\n";
        }
    }
}

// 1. Tabela `ocorrencias`
echo "--- Otimizando Tabela `ocorrencias` ---\n";
adicionarIndiceSeNaoExistir($link, 'ocorrencias', 'idx_ocorrencias_uuid_remoto', '`uuid_remoto`');
adicionarIndiceSeNaoExistir($link, 'ocorrencias', 'idx_ocorrencias_protocolo_vds', '`protocolo_vds`');
adicionarIndiceSeNaoExistir($link, 'ocorrencias', 'idx_ocorrencias_bloco_unidade', '`bloco`, `unidade`');
adicionarIndiceSeNaoExistir($link, 'ocorrencias', 'idx_ocorrencias_resolvido_abertura', '`resolvido`, `abertura` DESC');
adicionarIndiceSeNaoExistir($link, 'ocorrencias', 'idx_ocorrencias_tipo_abertura', '`oco_tipo`, `abertura` DESC');
adicionarIndiceSeNaoExistir($link, 'ocorrencias', 'idx_ocorrencias_resp', '`responsabilidade`');

// 2. Tabela `ocorrencia_leitura_conselheiro`
echo "\n--- Otimizando Tabela `ocorrencia_leitura_conselheiro` ---\n";
adicionarIndiceSeNaoExistir($link, 'ocorrencia_leitura_conselheiro', 'idx_leitura_conselheiro_lido', '`conselheiro_id`, `lido`');
adicionarIndiceSeNaoExistir($link, 'ocorrencia_leitura_conselheiro', 'idx_leitura_sync_remoto', '`conselheiro_id`, `sincronizado_remoto`');

// 3. Tabela `recurso`
echo "\n--- Otimizando Tabela `recurso` ---\n";
adicionarIndiceSeNaoExistir($link, 'recurso', 'idx_recurso_numero', '`numero`');
adicionarIndiceSeNaoExistir($link, 'recurso', 'idx_recurso_bloco_unidade', '`bloco`, `unidade`');
adicionarIndiceSeNaoExistir($link, 'recurso', 'idx_recurso_fase_data', '`fase`, `data` DESC');

// 4. Tabela `notificacoes`
echo "\n--- Otimizando Tabela `notificacoes` ---\n";
adicionarIndiceSeNaoExistir($link, 'notificacoes', 'idx_notificacoes_num_ano', '`numero`, `ano`');
adicionarIndiceSeNaoExistir($link, 'notificacoes', 'idx_notificacoes_torre_unidade', '`torre`, `unidade`');

// 5. Tabela `multas_cobradas`
echo "\n--- Otimizando Tabela `multas_cobradas` ---\n";
adicionarIndiceSeNaoExistir($link, 'multas_cobradas', 'idx_multas_num_ano', '`numero`, `ano`');

// 6. Tabela `parecer`
echo "\n--- Otimizando Tabela `parecer` ---\n";
adicionarIndiceSeNaoExistir($link, 'parecer', 'idx_parecer_id_concluido', '`id`, `concluido`');

// 7. Tabelas de Notas e Comentários de Ocorrências
echo "\n--- Otimizando Tabelas de Mensagens e Notas ---\n";
adicionarIndiceSeNaoExistir($link, 'ocorrencia_notas_internas', 'idx_notas_oco_created', '`ocorrencia_id`, `created_at` ASC');
adicionarIndiceSeNaoExistir($link, 'ocorrencia_comentarios_vds', 'idx_comentarios_oco_dt', '`ocorrencia_id`, `dt_criacao` ASC');

echo "\n=========================================================\n";
echo "OTIMIZAÇÃO CONCLUÍDA COM SUCESSO!\n";
echo "=========================================================\n";

DBClose($link);
