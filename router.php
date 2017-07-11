<?php

class Route {
	private $path;
	private $defaults;
	private $filters;
	private $ctl_subpath;
	private $route_filter;

	public function __construct($path, array $defaults = [], array $filters = []){
		$apath = explode('/', $path);
		if ($apath[0] == ''){
			array_shift($apath);
		}

		$this->path = $apath;
		$this->defaults = $defaults;
		$this->filters = $filters;
		$this->ctl_subpath = '';
		$this->route_filter = null;
	}

	public function setControllersSubpath($path){
		$this->ctl_subpath = $path;
		return $this;
	}

	public function setFilter(RouteFilter $filter){
		$this->route_filter = $filter;
		return $this;
	}

	public function getControllersSubpath(){
		return $this->ctl_subpath;
	}

	public function getFilter(){
		return $this->route_filter;
	}

	public function match(array $path){
		$res = $this->defaults;
		if (!array_key_exists('controller', $res)) $res['controller'] = null;
		if (!array_key_exists('action', $res)) $res['action'] = null;

		$pcnt = count($path);
		$rcnt = count($this->path);

		if ($pcnt > $rcnt){
			return false;
		}

		for ($i = 0; $i < $pcnt; ++$i){
			if (strlen($this->path[$i]) > 0 && $this->path[$i]{0} == '{'){
				$arg = substr($this->path[$i], 1, -1);

				if (array_key_exists($arg, $this->filters)){
					if (preg_match($this->filters[$arg], $path[$i]) !== 1){
						return false;
					}
				}

				if ($path[$i] != ''){
					$res[$arg] = $path[$i];
				}
			} else if ($this->path[$i] != $path[$i]){
				return false;
			}
		}

		if ($i < $rcnt){
			for ( ; $i < $rcnt; ++$i){
				if (strlen($this->path[$i]) > 0 && $this->path[$i]{0} != '{'){
					return false;
				}

				$arg = substr($this->path[$i], 1, -1);
				if (!array_key_exists($arg, $res)){
					return false;
				}
			}
		}

		if ($res['controller'] == '' || $res['action'] == ''){
			return false;
		}

		return $res;
	}
}

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
		$f = new ReflectionMethod($obj, $method);
		$res = array_fill(0, count($f), null);
		foreach ($f->getParameters() as $p){
			if (array_key_exists($p->name, $values))
				$res[$p->getPosition()] = $values[$p->name];
		}
		return $res;
	}

	public function processRoute($spath){
		$path = explode('/', $spath);

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

class RouteFilter {
	public function preRoute($path, $controller, $action, $args){
		return Router::CONTINUE;
	}
}
