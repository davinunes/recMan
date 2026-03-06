<?php
require "classes/repositorio.php";

// Ação em massa: sincroniza e finaliza (fase 5 / concluido 1) 
// todos os recursos que possuem parentescos com pareceres e um mailId válido.
$sql = "UPDATE conselho.recurso r
        INNER JOIN conselho.parecer p ON p.id = r.numero
        SET r.fase = 5, p.concluido = 1
        WHERE p.mailId IS NOT NULL 
          AND p.mailId != '' 
          AND (r.fase != 5 OR p.concluido != 1 OR p.concluido IS NULL)";

$result = DBExecute($sql);

if ($result) {
    $title = "Ação Concluída";
    $msg = "A sincronização foi realizada com sucesso! Todos os recursos com parecer enviado foram automaticamente alterados para a fase Concluído.";
    $color = "green";
    $icon = "check_circle";
} else {
    $title = "Erro na Ação";
    $msg = "Não foi possível concluir a ação. Verifique a base de dados.";
    $color = "red";
    $icon = "error";
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo $title; ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .container {
            margin-top: 50px;
        }

        .card-panel {
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card-panel white center-align hoverable z-depth-2">
            <i class="material-icons <?php echo $color; ?>-text" style="font-size: 5rem;">
                <?php echo $icon; ?>
            </i>
            <h4 class="blue-grey-text text-darken-3">
                <?php echo $title; ?>
            </h4>
            <br>
            <p class="flow-text grey-text text-darken-1">
                <?php echo $msg; ?>
            </p>
            <br>
            <a href="index.php?pag=tools" class="btn-large blue darken-1 waves-effect waves-light"
                style="border-radius: 20px;">
                <i class="material-icons left">arrow_back</i> Voltar para Ferramentas
            </a>
        </div>
    </div>
</body>

</html>