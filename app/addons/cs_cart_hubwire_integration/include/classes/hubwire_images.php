<?php 
namespace OMC\Hubwire;

use Tygh\Registry;

require_once 'hubwire_abstract_class.php';

class Images extends Abstract_Class{
	
	public $cscart_product_id;
	private $cscart_images_cache;
	
	public function __construct( $company_id = 0, $cscart_product_id = 0 ){
		parent::__construct( $company_id );
		
		$this->cscart_product_id = $cscart_product_id;
	}
	
	// Get images pair id with hubwire hubwire media id
	public function load_cscart_product_images( $cscart_product_id = 0 ){
		if( empty( $cscart_product_id ) )
			$cscart_product_id = $this->cscart_product_id;
		
		if( empty( $cscart_product_id ) )
			throw new \Exception( 'Product ID is not provided!' );
		
		if( empty( $this->cscart_images_cache ) )
			$this->cscart_images_cache = db_get_hash_array("SELECT pair_id, image_id, detailed_id, object_id, type, hubwire_media_id FROM ?:images_links a WHERE object_type = 'product' AND object_id = ?s ORDER BY a.position", 'hubwire_media_id', $cscart_product_id );
		
		return $this->cscart_images_cache;
	}
	
	// Main function to create/update image
	public function update( $hubwire_images ){
		
		if( empty( $this->cscart_product_id ) )
			throw new \Exception( 'Product ID is not provided!' );
		
		// Prepare Images update
		if( ! $this->prepare_images( $hubwire_images ) )
			return;
		
		// create/ update images
		fn_attach_image_pairs( 'product_main', 'product', $this->cscart_product_id, DEFAULT_LANGUAGE );
		
		// Update additional images
		fn_attach_image_pairs( 'product_add_additional', 'product', $this->cscart_product_id, DEFAULT_LANGUAGE );
	}
	
	// Check if need to update image
	public function is_modified( $hubwire_images ){
		
		// Load product image cache
		$this->load_cscart_product_images();
		
		// Difference total images
		if( count( $hubwire_images ) !== count( $this->cscart_images_cache ) )
			return true;
		
		// Difference image oreiantation
		$hubwire_image = reset( $hubwire_images );
		$cscart_image = reset( $this->cscart_images_cache );
		while( $hubwire_image ){
			
			// Different id
			if( $hubwire_image['id'] != $cscart_image['hubwire_media_id'] )
				return true;
			
			$hubwire_image = next( $hubwire_images );
			$cscart_image = next( $this->cscart_images_cache );
		}
		
		return false;			
	}
	
	// Remove current image pair
	public function remove_images(){
		
		// Load product image cache
		$this->load_cscart_product_images();
		
		foreach( $this->cscart_images_cache as $image )
			fn_delete_image_pair( $image['pair_id'] );
	}
	
	// Unset $_REQUEST about the image
	public function unset_request(){
		unset( 
			$_REQUEST['file_product_main_image_icon'],
			$_REQUEST['type_product_main_image_icon'],
			$_REQUEST['file_product_main_image_detailed'],
			$_REQUEST['type_product_main_image_detailed'],
			$_REQUEST['product_main_image_data'],
			$_REQUEST['file_product_add_additional_image_icon'],
			$_REQUEST['type_product_add_additional_image_icon'],
			$_REQUEST['file_product_add_additional_image_detailed'],
			$_REQUEST['type_product_add_additional_image_detailed'],
			$_REQUEST['product_add_additional_image_data']
		);		
	}
	
	// Request remove media from hubwire
	public function request_remove_media( $hubwire_images = array() ){
		
		$ids = array();
		foreach( $hubwire_images as $hubwire_image ){
			if( isset( $hubwire_image['id'] ) )
				$ids[] = $hubwire_image['id'];
		}
		
		if( empty( $ids ) )
			return;
		
		$pair_ids = db_get_fields( "SELECT pair_id FROM ?:images_links WHERE hubwire_media_id IN(?a)", $ids );
		
		foreach( $pair_ids as $pair_id ){
			fn_delete_image_pair( $pair_id );
		}
	}
	
	// Prepare $_REQUEST variable before create/update product images
	public function prepare_images( $hubwire_images ){
		
		$this->unset_request();
		
		if( empty( $hubwire_images ) || !$this->is_modified( $hubwire_images ) )
			return false;
		
		// Remove current product image
		$this->remove_images();
		
		$is_main = true;
		$position = 0;
		
		// For main images
		if( count( $hubwire_images ) > 0 ){
			$_REQUEST = array(
				'file_product_main_image_icon' => array(),
				'type_product_main_image_icon' => array(),
				'file_product_main_image_detailed' => array(),
				'type_product_main_image_detailed' => array(),
				'product_main_image_data' => array()
			) + $_REQUEST;			
		}
		
		// For additional images
		if( count( $hubwire_images ) > 1 ){
			$_REQUEST = array(
				'file_product_add_additional_image_icon' => array(),
				'type_product_add_additional_image_icon' => array(),
				'file_product_add_additional_image_detailed' => array(),
				'type_product_add_additional_image_detailed' => array(),
				'product_add_additional_image_data' => array()
			) + $_REQUEST;
		}
		
		// Loop
		foreach( $hubwire_images as $hubwire_image ){
			
			// Set the first image as main image
			if( $is_main ){
				
				// Set image path
				if ( !empty( $hubwire_image['url'] ) ) {
					$_REQUEST['file_product_main_image_detailed'][] =	$hubwire_image['url'];
					$_REQUEST['type_product_main_image_detailed'][] =	'url';
				}
				
				// Basic data to create new main image
				if( isset( $current_images[$hubwire_image['id']] ) ){
					$data = array(
						'pair_id' => $current_images[$hubwire_image['id']]['pair_id'],
						'type' => 'M',
					);
				} else {
					$data = array(
						'pair_id' => 0,
						'type' => 'M',
						'object_id' => 0,
						'image_alt' => '',
						'detailed_alt' => '',
						'hubwire_media_id' => $hubwire_image['id']
					);					
				}
					
				$_REQUEST['product_main_image_data'][] = $data;
				
				$is_main = false;
			}
			
			// Set other images as additional images
			else{
				
				// Basic data to create new additional image 
				if ( !empty( $hubwire_image['url'] ) ) {
					$_REQUEST['file_product_add_additional_image_detailed'][] =	$hubwire_image['url'];
					$_REQUEST['type_product_add_additional_image_detailed'][] = 'url';
				}
				
				// Basic data to create new additional image
				if( isset( $current_images[$hubwire_image['id']] ) ){
					$data = array(
						'pair_id' => $current_images[$hubwire_image['id']]['pair_id'],
						'type' => 'A',
					);
				} else {
					$data = array(
						'position' => ++$position,
						'pair_id' => 0,
						'type' => 'A',
						'object_id' => 0,
						'image_alt' => '',
						'detailed_alt' => '',
						'hubwire_media_id' => $hubwire_image['id']
					);					
				}
				
				$_REQUEST['product_add_additional_image_data'][] = $data;
			}	
		}
		
		return true;
	}
}