<?php

namespace Iassasin\Easyroute;

use Iassasin\Easyroute\Http\Request;
use Iassasin\Easyroute\Http\Response;
use Iassasin\Easyroute\Http\Responses\Response404;
use Iassasin\Easyroute\Http\Responses\Response500;
use Psr\Container\ContainerInterface;

class Router {
	const CONTINUE = 0;
	const COMPLETED = 1;

	private $ctl_path = './';
	private $routes = [];
	private $ctl_prefix = 'Controller';
	private $container;
	private $statusHandlers = [];
	private $responseHandlers = [];

	public function __construct(){
		$this->container = new SimpleContainer();
	}

	public function setControllersPath($path){
		$this->ctl_path = $path;
	}

	/**
	 * Set handler function for status code
	 * @param int $statusCode
	 * @param mixed $func function(Response $response)
	 */
	public function setStatusHandler($statusCode, $func){
		if (is_callable($func)){
			$this->statusHandlers[$statusCode] = $func;
		}
	}

	/**
	 * Set handler function for specified response class
	 * @param string $classFullName
	 * @param mixed $func function(Response $response)
	 */
	public function setResponseHandler($classFullName, $func){
		if (is_callable($func)){
			$this->responseHandlers[$classFullName] = $func;
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
		try {
			if ($req === null){
				$req = Request::createFromGlobals();
			}

			if ($this->container instanceof SimpleContainer){
				$this->container->setService(Request::class, $req);
			}

			$path = $req->getUri();

			foreach ($this->routes as $r){
				$filter = $r->getFilter();
				if ($ctl = $r->match($path)){
					$obj = $this->loadController($r->getControllersSubpath(), $ctl['controller']);
					if ($obj !== null && method_exists($obj, $ctl['action'])){
						if (is_callable([$obj, $ctl['action']])){
							if ($this->callFilterPreRoute($filter, $path, $obj, $ctl)){
								return;
							}

							$resp = call_user_func_array([$obj, $ctl['action']], $this->createArgsFor($obj, $ctl['action'], $ctl));
							if ($resp !== null){
								if (!($resp instanceof Response)){
									$resp = new Response($resp);
								}

								$this->processResponse($resp);
							}

							return;
						}
					}
					break;
				}
			}

			$resp = new Response404($path);
			$this->processResponse($resp);
		} catch (\Throwable $ex){
			$resp = new Response500($ex);
			$this->processResponse($resp);
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
			$obj = SimpleContainer::createInstance($this->container, $cn);
			return $obj;
		}

		return null;
	}

	/**
	 * @param RouteFilter|null $filter
	 * @param string $spath
	 * @param mixed $controller
	 * @param array $args Arguments for controller from url template
	 * @return bool Is route processing completed (no need to call controller's action)
	 */
	private function callFilterPreRoute($filter, $spath, $controller, array $args){
		if ($filter){
			$res = $filter->preRoute($spath, $controller, $args['action'], $args);
			if ($res == self::COMPLETED){
				return true;
			}
		}

		return false;
	}

	private function processResponse(Response $resp){
		// response class handler
		if (count($this->responseHandlers) > 0){
			$class = new \ReflectionClass(get_class($resp));
			do {
				$className = $class->name;
				if (array_key_exists($className, $this->responseHandlers)
					&& $this->responseHandlers[$className]($resp) === true)
				{
					return;
				}
				$class = $class->getParentClass();
			} while ($class !== false);
		}

		// status code handler
		$code = $resp->getStatusCode();
		if (array_key_exists($code, $this->statusHandlers) && $this->statusHandlers[$code]($resp) === true){
			return;
		}

		$resp->send();
	}
}
