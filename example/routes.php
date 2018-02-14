<?php

require_once '../vendor/autoload.php';

use Iassasin\Easyroute\Router;
use Iassasin\Easyroute\Route;

$router = new Router();
$router->setControllersPath(__DIR__.'/controllers/');
$router->addRoutes([
	new Route('/static/route', ['controller' => 'home', 'action' => 'staticRoute', 'arg' => 'static arg']),
	new Route('/(:controller:(/:action:(/:arg)?)?)?', ['controller' => 'home', 'action' => 'index', 'arg' => null]),
]);
$router->processRoute();
