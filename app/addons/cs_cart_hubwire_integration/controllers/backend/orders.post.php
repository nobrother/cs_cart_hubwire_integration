<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && defined( 'AJAX_REQUEST' )) {
		
		try{
			if ($mode == 'update_hubwire') {
				if( empty( $_REQUEST['order_id'] ) )
					throw new Exception( 'Order id is empty!' );
				
				if( empty( $_REQUEST['update_order']['hubwire_order_id'] ) )
					throw new Exception( 'Hubwire order id is empty!' );
				
				$order_id = $_REQUEST['order_id'];
				$hubwire_order_id = $_REQUEST['update_order']['hubwire_order_id'];
				$company_id = db_get_field( 'SELECT company_id FROM ?:orders WHERE order_id = ?i', $order_id );
				
				if( empty( $company_id ) )
					throw new Exception( 'Company ID is empty!' );
				
				require_once CCHI_CLASSES_DIR.'/hubwire_orders.php';
				$orders_handler = new \OMC\Hubwire\Orders( $company_id );
				$orders = $orders_handler->get_orders( $hubwire_order_id );
				$orders_handler->update( $orders );
				
				fn_set_notification('N', __('successful'), 'Yeah!', 'I', '', false);
			}
		}
		
		// Catch error
		catch(Exception $e){
			fn_set_notification('E', __('error'), $e->getMessage(), 'K', '', false);
		}
		
	exit;
}
?>