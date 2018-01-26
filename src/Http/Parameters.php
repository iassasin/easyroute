<?php

namespace Iassasin\Easyroute\Http;

class Parameters {
	protected $values;

	public function __construct(array $vals = []){
		$this->values = $vals;
	}

	public function get($key){
		return $this->values[$key];
	}

	public function has($key){
		return array_key_exists($key, $this->values);
	}

	public function all(){
		return $this->values;
	}
}
