<?php

namespace Iassasin\Easyroute;

class SimpleContainer implements \Psr\Container\ContainerInterface {
	private $services;

	public function __construct(){
		$this->services = [];
	}

	/**
	 * @inheritdoc
	 */
	public function get($id){
		if ($this->has($id)){
			return $this->services[mb_strtolower($id)];
		}

		throw new ServiceNotFoundException("Service ${id} not found");
	}

	/**
	 * @inheritdoc
	 */
	public function has($id){
		return array_key_exists(mb_strtolower($id), $this->services);
	}

	public function setService($id, $service){
		$this->services[mb_strtolower($id)] = $service;
	}
}
