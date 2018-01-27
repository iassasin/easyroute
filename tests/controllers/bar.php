<?php

class ControllerBar {
	public function index($arg){
		echo 'bar/index: '.$arg;
	}

	public function test(){
		return 'bar/test';
	}
}
