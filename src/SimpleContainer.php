<?php

namespace Iassasin\Easyroute;

use \Psr\Container\ContainerInterface;

class SimpleContainer implements ContainerInterface {
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
			$obj = static::createInstance($this, $id);
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

	/**
	 * Create instance of class and resolve all dependencies for it
	 * @param string $class class name
	 * @return mixed new instance of class
	 */
	public static function createInstance(ContainerInterface $container, $class){
		$class = new \ReflectionClass($class);
		$ctor = $class->getConstructor();

		if ($ctor === null){
			return $class->newInstance();
		}

		$params = $ctor->getParameters();
		$args = array_fill(0, count($params), null);
		foreach ($params as $param){
			$pclass = $param->getClass();
			if ($pclass === null){
				throw new ServiceNotFoundException("Service ${class} requires unknown dependency \$".$param->name);
			}

			$args[$param->getPosition()] = $container->get($pclass->name);
		}

		return $class->newInstanceArgs($args);
	}
}
