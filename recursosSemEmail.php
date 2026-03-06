<?php
require "classes/repositorio.php";

$sql = "SELECT r.id, r.unidade, r.bloco, r.numero, r.titulo, r.data 
        FROM conselho.recurso r
        LEFT JOIN conselho.parecer p ON p.id = r.numero
        WHERE r.fase = 5 AND (p.mailId IS NULL OR p.mailId = '')";

$result = DBExecute($sql);
$recursos = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recursos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Recursos Concluídos Sem Email</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .container {
            margin-top: 30px;
        }

        .card-panel {
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card-panel white">
            <h4 class="center-align blue-text text-darken-2">Recursos Sem E-mail Registrado</h4>
            <p class="center-align grey-text">Lista de recursos finalizados que podem ter sido esquecidos de enviar por
                e-mail.</p>

            <?php if (empty($recursos)): ?>
                <div class="card-panel green lighten-4 green-text text-darken-4 center-align">
                    <i class="material-icons medium">check_circle</i><br>
                    Nenhum recurso pendente de envio. Todos os recursos finalizados têm e-mail registrado.
                </div>
            <?php else: ?>
                <table class="striped highlight responsive-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Unidade</th>
                            <th>Título</th>
                            <th>Data</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recursos as $r): ?>
                            <tr>
                                <td><strong>
                                        <?php echo htmlspecialchars($r['numero']); ?>
                                    </strong></td>
                                <td>
                                    <?php echo htmlspecialchars($r['unidade'] . '-' . $r['bloco']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['titulo']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['data']); ?>
                                </td>
                                <td>
                                    <a href="index.php?pag=recurso&numero=<?php echo urlencode($r['numero']); ?>"
                                        class="btn blue btn-small" target="_blank">
                                        <i class="material-icons left">visibility</i> Resolver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="center-align" style="margin-top: 30px;">
                <a href="index.php?pag=tools" class="btn grey waves-effect waves-light"><i
                        class="material-icons left">arrow_back</i> Voltar</a>
            </div>
        </div>
    </div>
</body>

</html>