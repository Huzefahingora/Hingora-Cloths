<?php

namespace PaymentPlugins\PPCP\Blocks\Rest;

use PaymentPlugins\PayPalSDK\OrderApplicationContext;
use PaymentPlugins\WooCommerce\PPCP\Utils;

class Controller {

	public function __construct() {
		add_action( 'wc_ppcp_get_order_from_cart', [ $this, 'update_order_before_create' ], 10, 2 );
	}

	/**
	 * @param \PaymentPlugins\PayPalSDK\Order $order
	 * @param \WP_REST_Request                $request
	 */
	public function update_order_before_create( $order, $request ) {
		if ( ! empty( $request['address_provided'] ) ) {
			$context = $order->getApplicationContext();
			if ( $context->getShippingPreference() === OrderApplicationContext::GET_FROM_FILE ) {
				$purchase_unit = $order->getPurchaseUnits()->get( 0 );
				if ( ! $purchase_unit->getShipping() || ! $purchase_unit->getShipping()->getAddress() ) {
					$context->setShippingPreference( OrderApplicationContext::NO_SHIPPING );
				} else {
					$context->setShippingPreference( OrderApplicationContext::SET_PROVIDED_ADDRESS );
				}
			}
		}

		return $order;
	}

}