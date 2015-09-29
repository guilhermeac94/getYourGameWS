<html><head><title>Slim Application Error</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body><h1>Slim Application Error</h1><p>The application could not run because of the following error:</p><h2>Details</h2><div><strong>Type:</strong> ErrorException</div><div><strong>Code:</strong> 2</div><div><strong>Message:</strong> mysqli::mysqli(): (HY000/1049): Unknown database 'tcc'</div><div><strong>File:</strong> C:\wamp\www\troca_jogos\include\DbConnect.php</div><div><strong>Line:</strong> 24</div><h2>Trace</h2><pre>#0 [internal function]: Slim\Slim::handleErrors(2, 'mysqli::mysqli(...', 'C:\\wamp\\www\\tro...', 24, Array)
#1 C:\wamp\www\troca_jogos\include\DbConnect.php(24): mysqli->mysqli('localhost', 'root', '', 'tcc')
#2 C:\wamp\www\troca_jogos\include\DbHandler.php(18): DbConnect->connect()
#3 C:\wamp\www\troca_jogos\prototipo\index.php(81): DbHandler->__construct()
#4 [internal function]: {closure}()
#5 C:\wamp\www\troca_jogos\libs\Slim\Route.php(436): call_user_func_array(Object(Closure), Array)
#6 C:\wamp\www\troca_jogos\libs\Slim\Slim.php(1307): Slim\Route->dispatch()
#7 C:\wamp\www\troca_jogos\libs\Slim\Middleware\Flash.php(85): Slim\Slim->call()
#8 C:\wamp\www\troca_jogos\libs\Slim\Middleware\MethodOverride.php(92): Slim\Middleware\Flash->call()
#9 C:\wamp\www\troca_jogos\libs\Slim\Middleware\PrettyExceptions.php(67): Slim\Middleware\MethodOverride->call()
#10 C:\wamp\www\troca_jogos\libs\Slim\Slim.php(1254): Slim\Middleware\PrettyExceptions->call()
#11 C:\wamp\www\troca_jogos\prototipo\index.php(345): Slim\Slim->run()
#12 {main}</pre></body></html>

<?php
	echo '<br>teste';
?>