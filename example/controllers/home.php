<?php

use Iassasin\Easyroute\Http\Response;
use Iassasin\Easyroute\Http\Request;

class ControllerHome {
	public function index($arg){
		return '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set')
			.'<br><img src="/assets/paper.png"></body></html>';
	}

	public function staticRoute($arg, Request $request){
		return new Response('This is static route "'.$request->getUri().'" with arg: '.$arg, 200);
	}
}
