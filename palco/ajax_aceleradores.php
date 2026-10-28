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

$usuarioId = $_SESSION['user_id'] ?? null;
session_write_close(); // Libera trava de sessão PHP imediatamente para execução paralela dos 8 aceleradores

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

        case 'liberacoes_portaria':
            $dtInicio = $_GET['dtInicio'] ?? '';
            $dtFim = $_GET['dtFim'] ?? '';
            $res = vds_get_liberacoes_portaria_unidade($bloco, $unidade, $dtInicio, $dtFim, $usuarioId);
            echo json_encode(['success' => true, 'data' => $res]);
            break;

        case 'detalhes_liberacao_portaria':
            $uuid = $_GET['uuid'] ?? '';
            $res = vds_get_liberacao_portaria_detalhes($uuid, $usuarioId);
            echo json_encode($res);
            break;



        case 'verificar_email_requerente':
            $emailReq = trim($_GET['email'] ?? '');
            if (empty($emailReq) || !filter_var($emailReq, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => true,
                    'status' => 'sem_email',
                    'email' => $emailReq,
                    'badge_class' => 'grey darken-1',
                    'badge_icon' => 'mail_outline',
                    'titulo' => 'Sem e-mail',
                    'mensagem' => 'Nenhum e-mail registrado neste recurso.'
                ]);
                break;
            }

            $unidadesVds = vds_buscar_unidades_por_email($emailReq, $usuarioId);
            $blocoReq = strtoupper(trim($bloco));
            $unidadeReq = trim((string)$unidade);

            $match = false;
            $nomeMorador = null;

            if (!empty($unidadesVds)) {
                foreach ($unidadesVds as $u) {
                    if ($u['bloco'] === $blocoReq && ltrim($u['unidade'], '0') === ltrim($unidadeReq, '0')) {
                        $match = true;
                        $nomeMorador = $u['nome'] ?? null;
                        break;
                    }
                }

                if ($match) {
                    echo json_encode([
                        'success' => true,
                        'status' => 'confirmado',
                        'email' => $emailReq,
                        'badge_class' => 'green darken-2',
                        'badge_icon' => 'verified_user',
                        'titulo' => 'E-mail Morador VDS',
                        'mensagem' => 'E-mail cadastrado na portaria para esta unidade' . ($nomeMorador ? " ($nomeMorador)" : ''),
                        'unidades' => $unidadesVds
                    ]);
                } else {
                    $rotulos = array_column($unidadesVds, 'rotulo');
                    echo json_encode([
                        'success' => true,
                        'status' => 'divergente',
                        'email' => $emailReq,
                        'badge_class' => 'deep-orange darken-2',
                        'badge_icon' => 'warning',
                        'titulo' => 'E-mail Outra Unidade (VDS)',
                        'mensagem' => 'Atenção: E-mail cadastrado na portaria para: ' . implode(', ', $rotulos),
                        'unidades' => $unidadesVds
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'status' => 'nao_encontrado',
                    'email' => $emailReq,
                    'badge_class' => 'amber darken-4',
                    'badge_icon' => 'person_off',
                    'titulo' => 'E-mail Não Localizado (VDS)',
                    'mensagem' => 'E-mail não possui cadastro ativo de morador na portaria',
                    'unidades' => []
                ]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
