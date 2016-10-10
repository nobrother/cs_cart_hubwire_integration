<?php 
namespace OMC\Hubwire;

use Tygh\Registry;

require_once 'hubwire_abstract_class.php';
require_once 'hubwire_skus.php';
require_once 'hubwire_images.php';

class Webhooks extends Abstract_Class{	
	
	private $_cscart_products_cache = array();
	private $_cscart_brands_cache = array();
	
	public function __construct( $company_id = 0 ){
		parent::__construct( $company_id );
	}	
	
	public function create_webhooks(){
		
		$token = fn_omc_token_encode( array(
			'user' => $this->api_email,
			'api_key' => $this->api_key,
		) );
		
		$this->connect();
		$url = Registry::get('config.http_location');
		$url .= '/api/HubwireListener/';
		$url .= '?token='.CCHI_TOKEN.'__action-';
		$webhooks = array(
			'sales/created' => $url.'sales_created',
			'sales/updated' => $url.'sales_updated',
			'product/created' => $url.'product_created',
			'product/updated' => $url.'product_updated',
			'sku/created' => $url.'sku_created',
			'sku/updated' => $url.'sku_updated',
			'media/created' => $url.'media_created',
			'media/updated' => $url.'media_updated',
			'media/deleted' => $url.'media_deleted',
		);
		foreach( $webhooks as & $address ){
			$address .= '__company_id-'.$this->company_id;
		}
		
		//error_log( print_r( $webhooks, true ) );
		//error_log( print_r( self::$c->get_webhooks(), true ) );
		
		
		self::$c->create_webhooks( $webhooks );
	}
}