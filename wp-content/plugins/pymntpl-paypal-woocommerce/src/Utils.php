<?php


namespace PaymentPlugins\WooCommerce\PPCP;


use PaymentPlugins\PayPalSDK\Address;
use PaymentPlugins\WooCommerce\PPCP\Utilities\NumberUtil;

class Utils {

	/**
	 * @param int $len
	 *
	 * @return string
	 */
	public static function random_string( $len = 64 ) {
		$chars  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$max    = strlen( $chars ) - 1;
		$string = '';
		for ( $i = 0; $i < $len; $i ++ ) {
			$string .= $chars[ wp_rand( 0, $max ) ];
		}

		return $string;
	}

	public static function is_order_review() {
		return ! empty( $_GET['_ppcp_order_review'] );
	}

	/**
	 * Validates the address for the given country
	 *
	 * @param $address
	 * @param $country
	 */
	public static function is_valid_address( $address, $type = 'billing' ) {
		$type .= '_';
		if ( $address instanceof Address ) {
			$mappings = self::get_address_mappings( true );
			$country  = $address->getCountryCode();
		} else {
			$args     = self::get_address_mappings();
			$mappings = array_combine( \array_keys( $args ), array_values( array_keys( $args ) ) );
			$country  = $address['country'];
		}
		$fields = WC()->countries->get_address_fields( $country, $type );
		/**
		 * Country must be required, regardless of site settings. Some sites set billing country to
		 * false for example which can allow this validation to pass. We set country required to true
		 * to avoid this.
		 */
		$fields[ $type . 'country' ]['required'] = true;

		foreach ( $mappings as $key => $key2 ) {
			$value = isset( $address[ $key ] ) ? $address[ $key ] : '';
			$key2  = $type . $key2;
			if ( isset( $fields[ $key2 ] ) && $fields[ $key2 ]['required'] ) {
				if ( ! \is_string( $value ) || ! strlen( $value ) ) {
					return false;
				}
				if ( $key2 === $type . 'country' ) {
					// make sure the country value maps to an actual country
					$countries = WC()->countries->get_countries();
					if ( ! isset( $countries[ $value ] ) ) {
						return false;
					}
				}
			}
		}

		return true;
	}

	public static function get_address_mappings( $reverse = false ) {
		if ( $reverse ) {
			return array_flip( Constants::ADDRESS_MAPPINGS );
		}

		return Constants::ADDRESS_MAPPINGS;
	}

	/**
	 * @param \WC_Customer $customer
	 * @param string       $prefix
	 *
	 * @return string
	 */
	public static function get_name_from_customer( $customer, $prefix = 'billing' ) {
		return sprintf( '%s %s', $customer->{"get_{$prefix}_first_name"}(), $customer->{"get_{$prefix}_last_name"}() );
	}

	public static function get_name_from_order( $order ) {
		return sprintf( '%s %s', $order->get_shipping_first_name(), $order->get_shipping_last_name() );
	}

	/**
	 * @param \WC_Product $product
	 *
	 * @return string[]
	 */
	public static function get_product_data( $product ) {
		return [
			'id'            => $product->get_id(),
			'needsShipping' => $product->needs_shipping(),
			'total'         => NumberUtil::round( $product->get_price() ),
			'price'         => NumberUtil::round( wc_get_price_to_display( $product ) ),
			'currency'      => get_woocommerce_currency()
		];
	}

	/**
	 * @param \WC_Cart $cart
	 *
	 * @return array
	 */
	public static function get_cart_data( $cart ) {
		return [
			'total'         => NumberUtil::round( $cart->total, 2 ),
			'needsShipping' => $cart->needs_shipping()
		];
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
	public static function get_order_data( $order ) {
		return [
			'order_id'  => $order->get_id(),
			'order_key' => $order->get_order_key()
		];
	}

	public static function get_order_from_query_vars() {
		global $wp;
		$order = null;
		if ( isset( $wp->query_vars['order-pay'] ) ) {
			$order = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
		}

		return $order;
	}

	public static function parse_shipping_option( $id ) {
		preg_match( Constants::SHIPPING_OPTION_REGEX, $id, $matches );
		if ( $matches && isset( $matches[1], $matches[2] ) ) {
			return [ $matches[1], $matches[2] ];
		}

		return null;
	}

}