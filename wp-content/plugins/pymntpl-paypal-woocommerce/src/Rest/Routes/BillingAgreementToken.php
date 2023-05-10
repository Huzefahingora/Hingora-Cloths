<?php


namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;


use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\WPPayPalClient;

class BillingAgreementToken extends AbstractRoute {

	private $client;

	private $logger;

	public function __construct( WPPayPalClient $client, Logger $logger ) {
		$this->client = $client;
		$this->logger = $logger;
	}

	public function get_path() {
		return 'billing-agreement/token/?(?P<id>[\w-]+)?';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle_request' ]
			],
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'handle_request' ]
			]
		];
	}

	public function handle_post_request( \WP_REST_Request $request ) {
		// create the token
		$customer = WC()->customer;
		if ( $request['context'] === 'order_pay' ) {
			$needs_shipping = false;
		} else {
			$needs_shipping = WC()->cart->needs_shipping();
		}
		/**
		 * @var AbstractGateway $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];
		$params         = [
			'description' => $payment_method->get_option( 'billing_agreement_description' ),
			'payer'       => [
				'payment_method' => 'PAYPAL'
			],
			'plan'        => [
				'type'                 => 'MERCHANT_INITIATED_BILLING',
				'merchant_preferences' => [
					'cancel_url'                 => 'https://www.paypal.com/checkoutnow/error',
					'return_url'                 => 'https://www.paypal.com/checkoutnow/error',
					'notify_url'                 => 'https://www.paypal.com/checkoutnow/error',
					'skip_shipping_address'      => ! $needs_shipping,
					'immutable_shipping_address' => false
				]
			]
		];
		if ( $needs_shipping ) {
			$params['shipping_address'] = [
				'line1'          => $customer->get_shipping_address_1(),
				'line2'          => $customer->get_shipping_address_2(),
				'city'           => $customer->get_shipping_city(),
				'state'          => $customer->get_shipping_state(),
				'postal_code'    => $customer->get_shipping_postcode(),
				'country_code'   => $customer->get_shipping_country(),
				'recipient_name' => sprintf( '%s %s', $customer->get_shipping_first_name(), $customer->get_shipping_last_name() )
			];
			// get address fields for country and if any are empty, unset address
			$fields = WC()->countries->get_address_fields( $customer->get_shipping_country(), '' );
			foreach ( $params['shipping_address'] as $key => $value ) {
				$wc_key = '';
				switch ( $key ) {
					case 'line1':
						$wc_key = 'address_1';
						break;
					case 'line2':
						$wc_key = 'address_2';
						break;
					case 'city':
						$wc_key = 'city';
						break;
					case 'state':
						$wc_key = 'state';
						break;
					case 'postal_code':
						$wc_key = 'postcode';
						break;
					case 'country_code':
						$wc_key = 'country';
						break;
					case 'recipient_name':
						if ( isset( $fields['first_name']['required'] ) && $fields['first_name']['required'] ) {
							if ( empty( $value ) ) {
								unset( $params['shipping_address'] );
								break;
							}
						}
						if ( isset( $fields['last_name']['required'] ) && $fields['last_name']['required'] ) {
							if ( empty( $value ) ) {
								unset( $params['shipping_address'] );
								break;
							}
						}
						break;
				}
				if ( $wc_key && isset( $fields[ $wc_key ]['required'] ) ) {
					if ( $fields[ $wc_key ]['required'] ) {
						if ( empty( $value ) ) {
							unset( $params['shipping_address'] );
							break;
						}
					} else {
						if ( empty( $value ) ) {
							unset( $params['shipping_address'][ $key ] );
						}
					}
				}
			}
		}

		$token = $this->client->billingAgreementTokens->create( $params );

		if ( ! is_wp_error( $token ) ) {
			return $token->token_id;
		} else {
			/**
			 * @var \WP_Error $token
			 */
			$data = $token->get_error_data();
			if ( isset( $data['error']['details'][0]['name'] ) && $data['error']['details'][0]['name'] === 'REFUSED_MARK_REF_TXN_NOT_ENABLED' ) {
				$msg   = __( 'This merchant account is not permitted to create Merchant Initiated Billing Agreements. Please contact PayPal support and request reference transaction access.', 'pymntpl-paypal-woocommerce' );
				$token = new \WP_Error( $token->get_error_code(), $msg, $token->get_error_data() );
			}

			return $token;
		}
	}

	public function handle_get_request( \WP_REST_Request $request ) {
		$id = $request['id'];

		return $this->client->billingAgreementTokens->retrieve( $id );
	}

}