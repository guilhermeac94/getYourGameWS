<?php
require_once '../include/DbHandler.php';

$db = new DbHandler();

$foto = $db->getFoto(9);

/*
echo "<pre>";
print_r($response);
echo "</pre>";
*/

header("Content-Type: image/jpeg");
echo $foto;
?>