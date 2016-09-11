<?php

class Route {
	private $path;
	private $defaults;
	private $filters;
	
	public function __construct($path, array $defaults = [], array $filters = []){
		$apath = explode('/', $path);
		if ($apath[0] == ''){
			array_shift($apath);
		}
		
		$this->path = $apath;
		$this->defaults = $defaults;
		$this->filters = $filters;
	}
	
	public function match(array $path){
		$res = [
			'controller' => null,
			'action' => null,
		];
		
		$pcnt = count($path);
		$rcnt = count($this->path);
		
		if ($pcnt > $rcnt){
			return false;
		}
		
		for ($i = 0; $i < $pcnt; ++$i){
			if ($this->path[$i]{0} == '{'){
				$arg = substr($this->path[$i], 1, -1);
				
				if (array_key_exists($arg, $this->filters)){
					if (preg_match($this->filters[$arg], $path[$i]) !== 1){
						return false;
					}
				}
				
				$res[$arg] = $path[$i];
			} else if ($this->path[$i] != $path[$i]){
				return false;
			}
		}
		
		if ($i < $rcnt){
			for ( ; $i < $rcnt; ++$i){
				if ($this->path[$i]{0} != '{'){
					return false;
				}
				
				$arg = substr($this->path[$i], 1, -1);
				if (!array_key_exists($arg, $this->defaults)){
					return false;
				}
				
				$res[$arg] = $this->defaults[$arg];
			}
		}
		
		foreach ($this->defaults as $defk => $defv){
			if (!array_key_exists($defk, $res) || $res[$defk] == ''){
				$res[$defk] = $defv;
			}
		}
		
		if ($res['controller'] == '' || $res['action'] == ''){
			return false;
		}
		
		return $res;
	}
}

class Router {
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
			if ($ctl = $r->match($path)){
				$obj = $this->loadController($ctl['controller']);
				if ($obj !== null && method_exists($obj, $ctl['action'])){
					call_user_func_array([$obj, $ctl['action']], $this->createArgsFor($obj, $ctl['action'], $ctl));
					return;
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
	
	private function loadController($controller){
		if (strpos($controller, '.') !== false){
			return null;
		}
		
		$cp = $this->ctl_path.$controller.'.php';
		$cn = $this->ctl_prefix.$controller;
		
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
