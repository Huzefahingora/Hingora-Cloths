<?php

namespace PaymentPlugins\PPCP\WooFunnels;

use PaymentPlugins\PayPalSDK\PayPalClient;
use PaymentPlugins\PPCP\WooFunnels\Checkout\FieldMappings;
use PaymentPlugins\PPCP\WooFunnels\Upsell\PaymentGatewaysController;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\Cache\CacheHandler;
use PaymentPlugins\WooCommerce\PPCP\Config;
use PaymentPlugins\WooCommerce\PPCP\Package\AbstractPackage;
use PaymentPlugins\WooCommerce\PPCP\PaymentHandler;
use PaymentPlugins\PPCP\WooFunnels\Upsell\PaymentGateways\PayPal;

class Package extends AbstractPackage {

	public $id = 'woofunnels';

	const ASSETS = 'woofunnelsAssets';

	public function initialize() {
		if ( $this->is_upsell_active() ) {
			$this->container->get( Upsell\WooFunnelsIntegration::class );
			$this->container->get( PaymentGatewaysController::class );
		}
		if ( $this->is_checkout_active() ) {
			$this->container->get( Checkout\ExpressIntegration::class );
			$this->container->get( FieldMappings::class );
		}
	}

	public function register_dependencies() {
		if ( $this->is_upsell_active() ) {
			$this->register_upsell();
		}
		if ( $this->is_checkout_active() ) {
			$this->register_checkout();
		}
		$this->container->register( self::ASSETS, function ( $container ) {
			return new AssetsApi( new Config( $this->version, dirname( __FILE__ ) ) );
		} );
	}

	private function register_upsell() {
		$this->container->register( Upsell\WooFunnelsIntegration::class, function ( $container ) {
			return new Upsell\WooFunnelsIntegration(
				true,
				$container->get( PayPalClient::class ),
				$container->get( self::ASSETS )
			);
		} );
		$this->container->register( Upsell\PaymentGatewaysRegistry::class, function ( $container ) {
			return new Upsell\PaymentGatewaysRegistry( $container );
		} );
		$this->container->register( PaymentGatewaysController::class, function ( $container ) {
			return new PaymentGatewaysController( $container->get( Upsell\PaymentGatewaysRegistry::class ) );
		} );
		$this->container->register( PayPal::class, function ( $container ) {
			return new PayPal( $container->get( self::ASSETS ), $container->get( PaymentHandler::class ), WFOCU_Core()->log );
		} );
	}

	private function register_checkout() {
		$this->container->register( Checkout\ExpressIntegration::class, function ( $container ) {
			return new Checkout\ExpressIntegration( $container->get( self::ASSETS ) );
		} );
		$this->container->register( FieldMappings::class, function ( $container ) {
			return new FieldMappings( $container->get( CacheHandler::class ) );
		} );
	}

	public function is_active() {
		return $this->is_upsell_active() || $this->is_checkout_active();
	}

	public function is_upsell_active() {
		return function_exists( 'WFOCU_Core' );
	}

	public function is_checkout_active() {
		return class_exists( 'WFACP_Core' );
	}

}