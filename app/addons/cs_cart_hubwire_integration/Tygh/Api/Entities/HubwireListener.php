<?php

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;

class HubwireListener extends AEntity{
	
	public $company_id;
	
	public function create( $params = array() ){
		
		define( 'HUBWIRE_WEBHOOK_MODE', true );
		
		error_log( print_r( $_POST, true ) );
		error_log( print_r( $_SERVER, true ) );
		error_log( print_r( $params, true ) );
		
		try{
			if( empty( $_REQUEST['token'] ) )
				throw new \Exception( 'Token is not provided.' );
			
			$queries = $this->get_query_data( $_REQUEST['token'] );
			extract( $queries );
			
			if( empty( $action )  )
				throw new \Exception( 'Action is not provided.' );
			if( empty( $company_id ) )
				throw new \Exception( 'Company ID is not provided.' );
			
			$this->company_id = $company_id;
			
			// Data
			foreach( $params as $item ){
				$topic = isset( $item['topic'] ) ? $item['topic'] : '';
				$content = isset( $item['content'] ) ? $item['content'] : '';
				
				// Bypass empty topic or content
				if( empty( $topic ) || empty( $content ) )
					continue;
				
				// Allocate job
				switch( $topic ){
					case 'sales/created':
						$this->handle_sales_created( $content );
					break;
					
					case 'sales/updated':
						$this->handle_sales_updated( $content );
					break;
					
					case 'product/created':
						$this->handle_product_created( $content );
					break;
					
					case 'product/updated':
						$this->handle_product_updated( $content );
					break;
					
					case 'sku/created':
						$this->handle_sku_created( $content );
					break;
					
					case 'sku/updated':
						$this->handle_sku_updated( $content );
					break;
					
					case 'media/updated':
						$this->handle_media_updated( $content );
					break;
					
					case 'media/deleted':
						$this->handle_media_deleted( $content );
					break;
					
					case 'media/created':
						
					break;
					
					default:
						throw new \Exception( 'Action is not found.' );
				}				
			}
			
			return array(
				'status' => Response::STATUS_OK,
				'data' => array( 'status' => 1, 'msg' => 'Thanks :)', 'q' => $params ),
			);
			
		}
		
		catch( \Exception $e ){
			return array(
				'status' => Response::STATUS_BAD_REQUEST,
				'data' => array(
					'status' => 0,
					'error' => $e->getMessage()
				),
			);			
		}		
	}
	
	/*
	 * Handle sales created
	 */ 
	public function handle_sales_created( $data = array() ){
		// DO NOTHING		
	}
	
	/*
	 * Handle sales updated
	 */ 
	public function handle_sales_updated( $data = array() ){
		
		require_once CCHI_CLASSES_DIR.'/hubwire_orders.php';
		$orders_handler = new \OMC\Hubwire\Orders( $this->company_id );
		
		$hubwire_orders = array( $data );	// TODO??
		$orders_handler->update( $hubwire_orders );
		
	}
	
	/*
	 * Handle product created
	 */ 
	public function handle_product_created( $data = array() ){
		
		require_once CCHI_CLASSES_DIR.'/hubwire_products.php';
		$products_handler = new \OMC\Hubwire\Products( $this->company_id );
		
		$hubwire_products = array( $data );	// TODO??
		$products_handler->bulk_resync( $hubwire_products );
	}
	
	/*
	 * Handle product updated
	 */
	public function handle_product_updated( $data = array() ){
		
		require_once CCHI_CLASSES_DIR.'/hubwire_products.php';
		$products_handler = new \OMC\Hubwire\Products( $this->company_id );
		
		$hubwire_products = array( $data );	// TODO??
		$products_handler->bulk_resync( $hubwire_products );
	}
	
	/*
	 * Handle sku created
	 */
	public function handle_sku_created( $data = array() ){
		// DO NOTHING	
	}
	
	/*
	 * Handle sku updated
	 */
	public function handle_sku_updated( $data = array() ){
		require_once CCHI_CLASSES_DIR.'/hubwire_skus.php';
		$hubwire_skus = array( $data );	// TODO??
		$skus_handler = new \OMC\Hubwire\SKUs( $this->company_id, $hubwire_skus );
		$skus_handler->update();
	}
	
	/*
	 * Handle media created
	 */
	public function handle_media_updated( $data = array() ){
		// DO NOTHING			
	}
	
	/*
	 * Handle media deleted
	 */
	public function handle_media_deleted( $data = array() ){
		require_once CCHI_CLASSES_DIR.'/hubwire_images.php';
		$hubwire_images = array( $data );	// TODO??
		$images_handler = new \OMC\Hubwire\Images( $this->company_id );
		$images_handler->request_remove_media( $hubwire_images );
	}
	
	
	// Helper: Parse query data to readable array
	public function get_query_data( $queries = '' ){
		
		if( empty( $queries ) )
			$queries = $_REQUEST['token'];
		
		$data = explode( '__', $_REQUEST['token'] );
		$return = array();
		$return['token'] = array_shift( $data );
		
		foreach( $data as $query ){
			list( $key, $value ) = explode( '-', $query );
			$return[$key] = $value;
		}
		
		return $return;		
	}
	
	public function index($id = '', $params = array()){
		return array(
			'status' => Response::STATUS_METHOD_NOT_ALLOWED,
			'data' => array(),
		);
	}
	
	public function update($id, $params){
		return array(
			'status' => Response::STATUS_METHOD_NOT_ALLOWED,
			'data' => array()
		);
	}

	public function delete($id){
		return array(
			'status' => Response::STATUS_METHOD_NOT_ALLOWED,
		);
	}
	
	public function privileges(){
		return array(
			'create' => 'manage_catalog',
			'update' => 'manage_catalog',
			'delete' => 'manage_catalog',
			'index'  => 'view_catalog'
		);
	}
	
	public function privilegesCustomer(){
    return array(
        'index' => true,
				'create' => true,
    );
	}
}