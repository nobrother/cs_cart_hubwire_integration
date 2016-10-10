<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && defined( 'AJAX_REQUEST' )) {
		
	
	
	try{
		// Create webhooks
		if ($mode == 'hubwire_create_webhooks') {
			
			// Check $_REQUEST
			if ( empty( $_REQUEST['company_id'] )	) 
				throw new Exception( 'Company data is empty!' );
			
			require_once CCHI_CLASSES_DIR.'/hubwire_webhooks.php';
			$webhooks_handler = new \OMC\Hubwire\Webhooks( $_REQUEST['company_id'] );
			$webhooks_handler->create_webhooks();
			fn_set_notification('N', __('successful'), 'YEah', 'I', '', false);
			exit;
		}
		
		// Create resync products
		if ($mode == 'hubwire_resync_products') {
			
			// Check $_REQUEST
			if ( 
				empty( $_REQUEST['company_data']['company'] ) || 
				empty( $_REQUEST['company_id'] )
			) 
				throw new Exception( 'Company data is empty!' );
			
			 // Check permission
			if (
				Registry::get('runtime.company_id') && 
				Registry::get('runtime.company_id') != $_REQUEST['company_id']
			)
				throw new Exception( 'You are not allowed to do this.' );
			
			//Load products
			require_once CCHI_CLASSES_DIR.'/hubwire_products.php';
			$products_handler = new \OMC\Hubwire\Products();			
			$products_handler->connect();
			$hubwire_products = $products_handler->get_products();
			//error_log( print_r( $hubwire_products, true ) );
			$products_handler->bulk_resync( $hubwire_products );
			
			fn_set_notification('N', __('successful'), 'YEah!', 'K', '', false);
			exit;
		}
	}
	
	// Catch error
	catch(Exception $e){
		fn_set_notification('E', __('error'), $e->getMessage(), 'K', '', false);
	}
}
?>