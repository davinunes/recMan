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
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $respB = curl_exec($chB);
        $httpB = curl_getinfo($chB, CURLINFO_HTTP_CODE);
        curl_close($chB);

        if ($httpB === 401) {
            vds_mark_token_expired($token);
        }

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
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Origin: ' . VDS_ORIGIN_HEADER
            ]
        ]);
        $respU = curl_exec($chU);
        $httpU = curl_getinfo($chU, CURLINFO_HTTP_CODE);
        curl_close($chU);

        if ($httpU === 401) {
            vds_mark_token_expired($token);
        }

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
/**
 * Formata com segurança strings de data/hora vindas da API VDS em múltiplos formatos (ISO ou BR).
 * Evita que strtotime("DD/MM/YYYY") retorne false e vire 31/12/1969.
 */
function vds_format_datetime($rawDate, $format = 'd/m/Y H:i', $default = 'N/A') {
    if (empty($rawDate) || is_array($rawDate)) return $default;

    $rawStr = trim((string)$rawDate);
    if (empty($rawStr)) return $default;

    // Converter barras em traços para o strtotime interpretar como formato europeu/BR (DD-MM-YYYY)
    $normalized = str_replace('/', '-', $rawStr);
    $ts = strtotime($normalized);

    if ($ts !== false && $ts > 0) {
        return date($format, $ts);
    }

    // Se já estiver formatada em BR legível (ex: "29/07/2026 13:54")
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $rawStr)) {
        return $rawStr;
    }

    return $default;
}

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
            if (is_array($regs)) {
                $filtrados = [];
                $unidadeClean = trim($unidade);
                foreach ($regs as $acc) {
                    $accUnid = trim(is_array($acc['unidade'] ?? null) ? ($acc['unidade']['numero'] ?? ($acc['unidade']['nome'] ?? '')) : ($acc['unidade'] ?? ($acc['unidadeNumero'] ?? '')));
                    if (($accUnid !== '' && ($accUnid === $unidadeClean || ltrim($accUnid, '0') === ltrim($unidadeClean, '0'))) || ($accUnid === '' && !empty($unidadeUuid))) {
                        $fotoRel = $acc['foto'] ?? ($acc['fotoUrl'] ?? ($acc['pessoa']['fotoUrl'] ?? null));
                        $fotoUrl = !empty($fotoRel) ? (strpos($fotoRel, 'http') === 0 ? $fotoRel : 'https://app.vidadesindico.com.br' . $fotoRel) : null;

                        $pessoaNome = $acc['moradorNome'] ?? ($acc['pessoa']['nome'] ?? ($acc['pessoaNome'] ?? ($acc['nome'] ?? 'Visitante/Morador')));
                        $perfil = $acc['moradorTipo'] ?? ($acc['pessoa']['perfil']['descricao'] ?? ($acc['perfil'] ?? ($acc['tipoPessoa'] ?? 'Acesso')));
                        
                        $dispositivo = $acc['dispositivo'] ?? '';
                        $receptor = $acc['receptor'] ?? '';
                        $modulo = $acc['modulo'] ?? '';
                        $saida = $acc['saida'] ?? '';
                        $statusAcesso = $acc['nome'] ?? 'Acesso';

                        $detalheEvento = $statusAcesso;
                        $tagsContexto = array_filter([$modulo, $saida, $dispositivo, $receptor]);
                        if (!empty($tagsContexto)) {
                            $detalheEvento .= ' (' . implode(' • ', $tagsContexto) . ')';
                        }

                        $filtrados[] = [
                            'uuid' => $acc['uuid'] ?? null,
                            'dthora' => vds_format_datetime($acc['dthora'] ?? ($acc['dtExibicao'] ?? null), 'd/m/Y H:i:s'),
                            'pessoaNome' => $pessoaNome,
                            'perfil' => $perfil,
                            'tipoEvento' => $detalheEvento,
                            'statusAcesso' => $statusAcesso,
                            'modulo' => $modulo,
                            'saida' => $saida,
                            'dispositivo' => $dispositivo,
                            'receptor' => $receptor,
                            'descricao' => $acc['descricao'] ?? '',
                            'fotoUrl' => $fotoUrl
                        ];
                    }
                }
                return $filtrados;
            }
        }
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
function vds_get_entregas_unidade($bloco, $unidade, $dtIni = null, $dtFim = null, $usuarioIdConselho = null) {
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
        if ($dtIni) {
            $url .= '&dtInicio=' . urlencode($dtIni);
        }
        if ($dtFim) {
            $url .= '&dtFim=' . urlencode($dtFim);
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
            if (is_array($regs)) {
                $filtrados = [];
                $unidadeClean = trim($unidade);
                foreach ($regs as $ent) {
                    $eUnid = trim(is_array($ent['unidade'] ?? null) ? ($ent['unidade']['numero'] ?? ($ent['unidade']['nome'] ?? '')) : ($ent['unidade'] ?? ($ent['unidadeNumero'] ?? '')));
                    if (($eUnid !== '' && ($eUnid === $unidadeClean || ltrim($eUnid, '0') === ltrim($unidadeClean, '0'))) || ($eUnid === '' && !empty($unidadeUuid))) {
                        $descStr = vds_extract_string_value($ent['descricao'] ?? ($ent['pacote'] ?? ($ent['tipo'] ?? null)), 'Encomenda / Pacote');
                        $destStr = vds_extract_string_value($ent['destinatario'] ?? ($ent['recebidoPor'] ?? null), 'Morador');
                        $statusStr = vds_extract_string_value($ent['status'] ?? ($ent['situacao'] ?? null), 'Entregue');
                        $fotoRel = $ent['foto'] ?? null;
                        $fotoUrl = !empty($fotoRel) ? (strpos($fotoRel, 'http') === 0 ? $fotoRel : 'https://app.vidadesindico.com.br' . $fotoRel) : null;

                        $rawDtChegada = $ent['dthora'] ?? ($ent['dtCadastro'] ?? ($ent['dtExibicao'] ?? null));

                        $filtrados[] = [
                            'uuid' => $ent['uuid'] ?? ($ent['id'] ?? null),
                            'dthoraChegada' => vds_format_datetime($rawDtChegada, 'd/m/Y H:i', 'Recente'),
                            'descricao' => $descStr,
                            'destinatario' => $destStr,
                            'identificador' => $ent['identificador'] ?? ($ent['codigoRastreio'] ?? null),
                            'foto' => $fotoUrl,
                            'status' => $statusStr
                        ];
                    }
                }
                return $filtrados;
            }
        }
    }

    return [];
}

/**
 * Obtém os detalhes completos de uma entrega/encomenda por UUID na API v8 da VDS.
 */
function vds_get_entrega_detalhe($uuid, $usuarioIdConselho = null) {
    if (empty($uuid)) return ['success' => false, 'message' => 'UUID da entrega é obrigatório'];

    $token = vds_get_token($usuarioIdConselho);
    if (!$token) {
        return ['success' => false, 'message' => 'Token VDS indisponível'];
    }

    $url = VDS_BASE_URL . '/entrega/' . urlencode($uuid);
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
        $data = json_decode($response, true);
        if ($data) {
            if (!empty($data['foto']) && strpos($data['foto'], 'http') !== 0) {
                $data['fotoUrlCompleta'] = 'https://app.vidadesindico.com.br' . $data['foto'];
            } else {
                $data['fotoUrlCompleta'] = $data['foto'] ?? null;
            }

            if (!empty($data['dthora'])) {
                $data['dthoraFormatada'] = vds_format_datetime($data['dthora'], 'd/m/Y H:i');
            }
            if (!empty($data['dtFim'])) {
                $data['dtFimFormatada'] = vds_format_datetime($data['dtFim'], 'd/m/Y H:i');
            }

            return ['success' => true, 'data' => $data];
        }
    }

    return ['success' => false, 'httpCode' => $httpCode, 'message' => 'Entrega não encontrada ou erro na API VDS'];
}

/**
 * Consulta autorizações de acesso e convites prévios da unidade na API v8 da VDS.
 */
function vds_get_autorizacoes_acesso($bloco, $unidade, $dtIni = null, $dtFim = null, $usuarioIdConselho = null) {
    // 1. Resolver UUIDs de Bloco e Unidade
    $uuids = vds_resolve_bloco_unidade_uuid($bloco, $unidade, $usuarioIdConselho);
    $unidadeUuid = $uuids['unidadeUuid'];
    $blocoUuid = $uuids['blocoUuid'];

    $token = vds_get_token($usuarioIdConselho);

    if ($token) {
        $url = VDS_BASE_URL . '/autorizacao_acesso?page=1&limit=50&sortBy=nome&order=asc';
        if ($unidadeUuid) {
            $url .= '&Unidade.Uuid=' . urlencode($unidadeUuid);
        } elseif ($blocoUuid) {
            $url .= '&Bloco.Uuid=' . urlencode($blocoUuid);
        }
        if ($dtIni) {
            $url .= '&dtIni=' . urlencode($dtIni);
        }
        if ($dtFim) {
            $url .= '&dtFim=' . urlencode($dtFim);
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
            if (is_array($regs)) {
                $processados = [];
                $unidadeClean = trim($unidade);
                foreach ($regs as $aut) {
                    $aUnid = trim(is_array($aut['unidade'] ?? null) ? ($aut['unidade']['numero'] ?? ($aut['unidade']['nome'] ?? '')) : ($aut['unidade'] ?? ($aut['unidadeNumero'] ?? '')));
                    if (($aUnid !== '' && ($aUnid === $unidadeClean || ltrim($aUnid, '0') === ltrim($unidadeClean, '0'))) || ($aUnid === '' && !empty($unidadeUuid))) {
                        $fotoRel = $aut['foto'] ?? null;
                        $fotoUrl = !empty($fotoRel) ? (strpos($fotoRel, 'http') === 0 ? $fotoRel : 'https://app.vidadesindico.com.br' . $fotoRel) : null;
                        
                        $docStr = 'N/A';
                        if (!empty($aut['documento']) && is_array($aut['documento'])) {
                            $docTipo = strtoupper($aut['documento']['tipo'] ?? 'DOC');
                            $docNum = $aut['documento']['documento'] ?? '';
                            $docStr = $docTipo . ': ' . $docNum;
                        }

                        $processados[] = [
                            'uuid' => $aut['uuid'] ?? null,
                            'nome' => $aut['nome'] ?? 'Visitante / Prestador',
                            'foto' => $fotoUrl,
                            'documento' => $docStr,
                            'destino' => $aut['destino'] ?? '',
                            'dtInicio' => vds_format_datetime($aut['dtInicio'] ?? null, 'd/m/Y H:i'),
                            'dtFim' => vds_format_datetime($aut['dtFim'] ?? null, 'd/m/Y H:i'),
                            'autorizadoPor' => $aut['autorizadoPor']['nome'] ?? 'Morador',
                            'registradoPor' => $aut['registradoPor']['nome'] ?? 'Portaria',
                            'status' => $aut['status']['nome'] ?? ($aut['status'] ?? 'Ativo'),
                            'chave' => $aut['chave'] ?? null,
                            'dtHora' => vds_format_datetime($aut['dtHora'] ?? null, 'd/m/Y H:i:s')
                        ];
                    }
                }
                return $processados;
            }
        }
    }

    return [];
}

/**
 * Consulta reservas de área comum da unidade na API v8 da VDS.
 */
function vds_get_reservas_unidade($bloco, $unidade, $dtIni = null, $dtFim = null, $usuarioIdConselho = null) {
    $uuids = vds_resolve_bloco_unidade_uuid($bloco, $unidade, $usuarioIdConselho);
    $unidadeUuid = $uuids['unidadeUuid'];
    $blocoUuid = $uuids['blocoUuid'];

    $token = vds_get_token($usuarioIdConselho);

    if ($token) {
        $url = VDS_BASE_URL . '/reserva?page=1&limit=50&sortBy=dtReserva&order=desc';
        if ($unidadeUuid) {
            $url .= '&Unidade.Uuid=' . urlencode($unidadeUuid);
        } elseif ($blocoUuid) {
            $url .= '&Bloco.Uuid=' . urlencode($blocoUuid);
        }
        if ($dtIni) {
            $url .= '&DtIni=' . urlencode($dtIni);
        }
        if ($dtFim) {
            $url .= '&DtFim=' . urlencode($dtFim);
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
            if (is_array($regs)) {
                $processados = [];
                $unidadeClean = trim($unidade);
                foreach ($regs as $res) {
                    $rUnid = trim(is_array($res['unidade'] ?? null) ? ($res['unidade']['numero'] ?? ($res['unidade']['nome'] ?? '')) : ($res['unidade'] ?? ($res['unidadeNumero'] ?? '')));
                    if (($rUnid !== '' && ($rUnid === $unidadeClean || ltrim($rUnid, '0') === ltrim($unidadeClean, '0'))) || ($rUnid === '' && !empty($unidadeUuid))) {
                        $recursoNome = vds_extract_string_value($res['recurso'] ?? ($res['areaComum'] ?? null), 'Área Comum');
                        $rawDtReserva = $res['dtReserva'] ?? ($res['data'] ?? ($res['dtInicio'] ?? null));
                        $statusStr = vds_extract_string_value($res['status'] ?? ($res['situacao'] ?? null), 'Confirmada');
                        $valorStr = isset($res['valor']) ? ('R$ ' . number_format((float)$res['valor'], 2, ',', '.')) : null;

                        $horaInicio = $res['horaInicio'] ?? ($res['dtInicio'] ?? '');
                        $horaFim = $res['horaFim'] ?? ($res['dtFim'] ?? '');
                        $horarioStr = (!empty($horaInicio) && !empty($horaFim)) ? "{$horaInicio} - {$horaFim}" : '';

                        $processados[] = [
                            'uuid' => $res['uuid'] ?? ($res['id'] ?? null),
                            'recurso' => $recursoNome,
                            'dtReserva' => vds_format_datetime($rawDtReserva, 'd/m/Y'),
                            'horario' => $horarioStr,
                            'status' => $statusStr,
                            'valor' => $valorStr,
                            'solicitadoPor' => $res['morador']['nome'] ?? ($res['solicitadoPor'] ?? 'Morador')
                        ];
                    }
                }
                return $processados;
            }
        }
    }

    return [];
}

/**
 * Consulta moradores da unidade na API v8 da VDS e verifica o status de inadimplência.
 */
function vds_get_moradores_unidade($bloco, $unidade, $usuarioIdConselho = null) {
    $uuids = vds_resolve_bloco_unidade_uuid($bloco, $unidade, $usuarioIdConselho);
    $unidadeUuid = $uuids['unidadeUuid'];
    $blocoUuid = $uuids['blocoUuid'];

    $token = vds_get_token($usuarioIdConselho);
    $moradores = [];
    $inadimplente = false;

    if ($token && $unidadeUuid) {
        $url = VDS_BASE_URL . '/morador?Unidade.Uuid=' . urlencode($unidadeUuid) . '&Combo=true&status=true&sortBy=Pessoa.Nome&order=asc';

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
                foreach ($regs as $m) {
                    $nome = $m['pessoa']['nome'] ?? ($m['nome'] ?? ($m['morador']['nome'] ?? 'Morador'));
                    
                    $fotoRel = $m['pessoa']['foto'] ?? ($m['foto'] ?? ($m['fotoUrl'] ?? ($m['pessoa']['fotoUrl'] ?? null)));
                    $fotoUrl = !empty($fotoRel) ? (strpos($fotoRel, 'http') === 0 ? $fotoRel : 'https://app.vidadesindico.com.br' . $fotoRel) : null;

                    $rawTipo = $m['tipo'] ?? ($m['tipoDescricao'] ?? ($m['perfil'] ?? ($m['cargo'] ?? null)));
                    if (is_array($rawTipo)) {
                        $tipoStr = $rawTipo['nome'] ?? ($rawTipo['grupo']['nome'] ?? 'Morador');
                    } else {
                        $tipoStr = vds_extract_string_value($rawTipo, 'Morador');
                    }

                    $isUnidInad = !empty($m['unidade']['inadimplente']) || !empty($m['inadimplente']) || !empty($m['inadimplencia']) || (isset($m['adimplente']) && $m['adimplente'] === false);
                    if ($isUnidInad) {
                        $inadimplente = true;
                    }

                    $moradores[] = [
                        'uuid' => $m['uuid'] ?? ($m['id'] ?? null),
                        'nome' => $nome,
                        'foto' => $fotoUrl,
                        'tipo' => $tipoStr,
                        'inadimplente' => $isUnidInad
                    ];
                }
            }

        }
    }

    return [
        'moradores' => $moradores,
        'inadimplente' => $inadimplente
    ];
}

/**
 * Consulta veículos cadastrados para a unidade na API v8 da VDS.
 */
function vds_get_veiculos_unidade($bloco, $unidade, $usuarioIdConselho = null) {
    $uuids = vds_resolve_bloco_unidade_uuid($bloco, $unidade, $usuarioIdConselho);
    $unidadeUuid = $uuids['unidadeUuid'];

    $token = vds_get_token($usuarioIdConselho);
    $veiculos = [];

    if ($token && $unidadeUuid) {
        $url = VDS_BASE_URL . '/veiculo?Unidade.Uuid=' . urlencode($unidadeUuid) . '&order=asc';

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
                foreach ($regs as $item) {
                    $auto = $item['auto'] ?? $item;
                    $placa = $auto['placa'] ?? 'N/A';
                    $marca = is_array($auto['marca'] ?? null) ? ($auto['marca']['nome'] ?? '') : ($auto['marca'] ?? '');
                    $modelo = is_array($auto['modelo'] ?? null) ? ($auto['modelo']['nome'] ?? '') : ($auto['modelo'] ?? '');
                    $cor = is_array($auto['cor'] ?? null) ? ($auto['cor']['nome'] ?? '') : ($auto['cor'] ?? '');
                    $tipo = is_array($auto['tipo'] ?? null) ? ($auto['tipo']['nome'] ?? 'Automóvel') : ($auto['tipo'] ?? 'Automóvel');
                    
                    $proprietario = is_array($auto['proprietario'] ?? null) ? ($auto['proprietario']['nome'] ?? null) : ($auto['proprietario'] ?? null);
                    $observacao = $auto['observacao'] ?? '';

                    $fotoRel = $auto['foto'] ?? null;
                    $fotoUrl = !empty($fotoRel) ? (strpos($fotoRel, 'http') === 0 ? $fotoRel : 'https://app.vidadesindico.com.br' . $fotoRel) : null;

                    $rawDtHora = $item['dtHora'] ?? ($item['dthora'] ?? null);

                    $veiculos[] = [
                        'uuid' => $item['uuid'] ?? ($auto['uuid'] ?? null),
                        'placa' => strtoupper($placa),
                        'marca' => strtoupper($marca),
                        'modelo' => $modelo,
                        'cor' => $cor,
                        'tipo' => $tipo,
                        'proprietario' => $proprietario,
                        'observacao' => $observacao,
                        'foto' => $fotoUrl,
                        'dtHora' => vds_format_datetime($rawDtHora, 'd/m/Y H:i', 'N/A')
                    ];
                }
            }
        }
    }

    return $veiculos;
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

    // Lista de variantes de URL (HTML SPA web vs Espelho estático direto Superlógica)
    $urlsParaTestar = [$urlSegundaVia];
    if (preg_match('/-FaturaHtml-flSegundaVia$/i', $urlSegundaVia)) {
        // Testar variante estática sem a casca SPA (-FaturaHtml-flSegundaVia)
        $urlsParaTestar[] = preg_replace('/-FaturaHtml-flSegundaVia$/i', '', $urlSegundaVia);
        $urlsParaTestar[] = preg_replace('/-FaturaHtml-flSegundaVia$/i', '-flSegundaVia', $urlSegundaVia);
    }

    $html = null;
    $httpCode = 0;
    $curlErr = null;
    $urlSucesso = null;

    foreach ($urlsParaTestar as $targetUrl) {
        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_ENCODING => '', // Suporte a compressão GZIP / Deflate do Superlógica
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
            ]
        ]);

        $resHtml = curl_exec($ch);
        $resCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $resErr = curl_error($ch);
        curl_close($ch);

        if ($resCode === 200 && !empty($resHtml)) {
            // Se encontrar a tag de composição ou tabela no HTML, priorizar esta resposta
            if (preg_match('/corpoComposicao|aria-label=["\']Composição["\']|class=["\']item["\']/i', $resHtml)) {
                $html = $resHtml;
                $httpCode = $resCode;
                $urlSucesso = $targetUrl;
                break;
            } elseif (!$html) {
                // Fallback para qualquer resposta HTTP 200 válida
                $html = $resHtml;
                $httpCode = $resCode;
                $urlSucesso = $targetUrl;
            }
        } else {
            $httpCode = $resCode;
            $curlErr = $resErr;
        }
    }

    if (empty($html)) {
        return [
            'sugestoes' => [],
            'httpCode' => $httpCode,
            'curlErr' => $curlErr,
            'htmlLength' => 0
        ];
    }

    $sugestoes = [];
    $link = DBConnect();

    // 1. Extração Estruturada: Iterar sobre cada <tr> do HTML
    if (preg_match_all('/<tr[^>]*>([\s\S]*?)<\/tr>/i', $html, $trMatches)) {
        foreach ($trMatches[1] as $trHtml) {
            $textClean = trim(preg_replace('/\s+/', ' ', strip_tags($trHtml)));

            // Garantir que a linha contenha termos de multa/penalidade para evitar capturar taxas ordinárias (Ex: 08/2026)
            $isMulta = preg_match('/multa|infra[çc]|notifica[çc][ãa]o|not\b|penalidade|regimento|ri\b/i', $textClean);

            // Procurar se a linha possui o padrão numero/ano (Ex: 210/26 ou 155/2026) E um valor em R$
            if ($isMulta && preg_match('/(\d+)\/(\d{2,4})/', $textClean, $nm) && preg_match('/R\$\s*([\d\.,]+)/i', $textClean, $vm)) {
                $numero = $nm[1];
                $rawAno = $nm[2];
                $ano = strlen($rawAno) === 2 ? '20' . $rawAno : $rawAno;
                
                $valorRaw = $vm[1];
                $valorClean = (float)str_replace(['R$', '.', ' '], ['', '', ''], str_replace(',', '.', $valorRaw));

                // Buscar notificação no banco local
                $stmt = mysqli_prepare($link, "SELECT id, numero, ano, unidade, torre, multa_cobrada, valor FROM notificacoes WHERE numero = ? AND ano = ? LIMIT 1");
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
                    'item_descricao' => $textClean,
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

    // 2. Fallback Global em Texto/Frases no HTML (caso a tabela não utilize a estrutura <tr> padrão)
    if (empty($sugestoes)) {
        if (preg_match_all('/(?:multa|infra[çc]|notifica[çc][ãa]o|regimento|ri\b)[\s\S]*?(\d+)\/(\d{2,4})[\s\S]*?R\$\s*([\d\.,]+)/i', $html, $globalMatches, PREG_SET_ORDER)) {
            foreach ($globalMatches as $gm) {
                $numero = $gm[1];
                $rawAno = $gm[2];
                $ano = strlen($rawAno) === 2 ? '20' . $rawAno : $rawAno;
                $valorClean = (float)str_replace(['R$', '.', ' '], ['', '', ''], str_replace(',', '.', $gm[3]));
                
                $notificacaoId = "{$numero}/{$ano}";

                $stmt = mysqli_prepare($link, "SELECT id, numero, ano, unidade, torre, multa_cobrada, valor FROM notificacoes WHERE numero = ? AND ano = ? LIMIT 1");
                $jaLancado = false;
                $notificacaoEncontrada = false;
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
                    'item_descricao' => "Multa Notificação #{$notificacaoId}",
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

    // Trecho de debug se corpoComposicao estiver presente
    $snippet = '';
    if (preg_match('/<div[^>]*id=["\']corpoComposicao["\'][^>]*>([\s\S]*?)<\/div>/i', $html, $mCorp)) {
        $snippet = substr(trim(strip_tags($mCorp[1])), 0, 300);
    }

    DBClose($link);
    return [
        'sugestoes' => $sugestoes,
        'httpCode' => $httpCode,
        'htmlLength' => strlen($html),
        'urlUtilizada' => $urlSucesso,
        'snippet' => $snippet
    ];
}
