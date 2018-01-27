<?php

namespace Iassasin\Easyroute;

use Iassasin\Easyroute\Http\Request;
use Iassasin\Easyroute\Http\Response;
use Psr\Container\ContainerInterface;

class Router {
	const CONTINUE = 0;
	const COMPLETED = 1;

	private $ctl_path = './';
	private $routes = [];
	private $handler404 = null;
	private $ctl_prefix = 'Controller';
	private $container;

	public function __construct(){
		$this->container = new SimpleContainer();
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

	/**
	 * Get dependency injection container
	 * @return ContainerInterface
	 */
	public function getContainer(){
		return $this->container;
	}

	public function setContainer(ContainerInterface $container){
		$this->container = $container;
	}

	public function addRoutes(array $routes){
		$this->routes = $routes;
	}

	private function createArgsFor($obj, $method, $values){
		$f = new \ReflectionMethod($obj, $method);
		$params = $f->getParameters();
		$res = array_fill(0, count($params), null);
		foreach ($params as $p){
			if (array_key_exists($p->name, $values)){
				$res[$p->getPosition()] = $values[$p->name];
			}
			else if ($p->getClass() != null){
				//$p->isBuiltin not exists in PHP 5.6
				$res[$p->getPosition()] = $this->container->get($p->getClass()->name);
			}
		}
		return $res;
	}

	public function processRoute(Request $req = null){
		if ($req === null){
			$req = Request::createFromGlobals();
		}

		if ($this->container instanceof SimpleContainer){
			$this->container->setService(Request::class, $req);
		}

		$spath = $req->getUri();
		$path = Route::splitPathFromURI($spath);

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

						$resp = call_user_func_array([$obj, $ctl['action']], $this->createArgsFor($obj, $ctl['action'], $ctl));
						if ($resp !== null){
							if (!($resp instanceof Response)){
								$resp = new Response($resp);
							}

							$resp->send();
						}

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
