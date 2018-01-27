<?php

namespace Iassasin\Easyroute;

class SimpleContainer implements \Psr\Container\ContainerInterface {
	protected $services;
	protected $autowireEnabled;

	public function __construct(){
		$this->services = [];
		$this->autowireEnabled = true;
	}

	/**
	 * @inheritdoc
	 */
	public function get($id){
		if ($this->has($id)){
			return $this->services[$id];
		}

		if ($this->autowireEnabled && class_exists($id)){
			// recursion dependency break
			$this->services[$id] = null;
			$obj = $this->createInstance($id);
			$this->services[$id] = $obj;
			return $obj;
		}

		throw new ServiceNotFoundException("Service ${id} not found");
	}

	/**
	 * @inheritdoc
	 */
	public function has($id){
		return array_key_exists($id, $this->services);
	}

	/**
	 * Does container try to create new service instances by it's name
	 * @param bool $val
	 */
	public function setAutowireEnabled($val){
		$this->autowireEnabled = $val ? true : false;
	}

	/**
	 * Associate service $id with instance $service
	 * @param string $id
	 * @param object $service
	 */
	public function setService($id, $service){
		$this->services[$id] = $service;
	}

	protected function createInstance($id){
		$class = new \ReflectionClass($id);
		$ctor = $class->getConstructor();

		if ($ctor === null){
			return new $id();
		}

		$params = $ctor->getParameters();
		$args = array_fill(0, count($params), null);
		foreach ($params as $param){
			$pclass = $param->getClass();
			if ($pclass === null){
				throw new ServiceNotFoundException("Service ${id} requires unknown dependency \$".$param->name);
			}

			$args[$param->getPosition()] = $this->get($pclass->name);
		}

		return $ctor->invokeArgs(null, $args);
	}
}
