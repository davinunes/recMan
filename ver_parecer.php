<?php
// Endpoint de Visualização Inline de Parecer Oficial do Conselho
require_once __DIR__ . "/classes/repositorio.php";
require_once __DIR__ . "/classes/pdfParecer.php";

$numeroRec = trim($_GET['rec'] ?? '');

if (empty($numeroRec)) {
    http_response_code(400);
    die("Número do recurso não informado.");
}

$parecer = getParecer($numeroRec);

if (!$parecer || empty($parecer['id'])) {
    http_response_code(404);
    die("Parecer não localizado para o recurso " . htmlspecialchars($numeroRec) . ".");
}

$pdfData = [
    'notificacao' => $parecer['id'],
    'unidade' => $parecer['unidade'] ?? '',
    'assunto' => $parecer['assunto'] ?? ('Parecer do Conselho - ' . $parecer['id']),
    'fato' => $parecer['notificacao'] ?? '',
    'analise' => $parecer['analise'] ?? '',
    'resultado' => $parecer['resultado'] ?? '',
    'parecer' => $parecer['conclusao'] ?? '',
    'modelo' => $parecer['modelo'] ?? 'estatico',
    'data_emissao' => (!empty($parecer['data'])) ? date('d/m/Y', strtotime($parecer['data'])) : date('d/m/Y')
];

$respJson = getParecerPdf($pdfData);
$resp = json_decode($respJson, true);

if (!empty($resp['pdf_base64'])) {
    $pdfBytes = base64_decode($resp['pdf_base64']);
    $nomeArquivo = 'Parecer_' . str_replace(['/', '\\'], '-', $numeroRec) . '.pdf';

    // Headers HTTP para exibição inline direta no navegador (sem forçar download)
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    header('Cache-Control: private, max-age=3600, must-revalidate');
    header('Pragma: public');

    echo $pdfBytes;
    exit;
} else {
    http_response_code(500);
    $erroMsg = $resp['message'] ?? 'Falha na compilação do PDF pelo servidor de renderização.';
    die("Erro ao gerar PDF do Parecer: " . htmlspecialchars($erroMsg));
}
