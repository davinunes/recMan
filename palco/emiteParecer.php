<?php
require "classes/repositorio.php";
require "classes/pdfParecer.php";

$gmail = verificarToken();

$sql = "SELECT r.*, f.texto as fasee FROM recurso r
		left join fase f on f.id = r.fase 
		where r.numero = '{$_GET['rec']}'";

$result = DBExecute($sql);
$result = mysqli_fetch_assoc($result);

$votos = getMaisVotado($result['id']);
$rv = strtoupper($votos["voto"]);

$pdf['notificacao'] = $result["numero"];
$pdf['unidade'] = $result["bloco"].$result["unidade"];
$pdf['assunto'] = $result["titulo"];
$pdf['fato'] = "Fato narrado na cópia da Notificação";
$pdf['resultado'] = "Considerações finais a serem realizadas";
$pdf['parecer'] = $rv == "CONVERTER" ? "CONVERTER EM ADVERTÊNCIA": $rv;

//Dados do e-mail a ser enviado:
$assunto = "Parecer do Conselho ".$result["numero"];
$destinatarios = $result["email"];
$cc = "sindicogeral.miami@gmail.com";
$bcc = "admcond@solucoesdf.com.br";
$mensagem = "Prezados,<br/>
Segue parecer do conselho referente ao recurso {$pdf['notificacao']}, da unidade {$pdf['unidade']}.";
$nomeAnexo = "Parecer Recurso ".str_replace("/","-",$pdf['notificacao']).".pdf";

/***
converter,manter,revogar
**/

// var_dump($result);
// var_dump($votos);
$parecerPdf = json_decode(getParecerPdf($pdf), true)["pdf_base64"];
// var_dump($parecerPdf);

?>

<div class="row" >
    <div class="col s12 m6">
        <div class="card-panel indigo lighten-5">
<?php

echo "<br/>";

echo "<h5>Dados para envio do parecer</h5>";
echo "Destinatários: ".$destinatarios;
echo "<br/>";
echo "com cópia: ".$cc.", ".$bcc;
echo "<br/>";
echo "Assunto: ".$assunto;
echo "<br/>";
echo "Anexo: ".$nomeAnexo;
echo "<br/>";
echo "Mensagem: ".$mensagem;

$mime  = "Content-Type: multipart/mixed; boundary=foo_bar_baz
MIME-Version: 1.0\n";
$mime .= "to: davi.nunes@gmail.com"."\n";
$mime .= "cc: davi.nunes+cc@gmail.com"."\n";
$mime .= "bcc: davinunes.franca@eb.mil.br"."\n";
$mime .= "subject: $assunto"."\n"."\n";

$mime .= "--foo_bar_baz"."\n";
$mime .= $mensagem."\n";

$mime .= '--foo_bar_baz
Content-Type: application/pdf
MIME-Version: 1.0
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="'."$nomeAnexo".'"'."\n"."\n";

$mime .= $parecerPdf."\n"."\n";

$mime .= '--foo_bar_baz--';

echo "<details><summary>Visualizar metadados do e-mail</summary>";
echo "<pre id='mime'>";
echo $mime;
echo "</pre>";
echo "</details>";

if($gmail["status"] && $gmail["resta"] > 59){
	echo "Temos Token, válido por: ".$gmail['resta']."s<br/>";
	echo "<a class='btn' id='testeEnvioParecer'>Testar envio de e-mail</a>";
}else{
	echo "Não temos token Gmail!<br/>";
	echo "Clique no Link para obter um Token!<br/>";
	echo "É necessário o token para enviar e-mails!<br/>";
	echo "Se abrir uma tela solicitando logar com google, logue apenas com a conta do conselho!<br/>";
	echo "<a class='btn' target='_blank' href='/gmail/checkToken.php'>Obter Token</a>";
}
?>

		</div>
	</div>
</div>

<div class="row">
    <div class="col s12 m6">
        <div class="card">

<embed src="data:application/pdf;base64,<?php echo $parecerPdf; ?>" type="application/pdf" width="100%" height="600px">

		</div>
	</div>
</div>