<?php 
namespace OMC\Hubwire;

use Tygh\Registry;

require_once 'hubwire_abstract_class.php';

class SKUs extends Abstract_Class{
	
	public $hubwire_skus;
	public $cscart_product_id;
	private $sku_options_cache;
	static $options_super_cache = array();
	
	public function __construct( $company_id = 0, $hubwire_skus = array(), $cscart_product_id = 0 ){
		parent::__construct( $company_id );
		
		if( !empty( $cscart_product_id ) )
			$this->cscart_product_id = $cscart_product_id;
		
		if( empty( $hubwire_skus ) )
			throw new \Exception( 'Missing hubwire SKUs!' );
		
		$this->hubwire_skus = $hubwire_skus;
	}
	
	public function get_singular_values(){
		
		$hubwire_skus = $this->hubwire_skus;
		$price = $list_price = $weight = 0;
		$status = 'D';
		
		foreach( $hubwire_skus as $hubwire_sku ){	
			
			// Fix variable
			$hubwire_sku['sale_price'] = floatval( $hubwire_sku['sale_price'] );
			$hubwire_sku['retail_price'] = floatval( $hubwire_sku['retail_price'] );
			$hubwire_sku['weight'] = floatval( $hubwire_sku['weight'] );
			
			// minimum price
			$tmp = !empty( $hubwire_sku['sale_price'] ) ? $hubwire_sku['sale_price'] : $hubwire_sku['retail_price'];
			if( 0 === $price || $price > $tmp  )
				$price = $tmp;
			
			// minimum list price
			if( 0 === $list_price || $list_price > $hubwire_sku['retail_price']  )
				$list_price = $hubwire_sku['retail_price'];
			
			// minimum weight
			if( 0 === $weight || $weight > $hubwire_sku['weight']  )
				$weight = $hubwire_sku['weight'];
			
			// status
			if( 
				'A' !== $status && 
				!empty( $hubwire_sku['active'] ) && 
				'0' !== $hubwire_sku['active']
			)
				$status = 'A';
		}
		
		$return = compact( 'price', 'list_price', 'weight', 'status' );
		
		return $return;
	}
	
	// Main function: Update
	public function update_from_products( $cscart_product_id = 0 ){
		$cscart_product_id = $this->need_cscart_product_id( $cscart_product_id );
		
		// Update global cscart product options
		$global_product_options = $this->update_global_product_options();
		
		// Apply global cscart product options
		$this->apply_global_product_options( $global_product_options );
		
		// Remove current combination
		$sql = 'DELETE FROM ?:product_options_inventory WHERE product_id = ?i';
		db_query( $sql, $cscart_product_id );
		
		// Create combination
		foreach( $this->hubwire_skus as $hubwire_sku ){
			
			$combination = $this->build_combination_array( $hubwire_sku['options'], $global_product_options );
			
			if( empty( $combination ) )
				continue;
			
			// Fix variable
			$hubwire_sku['sale_price'] = floatval( $hubwire_sku['sale_price'] );
			$hubwire_sku['retail_price'] = floatval( $hubwire_sku['retail_price'] );
			$hubwire_sku['weight'] = floatval( $hubwire_sku['weight'] );
			
			// Price
			$price = !empty( $hubwire_sku['sale_price'] ) ? $hubwire_sku['sale_price'] : $hubwire_sku['retail_price'];
			
			// sku id
			$sku_id = isset( $hubwire_sku['sku_id'] ) ? $hubwire_sku['sku_id'] : ( isset( $hubwire_sku['id'] ) ? $hubwire_sku['id'] : 0 );
			
			if( $sku_id ){
				$data = array(
					'product_id' => $cscart_product_id,
					'combination' => $combination,
					'amount' => $hubwire_sku['quantity'],
					'price' => $price,
					'list_price' => $hubwire_sku['retail_price'],
					'weight' => $hubwire_sku['weight'],
					'hubwire_sku_id' => $sku_id,
					'hubwire_sku' => $hubwire_sku['hubwire_sku'],
				);
				
				fn_update_option_combination( $data );
			}
		}
	}
	
	// Update skus
	public function update(){
		$hubwire_skus = $this->hubwire_skus;		
		
		foreach( $hubwire_skus as $hubwire_sku ){
			
			// Fix variable
			$hubwire_sku['sale_price'] = floatval( $hubwire_sku['sale_price'] );
			$hubwire_sku['retail_price'] = floatval( $hubwire_sku['retail_price'] );
			$hubwire_sku['weight'] = floatval( $hubwire_sku['weight'] );
			
			// Price
			$price = !empty( $hubwire_sku['sale_price'] ) ? $hubwire_sku['sale_price'] : $hubwire_sku['retail_price'];
			
			$data = array(
				'amount' => $hubwire_sku['quantity'],
				'price' => $price,
				'list_price' => $hubwire_sku['retail_price'],
				'weight' => $hubwire_sku['weight'],
			);
			
			// sku id
			$sku_id = isset( $hubwire_sku['sku_id'] ) ? $hubwire_sku['sku_id'] : ( isset( $hubwire_sku['id'] ) ? $hubwire_sku['id'] : 0 );
			
			if( $sku_id ){
				// Query
				db_query( 'UPDATE ?:product_options_inventory SET ?u WHERE hubwire_sku_id = ?i', $data, $sku_id );
			}
		}
	}
	
	// 
	public function build_combination_array( $hubwire_options = array(), $global_product_options = array() ){
		
		$return = array();
		
		foreach( $hubwire_options as $key => $value ){
			if( isset( $global_product_options[$key] ) ){
				$product_option = $global_product_options[$key];
				
				if( isset( $product_option['variants'][$value] ) )
					$return[$product_option['option_id']] = $product_option['variants'][$value];
			}	
		}
		
		return $return;
	}
	
	public function apply_global_product_options( $product_options ){
		$product_id = $this->cscart_product_id;
		
		// Remove current product options link
		$sql = 'DELETE FROM ?:product_global_option_links WHERE product_id = ?i';
		db_query( $sql, $this->cscart_product_id );
		
		// Rebuild options link
		$sql = '';
		foreach( $product_options as $product_option )
			$sql .= "( $product_id, {$product_option['option_id']} ),";
		$sql = rtrim( $sql, ',' );
		
		db_query( "INSERT INTO ?:product_global_option_links ( product_id, option_id ) VALUES $sql" );		
	}
	
	/*
   *	Update global cscart product options
	 *	Return $sku_options with cscart_product_options id
	 */
	public function update_global_product_options(){
		
		$sku_options = $this->get_sku_options();
		
		foreach( $sku_options as $option_name => & $sku_option ){
			
			// Check cache
			if( isset( self::$options_super_cache[$option_name] ) )
				$sku_option_cache = & self::$options_super_cache[$option_name];
			
			else{
				// Check if the option exists
				$option = $this->get_global_product_option_by_name( $option_name );
				
				// Is in cscart
				if( isset( $option['option_id'] ) )
					$option_id = $option['option_id'];
				
				// Not in cscart
				else{
					// Create new option because it is not in cscart
					$data = array(
						'option_name' => $option_name,
						'position' => 0,
						'inventory' => 'Y',
						'company_id' => 0,
						'option_type' => 'S',
						'description' => '',
						'comment' => '',
						'required' => 'N',
						'regexp' => '',
						'inner_hint' => '',
						'incorrect_message' => '',
						'allowed_extensions' => '',
						'max_file_size' => '',
						'multiupload' => 'N',
						'lang_code' => 'en'
					);
					$option_id = $data['option_id'] = db_query( 'INSERT INTO ?:product_options ?e', $data );
					db_query( "INSERT INTO ?:product_options_descriptions ?e", $data );
				}
				
				// Store super cache
				self::$options_super_cache[$option_name] = array(
					'option_id' => $option_id,
					'variants' => array()
				);
				$sku_option_cache = &self::$options_super_cache[$option_name];
			}
			
			// Store option id for output
			$sku_option['option_id'] = $sku_option_cache['option_id'];
			
			// Go through variants
			foreach( $sku_option['variants'] as $variant_name => & $variant ){
				
				// Check cache
				if( isset( $sku_option_cache['variants'][$variant_name] ) )
					$sku_variant_cache = & $sku_option_cache['variants'][$variant_name];
				
				else{
					// Check if the variant exists
					$variant = $this->get_global_product_option_variant_by_name( $variant_name, $sku_option['option_id'] );
					
					// Is in cscart
					if( isset( $variant['variant_id'] ) )
						$variant_id = $variant['variant_id'];
					
					// Not in cscart
					else{
						// Create variant
						$data = array(
							'option_id' => $sku_option['option_id'],
							'variant_name' => $variant_name,
							'status' => 'A',
							'lang_code' => 'en',
						);
						
						$variant_id = $data['variant_id'] = db_query( "INSERT INTO ?:product_option_variants ?e", $data );
            db_query( "INSERT INTO ?:product_option_variants_descriptions ?e", $data );
					}
					
					$sku_option_cache['variants'][$variant_name] = $variant_id;
					$sku_variant_cache = & $sku_option_cache['variants'][$variant_name];					
				}
				
				// Store variant for output
				$variant = $sku_variant_cache;				
			}
		}
		
		return $sku_options;
		
	}
	
	// Get global option by name
	public function get_global_product_option_by_name( $name = '' ){
		if( empty( $name ) )
			return array();
		
		$sql = 'SELECT a.option_id FROM ?:product_options a JOIN ?:product_options_descriptions b ON a.option_id = b.option_id WHERE a.product_id = 0 AND b.option_name = ?s';
		
		return db_get_row( $sql, $name );
		
	}
	
	// Get global option variant by name
	public function get_global_product_option_variant_by_name( $name = '', $option_id = 0 ){
		if( empty( $name ) || empty( $option_id ) )
			return array();
		
		$sql = 'SELECT b.variant_id FROM ?:product_options a JOIN ?:product_option_variants b ON a.option_id = b.option_id JOIN ?:product_option_variants_descriptions c ON b.variant_id = c.variant_id WHERE a.product_id = 0 AND a.option_id = ?i AND c.variant_name = ?s';
		
		return db_get_row( $sql, $option_id, $name );
	}
	
	// Extract unique sku options
	public function get_sku_options(){
		
		// Check cache
		if( !empty( $this->sku_options_cache ) )
			return $this->sku_options_cache;
		
		$data = array();
		// Loop
		foreach( $this->hubwire_skus as $sku ){
			$options = (array) $sku['options'];
			
			foreach( $options as $key => $value ){				
				if( !isset( $data[$key] ) ) 
					$data[$key] = array( 'option_id' => 0, 'variants' => array() );
				if( !isset( $data[$key]['variants'][$value] ) ) 
					$data[$key]['variants'][$value] = 0;
			}
		}
		
		return ( $this->sku_options_cache = $data );
	}
	
	// Return cscart product id, if not then throw error
	public function need_cscart_product_id( $cscart_product_id = 0 ){
		if( empty( $cscart_product_id ) )
			$cscart_product_id = $this->cscart_product_id;
		
		if( empty( $cscart_product_id ) )
			throw new \Exception( 'Missing Cs Cart product ID.' );
		
		if( empty( $this->cscart_product_id ) )
			$this->cscart_product_id = $cscart_product_id;
		
		else{
			if( $this->cscart_product_id !== $cscart_product_id )
				throw new \Exception( 'Cannot change cscart product id.' );
		}
		
		return $cscart_product_id;
	}
}