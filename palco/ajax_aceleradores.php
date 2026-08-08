<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloquear acesso se não estiver logado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Sessão expirada.']);
    exit;
}

// Iniciar buffer para capturar e descartar qualquer saída acidental de includes
ob_start();

require_once __DIR__ . "/../classes/repositorio.php";
require_once __DIR__ . "/../classes/vds_acesso_service.php";

// Limpar buffer antes de enviar o JSON
ob_clean();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$bloco = $_GET['bloco'] ?? '';
$unidade = $_GET['unidade'] ?? '';
$usuarioId = $_SESSION['user_id'] ?? null;

if (empty($bloco) || empty($unidade)) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros insuficientes (bloco/unidade).']);
    exit;
}

try {
    switch ($action) {
        case 'moradores':
            $res = vds_get_moradores_unidade($bloco, $unidade, $usuarioId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'veiculos':
            $res = vds_get_veiculos_unidade($bloco, $unidade, $usuarioId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'visitantes':
            $res = vds_get_visitantes_unidade($bloco, $unidade, $usuarioId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'acessos':
            $dtInicio = $_GET['dtInicio'] ?? '';
            $dtFim = $_GET['dtFim'] ?? '';
            $res = vds_get_eventos_acesso($bloco, $unidade, $dtInicio, $dtFim, $usuarioId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'autorizacoes':
            $dtIniJanela = $_GET['dtIniJanela'] ?? '';
            $dtFimJanela = $_GET['dtFimJanela'] ?? '';
            $res = vds_get_autorizacoes_acesso($bloco, $unidade, $dtIniJanela, $dtFimJanela, $usuarioId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'entregas':
            $res = vds_get_entregas_unidade($bloco, $unidade, null, null, $usuarioId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'chamados':
            $res = vds_get_chamados_unidade($bloco, $unidade);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
