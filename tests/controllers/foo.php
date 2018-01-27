<?php

class ControllerFoo {
	public function index($arg){
		echo 'foo/index: '.$arg;
	}

	public function test(){
		return 'foo/test';
	}
}
