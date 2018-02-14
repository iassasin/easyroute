<?php
/**
 * Author: iassasin <iassasin@yandex.ru>
 * License: beerware
 * Use for good
 */

use PHPUnit\Framework\TestCase;
use Iassasin\Easyroute\Path;

/**
 * @covers \Iassasin\Easyroute\Path
 */
class PathTest extends TestCase {
	private function _test(string $url, string $rx, array $varGroups){
		$res = Path::parse($url);
		$this->assertEquals($res->regex, $rx);
		$this->assertEquals($res->varGroups, $varGroups);
	}

	public function testPath(){
		$this->_test('', '/^$/', []);
		$this->_test('/', '/^\/$/', []);
		$this->_test('/static/route/?', '/^\/static\/route\/?$/', []);

		$this->_test(
			'/:controller/(test)/:action/:id',
			'/^\/(?P<controller>[^\/]+)\/(test)\/(?P<action>[^\/]+)\/(?P<id>[^\/]+)$/',
			['controller', 'action', 'id']
		);

		$this->_test('/test-:id(\d+)', '/^\/test-(?P<id>\d+)$/', ['id']);
		$this->_test('/test-:id(\d+)?', '/^\/test-(?P<id>\d+)?$/', ['id']);
		$this->_test('/test-:(\d+)', '/^\/test-:(\d+)$/', []);
		$this->_test('/test-:', '/^\/test-:$/', []);
		$this->_test('/test-:id(\d+)/:dt@4/', '/^\/test-(?P<id>\d+)\/(?P<dt>[^\/]+)@4\/$/', ['id', 'dt']);
		$this->_test('/:abc_123-45', '/^\/(?P<abc_123>[^\/]+)-45$/', ['abc_123']);
		$this->_test(':abc_123-45', '/^(?P<abc_123>[^\/]+)-45$/', ['abc_123']);
		$this->_test('/:a/:b:(/:c)?', '/^\/(?P<a>[^\/]+)\/(?P<b>[^\/]+)(\/(?P<c>[^\/]+))?$/', ['a', 'b', 'c']);
	}
}
