<?php 
namespace OMC\Hubwire;

require_once 'curl.php';

use OMC\Curl as c;

class Curl extends c{	
	
	public $root_url;
	public $client_id;
	public $client_secret;
	public $grant_type;
	
	public $access_token;
	public $token_type;
	public $expires_in;
	
	public function __construct( $options = array() ){
		parent::__construct();
		
		$options += array(
			'root_url' => HUBWIRE_API_URL,
			'grant_type' => 'client_credentials'
		);
		extract( $options );
		
		if( empty( $client_id ) || empty( $client_secret ) )
			throw new \Exception( 'Client ID or Client secret is not provided.' );
		
		// Variables
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->grant_type = $grant_type;
		
		$this->root_url = $root_url;
		$this->oauth_url = $root_url.'/oauth/access_token';
		$this->sales_url = $root_url.'/sales';
		$this->products_url = $root_url.'/products';
		$this->webhooks_url = $root_url.'/webhooks';
	}
	
	public function execute(){
		parent::execute();
		
		// Decode json		
		$response = json_decode( $this->response, true );
		if( !is_array ( $response ) ){	
			error_log(print_r($this->response, true));
			throw new \Exception( 'Response is not json.' );
		}
		
		if( isset( $response['error'] ) ){			
			throw new \Exception( print_r( $response['error'], true ) );
		}
		
		
		$this->response = $response;
		
		return $this;
	}
	
	public function set_authorization_header(){
		$this->header['Authorization'] = $this->token_type.' '.$this->access_token;		
	}
	
	/*
	 * Authenticate
	 */
	public function oauth(){
		if( !empty( $this->access_token ) )
			return $this;
		
		$this->url = $this->oauth_url;
		$this->post_fields = array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'grant_type' => $this->grant_type
		);
		$this->create()->execute();
		
		// Store token
		$response = $this->response;
		if( !isset( $response['access_token'] ) )
			throw new \Exception( 'Does not receive token' );
		
		$this->access_token = $response['access_token'];
		$this->token_type = $response['token_type'];
		$this->expires_in = $response['expires_in'];
		
		// Set Authorization header
		$this->set_authorization_header();
		
		return $this;
	}
	
	/*
	 * Get products
	 */
	public function get_products( $options = array(), $return = array() ){
		
		// Authenticate
		$this->oauth();		
		
		// Build options
		$options += array( 'start' => 0 );
		
		$this->url = $this->products_url.'?'.$this->build_query( $options );
		$this->post_fields = '';
		
		// Request
		$this->create()->execute();
		
		// Build result
		$response = $this->response;		
		if( is_array( $response['products'] ) )
			$return = array_merge( $return, $response['products'] );		
		
		// Check if is the last page
		if( $response['total'] > ( $response['limit'] + $response['start'] ) ){
			$options['start'] = $response['limit'] + $response['start'];
			return $this->get_products( $options, $return );
		}
		
		else
			return $return;
	}
	
	/*
	 * Get product
	 */
	public function get_product( $product_id = 0 ){
		
		// Authenticate
		$this->oauth();
		
		$this->url = $this->products_url.'/'.((int) $product_id);
		$this->post_fields = '';
		
		// Request
		$this->create()->execute();
		
		return $this->response;
	}
	
	/*
	 * Get orders
	 */
	public function get_orders( $options = array(), $return = array() ){
		
		// Authenticate
		$this->oauth();		
		
		// Build options
		$options += array( 'start' => 0 );
		
		$this->url = $this->sales_url.'?'.$this->build_query( $options );
		$this->post_fields = '';
		
		// Request
		$this->create()->execute();
		
		// Build result
		$response = $this->response;		
		if( is_array( $response['sales'] ) )
			$return = array_merge( $return, $response['sales'] );		
		
		// Check if is the last page
		if( $response['total'] > ( $response['limit'] + $response['start'] ) ){
			$options['start'] = $response['limit'] + $response['start'];
			return $this->get_orders( $options, $return );
		}
		
		else
			return $return;
	}
	
	/*
	 * Create order
	 */
	public function create_order( $options = array() ){
		// Authenticate
		$this->oauth();
		
		$this->url = $this->sales_url;
		$this->post_fields = $options;
		
		// Request
		$this->create()->execute();
		
		return $this->response; 
	}
	
	/*
	 * Get order
	 */
	public function get_order( $order_id = 0 ){
		
		// Authenticate
		$this->oauth();
		
		$this->url = $this->sales_url.'/'.((int) $order_id);
		$this->post_fields = '';
		
		// Request
		$this->create()->execute();
		
		return $this->response;
	}
	
	/*
	 * Get webhooks
	 */
	public function get_webhooks(){
		
		// Authenticate
		$this->oauth();
		
		$this->url = $this->webhooks_url;
		$this->post_fields = '';
		
		// Request
		$this->create()->execute();
		
		if( isset( $this->response['webhooks'] ) )
			return $this->response['webhooks'];
		else
			return array();
	}
	
	/*
	 * Create webhooks
	 */
	public function create_webhooks( $webhooks = array() ){
		
		// Authenticate
		$this->oauth();
		
		// Clear previous webhooks
		$this->delete_webhooks();
		
		// Create webhooks
		$webhooks += array(
			'sales/updated' => 'http://testing.com',
			'product/created' => 'http://testing.com',
			'sku/updated' => 'http://testing.com',
			'media/updated' => 'http://testing.com',
			'media/deleted' => 'http://testing.com',
		);
		
		$this->url = $this->webhooks_url;
		foreach( $webhooks as $topic => $address ){
			$this->post_fields = array(
				'topic' => $topic,
				'address' => $address,
			);
			$this->create()->execute();
		}	
	}
	
	/*
	 * Delete webhooks
	 */
	public function delete_webhooks( $webhooks = 'all' ){
		
		// Authenticate
		$this->oauth();
		
		if( 'all' === $webhooks )
			$webhooks = $this->get_webhooks();
		
		foreach( $webhooks as $webhook ){
			$this->url = $this->webhooks_url.'/'.$webhook['id'];
			$this->custom_request = 'DELETE';
			$this->create()->execute();			
		}	
	}
}