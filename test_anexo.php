<?php
require_once "classes/repositorio.php";
$res = DBExecute("SELECT * FROM recurso_anexos");
$d = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $d[] = $r;
    }
}
print_r($d);
