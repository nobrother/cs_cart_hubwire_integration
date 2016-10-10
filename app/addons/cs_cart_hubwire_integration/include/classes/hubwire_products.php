<?php 
namespace OMC\Hubwire;

use Tygh\Registry;

require_once 'hubwire_abstract_class.php';
require_once 'hubwire_skus.php';
require_once 'hubwire_images.php';

class Products extends Abstract_Class{	
	
	private $_cscart_products_cache = array();
	private $_cscart_brands_cache = array();
	
	public function __construct( $company_id = 0 ){
		parent::__construct( $company_id );
	}	
	
	/*
	 * Get products
	 */
	public function get_products( $product_id = 0 ){
		
		$this->connect();
		
		// Get all products
		if( empty( $product_id ) )
			$products = self::$c->get_products();
		
		// Get single
		else
			$products = array( self::$c->get_product( $product_id ) );
		
		// Return products in key-value style
		$return = array();
		foreach( $products as $product )
			$return[$product['id']] = $product;
		
		// Map with cscart product id
		$return = $this->map_cscart_product_ids( $return );			
		
		return $return;
	}
	
	/*
	 * Bulk Resync products
	 */
	public function bulk_resync( $hubwire_products = array() ){
		
		set_time_limit( 60 * 60 * 24 );	// One day
		
		if( empty( $hubwire_products ) )
			return;
		
		foreach( $hubwire_products as $hubwire_product ){
			$this->resync( $hubwire_product );
		}		
	}
	
	/*
	 * Resync a product
	 */
	public function resync( $hubwire_product ){
		
		// Get sku single value
		$skus_handler = new SKUs( $this->company_id, $hubwire_product['sku'] );
		$skus_singular_values = $skus_handler->get_singular_values( $hubwire_product['sku'] );
		
		// Build $data
		$data = array(
			'product' => $hubwire_product['name'],
			'company_id' => $this->company_id,
			'full_description' => $hubwire_product['description'],
			'tracking' => 'O',		// Inventory track with options
			'tax_ids' => array( $this->tax_id => $this->tax_id ),
			'short_description' => $hubwire_product['sub_description'],
			'options_type' => 'F',
		) + $skus_singular_values;
		
		
		// Update
		if( !empty( $hubwire_product['cscart_product_id'] ) ){
			
			// Prepare brand update
			$data += $this->handle_brand( $hubwire_product['brand']['name'], $hubwire_product['cscart_product_id'] );
			
			// Prepare Images update
			$images_handler = new Images( $this->company_id, $hubwire_product['cscart_product_id'] );
			$images_handler->prepare_images( $hubwire_product['media'] );
			
			// Update product
			$product_id = fn_update_product( $data, $hubwire_product['cscart_product_id'], DESCR_SL );
			
			// skus update
			$skus_handler->update_from_products( $product_id );
		}
		
		// Create
		else{
			// Build $data
			$data += array(
				'category_ids' => array( $this->default_category_id ),
				'main_category' => $this->default_category_id,
				'exceptions_type' => 'F',		// First time only
				'zero_price_action' => 'R',
				'min_qty' => '0',		// First time only
				'max_qty' => '0',		// First time only
				'qty_step' => '0',	// First time only
				'min_qty' => '0',	// First time only
				'min_qty' => '0',	// First time only
				'list_qty_count' => '0',
				'usergroup_ids' => '0',	// First time only
				'timestamp' => strtotime( $hubwire_product['created_at'] ),
				'avail_since' => '',
				'out_of_stock_actions' => 'N',	// First time only
				'details_layout' => 'default',	// First time only
				'free_shipping' =>  'N', // First time only
				'hubwire_product_id' => $hubwire_product['id'], // First time only			
			);
			
			// Prepare brand update
			$data += $this->handle_brand( $hubwire_product['brand']['name'], 0 );
			
			// Update product
			$product_id = fn_update_product( $data, 0, DESCR_SL );
			
			// Update Images
			$images_handler = new Images( $this->company_id, $product_id );
			$images_handler->update( $hubwire_product['media'] );
			
			// skus update
			$skus_handler->update_from_products( $product_id );
		}
	}
	
	/*
	 * Map with cscart product id
	 */
	public function map_cscart_product_ids( $hubwire_products = array() ){
		
		if( empty( $hubwire_products ) || !is_array( $hubwire_products ) )
			return array();
		
		$hubwire_product_ids = array_keys( $hubwire_products );
			
		$sql = 'SELECT product_id, hubwire_product_id FROM ?:products WHERE hubwire_product_id IN (?a)';
		$result = db_get_array( $sql, $hubwire_product_ids );
		
		if( !empty( $result ) ){
			foreach( $result as $row ){
				$key = $row['hubwire_product_id'];
				if( isset( $hubwire_products[$key] ) )
					$hubwire_products[$key]['cscart_product_id'] = $row['product_id'];				
			}
		}
		
		return $hubwire_products;
	}
	
	/*
	 * Get cscart product data 
	 */
	public function get_cscart_product_data( $product_id ){
		
		if( empty( $product_id ) )
			return array();
		
		// Get cache
		if( isset( $this->_cscart_products_cache[$product_id] ) )
			return $this->_cscart_products_cache[$product_id];
		
		$this->_cscart_products_cache[$product_id] = fn_get_product_data( $product_id, $auth, DESCR_SL, '', true, true, true, true, false, false, true );
		
		return $this->_cscart_products_cache[$product_id];		
	}
	
	/*
	 * Build array to handle brand
	 */
	public function handle_brand( $brand_name = '', $product_id = 0 ){
		
		if(	empty( $brand_name ) )
			return array();
		
		$return = array();
		$product_features = array();
		
		// Get current product features
		if( !empty( $product_id ) ) {
			$product_data = $this->get_cscart_product_data( $product_id );
			if( isset( $product_data['product_features'] ) )
				$product_features =  $product_data['product_features'];
		}		
		
		// Simplify $product_features
		foreach( $product_features as &$value )
			$value = $value['variant_id'];
		
		$variant_id = $this->get_brand_variant_id_by_name( $brand_name );
		$product_features[$this->brand_id] = $variant_id;
		
		$return['product_features'] = $product_features;
		
		return $return;
	}
	
	/*
	 * Get brand by name
	 */
	public function get_brand_variant_id_by_name( $name = '' ){
		 
		if( empty( $name ) )
			return 0;
		
		$name_hash = md5( $name );
		
		// Get cache
		if( isset( $this->_cscart_brands_cache[$name_hash] ) )
			return $this->_cscart_brands_cache[$name_hash];
		
		$sql = 'SELECT a.variant_id FROM ?:product_feature_variant_descriptions a JOIN ?:product_feature_variants b ON a.variant_id = b.variant_id WHERE b.feature_id = ?i AND a.variant = ?s';
		$result = db_get_array( $sql, $this->brand_id, $name );
		
		if( empty( $result ) )			
			$this->_cscart_brands_cache[$name_hash] = fn_add_feature_variant( $this->brand_id, array( 'variant' => $name ) );
		
		else
			$this->_cscart_brands_cache[$name_hash] = $result[0]['variant_id'];
			
		
		return $this->_cscart_brands_cache[$name_hash];
	}
}