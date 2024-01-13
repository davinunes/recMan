<?php
require "classes/repositorio.php";

$lista = getAllNotificacoes();

echo "<table class='striped' id='listaSolucoes'>";
echo "<thead>";
echo "<tr>";
	echo "<th>";
		echo "#";
	echo "</th>";
	echo "<th>";
		echo "ano";
	echo "</th>";
	echo "<th>";
		echo "unidade";
	echo "</th>";
	echo "<th>";
		echo "bloco";
	echo "</th>";
	echo "<th>";
		echo "Email";
	echo "</th>";
	echo "<th>";
		echo "Envio";
	echo "</th>";
	echo "<th>";
		echo "Ocorrido";
	echo "</th>";
	echo "<th>";
		echo "Assunto";
	echo "</th>";
	echo "<th>";
		echo "Notificação";
	echo "</th>";
	echo "<th>";
		echo "Cobrança";
	echo "</th>";
	echo "<th>";
		echo "Status";
	echo "</th>";
	echo "<th>";
		echo "Observação";
	echo "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
foreach($lista as $item){
	echo "<tr>";
		echo "<td>";
			echo $item[numero];
		echo "</td>";
		echo "<td>";
			echo $item[ano];
		echo "</td>";
		echo "<td>";
			echo $item[unidade];
		echo "</td>";
		echo "<td>";
			echo $item[torre];
		echo "</td>";
		echo "<td>";
			echo $item[data_email];
		echo "</td>";
		echo "<td>";
			echo $item[data_envio];
		echo "</td>";
		echo "<td>";
			echo $item[data_ocorrido];
		echo "</td>";
		echo "<td>";
			echo $item[assunto];
		echo "</td>";
		echo "<td>";
			echo $item[notificacao];
		echo "</td>";
		echo "<td>";
			echo $item[cobranca];
		echo "</td>";
		echo "<td>";
			echo $item[status];
		echo "</td>";
		echo "<td>";
			echo $item[obs];
		echo "</td>";

	echo "</tr>";
}
echo "</tbody>";
echo "</table>";

// dump($lista);

?>