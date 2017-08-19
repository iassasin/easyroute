<?php

namespace Iassasin\Easyroute;

class RouteFilter {
	public function preRoute($path, $controller, $action, $args){
		return Router::CONTINUE;
	}
}
