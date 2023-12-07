<?php
include "classes/repositorio.php";
$sql = "SELECT id, unidade, bloco, numero, artigo, fase, email, Nome, detalhes, titulo, `data`, fato
FROM conselho.recurso where id={$_GET['rec']}";
$result = DBExecute($sql);

echo "<div class='container'>";
echo "<div class='row'>";

if (mysqli_num_rows($result) > 0) {
	
    $recurso = mysqli_fetch_assoc($result);
	
	echo '<form id="atualizarRecursoForm">';
	echo '<input type="hidden" name="id" value="' . $recurso['id'] . '">';
	
	
	echo '<label for="nome">Nome:</label>';
	echo '<input type="text" name="nome" value="' . $recurso['Nome'] . '">';

	echo '<label for="titulo">Título:</label>';
	echo '<input type="text" name="titulo" value="' . $recurso['titulo'] . '">';
	
	
	$fases = getFasesRecurso();
	echo '<div class="col s12 m6 l6">';
	echo '<label for="fase">Fase:</label>';
	echo '<select name="fase">';
	foreach ($fases as $fase) {
		$selecionado = $recurso['fase'] == $fase['id'] ? "selected" : "";
		echo '<option value="' . $fase['id'] . '" '.$selecionado.'>' . $fase['texto'] . ' </option>';
	}
	echo '</select>';
	echo '</div>';
	
	echo '<div class="col s12 m6 l6">';
	echo '<label for="unidade">Unidade:</label>';
	echo '<input type="text" name="unidade" value="' . $recurso['unidade'] . '">';
	echo '</div>';
	
	echo '<div class="col s12 m6 l6">';
	echo '<label for="bloco">Bloco:</label>';
	echo '<input type="text" name="bloco" value="' . $recurso['bloco'] . '">';
	echo '</div>';
	
	echo '<div class="col s12 m6 l6">';
	echo '<label for="numero">Número:</label>';
	echo '<input type="text" name="numero" value="' . $recurso['numero'] . '">';
	echo '</div>';
	
	echo '<div class="col s12 m6 l6">';
	echo '<label for="artigo">Artigo:</label>';
	echo '<input type="text" name="artigo" value="' . $recurso['artigo'] . '">';
	echo '</div>';
	
	echo '<div class="col s12 m6 l6">';
	echo '<label for="data">Data:</label>';
	echo '<input type="text" name="data" value="' . $recurso['data'] . '">';
	echo '</div>';
	
	
	echo '<label for="email">Email:</label>';
	echo '<input type="email" name="email" value="' . $recurso['email'] . '">';
	
	echo '<div class="col s12">';	
	echo '<label for="detalhes">Detalhes:</label>';
	echo '<textarea class="materialize-textarea" name="detalhes">' . $recurso['detalhes'] . '</textarea>';
	echo '</div>';	
	
	echo '<div class="col s12">';	
	echo '<label for="fato">Fato:</label>';
	echo '<textarea class="materialize-textarea fato" name="fato">' . $recurso['fato'] . '</textarea>';
	echo '</div>';
	
	echo '<button class="btn orange darken-3" id="atualizarRecurso" type="submit">Atualizar</button>';
	echo '<a href="index.php?pag=recurso&rec='.$recurso['numero'].'"class="btn red darken-3 right" >Sair</a>';
	echo '</form>';
}
	echo "<br/>";

echo "</div>";
echo "</div>";
?>
