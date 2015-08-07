<?php
  namespace Chestnut\Http\Response;

  class Response implements \ArrayAccess, \Countable, \IteratorAggregate
  {
    protected $status;
    protected $content;
    protected $header;
    protected $length;

   /**
     * @var array HTTP response codes and messages
     */
    protected static $messages = array(
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        226 => '226 IM Used',
        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        //Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        426 => '426 Upgrade Required',
        428 => '428 Precondition Required',
        429 => '429 Too Many Requests',
        431 => '431 Request Header Fields Too Large',
        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported',
        506 => '506 Variant Also Negotiates',
        510 => '510 Not Extended',
        511 => '511 Network Authentication Required'
    );

    public function __construct($content = '', $status = 200, $header = []) {
      $this->setStatus($status);

      $this->header = array_merge(['Content-Type'=> 'text/html'], $header);

      $this->setContent($content);
    }

    public function getContent()
    {
      return $this->content;
    }

    public function setContent($content, $replace = false)
    {
      if($replace)
      {
        $this->content = $content;
      }
      else
      {
        $this->content .= (string)$content;
      }

      $this->length = strlen($this->content);

      return $this->content;
    }

    public function getLength()
    {
      return $this->length;
    }

    public function length($length = null)
    {
      if(! is_null($lenght))
      {
        $this->length = (int)$length;
      }

      return $this->length;
    }

    public function getStatus()
    {
      return $this->status;
    }

    public function setStatus($status)
    {
      $this->status = (int)$status;
    }

    public function status($status = null)
    {
      if(! is_null($status))
      {
        $this->status = (int)$status;
      }

      return $this->status;
    }

    public function header($key, $value = null)
    {
      if(! is_null($value))
      {
        $this->header[$key] = $value;
      }

      return $this->header[$key];
    }

    public function headers()
    {
      return $this->header;
    }

    public function finalize()
    {
      if(in_array($this->status, [204, 304]))
      {
        unset($this->header['Content-Type']);
        unset($this->header['Content-Length']);
        $this->setContent('');
      }

      return [$this->status, $this->header, $this->content];
    }

    public function offsetExists($offset)
    {
      return isset($this->header[$offset]);
    }

    public function offsetGet($offset)
    {
      return $this->header[$offset];
    }

    public function offsetSet($offset, $value)
    {
      $this->header[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
      unset($this->header[$offset]);
    }

    public function count()
    {
      return count($this->header);
    }

    public function getIterator()
    {
      return new \ArrayIterator($this->header);
    }

    public static function getMessageForCode($status)
    {
      if(isset(self::$messages[$status]))
      {
        return self::$messages[$status];
      }
      else
      {
        return null;
      }
    }

  }
