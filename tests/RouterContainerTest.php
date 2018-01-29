<?php
/**
 * Author: iassasin <iassasin@yandex.ru>
 * License: beerware
 * Use for good
 */

use PHPUnit\Framework\TestCase;
use Iassasin\Easyroute\Router;
use Iassasin\Easyroute\Route;
use Iassasin\Easyroute\Http\Request;
use Iassasin\Easyroute\SimpleContainer;
use Iassasin\Easyroute\ServiceNotFoundException;
use Iassasin\Easyroute\Http\Response;
use Iassasin\Easyroute\Http\Responses\Response404;
use Psr\Container\ContainerInterface;

/**
 * @covers \Iassasin\Easyroute\Router
 * @covers \Iassasin\Easyroute\Route
 * @covers \Iassasin\Easyroute\Http\Request
 * @covers \Iassasin\Easyroute\Http\Parameters
 * @covers \Iassasin\Easyroute\SimpleContainer
 * @covers \Iassasin\Easyroute\ServiceNotFoundException
 * @covers \Iassasin\Easyroute\Http\Response
 * @covers \Iassasin\Easyroute\Http\Responses\Response404
 */
class RouterContainerTest extends TestCase {
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

		$router->setStatusHandler(404, function(Response404 $resp){
			echo '404: '.$resp->getUrl();
			return true;
		});

		return $router;
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
