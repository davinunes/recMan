<?php

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/vds_auth_service.php";
require_once __DIR__ . "/vds_ocorrencia_service.php";

/**
 * Constantes de Tempo da Auditoria Regimental (Art. 12 do Regimento Interno)
 */
define('VDS_AUDITORIA_SEGUNDOS_DIA', 86400);
define('VDS_AUDITORIA_PRAZO_REGIMENTAL_DIAS', 7);
define('VDS_AUDITORIA_PRAZO_REGIMENTAL_SEGUNDOS', 604800); // 7 * 86400

/**
 * Classifica a natureza institucional de um participante com base nos dados reais da API VDS.
 *
 * @param string|null $cargo
 * @param string|null $por Nome da pessoa
 * @param string|null $fotoUrl Caminho ou URL da foto
 * @return array
 */
function vds_auditoria_classificar_participante($cargo, $por = '', $fotoUrl = '') {
    $cargoNorm = mb_strtolower(trim((string)$cargo), 'UTF-8');
    $porNorm = mb_strtolower(trim((string)$por), 'UTF-8');
    $fotoNorm = mb_strtolower(trim((string)$fotoUrl), 'UTF-8');

    // 1. Conselho Consultivo e Fiscal (ÓRGÃO FISCALIZADOR - NUNCA É RESPOSTA DA ADMINISTRAÇÃO)
    if (
        strpos($cargoNorm, 'conselh') !== false ||
        strpos($cargoNorm, 'fiscal') !== false ||
        strpos($porNorm, 'conselho') !== false
    ) {
        return [
            'ator' => 'conselho',
            'rotulo' => 'Conselho Consultivo',
            'is_gestao' => false,
            'cor' => '#6f42c1',
            'bg' => '#f3e8ff'
        ];
    }

    // 2. Subsíndico ou Síndico de Bloco
    if (
        strpos($cargoNorm, 'subsíndic') !== false ||
        strpos($cargoNorm, 'subsindic') !== false ||
        strpos($cargoNorm, 'síndico de bloco') !== false ||
        strpos($cargoNorm, 'sindico de bloco') !== false
    ) {
        return [
            'ator' => 'subsindico',
            'rotulo' => 'Subsíndico',
            'is_gestao' => true,
            'cor' => '#b02a37',
            'bg' => '#f8d7da'
        ];
    }

    // 3. Síndico Geral
    if (
        (strpos($cargoNorm, 'síndic') !== false || strpos($cargoNorm, 'sindic') !== false || strpos($fotoNorm, '/sindico/') !== false) &&
        strpos($cargoNorm, 'sub') === false
    ) {
        return [
            'ator' => 'sindico',
            'rotulo' => 'Síndico Geral',
            'is_gestao' => true,
            'cor' => '#dc3545',
            'bg' => '#ffebee'
        ];
    }

    // 4. Administração / Condomínio Gestão
    if (
        strpos($cargoNorm, 'administra') !== false ||
        strpos($cargoNorm, 'condomínio - gestão') !== false ||
        strpos($cargoNorm, 'condominio - gestao') !== false ||
        strpos($cargoNorm, 'gestão') !== false ||
        strpos($cargoNorm, 'gestao') !== false ||
        strpos($cargoNorm, 'gerên') !== false ||
        strpos($cargoNorm, 'geren') !== false
    ) {
        return [
            'ator' => 'administracao',
            'rotulo' => 'Administração',
            'is_gestao' => true,
            'cor' => '#0d6efd',
            'bg' => '#e7f1ff'
        ];
    }

    // 5. Departamentos Especializados (Mensageria, Portaria, Supervisão, Monitoramento)
    if (
        strpos($cargoNorm, 'mensageria') !== false ||
        strpos($cargoNorm, 'supervis') !== false ||
        strpos($cargoNorm, 'portaria') !== false ||
        strpos($cargoNorm, 'monitoramento') !== false ||
        strpos($cargoNorm, 'segurança') !== false ||
        strpos($cargoNorm, 'seguranca') !== false
    ) {
        return [
            'ator' => 'departamento',
            'rotulo' => 'Departamento',
            'is_gestao' => true,
            'cor' => '#0dcaf0',
            'bg' => '#cff4fc'
        ];
    }

    // 6. Funcionários e Prestadores Operacionais (Zeladoria, Manutenção)
    if (
        strpos($cargoNorm, 'zelador') !== false ||
        strpos($cargoNorm, 'manuten') !== false ||
        strpos($cargoNorm, 'porteiro') !== false ||
        strpos($cargoNorm, 'auxiliar') !== false ||
        strpos($cargoNorm, 'funcionário') !== false ||
        strpos($cargoNorm, 'funcionario') !== false
    ) {
        return [
            'ator' => 'funcionario',
            'rotulo' => 'Funcionário/Colaborador',
            'is_gestao' => true,
            'cor' => '#198754',
            'bg' => '#d1e7dd'
        ];
    }

    // 7. Morador / Solicitante (padrão Bloco X - Y ou cargo Morador)
    if (
        strpos($cargoNorm, 'morador') !== false ||
        preg_match('/bl(?:oco|\.)\s*[a-z0-9]+\s*-\s*\d+/i', $cargoNorm) ||
        preg_match('/^\s*[a-z0-9]+\s*-\s*\d+\s*$/i', $cargoNorm)
    ) {
        return [
            'ator' => 'morador',
            'rotulo' => 'Morador',
            'is_gestao' => false,
            'cor' => '#495057',
            'bg' => '#f8f9fa'
        ];
    }

    // 8. Outros / Não Identificados
    return [
        'ator' => 'outros',
        'rotulo' => !empty($cargo) ? htmlspecialchars($cargo) : 'Não Identificado',
        'is_gestao' => false,
        'cor' => '#6c757d',
        'bg' => '#e9ecef'
    ];
}

/**
 * Normaliza qualquer data/hora em timestamp UNIX seguro.
 */
function vds_auditoria_parse_timestamp($dtVal) {
    if (empty($dtVal)) return null;
    if (is_numeric($dtVal)) return (int)$dtVal;
    
    $clean = trim((string)$dtVal);
    // Formato BR: DD/MM/YYYY HH:MM:SS
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?/', $clean, $m)) {
        $h = $m[4] ?? '00';
        $i = $m[5] ?? '00';
        $s = $m[6] ?? '00';
        return strtotime("{$m[3]}-{$m[2]}-{$m[1]} {$h}:{$i}:{$s}");
    }
    // Formato ISO: YYYY-MM-DDTHH:MM:SS ou YYYY-MM-DD HH:MM:SS
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $clean)) {
        return strtotime($clean);
    }
    return null;
}

/**
 * Motor Fático: Analisa uma ocorrência e seu histórico de eventos segundo as 4 dimensões ortogonais.
 *
 * @param array $local Registro da tabela `ocorrencias`
 * @param array $eventosRemotos Array de eventos extraídos de `dados_json` ou GET /ocorrencia/{uuid}
 * @param string|null $dataReferenciaStr Data de corte (formato Y-m-d)
 * @return array
 */
function vds_auditoria_analisar_chamado($local, $eventosRemotos = [], $dataReferenciaStr = null) {
    $now = time();
    $dataRefStr = !empty($dataReferenciaStr) ? $dataReferenciaStr : date('Y-m-d');
    // Considerar o término do dia de referência às 23:59:59 para apurações fidedignas
    $tsReferencia = strtotime($dataRefStr . ' 23:59:59') ?: $now;

    // 1. Timestamp de Abertura
    $tsAbertura = null;
    if (!empty($local['abertura'])) {
        $tsAbertura = vds_auditoria_parse_timestamp($local['abertura']);
    }

    $dadosJson = !empty($local['dados_json']) ? (is_array($local['dados_json']) ? $local['dados_json'] : json_decode($local['dados_json'], true)) : [];

    if (!$tsAbertura && !empty($dadosJson['abertura'])) {
        $tsAbertura = vds_auditoria_parse_timestamp($dadosJson['abertura']);
    }
    if (!$tsAbertura && !empty($dadosJson['dtExibicao'])) {
        $tsAbertura = vds_auditoria_parse_timestamp($dadosJson['dtExibicao']);
    }
    if (!$tsAbertura) {
        $tsAbertura = $tsReferencia;
    }

    // 2. Extração e Ordenação Cronológica de Eventos
    $eventosOrdenados = [];
    if (!empty($eventosRemotos) && is_array($eventosRemotos)) {
        $eventosOrdenados = $eventosRemotos;
    } elseif (!empty($dadosJson['eventos']) && is_array($dadosJson['eventos'])) {
        $eventosOrdenados = $dadosJson['eventos'];
    } elseif (!empty($dadosJson['regs']) && is_array($dadosJson['regs'])) {
        $eventosOrdenados = $dadosJson['regs'];
    }

    // Ordenar eventos por timestamp crescente
    usort($eventosOrdenados, function($a, $b) {
        $dtA = vds_auditoria_parse_timestamp($a['dtHora'] ?? ($a['dthora'] ?? ($a['data'] ?? '')));
        $dtB = vds_auditoria_parse_timestamp($b['dtHora'] ?? ($b['dthora'] ?? ($b['data'] ?? '')));
        return ($dtA <=> $dtB);
    });

    // 3. Detecção da Primeira Manifestação Administrativa e Primeira Providência Objetiva
    $primeiraResposta = null;
    $primeiraProvidencia = null;
    $totalInteracoesGestao = 0;
    $totalInteracoesMorador = 0;
    $totalInteracoesConselho = 0;

    foreach ($eventosOrdenados as $ev) {
        $cargoEv = $ev['cargo'] ?? '';
        $porEv = $ev['por'] ?? ($ev['autor']['nome'] ?? '');
        $fotoEv = $ev['foto'] ?? ($ev['fotoUrl'] ?? '');
        $tipoEv = (int)($ev['tipo'] ?? 0);
        $msgEv = trim((string)($ev['mensagem'] ?? ''));
        $dtEvStr = $ev['dtHora'] ?? ($ev['dthora'] ?? ($ev['data'] ?? null));
        $tsEv = vds_auditoria_parse_timestamp($dtEvStr);

        if (!$tsEv) continue;

        $classificacao = vds_auditoria_classificar_participante($cargoEv, $porEv, $fotoEv);

        if ($classificacao['ator'] === 'conselho') {
            $totalInteracoesConselho++;
            // Conselho NÃO computa como manifestação da gestão
            continue;
        }

        if ($classificacao['is_gestao']) {
            $totalInteracoesGestao++;

            // Capturar Primeira Manifestação da Gestão
            if ($primeiraResposta === null) {
                $primeiraResposta = [
                    'ts' => $tsEv,
                    'data_formatada' => date('d/m/Y H:i:s', $tsEv),
                    'por' => $porEv,
                    'cargo' => $cargoEv,
                    'ator' => $classificacao['ator'],
                    'ator_rotulo' => $classificacao['rotulo'],
                    'mensagem' => $msgEv,
                    'resumo' => mb_substr(strip_tags($msgEv), 0, 250, 'UTF-8')
                ];
            }

            // Capturar Primeira Providência Objetiva (Atos procedimentais formais na API)
            // Na API VDS:
            // - tipo = 79: Ato formal de encaminhamento a funcionário/grupo
            // - Menciona formalmente despacho/OS gerada ou destinoId
            $isAtoFormalProvidencia = false;
            $motivoProvidencia = null;

            if ($tipoEv === 79 || !empty($ev['destinoId']) || !empty($ev['destinoUuid']) || stripos($msgEv, 'encaminhado para') !== false) {
                $isAtoFormalProvidencia = true;
                $motivoProvidencia = 'encaminhamento_formal_api';
            } elseif (stripos($msgEv, 'ordem de serviço') !== false || stripos($msgEv, 'ordem de servico') !== false || stripos($msgEv, 'os n') !== false || stripos($msgEv, 'os nº') !== false) {
                $isAtoFormalProvidencia = true;
                $motivoProvidencia = 'ordem_servico_formal';
            }

            if ($isAtoFormalProvidencia && $primeiraProvidencia === null) {
                $primeiraProvidencia = [
                    'ts' => $tsEv,
                    'data_formatada' => date('d/m/Y H:i:s', $tsEv),
                    'por' => $porEv,
                    'cargo' => $cargoEv,
                    'ator' => $classificacao['ator'],
                    'tipo_comprovacao' => $motivoProvidencia,
                    'descricao' => mb_substr(strip_tags($msgEv), 0, 300, 'UTF-8')
                ];
            }
        } elseif ($classificacao['ator'] === 'morador') {
            $totalInteracoesMorador++;
        }
    }

    // 4. Dimensão 3: Situação do Chamado (Ciclo de Vida Formal)
    $statusStr = mb_strtolower(trim((string)($local['status'] ?? '')), 'UTF-8');
    $isResolvidoBanco = !empty($local['resolvido']);
    $isStatusFechado = in_array($statusStr, ['resolvido', 'resolvida', 'concluido', 'concluida', 'fechado', 'fechada', 'finalizado', 'finalizada']);

    $situacaoChamado = 'aberto';
    $dataEncerramentoStr = null;
    $tsEncerramento = null;

    if ($isResolvidoBanco || $isStatusFechado) {
        $situacaoChamado = 'encerrado';
        // Determinar timestamp de encerramento
        if (!empty($local['data_ultima_mensagem'])) {
            $tsEncerramento = vds_auditoria_parse_timestamp($local['data_ultima_mensagem']);
        } elseif (!empty($local['ultimaAtualizacao'])) {
            $tsEncerramento = vds_auditoria_parse_timestamp($local['ultimaAtualizacao']);
        } else {
            $tsEncerramento = $tsReferencia;
        }
        $dataEncerramentoStr = date('Y-m-d H:i:s', $tsEncerramento);
    } elseif (in_array($statusStr, ['em andamento', 'aguardando material', 'em_andamento'])) {
        $situacaoChamado = 'em_andamento';
    } elseif (in_array($statusStr, ['aberto', 'aberta', 'não classificado', 'nao classificado', ''])) {
        $situacaoChamado = 'aberto';
    } else {
        $situacaoChamado = 'nao_identificado';
    }

    // 5. Dimensão 1: Tempo Transcorrido (Abertura até Referência ou Encerramento)
    $tsTerminoCalculo = ($situacaoChamado === 'encerrado' && $tsEncerramento) ? $tsEncerramento : $tsReferencia;
    $deltaSegundosTotal = max(0, $tsTerminoCalculo - $tsAbertura);
    $diasTranscorridos = (int)floor($deltaSegundosTotal / VDS_AUDITORIA_SEGUNDOS_DIA);

    $faixaTempo = 'ate_7_dias';
    if ($diasTranscorridos > 365) {
        $faixaTempo = 'mais_365_dias';
    } elseif ($diasTranscorridos >= 91) {
        $faixaTempo = '91_a_365_dias';
    } elseif ($diasTranscorridos >= 31) {
        $faixaTempo = '31_a_90_dias';
    } elseif ($diasTranscorridos >= 8) {
        $faixaTempo = '8_a_30_dias';
    } else {
        $faixaTempo = 'ate_7_dias';
    }

    // 6. Dimensão 2: Situação da Resposta (Art. 12 do Regimento Interno - 7 Dias Exatos)
    $situacaoResposta = 'dados_insuficientes';
    $tempoPrimeiraRespostaDias = null;

    if ($primeiraResposta !== null) {
        $deltaSegundosResp = max(0, $primeiraResposta['ts'] - $tsAbertura);
        $tempoPrimeiraRespostaDias = (int)floor($deltaSegundosResp / VDS_AUDITORIA_SEGUNDOS_DIA);

        if ($deltaSegundosResp <= VDS_AUDITORIA_PRAZO_REGIMENTAL_SEGUNDOS) {
            $situacaoResposta = 'respondido_no_prazo';
        } else {
            $situacaoResposta = 'respondido_com_atraso';
        }
    } else {
        // Sem resposta da gestão registrada
        $deltaSegundosAteRef = max(0, $tsReferencia - $tsAbertura);
        if ($deltaSegundosAteRef <= VDS_AUDITORIA_PRAZO_REGIMENTAL_SEGUNDOS) {
            // Aberto há menos de 7 dias completos: NÃO é atraso! Está no prazo regulamentar.
            $situacaoResposta = 'em_prazo_aguardando';
        } else {
            // Ultrapassou o prazo de 7 dias sem resposta registrada
            $situacaoResposta = 'sem_resposta';
        }
    }

    // 7. Dimensão 4: Situação das Providências
    $situacaoProvidencias = 'nao_determinado';
    $tempoPrimeiraProvidenciaDias = null;

    if ($primeiraProvidencia !== null) {
        $deltaSegundosProv = max(0, $primeiraProvidencia['ts'] - $tsAbertura);
        $tempoPrimeiraProvidenciaDias = (int)floor($deltaSegundosProv / VDS_AUDITORIA_SEGUNDOS_DIA);
        $situacaoProvidencias = 'providencia_identificavel';
    } elseif ($primeiraResposta !== null) {
        // Teve manifestação textual mas nenhum ato fático ou encaminhamento formal
        $situacaoProvidencias = 'apenas_manifestacao';
    } else {
        $situacaoProvidencias = 'nenhuma_providencia';
    }

    // 8. Tempo até Encerramento (se encerrado)
    $tempoAteEncerramentoDias = null;
    if ($situacaoChamado === 'encerrado' && $tsEncerramento) {
        $tempoAteEncerramentoDias = max(0, (int)floor(($tsEncerramento - $tsAbertura) / VDS_AUDITORIA_SEGUNDOS_DIA));
    }

    return [
        'ocorrencia_id' => (int)($local['id'] ?? 0),
        'protocolo_vds' => (string)($local['protocolo_vds'] ?? ($local['id'] ?? '')),
        'uuid_remoto' => $local['uuid_remoto'] ?? null,
        'bloco' => $local['bloco'] ?? null,
        'unidade' => $local['unidade'] ?? null,
        'categoria_oco_tipo' => (int)($local['oco_tipo'] ?? 0),
        'data_abertura_iso' => date('Y-m-d H:i:s', $tsAbertura),
        'data_abertura_formatada' => date('d/m/Y H:i', $tsAbertura),
        'data_referencia' => $dataRefStr,
        
        // Dimensão 1: Tempo Transcorrido
        'tempo_decorrido_dias' => $diasTranscorridos,
        'faixa_tempo' => $faixaTempo,
        
        // Dimensão 2: Situação da Resposta
        'situacao_resposta' => $situacaoResposta,
        'primeira_resposta_data' => $primeiraResposta ? date('Y-m-d H:i:s', $primeiraResposta['ts']) : null,
        'primeira_resposta_data_formatada' => $primeiraResposta ? $primeiraResposta['data_formatada'] : null,
        'primeira_resposta_tempo_dias' => $tempoPrimeiraRespostaDias,
        'primeira_resposta_por' => $primeiraResposta ? $primeiraResposta['por'] : null,
        'primeira_resposta_cargo_original' => $primeiraResposta ? $primeiraResposta['cargo'] : null,
        'primeira_resposta_ator' => $primeiraResposta ? $primeiraResposta['ator'] : null,
        'primeira_resposta_resumo' => $primeiraResposta ? $primeiraResposta['resumo'] : null,
        
        // Dimensão 3: Situação do Chamado
        'situacao_chamado' => $situacaoChamado,
        'data_encerramento' => $dataEncerramentoStr,
        'tempo_ate_encerramento_dias' => $tempoAteEncerramentoDias,
        
        // Dimensão 4: Situação das Providências
        'situacao_providencias' => $situacaoProvidencias,
        'primeira_providencia_data' => $primeiraProvidencia ? date('Y-m-d H:i:s', $primeiraProvidencia['ts']) : null,
        'primeira_providencia_tempo_dias' => $tempoPrimeiraProvidenciaDias,
        'primeira_providencia_descricao' => $primeiraProvidencia ? $primeiraProvidencia['descricao'] : null,
        'tipo_comprovacao_providencia' => $primeiraProvidencia ? $primeiraProvidencia['tipo_comprovacao'] : null,

        // Contadores Auxiliares
        'total_interacoes_gestao' => $totalInteracoesGestao,
        'total_interacoes_morador' => $totalInteracoesMorador,
        'total_interacoes_conselho' => $totalInteracoesConselho,
        'total_eventos' => count($eventosOrdenados),
        
        // Snapshot Bruto de Evidência
        'eventos_brutos' => $eventosOrdenados
    ];
}

/**
 * Calcula consolidados estatísticos e indicadores de tempo para um conjunto de itens auditados.
 *
 * @param array $itens Lista de resultados de `vds_auditoria_analisar_chamado`
 * @return array
 */
function vds_auditoria_calcular_metricas($itens) {
    $total = count($itens);
    if ($total === 0) {
        return [
            'total_chamados' => 0,
            'respondidos_no_prazo' => 0,
            'respondidos_com_atraso' => 0,
            'sem_resposta_extrapolada' => 0,
            'em_prazo_aguardando' => 0,
            'abertos' => 0,
            'em_andamento' => 0,
            'encerrados' => 0,
            'com_providencia' => 0,
            'apenas_manifestacao' => 0,
            'nenhuma_providencia' => 0,
            'pct_respondidos_no_prazo' => 0,
            'pct_sem_resposta_extrapolada' => 0,
            'tempo_medio_resposta' => 0,
            'tempo_mediano_resposta' => 0,
            'tempo_medio_providencia' => 0,
            'tempo_medio_encerramento' => 0,
            'maior_tempo_sem_resposta' => 0,
            'distribuicao_faixas' => [],
            'distribuicao_blocos' => [],
            'distribuicao_categorias' => []
        ];
    }

    $cRespNoPrazo = 0;
    $cRespComAtraso = 0;
    $cSemRespExtrapolada = 0;
    $cEmPrazoAguardando = 0;
    $cDadosInsuficientes = 0;

    $cAbertos = 0;
    $cEmAndamento = 0;
    $cEncerrados = 0;

    $cComProvidencia = 0;
    $cApenasManifestacao = 0;
    $cNenhumaProvidencia = 0;

    $temposResposta = [];
    $temposProvidencia = [];
    $temposEncerramento = [];
    $maiorTempoSemResposta = 0;

    $distFaixas = [
        'ate_7_dias' => 0,
        '8_a_30_dias' => 0,
        '31_a_90_dias' => 0,
        '91_a_365_dias' => 0,
        'mais_365_dias' => 0
    ];

    $distBlocos = [];
    $distCategorias = [];

    foreach ($itens as $it) {
        // Situação da Resposta
        switch ($it['situacao_resposta']) {
            case 'respondido_no_prazo': $cRespNoPrazo++; break;
            case 'respondido_com_atraso': $cRespComAtraso++; break;
            case 'sem_resposta':
                $cSemRespExtrapolada++;
                if ($it['tempo_decorrido_dias'] > $maiorTempoSemResposta) {
                    $maiorTempoSemResposta = $it['tempo_decorrido_dias'];
                }
                break;
            case 'em_prazo_aguardando': $cEmPrazoAguardando++; break;
            default: $cDadosInsuficientes++; break;
        }

        // Situação do Chamado
        switch ($it['situacao_chamado']) {
            case 'aberto': $cAbertos++; break;
            case 'em_andamento': $cEmAndamento++; break;
            case 'encerrado': $cEncerrados++; break;
        }

        // Situação das Providências
        switch ($it['situacao_providencias']) {
            case 'providencia_identificavel': $cComProvidencia++; break;
            case 'apenas_manifestacao': $cApenasManifestacao++; break;
            case 'nenhuma_providencia': $cNenhumaProvidencia++; break;
        }

        // Faixa de Tempo
        $faixa = $it['faixa_tempo'];
        if (isset($distFaixas[$faixa])) {
            $distFaixas[$faixa]++;
        }

        // Tempos
        if ($it['primeira_resposta_tempo_dias'] !== null) {
            $temposResposta[] = (int)$it['primeira_resposta_tempo_dias'];
        }
        if ($it['primeira_providencia_tempo_dias'] !== null) {
            $temposProvidencia[] = (int)$it['primeira_providencia_tempo_dias'];
        }
        if ($it['tempo_ate_encerramento_dias'] !== null) {
            $temposEncerramento[] = (int)$it['tempo_ate_encerramento_dias'];
        }

        // Bloco
        $bloco = !empty($it['bloco']) && $it['bloco'] !== 'Z' ? $it['bloco'] : 'Não Definido';
        $distBlocos[$bloco] = ($distBlocos[$bloco] ?? 0) + 1;

        // Categoria
        $cat = (int)($it['categoria_oco_tipo'] ?? 0);
        $distCategorias[$cat] = ($distCategorias[$cat] ?? 0) + 1;
    }

    // Cálculos de Média e Mediana
    $calcMedia = function($arr) {
        $c = count($arr);
        return $c > 0 ? round(array_sum($arr) / $c, 1) : 0;
    };
    $calcMediana = function($arr) {
        $c = count($arr);
        if ($c === 0) return 0;
        sort($arr);
        $meio = (int)floor($c / 2);
        if ($c % 2) {
            return $arr[$meio];
        }
        return round(($arr[$meio - 1] + $arr[$meio]) / 2, 1);
    };

    // Denominador estritamente válido para % de resposta
    // Exclui apenas registros abertos recentemente que ainda estão aguardando o transcurso legal dos 7 dias
    $denominadorAuditavel = $total - $cEmPrazoAguardando;
    $pctRespondidosNoPrazo = $denominadorAuditavel > 0 ? round(($cRespNoPrazo / $denominadorAuditavel) * 100, 1) : 0;
    $pctSemRespExtrapolada = $denominadorAuditavel > 0 ? round(($cSemRespExtrapolada / $denominadorAuditavel) * 100, 1) : 0;

    ksort($distBlocos);
    arsort($distCategorias);

    return [
        'total_chamados' => $total,
        'denominador_auditavel' => $denominadorAuditavel,
        'respondidos_no_prazo' => $cRespNoPrazo,
        'respondidos_com_atraso' => $cRespComAtraso,
        'sem_resposta_extrapolada' => $cSemRespExtrapolada,
        'em_prazo_aguardando' => $cEmPrazoAguardando,
        'dados_insuficientes' => $cDadosInsuficientes,
        'pct_respondidos_no_prazo' => $pctRespondidosNoPrazo,
        'pct_sem_resposta_extrapolada' => $pctSemRespExtrapolada,
        
        'abertos' => $cAbertos,
        'em_andamento' => $cEmAndamento,
        'encerrados' => $cEncerrados,
        'pendentes_total' => $cAbertos + $cEmAndamento,
        
        'com_providencia' => $cComProvidencia,
        'apenas_manifestacao' => $cApenasManifestacao,
        'nenhuma_providencia' => $cNenhumaProvidencia,
        
        'tempo_medio_resposta' => $calcMedia($temposResposta),
        'tempo_mediano_resposta' => $calcMediana($temposResposta),
        'tempo_medio_providencia' => $calcMedia($temposProvidencia),
        'tempo_mediano_providencia' => $calcMediana($temposProvidencia),
        'tempo_medio_encerramento' => $calcMedia($temposEncerramento),
        'maior_tempo_sem_resposta' => $maiorTempoSemResposta,
        
        'distribuicao_faixas' => $distFaixas,
        'distribuicao_blocos' => $distBlocos,
        'distribuicao_categorias' => $distCategorias
    ];
}

/**
 * Cria uma Sessão de Auditoria Formal com Snapshot Imutável e Preservação Probatória.
 *
 * @param string $titulo Título descritivo da apuração
 * @param string $dataReferencia Data de corte (Y-m-d)
 * @param array $filtros Filtros aplicados na coleta
 * @param int $usuarioId ID do conselheiro logado
 * @return array
 */
function vds_auditoria_criar_sessao_congelada($titulo, $dataReferencia, $filtros, $usuarioId) {
    $link = DBConnect();

    $identificador = 'AUD-' . date('Ymd-His');
    $dataColeta = date('Y-m-d H:i:s');
    $dataRef = !empty($dataReferencia) ? $dataReferencia : date('Y-m-d');
    $tituloSanitizado = !empty($titulo) ? trim($titulo) : "Auditoria Geral de Ocorrências ({$identificador})";

    // 1. Filtrar chamados locais
    $sqlWhere = " WHERE id > 0 ";
    $params = [];
    $types = "";

    if (!empty($filtros['dt_inicio'])) {
        $sqlWhere .= " AND abertura >= ? ";
        $params[] = $filtros['dt_inicio'] . ' 00:00:00';
        $types .= "s";
    }
    if (!empty($filtros['dt_fim'])) {
        $sqlWhere .= " AND abertura <= ? ";
        $params[] = $filtros['dt_fim'] . ' 23:59:59';
        $types .= "s";
    }
    if (!empty($filtros['bloco'])) {
        $sqlWhere .= " AND bloco = ? ";
        $params[] = $filtros['bloco'];
        $types .= "s";
    }
    if (!empty($filtros['oco_tipo'])) {
        $sqlWhere .= " AND oco_tipo = ? ";
        $params[] = (int)$filtros['oco_tipo'];
        $types .= "i";
    }

    $sql = "SELECT * FROM ocorrencias {$sqlWhere} ORDER BY abertura DESC";
    $stmt = mysqli_prepare($link, $sql);
    if (!empty($types) && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $itensAuditados = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $itensAuditados[] = vds_auditoria_analisar_chamado($row, [], $dataRef);
    }
    mysqli_stmt_close($stmt);

    $totalChamados = count($itensAuditados);
    $totaisMetricas = vds_auditoria_calcular_metricas($itensAuditados);

    $jsonFiltros = json_encode($filtros, JSON_UNESCAPED_UNICODE);
    $jsonMetricas = json_encode($totaisMetricas, JSON_UNESCAPED_UNICODE);

    // 2. Gravar Registro Mestre na `auditoria_sessoes`
    $stmtSessao = mysqli_prepare($link, "INSERT INTO auditoria_sessoes (identificador_execucao, titulo, data_referencia, data_coleta, usuario_id, status, total_chamados, parametros_filtro_json, totais_indicadores_json) VALUES (?, ?, ?, ?, ?, 'finalizada', ?, ?, ?)");
    mysqli_stmt_bind_param($stmtSessao, "ssssiiss", $identificador, $tituloSanitizado, $dataRef, $dataColeta, $usuarioId, $totalChamados, $jsonFiltros, $jsonMetricas);
    $okSessao = mysqli_stmt_execute($stmtSessao);
    $sessaoId = mysqli_insert_id($link);
    mysqli_stmt_close($stmtSessao);

    if (!$okSessao || !$sessaoId) {
        DBClose($link);
        return ['success' => false, 'message' => 'Falha ao registrar sessão de auditoria no banco de dados.'];
    }

    // 3. Gravar Itens Imutáveis na `auditoria_execucao_itens`
    $stmtItem = mysqli_prepare($link, "INSERT INTO auditoria_execucao_itens (
        sessao_id, ocorrencia_id, protocolo_vds, uuid_remoto, bloco, unidade, categoria_oco_tipo,
        data_abertura, data_referencia, tempo_decorrido_dias, faixa_tempo, situacao_resposta,
        primeira_resposta_data, primeira_resposta_tempo_dias, primeira_resposta_por,
        primeira_resposta_cargo_original, primeira_resposta_ator, primeira_resposta_resumo,
        situacao_chamado, data_encerramento, tempo_ate_encerramento_dias,
        situacao_providencias, primeira_providencia_data, primeira_providencia_tempo_dias,
        primeira_providencia_descricao, tipo_comprovacao_providencia, dados_originais_api_json
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($itensAuditados as $item) {
        $jsonSnapshot = json_encode([
            'ocorrencia_id' => $item['ocorrencia_id'],
            'protocolo' => $item['protocolo_vds'],
            'eventos' => $item['eventos_brutos']
        ], JSON_UNESCAPED_UNICODE);

        mysqli_stmt_bind_param(
            $stmtItem,
            "iissssissiississsssisisssss",
            $sessaoId,
            $item['ocorrencia_id'],
            $item['protocolo_vds'],
            $item['uuid_remoto'],
            $item['bloco'],
            $item['unidade'],
            $item['categoria_oco_tipo'],
            $item['data_abertura_iso'],
            $item['data_referencia'],
            $item['tempo_decorrido_dias'],
            $item['faixa_tempo'],
            $item['situacao_resposta'],
            $item['primeira_resposta_data'],
            $item['primeira_resposta_tempo_dias'],
            $item['primeira_resposta_por'],
            $item['primeira_resposta_cargo_original'],
            $item['primeira_resposta_ator'],
            $item['primeira_resposta_resumo'],
            $item['situacao_chamado'],
            $item['data_encerramento'],
            $item['tempo_ate_encerramento_dias'],
            $item['situacao_providencias'],
            $item['primeira_providencia_data'],
            $item['primeira_providencia_tempo_dias'],
            $item['primeira_providencia_descricao'],
            $item['tipo_comprovacao_providencia'],
            $jsonSnapshot
        );
        mysqli_stmt_execute($stmtItem);
    }
    mysqli_stmt_close($stmtItem);

    DBClose($link);

    return [
        'success' => true,
        'sessao_id' => $sessaoId,
        'identificador' => $identificador,
        'total_auditados' => $totalChamados,
        'metricas' => $totaisMetricas
    ];
}

/**
 * Consulta ocorrências para o painel em tempo real (sob demanda).
 */
function vds_auditoria_consultar_tempo_real($filtros, $dataReferencia) {
    $link = DBConnect();
    $dataRef = !empty($dataReferencia) ? $dataReferencia : date('Y-m-d');

    $sqlWhere = " WHERE id > 0 ";
    $params = [];
    $types = "";

    if (!empty($filtros['dt_inicio'])) {
        $sqlWhere .= " AND abertura >= ? ";
        $params[] = $filtros['dt_inicio'] . ' 00:00:00';
        $types .= "s";
    }
    if (!empty($filtros['dt_fim'])) {
        $sqlWhere .= " AND abertura <= ? ";
        $params[] = $filtros['dt_fim'] . ' 23:59:59';
        $types .= "s";
    }
    if (!empty($filtros['bloco'])) {
        $sqlWhere .= " AND bloco = ? ";
        $params[] = $filtros['bloco'];
        $types .= "s";
    }
    if (!empty($filtros['unidade'])) {
        $sqlWhere .= " AND unidade = ? ";
        $params[] = $filtros['unidade'];
        $types .= "s";
    }
    if (!empty($filtros['oco_tipo'])) {
        $sqlWhere .= " AND oco_tipo = ? ";
        $params[] = (int)$filtros['oco_tipo'];
        $types .= "i";
    }
    if (!empty($filtros['protocolo'])) {
        $sqlWhere .= " AND (protocolo_vds = ? OR id = ?) ";
        $params[] = $filtros['protocolo'];
        $params[] = (int)$filtros['protocolo'];
        $types .= "si";
    }

    $sql = "SELECT * FROM ocorrencias {$sqlWhere} ORDER BY abertura DESC LIMIT 1500";
    $stmt = mysqli_prepare($link, $sql);
    if (!empty($types) && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $todosItens = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $analise = vds_auditoria_analisar_chamado($row, [], $dataRef);

        // Filtro em memória pós-análise para as 4 dimensões se especificadas
        if (!empty($filtros['faixa_tempo']) && $analise['faixa_tempo'] !== $filtros['faixa_tempo']) {
            continue;
        }
        if (!empty($filtros['situacao_resposta']) && $analise['situacao_resposta'] !== $filtros['situacao_resposta']) {
            continue;
        }
        if (!empty($filtros['situacao_chamado']) && $analise['situacao_chamado'] !== $filtros['situacao_chamado']) {
            continue;
        }
        if (!empty($filtros['situacao_providencias']) && $analise['situacao_providencias'] !== $filtros['situacao_providencias']) {
            continue;
        }

        $todosItens[] = $analise;
    }
    mysqli_stmt_close($stmt);
    DBClose($link);

    $metricas = vds_auditoria_calcular_metricas($todosItens);

    return [
        'data_referencia' => $dataRef,
        'total' => count($todosItens),
        'itens' => $todosItens,
        'metricas' => $metricas
    ];
}

/**
 * Lista todas as sessões de auditoria formal já congeladas.
 */
function vds_auditoria_listar_sessoes() {
    $link = DBConnect();
    $sql = "SELECT s.*, u.nome as conselheiro_nome FROM auditoria_sessoes s LEFT JOIN usuarios u ON u.id = s.usuario_id ORDER BY s.data_coleta DESC";
    $res = @mysqli_query($link, $sql);
    $lista = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $r['totais'] = json_decode($r['totais_indicadores_json'] ?? '{}', true);
            $r['filtros'] = json_decode($r['parametros_filtro_json'] ?? '{}', true);
            $lista[] = $r;
        }
    }
    DBClose($link);
    return $lista;
}

/**
 * Recupera os dados congelados de uma sessão de auditoria salva.
 */
function vds_auditoria_carregar_sessao($sessaoId) {
    $link = DBConnect();
    $stmt = mysqli_prepare($link, "SELECT s.*, u.nome as conselheiro_nome FROM auditoria_sessoes s LEFT JOIN usuarios u ON u.id = s.usuario_id WHERE s.id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $sessaoId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $sessao = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$sessao) {
        DBClose($link);
        return null;
    }

    $sessao['totais'] = json_decode($sessao['totais_indicadores_json'] ?? '{}', true);
    $sessao['filtros'] = json_decode($sessao['parametros_filtro_json'] ?? '{}', true);

    $stmtItens = mysqli_prepare($link, "SELECT * FROM auditoria_execucao_itens WHERE sessao_id = ? ORDER BY data_abertura DESC");
    mysqli_stmt_bind_param($stmtItens, "i", $sessaoId);
    mysqli_stmt_execute($stmtItens);
    $resItens = mysqli_stmt_get_result($stmtItens);
    $itens = [];
    while ($it = mysqli_fetch_assoc($resItens)) {
        $itens[] = $it;
    }
    mysqli_stmt_close($stmtItens);
    DBClose($link);

    $sessao['itens'] = $itens;
    return $sessao;
}
