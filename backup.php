<?php
// Configurações do banco de dados
require("classes/key.php");
$host = 'localhost'; // Host do banco de dados
$username = DB_USERNAME; // Nome de usuário do banco de dados
$password = DB_PASSWORD; // Senha do banco de dados  
$database = DB_DATABASE; // Nome do banco de dados

// Caminho para salvar o arquivo de backup
$backupFile = '/var/www/html/backup.sql';

// Comando para criar o backup usando mysqldump
$command1 = "mysqldump --host=$host --user=$username --password=$password --databases $database > $backupFile";
$command2 = "tar -cvf $backupFile.tar ".$backupFile;
$command3 = "gzip $backupFile.tar";
$command4 = "openssl enc -aes-256-cbc -salt -in $backupFile.tar.gz -out $backupFile.tar.gz.enc -pass pass:".'"'.$password.'"';
$command5 = "rm $backupFile && rm $backupFile.tar && rm $backupFile.tar.gz";

// Executa o comando
exec("ls -lah");
exec($command1, $output, $returnValue);
exec("ls -lah");
exec($command2, $output, $returnValue);
exec("ls -lah");
exec($command3, $output, $returnValue);
exec("ls -lah");
exec($command4, $output, $returnValue);
exec("ls -lah");
exec($command5, $output, $returnValue);
exec("ls -lah");

if ($returnValue === 0) {
    echo "Backup do banco de dados criado com sucesso!";
} else {
    echo "Erro ao criar o backup do banco de dados.";
}
?>
