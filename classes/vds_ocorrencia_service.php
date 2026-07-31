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
/**
 * Consulta a lista de ocorrências da VDS e sincroniza na tabela `ocorrencias`.
 * Utiliza o limit=10 e checagem de totalRegs para requisições ultra-rápidas sem timeout.
 */
function vds_sync_ocorrencias($condominioUuid = null, $usuarioIdConselho = null, $maxPages = 2, $limitPerPage = 10) {
    // Para sincronização automática global, priorizar o token do condomínio/sistema
    $token = vds_get_token(null);
    if (!$token) {
        $token = vds_get_token($usuarioIdConselho);
    }
    if (!$token) {
        return ['success' => false, 'message' => 'Nenhum token ativo disponível para sincronização.'];
    }

    $link = DBConnect();
    $count = 0;
    $insertedCount = 0;
    $totalRegs = 0;

    for ($page = 1; $page <= $maxPages; $page++) {
        $url = VDS_BASE_URL . '/ocorrencia?page=' . $page . '&limit=' . (int)$limitPerPage . '&sortBy=dtExibicao&order=desc&Caixa=0&Lida=9';
        if ($condominioUuid) {
            $url .= '&Condominio.Uuid=' . urlencode($condominioUuid);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 401) {
            vds_mark_token_expired($token);
            if ($count === 0) {
                DBClose($link);
                return ['success' => false, 'httpCode' => 401, 'message' => 'Token expirado ao sincronizar ocorrências.'];
            }
            break;
        }

        if ($httpCode !== 200 || !$response) {
            if ($count === 0) {
                DBClose($link);
                return ['success' => false, 'httpCode' => $httpCode, 'message' => 'Erro ao consultar ocorrências da VDS.'];
            }
            break;
        }

        $data = json_decode($response, true);
        $items = $data['regs'] ?? ($data['items'] ?? ($data['data'] ?? (is_array($data) ? $data : [])));
        $totalRegs = (int)($data['totalRegs'] ?? ($data['total'] ?? $totalRegs));

        if (empty($items)) {
            break;
        }

        foreach ($items as $item) {
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

            $stmtFind = mysqli_prepare($link, "SELECT id FROM ocorrencias WHERE id = ? OR protocolo_vds = ? OR uuid_remoto = ? OR id = ? LIMIT 1");
            $protoStr = (string)($protocolo ?? $ocoId);
            $protoInt = (int)$protoStr;
            $uuidStr = (string)$uuidRemoto;
            mysqli_stmt_bind_param($stmtFind, "issi", $ocoId, $protoStr, $uuidStr, $protoInt);
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
                $insertedCount++;
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

        if ($totalRegs > 0 && ($page * $limitPerPage >= $totalRegs)) {
            break;
        }
    }

    DBClose($link);
    return ['success' => true, 'count' => $count, 'inserted' => $insertedCount, 'totalRegs' => $totalRegs];
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
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Origin: ' . VDS_ORIGIN_HEADER
                ]
            ]);
            $respSearch = curl_exec($chSearch);
            $codeSearch = curl_getinfo($chSearch, CURLINFO_HTTP_CODE);
            $curlErrStr = curl_error($chSearch);
            curl_close($chSearch);

            if ($codeSearch === 401) {
                vds_mark_token_expired($token);
            }

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

    // 3. Buscar Notas Internas locais do Conselho (incluindo avatar do usuário local)
    $sqlNotas = "SELECT n.*, u.avatar as conselheiro_avatar, u.nome as usuario_nome_db FROM ocorrencia_notas_internas n LEFT JOIN usuarios u ON u.id = n.conselheiro_id WHERE n.ocorrencia_id = ? ORDER BY n.created_at ASC";
    $stmtNotas = @mysqli_prepare($link, $sqlNotas);
    if (!$stmtNotas) {
        $sqlNotas = "SELECT n.*, u.avatar as conselheiro_avatar, u.nome as usuario_nome_db FROM ocorrencia_notas_internas n LEFT JOIN conselho.usuarios u ON u.id = n.conselheiro_id WHERE n.ocorrencia_id = ? ORDER BY n.created_at ASC";
        $stmtNotas = @mysqli_prepare($link, $sqlNotas);
    }
    if (!$stmtNotas) {
        $stmtNotas = mysqli_prepare($link, "SELECT * FROM ocorrencia_notas_internas WHERE ocorrencia_id = ? ORDER BY created_at ASC");
    }
    mysqli_stmt_bind_param($stmtNotas, "i", $ocorrencia['id']);
    mysqli_stmt_execute($stmtNotas);
    $resNotas = mysqli_stmt_get_result($stmtNotas);
    $notasInternas = [];
    while ($n = mysqli_fetch_assoc($resNotas)) {
        if (!empty($n['usuario_nome_db'])) {
            $n['conselheiro_nome'] = $n['usuario_nome_db'];
        }
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
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $detailCurlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 401) {
            vds_mark_token_expired($token);
        }

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

    // Regra de Negócio: Apenas o próprio autor da nota interna pode publicá-la no remoto
    if ($usuarioIdConselho !== null && (int)($nota['conselheiro_id'] ?? 0) !== (int)$usuarioIdConselho) {
        DBClose($link);
        return ['success' => false, 'message' => 'Permissão negada: Apenas o autor da nota interna pode publicá-la no sistema remoto VDS.'];
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

    // Exigir Ultra-Login individual do conselheiro para postar comentário na VDS
    $token = vds_get_token($usuarioIdConselho, false);
    if (!$token) {
        DBClose($link);
        return ['success' => false, 'message' => 'Publicação no VDS bloqueada: É necessário ativá-lo via Ultra-Login em Configurações VDS. Somente Notas Internas do Conselho são permitidas sem Ultra-Login.'];
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
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 401) {
        vds_mark_token_expired($token);
    }

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
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Origin: ' . VDS_ORIGIN_HEADER
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 401) {
        vds_mark_token_expired($token);
    }

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
 * Garante que a tabela relacional de leitura por conselheiro existe no banco de dados.
 */
function vds_ensure_leitura_table_exists($link) {
    @mysqli_query($link, "CREATE TABLE IF NOT EXISTS ocorrencia_leitura_conselheiro (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

/**
 * Consulta a lista de chamados não lidos (Lida=0) diretamente na VDS API para a Visão Prática.
 * Com limit=10 por página, a API responde em < 1s de forma ultra-rápida.
 */
function vds_get_ocorrencias_pratico($usuarioIdConselho = null, $limit = 10, $page = 1) {
    $uId = (int)($usuarioIdConselho ?? 1);
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);

    $debug = [
        'usuario_id_conselho' => $uId,
        'page' => $page,
        'limit' => $limit,
        'token_found' => false,
        'url' => null,
        'http_code' => null,
        'curl_error' => null,
        'response_preview' => null
    ];

    // Visão Prática exige o Ultra-Login individual do conselheiro
    $token = vds_get_token($usuarioIdConselho, false);
    $debug['token_found'] = !empty($token);

    if (!$token) {
        return [
            'success' => false,
            'message' => 'Ultra-Login não ativado para o seu usuário (ID ' . $uId . '). Acesse Configurações VDS para conectar.',
            'items' => [],
            'page' => $page,
            'limit' => $limit,
            'totalRegs' => 0,
            'hasMore' => false,
            'debug' => $debug
        ];
    }

    $url = VDS_BASE_URL . '/ocorrencia?page=' . $page . '&limit=' . $limit . '&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0';
    $debug['url'] = $url;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RecManVDS/1.0',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Origin: ' . VDS_ORIGIN_HEADER,
            'Accept: application/json, text/plain, */*'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $debug['http_code'] = $httpCode;
    $debug['curl_error'] = $curlErr;
    $debug['response_preview'] = substr((string)$response, 0, 1000);

    if ($httpCode === 401) {
        vds_mark_token_expired($token);
        $retryToken = vds_get_token($usuarioIdConselho, false);
        if ($retryToken && $retryToken !== $token) {
            $chRetry = curl_init($url);
            curl_setopt_array($chRetry, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RecManVDS/1.0',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $retryToken,
                    'Origin: ' . VDS_ORIGIN_HEADER,
                    'Accept: application/json, text/plain, */*'
                ]
            ]);
            $response = curl_exec($chRetry);
            $httpCode = curl_getinfo($chRetry, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($chRetry);
            curl_close($chRetry);

            $debug['http_code'] = $httpCode;
            $debug['curl_error'] = $curlErr;
            $debug['response_preview'] = substr((string)$response, 0, 1000);
        }
    }

    if ($httpCode !== 200 || !$response) {
        $errDetail = !empty($curlErr) ? "cURL: {$curlErr}" : "HTTP {$httpCode}";
        return [
            'success' => false,
            'httpCode' => $httpCode,
            'message' => "Erro ao consultar chamados não lidos na VDS ({$errDetail}).",
            'items' => [],
            'page' => $page,
            'limit' => $limit,
            'totalRegs' => 0,
            'hasMore' => false,
            'debug' => $debug
        ];
    }

    $data = json_decode($response, true);
    $rawItems = $data['regs'] ?? ($data['items'] ?? ($data['data'] ?? (is_array($data) ? $data : [])));
    $totalRegs = (int)($data['totalRegs'] ?? ($data['total'] ?? count($rawItems)));
    $hasMore = ($page * $limit) < $totalRegs;

    $items = [];
    $link = DBConnect();

    foreach ($rawItems as $item) {
        $localId = vds_persist_item_local($item, $link);

        // Como estes itens vieram da VDS via consulta de não lidos (Lida=0),
        // garantir no banco relacional que o status deste conselheiro seja lido = 0 (Não Lido)
        if ($localId > 0) {
            vds_ensure_leitura_table_exists($link);
            $uuidVal = $item['uuid'] ?? ($item['uuid_remoto'] ?? null);
            $uuidEsc = mysqli_real_escape_string($link, (string)$uuidVal);
            @mysqli_query($link, "INSERT INTO ocorrencia_leitura_conselheiro (conselheiro_id, ocorrencia_id, uuid_remoto, lido, sincronizado_remoto) VALUES ({$uId}, {$localId}, '{$uuidEsc}', 0, 1) ON DUPLICATE KEY UPDATE lido = 0, sincronizado_remoto = 1");
        }

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

    return [
        'success' => true,
        'items' => $items,
        'page' => $page,
        'limit' => $limit,
        'totalRegs' => $totalRegs,
        'hasMore' => $hasMore,
        'debug' => $debug
    ];
}

/**
 * Marca uma ocorrência como lida localmente no controle relacional do conselheiro
 * e tenta o envio síncrono/assíncrono para a VDS API.
 */
function vds_marcar_como_lido($uuidRemoto, $usuarioIdConselho = null, $ocorrenciaId = null, $novoStatusLido = true) {
    $uId = (int)($usuarioIdConselho ?? 1);
    $ocoId = (int)$ocorrenciaId;
    $lidoVal = $novoStatusLido ? 1 : 0;

    $link = DBConnect();
    vds_ensure_leitura_table_exists($link);

    if (!$ocoId && !empty($uuidRemoto)) {
        $stmtF = mysqli_prepare($link, "SELECT id FROM ocorrencias WHERE uuid_remoto = ? LIMIT 1");
        if ($stmtF) {
            mysqli_stmt_bind_param($stmtF, "s", $uuidRemoto);
            mysqli_stmt_execute($stmtF);
            $resF = mysqli_stmt_get_result($stmtF);
            $rF = mysqli_fetch_assoc($resF);
            if ($rF) { $ocoId = (int)$rF['id']; }
            mysqli_stmt_close($stmtF);
        }
    }

    if ($ocoId > 0) {
        $stmtUpsert = mysqli_prepare($link, "INSERT INTO ocorrencia_leitura_conselheiro (conselheiro_id, ocorrencia_id, uuid_remoto, lido, sincronizado_remoto, read_at) VALUES (?, ?, ?, ?, 0, NOW()) ON DUPLICATE KEY UPDATE lido = VALUES(lido), sincronizado_remoto = 0, read_at = NOW()");
        if ($stmtUpsert) {
            mysqli_stmt_bind_param($stmtUpsert, "iisi", $uId, $ocoId, $uuidRemoto, $lidoVal);
            mysqli_stmt_execute($stmtUpsert);
            mysqli_stmt_close($stmtUpsert);
        }
    }
    DBClose($link);

    // Tentar envio à VDS via PUT com timeout curto (5s) se tiver Ultra-Login
    $token = vds_get_token($usuarioIdConselho, false);
    if ($token && $uuidRemoto) {
        $urlLeitura = VDS_BASE_URL . '/ocorrencia/leitura/' . urlencode($uuidRemoto);
        $ch = curl_init($urlLeitura);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Length: 0',
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 204) {
            $link2 = DBConnect();
            $stmtUpSync = mysqli_prepare($link2, "UPDATE ocorrencia_leitura_conselheiro SET sincronizado_remoto = 1 WHERE conselheiro_id = ? AND ocorrencia_id = ?");
            if ($stmtUpSync) {
                mysqli_stmt_bind_param($stmtUpSync, "ii", $uId, $ocoId);
                mysqli_stmt_execute($stmtUpSync);
                mysqli_stmt_close($stmtUpSync);
            }
            DBClose($link2);
        }
    }

    return ['success' => true, 'message' => 'Status de leitura atualizado localmente!'];
}

/**
 * Descarrega a fila de marcações de leitura pendentes enviando-as para a VDS API.
 */
function vds_flush_pending_reads($usuarioIdConselho = null) {
    $link = DBConnect();
    vds_ensure_leitura_table_exists($link);

    $sql = "SELECT l.*, o.uuid_remoto as oco_uuid FROM ocorrencia_leitura_conselheiro l
            LEFT JOIN ocorrencias o ON o.id = l.ocorrencia_id
            WHERE l.sincronizado_remoto = 0 AND (l.uuid_remoto IS NOT NULL OR o.uuid_remoto IS NOT NULL)";
    if ($usuarioIdConselho) {
        $sql .= " AND l.conselheiro_id = " . (int)$usuarioIdConselho;
    }
    $sql .= " LIMIT 50";

    $res = mysqli_query($link, $sql);
    $pending = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $pending[] = $r;
        }
    }
    DBClose($link);

    $flushedCount = 0;
    foreach ($pending as $p) {
        $cId = (int)$p['conselheiro_id'];
        $uuid = !empty($p['uuid_remoto']) ? $p['uuid_remoto'] : ($p['oco_uuid'] ?? null);

        if (!$uuid) continue;

        $token = vds_get_token($cId, false);
        if (!$token) continue;

        $urlLeitura = VDS_BASE_URL . '/ocorrencia/leitura/' . urlencode($uuid);
        $ch = curl_init($urlLeitura);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Length: 0',
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 204) {
            $link2 = DBConnect();
            $stmtUp = mysqli_prepare($link2, "UPDATE ocorrencia_leitura_conselheiro SET sincronizado_remoto = 1 WHERE id = ?");
            if ($stmtUp) {
                mysqli_stmt_bind_param($stmtUp, "i", $p['id']);
                mysqli_stmt_execute($stmtUp);
                mysqli_stmt_close($stmtUp);
            }
            DBClose($link2);
            $flushedCount++;
        }
    }

    return $flushedCount;
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

/**
 * Varre o banco local por ocorrências legadas (sem protocolo_vds ou sem uuid_remoto)
 * e realiza a busca na API VDS para preencher protocolo_vds, uuid_remoto, oco_tipo e dados_json.
 */
function vds_enrich_legacy_ocorrencias($usuarioIdConselho = null) {
    $link = DBConnect();

    // 1. Garantir que protocolo_vds seja populado com o id numérico legado para onde estivesse NULL
    @mysqli_query($link, "UPDATE ocorrencias SET protocolo_vds = CAST(id AS CHAR) WHERE (protocolo_vds IS NULL OR protocolo_vds = '') AND id > 0");

    // 2. Buscar ocorrências que ainda não possuem uuid_remoto ou dados_json
    $res = mysqli_query($link, "SELECT id, protocolo_vds FROM ocorrencias WHERE (uuid_remoto IS NULL OR uuid_remoto = '' OR dados_json IS NULL) AND id > 0 ORDER BY id DESC");

    $count = 0;
    $total = 0;

    if ($res) {
        $total = mysqli_num_rows($res);
        while ($row = mysqli_fetch_assoc($res)) {
            $proto = $row['protocolo_vds'] ?: (string)$row['id'];
            if (!empty($proto)) {
                // Tenta consultar detalhe via API v8 da VDS (que já faz a busca por protocolo e atualiza o banco local)
                $detalhe = vds_get_ocorrencia_detalhe($proto, $usuarioIdConselho);
                if ($detalhe && !empty($detalhe['local']['uuid_remoto'])) {
                    $count++;
                }
            }
        }
    }

    DBClose($link);
    return ['success' => true, 'updated' => $count, 'total' => $total];
}

