<?php
require_once __DIR__ . "/classes/repositorio.php";

$dataFormat = $_POST['dataFormat'] ?? "d/m/Y";

// --- 1. PROCESSAMENTO LEGADO DE JSON COLADO VIA POST ---
if (isset($_POST['processar']) && $_POST['processar'] === 'sim') {
    header('Content-Type: text/plain; charset=utf-8');
    $jsonData = $_POST['jsonData'] ?? '';
    $dataArray = json_decode($jsonData, true);

    if ($dataArray !== null) {
        $count = 0;
        foreach ($dataArray as $linha) {
            if (empty($linha["numero"])) continue;
            
            if (strpos((string)$linha["numero"], '.') !== false) {
                $linha["ano"] = "20" . substr(explode(".", $linha["numero"])[1], 0, 2);
                $linha["numero"] = intval(explode(".", $linha["numero"])[0]);
            } elseif (strpos((string)$linha["numero"], '/') !== false) {
                $parts = explode('/', $linha["numero"]);
                $linha["numero"] = intval($parts[0]);
                $linha["ano"] = intval($parts[1]);
            }

            $linha["unidade"] = isset($linha["unidade"]) ? intval($linha["unidade"]) : 0;
            if ($linha["unidade"] === 0) continue;

            $converterData = function($d) use ($dataFormat) {
                if (empty($d)) return null;
                $dt = DateTime::createFromFormat($dataFormat, trim($d));
                return $dt ? date("Y-m-d", $dt->getTimestamp()) : null;
            };

            $linha["data_email"] = $converterData($linha["data_email"] ?? '');
            $linha["data_envio"] = $converterData($linha["data_envio"] ?? '');
            $linha["data_ocorrido"] = $converterData($linha["data_ocorrido"] ?? '');

            if (!empty($linha["torre"])) {
                $linha["torre"] = preg_replace('/[^A-F]/i', '', strtoupper($linha["torre"]));
            }

            upsertNotificacao($linha);
            $count++;
        }
        echo "JSON processado com sucesso! Total de notificações: $count";
    } else {
        echo "Erro ao decodificar o JSON recebido via POST.";
    }
    exit;
}

// --- 2. EXECUÇÃO DA IMPORTAÇÃO DIRETA DO SUPABASE (MAGNACOM) ---
if (isset($_GET['importar_magnacom'])) {
    header("Content-Type: text/html; charset=utf-8");

    $cfg = getMagnacomConfig();
    $supabaseUrl = $cfg['url'];
    $supabaseAnonKey = $cfg['anon_key'];

    // Filtro por ano (opcional via URL: ?importar_magnacom=1&ano=2026)
    $filtroAnoParam = isset($_GET['ano']) ? trim($_GET['ano']) : '';
    if (empty($filtroAnoParam)) {
        // Padrão: ano corrente e anterior (>= 2025)
        $queryFilter = 'year=gte.2025';
    } elseif (strtolower($filtroAnoParam) === 'todos') {
        $queryFilter = '';
    } else {
        $queryFilter = 'year=eq.' . intval($filtroAnoParam);
    }

    $token = getValidMagnacomToken();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sincronização Magnacom / Supabase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; padding: 25px; }
        .card { border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .stat-badge { font-weight: 600; font-size: 1.15rem; padding: 6px 14px; border-radius: 20px; }
        .log-box { max-height: 380px; overflow-y: auto; background-color: #1e293b; color: #f8fafc; font-family: monospace; font-size: 0.85rem; padding: 15px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
            <h4 style="margin: 0; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                <i class="material-icons blue-text text-darken-2" style="font-size: 2.2rem;">cloud_sync</i>
                Sincronização de Notificações (Magnacom)
            </h4>
            <a href="index.php?pag=tools" class="btn-flat grey-text text-darken-2 waves-effect">
                <i class="material-icons left">arrow_back</i> Voltar
            </a>
        </div>

        <?php if (empty($supabaseUrl) || empty($supabaseAnonKey)): ?>
            <div class="card red lighten-5" style="border-left: 5px solid #d32f2f;">
                <div class="card-content red-text text-darken-4">
                    <span class="card-title" style="font-weight: 600;">Configurações Ausentes</span>
                    <p>A URL do Supabase ou a Chave Pública (Anon Key) não estão configuradas no sistema.</p>
                    <div style="margin-top: 15px;">
                        <a href="index.php?pag=configuracoes_magnacom" class="btn red darken-2 waves-effect waves-light">
                            <i class="material-icons left">settings</i> Configurar Supabase
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif (empty($token)): ?>
            <div class="card amber lighten-5" style="border-left: 5px solid #f57c00;">
                <div class="card-content brown-text text-darken-4">
                    <span class="card-title" style="font-weight: 600;">Token de Autenticação Necessário</span>
                    <p>Não foi possível obter ou renovar o Token de Acesso JWT do Supabase. Como a Magnacom adicionou proteção de bot/Cloudflare Turnstile no login, é necessário salvar um token ativo.</p>
                    <div style="margin-top: 15px;">
                        <a href="index.php?pag=configuracoes_magnacom" class="btn orange darken-3 waves-effect waves-light">
                            <i class="material-icons left">vpn_key</i> Atualizar Tokens no Painel
                        </a>
                    </div>
                </div>
            </div>
        <?php else: 
            // Inicia busca paginada no Supabase
            $limit = 1000;
            $offset = 0;
            $hasMore = true;
            $totalEncontradas = 0;
            $notificacoesSalvas = 0;
            $retiradasSalvas = 0;
            $erros = 0;
            $primeiraNotificacao = null;
            $ultimaNotificacao = null;
            $logHtml = [];

            $formatarData = function($dateStr) {
                if (empty($dateStr)) return null;
                $dateStr = trim($dateStr);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
                $ts = strtotime($dateStr);
                return $ts ? date('Y-m-d', $ts) : null;
            };

            while ($hasMore) {
                $dbUrl = rtrim($supabaseUrl, '/') . '/rest/v1/notificacoes?select=*';
                if (!empty($queryFilter)) {
                    $dbUrl .= '&' . ltrim($queryFilter, '&');
                }
                $dbUrl .= '&order=id.asc&limit=' . $limit . '&offset=' . $offset;

                $ch = curl_init($dbUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'apikey: ' . $supabaseAnonKey,
                    'authorization: Bearer ' . $token,
                    'accept-profile: public',
                    'accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $resp = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                // Tentativa de auto-renovação em caso de 401
                if ($httpCode === 401 && !empty($cfg['refresh_token'])) {
                    $renovado = renovarTokenMagnacomViaRefresh();
                    if ($renovado['success'] && !empty($renovado['access_token'])) {
                        $token = $renovado['access_token'];
                        // Reexecuta o bloco
                        $ch = curl_init($dbUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'apikey: ' . $supabaseAnonKey,
                            'authorization: Bearer ' . $token,
                            'accept-profile: public',
                            'accept: application/json'
                        ]);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                        $resp = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                    }
                }

                if ($httpCode !== 200) {
                    $logHtml[] = "<span style='color:#ef4444;'>[ERRO HTTP $httpCode] Falha ao consultar lote (offset $offset): " . htmlspecialchars($resp ?: $curlErr) . "</span>";
                    $hasMore = false;
                    break;
                }

                $lote = json_decode($resp, true);
                if (!is_array($lote) || count($lote) === 0) {
                    $hasMore = false;
                    break;
                }

                $qtdLote = count($lote);
                $totalEncontradas += $qtdLote;

                foreach ($lote as $row) {
                    // Resolução de número e ano
                    $numStr = isset($row['numero']) ? trim($row['numero']) : '';
                    $numeroParts = explode('/', $numStr);
                    $numero = isset($numeroParts[0]) ? intval(preg_replace('/[^\d]/', '', $numeroParts[0])) : null;
                    $ano = isset($numeroParts[1]) ? intval(preg_replace('/[^\d]/', '', $numeroParts[1])) : null;

                    if (!$ano && isset($row['year'])) $ano = intval($row['year']);
                    if (!$numero && isset($row['numero_ordem'])) $numero = intval($row['numero_ordem']);

                    if (!$numero || !$ano) {
                        $erros++;
                        continue;
                    }

                    $recLabel = $numero . '/' . $ano;
                    if ($primeiraNotificacao === null) $primeiraNotificacao = $recLabel;
                    $ultimaNotificacao = $recLabel;

                    // Torre e Unidade
                    $torre = !empty($row['torre']) ? preg_replace('/[^A-F]/i', '', strtoupper($row['torre'])) : null;
                    $unidade = !empty($row['apto']) ? intval(preg_replace('/[^\d]/', '', $row['apto'])) : 0;
                    if ($unidade === 0) {
                        $erros++;
                        continue;
                    }

                    // Tipo
                    $tipo = isset($row['tipo']) ? trim($row['tipo']) : '';
                    $normalizedTipo = null;
                    if ($tipo !== '') {
                        $accents = ['à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','À','Á','Â','Ã','Ä','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý'];
                        $clean = ['a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','U','Y'];
                        $norm = strtoupper(str_replace($accents, $clean, $tipo));
                        if (strpos($norm, 'MULTA') !== false) {
                            $normalizedTipo = 'MULTA';
                        } elseif (strpos($norm, 'ADVERT') !== false) {
                            $normalizedTipo = 'ADVERTENCIA';
                        } else {
                            $normalizedTipo = $norm;
                        }
                    }

                    // Assunto e Artigo
                    $assunto = !empty($row['infracao']) ? trim($row['infracao']) : null;
                    $artigo = parseTipificacaoToNotacao($row['tipificacao'] ?? '');

                    // Datas
                    $data_email = $formatarData($row['confeccao'] ?? '');
                    $data_envio = $formatarData($row['envio'] ?? '');
                    $data_ocorrido = $formatarData($row['data_ocorrido'] ?? '');
                    $status = !empty($row['resultado']) ? trim($row['resultado']) : null;

                    // Cobrança
                    $cobrado = (!empty($row['cobrado_realizado'])) ? 'Cobrado' : 'Não cobrado';
                    $parcelamento = !empty($row['parcelamento']) ? trim($row['parcelamento']) : '';
                    $cobranca = $cobrado . ($parcelamento !== '' ? (' - ' . $parcelamento) : '');
                    $obs = ($parcelamento !== '') ? $parcelamento : null;

                    $linha = [
                        'numero' => $numero,
                        'ano' => $ano,
                        'torre' => $torre,
                        'unidade' => $unidade,
                        'notificacao' => $normalizedTipo,
                        'assunto' => $assunto,
                        'artigo' => $artigo,
                        'data_email' => $data_email,
                        'data_envio' => $data_envio,
                        'data_ocorrido' => $data_ocorrido,
                        'status' => $status,
                        'cobranca' => $cobranca,
                        'obs' => $obs
                    ];

                    $resNotif = upsertNotificacao($linha);
                    if ($resNotif === 'ok') {
                        $notificacoesSalvas++;
                    } else {
                        $erros++;
                    }

                    // Datas de Retirada (Ciência)
                    $dia_retirada = null;
                    if (!empty($row['recebimento_fisico'])) {
                        $dia_retirada = $formatarData($row['recebimento_fisico']);
                    } elseif (!empty($row['conf_leitura_digital'])) {
                        $dia_retirada = $formatarData($row['conf_leitura_digital']);
                    }

                    if ($dia_retirada !== null) {
                        $dadosRetirada = [
                            'notificacao' => $numero,
                            'ano' => $ano,
                            'dia_retirada' => $dia_retirada,
                            'bloco' => $torre,
                            'apartamento' => $unidade,
                            'obs' => $cobranca
                        ];
                        $resRet = upsertDatasDeRetirada($dadosRetirada);
                        if ($resRet === 'ok') {
                            $retiradasSalvas++;
                        }
                    }
                }

                if ($qtdLote < $limit) {
                    $hasMore = false;
                } else {
                    $offset += $limit;
                }
            }
        ?>

        <div class="card">
            <div class="card-content">
                <span class="card-title" style="font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="material-icons green-text">check_circle</i> Sincronização Concluída com Sucesso!
                </span>

                <div class="row" style="margin-top: 20px; margin-bottom: 0;">
                    <div class="col s12 m3 center-align" style="margin-bottom: 15px;">
                        <div class="grey lighten-4" style="padding: 15px; border-radius: 8px;">
                            <div class="grey-text text-darken-1" style="font-size: 0.88rem; font-weight: 600;">TOTAL CONSULTADO</div>
                            <div class="blue-text text-darken-2" style="font-size: 1.8rem; font-weight: 700; margin-top: 4px;">
                                <?php echo $totalEncontradas; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col s12 m3 center-align" style="margin-bottom: 15px;">
                        <div class="grey lighten-4" style="padding: 15px; border-radius: 8px;">
                            <div class="grey-text text-darken-1" style="font-size: 0.88rem; font-weight: 600;">NOTIFICAÇÕES SALVAS</div>
                            <div class="teal-text text-darken-2" style="font-size: 1.8rem; font-weight: 700; margin-top: 4px;">
                                <?php echo $notificacoesSalvas; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col s12 m3 center-align" style="margin-bottom: 15px;">
                        <div class="grey lighten-4" style="padding: 15px; border-radius: 8px;">
                            <div class="grey-text text-darken-1" style="font-size: 0.88rem; font-weight: 600;">CIÊNCIAS / RETIRADAS</div>
                            <div class="purple-text text-darken-2" style="font-size: 1.8rem; font-weight: 700; margin-top: 4px;">
                                <?php echo $retiradasSalvas; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col s12 m3 center-align" style="margin-bottom: 15px;">
                        <div class="grey lighten-4" style="padding: 15px; border-radius: 8px;">
                            <div class="grey-text text-darken-1" style="font-size: 0.88rem; font-weight: 600;">ÚLTIMO REGISTRO</div>
                            <div class="orange-text text-darken-3" style="font-size: 1.6rem; font-weight: 700; margin-top: 4px;">
                                <?php echo htmlspecialchars($ultimaNotificacao ?: 'Nenhum'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-panel grey lighten-4" style="margin-top: 15px; border-radius: 6px; padding: 12px 18px;">
                    <div style="font-size: 0.95rem; color: #334155;">
                        <strong>Filtro aplicado:</strong> <code><?php echo htmlspecialchars($queryFilter ?: 'Todas as Notificações'); ?></code> | 
                        <strong>Intervalo de registros:</strong> <?php echo htmlspecialchars($primeiraNotificacao ?: '0'); ?> até <strong><?php echo htmlspecialchars($ultimaNotificacao ?: '0'); ?></strong>
                        <?php if ($erros > 0): ?>
                            | <span class="red-text">Ignorados/inconsistentes: <?php echo $erros; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 25px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="index.php?pag=tools" class="btn blue darken-2 waves-effect waves-light">
                        <i class="material-icons left">build</i> Painel de Ferramentas
                    </a>
                    <a href="index.php?pag=documentosOficiais" class="btn green darken-2 waves-effect waves-light">
                        <i class="material-icons left">gavel</i> Documentos Oficiais
                    </a>
                    <a href="index.php?pag=configuracoes_magnacom" class="btn purple darken-1 waves-effect waves-light">
                        <i class="material-icons left">settings</i> Configurações Magnacom
                    </a>
                    <a href="json.php?importar_magnacom=1&ano=2026" class="btn-flat waves-effect">
                        <i class="material-icons left">refresh</i> Sincronizar apenas 2026
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Conversor e Sincronizador Magnacom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; padding: 30px; }
        .card { border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">
        <div class="card z-depth-1">
            <div class="card-content">
                <span class="card-title" style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                    <i class="material-icons blue-text text-darken-2" style="font-size: 2.2rem;">cloud_sync</i>
                    Importação de Notificações da Magnacom
                </span>
                <p class="grey-text text-darken-1" style="margin-bottom: 25px;">
                    O sistema da Magnacom migrou para o <strong>Supabase</strong>. Utilize o botão abaixo para sincronizar todas as notificações em tempo real diretamente do banco da administradora.
                </p>

                <div class="row">
                    <div class="col s12 m6">
                        <div class="card-panel blue lighten-5 blue-text text-darken-4" style="border-radius: 6px; padding: 20px;">
                            <strong style="font-size: 1.1rem; display: block; margin-bottom: 8px;">Sincronizar via Supabase API (Recomendado)</strong>
                            <p style="font-size: 0.92rem; margin-bottom: 20px;">
                                Conecta-se diretamente ao backend do condomínio e atualiza todas as multas, advertências e datas de retirada.
                            </p>
                            <a href="json.php?importar_magnacom=1" class="btn blue darken-2 waves-effect waves-light" style="width: 100%;">
                                <i class="material-icons left">sync</i> Sincronizar Tudo (2025 e 2026)
                            </a>
                            <div style="margin-top: 10px; text-align: center;">
                                <a href="json.php?importar_magnacom=1&ano=2026" class="blue-text text-darken-3" style="font-size: 0.88rem; font-weight: 500;">
                                    Sincronizar apenas o ano de 2026 &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col s12 m6">
                        <div class="card-panel grey lighten-4 grey-text text-darken-3" style="border-radius: 6px; padding: 20px;">
                            <strong style="font-size: 1.1rem; display: block; margin-bottom: 8px;">Configurações e Tokens</strong>
                            <p style="font-size: 0.92rem; margin-bottom: 20px;">
                                Gerencie o token de acesso do Supabase, refresh token e credenciais de login.
                            </p>
                            <a href="index.php?pag=configuracoes_magnacom" class="btn purple darken-1 waves-effect waves-light" style="width: 100%;">
                                <i class="material-icons left">settings</i> Configurações Magnacom
                            </a>
                        </div>
                    </div>
                </div>

                <div class="divider" style="margin: 30px 0;"></div>

                <!-- Formulário legado de colagem manual de JSON -->
                <span style="font-weight: 600; font-size: 1rem; color: #475569; display: flex; align-items: center; gap: 6px;">
                    <i class="material-icons">code</i> Importação Manual (Fallback JSON Colado)
                </span>
                <p class="grey-text" style="font-size: 0.88rem; margin-top: 4px;">
                    Caso precise importar um lote manual convertido de XLS/Planilha:
                </p>

                <div class="input-field" style="margin-top: 15px;">
                    <textarea id="json2" class="materialize-textarea" rows="6" style="font-family: monospace; font-size: 0.85rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px;"></textarea>
                    <label for="json2">Cole o JSON gerado aqui</label>
                </div>

                <div class="row" style="margin-bottom: 0;">
                    <div class="input-field col s12 m4">
                        <input id="json3" type="text" value="d/m/Y">
                        <label for="json3" class="active">Formato de Data</label>
                    </div>
                    <div class="col s12 m8" style="margin-top: 20px;">
                        <button id="processar" class="btn teal darken-1 waves-effect waves-light">
                            <i class="material-icons left">play_arrow</i> Processar JSON Colado
                        </button>
                    </div>
                </div>

                <div id="resultado" style="display: none; margin-top: 20px;">
                    <div class="card-panel green lighten-5 green-text text-darken-4" id="json1"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $("#processar").click(function() {
                var jsonStr = $("#json2").val().trim();
                if (!jsonStr) {
                    M.toast({html: 'Por favor, cole um JSON válido no campo de texto.', classes: 'red rounded'});
                    return;
                }

                var btn = $(this);
                btn.addClass('disabled').text('Processando...');

                $.ajax({
                    type: "POST",
                    url: "json.php",
                    data: {
                        processar: "sim",
                        jsonData: jsonStr,
                        dataFormat: $("#json3").val()
                    },
                    success: function(response) {
                        btn.removeClass('disabled').html('<i class="material-icons left">play_arrow</i> Processar JSON Colado');
                        $("#resultado").show();
                        $("#json1").text(response);
                        M.toast({html: 'Processamento concluído!', classes: 'green rounded'});
                    },
                    error: function(xhr, status, error) {
                        btn.removeClass('disabled').html('<i class="material-icons left">play_arrow</i> Processar JSON Colado');
                        M.toast({html: 'Erro ao processar: ' + error, classes: 'red rounded'});
                    }
                });
            });
        });
    </script>
</body>
</html>