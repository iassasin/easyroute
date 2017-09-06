<?php

namespace Iassasin\Easyroute;

class Route {
	private $path;
	private $defaults;
	private $filters;
	private $ctl_subpath;
	private $route_filter;

	public static function splitPathFromURI($path){
		$argspos = strpos($path, '?');
		if ($argspos !== false){
			$path = substr($path, 0, $argspos);
		}

		$apath = preg_split('/\/+/', $path);
		foreach ($apath as &$p){
			$p = urldecode($p);
		}

		return $apath;
	}

	public function __construct($path, array $defaults = [], array $filters = []){
		$apath = self::splitPathFromURI($path);
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

				if ($path[$i] == ''){
					break;
				}

				$res[$arg] = $path[$i];
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
