<?php
/**
 * Author: iassasin <iassasin@yandex.ru>
 * License: beerware
 * Use for good
 */

use Iassasin\Easyroute\Router;
use Iassasin\Easyroute\Route;
use Iassasin\Easyroute\RouteFilter;
use Iassasin\Easyroute\Http\Request;
use Iassasin\Easyroute\SimpleContainer;
use Iassasin\Easyroute\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * @covers \Iassasin\Easyroute\Router
 * @covers \Iassasin\Easyroute\Route
 * @covers \Iassasin\Easyroute\RouteFilter
 * @covers \Iassasin\Easyroute\Http\Request
 * @covers \Iassasin\Easyroute\Http\Parameters
 * @covers \Iassasin\Easyroute\SimpleContainer
 * @covers \Iassasin\Easyroute\ServiceNotFoundException
 */
class RouterTest extends PHPUnit_Framework_TestCase {
	private function _test(Router $router, $route, $expected){
		ob_start();
		try {
			$router->processRoute(new Request(
				[], // query
				[], // request
				[], // attributes
				[], // cookies
				[], // files
				[ // server
					'REQUEST_URI' => $route,
				],
				'' // content
			));
			$out = ob_get_clean();
			$this->assertEquals($expected, $out);
		} catch (\Exception $e){
			ob_get_clean();
			throw $e;
		}
	}

	private function _testWithDI(Router $router, $route, $expected){
		ob_start();
		try {
			$req = new Request(
				[], // query
				[], // request
				[], // attributes
				[], // cookies
				[], // files
				[ // server
					'REQUEST_URI' => $route,
				],
				'' // content
			);
			$router->setContainer(new ExternalContainer($req));
			$router->processRoute($req);
			$out = ob_get_clean();
			$this->assertEquals($expected, $out);
		} catch (\Exception $e){
			ob_get_clean();
			throw $e;
		}
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

	public function testStaticRoutes(){
		$router = $this->_initRouter([
			new Route('/', ['controller' => 'foo', 'action' => 'index', 'arg' => null]),
		]);

		$this->_test($router, '', 'foo/index: ');
		$this->_test($router, '/', 'foo/index: ');

		$router = $this->_initRouter([
			new Route('/test/test', ['controller' => 'foo', 'action' => 'test']),
		]);

		$this->_test($router, 'test/test', 'foo/test');
		$this->_test($router, '/test/test', 'foo/test');
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
		$this->_test($router, '/', 'foo/index: ');
		$this->_test($router, 'foo', 'foo/index: ');
		$this->_test($router, 'foo/', 'foo/index: ');
		$this->_test($router, '/foo', 'foo/index: ');
		$this->_test($router, '/foo/', 'foo/index: ');
		$this->_test($router, 'foo/index/', 'foo/index: ');
		$this->_test($router, 'foo/index/var', 'foo/index: var');

		$this->_test($router, 'bar', 'bar/index: ');
		$this->_test($router, 'bar/test', 'bar/test');
		$this->_test($router, 'bar/index/var', 'bar/index: var');

		$this->_test($router, '/bar', 'bar/index: ');
		$this->_test($router, '/bar/test', 'bar/test');
		$this->_test($router, '/bar/index/var', 'bar/index: var');
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

	public function testDIContainerServiceNotFound(){
		$router = $this->_initRouter([
			new Route('/{controller}/{action}/{arg}', []),
			new Route('/{controller}/{action}', []),
		]);

		$this->expectException(ServiceNotFoundException::class);
		$this->_test($router, 'di/req6/abc', 'di/req6');
	}

	public function testDIContainer(){
		$router = $this->_initRouter([
			new Route('/{controller}/{action}/{arg}', []),
			new Route('/{controller}/{action}', []),
		]);

		$this->_makeDITests($router, [$this, '_test']);
		$this->_test($router, 'di/req7/abc', 'di/req7: abc, s2s1, s1');
	}

	public function testExternalDIContainer(){
		$router = $this->_initRouter([
			new Route('/{controller}/{action}/{arg}', []),
			new Route('/{controller}/{action}', []),
		]);

		$this->_makeDITests($router, [$this, '_testWithDI']);
	}

	public function testNoAutowireContainer(){
		$router = $this->_initRouter([
			new Route('/{controller}/{action}/{arg}', []),
			new Route('/{controller}/{action}', []),
		]);
		$router->getContainer()->setAutowireEnabled(false);

		$this->_makeDITests($router, [$this, '_test']);

		$this->expectException(ServiceNotFoundException::class);
		$this->_test($router, 'di/req7/abc', 'di/req7: abc, s2s1, s1');
	}

	private function _makeDITests($router, $tester){
		$tester($router, 'di/req1', 'di/req1');
		$tester($router, 'di/req2/123', 'di/req2: 123');
		$tester($router, 'di/req3', 'di/req3: uri: di/req3');
		$tester($router, 'di/req4/abc', 'di/req4: abc, uri: di/req4/abc');
		$tester($router, 'di/req5/abc', 'di/req5: abc, uri: di/req5/abc');
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

class ExternalContainer implements ContainerInterface {
	private $request;

	public function __construct(Request $req){
		$this->request = $req;
	}

	public function get($id){
		if ($id == Request::class)
			return $this->request;

		throw new ServiceNotFoundException();
	}

	public function has($id){
		return $id == Request::class;
	}
}
