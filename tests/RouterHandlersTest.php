<?php
/**
 * Author: iassasin <iassasin@yandex.ru>
 * License: beerware
 * Use for good
 */

use Iassasin\Easyroute\Router;
use Iassasin\Easyroute\Route;
use Iassasin\Easyroute\Http\Request;
use Iassasin\Easyroute\ServiceNotFoundException;
use Iassasin\Easyroute\Http\Response;
use Iassasin\Easyroute\Http\Responses\Response404;
use Iassasin\Easyroute\Http\Responses\ResponseJson;
use Psr\Container\ContainerInterface;

/**
 * @covers \Iassasin\Easyroute\Router
 * @covers \Iassasin\Easyroute\Route
 * @covers \Iassasin\Easyroute\Http\Request
 * @covers \Iassasin\Easyroute\Http\Parameters
 * @covers \Iassasin\Easyroute\SimpleContainer
 * @covers \Iassasin\Easyroute\Http\Response
 * @covers \Iassasin\Easyroute\Http\Responses\Response404
 * @covers \Iassasin\Easyroute\Http\Responses\ResponseJson
 */
class RouterHandlersTest extends PHPUnit_Framework_TestCase {
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

	private function _initRouter($routes){
		$router = new Router();
		$router->setControllersPath(__DIR__.'/controllers/');
		$router->addRoutes($routes);

		return $router;
	}

	private function get404Message($url){
		return '<html><body><h1>404 Not Found</h1> The requested url "<i>'.$url.'</i>" not found!</body></html>';
	}

	public function testHandlers(){
		$router = $this->_initRouter([
			new Route('/{action}', ['controller' => 'handlers']),
		]);

		// status code handlers

		$router->setStatusHandler(404, function(Response404 $resp){
			echo '404: '.$resp->getUrl();
			return true;
		});

		$router->setStatusHandler(200, function(Response $resp){
			echo '200: '.$resp->getContent();
			return true;
		});

		$this->_test($router, '/', '404: /');
		$this->_test($router, '/handle404', '404: no url here');
		$this->_test($router, '/handle200', '200: handle200');

		// response handlers test (have higher priority than status code handlers)

		$router->setResponseHandler(Response::class, function(Response $resp){
			echo 'all '.$resp->getStatusCode().': '.$resp->getContent();
			return true;
		});

		$router->setResponseHandler(MyResponse200::class, function(MyResponse200 $resp){
			echo 'MyResponse200: '.$resp->getContent();
			return true;
		});

		$this->_test($router, '/', 'all 404: '.$this->get404Message('/'));
		$this->_test($router, '/handle404', 'all 404: no content here');
		$this->_test($router, '/handle200', 'MyResponse200: handle200');

		// chain handlers test

		$router->setResponseHandler(MyResponse200::class, function(MyResponse200 $resp){
			echo 'MyResponse200: '.$resp->getContent()."\n";
		});

		$this->_test($router, '/', 'all 404: '.$this->get404Message('/'));
		$this->_test($router, '/handle404', 'all 404: no content here');
		$this->_test($router, '/handle200', "MyResponse200: handle200\nall 200: handle200");
	}

	public function testJsonHandler(){
		$router = $this->_initRouter([
			new Route('/{action}', ['controller' => 'handlers']),
		]);

		$router->setResponseHandler(ResponseJson::class, function(ResponseJson $resp){
			echo 'ResponseJson: '.$resp->getContent();
			return true;
		});

		$this->_test($router, '/', $this->get404Message('/'));
		$this->_test($router, '/handle404', 'no content here');
		$this->_test($router, '/handle200', 'handle200');
		$this->_test($router, '/handleJson', 'ResponseJson: {"a":"b","c":2}');
	}
}
