<?php 
namespace OMC\Hubwire;

use Tygh\Registry;

class Abstract_Class {
	
	static $c;
	public $company_id;
	public $default_category_id;
	public $tax_id;
	public $order_shipped_status;
	
	public function __construct( $company_id = 0 ){
		if( 
			empty( $company_id ) && 
			isset( $_REQUEST['company_id'] ) 
		)
			$company_id = $_REQUEST['company_id'];
			
		if( empty( $company_id ) )
			throw new \Exception( 'Company ID is not set!' );
		
		// Set basic properties
		$this->company_id = $company_id;
		
		// Set api email
		$api_email = Registry::get('addons.cs_cart_hubwire_integration.api_email');
		if( empty( $api_email ) )
			throw new \Exception( 'Api email is not set.' );
		$this->api_email = $api_email;
		
		// Set api key
		$api_key = Registry::get('addons.cs_cart_hubwire_integration.api_key');
		if( empty( $api_key ) )
			throw new \Exception( 'Api key is not set.' );
		$this->api_key = $api_key;
		
		// Set brand id
		$brand_id = Registry::get('addons.cs_cart_hubwire_integration.brand_id');
		if( empty( $brand_id ) )
			throw new \Exception( 'Brand ID is not set.' );
		$this->brand_id = $brand_id;
		
		// Set tax id
		$tax_id = Registry::get('addons.cs_cart_hubwire_integration.tax_id');
		if( empty( $tax_id ) )
			throw new \Exception( 'Tax ID is not set.' );
		$this->tax_id = $tax_id;
		
		// Set tax id
		$order_shipped_status = Registry::get('addons.cs_cart_hubwire_integration.shipped_order_status');
		if( empty( $order_shipped_status ) )
			throw new \Exception( 'Shipped status is not set.' );
		$this->order_shipped_status = $order_shipped_status;
		
		// Set default category
		$default_category_id = db_get_field( "SELECT default_products_category FROM ?:companies WHERE company_id = ?i", $company_id );
		if( empty( $default_category_id ) )
			$default_category_id = Registry::get('addons.cs_cart_hubwire_integration.default_category_id');
		if( empty( $default_category_id ) )
			throw new \Exception( 'Default product category is not set.' );
		$this->default_category_id = $default_category_id;
	}
	
	public function connect(){
		if( !empty( self::$c ) )
			return;
		
		$api_info = $this->get_company_api_info();
		
		if( 
			empty( $api_info ) ||
			empty( $api_info['client_id'] ) ||
			empty( $api_info['client_secret'] )
		)
			throw new \Exception( 'This company does not have hubwire api info!' );
		
		require_once 'hubwire_curl.php';
		self::$c = new Curl( $api_info );
	}
	
	public function get_company_api_info(){
		
		$company_id = $this->company_id;
		
		// Seek api info in $_REQUEST
		if( 
			isset( $_REQUEST['company_id'] ) &&
			isset( $_REQUEST['company_data'] ) &&
			$_REQUEST['company_id'] == $company_id			
		){
			$data = $_REQUEST['company_data'];
		} 
		
		// Otherwise, go search database
		else {
			$data = fn_get_company_data( $company_id );
		}
		
		return array(
			'client_id' => !empty( $data['hubwire_client_id'] ) ? $data['hubwire_client_id'] : '',
			'client_secret' => !empty( $data['hubwire_client_secret'] ) ? $data['hubwire_client_secret'] : '',
		);
	}	
}