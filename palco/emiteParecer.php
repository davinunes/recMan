<?php
require "classes/repositorio.php";
require "classes/pdfParecer.php";

$gmail = verificarToken();

$email_teste = $_SESSION["user_email"];
$temParecer = existeParecer($_GET['rec']);

// dump($temParecer);
$sql = "SELECT r.*, f.texto as fasee FROM recurso r
		left join fase f on f.id = r.fase 
		where r.numero = '{$_GET['rec']}'";

$result = DBExecute($sql);
$result = mysqli_fetch_assoc($result);

if (!$temParecer) { //Se não tem Parecer

  $votos = getMaisVotado($result['id']);
  $rv = strtoupper($votos["voto"]);

  switch ($rv) {
    case "REVOGAR":
      $pdf['resultado'] = "Após análise detalhada, e considerando a argumentação e os dados de vídeo preservados pela administração, observando os princípios de razoabilidade e proporcionalidade, o Conselho considerou revogar esta penalidade.";
      break;

    case "MANTER":
      $pdf['resultado'] = "Após análise detalhada, e considerando a argumentação e os dados de vídeo preservados pela administração, observando os princípios de razoabilidade e proporcionalidade, o Conselho não encontrou vícios na aplicação da sansão e considerou mantê-la.";
      break;

    case "CONVERTER":
      $pdf['resultado'] = "Após análise detalhada, e considerando a argumentação e os dados de vídeo preservados pela administração, observando os princípios de razoabilidade e proporcionalidade, o Conselho considerou converter esta penalidade em Advertência.";
      break;
  }


  $pdf['id'] = $result["numero"];
  $pdf['unidade'] = $result["bloco"] . $result["unidade"];
  $pdf['assunto'] = $result["titulo"];
  $pdf['notificacao'] = $result["fato"];
  $pdf['conclusao'] = $rv == "CONVERTER" ? "CONVERTER EM ADVERTÊNCIA" : $rv;

  // dump($result);

  // dump($pdf);

  insertParecer($pdf);

  header("Location: {$_SERVER['REQUEST_URI']}");

  exit;
}

$parecer = getParecer($_GET['rec']);
$parecerJaFoiEnviado = $parecer["concluido"] == 1 ? true : false;

if (!$parecerJaFoiEnviado) {
    $hoje = date('Y-m-d');
    if (empty($parecer['data']) || date('Y-m-d', strtotime($parecer['data'])) !== $hoje) {
        $idParecer = DBEscape($parecer['id']);
        DBExecute("UPDATE conselho.parecer SET data = CURDATE() WHERE id = '$idParecer' AND (concluido IS NULL OR concluido = 0)");
        $parecer['data'] = $hoje;
    }
}


$pdf['notificacao'] = $parecer["id"];
$pdf['unidade'] = $parecer["unidade"];
$pdf['assunto'] = $parecer["assunto"];
$pdf['fato'] = $parecer["notificacao"];
$pdf['analise'] = $parecer["analise"];
$pdf['resultado'] = $parecer["resultado"];
$pdf['parecer'] = $parecer["conclusao"];
$pdf['modelo'] = $parecer["modelo"] ?? "estatico";
$pdf['data_emissao'] = date('d/m/Y', strtotime($parecer['data']));

// dump($pdf);
//Dados do e-mail a ser enviado:
$assunto = "Parecer do Conselho " . $parecer["id"];
$destinatarios = $result["email"];
$cc = "sindicogeral.miami@gmail.com, centralderecursosmiamibeach@gmail.com";
// $bcc = "admcond@solucoesdf.com.br, juridico@solucoesdf.com.br";
$mensagem = "Prezados,<br/>
Segue parecer do conselho referente ao recurso {$pdf['notificacao']}, da unidade {$pdf['unidade']}.";
$nomeAnexo = "Parecer Recurso " . str_replace("/", "-", $pdf['notificacao']) . ".pdf";


$parecerPdf = json_decode(getParecerPdf($pdf), true)["pdf_base64"];


?>
<div class='container'>

  <div class="row">
    <div class="">
      <div class="card-panel indigo lighten-5">
        <?php

        echo "<br/>";

        echo "<h5>Dados para envio do parecer</h5>";
        echo "Destinatários: " . $destinatarios;
        echo "<br/>";
        echo "com cópia: " . $cc . ", " . $bcc;
        echo "<br/>";
        echo "Assunto: " . $assunto;
        echo "<br/>";
        echo "Anexo: " . $nomeAnexo;
        echo "<br/>";
        echo "Mensagem: " . $mensagem;

        $mime = "Content-Type: multipart/mixed; boundary=foo_bar_baz
MIME-Version: 1.0\n";
        $mime .= "to: " . $destinatarios . "\n";
        $mime .= "cc: " . $cc . "\n";
        $mime .= "bcc: " . $bcc . "\n";
        $mime .= "subject: $assunto" . "\n" . "\n";

        $mime .= "--foo_bar_baz" . "\n";
        $mime .= $mensagem . "\n";

        $mime .= '--foo_bar_baz
Content-Type: application/pdf
MIME-Version: 1.0
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="' . "$nomeAnexo" . '"' . "\n" . "\n";

        $mime .= $parecerPdf . "\n" . "\n";

        $mime .= '--foo_bar_baz--';

        echo "<details><summary>Visualizar metadados do e-mail</summary>";
        echo "<pre id='mime'>";
        echo $mime;
        echo "</pre>";
        echo "</details>";

        if ($gmail["status"] && $gmail["resta"] > 59) {
          echo "Temos Token, válido por: " . $gmail['resta'] . "s<br/>";
          if (!$parecerJaFoiEnviado)
            echo "<a class='btn red' id='finalizaEnviaParecer' idParecer='{$parecer['id']}'>Enviar parecer e finalizar recurso</a>";
        } else {
          // echo "Não temos token Gmail!<br/>";
          // echo "Clique no Link para obter um Token!<br/>";
          // echo "É necessário o token para enviar e-mails!<br/>";
          // echo "Se abrir uma tela solicitando logar com google, logue apenas com a conta do conselho!<br/>";
          // echo "<a class='btn' target='_blank' href='/gmail/checkToken.php'>Obter Token</a>";
          include("/var/www/html/gmail/refresh.php");
        }

        if (!$parecerJaFoiEnviado || $_GET[edit])
          echo "<a class='btn blue right' id='btnAlterarParecer'>Quero alterar dados do parecer</a>";

        if ($parecerJaFoiEnviado) {
          $link = "https://mail.google.com/mail/#inbox/" . $parecer['mailId'];
          echo "<a class='btn' href='{$link}'>Email de Entrega do Parecer (abrir como conselho)</a>";
          echo " <a class='btn blue' href='index.php'>Voltar para Recursos</a>";

        }

        ?>

      </div>
    </div>
  </div>




  <div class="card" id="previaPDF">
    <div class="card-content" style="padding-bottom: 12px; border-bottom: 1px solid #e0e0e0;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <span class="card-title" style="margin: 0; font-size: 1.2rem; font-weight: bold; color: #1a237e; display: flex; align-items: center;">
          <i class="material-icons left" style="color: #c62828;">picture_as_pdf</i> Prévia Oficial do Parecer
        </span>
        <div>
          <a href="ver_parecer.php?rec=<?php echo urlencode($parecer['id']); ?>" target="_blank" class="btn blue darken-2 waves-effect waves-light" style="font-weight: 500;">
            <i class="material-icons left">open_in_new</i>Abrir em Tela Cheia
          </a>
        </div>
      </div>
    </div>

    <!-- Visualizador Universal via PDF.js (compatível com Android, iOS e Desktop) -->
    <div id="pdfCanvasContainer" style="background: #525659; padding: 20px 10px; border-radius: 0 0 4px 4px; overflow-x: auto; text-align: center; min-height: 250px;">
      <div id="pdfLoading" style="color: #ffffff; padding: 30px 10px; font-size: 0.95rem;">
        <div class="preloader-wrapper small active" style="vertical-align: middle; margin-right: 10px;">
          <div class="spinner-layer spinner-blue-only">
            <div class="circle-clipper left"><div class="circle"></div></div>
            <div class="gap-patch"><div class="circle"></div></div>
            <div class="circle-clipper right"><div class="circle"></div></div>
          </div>
        </div>
        Carregando prévia do parecer...
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const base64Data = <?php echo json_encode($parecerPdf ?? ''); ?>;
      const container = document.getElementById('pdfCanvasContainer');
      const loadingElem = document.getElementById('pdfLoading');

      if (!base64Data || !window.pdfjsLib) {
        if (loadingElem) {
          loadingElem.innerHTML = "<p style='color:#ffcdd2;'>Prévia não disponível.</p><p><a href='ver_parecer.php?rec=<?php echo urlencode($parecer['id']); ?>' target='_blank' class='btn blue waves-effect'>Abrir PDF Direto</a></p>";
        }
        return;
      }

      pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

      try {
        const rawData = atob(base64Data);
        const uint8Array = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; i++) {
          uint8Array[i] = rawData.charCodeAt(i);
        }

        pdfjsLib.getDocument({ data: uint8Array }).promise.then(function (pdf) {
          if (loadingElem) loadingElem.style.display = 'none';

          for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            pdf.getPage(pageNum).then(function (page) {
              const containerWidth = container.clientWidth - 24;
              const unscaledViewport = page.getViewport({ scale: 1.0 });
              const targetWidth = Math.min(containerWidth > 220 ? containerWidth : 820, 850);
              const scale = targetWidth / unscaledViewport.width;
              const viewport = page.getViewport({ scale: scale });

              const canvas = document.createElement('canvas');
              canvas.className = 'pdf-page-canvas';
              canvas.style.maxWidth = '100%';
              canvas.style.height = 'auto';
              canvas.style.display = 'block';
              canvas.style.margin = '0 auto 15px auto';
              canvas.style.boxShadow = '0 4px 14px rgba(0,0,0,0.35)';
              canvas.style.backgroundColor = '#ffffff';
              canvas.style.borderRadius = '3px';

              const context = canvas.getContext('2d');
              canvas.height = viewport.height;
              canvas.width = viewport.width;

              const renderContext = {
                canvasContext: context,
                viewport: viewport
              };

              page.render(renderContext);
              container.appendChild(canvas);
            });
          }
        }).catch(function (err) {
          console.error("Erro na decodificação do PDF:", err);
          if (loadingElem) {
            loadingElem.innerHTML = "<p style='color:#ffcdd2;'>Falha ao desenhar o PDF no leitor interno.</p><p><a href='ver_parecer.php?rec=<?php echo urlencode($parecer['id']); ?>' target='_blank' class='btn blue darken-2 waves-effect'>Abrir em Tela Cheia</a></p>";
          }
        });
      } catch (err) {
        console.error("Erro de Base64:", err);
        if (loadingElem) {
          loadingElem.innerHTML = "<p style='color:#ffcdd2;'>Formato do parecer inválido.</p>";
        }
      }
    });
  </script>




  <div id="formParecer" class="card hide">
    <div class="card-content">
      <span class="card-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        Editar Parecer
        <button type="button" class="btn purple darken-2 waves-effect waves-light" id="btnAjudaIA" data-rec="<?php echo htmlspecialchars($parecer['id']); ?>">
          <i class="material-icons left">psychology</i>Ajuda da IA
        </button>
      </span>

      <!-- Formulário de Edição -->
      <form action="processa_edicao_parecer.php" method="post">
        <div class="input-field" style="margin-bottom: 25px;">
          <label style="transform: translateY(-14px) scale(0.85); transform-origin: 0 0; color: #1a237e; font-weight: bold;">Modelo do Parecer / Layout no PDF:</label>
          <select id="selectModeloParecer" name="modelo" class="browser-default" style="display: block; margin-top: 8px; border: 1px solid #7986cb; border-radius: 6px; padding: 10px; background-color: #f5f5f5; font-weight: 500;">
            <option value="estatico" <?php echo (!isset($parecer['modelo']) || $parecer['modelo'] == 'estatico') ? 'selected' : ''; ?>>📄 Modelo Padrão Seccionado (5 Caixas Estáticas)</option>
            <option value="full_dinamico" <?php echo (isset($parecer['modelo']) && $parecer['modelo'] == 'full_dinamico') ? 'selected' : ''; ?>>📑 Modelo Full Dinâmico (Texto Livre / Seções Personalizadas I, II, III...)</option>
          </select>
        </div>

        <div class="input-field">
          <textarea id="assunto" name="assunto"
            class="materialize-textarea"><?php echo $parecer["assunto"]; ?></textarea>
          <label for="assunto">Assunto</label>
        </div>

        <div class="input-field campo-estatico">
          <textarea id="notificacao" name="notificacao"
            class="materialize-textarea fato"><?php echo $parecer["notificacao"]; ?></textarea>
          <label for="notificacao">Fato que gerou a notificação</label>
        </div>

        <div class="input-field">
          <textarea id="analise" name="analise" style="min-height: 120px;"
            class="materialize-textarea"><?php echo $parecer["analise"]; ?></textarea>
          <label for="analise" id="labelAnalise">Da análise do Conselho:</label>
        </div>

        <div class="input-field campo-estatico">
          <textarea id="resultado" name="resultado"
            class="materialize-textarea"><?php echo $parecer["resultado"]; ?></textarea>
          <label for="resultado">Considerações finais:</label>
        </div>

        <div class="input-field campo-estatico">
          <textarea id="conclusao" name="conclusao"
            class="materialize-textarea"><?php echo $parecer["conclusao"]; ?></textarea>
          <label for="conclusao">Parecer ou veredito do Conselho:</label>
        </div>

        <script>
          function atualizarExibicaoCamposModelo() {
            var modelo = $('#selectModeloParecer').val();
            if (modelo === 'full_dinamico') {
              $('.campo-estatico').slideUp(200);
              $('#labelAnalise').text('Texto Integral do Parecer (Modelo Full Dinâmico):').css('font-weight', 'bold').css('color', '#1a237e');
              $('#analise').css('min-height', '240px');
            } else {
              $('.campo-estatico').slideDown(200);
              $('#labelAnalise').text('Da análise do Conselho:').css('font-weight', 'normal').css('color', '');
              $('#analise').css('min-height', '120px');
            }
            if (window.M && M.updateTextFields) { M.updateTextFields(); }
          }

          $(document).ready(function() {
            atualizarExibicaoCamposModelo();
            $(document).on('change', '#selectModeloParecer', function() {
              atualizarExibicaoCamposModelo();
            });
          });
        </script>

        <div class="input-field hide">
          <textarea id="id_parecer" name="id_parecer"
            class="materialize-textarea"><?php echo $parecer["id"]; ?></textarea>
        </div>

        <div class="row">

          <div class="input-field col s6">
            <label>
              <input disabled type="checkbox" id="concluido" name="concluido" <?php echo $parecer["concluido"] == 1 ? "checked" : ""; ?>>
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

<!-- 

{
  "id": "18c441555f7bcb23",
  "threadId": "18c441555f7bcb23",
  "labelIds": [
    "SENT"
  ]
}

-->