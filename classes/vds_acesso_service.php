<?php

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/vds_auth_service.php";

/**
 * Garante e resolve os UUIDs de Bloco e Unidade na API v8 da VDS,
 * persistindo-os na tabela `vds_uuid_mapping` para acelerar consultas futuras.
 */
function vds_resolve_bloco_unidade_uuid($bloco, $unidade, $usuarioIdConselho = null) {
    if (empty($bloco) || empty($unidade)) {
        return ['blocoUuid' => null, 'unidadeUuid' => null];
    }

    $blocoClean = strtoupper(trim(str_replace(['bloco', 'bl.', 'bl'], '', strtolower($bloco))));
    $unidadeClean = trim($unidade);
    $chaveUnidade = $blocoClean . ":" . $unidadeClean;

    $link = DBConnect();

    // 1. Tentar buscar no cache local (vds_uuid_mapping)
    $blocoUuid = null;
    $unidadeUuid = null;

    $stmtU = mysqli_prepare($link, "SELECT uuid_remoto FROM vds_uuid_mapping WHERE entidade_tipo = 'unidade' AND chave_local = ? LIMIT 1");
    if ($stmtU) {
        mysqli_stmt_bind_param($stmtU, "s", $chaveUnidade);
        mysqli_stmt_execute($stmtU);
        $resU = mysqli_stmt_get_result($stmtU);
        if ($rowU = mysqli_fetch_assoc($resU)) {
            $unidadeUuid = $rowU['uuid_remoto'];
        }
        mysqli_stmt_close($stmtU);
    }

    $stmtB = mysqli_prepare($link, "SELECT uuid_remoto FROM vds_uuid_mapping WHERE entidade_tipo = 'bloco' AND chave_local = ? LIMIT 1");
    if ($stmtB) {
        mysqli_stmt_bind_param($stmtB, "s", $blocoClean);
        mysqli_stmt_execute($stmtB);
        $resB = mysqli_stmt_get_result($stmtB);
        if ($rowB = mysqli_fetch_assoc($resB)) {
            $blocoUuid = $rowB['uuid_remoto'];
        }
        mysqli_stmt_close($stmtB);
    }

    // Se a unidade já tiver UUID cadastrado no cache, retornar imediatamente
    if (!empty($unidadeUuid)) {
        DBClose($link);
        return ['blocoUuid' => $blocoUuid, 'unidadeUuid' => $unidadeUuid];
    }

    // 2. Se não encontrou no cache local, consultar API VDS via Bearer Token
    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        DBClose($link);
        return ['blocoUuid' => $blocoUuid, 'unidadeUuid' => $unidadeUuid];
    }

    // 2a. Se ainda não temos o blocoUuid, consultar GET /bloco
    if (empty($blocoUuid)) {
        $chB = curl_init(VDS_BASE_URL . '/bloco?Combo=True&IsAdmin=false');
        curl_setopt_array($chB, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $respB = curl_exec($chB);
        $httpB = curl_getinfo($chB, CURLINFO_HTTP_CODE);
        curl_close($chB);

        if ($httpB === 200 && $respB) {
            $dataB = json_decode($respB, true);
            $listBlocos = $dataB['regs'] ?? ($dataB['data'] ?? (is_array($dataB) ? $dataB : []));
            foreach ($listBlocos as $bItem) {
                $bNome = strtoupper(trim(str_replace(['bloco', 'bl.', 'bl'], '', strtolower($bItem['nome'] ?? ($bItem['sigla'] ?? ($bItem['descricao'] ?? ''))))));
                $bUuid = $bItem['uuid'] ?? ($bItem['id'] ?? null);
                if ($bUuid && ($bNome === $blocoClean || strpos($bNome, $blocoClean) !== false || strpos($blocoClean, $bNome) !== false)) {
                    $blocoUuid = $bUuid;
                    // Salvar bloco no cache local
                    $stmtInsB = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto) VALUES ('bloco', ?, ?) ON DUPLICATE KEY UPDATE uuid_remoto = VALUES(uuid_remoto)");
                    if ($stmtInsB) {
                        mysqli_stmt_bind_param($stmtInsB, "ss", $blocoClean, $blocoUuid);
                        mysqli_stmt_execute($stmtInsB);
                        mysqli_stmt_close($stmtInsB);
                    }
                    break;
                }
            }
        }
    }

    // 2b. Se temos o blocoUuid, consultar GET /unidade?Combo=True&bloco.uuid={blocoUuid}
    if (!empty($blocoUuid)) {
        $chU = curl_init(VDS_BASE_URL . '/unidade?Combo=True&bloco.uuid=' . urlencode($blocoUuid));
        curl_setopt_array($chU, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $respU = curl_exec($chU);
        $httpU = curl_getinfo($chU, CURLINFO_HTTP_CODE);
        curl_close($chU);

        if ($httpU === 200 && $respU) {
            $dataU = json_decode($respU, true);
            $listUnidades = $dataU['regs'] ?? ($dataU['data'] ?? (is_array($dataU) ? $dataU : []));
            foreach ($listUnidades as $uItem) {
                $uNum = trim($uItem['numero'] ?? ($uItem['nome'] ?? ($uItem['descricao'] ?? '')));
                $uUuid = $uItem['uuid'] ?? ($uItem['id'] ?? null);
                if ($uUuid && ($uNum === $unidadeClean || ltrim($uNum, '0') === ltrim($unidadeClean, '0'))) {
                    $unidadeUuid = $uUuid;
                    // Salvar unidade no cache local
                    $stmtInsU = mysqli_prepare($link, "INSERT INTO vds_uuid_mapping (entidade_tipo, chave_local, uuid_remoto) VALUES ('unidade', ?, ?) ON DUPLICATE KEY UPDATE uuid_remoto = VALUES(uuid_remoto)");
                    if ($stmtInsU) {
                        mysqli_stmt_bind_param($stmtInsU, "ss", $chaveUnidade, $unidadeUuid);
                        mysqli_stmt_execute($stmtInsU);
                        mysqli_stmt_close($stmtInsU);
                    }
                    break;
                }
            }
        }
    }

    DBClose($link);

    return ['blocoUuid' => $blocoUuid, 'unidadeUuid' => $unidadeUuid];
}

/**
 * Consulta eventos de acesso da unidade na VDS para a janela temporal especificada.
 */
function vds_get_eventos_acesso($bloco, $unidade, $dtInicio, $dtFim, $usuarioIdConselho = null) {
    // 1. Resolver UUIDs de Bloco e Unidade silenciosamente
    $uuids = vds_resolve_bloco_unidade_uuid($bloco, $unidade, $usuarioIdConselho);
    $unidadeUuid = $uuids['unidadeUuid'];
    $blocoUuid = $uuids['blocoUuid'];

    $token = vds_get_token($usuarioIdConselho);

    if ($token) {
        $url = VDS_BASE_URL . '/evento_acesso?page=1&limit=50&sortBy=dthora&order=desc'
             . '&dtInicio=' . urlencode($dtInicio)
             . '&dtFim=' . urlencode($dtFim);

        if ($unidadeUuid) {
            $url .= '&unidade.uuid=' . urlencode($unidadeUuid);
        } elseif ($blocoUuid) {
            $url .= '&unidade.bloco.uuid=' . urlencode($blocoUuid);
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

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            $regs = $json['regs'] ?? ($json['data'] ?? ($json['items'] ?? (is_array($json) ? $json : [])));
            if (!empty($regs) && is_array($regs)) {
                $filtrados = [];
                $unidadeClean = trim($unidade);
                foreach ($regs as $acc) {
                    $accUnid = trim($acc['unidade']['numero'] ?? ($acc['unidadeNumero'] ?? ($acc['unidade'] ?? '')));
                    if (empty($accUnid) || $accUnid === $unidadeClean || ltrim($accUnid, '0') === ltrim($unidadeClean, '0')) {
                        $filtrados[] = [
                            'dthora' => $acc['dthora'] ?? ($acc['dtExibicao'] ?? date('Y-m-d H:i:s')),
                            'pessoaNome' => $acc['pessoa']['nome'] ?? ($acc['pessoaNome'] ?? ($acc['nome'] ?? 'Visitante/Morador')),
                            'perfil' => $acc['pessoa']['perfil']['descricao'] ?? ($acc['perfil'] ?? ($acc['tipoPessoa'] ?? 'Acesso')),
                            'tipoEvento' => $acc['tipoEvento']['descricao'] ?? ($acc['tipoEvento'] ?? ($acc['evento'] ?? 'Registro de Acesso')),
                            'fotoUrl' => $acc['pessoa']['fotoUrl'] ?? ($acc['fotoUrl'] ?? ($acc['foto'] ?? ''))
                        ];
                    }
                }
                return !empty($filtrados) ? $filtrados : $regs;
            }
        }
    }

    // Fallback: Mock para testes offline
    $mockPath = __DIR__ . '/../docs/mocks/mock_evento_acesso.json';
    if (file_exists($mockPath)) {
        $mock = json_decode(file_get_contents($mockPath), true);
        return $mock['regs'] ?? ($mock['data'] ?? []);
    }

    return [];
}

/**
 * Extrai valor em formato string caso a propriedade da API seja um objeto/array.
 */
function vds_extract_string_value($val, $default = 'N/A') {
    if (empty($val)) return $default;
    if (is_string($val) || is_numeric($val)) return (string)$val;
    if (is_array($val)) {
        return $val['descricao'] ?? ($val['nome'] ?? ($val['tipo'] ?? ($val['detalhe'] ?? ($val['texto'] ?? $default))));
    }
    return $default;
}

/**
 * Consulta entregas/correspondências recentes da unidade.
 */
function vds_get_entregas_unidade($bloco, $unidade, $usuarioIdConselho = null) {
    // 1. Resolver UUIDs de Bloco e Unidade
    $uuids = vds_resolve_bloco_unidade_uuid($bloco, $unidade, $usuarioIdConselho);
    $unidadeUuid = $uuids['unidadeUuid'];
    $blocoUuid = $uuids['blocoUuid'];

    $token = vds_get_token($usuarioIdConselho);

    if ($token) {
        $url = VDS_BASE_URL . '/entrega?page=1&limit=50&sortBy=dthora&order=desc';
        if ($unidadeUuid) {
            $url .= '&Unidade.Uuid=' . urlencode($unidadeUuid);
        } elseif ($blocoUuid) {
            $url .= '&Bloco.Uuid=' . urlencode($blocoUuid);
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

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            $regs = $json['regs'] ?? ($json['data'] ?? ($json['items'] ?? (is_array($json) ? $json : [])));
            if (!empty($regs) && is_array($regs)) {
                $filtrados = [];
                $unidadeClean = trim($unidade);
                foreach ($regs as $ent) {
                    $eUnid = trim($ent['unidade']['numero'] ?? ($ent['unidadeNumero'] ?? ($ent['unidade'] ?? '')));
                    if (empty($eUnid) || $eUnid === $unidadeClean || ltrim($eUnid, '0') === ltrim($unidadeClean, '0')) {
                        $descStr = vds_extract_string_value($ent['descricao'] ?? ($ent['pacote'] ?? ($ent['tipo'] ?? null)), 'Encomenda / Pacote');
                        $destStr = vds_extract_string_value($ent['destinatario'] ?? ($ent['recebidoPor'] ?? null), 'Morador');
                        $statusStr = vds_extract_string_value($ent['status'] ?? ($ent['situacao'] ?? null), 'Entregue');

                        $filtrados[] = [
                            'dthoraChegada' => !empty($ent['dthora']) ? date('d/m/Y H:i', strtotime($ent['dthora'])) : ($ent['dtExibicao'] ?? 'Recente'),
                            'descricao' => $descStr,
                            'destinatario' => $destStr,
                            'status' => $statusStr
                        ];
                    }
                }
                return !empty($filtrados) ? $filtrados : $regs;
            }
        }
    }

    // Fallback Mock
    $mockPath = __DIR__ . '/../docs/mocks/mock_entrega.json';
    if (file_exists($mockPath)) {
        $mock = json_decode(file_get_contents($mockPath), true);
        return $mock['data'] ?? ($mock['regs'] ?? []);
    }

    return [];
}

/**
 * Busca todos os chamados/ocorrências onde a unidade é autora, reclamada, citada ou possui tag vinculada.
 */
function vds_get_chamados_unidade($bloco, $unidade) {
    $link = DBConnect();
    
    $blocoClean = strtoupper(trim(str_replace(['bloco', 'bl.', 'bl'], '', strtolower($bloco))));
    $unidadeClean = trim($unidade);
    $tagComposta = $blocoClean . $unidadeClean; // ex: A105 ou B1108
    $tagCompostaSep = $blocoClean . "/" . $unidadeClean; // ex: A/105

    $sql = "SELECT DISTINCT o.*, 
                   COALESCE(t.tipo_vinculo, IF(o.bloco = ? AND o.unidade = ?, 'autora', 'citada')) as vinculo_final
            FROM ocorrencias o
            LEFT JOIN ocorrencia_unidade_tag t ON t.ocorrencia_id = o.id
            WHERE (o.bloco = ? AND o.unidade = ?) 
               OR (t.bloco = ? AND t.unidade = ?)
               OR (t.unidade = ? OR t.unidade = ? OR t.unidade = ?)
               OR (o.id IN (
                   SELECT ro.id_ocorrencia 
                   FROM recurso_ocorrencia ro 
                   JOIN recurso r ON r.id = ro.id_recurso 
                   WHERE (r.bloco = ? AND r.unidade = ?)
               ))
            ORDER BY o.abertura DESC";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssss", 
        $blocoClean, $unidadeClean, 
        $blocoClean, $unidadeClean, 
        $blocoClean, $unidadeClean, 
        $tagComposta, $tagCompostaSep, $unidadeClean,
        $blocoClean, $unidadeClean
    );
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $chamados = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $chamados[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    DBClose($link);

    return $chamados;
}

/**
 * Consulta boletos e lançamentos financeiros da unidade na VDS para o ano informado.
 */
function vds_get_boletos_unidade($bloco, $unidade, $ano = null, $usuarioIdConselho = null) {
    if (empty($ano)) {
        $ano = date('Y');
    }

    // 1. Resolver UUIDs de Bloco e Unidade
    $uuids = vds_resolve_bloco_unidade_uuid($bloco, $unidade, $usuarioIdConselho);
    $unidadeUuid = $uuids['unidadeUuid'];
    $blocoUuid = $uuids['blocoUuid'];

    $token = vds_get_token($usuarioIdConselho);

    if ($token && $unidadeUuid && $blocoUuid) {
        $url = VDS_BASE_URL . '/boleto?page=1&limit=50&sortBy=status&order=asc'
             . '&Ano=' . urlencode($ano)
             . '&Bloco.Uuid=' . urlencode($blocoUuid)
             . '&Unidade.Uuid=' . urlencode($unidadeUuid);

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

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            $regs = $json['regs'] ?? ($json['data'] ?? ($json['items'] ?? (is_array($json) ? $json : [])));
            if (is_array($regs)) {
                return $regs;
            }
        }
    }

    // Fallback Mock de testes se offline ou falha
    $mockPath = __DIR__ . '/../docs/mocks/retornos dos endpoints';
    if (file_exists($mockPath)) {
        $mockContent = file_get_contents($mockPath);
        if (preg_match('/5\.1\s*(\{[\s\S]*?\})\s*(?:\/\/|$)/', $mockContent, $m)) {
            $mockData = json_decode($m[1], true);
            return $mockData['regs'] ?? [];
        }
    }

    return [];
}

/**
 * Inspeciona o espelho HTML da 2ª via do Superlógica e extrai sugestões de multa por notificação (Ex: NOT. Nº 210/26).
 */
function vds_extrair_sugestoes_multa_boleto($urlSegundaVia, $boletoStatus = null, $boletoDtVencimento = null) {
    if (empty($urlSegundaVia)) return ['sugestoes' => [], 'error' => 'URL vazia'];

    $ch = curl_init($urlSegundaVia);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_ENCODING => '', // Suporte crucial a compressão GZIP / Deflate do Superlógica
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
        ]
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || empty($html)) {
        return [
            'sugestoes' => [],
            'httpCode' => $httpCode,
            'curlErr' => $curlErr,
            'htmlLength' => strlen($html ?? '')
        ];
    }

    $sugestoes = [];
    $link = DBConnect();

    // 1. Extração iterando sobre cada tag <tr> do HTML
    if (preg_match_all('/<tr[^>]*>([\s\S]*?)<\/tr>/i', $html, $trMatches)) {
        foreach ($trMatches[1] as $trHtml) {
            $itemDesc = null;
            $valorRaw = null;

            if (preg_match('/class=["\']item["\'][^>]*>([\s\S]*?)<\/td>/i', $trHtml, $mItem)) {
                $itemDesc = trim(strip_tags($mItem[1]));
            }
            if (preg_match('/class=["\']valor["\'][^>]*>([\s\S]*?)<\/td>/i', $trHtml, $mValor)) {
                $valorRaw = trim(strip_tags($mValor[1]));
            }

            if ($itemDesc && $valorRaw) {
                // Verificar se o texto contém padrão numero/ano (Ex: 210/26 ou 155/2026)
                if (preg_match('/(\d+)\/(\d{2,4})/', $itemDesc, $nm)) {
                    $numero = $nm[1];
                    $rawAno = $nm[2];
                    $ano = strlen($rawAno) === 2 ? '20' . $rawAno : $rawAno;
                    
                    // Converter valor R$764,53 -> 764.53
                    $valorClean = (float)str_replace(['R$', '.', ' '], ['', '', ''], str_replace(',', '.', $valorRaw));

                    // Buscar se essa notificação existe no banco local e se já foi cobrada
                    $stmt = mysqli_prepare($link, "SELECT id, numero, ano, unidade, torre, multa_cobrada, valor, data_vencimento, data_pagamento FROM notificacoes WHERE numero = ? AND ano = ? LIMIT 1");
                    $jaLancado = false;
                    $notificacaoEncontrada = false;
                    $notificacaoId = "{$numero}/{$ano}";

                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "ss", $numero, $ano);
                        mysqli_stmt_execute($stmt);
                        $res = mysqli_stmt_get_result($stmt);
                        if ($row = mysqli_fetch_assoc($res)) {
                            $notificacaoEncontrada = true;
                            if (!empty($row['multa_cobrada']) && strtoupper($row['multa_cobrada']) === 'SIM' && !empty($row['valor']) && (float)$row['valor'] > 0) {
                                $jaLancado = true;
                            }
                        }
                        mysqli_stmt_close($stmt);
                    }

                    $sugestoes[] = [
                        'numero' => $numero,
                        'ano' => $ano,
                        'numero_ano' => $notificacaoId,
                        'item_descricao' => $itemDesc,
                        'valor' => $valorClean,
                        'valor_formatado' => 'R$ ' . number_format($valorClean, 2, ',', '.'),
                        'boleto_status' => $boletoStatus ?? 'Desconhecido',
                        'data_vencimento' => $boletoDtVencimento ?? date('Y-m-d'),
                        'data_pagamento_sugerida' => (strpos(strtolower($boletoStatus ?? ''), 'liquidado') !== false || strpos(strtolower($boletoStatus ?? ''), 'pago') !== false) ? ($boletoDtVencimento ?? date('Y-m-d')) : null,
                        'notificacao_encontrada' => $notificacaoEncontrada,
                        'ja_lancado' => $jaLancado,
                        'url_boleto' => $urlSegundaVia
                    ];
                }
            }
        }
    }

    DBClose($link);
    return [
        'sugestoes' => $sugestoes,
        'httpCode' => $httpCode,
        'htmlLength' => strlen($html)
    ];
}
