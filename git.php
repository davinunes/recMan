<?php

	if(isset($_GET['git']) and  $_GET['git'] == 1){
		
		// exit;
		
		$mensagem = $_POST['msg'] ? $_POST['msg'] : "Atualização";
		// echo $mensagem."<br/>";
		$cmd = 'cd /var/www/html 2>&1 
		&& git pull 2>&1 
		&& git add . 2>&1 
		&& git commit -m "'.$mensagem.'" 
		&& git push origin main 2>&1';
		
		// echo $cmd."<br/>";
		$command = '/usr/bin/python /var/www/ilunne/boss/py/ssh.py '."'".$cmd."'";
		// echo $command."<br/>";

		$output = shell_exec($command);
		echo "<pre>".$output."</pre>";
		exit;
	}
	
	$cmd = 'cd /var/www/html; /usr/bin/git status 2>&1'; 
	// $cmd = '/usr/bin/git config --global --add safe.directory /var/www/html 2>&1'; 
	// $cmd = 'whoami'; 

	$output = shell_exec($cmd);
	echo "<pre>".$output."</pre>";
?>
<form action="git.php?git=1" method="post">
        <label for="texto">Descrição do Commit:</label>
        <input type="text" id="msg" name="msg">
        <input type="submit" value="Enviar">
</form>