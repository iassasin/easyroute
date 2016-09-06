<?php

require_once 'router.php';

$router = new Router();
$router->setControllersPath($_SERVER['DOCUMENT_ROOT'].'/controllers/');
$router->addRoutes([
	new Route('/{controller}/{action}/{arg}', ['controller' => 'home', 'action' => 'index', 'arg' => null]),
]);
$router->processRoute($_GET['url']);
