<?php
class HTTP {

	public $cookie_file = 'cookies/cookie.txt';
	public $errors = array();
	public $follow = TRUE;
	public $force_fresh = TRUE;
	public $max_redirects = 4;
	public $method = 'POST';
	public $options = array();
	public $page = NULL;
	public $query = array();
	public $referer = NULL;
	public $return_body = TRUE;
	public $return_header = FALSE;
	public $return_transfer = TRUE;
	public $ssl_certficate = FALSE;
	public $timeout = 25;
	public $url = NULL;
	public $webbot = NULL;

	protected $page_info = array();
	
	public function __construct()
	{
		if (extension_loaded("curl") === FALSE) {
			$this->set_error("The cURL extension was not found on your server. Learn how to install here: http://www.php.net/curl/");
			exit();
		}
	}
	
	public function connect($url=NULL, $method='GET', $return=TRUE)
	{
		if (empty($url)) {
			$this->set_error("No URL to open.");
			exit();
		}
		
		$this->errors = array();
		
		$method = trim(strtoupper($method));
		$options = array();
		
		$ch = curl_init();
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_COOKIEFILE] = $this->cookie_file;
		$options[CURLOPT_COOKIEJAR] = $this->cookie_file;
		$options[CURLOPT_FRESH_CONNECT] = $this->force_fresh;
		$options[CURLOPT_FOLLOWLOCATION] = $this->follow;
		$options[CURLOPT_HEADER] = $this->return_header;
		$options[CURLOPT_MAXREDIRS] = $this->max_redirects;
		$options[CURLOPT_NOBODY] = ($this->return_body === TRUE) ? FALSE : TRUE;
		$options[CURLOPT_REFERER] = (empty($this->referer)) ? $url : $this->referer;
		$options[CURLOPT_RETURNTRANSFER] = $this->return_transfer;
		$options[CURLOPT_SSL_VERIFYPEER] = $this->ssl_certficate;
		$options[CURLOPT_TIMEOUT] = $this->timeout;
		$options[CURLOPT_USERAGENT] = $this->webbot;
		
		if ($method == 'GET') {
			$query = $this->format_query();
			$query = (! empty($query)) ? "?" . $query : $query;
			$options[CURLOPT_URL] = $url . $query;
			$options[CURLOPT_HTTPGET] = TRUE;
			$options[CURLOPT_POST] = FALSE;
		}
		elseif ($method == 'POST') {
			$query = $this->format_query();
			if (! empty($query)) { $options[CURLOPT_POSTFIELDS] = $query; }
			$options[CURLOPT_HTTPGET] = FALSE;
			$options[CURLOPT_POST] = TRUE;
		}
		else {
			$options[CURLOPT_CUSTOMREQUEST] = strtoupper($this->method);
			if (@count($this->query) >= 1) {
					$options[CURLOPT_POSTFIELDS] = $this->query;
			}
		}
		
		foreach ($this->options as $property => $value) {
			$options[strtoupper($property)] = $value;
		}
		
		foreach ($options as $property => $value) {
			@curl_setopt($ch, $property, $value);
		}

		$this->page = curl_exec($ch);
		$this->page_info = curl_getinfo($ch);
		$e = curl_error($ch);
		(! empty($e)) ? $this->set_error($e) : NULL;
		curl_close($ch);
		
		if ($return === TRUE) { return $this->page; }
	}
	
	public function errors()
	{
		return ($this->total_errors() >= 1) ? TRUE : FALSE;
	}
	
	protected function format_query()
	{
		$tmp = NULL;
		if (count($this->query) > 0) {
			foreach ($this->query as $field => $value) {
				$tmp .= $field . "=" . urlencode($value) . "&";
			}
			return substr($tmp, 0, strlen($tmp)-1);
		}
		return "";
	}
	
	protected function get_errors()
	{
		return @count($this->http_errors);
	}

	
	protected function get_status()
	{
		$status = array(
			100 => "100 Continue",
			101 => "101 Switching Protocols",
			200 => "200 OK",
			201 => "201 Created",
			202 => "202 Accepted",
			203 => "203 Non-Authoritative Information",
			204 => "204 No Content",
			205 => "205 Reset Content",
			206 => "206 Partial Content",
			300 => "300 Multiple Choices",
			301 => "301 Moved Permanently",
			302 => "302 Found",
			303 => "303 See Other",
			304 => "304 Not Modified",
			305 => "305 Use Proxy",
			306 => "306 (Unused)",
			307 => "307 Temporary Redirect",
			400 => "400 Bad Request",
			401 => "401 Unauthorized",
			402 => "402 Payment Required",
			403 => "403 Forbidden",
			404 => "404 Not Found",
			405 => "405 Method Not Allowed",
			406 => "406 Not Acceptable",
			407 => "407 Proxy Authentication Required",
			408 => "408 Request Timeout",
			409 => "409 Conflict",
			410 => "410 Gone",
			411 => "411 Length Required",
			412 => "412 Precondition Failed",
			413 => "413 Request Entity Too Large",
			414 => "414 Request-URI Too Long",
			415 => "415 Unsupported Media Type",
			416 => "416 Requested Range Not Satisfiable",
			417 => "417 Expectation Failed",
			500 => "500 Internal Server Error",
			501 => "501 Not Implemented",
			502 => "502 Bad Gateway",
			503 => "503 Service Unavailable",
			504 => "504 Gateway Timeout",
			505 => "505 HTTP Version Not Supported"
		);

		return $status[$this->page_info['http_code']];
	}
	
	public function print_errors($d=NULL)
	{
		if (empty($d)) {
			print "<pre>";
			print_r($this->errors);
			print "</pre>";
		}
		else {
			print implode($d, $this->errors);
		}
	}
	
	protected function set_error($msg)
	{
		array_push($this->errors, $msg);
	}
	
	public function total_errors()
	{
		return count($this->errors);
	}

}
?>