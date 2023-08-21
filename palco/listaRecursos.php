<div class="container">
        <h3>Recursos Registrados</h3>
        <table class="striped">
            <thead>
                <tr>
                    <th>Unidade</th>
                    <th>Número</th>
                    <th>Data</th>
                    <th>Assunto</th>
                </tr>
            </thead>
            <tbody>
                <!-- Loop para exibir os recursos -->
                <?php
                require "classes/database.php"; // Certifique-se de incluir o arquivo de conexão com o banco de dados
                $sql = "SELECT id, unidade, bloco, numero, fase, titulo, data FROM recurso";
                $result = DBExecute($sql);
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr class='recurso' rec='{$row['numero']}'>";
                    // echo "<td>{$row['id']}</td>";
                    echo "<td>{$row['bloco']}{$row['unidade']}</td>";
                    echo "<td>{$row['numero']}</td>";
                    echo "<td>{$row['data']}</td>";
                    echo "<td>{$row['titulo']}</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>