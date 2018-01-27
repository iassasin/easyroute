<?php

namespace Iassasin\Easyroute\Http;

class Response {
	protected $statusCode;
	protected $content;
	protected $headers;

	/**
	 * @param string $content
	 * @param int $statusCode
	 * @param array $headers Assoc array of headers
	 */
	public function __construct($content, $statusCode = 200, array $headers = []){
		$this->statusCode = $statusCode;
		$this->content = $content;
		$this->headers = $headers;
	}

	/**
	 * @return int
	 */
	public function getStatusCode(){
		return $this->statusCode;
	}

	/**
	 * @param int $code
	 */
	public function setStatusCode($code){
		$this->statusCode = $code;
	}

	/**
	 * @return string
	 */
	public function getContent(){
		return $this->content;
	}

	/**
	 * @param string $content
	 */
	public function setContent($content){
		$this->content = $content;
	}

	/**
	 * @return array Assoc array of headers
	 */
	public function getHeaders(){
		return $this->headers;
	}

	/**
	 * @param array $headers Assoc array of headers
	 */
	public function setHeaders(array $headers){
		$this->headers = $headers;
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function setHeader($name, $value){
		$this->headers[$name] = $value;
	}

	/**
	 * Send to client headers and response content
	 */
	public function send(){
		http_response_code($this->getStatusCode());

		foreach ($this->getHeaders() as $name => $val){
			header("$name: $val");
		}

		echo $this->getContent();
	}
}
