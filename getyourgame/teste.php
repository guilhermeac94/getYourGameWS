<?php
require_once '../include/DbHandler.php';

$db = new DbHandler();

$response = $db->getTodosJogos(null);
			
echo "<pre>";
print_r($response);
echo "</pre>";
?>