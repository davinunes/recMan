<?php
// Configurações do banco de dados
require_once("classes/key.php");

$host = 'localhost'; // Host do banco de dados
$username = DB_USERNAME; // Nome de usuário do banco de dados
$password = DB_PASSWORD; // Senha do banco de dados  
$database = DB_DATABASE; // Nome do banco de dados

// Caminho base para backups (mantendo compatibilidade com o servidor remoto)
$backupBase = '/var/www/html/';

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] == 'run') {
        $timestamp = date('Ymd_His');
        $fileName = "backup_{$database}_{$timestamp}";
        $backupSql = $backupBase . $fileName . ".sql";
        $backupTar = $backupSql . ".tar";
        $backupGz = $backupTar . ".gz";
        $backupEnc = $backupGz . ".enc";

        $steps = [];

        // Passo 1: MySQL Dump
        $cmd1 = "mysqldump --host=$host --user=$username --password=$password --databases $database > " . escapeshellarg($backupSql);
        exec($cmd1, $output, $ret1);
        $steps['dump'] = ($ret1 === 0);

        if ($steps['dump']) {
            // Passo 2: Tar
            $cmd2 = "tar -C " . escapeshellarg($backupBase) . " -cvf " . escapeshellarg($backupTar) . " " . escapeshellarg($fileName . ".sql");
            exec($cmd2, $output, $ret2);
            $steps['tar'] = ($ret2 === 0);

            // Passo 3: Gzip
            if ($steps['tar']) {
                $cmd3 = "gzip " . escapeshellarg($backupTar);
                exec($cmd3, $output, $ret3);
                $steps['gzip'] = ($ret3 === 0);
            }

            // Passo 4: Encrypt
            if ($steps['gzip']) {
                $cmd4 = "openssl enc -aes-256-cbc -salt -in " . escapeshellarg($backupGz) . " -out " . escapeshellarg($backupEnc) . " -pass pass:" . escapeshellarg($password);
                exec($cmd4, $output, $ret4);
                $steps['encrypt'] = ($ret4 === 0);
            }

            // Passo 5: Cleanup
            $cmd5 = "rm -f " . escapeshellarg($backupSql) . " " . escapeshellarg($backupTar) . " " . escapeshellarg($backupGz);
            exec($cmd5, $output, $ret5);
            $steps['cleanup'] = ($ret5 === 0);
        }

        $success = isset($steps['encrypt']) && $steps['encrypt'];
        echo json_encode([
            'success' => $success,
            'steps' => $steps,
            'file' => $success ? $fileName . ".sql.tar.gz.enc" : null
        ]);
        exit;
    }

    if ($_GET['action'] == 'decrypt' && isset($_FILES['backup_file'])) {
        $tmpFile = $_FILES['backup_file']['tmp_name'];
        $outName = $_FILES['backup_file']['name'] . ".decrypted.tar.gz";
        $outPath = $backupBase . $outName;

        $cmd = "openssl enc -d -aes-256-cbc -in " . escapeshellarg($tmpFile) . " -out " . escapeshellarg($outPath) . " -pass pass:" . escapeshellarg($password);
        exec($cmd, $output, $ret);

        if ($ret === 0) {
            echo json_encode(['success' => true, 'file' => $outName]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha na descriptografia. Verifique o arquivo e a senha.']);
        }
        exit;
    }
}

// Listar backups existentes
$backups = glob($backupBase . "*.sql.tar.gz.enc");
$backupList = [];
foreach ($backups as $b) {
    $backupList[] = basename($b);
}
rsort($backupList);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Backup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <style>
        body {
            background-color: #f5f5f5;
        }

        .card {
            border-radius: 8px;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
        }

        #log {
            max-height: 200px;
            overflow-y: auto;
            background: #eee;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
        }

        .step-success {
            color: green;
        }

        .step-error {
            color: red;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row">
            <div class="col s12">
                <h3>Backup do Sistema</h3>
            </div>
        </div>

        <div class="row">
            <!-- Card de Criação de Backup -->
            <div class="col s12 l6">
                <div class="card white">
                    <div class="card-content">
                        <span class="card-title">Gerar Novo Backup</span>
                        <p>Isso criará um dump do banco de dados <b><?php echo $database; ?></b>, compactará e
                            criptografará com AES-256.</p>
                        <br>
                        <button id="btnRunBackup" class="btn waves-effect waves-light teal">
                            <i class="material-icons left">cloud_upload</i> Iniciar Backup
                        </button>

                        <div id="backupProgress" style="display:none; margin-top: 20px;">
                            <div class="progress">
                                <div class="indeterminate"></div>
                            </div>
                            <p id="backupStatus">Iniciando...</p>
                            <div id="log"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Descriptografia -->
            <div class="col s12 l6">
                <div class="card white">
                    <div class="card-content">
                        <span class="card-title">Restaurar / Descriptografar</span>
                        <p>Envie um arquivo <code>.enc</code> para descriptografar usando a chave de segurança do
                            sistema.</p>
                        <form id="formDecrypt" enctype="multipart/form-data">
                            <div class="file-field input-field">
                                <div class="btn blue-grey">
                                    <span>Arquivo</span>
                                    <input type="file" name="backup_file" id="backup_file">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text"
                                        placeholder="Selecione o arquivo .enc">
                                </div>
                            </div>
                            <button type="submit" class="btn waves-effect waves-light orange">
                                <i class="material-icons left">lock_open</i> Descriptografar
                            </button>
                        </form>
                        <div id="decryptStatus" style="margin-top: 10px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Backups -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Arquivos Disponíveis no Servidor</span>
                        <table class="striped">
                            <thead>
                                <tr>
                                    <th>Nome do Arquivo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($backupList)): ?>
                                    <tr>
                                        <td colspan="2">Nenhum backup encontrado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($backupList as $file): ?>
                                        <tr>
                                            <td><?php echo $file; ?></td>
                                            <td>
                                                <a href="<?php echo $file; ?>" download
                                                    class="btn-small waves-effect waves-light blue">
                                                    <i class="material-icons">cloud_download</i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#btnRunBackup').click(function () {
                const $btn = $(this);
                const $progress = $('#backupProgress');
                const $status = $('#backupStatus');
                const $log = $('#log');

                $btn.addClass('disabled');
                $progress.show();
                $log.empty();
                $status.text('Processando... Isso pode levar alguns segundos.');

                function addLog(msg, success = null) {
                    let colorClass = success === true ? 'step-success' : (success === false ? 'step-error' : '');
                    $log.append(`<div class="${colorClass}">${msg}</div>`);
                    $log.scrollTop($log[0].scrollHeight);
                }

                addLog('Solicitando execução ao servidor...');

                $.ajax({
                    url: 'backup.php?action=run',
                    method: 'GET',
                    success: function (res) {
                        if (res.steps) {
                            addLog('Dump do Banco: ' + (res.steps.dump ? 'OK' : 'FALHA'), res.steps.dump);
                            addLog('Compactação TAR: ' + (res.steps.tar ? 'OK' : 'FALHA'), res.steps.tar);
                            addLog('Compressão GZIP: ' + (res.steps.gzip ? 'OK' : 'FALHA'), res.steps.gzip);
                            addLog('Criptografia AES-256: ' + (res.steps.encrypt ? 'OK' : 'FALHA'), res.steps.encrypt);
                            addLog('Limpeza de Temporários: ' + (res.steps.cleanup ? 'OK' : 'FALHA'), res.steps.cleanup);
                        }

                        if (res.success) {
                            $status.html('<b class="green-text">Backup Concluído com Sucesso!</b>');
                            M.toast({ html: 'Backup concluído!', classes: 'green' });
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            $status.html('<b class="red-text">Erro ao processar backup.</b>');
                            M.toast({ html: 'Erro no backup', classes: 'red' });
                        }
                    },
                    error: function () {
                        addLog('Erro de conexão com o servidor.', false);
                        $status.html('<b class="red-text">Erro FATAL na conexão.</b>');
                    },
                    complete: function () {
                        $btn.removeClass('disabled');
                        $('.progress').hide();
                    }
                });
            });

            $('#formDecrypt').submit(function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const $status = $('#decryptStatus');

                $status.html('<div class="progress"><div class="indeterminate"></div></div>');

                $.ajax({
                    url: 'backup.php?action=decrypt',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        if (res.success) {
                            $status.html(`<p class="green-text">Sucesso! <a href="${res.file}" download class="btn-small blue">Baixar ${res.file}</a></p>`);
                            M.toast({ html: 'Arquivo descriptografado!', classes: 'green' });
                        } else {
                            $status.html(`<p class="red-text">${res.error}</p>`);
                        }
                    },
                    error: function () {
                        $status.html('<p class="red-text">Erro na solicitação.</p>');
                    }
                });
            });
        });
    </script>

</body>

</html>