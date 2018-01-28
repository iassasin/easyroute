<?php

use Iassasin\Easyroute\Http\Response;
use Iassasin\Easyroute\Http\Responses\Response404;

class ControllerHandlers {
	public function handle404(){
		$resp = new Response404('no url here');
		$resp->setContent('no content here');
		return $resp;
	}

	public function handle200(){
		return new MyResponse200('handle200');
	}
}

class MyResponse200 extends Response {

}
