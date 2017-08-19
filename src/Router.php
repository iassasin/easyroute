<?php

namespace Iassasin\Easyroute;

class Router {
	const CONTINUE = 0;
	const COMPLETED = 1;

	private $ctl_path = './';
	private $routes = [];
	private $handler404 = null;
	private $ctl_prefix = 'Controller';

	public function __construct(){

	}

	public function setControllersPath($path){
		$this->ctl_path = $path;
	}

	public function setHandler404($func){
		if (is_callable($func)){
			$this->handler404 = $func;
		}
	}

	public function setControllerClassPrefix($pref){
		$this->ctl_prefix = $pref;
	}

	public function addRoutes(array $routes){
		$this->routes = $routes;
	}

	private function createArgsFor($obj, $method, $values){
		$f = new \ReflectionMethod($obj, $method);
		$res = array_fill(0, count($f), null);
		foreach ($f->getParameters() as $p){
			if (array_key_exists($p->name, $values))
				$res[$p->getPosition()] = $values[$p->name];
		}
		return $res;
	}

	public function processRoute($spath){
		$path = preg_split('/\/+/', $spath);

		foreach ($this->routes as $r){
			$filter = $r->getFilter();
			if ($ctl = $r->match($path)){
				$obj = $this->loadController($r->getControllersSubpath(), $ctl['controller']);
				if ($obj !== null && method_exists($obj, $ctl['action'])){
					if (is_callable([$obj, $ctl['action']])){
						if ($filter){
							$res = $filter->preRoute($spath, $obj, $ctl['action'], $ctl);
							if ($res == self::COMPLETED){
								return;
							}
						}

						call_user_func_array([$obj, $ctl['action']], $this->createArgsFor($obj, $ctl['action'], $ctl));
						return;
					}
				}
				break;
			}
		}

		if ($this->handler404 !== null){
			($this->handler404)($spath);
		} else {
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
			echo '<html><body><h1>404 Not Found</h1> The requested url "<i>'.htmlspecialchars($spath).'</i>" not found!';
		}
	}

	private function loadController($subpath, $controller){
		if (strpos($controller, '.') !== false){
			return null;
		}

		$cn = $this->ctl_prefix.$controller;

		$cp = $this->ctl_path;
		if ($subpath != '') $cp .= '/'.$subpath;
		$cp .= '/'.$controller.'.php';

		if (!file_exists($cp)){
			return null;
		}

		include_once $cp;

		if (class_exists($cn, false)){
			$obj = new $cn;
			return $obj;
		}

		return null;
	}
}
