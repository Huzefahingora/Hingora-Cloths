<?php

namespace PaymentPlugins\PPCP\Elementor;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetDataApi;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;

class WidgetController {

	private $widgets;

	public function __construct() {
	}

	public function initialize( $container, $widgets ) {
		$this->widgets = $widgets;
		$this->initialize_widgets( $container );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	public function register_scripts() {

	}

	private function initialize_widgets( $container ) {
		foreach ( $this->widgets as $widget ) {
		}
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_mgr
	 *
	 * @return void
	 */
	public function register_widgets( $widgets_mgr ) {
		foreach ( $this->widgets as $widget ) {
			$widgets_mgr->register( $widget );
		}
	}

}