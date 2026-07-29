<?php

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/vds_auth_service.php";

define('VDS_TEST_PROTOCOL', '259564');
define('VDS_TEST_MODE', true);

/**
 * Limpa e formata texto de mensagem evitando exibição de tags HTML brutas como <br/>.
 */
function vds_format_mensagem_text($text) {
    if (!$text) return '';
    $clean = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $clean = preg_replace('/<\/p>/i', "\n", $clean);
    $clean = strip_tags($clean);
    return nl2br(htmlspecialchars(trim($clean)));
}

/**
 * Consulta a lista de ocorrências da VDS e sincroniza na tabela `ocorrencias`.
 */
function vds_sync_ocorrencias($condominioUuid = null, $usuarioIdConselho = null) {
    // Para sincronização automática global, priorizar o token do condomínio/sistema
    $token = vds_get_token(null);
    if (!$token) {
        $token = vds_get_token($usuarioIdConselho);
    }
    if (!$token) {
        return ['success' => false, 'message' => 'Nenhum token ativo disponível para sincronização.'];
    }

    $url = VDS_BASE_URL . '/ocorrencia?page=1&limit=50&sortBy=dtExibicao&order=desc&Caixa=0&Lida=9';
    if ($condominioUuid) {
        $url .= '&Condominio.Uuid=' . urlencode($condominioUuid);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 401) {
        return ['success' => false, 'httpCode' => 401, 'message' => 'Token expirado ao sincronizar ocorrências.'];
    }

    if ($httpCode !== 200 || !$response) {
        return ['success' => false, 'httpCode' => $httpCode, 'message' => 'Erro ao consultar ocorrências da VDS.'];
    }

    $data = json_decode($response, true);
    $items = $data['regs'] ?? ($data['items'] ?? ($data['data'] ?? (is_array($data) ? $data : [])));

    $link = DBConnect();
    $count = 0;

    foreach ($items as $item) {
        $ocoId = (int)($item['ocorrenciaId'] ?? ($item['id'] ?? 0));
        $protocolo = $item['protocolo'] ?? ($item['Protocolo'] ?? null);
        // Parsear bloco e unidade do campo 'cargo' (ex: "Bloco B - 1108", "Bl. A - 102")
        $bloco = null;
        $unidade = null;
        $cargoStr = $item['cargo'] ?? '';
        if (preg_match('/Bl(?:oco|\.)\s*([A-Za-z0-9]+)\s*-\s*(\d+)/i', $cargoStr, $bu)) {
            $bloco = trim($bu[1]);
            $unidade = trim($bu[2]);
        }
        // Fallback somente quando não há dados de bloco/unidade (ex: 'CONDOMÍNIO - GESTÃO')
        if (empty($bloco)) { $bloco = 'Z'; }
        if (empty($unidade)) { $unidade = '999'; }

        $rawDt = $item['dtExibicao'] ?? ($item['abertura'] ?? null);
        if ($rawDt && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}:\d{2}:\d{2})/', $rawDt, $m)) {
            $abertura = "{$m[3]}-{$m[2]}-{$m[1]} {$m[4]}";
        } elseif ($rawDt && preg_match('/^\d{4}-\d{2}-\d{2}/', $rawDt)) {
            $abertura = date('Y-m-d H:i:s', strtotime($rawDt));
        } else {
            $abertura = date('Y-m-d H:i:s');
        }

        $ocoTipo = (int)($item['tipoId'] ?? ($item['tipo'] ?? ($item['ocoTipo'] ?? 115)));
        $status = $item['statusNome'] ?? ($item['statusStr'] ?? ($item['status'] ?? 'Aberto'));

        if (!$protocolo && !$uuidRemoto && !$ocoId) continue;

        // Tentar obter registro local existente por ID, protocolo_vds ou uuid_remoto
        $stmtFind = mysqli_prepare($link, "SELECT id FROM ocorrencias WHERE id = ? OR protocolo_vds = ? OR uuid_remoto = ? LIMIT 1");
        $protoStr = (string)($protocolo ?? $ocoId);
        $uuidStr = (string)$uuidRemoto;
        mysqli_stmt_bind_param($stmtFind, "iss", $ocoId, $protoStr, $uuidStr);
        mysqli_stmt_execute($stmtFind);
        $resFind = mysqli_stmt_get_result($stmtFind);
        $rowFind = mysqli_fetch_assoc($resFind);
        mysqli_stmt_close($stmtFind);

        $jsonEncoded = json_encode($item, JSON_UNESCAPED_UNICODE);

        if ($rowFind) {
            // Só atualizar bloco/unidade se temos dados reais (não fallback)
            $blocoReal = ($bloco !== 'Z') ? $bloco : null;
            $unidadeReal = ($unidade !== '999') ? $unidade : null;
            $stmtUp = mysqli_prepare($link, "UPDATE ocorrencias SET uuid_remoto = ?, protocolo_vds = ?, oco_tipo = ?, bloco = IFNULL(?, bloco), unidade = IFNULL(?, unidade), status = ?, dados_json = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmtUp, "ssissssi", $uuidStr, $protoStr, $ocoTipo, $blocoReal, $unidadeReal, $status, $jsonEncoded, $rowFind['id']);
            mysqli_stmt_execute($stmtUp);
            mysqli_stmt_close($stmtUp);
        } else {
            if ($ocoId > 0) {
                $stmtIns = mysqli_prepare($link, "INSERT INTO ocorrencias (id, abertura, bloco, unidade, status, uuid_remoto, protocolo_vds, oco_tipo, dados_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmtIns, "issssssis", $ocoId, $abertura, $bloco, $unidade, $status, $uuidStr, $protoStr, $ocoTipo, $jsonEncoded);
            } else {
                $stmtIns = mysqli_prepare($link, "INSERT INTO ocorrencias (abertura, bloco, unidade, status, uuid_remoto, protocolo_vds, oco_tipo, dados_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmtIns, "ssssssis", $abertura, $bloco, $unidade, $status, $uuidStr, $protoStr, $ocoTipo, $jsonEncoded);
            }
            mysqli_stmt_execute($stmtIns);
            mysqli_stmt_close($stmtIns);
        }

        // Mapear unidade na vds_uuid_mapping se houver bloco e unidade
        if ($bloco && $unidade && !empty($item['unidade']['uuid'])) {
            $chave = strtoupper($bloco) . ":" . trim($unidade);
            $stmtMap = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto) VALUES ('unidade', ?, ?) ON DUPLICATE KEY UPDATE uuid_remoto = VALUES(uuid_remoto)");
            $uUuid = $item['unidade']['uuid'];
            mysqli_stmt_bind_param($stmtMap, "ss", $chave, $uUuid);
            mysqli_stmt_execute($stmtMap);
            mysqli_stmt_close($stmtMap);
        }

        $count++;
    }

    DBClose($link);
    return ['success' => true, 'count' => $count];
}

/**
 * Retorna os detalhes de uma ocorrência (histórico de eventos e notas internas).
 * Se a ocorrência legada não possui uuid_remoto ou não existe localmente, busca na VDS pelo protocolo para resolver o UUID e sincronizar.
 */
function vds_get_ocorrencia_detalhe($ocorrenciaId, $usuarioIdConselho = null) {
    $debug = [
        'input_ocorrencia_id' => $ocorrenciaId,
        'usuario_id_conselho' => $usuarioIdConselho,
        'local_found' => false,
        'token_found' => false,
        'search_api_called' => false,
        'search_url' => null,
        'search_http_code' => null,
        'search_raw_response' => null,
        'detail_api_called' => false,
        'detail_url' => null,
        'detail_http_code' => null,
        'detail_raw_response' => null,
        'error_log' => []
    ];

    $link = DBConnect();

    // 1. Tentar buscar no banco local por ID (numérico) ou por protocolo_vds (string)
    $stmt = mysqli_prepare($link, "SELECT * FROM ocorrencias WHERE id = ? OR protocolo_vds = ? LIMIT 1");
    $ocoInt = (int)$ocorrenciaId;
    $ocoStr = (string)$ocorrenciaId;
    mysqli_stmt_bind_param($stmt, "is", $ocoInt, $ocoStr);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ocorrencia = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($ocorrencia) {
        $debug['local_found'] = true;
        $debug['local_record'] = $ocorrencia;

        // Auto-corrigir bloco/unidade se estão com fallback Z/999 — parsear do cargo no dados_json
        if (($ocorrencia['bloco'] === 'Z' || $ocorrencia['unidade'] === '999') && !empty($ocorrencia['dados_json'])) {
            $dadosJson = json_decode($ocorrencia['dados_json'], true);
            $cargoStr = $dadosJson['cargo'] ?? '';
            if (preg_match('/Bl(?:oco|\.)\s*([A-Za-z0-9]+)\s*-\s*(\d+)/i', $cargoStr, $bu)) {
                $blocoCorrigido = trim($bu[1]);
                $unidadeCorrigida = trim($bu[2]);
                $stmtFix = mysqli_prepare($link, "UPDATE ocorrencias SET bloco = ?, unidade = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmtFix, "ssi", $blocoCorrigido, $unidadeCorrigida, $ocorrencia['id']);
                mysqli_stmt_execute($stmtFix);
                mysqli_stmt_close($stmtFix);
                $ocorrencia['bloco'] = $blocoCorrigido;
                $ocorrencia['unidade'] = $unidadeCorrigida;
                $debug['bloco_unidade_corrigido'] = true;
            }
        }
    }

    $token = vds_get_token($usuarioIdConselho);
    $debug['token_found'] = !empty($token);
    if (!$token) {
        $debug['error_log'][] = 'Token da VDS não encontrado no banco (vds_tokens). Faça login em Configurações.';
    }

    // 2. Se a ocorrência não existe no banco ou não tem uuid_remoto, consulta na VDS por Protocolo
    if ((!$ocorrencia || empty($ocorrencia['uuid_remoto'])) && $token) {
        $protoSearch = !empty($ocorrencia['protocolo_vds']) ? $ocorrencia['protocolo_vds'] : $ocoStr;
        if (!empty($protoSearch)) {
            $urlSearch = VDS_BASE_URL . '/ocorrencia?page=1&limit=20&sortBy=dtExibicao&order=desc&Lida=9&Caixa=0&Protocolo=' . urlencode($protoSearch);
            $debug['search_api_called'] = true;
            $debug['search_url'] = $urlSearch;

            $chSearch = curl_init($urlSearch);
            curl_setopt_array($chSearch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Origin: ' . VDS_ORIGIN_HEADER
                ]
            ]);
            $respSearch = curl_exec($chSearch);
            $codeSearch = curl_getinfo($chSearch, CURLINFO_HTTP_CODE);
            $curlErrStr = curl_error($chSearch);
            curl_close($chSearch);

            $debug['search_http_code'] = $codeSearch;
            $debug['search_raw_response'] = $respSearch;
            if ($curlErrStr) {
                $debug['error_log'][] = "Erro cURL na busca de protocolo: {$curlErrStr}";
            }

            if ($codeSearch === 200 && $respSearch) {
                $dataSearch = json_decode($respSearch, true);
                $regsSearch = $dataSearch['regs'] ?? ($dataSearch['items'] ?? []);
                $debug['search_regs_count'] = count($regsSearch);

                if (!empty($regsSearch[0])) {
                    $item = $regsSearch[0];
                    $realOcoId = (int)($item['ocorrenciaId'] ?? ($item['id'] ?? 0));
                    $realProtocolo = $item['protocolo'] ?? $protoSearch;
                    $realUuid = $item['uuid'] ?? null;
                    
                    // Parsear bloco e unidade do campo 'cargo'
                    $bloco = null;
                    $unidade = null;
                    $cargoStr = $item['cargo'] ?? '';
                    if (preg_match('/Bl(?:oco|\.)\s*([A-Za-z0-9]+)\s*-\s*(\d+)/i', $cargoStr, $bu)) {
                        $bloco = trim($bu[1]);
                        $unidade = trim($bu[2]);
                    }
                    if (empty($bloco)) { $bloco = 'Z'; }
                    if (empty($unidade)) { $unidade = '999'; }

                    $rawDt = $item['dtExibicao'] ?? ($item['dthora'] ?? null);
                    if ($rawDt && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}:\d{2}:\d{2})/', $rawDt, $m)) {
                        $abertura = "{$m[3]}-{$m[2]}-{$m[1]} {$m[4]}";
                    } elseif ($rawDt && preg_match('/^\d{4}-\d{2}-\d{2}/', $rawDt)) {
                        $abertura = date('Y-m-d H:i:s', strtotime($rawDt));
                    } else {
                        $abertura = date('Y-m-d H:i:s');
                    }

                    $ocoTipo = (int)($item['tipoId'] ?? ($item['tipo'] ?? 115));
                    $status = $item['statusNome'] ?? 'Aberto';
                    $jsonEnc = json_encode($item, JSON_UNESCAPED_UNICODE);

                    if ($ocorrencia) {
                        // Atualizar dados remotos e corrigir bloco/unidade se necessário
                        $blocoReal = ($bloco !== 'Z') ? $bloco : null;
                        $unidadeReal = ($unidade !== '999') ? $unidade : null;
                        $stmtUp = mysqli_prepare($link, "UPDATE ocorrencias SET uuid_remoto = ?, protocolo_vds = ?, oco_tipo = ?, bloco = IFNULL(?, bloco), unidade = IFNULL(?, unidade), dados_json = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmtUp, "ssisssi", $realUuid, $realProtocolo, $ocoTipo, $blocoReal, $unidadeReal, $jsonEnc, $ocorrencia['id']);
                        if (!mysqli_stmt_execute($stmtUp)) {
                            $debug['error_log'][] = "Erro MySQL UPDATE: " . mysqli_stmt_error($stmtUp);
                        }
                        mysqli_stmt_close($stmtUp);

                        $ocorrencia['uuid_remoto'] = $realUuid;
                        $ocorrencia['protocolo_vds'] = $realProtocolo;
                        $ocorrencia['oco_tipo'] = $ocoTipo;
                        $ocorrencia['dados_json'] = $jsonEnc;
                        if ($blocoReal) { $ocorrencia['bloco'] = $blocoReal; }
                        if ($unidadeReal) { $ocorrencia['unidade'] = $unidadeReal; }
                    } else {
                        $stmtCheck = mysqli_prepare($link, "SELECT id FROM ocorrencias WHERE id = ? LIMIT 1");
                        mysqli_stmt_bind_param($stmtCheck, "i", $realOcoId);
                        mysqli_stmt_execute($stmtCheck);
                        $resCheck = mysqli_stmt_get_result($stmtCheck);
                        $rowCheck = mysqli_fetch_assoc($resCheck);
                        mysqli_stmt_close($stmtCheck);

                        if ($rowCheck) {
                            $stmtUp = mysqli_prepare($link, "UPDATE ocorrencias SET uuid_remoto = ?, protocolo_vds = ?, oco_tipo = ?, bloco = IFNULL(?, bloco), unidade = IFNULL(?, unidade), dados_json = ? WHERE id = ?");
                            $blocoReal2 = ($bloco !== 'Z') ? $bloco : null;
                            $unidadeReal2 = ($unidade !== '999') ? $unidade : null;
                            mysqli_stmt_bind_param($stmtUp, "ssisssi", $realUuid, $realProtocolo, $ocoTipo, $blocoReal2, $unidadeReal2, $jsonEnc, $realOcoId);
                            if (!mysqli_stmt_execute($stmtUp)) {
                                $debug['error_log'][] = "Erro MySQL UPDATE (rowCheck): " . mysqli_stmt_error($stmtUp);
                            }
                            mysqli_stmt_close($stmtUp);
                            $targetId = $realOcoId;
                        } else {
                            if ($realOcoId > 0) {
                                $stmtIns = mysqli_prepare($link, "INSERT INTO ocorrencias (id, abertura, bloco, unidade, status, uuid_remoto, protocolo_vds, oco_tipo, dados_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                mysqli_stmt_bind_param($stmtIns, "issssssis", $realOcoId, $abertura, $bloco, $unidade, $status, $realUuid, $realProtocolo, $ocoTipo, $jsonEnc);
                            } else {
                                $stmtIns = mysqli_prepare($link, "INSERT INTO ocorrencias (abertura, bloco, unidade, status, uuid_remoto, protocolo_vds, oco_tipo, dados_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                mysqli_stmt_bind_param($stmtIns, "ssssssis", $abertura, $bloco, $unidade, $status, $realUuid, $realProtocolo, $ocoTipo, $jsonEnc);
                            }
                            if (!mysqli_stmt_execute($stmtIns)) {
                                $debug['error_log'][] = "Erro MySQL INSERT ocorrencia: " . mysqli_stmt_error($stmtIns);
                            }
                            $targetId = ($realOcoId > 0) ? $realOcoId : mysqli_insert_id($link);
                            mysqli_stmt_close($stmtIns);
                        }

                        $stmtFetch = mysqli_prepare($link, "SELECT * FROM ocorrencias WHERE id = ? LIMIT 1");
                        mysqli_stmt_bind_param($stmtFetch, "i", $targetId);
                        mysqli_stmt_execute($stmtFetch);
                        $resFetch = mysqli_stmt_get_result($stmtFetch);
                        $ocorrencia = mysqli_fetch_assoc($resFetch);
                        mysqli_stmt_close($stmtFetch);
                    }
                } else {
                    $debug['error_log'][] = "API de busca de protocolo não retornou nenhum registro na chave 'regs'.";
                }
            } else {
                $debug['error_log'][] = "HTTP {$codeSearch} ao buscar protocolo na VDS.";
            }
        }
    }

    if (!$ocorrencia) {
        DBClose($link);
        return [
            'local' => null,
            'notasInternas' => [],
            'tagsUnidades' => [],
            'remoteData' => null,
            'debug' => $debug
        ];
    }

    // 3. Buscar Notas Internas locais do Conselho
    $stmtNotas = mysqli_prepare($link, "SELECT * FROM ocorrencia_notas_internas WHERE ocorrencia_id = ? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmtNotas, "i", $ocorrencia['id']);
    mysqli_stmt_execute($stmtNotas);
    $resNotas = mysqli_stmt_get_result($stmtNotas);
    $notasInternas = [];
    while ($n = mysqli_fetch_assoc($resNotas)) {
        $notasInternas[] = $n;
    }
    mysqli_stmt_close($stmtNotas);

    // 4. Buscar Tags de Unidades vinculadas
    $stmtTags = mysqli_prepare($link, "SELECT * FROM ocorrencia_unidade_tag WHERE ocorrencia_id = ?");
    mysqli_stmt_bind_param($stmtTags, "i", $ocorrencia['id']);
    mysqli_stmt_execute($stmtTags);
    $resTags = mysqli_stmt_get_result($stmtTags);
    $tagsUnidades = [];
    while ($t = mysqli_fetch_assoc($resTags)) {
        $tagsUnidades[] = $t;
    }
    mysqli_stmt_close($stmtTags);

    DBClose($link);

    // 5. Buscar os detalhes reais de eventos na API VDS via HTTP GET /ocorrencia/{uuid}
    $remoteData = null;
    $uuidRemoto = $ocorrencia['uuid_remoto'] ?? null;

    if ($uuidRemoto && $token) {
        $urlDetail = VDS_BASE_URL . '/ocorrencia/' . urlencode($uuidRemoto);
        $debug['detail_api_called'] = true;
        $debug['detail_url'] = $urlDetail;

        $ch = curl_init($urlDetail);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $detailCurlErr = curl_error($ch);
        curl_close($ch);

        $debug['detail_http_code'] = $httpCode;
        $debug['detail_raw_response'] = $response;
        if ($detailCurlErr) {
            $debug['error_log'][] = "Erro cURL nos detalhes da ocorrência: {$detailCurlErr}";
        }

        if ($httpCode === 200 && $response) {
            $remoteData = json_decode($response, true);
        } else {
            $debug['error_log'][] = "HTTP {$httpCode} ao buscar detalhes /ocorrencia/{$uuidRemoto}.";
        }
    } else {
        if (empty($uuidRemoto)) {
            $debug['error_log'][] = "uuid_remoto está vazio na ocorrência local ID {$ocorrencia['id']}.";
        }
    }

    return [
        'local' => $ocorrencia,
        'notasInternas' => $notasInternas,
        'tagsUnidades' => $tagsUnidades,
        'remoteData' => $remoteData,
        'debug' => $debug
    ];
}

/**
 * Adiciona uma Nota Interna do Conselho (1º Fator - Salva localmente por padrão).
 */
function vds_adicionar_nota_interna($ocorrenciaId, $conselheiroId, $conselheiroNome, $texto, $anexoCaminho = null) {
    $link = DBConnect();

    // Obter protocolo
    $stmtOco = mysqli_prepare($link, "SELECT protocolo_vds FROM ocorrencias WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtOco, "i", $ocorrenciaId);
    mysqli_stmt_execute($stmtOco);
    $resOco = mysqli_stmt_get_result($stmtOco);
    $rowOco = mysqli_fetch_assoc($resOco);
    $protocolo = $rowOco['protocolo_vds'] ?? null;
    mysqli_stmt_close($stmtOco);

    $stmt = mysqli_prepare($link, "INSERT INTO ocorrencia_notas_internas (ocorrencia_id, protocolo_vds, conselheiro_id, conselheiro_nome, texto, anexo_caminho, enviado_remoto) VALUES (?, ?, ?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmt, "isisss", $ocorrenciaId, $protocolo, $conselheiroId, $conselheiroNome, $texto, $anexoCaminho);
    $success = mysqli_stmt_execute($stmt);
    $notaId = mysqli_insert_id($link);
    mysqli_stmt_close($stmt);

    DBClose($link);
    return ['success' => $success, 'notaId' => $notaId];
}

/**
 * Publica uma Nota Interna no Sistema Remoto VDS (2º Fator).
 * COM TRAVA DE SEGURANÇA: Permite postagem apenas no protocolo 259564 em modo de teste.
 */
function vds_publicar_nota_remoto($notaId, $usuarioIdConselho = null) {
    $link = DBConnect();

    $stmt = mysqli_prepare($link, "SELECT n.*, o.id as local_oco_id, o.uuid_remoto, o.dados_json FROM ocorrencia_notas_internas n JOIN ocorrencias o ON o.id = n.ocorrencia_id WHERE n.id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $notaId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $nota = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$nota) {
        DBClose($link);
        return ['success' => false, 'message' => 'Nota interna não encontrada.'];
    }

    // Resolvendo o ID remoto real da VDS
    $remoteOcoId = (int)$nota['local_oco_id'];
    if (!empty($nota['dados_json'])) {
        $dataDec = json_decode($nota['dados_json'], true);
        if (!empty($dataDec['ocorrenciaId'])) {
            $remoteOcoId = (int)$dataDec['ocorrenciaId'];
        } elseif (!empty($dataDec['id'])) {
            $remoteOcoId = (int)$dataDec['id'];
        }
    }

    // TRAVA DE SEGURANÇA PARA TESTES DE ESCRITA
    $protocolo = $nota['protocolo_vds'];
    if (VDS_TEST_MODE && $protocolo !== VDS_TEST_PROTOCOL) {
        DBClose($link);
        return [
            'success' => false,
            'blockedByLock' => true,
            'message' => 'Modo de Teste Ativo: A publicação remota está restrita EXCLUSIVAMENTE ao Protocolo ' . VDS_TEST_PROTOCOL . '. (Protocolo atual: ' . ($protocolo ?? 'Desconhecido') . ').'
        ];
    }

    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        DBClose($link);
        return ['success' => false, 'message' => 'Nenhum token ativo para publicar no remoto.'];
    }

    // Enviar mensagem/comentário para a API VDS (Endpoint: POST /ocorrencia/comentario)
    $payloadData = [
        'uuid' => $nota['uuid_remoto'],
        'mensagem' => $nota['texto'],
        'ocorrenciaPaiId' => $remoteOcoId
    ];

    $payload = json_encode($payloadData, JSON_UNESCAPED_UNICODE);

    $ch = curl_init(VDS_BASE_URL . '/ocorrencia/comentario');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
        $resJson = json_decode($response, true);
        $vdsEventoId = (string)($resJson['ocorrenciaId'] ?? ($resJson['id'] ?? null));

        $stmtUp = mysqli_prepare($link, "UPDATE ocorrencia_notas_internas SET enviado_remoto = 1, data_envio_remoto = NOW(), vds_evento_uuid = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmtUp, "si", $vdsEventoId, $notaId);
        mysqli_stmt_execute($stmtUp);
        mysqli_stmt_close($stmtUp);
        DBClose($link);
        return ['success' => true, 'message' => "Nota publicada com sucesso no chamado remoto (ID VDS {$remoteOcoId}" . ($vdsEventoId ? ", Evento VDS {$vdsEventoId}" : "") . ")!"];
    }

    DBClose($link);
    return ['success' => false, 'httpCode' => $httpCode, 'message' => "Erro ao enviar mensagem para a VDS ({$httpCode}). Resposta: " . substr($response, 0, 300)];
}

/**
 * Upload de mídias/anexos na VDS.
 */
function vds_upload_midia($base64String, $usuarioIdConselho = null) {
    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        return ['success' => false, 'message' => 'Token indisponível para upload.'];
    }

    $payload = json_encode(['base64String' => $base64String]);

    $ch = curl_init(VDS_BASE_URL . '/upload');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        return ['success' => true, 'data' => json_decode($response, true)];
    }

    return ['success' => false, 'httpCode' => $httpCode, 'message' => 'Erro no upload de imagem/arquivo.'];
}

/**
 * Vincula uma unidade (Tag) a uma ocorrência.
 */
function vds_vincular_unidade_tag($ocorrenciaId, $bloco, $unidade, $tipoVinculo = 'citada') {
    $link = DBConnect();
    $stmtOco = mysqli_prepare($link, "SELECT protocolo_vds FROM ocorrencias WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtOco, "i", $ocorrenciaId);
    mysqli_stmt_execute($stmtOco);
    $resOco = mysqli_stmt_get_result($stmtOco);
    $rowOco = mysqli_fetch_assoc($resOco);
    $protocolo = $rowOco['protocolo_vds'] ?? null;
    mysqli_stmt_close($stmtOco);

    $stmt = mysqli_prepare($link, "INSERT INTO ocorrencia_unidade_tag (ocorrencia_id, protocolo_vds, bloco, unidade, tipo_vinculo) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", $ocorrenciaId, $protocolo, $bloco, $unidade, $tipoVinculo);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    DBClose($link);
    return ['success' => $success];
}

/**
 * Persiste/Atualiza um item individual vindo da API da VDS na tabela local `ocorrencias`.
 */
function vds_persist_item_local($item, $linkParam = null) {
    $closeLink = false;
    $link = $linkParam;
    if (!$link) {
        $link = DBConnect();
        $closeLink = true;
    }

    $ocoId = (int)($item['ocorrenciaId'] ?? ($item['id'] ?? 0));
    $protocolo = $item['protocolo'] ?? ($item['Protocolo'] ?? null);
    $uuidRemoto = $item['uuid'] ?? ($item['uuid_remoto'] ?? null);

    $bloco = null;
    $unidade = null;
    $cargoStr = $item['cargo'] ?? '';
    if (preg_match('/Bl(?:oco|\.)\s*([A-Za-z0-9]+)\s*-\s*(\d+)/i', $cargoStr, $bu)) {
        $bloco = trim($bu[1]);
        $unidade = trim($bu[2]);
    }
    if (empty($bloco)) { $bloco = 'Z'; }
    if (empty($unidade)) { $unidade = '999'; }

    $rawDt = $item['dtExibicao'] ?? ($item['dthora'] ?? ($item['abertura'] ?? null));
    if ($rawDt && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}:\d{2}:\d{2})/', $rawDt, $m)) {
        $abertura = "{$m[3]}-{$m[2]}-{$m[1]} {$m[4]}";
    } elseif ($rawDt && preg_match('/^\d{4}-\d{2}-\d{2}/', $rawDt)) {
        $abertura = date('Y-m-d H:i:s', strtotime($rawDt));
    } else {
        $abertura = date('Y-m-d H:i:s');
    }

    $ocoTipo = (int)($item['tipoId'] ?? ($item['tipo'] ?? ($item['ocoTipo'] ?? 115)));
    $status = $item['statusNome'] ?? ($item['statusStr'] ?? ($item['status'] ?? 'Aberto'));

    $localId = $ocoId;

    if ($protocolo || $uuidRemoto || $ocoId) {
        $stmtFind = mysqli_prepare($link, "SELECT id, bloco, unidade FROM ocorrencias WHERE id = ? OR protocolo_vds = ? OR uuid_remoto = ? LIMIT 1");
        $protoStr = (string)($protocolo ?? $ocoId);
        $uuidStr = (string)$uuidRemoto;
        mysqli_stmt_bind_param($stmtFind, "iss", $ocoId, $protoStr, $uuidStr);
        mysqli_stmt_execute($stmtFind);
        $resFind = mysqli_stmt_get_result($stmtFind);
        $rowFind = mysqli_fetch_assoc($resFind);
        mysqli_stmt_close($stmtFind);

        $jsonEncoded = json_encode($item, JSON_UNESCAPED_UNICODE);

        if ($rowFind) {
            $blocoReal = ($bloco !== 'Z') ? $bloco : null;
            $unidadeReal = ($unidade !== '999') ? $unidade : null;
            $stmtUp = mysqli_prepare($link, "UPDATE ocorrencias SET uuid_remoto = ?, protocolo_vds = ?, oco_tipo = ?, bloco = IFNULL(?, bloco), unidade = IFNULL(?, unidade), status = ?, dados_json = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmtUp, "ssissssi", $uuidStr, $protoStr, $ocoTipo, $blocoReal, $unidadeReal, $status, $jsonEncoded, $rowFind['id']);
            mysqli_stmt_execute($stmtUp);
            mysqli_stmt_close($stmtUp);
            $localId = $rowFind['id'];
        } else {
            if ($ocoId > 0) {
                $stmtIns = mysqli_prepare($link, "INSERT INTO ocorrencias (id, abertura, bloco, unidade, status, uuid_remoto, protocolo_vds, oco_tipo, dados_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmtIns, "issssssis", $ocoId, $abertura, $bloco, $unidade, $status, $uuidStr, $protoStr, $ocoTipo, $jsonEncoded);
            } else {
                $stmtIns = mysqli_prepare($link, "INSERT INTO ocorrencias (abertura, bloco, unidade, status, uuid_remoto, protocolo_vds, oco_tipo, dados_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmtIns, "ssssssis", $abertura, $bloco, $unidade, $status, $uuidStr, $protoStr, $ocoTipo, $jsonEncoded);
            }
            mysqli_stmt_execute($stmtIns);
            $localId = mysqli_insert_id($link) ?: $ocoId;
            mysqli_stmt_close($stmtIns);
        }
    }

    if ($closeLink) {
        DBClose($link);
    }

    return $localId;
}

/**
 * Consulta a lista de chamados não lidos (Lida=0) diretamente na VDS para a Visão Prática.
 * Cada chamado retornado é persistido no banco local.
 */
function vds_get_ocorrencias_pratico($usuarioIdConselho = null, $limit = 50) {
    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        return ['success' => false, 'message' => 'Token VDS indisponível.', 'items' => []];
    }

    $url = VDS_BASE_URL . '/ocorrencia?page=1&limit=' . (int)$limit . '&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return ['success' => false, 'httpCode' => $httpCode, 'message' => 'Erro ao consultar chamados não lidos na VDS.', 'items' => []];
    }

    $data = json_decode($response, true);
    $rawItems = $data['regs'] ?? ($data['items'] ?? ($data['data'] ?? (is_array($data) ? $data : [])));

    $items = [];
    $link = DBConnect();

    foreach ($rawItems as $item) {
        $localId = vds_persist_item_local($item, $link);

        // Carregar a linha local completa com responsabilidade e status
        $stmtLoc = mysqli_prepare($link, "SELECT * FROM ocorrencias WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtLoc, "i", $localId);
        mysqli_stmt_execute($stmtLoc);
        $resLoc = mysqli_stmt_get_result($stmtLoc);
        $rowLoc = mysqli_fetch_assoc($resLoc);
        mysqli_stmt_close($stmtLoc);

        if ($rowLoc) {
            $items[] = $rowLoc;
        } else {
            $bloco = 'Z'; $unidade = '999';
            if (preg_match('/Bl(?:oco|\.)\s*([A-Za-z0-9]+)\s*-\s*(\d+)/i', $item['cargo'] ?? '', $bu)) {
                $bloco = trim($bu[1]); $unidade = trim($bu[2]);
            }
            $items[] = [
                'id' => $localId,
                'uuid_remoto' => $item['uuid'] ?? null,
                'protocolo_vds' => $item['protocolo'] ?? null,
                'abertura' => $item['dtExibicao'] ?? ($item['dthora'] ?? date('Y-m-d H:i:s')),
                'bloco' => $bloco,
                'unidade' => $unidade,
                'oco_tipo' => $item['tipoId'] ?? 115,
                'status' => $item['statusNome'] ?? 'Aberto',
                'responsabilidade' => null,
                'resolvido' => 0,
                'dados_json' => json_encode($item, JSON_UNESCAPED_UNICODE)
            ];
        }
    }

    DBClose($link);
    return ['success' => true, 'items' => $items];
}

/**
 * Marca uma ocorrência como lida no ambiente remoto da VDS e atualiza os dados locais.
 */
function vds_marcar_como_lido($uuidRemoto, $usuarioIdConselho = null, $ocorrenciaId = null, $novoStatusLido = true) {
    $token = vds_get_token($usuarioIdConselho);

    if ($token && $uuidRemoto) {
        // Disparar PUT em /ocorrencia/leitura/{uuid} (Toggle endpoint da VDS)
        $urlLeitura = VDS_BASE_URL . '/ocorrencia/leitura/' . urlencode($uuidRemoto);
        $ch = curl_init($urlLeitura);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Length: 0',
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Se estamos marcando como lido, também dispara visualização
        if ($novoStatusLido) {
            $urlVisualizar = VDS_BASE_URL . '/ocorrencia/visualizar/' . urlencode($uuidRemoto);
            $chVis = curl_init($urlVisualizar);
            curl_setopt_array($chVis, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Length: 0',
                    'Origin: ' . VDS_ORIGIN_HEADER
                ]
            ]);
            curl_exec($chVis);
            curl_close($chVis);
        }
    }

    // Atualizar flag local em dados_json
    if ($ocorrenciaId || $uuidRemoto) {
        $link = DBConnect();
        $stmtFind = mysqli_prepare($link, "SELECT id, dados_json FROM ocorrencias WHERE id = ? OR uuid_remoto = ? LIMIT 1");
        $ocoInt = (int)$ocorrenciaId;
        $uuidStr = (string)$uuidRemoto;
        mysqli_stmt_bind_param($stmtFind, "is", $ocoInt, $uuidStr);
        mysqli_stmt_execute($stmtFind);
        $resFind = mysqli_stmt_get_result($stmtFind);
        $rowFind = mysqli_fetch_assoc($resFind);
        mysqli_stmt_close($stmtFind);

        if ($rowFind) {
            $dados = !empty($rowFind['dados_json']) ? json_decode($rowFind['dados_json'], true) : [];
            $dados['lida'] = (bool)$novoStatusLido;
            $dados['isLida'] = (bool)$novoStatusLido;
            $jsonUp = json_encode($dados, JSON_UNESCAPED_UNICODE);

            $stmtUp = mysqli_prepare($link, "UPDATE ocorrencias SET dados_json = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmtUp, "si", $jsonUp, $rowFind['id']);
            mysqli_stmt_execute($stmtUp);
            mysqli_stmt_close($stmtUp);
        }
        DBClose($link);
    }

    return ['success' => true, 'message' => $novoStatusLido ? 'Ocorrência marcada como LIDA na VDS!' : 'Ocorrência marcada como NÃO LIDA na VDS!'];
}

/**
 * Adiciona uma tag livre com auto-detecção de tipo (Unidade B1108 ou Notificação 123/2026).
 */
function vds_adicionar_tag_livre($ocorrenciaId, $tagInput) {
    $tagInput = trim($tagInput);
    if (empty($tagInput)) {
        return ['success' => false, 'message' => 'Tag vazia.'];
    }

    // 1. Notificação / Recurso (ex: 123/2026, 45/26, N123/2026)
    if (preg_match('/(?:N[oº\.\s]*)?(\d+)\/(\d{2,4})/i', $tagInput, $m)) {
        $num = $m[1];
        $ano = strlen($m[2]) == 2 ? '20' . $m[2] : $m[2];
        return vds_vincular_unidade_tag($ocorrenciaId, 'NOTIF', "{$num}/{$ano}", 'notificacao');
    }

    // 2. Unidade (ex: B1108, Bloco B - 1108, Bl. A 102, 1108, B-102)
    if (preg_match('/(?:Bl(?:oco|\.)?\s*)?([A-Za-z]?)\s*[-:]?\s*(\d{2,4})/i', $tagInput, $m)) {
        $bloco = !empty($m[1]) ? strtoupper($m[1]) : 'Z';
        $unidade = $m[2];
        return vds_vincular_unidade_tag($ocorrenciaId, $bloco, $unidade, 'unidade');
    }

    // 3. Tag livre genérica
    return vds_vincular_unidade_tag($ocorrenciaId, 'TAG', $tagInput, 'tag');
}
