<?php

namespace Iassasin\Easyroute;

class Route {
	private $pathrx;
	private $varGroups;
	private $defaults;
	private $ctlSubpath;
	private $routeFilter;

	public function __construct(string $pathrx, array $defaults = []){
		$this->defaults = $defaults;
		$this->setRegexRoute($pathrx);
		$this->ctlSubpath = '';
		$this->routeFilter = null;
	}

	public function setControllersSubpath(string $path){
		$this->ctlSubpath = $path;
		return $this;
	}

	public function setFilter(RouteFilter $filter){
		$this->routeFilter = $filter;
		return $this;
	}

	public function getControllersSubpath(){
		return $this->ctlSubpath;
	}

	public function getFilter(){
		return $this->routeFilter;
	}

	public function match(string $url){
		$argspos = strpos($url, '?');
		if ($argspos !== false){
			$url = substr($url, 0, $argspos);
		}

		$res = $this->defaults;
		if (!array_key_exists('controller', $res)) $res['controller'] = null;
		if (!array_key_exists('action', $res)) $res['action'] = null;

		if (preg_match($this->pathrx, $url, $match) === 1){
			foreach ($this->varGroups as $name){
				if (array_key_exists($name, $match) && $match[$name] != ''){
					$res[$name] = urldecode($match[$name]);
				}
			}
		} else {
			return false;
		}

		if ($res['controller'] == '' || $res['action'] == ''){
			return false;
		}

		return $res;
	}

	private function setRegexRoute(string $rx){
		$path = Path::parse($rx);
		$this->pathrx = $path->regex;
		$this->varGroups = $path->varGroups;
	}
}
