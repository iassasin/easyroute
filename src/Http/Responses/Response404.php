<?php

namespace Iassasin\Easyroute\Http\Responses;

use Iassasin\Easyroute\Http\Response;

class Response404 extends Response {
	protected $url;

	/**
	 * @param string $url Url being processed
	 * @param array $headers Assoc array of headers
	 */
	public function __construct($url, array $headers = []){
		$this->statusCode = 404;
		$this->headers = $headers;
		$this->url = $url;

		$this->content = '<html><body><h1>404 Not Found</h1>'
			.' The requested url "<i>'.htmlspecialchars($url).'</i>" not found!</body></html>';
	}

	/**
	 * Get url being processed
	 * @return string
	 */
	public function getUrl(){
		return $this->url;
	}
}
