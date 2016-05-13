<?php
namespace App;

class Response
{
	const HTTP_OK = 200;
	const HTTP_CREATED = 201;
	const HTTP_NOT_FOUND = 404;
	const HTTP_INTERNAL_SERVER_ERROR = 500;
	const HTTP_NOT_IMPLEMENTED = 501;

	public $headers;
	protected $statusCode;
	protected $statusText;
	protected $content;
	protected $version;

	public static $statusTexts = array(
		200 => 'OK',
		201 => 'Created',
		404 => 'Not Found',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
	);

	public function __construct($content = '', $status = self::HTTP_OK, $headers = array('Content-Type' => 'application/json' ))
	{
        	$this->setContent($content);
        	$this->setStatusCode($status);
        	$this->setProtocolVersion('1.1');
		$this->headers = array('HTTP' => sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));
		foreach($headers as $name => $value) {
			$this->headers[] = $name . ":" .$value;
		}
	}

	public static function create($content = '', $status = 200, $headers = array())
	{
        	return new static($content, $status, $headers);
	}

	public function __toString()
	{
        	return
            		sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText)."\r\n" . 
            		$this->headers."\r\n" . 
            		$this->getContent();
	}

	public function send()
	{
		// headers have already been sent by the developer
        	if (headers_sent()) {
            		return $this;
        	}

		foreach($this->headers as $header) {
			header($header);	
		}
		
		echo $this->content;
		return $this;
	}

	public function setProtocolVersion($version)
    	{
        	$this->version = $version;
        	return $this;
    	}

	/**
	 * Gets the HTTP protocol version.
	 *
	 * @return string The HTTP protocol version
	 */
	public function getProtocolVersion()
	{
	    return $this->version;
	}

	 /**
     * Sets the response status code.
     *
     * @param int   $code HTTP status code
     * @param mixed $text HTTP status text
     *
     * If the status text is null it will be automatically populated for the known
     * status codes and left empty otherwise.
     *
     * @return Response
     *
     * @throws \Exception When the HTTP status code is not valid
     */
    public function setStatusCode($code, $text = null)
    {
        $this->statusCode = $code = (int) $code;
        if ($this->isInvalid()) {
            throw new \Exception(sprintf('The HTTP status code "%s" is not valid.', $code));
        }
        if (null === $text) {
            $this->statusText = isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : 'unknown status';
            return $this;
        }
        if (false === $text) {
            $this->statusText = '';
            return $this;
        }
        $this->statusText = $text;
        return $this;
    }	

    /**
     * Retrieves the status code for the current web response.
     *
     * @return int Status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the response content.
     *
     * Valid types are strings, numbers, null, and objects that implement a __toString() method.
     *
     * @param mixed $content Content that can be cast to string
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function setContent($content)
    {
        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
            throw new \Exception(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
        }
        $this->content = (string) $content;
        return $this;
    }

    /**
     * Gets the current response content.
     *
     * @return string Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Is response invalid?
     *
     * @return bool
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }

    /**
     * Is response informative?
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Is the response empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->statusCode, array(204, 304));
    }
}
