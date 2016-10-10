<?php 

namespace OMC;

class Curl { 

	public $useragent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36';  // Pretent to be Chrome
	public $url; 
	public $follow_location; 
	public $timeout; 
	public $max_redirects; 
	public $cookie_file_location; 
	public $post; 
	public $post_fields; 
	public $referer; 
	public $response; 
	public $header; 
	public $include_header; 
	public $no_body; 
	public $status; 
	public $binary_transfer; 
	public $authentication; 
	public $auth_name; 
	public $auth_pass;
	public $custom_request;
	protected $_curl;

	public function __construct(){ 
		
		// Defaults
		$this->useragent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36';  // Pretent to be Chrome
		$this->url = 'https://www.google.com';
		$this->follow_location = true; 
		$this->timeout = 30; 
		$this->max_redirects = 5; 
		$this->cookie_file_location = dirname(__FILE__).'./cookies/'.
																	( isset( $_COOKIE['unique_user_id'] ) ? $_COOKIE['unique_user_id'] : 
																		( isset( $_COOKIE['PHPSESSID'] ) ? $_COOKIE['PHPSESSID'] : 'all' ) ).
																	'.txt'; 
		$this->post = false; 
		$this->post_fields = ''; 
		$this->referer ="https://www.google.com"; 
		$this->response = ''; 
		$this->header = array(); 
		$this->include_header = false;
		$this->no_body = false; 
		$this->status = ''; 
		$this->binary_transfer = false; 
		$this->authentication = 0; 
		$this->auth_name = ''; 
		$this->auth_pass = ''; 
	}

	public function create(){ 
			
		if( !empty( $this->_curl ) )
			return $this;
			
		$this->_curl = curl_init();
		curl_setopt( $this->_curl, CURLOPT_RETURNTRANSFER, true ); 
		curl_setopt( $this->_curl, CURLOPT_FOLLOWLOCATION, $this->follow_location ); 
		curl_setopt( $this->_curl, CURLOPT_COOKIEJAR, $this->cookie_file_location ); 
		curl_setopt( $this->_curl, CURLOPT_COOKIEFILE, $this->cookie_file_location );
		curl_setopt( $this->_curl,CURLOPT_USERAGENT,$this->useragent ); 
		
		return $this;
	}
	
	public function execute(){
		
		// Header
		$header_output = array();
		if( !empty( $this->header ) && is_array( $this->header ) ){
			foreach( $this->header as $key => $value )
				$header_output[] = $key.':'.$value;
		}
		
		// Set options
		curl_setopt( $this->_curl, CURLOPT_URL, $this->url ); 
		curl_setopt( $this->_curl, CURLOPT_HTTPHEADER, $header_output ); 
		curl_setopt( $this->_curl, CURLOPT_TIMEOUT, $this->timeout ); 
		curl_setopt( $this->_curl, CURLOPT_MAXREDIRS, $this->max_redirects ); 
		curl_setopt( $this->_curl, CURLOPT_HEADER, $this->include_header ); 
		curl_setopt( $this->_curl, CURLOPT_NOBODY, $this->no_body ); 
		curl_setopt( $this->_curl, CURLOPT_BINARYTRANSFER, $this->binary_transfer );
		curl_setopt( $this->_curl, CURLOPT_REFERER,$this->referer );
		curl_setopt( $this->_curl, CURLINFO_HEADER_OUT, true );	// TODO
		curl_setopt( $this->_curl, CURLOPT_SSL_VERIFYPEER, false );	// TODO
		
		if( !empty( $this->auth_name ) ){
			curl_setopt( $this->_curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt( $this->_curl, CURLOPT_USERPWD, $this->auth_name.':'.$this->auth_pass); 
		}
		
		if( !empty( $this->post_fields ) ){ 
			curl_setopt( $this->_curl, CURLOPT_POST, true ); 
			curl_setopt( $this->_curl, CURLOPT_POSTFIELDS, $this->build_query( $this->post_fields ) ); 
		} else {
			curl_setopt( $this->_curl, CURLOPT_POST, false );				
		}
		
		if( !empty( $this->custom_request ) )
			curl_setopt( $this->_curl, CURLOPT_CUSTOMREQUEST, $this->custom_request );
		else
			curl_setopt( $this->_curl, CURLOPT_CUSTOMREQUEST, null );
		
		$this->response = curl_exec( $this->_curl ); 
		$this->status = curl_getinfo( $this->_curl ); 
		$this->error = curl_error( $this->_curl );
		
		if( $this->error )
			throw new \Exception( $this->error );
		
		// Reset
		$this->post_fields = array();
		$this->custom_request = null;
		
		return $this;
	}
	
	public function close(){
		curl_close( $this->_curl ); 
		return $this;
	}
	
	public function build_query( $data ) {
		return $this->_http_build_query( $data, null, '&', '', false );
	}
	
	private function _http_build_query( $data, $prefix = null, $sep = null, $key = '', $urlencode = true ) {
		$ret = array();
 
		foreach ( (array) $data as $k => $v ) {
			if ( $urlencode)
					$k = urlencode($k);
			if ( is_int($k) && $prefix != null )
					$k = $prefix.$k;
			if ( !empty($key) )
					$k = $key . '%5B' . $k . '%5D';
			if ( $v === null )
					continue;
			elseif ( $v === false )
					$v = '0';

			if ( is_array($v) || is_object($v) )
					array_push($ret, $this->_http_build_query($v, '', $sep, $k, $urlencode));
			elseif ( $urlencode )
					array_push($ret, $k.'='.urlencode($v));
			else
					array_push($ret, $k.'='.$v);
		}
 
		if ( null === $sep )
			$sep = ini_get('arg_separator.output');
 
		return implode($sep, $ret);
	}
} 
?>