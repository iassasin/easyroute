<?php
/**
 * Author: iassasin <iassasin@yandex.ru>
 * License: beerware
 * Use for good
 */

use Iassasin\Easyroute\Router;
use Iassasin\Easyroute\Route;
use Iassasin\Easyroute\RouteFilter;

/**
 * @covers \Iassasin\Easyroute\Router
 * @covers \Iassasin\Easyroute\Route
 * @covers \Iassasin\Easyroute\RouteFilter
 */
class RouterTest extends PHPUnit_Framework_TestCase {
	private function _test($router, $route, $expected){
		ob_start();
		$router->processRoute($route);
		$out = ob_get_clean();
		$this->assertEquals($expected, $out);
	}

	private function _initRouter($routes){
		$router = new Router();
		$router->setControllersPath(__DIR__.'/controllers/');
		$router->addRoutes($routes);

		$router->setHandler404(function($path){
			echo '404: '.$path;
		});

		return $router;
	}

	public function testBasicRoutes(){
		$router = $this->_initRouter([
			new Route('/test/test', ['controller' => 'foo', 'action' => 'test']),
			new Route('/{controller}/{action}/{arg}', ['controller' => 'foo', 'action' => 'index', 'arg' => null]),
		]);

		$this->_test($router, 'test/test', 'foo/test');
		$this->_test($router, 'foo/test', 'foo/test');
		$this->_test($router, 'foo/test/test', 'foo/test');

		$this->_test($router, '', 'foo/index: ');
		$this->_test($router, 'foo', 'foo/index: ');
		$this->_test($router, 'foo/', 'foo/index: ');
		$this->_test($router, 'foo/index/', 'foo/index: ');
		$this->_test($router, 'foo/index/var', 'foo/index: var');

		$this->_test($router, 'bar', 'bar/index: ');
		$this->_test($router, 'bar/test', 'bar/test');
		$this->_test($router, 'bar/index/var', 'bar/index: var');
	}

	public function testEscapeSymbolsAndGetParams(){
		$router = $this->_initRouter([
			new Route('/test/test', ['controller' => 'foo', 'action' => 'test']),
			new Route('/{controller}/{action}/{arg}', ['controller' => 'foo', 'action' => 'index', 'arg' => null]),
		]);

		$this->_test($router, 'bar/index/var%20%2F%20rav', 'bar/index: var / rav');
		$this->_test($router, 'bar/index/var%20%2F%20rav?var=123&ff=321', 'bar/index: var / rav');
	}

	public function testRegexRoute(){
		$router = $this->_initRouter([
			new Route('/test/{arg}', ['controller' => 'bar', 'action' => 'index'],
				['arg' => '/^\d+$/']),
		]);

		$this->_test($router, 'foo/test', '404: foo/test');

		$this->_test($router, 'test/test', '404: test/test');
		$this->_test($router, 'test', '404: test');
		$this->_test($router, 'test/', '404: test/');
		$this->_test($router, 'test/123', 'bar/index: 123');
	}

	public function testZonesAndFilters(){
		$router = $this->_initRouter([
			(new Route('/zone/{controller}/{action}/{arg}', ['controller' => 'zbar', 'action' => 'index', 'arg' => null]))
				->setControllersSubpath('zone/')
				->setFilter(new NoTestFilter()),
		]);

		$this->_test($router, 'foo/test', '404: foo/test');
		$this->_test($router, 'zfoo/test', '404: zfoo/test');

		$this->_test($router, 'zone/zfoo', 'zone/zfoo/index: ');
		$this->_test($router, 'zone', 'zone/zbar/index: ');
		$this->_test($router, 'zone/zfoo/index/arg', 'zone/zfoo/index: arg');

		$this->_test($router, 'zone/zfoo/test', 'test denied');
		$this->_test($router, 'zone/zbar/test', 'test denied');
		$this->_test($router, 'zone/invalid/test', '404: zone/invalid/test');
	}
}

class NoTestFilter extends RouteFilter {
	public function preRoute($path, $controller, $action, $args){
		if (strpos($path, 'test') !== false){
			echo 'test denied';
			return Router::COMPLETED;
		}

		return Router::CONTINUE;
	}
}
