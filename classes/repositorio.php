<?php
require "/var/www/html/classes/database.php";

function dump($el)
{
    echo "<pre>";
    print_r($el);
    echo "</pre>";
}

function styledDump($sql)
{
    // Cores para o "Carnaval" do Debug
    $keywords = ['INSERT INTO', 'VALUES', 'ON DUPLICATE KEY UPDATE', 'NULL'];
    $highlightedSql = $sql;

    foreach ($keywords as $word) {
        $highlightedSql = str_replace($word, "<b style='color: #ff79c6;'>$word</b>", $highlightedSql);
    }

    echo "
    <style>
        .debug-card {
            background-color: #282a36;
            color: #f8f8f2;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 12px;
            padding: 10px;
            margin: 5px 0;
            border-left: 5px solid #50fa7b;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            overflow-x: auto;
            line-height: 1.4;
        }
        .debug-label {
            color: #6272a4;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
            font-weight: bold;
        }
    </style>
    <div class='debug-card'>
        <span class='debug-label'>[SQL Query - " . date('H:i:s') . "]</span>
        <pre style='margin: 0; white-space: pre-wrap;'>" . $highlightedSql . "</pre>
    </div>
    ";
}

function getUsuarios($id = 1)
{
    $sql = " SELECT id, email, senha, nome, status, unidade, avatar ";
    $sql .= " FROM conselho.usuarios";

    $result = DBExecute($sql);
    // var_dump($sql);
    if (!mysqli_num_rows($result)) {

    } else {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }
    return $dados;
}

function getUsuariosById($id = 1)
{
    $sql = " SELECT id, email, senha, nome, status, unidade, avatar ";
    $sql .= " FROM conselho.usuarios";
    $sql .= " where id=" . $id;

    $result = DBExecute($sql);
    // var_dump($sql);
    if (!mysqli_num_rows($result)) {

    } else {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }
    return $dados[0];
}

function getUsuario($login)
{
    $sql = " SELECT id, email, nome, status, unidade, senha, avatar ";
    $sql .= " FROM conselho.usuarios";
    $sql .= " where email = '$login'";

    $result = DBExecute($sql);
    // var_dump($sql);
    if (!mysqli_num_rows($result)) {

    } else {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }
    return $dados[0];
}

function existeParecer($id)
{
    $sql = " SELECT COUNT(*) AS pareceres_encontrados ";
    $sql .= " FROM conselho.parecer";
    $sql .= " where id = '$id'";

    $result = DBExecute($sql);
    // var_dump($sql);
    if (!mysqli_num_rows($result)) {

    } else {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }
    return $dados[0]["pareceres_encontrados"] == 1 ? true : false;
}

function getParecer($id)
{
    $sql = " SELECT * ";
    $sql .= " FROM conselho.parecer";
    $sql .= " where id = '$id'";

    $result = DBExecute($sql);
    // var_dump($sql);
    if (!mysqli_num_rows($result)) {

    } else {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }
    return $dados[0];
}

function updateParecer($dados)
{
    $id = DBEscape($dados['id_parecer']);
    $resultado = DBEscape($dados['resultado']);
    $assunto = DBEscape($dados['assunto']);
    $notificacao = DBEscape($dados['notificacao']);
    $analise = DBEscape($dados['analise']);
    $conclusao = DBEscape($dados['conclusao']);

    $sql = "UPDATE conselho.parecer
            SET resultado = '$resultado',
                assunto = '$assunto',
                notificacao = '$notificacao',
                analise = '$analise',
                conclusao = '$conclusao'
            WHERE id = '$id'";

    if (DBExecute($sql)) {
        return true;
    } else {
        return false;
    }
}

function finalizaParecer($dados)
{
    $id = DBEscape($dados['id_parecer']);
    $rid = DBEscape($dados['mailId']);
    $userId = DBEscape($dados['userId']);

    // Inicia uma transação
    DBExecute("BEGIN");

    // Atualiza a tabela 'parecer'
    $sqlParecer = "UPDATE conselho.parecer
                    SET mailId = '$rid', concluido = 1, quemFinalizou = $userId 
                    WHERE id = '$id'";

    // Atualiza a tabela 'recurso'
    $sqlRecurso = "UPDATE conselho.recurso
                    SET fase = 5 
                    WHERE numero = '$id'";

    // dump($sqlParecer);
    // dump($sqlRecurso);

    // Executa as atualizações
    if (DBExecute($sqlParecer) && DBExecute($sqlRecurso)) {
        // Se ambas as atualizações forem bem-sucedidas, realiza o COMMIT
        DBExecute("COMMIT");
        return true;
    } else {
        // Se ocorrer algum erro, realiza o ROLLBACK
        DBExecute("ROLLBACK");
        return false;
    }
}

function insertParecer($dados)
{
    $id = DBEscape($dados['id']);
    $unidade = DBEscape($dados['unidade']);
    $assunto = DBEscape($dados['assunto']);
    $notificacao = DBEscape($dados['notificacao']);
    $resultado = DBEscape($dados['resultado']);
    $conclusao = DBEscape($dados['conclusao']);
    $concluido = isset($dados['concluido']) ? (int) $dados['concluido'] : 0; // Converte para inteiro (0 ou 1)

    $sql = "INSERT INTO conselho.parecer ";
    $sql .= "(id, unidade, assunto, conclusao, concluido, resultado, notificacao) ";
    $sql .= "VALUES ('$id', '$unidade', '$assunto',  '$conclusao', $concluido, '$resultado', '$notificacao') ";

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function getMensagens($recurso)
{
    $sql = "SELECT m.*, u.avatar 
            FROM conselho.mensagem m
			left join conselho.usuarios u on u.id = m.id_usuario
            WHERE id_recurso = '$recurso'";

    $result = DBExecute($sql);
    $dados = array(); // Inicializa a variável $dados como um array vazio

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function getDiligencias($recurso)
{
    $sql = "SELECT m.*, u.avatar 
            FROM conselho.diligencia m
			left join conselho.usuarios u on u.id = m.id_usuario
            WHERE id_recurso = '$recurso'";

    $result = DBExecute($sql);
    $dados = array(); // Inicializa a variável $dados como um array vazio

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function getEstatisticas($mes = null, $ano = null)
{
    // Se $ano não for fornecido, define-o como o ano atual
    if ($ano === null) {
        $ano = date('Y');
    }

    // Se $mes não for fornecido ou for 0, não aplicamos o filtro de mês
    if ($mes === null || $mes == 0) {
        $mesFiltro = ''; // Deixa $mesFiltro vazio para não aplicar o filtro de mês
    } else {
        $mesFiltro = "AND MONTH(data) = '$mes'";
    }

    $sql = "SELECT conclusao, COUNT(*) as total_pareceres, GROUP_CONCAT(id) as lista_ids
            FROM conselho.parecer
            WHERE YEAR(data) = '$ano' $mesFiltro
            GROUP BY conclusao";

    // dump($sql);
    $result = DBExecute($sql);
    $dados = array(); // Inicializa a variável $dados como um array vazio

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}


function getVotos($recurso)
{
    // Verifica se o valor contém uma barra ("/")
    if (strpos($recurso, '/') !== false) {
        // Se contiver barra, pesquisar por r.numero
        $campo = 'r.numero';
    } else {
        // Caso contrário, pesquisar por v.id_recurso
        $campo = 'v.id_recurso';
    }

    $sql = "SELECT v.id, v.id_recurso, v.id_usuario, v.voto, v.data, u.nome, u.avatar
            FROM conselho.votos v
            LEFT JOIN conselho.usuarios u ON u.id = v.id_usuario 
            LEFT JOIN conselho.recurso r ON r.id = v.id_recurso
            WHERE $campo = '$recurso'";

    // dump($sql);
    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}



function getNotificacoes($unidade, $torre)
{
    $sql = "SELECT 
                n.numero_ano_virtual,
                n.numero as numero,
                n.ano,
                n.unidade as unidade,
                n.torre as bloco,
                DATE_FORMAT(n.data_email, '%d/%m/%Y') AS data_email,
				DATE_FORMAT(n.data_envio, '%d/%m/%Y') AS data_envio,
				DATE_FORMAT(n.data_ocorrido, '%d/%m/%Y') AS data_ocorrido,
                DATE_FORMAT(d.dia_retirada, '%d/%m/%Y') AS dia_retirada,
				DATEDIFF(CURRENT_DATE(), d.dia_retirada) AS defasagem,
                n.assunto,
                n.notificacao,
                n.cobranca,
                n.status,
                n.obs as obs_soluções,
				d.obs as obs_retirada,
                (SELECT 'Sim' FROM recurso WHERE recurso.numero = n.numero_ano_virtual) AS recurso,
                (SELECT conclusao FROM parecer WHERE parecer.id = n.numero_ano_virtual) AS parecer
            FROM 
                notificacoes n 
                LEFT JOIN DatasDeRetirada d ON d.virtual = n.numero_ano_virtual 
            WHERE unidade = '$unidade' and torre = '$torre'
			order by n.ano asc, n.numero asc";
    // dump($sql);

    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function getAllNotificacoes()
{
    $sql = "SELECT n.numero
                    ,n.torre
                    ,n.unidade
                    ,DATE_FORMAT(n.data_email, '%d/%m/%Y') AS data_email
                    ,DATE_FORMAT(n.data_envio, '%d/%m/%Y') AS data_envio
                    ,DATE_FORMAT(n.data_ocorrido, '%d/%m/%Y') AS data_ocorrido
                    ,n.assunto
                    ,n.notificacao
                    ,n.ano
                    ,n.numero_ano_virtual
                    ,n.cobranca
                    ,n.status
                    ,n.obs
                    ,DATE_FORMAT(d.dia_retirada, '%d/%m/%Y') AS dia_retirada
                    ,(SELECT 'Sim' FROM recurso WHERE recurso.numero = n.numero_ano_virtual) AS existe_recurso
                    ,(SELECT conclusao FROM parecer WHERE parecer.id = n.numero_ano_virtual) AS existe_parecer
                    ,DATEDIFF(CURRENT_DATE(), d.dia_retirada) AS diferenca_dias
                    ,mc.valor
                    ,DATE_FORMAT(mc.data_vencimento, '%d/%m/%Y') AS data_vencimento
                    ,DATE_FORMAT(mc.data_pagamento, '%d/%m/%Y') AS data_pagamento
                    ,CASE WHEN mc.numero IS NOT NULL THEN 'Sim' ELSE 'Não' END AS multa_cobrada
            FROM notificacoes n 
            LEFT JOIN DatasDeRetirada d ON d.virtual = n.numero_ano_virtual 
            LEFT JOIN multas_cobradas mc ON mc.numero = n.numero AND mc.ano = n.ano
            ORDER BY n.ano ASC, n.numero DESC";

    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function getNotificacaoByNumeroAno($numero, $ano)
{
    $sql = "SELECT * FROM notificacoes WHERE numero = '$numero' AND ano = '$ano'";
    $result = DBExecute($sql);

    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }

    return false;
}

function upsertMultaCobrada($data)
{
    // Preparar valores para NULL
    $data_vencimento = $data['data_vencimento'];
    $data_pagamento = $data['data_pagamento'] ? "'{$data['data_pagamento']}'" : "NULL";
    $valor = $data['valor'];

    // Verificar se já existe registro
    $check_sql = "SELECT id FROM multas_cobradas WHERE numero = '{$data['numero']}' AND ano = '{$data['ano']}'";
    $check_result = DBExecute($check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        // UPDATE
        $sql = "UPDATE multas_cobradas SET 
                valor = '{$valor}',
                data_vencimento = '{$data_vencimento}',
                data_pagamento = {$data_pagamento},
                updated_at = NOW()
                WHERE numero = '{$data['numero']}' AND ano = '{$data['ano']}'";
    } else {
        // INSERT
        $sql = "INSERT INTO multas_cobradas 
                (unidade, bloco, data_vencimento, data_pagamento, valor, numero, ano, created_at, updated_at)
                VALUES 
                ('{$data['unidade']}', '{$data['bloco']}', '{$data_vencimento}', {$data_pagamento}, 
                 '{$valor}', '{$data['numero']}', '{$data['ano']}', NOW(), NOW())";
    }

    $result = DBExecute($sql);
    return $result ? "ok" : "error";
}

function getNotificacoesByFilters($ano = null, $status = null, $tipo = null, $bloco = null, $multa_cobrada = null)
{
    $sql = "SELECT n.numero
                    ,n.torre
                    ,n.unidade
                    ,DATE_FORMAT(n.data_email, '%d/%m/%Y') AS data_email
                    ,DATE_FORMAT(n.data_envio, '%d/%m/%Y') AS data_envio
                    ,DATE_FORMAT(n.data_ocorrido, '%d/%m/%Y') AS data_ocorrido
                    ,n.assunto
                    ,n.notificacao
                    ,n.ano
                    ,n.numero_ano_virtual
                    ,n.cobranca
                    ,n.status
                    ,n.obs
                    ,DATE_FORMAT(d.dia_retirada, '%d/%m/%Y') AS dia_retirada
                    ,(SELECT 'Sim' FROM recurso WHERE recurso.numero = n.numero_ano_virtual) AS existe_recurso
                    ,(SELECT conclusao FROM parecer WHERE parecer.id = n.numero_ano_virtual) AS existe_parecer
                    ,DATEDIFF(CURRENT_DATE(), d.dia_retirada) AS diferenca_dias
                    ,mc.valor
                    ,DATE_FORMAT(mc.data_vencimento, '%d/%m/%Y') AS data_vencimento
                    ,DATE_FORMAT(mc.data_pagamento, '%d/%m/%Y') AS data_pagamento
                    ,CASE WHEN mc.numero IS NOT NULL THEN 'Sim' ELSE 'Não' END AS multa_cobrada
            FROM notificacoes n 
            LEFT JOIN DatasDeRetirada d ON d.virtual = n.numero_ano_virtual 
            LEFT JOIN multas_cobradas mc ON mc.numero = n.numero AND mc.ano = n.ano
            WHERE 1=1";

    // Filtro por ano
    if ($ano !== null) {
        $ano = DBEscape($ano);
        $sql .= " AND n.ano = '$ano'";
    }

    // Filtro por status
    if ($status !== null) {
        $status = DBEscape($status);
        $sql .= " AND n.status = '$status'";
    }

    // Filtro por tipo de notificação
    if ($tipo !== null) {
        $tipo = DBEscape($tipo);
        $sql .= " AND n.notificacao = '$tipo'";
    }

    // Filtro por bloco
    if ($bloco !== null) {
        $bloco = DBEscape($bloco);
        $sql .= " AND n.torre = '$bloco'";
    }

    // Filtro por multa cobrada
    if ($multa_cobrada !== null) {
        $multa_cobrada = DBEscape($multa_cobrada);
        if (strtolower($multa_cobrada) === 'sim') {
            $sql .= " AND mc.numero IS NOT NULL";
        } elseif (strtolower($multa_cobrada) === 'não' || strtolower($multa_cobrada) === 'nao') {
            $sql .= " AND mc.numero IS NULL";
        } else {
            // Se passar um valor diferente, usa a verificação da CASE
            $sql .= " AND CASE WHEN mc.numero IS NOT NULL THEN 'Sim' ELSE 'Não' END = '$multa_cobrada'";
        }
    }

    $sql .= " ORDER BY n.ano ASC, n.numero DESC";

    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function getNotificacoesByDate($inicio, $fim, $coluna)
{
    $sql = "select n.numero
					,n.torre
					,n.unidade
					,DATE_FORMAT(n.data_email, '%d/%m/%Y') AS data_email
					,DATE_FORMAT(n.data_envio, '%d/%m/%Y') AS data_envio
					,DATE_FORMAT(n.data_ocorrido, '%d/%m/%Y') AS data_ocorrido
					,n.assunto
					,n.notificacao
					,n.ano
					,n.numero_ano_virtual
					,n.cobranca
					,n.status
					,n.obs
					,DATE_FORMAT(d.dia_retirada, '%d/%m/%Y') AS dia_retirada
					,DATEDIFF(CURRENT_DATE(), d.dia_retirada) AS diferenca_dias
	from notificacoes n left join DatasDeRetirada d on d.virtual = n.numero_ano_virtual 
	where $coluna  BETWEEN '$inicio' AND '$fim'
	order by n.ano asc, n.numero desc";

    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function getNotificacoesByDateWithStatus($inicio, $fim, $coluna, $tipo)
{
    // Inicializa a parte da consulta relacionada às datas como uma string vazia
    $dataFilter = "";

    // Verifica se as datas foram fornecidas
    if (!empty($inicio) && !empty($fim)) {
        $dataFilter = "AND $coluna BETWEEN '$inicio' AND '$fim'";
    }

    // Converte o tipo para a condição WHERE adequada
    if ($tipo == "tudo") {
        $tipoFilter = "";
    } else if ($tipo == "NULL") {
        $tipoFilter = "AND n.notificacao is NULL";
    } else {
        $tipoFilter = "AND n.notificacao = '$tipo'";
    }

    // Monta a consulta SQL com as condições
    $sql = "SELECT 
                n.numero,
                n.torre,
                n.unidade,
                DATE_FORMAT(n.data_email, '%d/%m/%Y') AS data_email,
				DATE_FORMAT(n.data_envio, '%d/%m/%Y') AS data_envio,
				DATE_FORMAT(n.data_ocorrido, '%d/%m/%Y') AS data_ocorrido,
                n.assunto,
                n.notificacao,
                n.ano,
                n.numero_ano_virtual,
                n.cobranca,
                n.status,
                n.obs,
                DATE_FORMAT(d.dia_retirada, '%d/%m/%Y') AS dia_retirada,
                (SELECT 'Sim' FROM recurso WHERE recurso.numero = n.numero_ano_virtual) AS existe_recurso,
                (SELECT conclusao FROM parecer WHERE parecer.id = n.numero_ano_virtual) AS existe_parecer,
				DATEDIFF(CURRENT_DATE(), d.dia_retirada) AS diferenca_dias
            FROM 
                notificacoes n 
                LEFT JOIN DatasDeRetirada d ON d.virtual = n.numero_ano_virtual 
            WHERE 
                1=1 $dataFilter $tipoFilter
            ORDER BY 
                n.ano ASC, n.numero DESC";

    // dump($sql);

    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}


function buscaNotificacoes($numero = 1, $ano = 2023)
{
    $sql = "select * from notificacoes where numero='$numero' and ano='$ano'";
    // echo $sql;
    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function buscaRecursoParecer($numero = 1, $ano = 2023)
{
    $sql = "select numero, count(p.id) as temParecer from recurso r left join parecer p on p.id = r.numero where r.numero='$numero/$ano'";
    // echo $sql;
    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function verificarLogin($username, $password)
{
    $password = hash('sha256', $password);

    $sql = " SELECT id, email, senha, nome, status, unidade ";
    $sql .= " FROM conselho.usuarios ";
    $sql .= " WHERE email = '$username' AND senha = '$password' AND status=1";

    $result = DBExecute($sql);
    $result = mysqli_num_rows($result);
    // var_dump($result);

    return $result;
    DBClose($link); // Feche a conexão com o banco de dados
}

function getFasesRecurso()
{
    $sql = " SELECT id, texto
				FROM conselho.fase";

    $result = DBExecute($sql);
    if (!mysqli_num_rows($result)) {

    } else {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }
    return $dados;
}

function upsertUsuario($dados)
{
    $email = $dados['email'];
    $senha = isset($dados['senha']) && $dados['senha'] !== '' ? hash('sha256', $dados['senha']) : null;
    $nome = $dados['nome'];
    $status = isset($dados['status']) ? $dados['status'] : 1;
    $unidade = $dados['unidade'];
    $avatar = isset($dados['avatar']) ? $dados['avatar'] : null;

    // Sempre incluir senha no INSERT, mesmo que seja null
    $sql = "INSERT INTO conselho.usuarios (email, senha, nome, status, unidade" . ($avatar !== null ? ", avatar" : "") . ") ";
    $sql .= "VALUES ('$email', '" . ($senha ?? '') . "', '$nome', '$status', '$unidade'" . ($avatar !== null ? ", '$avatar'" : "") . ") ";

    // UPDATE dinâmico
    $sql .= "ON DUPLICATE KEY UPDATE ";
    $updates = [];

    if ($senha !== null) {
        $updates[] = "senha = '$senha'";
    }
    $updates[] = "nome = '$nome'";
    $updates[] = "status = $status";
    $updates[] = "unidade = '$unidade'";

    if (isset($dados['avatar'])) {
        $avatar = $dados['avatar'];
        $updates[] = "avatar = '$avatar'";
    }

    $sql .= implode(", ", $updates);

    // echo $sql; // Para debug, se quiser ver o SQL gerado

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}




function updateUsuario($dados, $avatar = '')
{
    $email = $dados['email'];
    $nome = $dados['nome'];
    $status = isset($_POST['status']) ? $_POST['status'] : 1;
    $unidade = $dados['unidade'];
    $id = $dados['id'];

    $sql = "UPDATE conselho.usuarios ";
    $sql .= "SET ";
    $sql .= "email = '$email', ";
    $sql .= "nome = '$nome', ";
    $sql .= "status = $status, ";
    $sql .= "unidade = '$unidade', ";
    $sql .= "avatar = '$avatar' ";
    $sql .= "where ";
    $sql .= "id = $id ";

    // var_dump($sql);


    // sha2('{$_POST[password]}', '256') 

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function trocaSenha($dados)
{
    $id = $dados["user_id"];
    $senha = hash('sha256', $dados["newPassword"]);

    $sql = "update conselho.usuarios ";
    $sql .= "set ";
    $sql .= "senha = '$senha' ";
    $sql .= "where ";
    $sql .= "id = $id ";

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function upsertDatasDeRetirada($dados)
{
    // dump($dados);
    // Verifique se os campos obrigatórios estão presentes
    if (!isset($dados['notificacao'], $dados['ano'])) {
        return "Campos obrigatórios 'notificacao' e 'ano' não estão preenchidos.";
    }

    // Converta a data para o formato adequado
    // dump($dados['dia_retirada']);
    $dateTimeObject = DateTime::createFromFormat('d/m/Y H:i', $dados['dia_retirada'] . ' 00:00');
    if (!$dateTimeObject) {
        $timestamp = strtotime($dados['dia_retirada']);
        $dateTimeObject = new DateTime("@$timestamp");

    }

    $dados['dia_retirada'] = $dateTimeObject->format('Y-m-d');
    // Verifique se o valor de 'apartamento' está no intervalo desejado
    if (isset($dados['apartamento'])) {
        $apartamento = (int) $dados['apartamento'];
        if ($apartamento < 101 || $apartamento > 1912) {
            return "O valor de 'apartamento' deve estar entre 101 e 1912.";
        }

    }

    // Construa a consulta SQL de inserção/atualização
    $fields = implode(', ', array_keys($dados));
    $values = implode(', ', array_map(function ($value) {
        return $value !== null ? "'" . DBEscape($value) . "'" : 'NULL';
    }, $dados));

    $sql = "INSERT INTO DatasDeRetirada ($fields) VALUES ($values) ";
    $sql .= "ON DUPLICATE KEY UPDATE ";

    foreach ($dados as $key => $value) {
        if ($value !== null && $key !== 'notificacao' && $key !== 'ano') {
            $sql .= "$key = '$value', ";
        }
    }

    // Remova a última vírgula e espaço desnecessários
    $sql = rtrim($sql, ', ');

    // dump($sql);

    // Execute a consulta
    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "Erro na execução da consulta.";
    }
}


function getDatasDeRetiradaByID($id)
{
    $sql = "select * from  DatasDeRetirada where virtual = '$id' ";


    $result = DBExecute($sql);
    $dados = array();

    if (mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}


function upsertNotificacao($dados)
{
    // Verifique se os campos obrigatórios estão presentes
    if (!isset($dados['ano'], $dados['numero'])) {
        return "Campos obrigatórios 'ano' e 'numero' não estão preenchidos.";
    }

    // Construa a consulta SQL de inserção/atualização
    $fields = implode(', ', array_keys($dados));
    $values = implode(', ', array_map(function ($value) {
        return $value !== null ? "'" . DBEscape($value) . "'" : 'NULL';
    }, $dados));

    $sql = "INSERT INTO notificacoes \n ($fields) \n VALUES \n ($values) \n";
    $sql .= "ON DUPLICATE KEY UPDATE \n";

    foreach ($dados as $key => $value) {
        if ($value !== null) {
            $sql .= "$key = VALUES($key), ";
        }
    }
    // Remova a última vírgula e espaço desnecessários
    $sql = rtrim($sql, ', ');

    styledDump($sql);
    // Execute a consulta
    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "Erro na execução da consulta.";
    }
}

function upsertEstacionamento($dados)
{
    // Verifique se os campos obrigatórios estão presentes
    if (!isset($dados['id_estacionamento'], $dados['bloco'], $dados['unidade'], $dados['tipo'], $dados['local'])) {
        return "Campos obrigatórios 'id_estacionamento', 'bloco', 'unidade' e 'tipo' e 'local' não estão preenchidos.";
    }

    // Construa a consulta SQL de inserção/atualização
    $fields = implode(', ', array_keys($dados));
    $values = implode(', ', array_map(function ($value) {
        return $value !== null ? "'" . DBEscape($value) . "'" : 'NULL';
    }, $dados));

    $sql = "INSERT INTO estacionamento ($fields) VALUES ($values) ";
    $sql .= "ON DUPLICATE KEY UPDATE ";

    foreach ($dados as $key => $value) {
        if ($value !== null) {
            $sql .= "$key = VALUES($key), ";
        }
    }
    // Remova a última vírgula e espaço desnecessários
    $sql = rtrim($sql, ', ');
    // var_dump($sql);

    // Execute a consulta
    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "Erro na execução da consulta.";
    }
}

function upsertRecurso($dados)
{
    // Verifique se os campos obrigatórios estão presentes
    if (!isset($dados['unidade'], $dados['bloco'], $dados['numero'], $dados['fase'])) {
        return "Campos obrigatórios não estão preenchidos.";
    }

    // Construa a consulta SQL de inserção/atualização
    $fields = implode(', ', array_keys($dados));
    $values = implode(', ', array_map(function ($value) {
        return $value !== null ? "'" . DBEscape($value) . "'" : 'NULL';
    }, $dados));

    $sql = "INSERT INTO recurso ($fields) VALUES ($values) ";
    $sql .= "ON DUPLICATE KEY UPDATE ";

    foreach ($dados as $key => $value) {
        if ($value !== null) {
            $sql .= "$key = VALUES($key), ";
        }
    }
    // Remova a última vírgula e espaço desnecessários
    $sql = rtrim($sql, ', ');

    // Execute a consulta
    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "Erro na execução da consulta.";
    }
}

function upsertComentario($dados)
{
    $id_recurso = $dados['id_recurso'];
    $id_usuario = $dados['user_id']; // Supondo que você tenha o ID do usuário na sessão
    $mensagem = DBEscape($dados['messageText']);

    $sql = "INSERT INTO conselho.mensagem ";
    $sql .= "(id_usuario, id_recurso, texto) ";
    $sql .= "VALUES ('$id_usuario', '$id_recurso', '$mensagem') ";

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function upsertDiligencia($dados)
{
    $id_recurso = $dados['id_recurso'];
    $id_usuario = $dados['user_id']; // Supondo que você tenha o ID do usuário na sessão
    $mensagem = DBEscape($dados['messageText']);

    $sql = "INSERT INTO conselho.diligencia ";
    $sql .= "(id_usuario, id_recurso, texto) ";
    $sql .= "VALUES ('$id_usuario', '$id_recurso', '$mensagem') ";

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function updateComentario($dados)
{
    $id_comentario = $dados['id_comentario'];
    $mensagem = DBEscape($dados['comentario']);
    $usuario = DBEscape($dados['usuario']);

    $sql = "update conselho.mensagem ";
    $sql .= "set texto =  '$mensagem' ";
    $sql .= "where id = $id_comentario ";
    $sql .= "and  id_usuario = $usuario ";

    // echo $sql;

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function upsertGmailToken($access_token, $expires_in, $scope, $token_type, $refresh_token = null)
{

    $sql = "INSERT INTO conselho.tokens  ";
    $sql .= "(access_token, expires_in, scope, token_type, refresh_token) ";
    $sql .= "VALUES ('$access_token', $expires_in, '$scope', '$token_type', '$refresh_token') ";
    // $sql .= "ON DUPLICATE KEY UPDATE  ";
    // $sql .= "access_token = '$access_token',  ";
    // $sql .= "expires_in = $expires_in,  ";
    // $sql .= "scope = '$scope',  ";
    // $sql .= "token_type = '$token_type'  ";

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function getMaisVotado($id_recurso)
{
    $sql2 = " SELECT voto, COUNT(*) AS total ";
    $sql2 .= " FROM conselho.votos  ";
    $sql2 .= " WHERE id_recurso = $id_recurso  ";
    $sql2 .= " GROUP BY voto  ";
    $sql2 .= " HAVING total >= 2 ";

    $result = DBExecute($sql2);
    $result = DBExecute($sql2);

    if (!mysqli_num_rows($result)) {

    } else {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $r[] = $retorno;
        }
    }
    // Só deve vir uma linha mesmo...
    return $r[0];
}

function getEstacionamento($bloco, $unidade)
{
    $sql2 = " select * ";
    $sql2 .= " FROM conselho.estacionamento  ";
    $sql2 .= " WHERE bloco = '$bloco' ";
    $sql2 .= " and unidade = $unidade  ";

    $result = DBExecute($sql2);
    $result = DBExecute($sql2);

    if (!mysqli_num_rows($result)) {

    } else {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $r[] = $retorno;
        }
    }
    return $r;
}

function upsertVoto($dados)
{
    $id_recurso = $dados['idRec'];
    $id_usuario = $dados['user_id'];
    $voto = $dados['voto'];

    $sql = "INSERT INTO conselho.votos ";
    $sql .= "(id_recurso, id_usuario, voto) ";
    $sql .= "VALUES ('$id_recurso', '$id_usuario', '$voto') ";
    $sql .= "ON DUPLICATE KEY UPDATE ";
    $sql .= "voto = '$voto'";



    if (DBExecute($sql)) {

        $sql2 = " SELECT voto, COUNT(*) AS total ";
        $sql2 .= " FROM conselho.votos  ";
        $sql2 .= " WHERE id_recurso = $id_recurso  ";
        $sql2 .= " GROUP BY voto  ";
        $sql2 .= " HAVING total >= 2 ";

        $result = DBExecute($sql2);

        if (!mysqli_num_rows($result)) {

        } else {
            while ($retorno = mysqli_fetch_assoc($result)) {
                $numVotos[] = $retorno;
            }
        }

        if (isset($numVotos[0]["total"]) && $numVotos[0]["total"] >= 2) {
            // Se tem mais de 1 voto igual para este recurso, mude a fase para confecionar parecer
            $sql3 = "update conselho.recurso ";
            $sql3 .= "set ";
            $sql3 .= "fase = 4 ";
            $sql3 .= "where ";
            $sql3 .= "id = '$id_recurso'";

            $result = DBExecute($sql3);

        } else {
            // Senão configura como em análise
            $sql3 = "update conselho.recurso ";
            $sql3 .= "set ";
            $sql3 .= "fase = 3 ";
            $sql3 .= "where ";
            $sql3 .= "id = '$id_recurso'";

            $result = DBExecute($sql3);

        }

        return "ok";
    } else {
        return "erro";
    }
}

function upsertFase($dados)
{
    $id_recurso = $dados['idRec'];
    $id_usuario = $dados['user_id'];
    $fase = $dados['fase'];

    // return var_dump($dados);
    $sql = "update conselho.recurso ";
    $sql .= "set ";
    $sql .= "fase = '$fase' ";
    $sql .= "where ";
    $sql .= "id = '$id_recurso'";

    if (DBExecute($sql)) {
        return "ok";
    } else {
        return "erro";
    }
}

function isTokenExpired($expirationTime)
{
    $currentTime = time();
    return $expirationTime <= $currentTime;
}

// Função para obter o último token do banco
function getLastTokenFromDatabase()
{
    $sql = "SELECT * FROM conselho.tokens ORDER BY id DESC LIMIT 1";

    $dados = null;  // Inicializa $dados como nulo

    $result = DBExecute($sql);
    if (mysqli_num_rows($result) > 0) {
        $dados = mysqli_fetch_assoc($result);
    }
    return $dados;
}
// Função para obter o último token do banco
function getLastRefreshTokenFromDatabase()
{
    $sql = "SELECT refresh_token FROM conselho.tokens where refresh_token != 'NULL' ORDER BY id DESC LIMIT 1";

    $dados = null;  // Inicializa $dados como nulo

    $result = DBExecute($sql);
    if (mysqli_num_rows($result) > 0) {
        $dados = mysqli_fetch_assoc($result);
    }
    return $dados["refresh_token"];
}

// Função principal para verificar o token
function verificarToken()
{
    // Obter o último token do banco
    $lastToken = getLastTokenFromDatabase();

    // var_dump($lastToken);

    // Verificar se o token está vazio
    if (!$lastToken) {
        echo "Nenhum token encontrado no banco.";
        return;
    }

    $tokenData = $lastToken;
    $expirationTime = strtotime($tokenData["created_at"]) + $tokenData["expires_in"];
    $timeRemaining = $expirationTime - time();

    $r['status'] = !isTokenExpired($expirationTime);
    $r['resta'] = $timeRemaining;
    $r['tkn'] = $tokenData["access_token"];
    // Verificar se o token está expirado

    return $r;
}



function getEstatisticasNotificacoes($ano = null, $agruparPor = 'assunto')
{
    if ($ano === null) {
        $ano = date('Y');
    }

    $campo = ($agruparPor == 'torre') ? 'torre' : 'assunto';

    $sql = "SELECT
                UPPER($campo) AS chave_agrupado,
                SUM(CASE WHEN UPPER(notificacao) = 'MULTA' THEN 1 ELSE 0 END) as total_multas,
                SUM(CASE WHEN UPPER(notificacao) = 'ADVERTENCIA' THEN 1 ELSE 0 END) as total_advertencias,
                COUNT(*) as total
            FROM conselho.notificacoes
            WHERE ano = '$ano'
            GROUP BY chave_agrupado
            ORDER BY total DESC
            LIMIT 15";

    $result = DBExecute($sql);
    $dados = array();

    if ($result && mysqli_num_rows($result) > 0) {
        while ($retorno = mysqli_fetch_assoc($result)) {
            $dados[] = $retorno;
        }
    }

    return $dados;
}

function getResumoGeral($ano = null)
{
    if ($ano === null) {
        $ano = date('Y');
    }

    $resumo = array();

    // Total de Recursos
    $sql = "SELECT COUNT(*) as total FROM conselho.recurso";
    $res = DBExecute($sql);
    $resumo['total_recursos'] = mysqli_fetch_assoc($res)['total'];

    // Recursos em Aberto (fase < 5)
    $sql = "SELECT COUNT(*) as total FROM conselho.recurso WHERE fase < 5";
    $res = DBExecute($sql);
    $resumo['recursos_abertos'] = mysqli_fetch_assoc($res)['total'];

    // Total de Pareceres no ano
    $sql = "SELECT COUNT(*) as total FROM conselho.parecer WHERE YEAR(data) = '$ano'";
    $res = DBExecute($sql);
    $resumo['pareceres_ano'] = mysqli_fetch_assoc($res)['total'];

    // Total de Notificações no ano
    $sql = "SELECT COUNT(*) as total FROM conselho.notificacoes WHERE ano = '$ano'";
    $res = DBExecute($sql);
    $resumo['notificacoes_ano'] = mysqli_fetch_assoc($res)['total'];

    return $resumo;
}

?>