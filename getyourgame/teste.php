<?php
require_once '../include/DbHandler.php';

$db = new DbHandler();

$response = $db->getUserById(5);

$foto = $response["foto"];

/*
echo "<pre>";
print_r($response);
echo "</pre>";
*/

header("Content-Type: image/jpeg");
echo $foto;
?>