<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && defined( 'AJAX_REQUEST' )) {
		
		try{
			// Catch mode
			if ($mode == 'update_hubwire') {
				
				if( empty( $_REQUEST['product_data']['company_id'] ) )
					throw new Exception( 'We do not know the vendor.' );
				
				if( empty( $_REQUEST['product_data']['hubwire_product_id'] ) )
					throw new Exception( 'We do not know the hubwire product ID.' );
				
				$company_id = $_REQUEST['product_data']['company_id'];
				$hubwire_product_id = $_REQUEST['product_data']['hubwire_product_id'];
				
				//Load products
				require_once CCHI_CLASSES_DIR.'/hubwire_products.php';
				$products_handler = new \OMC\Hubwire\Products( $company_id );
				$products_handler->connect();
				$hubwire_products = $products_handler->get_products( $hubwire_product_id );
				$products_handler->bulk_resync( $hubwire_products );
			
				fn_set_notification('N', __('successful'), 'YEah!', 'K', '', false);
			}
			
		}
		
		// Catch error
		catch(Exception $e){
			fn_set_notification('E', __('error'), $e->getMessage(), 'K', '', false);
		}
		
		
	exit;
}