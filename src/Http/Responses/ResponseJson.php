<?php

namespace Iassasin\Easyroute\Http\Responses;

use Iassasin\Easyroute\Http\Response;

class ResponseJson extends Response {
	/**
	 * @param array|object $data
	 * @param int $statusCode
	 * @param array $headers Assoc array of headers
	 */
	public function __construct($data, $statusCode = 200, array $headers = []){
		$this->statusCode = $statusCode;
		$this->content = $data;
		$this->headers = array_merge(['Content-Type' => 'application/json'], $headers);
	}

	/**
	 * Get object or array which will be sent
	 * @return array|object Object or array which will be sent
	 */
	public function getData(){
		return $this->content;
	}

	/**
	 * Set object or array which will be sent
	 * @param array|object $data Object or array which will be sent
	 */
	public function setData($data){
		$this->content = $data;
	}

	/**
	 * @inheritdoc
	 */
	public function getContent(){
		return json_encode($this->content);
	}

	/**
	 * @inheritdoc
	 */
	public function setContent($content){
		throw new \LogicException('This method can\'t be used in json response. Use setData() instead.');
	}
}
