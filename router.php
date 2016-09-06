<?php

class Route {
	private $path;
	private $defaults;
	
	public function __construct($path, array $defaults = []){
		$apath = explode('/', $path);
		if ($apath[0] == ''){
			array_shift($apath);
		}
		
		$this->path = $apath;
		$this->defaults = $defaults;
	}
	
	public function match(array $path){
		$res = [
			'controller' => null,
			'action' => null,
			'args' => [],
		];
		
		$pcnt = count($path);
		$rcnt = count($this->path);
		
		if ($pcnt > $rcnt){
			return false;
		}
		
		for ($i = 0; $i < $pcnt; ++$i){
			if ($this->path[$i]{0} == '{'){
				$arg = substr($this->path[$i], 1, -1);
				
				if ($arg == 'controller' || $arg == 'action'){
					$res[$arg] = $path[$i];
				} else {
					$res['args'][] = [
						'name' => $arg,
						'value' => $path[$i],
					];
				}
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
				
				if ($arg == 'controller' || $arg == 'action'){
					$res[$arg] = $this->defaults[$arg];
				} else {
					$res['args'][] = [
						'name' => $arg,
						'value' => $this->defaults[$arg],
					];
				}
			}
		}
		
		foreach (['controller', 'action'] as $arg){
			if ($res[$arg] === null){
				if (!array_key_exists($arg, $this->defaults)){
					return false;
				}
				
				$res[$arg] = $this->defaults[$arg];
			}
		}
		
		return $res;
	}
}

class Router {
	private $ctl_path = './';
	private $routes = [];
	
	public function __construct(){
		
	}
	
	public function setControllersPath($path){
		$this->ctl_path = $path;
	}
	
	public function addRoutes(array $routes){
		$this->routes = $routes;
	}
	
	public function processRoute($spath){
		$path = explode('/', $spath);
		if ($path[0] == ''){
			array_shift($path);
		}
		
		foreach ($this->routes as $r){
			if ($ctl = $r->match($path)){
				$obj = $this->loadController($ctl['controller']);
				if ($obj !== null && method_exists($obj, $ctl['action'])){
					$args = [];
					foreach ($ctl['args'] as $arg){
						$args[] = $arg['value'];
					}
					
					call_user_func_array([$obj, $ctl['action']], $args);
					
					return;
				}
				break;
			}
		}
		
		header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
		echo '<html><body><h1>404 Not Found</h1> The requested url "<i>'.htmlspecialchars($spath).'</i>" not found!';
	}
	
	private function loadController($controller){
		if (strpos($controller, '.') !== false){
			return null;
		}
		
		$cp = $this->ctl_path.$controller.'.php';
		if (!file_exists($cp)){
			return null;
		}
		
		include_once $cp;
		
		if (class_exists($controller, false)){
			$obj = new $controller;
			return $obj;
		}
		
		return null;
	}
}
