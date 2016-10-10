<?php

if ( !defined('AREA') ) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Navigation\LastView;
use Tygh\Mailer;

// Call setting template
function fn_cs_cart_hubwire_integration_settings_tpl(){
	
	if ( isset(Tygh::$app['view'] ) )
		return Tygh::$app['view']->fetch('addons/cs_cart_hubwire_integration/settings/info.tpl');
}

// Add hubwire media id
function fn_cs_cart_hubwire_integration_update_image_pairs( $pair_ids, $icons, $detailed, $pairs_data, $object_id, $object_type, $object_ids, $update_alt_desc, $lang_code ){
	
	if( !empty( $pair_ids ) && !empty( $pairs_data ) ){
		for( $i = 0, $l = count( $pair_ids ); $i < $l; $i++ ){
			if( 
				!empty( $pair_ids[$i] ) &&
				!empty( $pairs_data[$i]['hubwire_media_id'] )
			){
				db_query(
					"UPDATE ?:images_links SET hubwire_media_id = ?i WHERE pair_id = ?i", 
					$pairs_data[$i]['hubwire_media_id'], 
					$pair_ids[$i] 
				);
			}			
		}		
	}	
}

// Store extra hubwire sku info when option combination update
function fn_cs_cart_hubwire_integration_update_option_combination_post( $combination_data, $combination_hash, $inventory_amount ){
	
	// Update hubwire sku info
	if( !empty( $combination_data['hubwire_sku_id'] ) ){
		$sql = 'UPDATE ?:product_options_inventory SET ?u WHERE product_id = ?i AND combination_hash = ?s';
		
		$data = array(
			'price' => $combination_data['price'],
			'list_price' => $combination_data['list_price'],
			'weight' => $combination_data['weight'],
			'hubwire_sku_id' => $combination_data['hubwire_sku_id'],
			'hubwire_sku' => $combination_data['hubwire_sku'],
		);
		$product_id = $combination_data['product_id'];
		db_query( $sql, $data, $product_id, $combination_hash );		
	}	
}

// Add other way to authenticate access api
function fn_cs_cart_hubwire_integration_api_get_user_data_pre( $api, $user_data ){
	
	if( !empty( $_REQUEST['token'] ) && strpos( $_REQUEST['token'], CCHI_TOKEN ) === 0 ){
		
		$user = Registry::get('addons.cs_cart_hubwire_integration.api_email');
		$api_key = Registry::get('addons.cs_cart_hubwire_integration.api_key');
		
		if( !empty( $user ) && !empty( $api_key ) )		
			$user_data = fn_get_api_user( $user, $api_key );		
		
		//error_log( print_r( $user_data, true ) );
	}
}

// Store extra hubwire sku info when option combination update
function fn_cs_cart_hubwire_integration_change_order_status( $status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order ){
	
	//error_log( print_r( $order_info, true ) );
	
	try{
		
		/*
		 * Send order to Hubwire
		 */
		if( 
			!defined( 'HUBWIRE_WEBHOOK_MODE' ) &&
			'P' === $status_to &&		// Order status change to 'Processing'
			!empty( $order_info ) &&
			$order_info['is_parent_order'] == 'N' &&
			empty( $order_info['hubwire_order_id'] ) &&
			fn_is_hubwire_merchant( $order_info['company_id'] )
		){
			
			require_once CCHI_CLASSES_DIR.'/hubwire_orders.php';
			$orders_handler = new \OMC\Hubwire\Orders( $order_info['company_id'] );
			$orders_handler->post_order( $order_info );
		}
	}
		
	// Catch error
	catch(Exception $e){
		fn_set_notification('E', __('error'), $e->getMessage(), 'K', '', false);
	}
}

// Add new formatter
function fn_cs_cart_hubwire_integration_api_handle_request( $api, $authorized ){
	require_once( 'Tygh/Api/Formats/Form.php' );
	\Tygh\Api\FormatManager::initiate( array( 'json', 'text', 'form' ) );	
}


// Helper: Check company is hubwire merchant or not
function fn_is_hubwire_merchant( $company_id = 0 ){
	if( empty( $company_id ) )
		return false;
	
	return db_get_field( 'SELECT hubwire_client_id FROM ?:companies WHERE company_id = ?i', $company_id ) ? true : false;	
}

// Helper: Encode token
function fn_omc_token_encode( $data = '' ){
	// $token = serialize( $data );
	// $token = convert_uuencode( $token );
	// $token = base64_encode( $token );
	// $token = str_replace( '=', '_', $token );
	// $token = str_replace( '/', '%', $token );
	$token = str_replace( '@', '.', $data );
	
	return $token;
}

// Helper: Decode token
function fn_omc_token_decode( $token = 0 ){
	// $return = str_replace( '%', '/', $token );
	// $return = str_replace( '_', '=', $return );
	// $return = base64_decode( $return );
	// $return = convert_uudecode( $return );
	// $return = unserialize( $return );
	return $return;
}

?>