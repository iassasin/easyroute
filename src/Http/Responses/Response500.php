<?php

namespace Iassasin\Easyroute\Http\Responses;

use Iassasin\Easyroute\Http\Response;

class Response500 extends Response {
	protected $exception;

	/**
	 * @param \Throwable $exception Exception was thrown during route process
	 * @param array $headers Assoc array of headers
	 */
	public function __construct(\Throwable $exception = null, array $headers = []){
		$this->statusCode = 500;
		$this->headers = $headers;
		$this->exception = $exception;

		$msg = $exception ? $exception->getMessage() : '';

		$this->content = '<html><body><h1>500 Internal Server Error</h1> '.$msg.'</body></html>';
	}

	/**
	 * Get exception was thrown during route process
	 * @return \Throwable
	 */
	public function getException(){
		return $this->exception;
	}
}
