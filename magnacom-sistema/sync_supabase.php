<?php
/**
 * Script de Sincronização do Supabase para Notificações
 * 
 * Este script conecta-se diretamente ao backend Supabase do condomínio,
 * autentica-se usando as credenciais configuradas no arquivo .env e
 * atualiza a base de dados local com as notificações e datas de retirada.
 * 
 * Projetado para ser executado via CLI (Manual ou Agendado no Cron).
 */

// Define o cabeçalho padrão para execução via console/cron (UTF-8)
if (php_sapi_name() === 'cli') {
    ini_set('default_charset', 'UTF-8');
} else {
    header("Content-Type: text/plain; charset=utf-8");
}

// Configurações de erro limpas para CLI
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Função auxiliar para exibir logs com data e hora
function logMessage($msg) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
}

// --- 1. CARREGAMENTO DAS VARIÁVEIS DE AMBIENTE (.env) ---
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignora comentários
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Remove aspas simples/duplas das pontas do valor
            if (preg_match('/^"([^"]*)"$/', $val, $matches) || preg_match('/^\'([^\']*)\'$/', $val, $matches)) {
                $val = $matches[1];
            }
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
    return true;
}

$envPath = __DIR__ . '/.env';
if (!loadEnv($envPath)) {
    logMessage("Erro: Arquivo de configuração .env não encontrado em: $envPath");
    exit(1);
}

// Se executado via Web (browser ou cURL de fora do console), exige token de segurança
if (php_sapi_name() !== 'cli') {
    $secretToken = getenv('SYNC_SECRET_TOKEN');
    $receivedToken = $_GET['token'] ?? '';
    if (empty($secretToken) || $receivedToken !== $secretToken) {
        http_response_code(403);
        echo "Acesso Negado: Token de seguranca (?token=...) invalido ou nao configurado no .env.\n";
        exit(1);
    }
}

$supabaseUrl = getenv('SUPABASE_URL');
$supabaseAnonKey = getenv('SUPABASE_ANON_KEY');
$supabaseEmail = getenv('SUPABASE_EMAIL');
$supabasePassword = getenv('SUPABASE_PASSWORD');
$supabaseQueryFilter = getenv('SUPABASE_QUERY_FILTER') ?: '';

if (empty($supabaseUrl) || empty($supabaseAnonKey) || empty($supabaseEmail) || empty($supabasePassword)) {
    logMessage("Erro: Uma ou mais variáveis obrigatórias no arquivo .env estão vazias.");
    logMessage("Certifique-se de preencher: SUPABASE_URL, SUPABASE_ANON_KEY, SUPABASE_EMAIL e SUPABASE_PASSWORD.");
    exit(1);
}

// --- 2. CARREGAMENTO DOS REPOSITÓRIOS DO SISTEMA ---
$repositorioPath = dirname(__DIR__) . "/classes/repositorio.php";
if (!file_exists($repositorioPath)) {
    logMessage("Erro: Repositório do banco de dados não encontrado em: $repositorioPath");
    exit(1);
}
require_once $repositorioPath;

// --- 3. FUNÇÕES DE COMUNICAÇÃO COM O SUPABASE (cURL) ---

/**
 * Autentica o usuário no Supabase Auth e retorna o JWT access_token
 */
function getSupabaseToken($url, $apiKey, $email, $password) {
    $authUrl = rtrim($url, '/') . '/auth/v1/token?grant_type=password';
    $payload = json_encode([
        'email' => $email,
        'password' => $password
    ]);

    $ch = curl_init($authUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new Exception("Erro de rede (cURL) ao autenticar: " . $err);
    }

    if ($httpCode !== 200) {
        throw new Exception("Status de erro HTTP na autenticação ($httpCode). Detalhes: " . $response);
    }

    $data = json_decode($response, true);
    if (!isset($data['access_token'])) {
        throw new Exception("Token de acesso (access_token) ausente no payload de autenticação.");
    }

    return $data['access_token'];
}

/**
 * Consulta a tabela 'notificacoes' no Supabase usando o token de autenticação
 */
function getSupabaseNotifications($url, $apiKey, $token, $queryFilter) {
    $dbUrl = rtrim($url, '/') . '/rest/v1/notificacoes?select=*';
    if (!empty($queryFilter)) {
        $dbUrl .= '&' . ltrim($queryFilter, '&');
    }

    $ch = curl_init($dbUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $apiKey,
        'authorization: Bearer ' . $token,
        'accept-profile: public',
        'accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new Exception("Erro de rede (cURL) ao buscar notificações: " . $err);
    }

    if ($httpCode !== 200) {
        throw new Exception("Status de erro HTTP ao buscar notificações ($httpCode). Detalhes: " . $response);
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new Exception("Payload de resposta inválido do banco de dados (não é um array JSON).");
    }

    return $data;
}

/**
 * Auxiliar para converter datas recebidas do Supabase em formato YYYY-MM-DD
 */
function formatDbDate($dateStr) {
    if (empty($dateStr)) return null;
    $dateStr = trim($dateStr);
    
    // Se já estiver em formato YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        return $dateStr;
    }
    
    // Tenta obter timestamp
    $timestamp = strtotime($dateStr);
    if ($timestamp) {
        return date('Y-m-d', $timestamp);
    }
    return null;
}

/**
 * Converte tipificacao da Magnacom (ex: "Art. 14, inciso I") para notacao do regimento (ex: "14.1")
 */
function parseTipificacaoToNotacao($tipificacao) {
    $tipificacao = trim($tipificacao);
    if (empty($tipificacao)) return null;

    // Remove prefixos como "Art." ou "Artigo"
    $clean = preg_replace('/^art(igo)?\.?\s+/i', '', $tipificacao);

    // Tenta encontrar o número do artigo
    if (!preg_match('/^(\d+)/', $clean, $matches)) {
        return null;
    }
    $artigo = $matches[1];

    $resto = trim(substr($clean, strlen($artigo)));

    // Mapeamento de romanos para números
    $romanos = [
        'xiv' => 14, 'xiii' => 13, 'xii' => 12, 'xi' => 11, 'x' => 10,
        'ix' => 9, 'viii' => 8, 'vii' => 7, 'vi' => 6, 'v' => 5,
        'iv' => 4, 'iii' => 3, 'ii' => 2, 'i' => 1
    ];

    // Busca por Inciso romano ou palavra "inciso"
    if (preg_match('/(?:inciso\s+)?([ivxldcm]+)\b/i', $resto, $matches)) {
        $romano = strtolower($matches[1]);
        if (isset($romanos[$romano])) {
            return $artigo . '.' . $romanos[$romano];
        }
    }
    if (preg_match('/inciso\s+(\d+)/i', $resto, $matches)) {
        return $artigo . '.' . $matches[1];
    }

    // Busca por Parágrafo Único ou Parágrafo X
    if (preg_match('/par[aá]grafo\s+u[nni]co/i', $resto)) {
        return $artigo . '.unico';
    }
    if (preg_match('/(?:par[aá]grafo\s+|§\s*)(\d+)/ui', $resto, $matches)) {
        return $artigo . '.' . $matches[1];
    }

    return $artigo;
}

// --- 4. FLUXO DE EXECUÇÃO PRINCIPAL ---

try {
    logMessage("Iniciando processo de sincronização com o Supabase...");
    
    // Autenticação
    logMessage("Autenticando no Supabase com o e-mail: $supabaseEmail...");
    $token = getSupabaseToken($supabaseUrl, $supabaseAnonKey, $supabaseEmail, $supabasePassword);
    logMessage("Autenticação realizada com sucesso!");

    // Download dos dados
    $filterLog = !empty($supabaseQueryFilter) ? " com filtro '$supabaseQueryFilter'" : " (sem filtros)";
    logMessage("Baixando notificações do banco remoto$filterLog...");
    $notificacoes = getSupabaseNotifications($supabaseUrl, $supabaseAnonKey, $token, $supabaseQueryFilter);
    $totalRegistros = count($notificacoes);
    logMessage("Download concluído. Total de registros recebidos: $totalRegistros.");

    // Estatísticas da operação
    $stats = [
        'total' => $totalRegistros,
        'notificacoes_success' => 0,
        'notificacoes_error' => 0,
        'retiradas_success' => 0,
        'retiradas_error' => 0,
        'skipped' => 0
    ];

    // Processamento e mapeamento
    foreach ($notificacoes as $row) {
        // A. Resolução de número e ano
        $numStr = isset($row['numero']) ? trim($row['numero']) : '';
        $numeroParts = explode('/', $numStr);
        
        $numero = isset($numeroParts[0]) ? intval(preg_replace('/[^\d]/', '', $numeroParts[0])) : null;
        $ano = isset($numeroParts[1]) ? intval(preg_replace('/[^\d]/', '', $numeroParts[1])) : null;

        if (!$ano && isset($row['year'])) {
            $ano = intval($row['year']);
        }
        if (!$numero && isset($row['numero_ordem'])) {
            $numero = intval($row['numero_ordem']);
        }

        if (!$numero || !$ano) {
            logMessage("Aviso: Registro ignorado por falta de número ou ano válido (UUID Supabase: " . ($row['id'] ?? 'unknown') . ").");
            $stats['skipped']++;
            continue;
        }

        // B. Limpeza da torre (A-F)
        $torre = null;
        if (isset($row['torre']) && $row['torre'] !== '') {
            $torre = preg_replace('/[^A-F]/i', '', strtoupper($row['torre']));
            $torre = !empty($torre) ? $torre : null;
        }

        // C. Validação da unidade (apto)
        $unidade = 0;
        if (isset($row['apto']) && $row['apto'] !== '') {
            $unidade = intval(preg_replace('/[^\d]/', '', $row['apto']));
        }
        if ($unidade === 0) {
            logMessage("Aviso: Notificação $numero/$ano ignorada pois a unidade (apto) é zero ou inválida.");
            $stats['skipped']++;
            continue;
        }

        // D. Normalização do tipo de notificação
        $tipo = isset($row['tipo']) ? trim($row['tipo']) : '';
        $normalizedTipo = null;
        if ($tipo !== '') {
            $accents = ['à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','À','Á','Â','Ã','Ä','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý'];
            $clean = ['a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','U','U','U','U','Y'];
            $normalized = strtoupper(str_replace($accents, $clean, $tipo));
            if (strpos($normalized, 'MULTA') !== false) {
                $normalizedTipo = 'MULTA';
            } elseif (strpos($normalized, 'ADVERT') !== false) {
                $normalizedTipo = 'ADVERTENCIA';
            } else {
                $normalizedTipo = $normalized;
            }
        }

        // E. Assunto (infracao)
        $assunto = isset($row['infracao']) && $row['infracao'] !== '' ? trim($row['infracao']) : null;

        // F. Mapeamento de datas
        $data_email = formatDbDate($row['confeccao'] ?? '');
        $data_envio = formatDbDate($row['envio'] ?? '');
        $data_ocorrido = formatDbDate($row['data_ocorrido'] ?? '');

        // G. Status (resultado)
        $status = isset($row['resultado']) && $row['resultado'] !== '' ? trim($row['resultado']) : null;

        // H. Cobrança e Parcelamento
        $cobrado = (isset($row['cobrado_realizado']) && $row['cobrado_realizado']) ? 'Cobrado' : 'Não cobrado';
        $parcelamento = isset($row['parcelamento']) && $row['parcelamento'] !== '' ? trim($row['parcelamento']) : '';
        $cobranca = $cobrado;
        if ($parcelamento !== '') {
            $cobranca .= ' - ' . $parcelamento;
        }

        // I. Observação (obs)
        $obs = ($parcelamento !== '') ? $parcelamento : null;

        // J. Montagem e Upsert da Notificação
        $linha = [
            'numero' => $numero,
            'ano' => $ano,
            'torre' => $torre,
            'unidade' => $unidade,
            'notificacao' => $normalizedTipo,
            'assunto' => $assunto,
            'artigo' => parseTipificacaoToNotacao($row['tipificacao'] ?? ''),
            'data_email' => $data_email,
            'data_envio' => $data_envio,
            'data_ocorrido' => $data_ocorrido,
            'status' => $status,
            'cobranca' => $cobranca,
            'obs' => $obs
        ];

        $resNotif = upsertNotificacao($linha);
        if ($resNotif === 'ok') {
            $stats['notificacoes_success']++;
        } else {
            logMessage("Erro ao atualizar notificação $numero/$ano: " . $resNotif);
            $stats['notificacoes_error']++;
        }

        // K. Mapeamento e Upsert de Datas de Retirada (Dia da Ciência)
        $dia_retirada = null;
        if (!empty($row['recebimento_fisico'])) {
            $dia_retirada = formatDbDate($row['recebimento_fisico']);
        } elseif (!empty($row['conf_leitura_digital'])) {
            $dia_retirada = formatDbDate($row['conf_leitura_digital']);
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

            $resRetirada = upsertDatasDeRetirada($dadosRetirada);
            if ($resRetirada === 'ok') {
                $stats['retiradas_success']++;
            } else {
                logMessage("Erro ao atualizar dados de retirada para $numero/$ano: " . $resRetirada);
                $stats['retiradas_error']++;
            }
        }
    }

    // Relatório final
    logMessage("--- PROCESSO DE SINCRONIZAÇÃO CONCLUÍDO ---");
    logMessage("Total de registros processados: " . $stats['total']);
    logMessage("Notificações salvas com sucesso: " . $stats['notificacoes_success']);
    logMessage("Falhas ao salvar notificações: " . $stats['notificacoes_error']);
    logMessage("Datas de Retirada salvas com sucesso: " . $stats['retiradas_success']);
    logMessage("Falhas ao salvar Datas de Retirada: " . $stats['retiradas_error']);
    logMessage("Registros ignorados (dados inconsistentes): " . $stats['skipped']);

} catch (Exception $e) {
    logMessage("CRITICAL ERROR: " . $e->getMessage());
    exit(1);
}
