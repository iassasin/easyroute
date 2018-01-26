<?php

use Iassasin\Easyroute\Http\Request;

class ControllerDi {
	public function req1(){
		echo 'di/req1';
	}

	public function req2($arg){
		echo 'di/req2: '.$arg;
	}

	public function req3(Request $req){
		echo 'di/req3: uri: '.$req->getUri();
	}

	public function req4(Request $req, $arg){
		echo 'di/req4: '.$arg.', uri: '.$req->getUri();
	}

	public function req5($arg, Request $req){
		echo 'di/req5: '.$arg.', uri: '.$req->getUri();
	}

	public function req6($arg, Request $req, ControllerDi $class){
		echo 'di/req6';
	}
}
