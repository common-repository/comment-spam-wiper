<?php

define('API_VERSION', '1.0');
define('API_URL', 'http://api.spamwipe.com/'.API_VERSION.'/comments/');
define('API_KEY', 'HTTP_X_API_KEY');

/**
 * PHP SDK interface for CSW REST API
 *
 */
class CSW_SDK
{
	const USER_AGENT = 'CSW SDK';
	private $with_curl;
	private $username;
	private $password;
	private $status;
	private $api_key;
	private $format = 'raw';
	private $params = array();

    /**
     * Constructor of the CSW_SDK
     */
	public function __construct($username = NULL, $password = NULL)
	{
		if(function_exists("curl_init"))
		{
			$this->with_curl = TRUE;
		}
		else
		{
			$this->with_curl = FALSE;
		}
		$this->username = $username;
		$this->password = $password;
		$this->params['format'] = $this->format;
	}

    /**
     * Call the HTTP 'POST' method
     * @param string $url URL of the service..
     * @param string $data request data
     * @param array $content_type the http content type
     * @return response string
     */
	public function post($url, $data, $content_type = "application/atom+xml;type=entr")
	{		
		$result = "";
		
		if($this->with_curl)
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
      		curl_setopt($curl, CURLOPT_POST, TRUE);
			if (version_compare(PHP_VERSION, '5.3') < 0) {
				$string_data = '';
				foreach ($data as $var => $value) {
					$string_data .= "$var=$value&";
				}
				$string_data = substr($string_data, 0, -1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $string_data);
				if ($content_type == 'multipart/form-data')
 					curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded"));
			} else {
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: ".$content_type));
			}
      		
      		if($this->username !== NULL)
      		{
				curl_setopt($curl, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
      		}
      		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
      		curl_setopt($curl, CURLOPT_USERAGENT, CSW_SDK::USER_AGENT);
      		$result = curl_exec($curl);
      		$this->status = curl_getinfo($curl,CURLINFO_HTTP_CODE);
      		curl_close($curl);
		}
		else
		{
			$username_str = "{$this->username}:{$this->password}";
			$auth_string = "Basic " + base64_encode($username_str);
			
			$parsed = parse_url($url);
			$path = $parsed['path'];
			$host = $parsed['host'];
			
			$data['comment'] = rawurlencode($data['comment']);
			foreach ($data as $var => $value) {
				//$string_data .= "$var=".urlencode($value)."&";
				$string_data .= "$var=$value&";
			}
			$string_data = substr($string_data, 0, -1);
			$content_length = strlen($string_data);
			
			$http_request  = "POST $path HTTP/1.0\r\n";
			$http_request .= "Host: $host\r\n";
			$http_request .= "User-Agent: ".CSW_SDK::USER_AGENT."\r\n";
			$http_request .= "Authorization: ".$auth_string."\r\n";
			$http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$http_request .= "Content-Length: {$content_length}\r\n";
			$http_request .= "\r\n";
			$http_request .= $string_data;
			
			$response = '';
			if( false != ( $fs = @fsockopen( $host, 80, $errno, $errstr, 10 ) ) ) {
				fwrite( $fs, $http_request );
	
				while ( !feof( $fs ) )
					$result .= fgets( $fs, 1160 ); // One TCP-IP packet
				fclose( $fs );
				$fullresult = explode( "\r\n\r\n", $result, 2 );
				$result=$fullresult[1];
			}
		}
		return $result;
	}
	
	public function getLastResponseStatus()
	{
		return $this->status;
	}
	
	/**
	 * Set API Key
	 * @param string $key API Key
	 * @return empty 
	 */
	public function api_key($key = '') {
		$this->api_key = $key;
	}
	
	/**
	 * Add API Key and default parameters to API call parameters
	 * @param array $params API call parameters
	 * @return array
	 */
	private function add_params($params = array()) {
		$params[API_KEY] = $this->api_key;
		foreach (array_keys($this->params) as $key) {
			if (empty($params[$key]))
				$params[$key] = $this->params[$key];
		}
		return $params;
	}
	
	/**
	 * API CALL: Verify API Key
	 * @param string $key API Key
	 * @param string $site Site URL
	 * @return string
	 */
	public function verify_key($key = '', $site = '') {
		$url = API_URL.'verify-key/';
		return $this->post($url, array('key' => $key, 'site' => $site, 'format' => 'raw'), 'multipart/form-data');
	}
	
	/**
	 * API CALL: Mark comment as SPAM
	 * @param array $params
	 * @return string
	 */
	public function markas_spam($params = NULL) {
		$url = API_URL.'markas-spam/';
		return $this->post($url, $this->add_params($params), 'multipart/form-data');
	}
	
	/**
	 * API CALL: Mark comment as HAM
	 * @param array $params
	 * @return string
	 */
	public function markas_ham($params = NULL) {
		$url = API_URL.'markas-ham/';
		return $this->post($url, $this->add_params($params), 'multipart/form-data');
	}
	
	/**
	 * API CALL: Auto Classify the comment as SPAM or HAM
	 * @param params $params
	 * @return string
	 */
	public function classify($params = NULL) {
		$url = API_URL.'classify/';
		return $this->post($url, $this->add_params($params), 'multipart/form-data');
	}
	
	/**
	 * Set default parameters for future calls
	 * @param array $params
	 * @return array
	 */
	public function set_params($params = NULL) {
		$ret = array();
		$ret['site_ip'] = gethostbyname($_SERVER['HTTP_HOST']);
		$ret['client_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$ret['site_lang'] = 'en';
		
		$ret['ip'] = $_SERVER['REMOTE_ADDR'];
		$ret['client_referer'] = $_SERVER['HTTP_REFERER'];
		$ret['client_ua'] = $_SERVER['HTTP_USER_AGENT'];
		$ret['client_proxy'] = ($this->proxy_detect())?'y':'n';
		$ret['client_lang'] = $this->get_client_language();
		
		$ret['type'] = 'comment';
		
		if (!is_null($params)) {
			foreach (array_keys($params) as $key) {
				$ret[$key] = $params[$key];
			}
		}
		$this->params = $ret;
	}
	
	/**
	 * Get the client language
	 * @param string $default
	 * @return string
	 */
	public function get_client_language($default='en') {
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$langs=explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			//start going through each one
			foreach ($langs as $value){
				$choice=substr($value,0,2);
				return $choice;
			}
		} 
		return $default;
	}

	
	/**
	 * Detect if the user is using a proxy
	 * @return bool
	 */
	public function proxy_detect() {
		$proxy_headers = array(
		     'HTTP_VIA',   
		     'HTTP_X_FORWARDED_FOR',   
		     'HTTP_FORWARDED_FOR',   
		     'HTTP_X_FORWARDED',   
		     'HTTP_FORWARDED',   
		     'HTTP_CLIENT_IP',   
		     'HTTP_FORWARDED_FOR_IP',   
		     'VIA',   
		     'X_FORWARDED_FOR',   
		     'FORWARDED_FOR',   
		     'X_FORWARDED',   
		     'FORWARDED',   
		     'CLIENT_IP',   
		     'FORWARDED_FOR_IP',   
		     'HTTP_PROXY_CONNECTION',
		     'HTTP_X_COMING_FROM',
		     'HTTP_COMING_FROM',
		     'HTTP_X_CLUSTER_CLIENT_IP',
		);
		$is_proxy = false;
		foreach ($proxy_headers AS $x) {
			if (isset($_SERVER[$x]))
				$is_proxy = true;
		}
		return $is_proxy;
	}
}

?>