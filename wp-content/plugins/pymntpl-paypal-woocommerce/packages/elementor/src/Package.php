<?php

namespace PaymentPlugins\PPCP\Elementor;

use PaymentPlugins\PPCP\Elementor\Widget\CartPaymentButtonsWidget;
use PaymentPlugins\PPCP\Elementor\Widget\ProductPayLaterMessageWidget;
use PaymentPlugins\PPCP\Elementor\Widget\ProductPaymentButtonsWidget;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\Config;

class Package extends \PaymentPlugins\WooCommerce\PPCP\Package\AbstractPackage {

	public $id = 'ppcp_elementor';

	public function is_active() {
		return did_action( 'elementor/loaded' );
	}

	public function initialize() {
		$this->container->get( WidgetController::class )->initialize( $this->container, [
			$this->container->get( CartPaymentButtonsWidget::class ),
			$this->container->get( ProductPaymentButtonsWidget::class ),
			$this->container->get( ProductPayLaterMessageWidget::class )
		] );
	}

	public function register_dependencies() {
		$this->container->register( WidgetController::class, function ( $container ) {
			return new WidgetController( $container->get( 'elementorWidgetAssets' ) );
		} );
		$this->container->register( 'elementorWidgetAssets', function ( $container ) {
			return new AssetsApi( new Config( $this->version, dirname( __FILE__ ) ) );
		} );
		$this->container->register( CartPaymentButtonsWidget::class, function ( $container ) {
			return new CartPaymentButtonsWidget();
		} );
		$this->container->register( ProductPaymentButtonsWidget::class, function ( $container ) {
			return new ProductPaymentButtonsWidget();
		} );
		$this->container->register( ProductPayLaterMessageWidget::class, function ( $container ) {
			return new ProductPayLaterMessageWidget();
		} );
	}

}