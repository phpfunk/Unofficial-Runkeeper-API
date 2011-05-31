<?php
class HTTP {

	public $cookie_file = 'cookies/cookie.txt';
	public $errors = array();
	public $follow = TRUE;
	public $force_fresh = TRUE;
	public $max_redirects = 4;
	public $method = "POST";
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
	
	protected $called = FALSE;
	protected $dom = array();
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
    $this->url = (! empty($url)) ? $url : $this->url;
    
		if (empty($this->url)) {
			$this->set_error("No URL to open.");
			exit();
		}
		
		$this->errors = array();
		
		$method = trim(strtoupper($this->method));
		$options = array();
		
		$ch = curl_init();
		$options[CURLOPT_URL] = $this->url;
		$options[CURLOPT_COOKIEFILE] = $this->cookie_file;
		$options[CURLOPT_COOKIEJAR] = $this->cookie_file;
		$options[CURLOPT_FRESH_CONNECT] = $this->force_fresh;
		$options[CURLOPT_FOLLOWLOCATION] = $this->follow;
		$options[CURLOPT_HEADER] = $this->return_header;
		$options[CURLOPT_MAXREDIRS] = $this->max_redirects;
		$options[CURLOPT_NOBODY] = ($this->return_body === TRUE) ? FALSE : TRUE;
		$options[CURLOPT_REFERER] = (empty($this->referer)) ? $this->url : $this->referer;
		$options[CURLOPT_RETURNTRANSFER] = $this->return_transfer;
		$options[CURLOPT_SSL_VERIFYPEER] = $this->ssl_certficate;
		$options[CURLOPT_TIMEOUT] = $this->timeout;
		$options[CURLOPT_USERAGENT] = $this->webbot;
		
		if ($method == 'GET') {
		  $query = $this->format_query();
		  $query = (! empty($query)) ? "?" . $query : $query;
		  $options[CURLOPT_URL] = $this->url . $query;
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
		$this->reset_dom();
		$this->called = TRUE;
		
		if ($return === TRUE) { return $this->page; }
	}
	
	protected function called()
	{
		return ($this->called === TRUE) ? TRUE : FALSE;
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
	
	public function get($i=NULL, $connect=FALSE)
	{
		(!$this->called() || $connect === TRUE) ? $this->connect() : NULL;
		$arr = array(
			"connect_time"		=>		"connect_time",
			"content_type"		=>		"content_type",
			"curl_version"		=>		"curl_version",
			"dom"				=>		"dom",
			"download_length"	=>		"download_content_length",
			"download_size"		=>		"size_download",
			"download_speed"	=>		"speed_download",
			"errors"			=>		"errors",
			"header_out"		=>		"header_out",
			"header_size"		=>		"header_size",
			"http_code"			=>		"http_code",
			"last_modified"		=>		"filetime",
			"load_time"			=>		"total_time",
			"markup_size"		=>		"size_download",
			"namelookup"		=>		"namelookup_time",
			"pretransfer_time"	=>		"pretransfer_time",
			"redirect_time"		=>		"redirect_time",
			"request_size"		=>		"request_size",
			"ssl_verify"		=>		"ssl_verify_result",
			"status"			=>		"status",
			"total_redirects"	=>		"redirect_count",
			"transfer_time"		=>		"starttransfer_time",
			"upload_length"		=>		"upload_content_length",
			"upload_size"		=>		"size_upload",
			"upload_speed"		=>		"speed_upload",
			"url"				=>		"url",
			"weight"			=>		"weight"
		);
		
		if (empty($i)) {
			$i = $arr;
		}
		else {
			$tmp = array();
			foreach (explode(",", $i) as $k => $v) {
				$v = trim($v);
				if (array_key_exists($v, $arr)) {
					$tmp[$v] = $arr[$v];
				}
			}
			$i = $tmp;
		}
		
		$tmp = array();
		foreach ($i as $property => $value) {
			$property = strtolower($property);
			$method = (array_key_exists($property, $arr)) ? "get_" . $property : NULL;
			if ($method == "get_dom") {
				$v = (@count($this->dom) > 0) ? $this->dom : $this->$method();
			}
			else {
				$v = (!empty($method)) ? (method_exists($this, $method)) ? $this->$method() : NULL : NULL;
			}
			$tmp[$property] = (empty($v)) ? (array_key_exists($property, $arr)) ? $this->page_info[$arr[$property]] : $this->page_info[$property] : $v;
		}
		return (@count($tmp) == 1) ? $tmp[$property] : $tmp;
	}
	
	protected function get_curl_version()
	{
		$v = curl_version();
		return $v['version'];
	}
	
	protected function get_dom($txt=NULL)
	{

		/*$regex = "/<((\w+)(\s?[^>]*))\/?>((.*?)<\/2>)?/s";*/
		$regex = "~<((div)([^>]*))/?>((.*?)</div>)?~is";
		$attr_regex = '/(\w+)=(["|\'])(.*?)(\\2)/s';
		$txt = (empty($txt)) ? $this->page : $txt;
		$kill_tags = array("br","center","hr");

		preg_match_all($regex, $txt, $match);
		//print 'MATCH';
		//print_r($match);

		foreach ($match[0] as $k => $v) {
			$key = @count($this->dom[$match[2][$k]]);
			$tag = strtolower(trim($match[2][$k]));
			$attributes = trim($match[3][$k]);
			$innerHTML = trim($match[5][$k]);
			
			if (! in_array($tag, $kill_tags)) {
				preg_match_all($attr_regex, $attributes, $attr_match);
				foreach ($attr_match[0] as $k => $v) {
					$attr = trim($attr_match[1][$k]);
					$val = trim($attr_match[3][$k]);
					$this->dom[$tag][$key][$attr] = $val;
				}

				if (preg_match($regex,$innerHTML)) {
					$this->get_dom($innerHTML);
				}
				else {
					$this->dom[$tag][$key]['innerHTML'] = $innerHTML;
				}
			}
		}
		
		ksort($this->dom);
		return $this->dom;
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

	/**
	NOT READY FOR PRODUCTION USE
	**/
	protected function get_weight()
	{
		ini_set("max_execution_time", 90);
		$pw = $this->get("markup_size");
		$dom = $this->get("dom");
		$totals = array();
		$img_list = array();

		$totals['img'] = @count($dom['img']);
		$totals['script'] = @count($dom['script']);
		$totals['link'] = @count($dom['link']);
		$totals['frame'] = @count($dom['frame']);
		$totals['iframe'] = @count($dom['iframe']);
		
		foreach ($totals as $k => $v) {
			for ($i = 0; $i < $totals[$k]; $i++) {
				$src = ($k == "link" && $dom[$k][$i]['rel'] == "stylesheet") ? $dom[$k][$i]['href'] : $dom[$k][$i]['src'];
				if (!empty($src)) {
					$src = (!preg_match("/((ht|f)tp(s?):\/\/)/i", $src)) ? $this->url . $src : $src;
					$contents = file_get_contents($src);
					$pw += strlen($contents);
					
					if ($k == "link" && $dom[$k][$i]['rel'] == "stylesheet") {
						$regex = '/([\.|#]\w+)\s*{.*?background(-image)*:.*?url\((.*?)\).*?}/is';
						preg_match_all($regex, $contents, $match);
						foreach ($match[0] as $kk => $vv) {
							$class_id = explode(" ", preg_replace("/(\.|#|,)/", " ", $match[1][$kk]));
							foreach ($class_id as $name) {
								$name = trim($name);
								if (!empty($name)) {
									if (preg_match('/(id=[\'|"].*?' . $name . '.*?[\'|"])*(class=[\'|"].*?' . $name . '.*?[\'|"])/is', $this->page)) {
										//array_push($img_list, str_replace("../..", "", $match[3][$kk]));
										$pw += strlen(file_get_contents($this->url . str_replace("../..", "", $match[3][$kk])));
									}
								}
							}
							//print $class_id . "<BR>";
							//$src = $match[3][$kk];
							//print $match[1][$kk] . " | " . $match[2][$kk] . " | " . $match[3][$kk] . " | " . $match[4][$kk] ."<br>";
						}
					}
				}
			}
		}
		return $pw;
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
	
	public function reset_dom()
	{
		$this->dom = array();
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