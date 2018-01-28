<?php

class ControllerHome {
	public function index($arg){
		return '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set')
			.'<br><img src="/assets/paper.png"></body></html>';
	}

	public function staticRoute($arg){
		return new Response('This is static route with arg: '.$arg, 200);
	}
}
