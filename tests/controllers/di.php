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

	public function req6($arg, Request $req, CantInstantiateService $class){
		echo 'di/req6';
	}

	public function req7($arg, AutowireService2 $svc2, AutowireService1 $svc1){
		echo 'di/req7: '.$arg.', '.$svc2->get().', '.$svc1->get();
	}
}

class CantInstantiateService {
	public function __construct($arg){

	}
}

class AutowireService1 {
	public function get(){ return 's1'; }
}

class AutowireService2 {
	private $service1;

	public function __construct(AutowireService1 $service1){
		$this->service1 = $service1;
	}

	public function get(){ return 's2'.$this->service1->get(); }
}
