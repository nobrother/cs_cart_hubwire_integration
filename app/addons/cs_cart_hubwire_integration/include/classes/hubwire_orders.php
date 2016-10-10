<?php 
namespace OMC\Hubwire;

use Tygh\Registry;

require_once 'hubwire_abstract_class.php';

class Orders extends Abstract_Class{
	
	public $cscart_product_cache;
	public $cscart_promotions_cache;
	static $order_prefix = 'RD_';
	public $statuses;
	
	public function __construct( $company_id = 0 ){
		parent::__construct( $company_id );
		
		$this->statuses = array(
			'processed' => 'P',
			'shipped' => $this->order_shipped_status,
			'completed' => 'C',
			'cancelled' => 'I',
		);
	}
	
	/*
	 * Get orders
	 */
	public function get_orders( $cscart_order_id = 0 ){
		
		$this->connect();
		
		// Get all orders
		if( empty( $cscart_order_id ) )
			$orders = self::$c->get_orders();
		
		// Get single
		else
			$orders = array( self::$c->get_order( $cscart_order_id ) );
		
		// Return orders in key-value style
		$return = array();
		foreach( $orders as $order )
			$return[$order['id']] = $order;	
		
		return $return;
	}
	
	
	/*
	 * POST and order to hubwire
	 */
	public function post_order( $order_info = array() ){
		
		// Checking security and  variables
		if( empty( $order_info ) )
			throw new \Exception( 'Order Info is empty!' );		
		if( !$order_info['company_id'] == $this->company_id )
			throw new \Exception( 'Company ID is not consistance!' );		
		if( $order_info['is_parent_order'] !== 'N' )
			throw new \Exception( 'Parent order is not allowed!' );
		
		// Check promotions
		$has_promotion = false;
		$post_promotion = false;
		if( !empty( $order_info['promotions'] ) && !empty( $order_info['coupons'] ) ){
			$provider = (int) $this->get_promotion_provider( key( $order_info['promotions'] ) );
			
			if( $provider == $this->company_id || $provider === 0 ){
				$has_promotion = true;
				$post_promotion = ( $provider === 0 );
			}
		}
		
		$data = array(
			'order_number' => self::$order_prefix.$order_info['order_id'],
			'order_date' => date( 'Y-m-d H:i:s', $order_info['timestamp'] ),
			'total_price' => $order_info['total'],
			'total_discount' => $post_promotion ? ( $order_info['discount'] + $order_info['subtotal_discount'] ) : 0,
			'shipping_fee' => 0,	// TODO: need to discuss
			'currency' => 'MYR',
			'payment_type' => '',
			'status' => 'paid',
			'shipping_info' => array(
				'recipient' => $order_info['s_firstname'].' '.$order_info['s_lastname'],
				'phone' => $order_info['s_phone'],
				'tracking_no' => '',
				'address_1' => $order_info['s_address'],
				'address_2' => $order_info['s_address_2'],
				'city' => $order_info['s_city'],
				'postcode' => $order_info['s_zipcode'],
				'state' => $order_info['s_state_descr'],
				'country' => $order_info['s_country_descr'],
			),
			'items' => array(),
			'customer' => array(
				'name' => $order_info['firstname'].' '.$order_info['lastname'],
				'email' => $order_info['email'],
				'phone' => $order_info['phone'],
			),
		);
		
		// Payment
		if( isset( $order_info['payment_method'], $order_info['payment_method']['payment_id'] ) )
			$data['payment_type'] = $order_info['payment_method']['payment'];
		
		// Items
		$items = &$data['items'];
		foreach( $order_info['products'] as $combination_hash => $product ){
			
			$item = array(
				// No need
				//'id' => $this->get_hubwire_product_id( $product['product_id'] ), 
				'sku_id' => 0,
				'product_name' => $product['product'],
				'hubwire_sku' => '',
				'quantity' => $product['amount'],
				'returned_quantity' => 0,
				'price' => $product['subtotal'],
				'discount' => $post_promotion ? $product['discount'] : 0,
				'tax' => $product['tax_value'],
				'tax_inclusive' => 1
			);
			
			$variant = $this->get_cscart_product_variant( $combination_hash );
			if( !empty( $variant ) ){
				$item['sku_id'] = $variant['hubwire_sku_id'];
				$item['hubwire_sku'] = $variant['hubwire_sku'];		
			}
			
			if( !empty( $item['sku_id'] ) )
				$items[] = $item;
			
		}
		
		//$data['order_number'] = uniqid();  // Testing only
		$this->connect();
		$response = self::$c->create_order( $data );
		
		// Update hubwire_product_id
		db_query( 'UPDATE ?:orders SET ?u WHERE order_id = ?i', array( 'hubwire_order_id' =>$response['order_id'] ), $order_info['order_id'] );
	}
	
	// Update
	public function update( $hubwire_orders = array() ){
		
		if( empty( $hubwire_orders ) )
			return;
		
		foreach( $hubwire_orders as $hubwire_order ){
			// Extract the order id
			$cscart_order_id = preg_replace( '/\D/', '', $hubwire_order['order_number'] );
			if( empty( $cscart_order_id ) )
				continue;
			
			// Status change
			$status = $this->map_status( $hubwire_order['status'] );
			$notification = array(
				'notify_user' => true,
				'notify_department' => true,
			);
			fn_change_order_status( $cscart_order_id, $status, '', fn_get_notification_rules( $notification, true ) );
			
			// Change tracking no
			$data = array(
				'hubwire_tracking_no' => empty( $hubwire_order['shipping_info']['tracking_no'] ) ? '' : $hubwire_order['shipping_info']['tracking_no']
			);
			$sql = "UPDATE ?:orders SET ?u WHERE order_id = ?i";
			db_query( $sql, $data, $cscart_order_id );
		}		
	}
	
	// Map hubwire order status to cs cart order status
	public function map_status( $hubwire_order_status = '' ){
		
		switch( strtolower( $hubwire_order_status ) ){
			
			case 'shipped':
			case 'shipping':
			case 'completed':
			case 'complete':
				return $this->statuses['shipped'];
			
			case 'cancel':
			case 'canceled':
			case 'cancelled':
			case 'refund':
			case 'refunded':
			case 'fail':
			case 'failed':
				return $this->statuses['cancelled'];
			
			case 'paid':
			case 'new': 
			case 'picking': 
			case 'packing': 
			case 'ready to ship':
			default:
				return $this->statuses['processed'];
		}
		
	}
	
	// Get product option combination
	public function get_cscart_product_variant( $combination_hash = '' ){
		
		if( empty( $combination_hash ) )
			return array();
		
		return db_get_row( 'SELECT * FROM ?:product_options_inventory WHERE combination_hash = ?s', $combination_hash );
		
	}
	
	// Get hubwire product id
	public function get_hubwire_product_id( $cscart_product_id = 0 ){		
		
		$product = $this->get_cscart_product( $cscart_product_id );
		if( empty( $product ) )
			return 0;
		
		return $product['hubwire_product_id'];
	}
	
	// Get cscart product id
	public function get_cscart_product( $cscart_product_id = 0 ){
		if( empty( $cscart_product_id ) )
			return 0;
		
		if( isset( $this->cscart_product_cache[$cscart_product_id] ) )
			return $this->cscart_product_cache[$cscart_product_id];
		
		$result = db_get_row( 'SELECT * FROM ?:products WHERE product_id = ?i', $cscart_product_id);
		
		if( empty( $result ) )
			return 0;
		
		return ( $this->cscart_product_cache[$cscart_product_id] = $result );
	}
	
	// Get promotion provider
	public function get_promotion_provider( $promotion_id = 0 ){		
		
		$promotion = $this->get_promotion( $promotion_id );
		if( empty( $promotion ) )
			return 0;
		
		return $promotion['provider'];
	}
	
	// Get cscart product id
	public function get_promotion( $promotion_id = 0 ){
		if( empty( $promotion_id ) )
			return 0;
		
		if( isset( $this->cscart_promotions_cache[$promotion_id] ) )
			return $this->cscart_promotions_cache[$promotion_id];
		
		$result = db_get_row( 'SELECT * FROM ?:promotions WHERE promotion_id = ?i', $promotion_id);
		
		if( empty( $result ) )
			return 0;
		
		return ( $this->cscart_promotions_cache[$promotion_id] = $result );
	}
}