<?php


namespace PaymentPlugins\WooCommerce\PPCP;


use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;

class PaymentResult {

	private $success;

	private $order;

	private $error_message;

	private $payment_method;

	/**
	 * @var Order
	 */
	public $paypal_order;

	/**
	 * PaymentResult constructor.
	 *
	 * @param \PaymentPlugins\PayPalSDK\Order $paypal_order
	 * @param \WC_Order                       $order
	 * @param AbstractGateway                 $payment_method
	 * @param string                          $error_message
	 */
	public function __construct( $paypal_order, \WC_Order $order, AbstractGateway $payment_method = null, $error_message = '' ) {
		if ( is_wp_error( $paypal_order ) ) {
			$this->success       = false;
			$this->error_message = $paypal_order->get_error_message();
		} elseif ( $paypal_order === false ) {
			$this->success       = false;
			$this->error_message = $error_message;
		} else {
			$this->success      = true;
			$this->paypal_order = $paypal_order;
		}
		$this->order          = $order;
		$this->payment_method = $payment_method;
	}

	public function success() {
		return $this->success;
	}

	public function is_captured() {
		return $this->paypal_order->intent === 'CAPTURE';
	}

	/**
	 * Returns the ID of the capture
	 *
	 * @return string
	 */
	public function get_capture_id() {
		return $this->paypal_order->purchase_units[0]->payments->captures[0]->id;
	}

	/**
	 * @return \PaymentPlugins\PayPalSDK\Capture
	 */
	public function get_capture() {
		return $this->paypal_order->purchase_units[0]->payments->captures[0];
	}

	/**
	 * Returns the ID of the authorization
	 *
	 * @return string
	 */
	public function get_authorization_id() {
		return $this->paypal_order->purchase_units[0]->payments->authorizations[0]->id;
	}

	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * @since 1.0.24
	 * @return \PaymentPlugins\PayPalSDK\Order
	 */
	public function get_paypal_order() {
		return $this->paypal_order;
	}

	public function set_error_message( $message ) {
		$this->error_message = $message;
	}

	public function get_failure_response() {
		return apply_filters( 'wc_ppcp_process_payment_error_response', [
			'result'           => 'failure',
			'redirect'         => '',
			'ppcpErrorMessage' => $this->get_error_message()
		], $this->order, $this->payment_method );
	}

	public function get_success_response() {
		return apply_filters( 'wc_ppcp_process_payment_success_response', [
			'result'   => 'success',
			'redirect' => $this->payment_method->get_return_url( $this->order )
		], $this->order, $this->payment_method );
	}

}