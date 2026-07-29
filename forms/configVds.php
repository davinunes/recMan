<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../classes/vds_auth_service.php";

$usuarioIdConselho = $_SESSION['usuario_id'] ?? 1;
$msg = null;
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'sync_condominio') {
        $user = $_POST['vds_user'] ?? '';
        $pass = $_POST['vds_pass'] ?? '';
        $res = vds_save_condominio_token($user, $pass);
        if ($res['success']) {
            $msg = "Token de Sincronização do Condomínio atualizado com sucesso!";
            $msgType = "success";
        } else {
            $msg = "Falha ao autenticar Condomínio: " . ($res['message'] ?? 'Erro desconhecido');
            $msgType = "danger";
        }
    } elseif ($action === 'ultra_login_conselheiro') {
        $user = $_POST['vds_user'] ?? '';
        $pass = $_POST['vds_pass'] ?? '';
        $res = vds_save_conselheiro_token($usuarioIdConselho, $user, $pass);
        if ($res['success']) {
            $msg = "Ultra-Login do Conselheiro ativado com sucesso! Seu token de sessão está registrado.";
            $msgType = "success";
        } else {
            $msg = "Falha no Ultra-Login: " . ($res['message'] ?? 'Erro desconhecido');
            $msgType = "danger";
        }
    }
}

// Consultar status dos tokens
$tokenCond = vds_get_token(null);
$statusCond = vds_check_token_status($tokenCond);

$tokenConselheiro = vds_get_token($usuarioIdConselho);
$statusConselheiro = vds_check_token_status($tokenConselheiro);
?>

<div class="container" style="margin-top: 30px;">
    <h4 style="margin-bottom: 20px; font-weight: 600;">Configurações de Sincronização & Ultra-Login (Vida de Síndico API v8)</h4>

    <?php if ($msg): ?>
        <div class="card-panel <?= $msgType === 'success' ? 'teal lighten-2 white-text' : 'red lighten-2 white-text' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Card 1: Token do Condomínio (Sincronização Automática) -->
        <div class="col s12 m6">
            <div class="card border-top-primary">
                <div class="card-content">
                    <span class="card-title">Token Geral do Condomínio</span>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">
                        Utilizado pelas rotinas de fundo (cron a cada 15 min) para sincronizar ocorrências e eventos.
                    </p>

                    <div style="margin-bottom: 15px;">
                        <strong>Status Atual:</strong>
                        <?php if ($statusCond): ?>
                            <span class="badge green white-text" style="float:none; padding:3px 8px; border-radius:4px;">Ativo & Conectado</span>
                        <?php else: ?>
                            <span class="badge red white-text" style="float:none; padding:3px 8px; border-radius:4px;">Expirado / Desconectado</span>
                        <?php endif; ?>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="sync_condominio">
                        <div class="input-field">
                            <input type="text" id="vds_user_cond" name="vds_user" required>
                            <label for="vds_user_cond">Usuário Administrador (VDS)</label>
                        </div>
                        <div class="input-field">
                            <input type="password" id="vds_pass_cond" name="vds_pass" required>
                            <label for="vds_pass_cond">Senha Administradora (VDS)</label>
                        </div>
                        <button type="submit" class="btn waves-effect waves-light blue">
                            Conectar Condomínio <i class="material-icons right">sync</i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Card 2: Ultra-Login do Conselheiro -->
        <div class="col s12 m6">
            <div class="card border-top-accent">
                <div class="card-content">
                    <span class="card-title">Ultra-Login do Conselheiro</span>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">
                        Conecta seu usuário pessoal ao Condomínio Digital. Suas respostas nas ocorrências utilizarão seu token individual. <strong>Sua senha nunca é armazenada.</strong>
                    </p>

                    <div style="margin-bottom: 15px;">
                        <strong>Seu Status de Ultra-Login:</strong>
                        <?php if ($statusConselheiro): ?>
                            <span class="badge green white-text" style="float:none; padding:3px 8px; border-radius:4px;">Ultra-Login Ativo</span>
                        <?php else: ?>
                            <span class="badge orange white-text" style="float:none; padding:3px 8px; border-radius:4px;">Pendente de Ativação</span>
                        <?php endif; ?>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="ultra_login_conselheiro">
                        <div class="input-field">
                            <input type="text" id="vds_user_cons" name="vds_user" required>
                            <label for="vds_user_cons">Seu Usuário na VDS</label>
                        </div>
                        <div class="input-field">
                            <input type="password" id="vds_pass_cons" name="vds_pass" required>
                            <label for="vds_pass_cons">Sua Senha na VDS</label>
                        </div>
                        <button type="submit" class="btn waves-effect waves-light purple">
                            Ativar Ultra-Login <i class="material-icons right">vpn_key</i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
