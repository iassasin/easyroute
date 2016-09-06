<?php

class home {
	public function index($arg){
		echo '<html><body>Home page! '.($arg !== null ? 'Argument: '.$arg : 'Argument not set').'</body></html>';
	}
}
