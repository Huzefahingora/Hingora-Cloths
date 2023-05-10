<?php

namespace PaymentPlugins\PPCP\WooFunnels\Upsell;

use PaymentPlugins\PPCP\WooFunnels\Upsell\PaymentGateways\PayPal;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Main;
use PaymentPlugins\WooCommerce\PPCP\Rest\RestController;

class PaymentGatewaysController {

	private $registry;

	public function __construct( PaymentGatewaysRegistry $registry ) {
		$this->registry = $registry;
		add_action( 'init', [ $this->registry, 'initialize' ] );
		add_filter( 'woocommerce_ppcp_woofunnels_gateways_registration', [ $this, 'register_gateways' ], 10, 2 );
		add_filter( 'wfocu_wc_get_supported_gateways', [ $this, 'get_supported_gateways' ] );
		add_filter( 'wfocu_subscriptions_get_supported_gateways', [ $this, 'get_subscription_gateways' ] );
		add_filter( 'wfocu_gateways_paypal_support_non_reference_trans', [ $this, 'add_no_reference_txn_support' ] );
		add_action( 'wfocu_footer_before_print_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'wfocu_localized_data', [ $this, 'add_script_data' ] );
	}

	public function get_supported_gateways( $gateways ) {
		return array_merge( $gateways, $this->get_payment_gateways() );
	}

	public function get_subscription_gateways( $gateways ) {
		return array_merge( $gateways, array_keys( $this->get_payment_gateways() ) );
	}

	private function get_payment_gateways() {
		return [
			'ppcp' => 'PaymentPlugins\PPCP\WooFunnels\Upsell\PaymentGateways\PayPal'
		];
	}

	private function is_supported_gateway( $id ) {
		return in_array( $id, array_keys( $this->get_payment_gateways() ) );
	}

	public function register_gateways( PaymentGatewaysRegistry $registry, $container ) {
		$registry->register( $container->get( PayPal::class ) );
	}

	private function get_payment_method_script_handles() {
		$handles = [];
		foreach ( $this->registry->get_registered_integrations() as $integration ) {
			$handles = array_merge( $handles, $integration->get_payment_method_script_handles() );
		}

		return $handles;
	}

	public function add_script_data( $data ) {
		$settings               = [
			'generalData' => Main::container()->get( RestController::class )->add_asset_data( [] )
		];
		$data['wcPPCPSettings'] = $settings;

		return $data;
	}

	public function enqueue_scripts() {
		if ( ! \WFOCU_Core()->public->if_is_offer() || WFOCU_Core()->public->if_is_preview() ) {
			return true;
		}
		$order = WFOCU_Core()->data->get_current_order();
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$payment_method    = $order->get_payment_method();
		$billing_agreement = $order->get_meta( Constants::BILLING_AGREEMENT_ID );
		if ( ! $billing_agreement && $this->is_supported_gateway( $payment_method ) ) {
			global $wp_scripts;
			$handles = $this->get_payment_method_script_handles();
			$wp_scripts->do_items( $handles );
		}
	}

	public function add_no_reference_txn_support( $ids ) {
		$ids[] = 'ppcp';

		return $ids;
	}

}