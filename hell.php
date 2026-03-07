<?php
session_start();
require_once "classes/repositorio.php";

// Verifica se é conselheiro
if (empty($_SESSION['user_id'])) {
    die("Acesso negado. Apenas para conselheiros logados.");
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nuke') {
    $numero = $_POST['numero'] ?? '';
    $ano = $_POST['ano'] ?? '';

    if (!empty($numero) && !empty($ano)) {
        $numeroCompleto = DBEscape(trim($numero) . '/' . trim($ano));

        // 1. Apagar arquivos do disco
        $anexos = getAnexos($numeroCompleto);
        if ($anexos) {
            foreach ($anexos as $anx) {
                $caminho = __DIR__ . '/storage/anexos/' . $anx['caminho_arquivo'];
                if (file_exists($caminho)) {
                    @unlink($caminho);
                }
            }
        }

        // 2. Apagar as referências do banco
        $tabelasDependentes = [
            "DELETE FROM recurso_anexos WHERE numero_recurso = '$numeroCompleto'",
            "DELETE FROM votos WHERE id_recurso IN (SELECT id FROM recurso WHERE numero = '$numeroCompleto')",
            "DELETE FROM diligencia WHERE id_recurso IN (SELECT id FROM recurso WHERE numero = '$numeroCompleto')",
            "DELETE FROM mensagem WHERE id_recurso IN (SELECT id FROM recurso WHERE numero = '$numeroCompleto')",
            "DELETE FROM parecer WHERE id = '$numeroCompleto'",
            "DELETE FROM recurso WHERE numero = '$numeroCompleto'"
        ];

        $erros = 0;
        foreach ($tabelasDependentes as $sql) {
            if (!DBExecute($sql)) {
                $erros++;
            }
        }

        if ($erros == 0) {
            $msg = "<div style='color:green; padding:10px; border:1px solid green; background:#e8f5e9; border-radius:4px;'>Recurso $numeroCompleto apagado completamente das profundezas do inferno! (Banco de dados e disco) 😈🔥</div>";
        } else {
            $msg = "<div style='color:red; padding:10px; border:1px solid red; background:#ffebee; border-radius:4px;'>Houve alguns erros ao apagar registros filhos de $numeroCompleto, mas o script tentou...</div>";
        }
    } else {
        $msg = "<div style='color:orange;'>Preencha os campos corretamente.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sua área secreta (Hell Mode)</title>
    <style>
        body {
            font-family: monospace;
            background-color: #1a1a1a;
            color: #d4d4d4;
            padding: 40px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #444;
        }

        h1 {
            color: #ff5252;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
            background: #1a1a1a;
            border: 1px solid #555;
            color: #fff;
        }

        button {
            background: #b71c1c;
            color: #fff;
            border: none;
            padding: 12px 20px;
            width: 100%;
            margin-top: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
            transition: 0.3s;
        }

        button:hover {
            background: #d32f2f;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔥 HELL MODE 🔥</h1>
        <p style="text-align:center; color:#999;">Esta tela apagará impiedosamente qualquer evidência de homologação e
            voltará o banco de dados no tempo para o respectivo recurso (Exclusivo para admins logados).</p>

        <?= $msg ?>

        <form method="POST"
            onsubmit="return confirm('TEM CERTEZA ABSOLUTA? ISSO NÃO PODE SER DESFEITO! As provas e PDF serão varridos do disco!')">
            <input type="hidden" name="action" value="nuke">

            <label>Número do Recurso no sistema (ex: 220)</label>
            <input type="text" name="numero" required placeholder="Apenas os números iniciais">

            <label>Ano do Recurso (ex: 2026)</label>
            <input type="text" name="ano" required placeholder="Apenas o ano de quatro dígitos">

            <button type="submit">☠️ OBLITERAR RECURSO DO SERVIDOR ☠️</button>
        </form>
    </div>
</body>

</html>