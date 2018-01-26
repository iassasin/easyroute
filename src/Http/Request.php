<?php

namespace Iassasin\Easyroute\Http;

class Request {
	public $query;
	public $request;
	public $attributes;
	public $cookies;
	public $files;
	public $server;
	protected $content;

	public function __construct(
		array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [],
		array $server = [], $content = null
	){
		$this->query = new Parameters($query);
		$this->request = new Parameters($request);
		$this->attributes = new Parameters($attributes);
		$this->cookies = new Parameters($cookies);
		$this->files = new Parameters($files);
		$this->server = new Parameters($server);
		$this->content= $content;
	}

	/**
	 * @param mixed[] $attributes custom attributes
	 * @return Request
	 */
	public static function createFromGlobals(array $attributes = []){
		return new static(
			$_GET,
			$_POST,
			$attributes,
			$_COOKIE,
			$_FILES,
			$_SERVER,
			file_get_contents('php://input')
		);
	}

	public function getClientIP(){
		return $this->server->get('REMOTE_ADDR');
	}

	public function getScriptName(){
		return $this->server->get('SCRIPT_NAME');
	}

	public function getScheme(){
		return $this->server->get('REQUEST_SCHEME');
	}

	public function getHost(){
		return $this->server->get('SERVER_NAME');
	}

	public function getUri(){
		return $this->server->get('REQUEST_URI');
	}

	public function getMethod(){
		return $this->server->get('REQUEST_METHOD');
	}

	public function getProtocol(){
		return $this->server->get('SERVER_PROTOCOL');
	}

	public function getContent(){
		return $this->content;
	}
}
