<?php
require "classes/repositorio.php";
require "classes/pdfParecer.php";

$gmail = verificarToken();

$temParecer = existeParecer($_GET['rec']);

// dump($temParecer);

if(!$temParecer){ //Se não tem Parecer
	$sql = "SELECT r.*, f.texto as fasee FROM recurso r
			left join fase f on f.id = r.fase 
			where r.numero = '{$_GET['rec']}'";

	$result = DBExecute($sql);
	$result = mysqli_fetch_assoc($result);

	$votos = getMaisVotado($result['id']);
	$rv = strtoupper($votos["voto"]);

	$pdf['id'] = $result["numero"];
	$pdf['unidade'] = $result["bloco"].$result["unidade"];
	$pdf['assunto'] = $result["titulo"];
	$pdf['notificacao'] = $result["fato"];
	$pdf['conclusao'] = $rv == "CONVERTER" ? "CONVERTER EM ADVERTÊNCIA": $rv;
	
	insertParecer($pdf);
	
	header("Location: {$_SERVER['REQUEST_URI']}");
	
	exit;
}

$parecer = getParecer($_GET['rec']);

// dump($parecer);

$pdf['notificacao'] = $parecer["id"];
$pdf['unidade'] = $parecer["unidade"];
$pdf['assunto'] = $parecer["assunto"];
$pdf['fato'] = $parecer["notificacao"];
$pdf['analise'] = $parecer["analise"];
$pdf['resultado'] = $parecer["resultado"];
$pdf['parecer'] = $parecer["conclusao"];

//Dados do e-mail a ser enviado:
$assunto = "Parecer do Conselho ".$result["numero"];
$destinatarios = $result["email"];
$cc = "sindicogeral.miami@gmail.com";
$bcc = "admcond@solucoesdf.com.br";
$mensagem = "Prezados,<br/>
Segue parecer do conselho referente ao recurso {$pdf['notificacao']}, da unidade {$pdf['unidade']}.";
$nomeAnexo = "Parecer Recurso ".str_replace("/","-",$pdf['notificacao']).".pdf";


$parecerPdf = json_decode(getParecerPdf($pdf), true)["pdf_base64"];


?>
<div class='container'>

<div class="row" >
    <div class="">
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
	// echo "Não temos token Gmail!<br/>";
	// echo "Clique no Link para obter um Token!<br/>";
	// echo "É necessário o token para enviar e-mails!<br/>";
	// echo "Se abrir uma tela solicitando logar com google, logue apenas com a conta do conselho!<br/>";
	// echo "<a class='btn' target='_blank' href='/gmail/checkToken.php'>Obter Token</a>";
	include("/var/www/html/gmail/refresh.php");
}

echo "<a class='btn blue right' id='btnAlterarParecer'>Quero alterar dados do parecer</a>";
?>

		</div>
	</div>
</div>




        <div class="card" id="previaPDF">

<embed src="data:application/pdf;base64,<?php echo $parecerPdf; ?>" type="application/pdf" width="100%" height="600px">

		</div>




      <div id="formParecer" class="card hide">
        <div class="card-content">
          <span class="card-title">Editar Parecer</span>

          <!-- Formulário de Edição -->
          <form action="processa_edicao_parecer.php" method="post">
            <div class="input-field">
              <textarea id="assunto" name="assunto" class="materialize-textarea"><?php echo $parecer["assunto"]; ?></textarea>
              <label for="assunto">Assunto</label>
            </div>

            <div class="input-field">
              <textarea id="notificacao" name="notificacao" class="materialize-textarea fato"><?php echo $parecer["notificacao"]; ?></textarea>
              <label for="notificacao">Fato que gerou a notificação</label>
            </div>

            <div class="input-field">
              <textarea id="analise" name="analise" class="materialize-textarea"><?php echo $parecer["analise"]; ?></textarea>
              <label for="analise">Da análise do Conselho:</label>
            </div>
			
            <div class="input-field">
              <textarea id="resultado" name="resultado" class="materialize-textarea"><?php echo $parecer["resultado"]; ?></textarea>
              <label for="resultado">Considerações finais:</label>
            </div>

            <div class="input-field">
              <textarea id="conclusao" name="conclusao" class="materialize-textarea"><?php echo $parecer["conclusao"]; ?></textarea>
              <label for="conclusao">Parecer ou veredito do Conselho:</label>
            </div>
			
			<div class="input-field hide">
              <textarea id="id_parecer" name="id_parecer" class="materialize-textarea"><?php echo $parecer["id"]; ?></textarea>
            </div>
			
			<div class="row">

            <div class="input-field col s6">
              <label>
                <input disabled type="checkbox" id="concluido" name="concluido" <?php echo $parecer["concluido"] == 1 ? "checked": ""; ?>>
                <span>Concluído</span>
              </label>
            </div>

            <div class="input-field col s6">
            <a class="btn waves-effect waves-light right" id="btnSalvarParecer">Salvar
              <i class="material-icons right">save</i>
            </a>
			</div>
			
			</div>

          </form>
          <!-- Fim do Formulário -->

        </div>
      </div>


</div class='container'>