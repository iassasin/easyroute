<?php

class ControllerHome {
	public function index($arg){
		echo '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set')
			.'<br><img src="/assets/paper.png"></body></html>';
	}
}
