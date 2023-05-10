<?php

namespace PaymentPlugins\WooCommerce\PPCP\Factories;

use PaymentPlugins\PayPalSDK\OrderApplicationContext;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;

class ApplicationContextFactory extends AbstractFactory {

	private $settings;

	public function __construct( AdvancedSettings $settings, ...$args ) {
		$this->settings = $settings;
		parent::__construct( ...$args );
	}

	/**
	 * @param false $needs_shipping
	 *
	 * @return \PaymentPlugins\PayPalSDK\OrderApplicationContext
	 */
	public function get( $needs_shipping = false, $set_provided = false ) {
		$context = new OrderApplicationContext();
		if ( $needs_shipping ) {
			if ( $set_provided ) {
				$context->setShippingPreference( OrderApplicationContext::SET_PROVIDED_ADDRESS );
			} else {
				$context->setShippingPreference( OrderApplicationContext::GET_FROM_FILE );
			}
		} else {
			$context->setShippingPreference( OrderApplicationContext::NO_SHIPPING );
		}
		$context->setBrandName( $this->settings->get_option( 'display_name' ) );

		return $context;
	}

}