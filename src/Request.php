<?php
namespace App;

// From https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/Request.php
class Request {
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	public $method;
	public $attributes;
	public $request;
	public $query;
	public $server;
	public $headers;

	public function __construct($method, array $query = array(), array $request = array(), array $attributes = array(), array $server = array())
	{
		$this->method = $method;
		$this->query = $query;
		$this->request = $request;
		$this->attributes = $attributes;
		$this->server = $server;
	}
	
	public static function createFromGlobals()
	{
		return new static($_SERVER['REQUEST_METHOD'], $_GET, $_POST, pathinfo($_SERVER['REDIRECT_URL']), $_SERVER);
	}
	
	public function __get($attribute) 
	{
		return $this->$attribute;
	}

	public function __set($attribute, $value)
	{
		$this->$attribute = $value;
	}
}	

