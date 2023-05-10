<?php


namespace PaymentPlugins\PPCP\Blocks\Payments\Gateways;

/**
 * Class PayPalGateway
 * @package PaymentPlugins\PPCP\Blocks\Payments\Gateways
 */
class PayPalGateway extends AbstractGateway {

	protected $name = 'ppcp';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-ppcp-blocks-commons', 'build/blocks-commons.js' );
		$this->assets_api->register_script( 'wc-ppcp-blocks-paypal', 'build/paypal.js', [ 'wc-ppcp-blocks-commons' ] );

		return [ 'wc-ppcp-blocks-paypal' ];
	}

	public function get_payment_method_data() {
		$sources = [ 'paypal', 'paylater', 'card', 'venmo' ];
		$data    = [
			'payLaterEnabled'    => wc_string_to_bool( $this->get_setting( 'paylater_enabled', 'no' ) ),
			'cardEnabled'        => wc_string_to_bool( $this->get_setting( 'card_enabled', 'no' ) ),
			'venmoEnabled'       => wc_string_to_bool( $this->get_setting( 'venmo_enabled', 'no' ) ),
			'paypalSections'     => $this->get_setting( 'sections', [] ),
			'payLaterSections'   => $this->get_setting( 'paylater_sections', [] ),
			'creditCardSections' => $this->get_setting( 'credit_card_sections', [] ),
			'venmoSections'      => $this->get_setting( 'venmo_sections', [] ),
			'buttonOrder'        => $this->get_setting( 'buttons_order' ),
			'buttons'            => array_combine( $sources, array_map( function ( $source ) {
				if ( $source === 'venmo' ) {
					return [
						'layout' => 'vertical',
						'shape'  => $this->get_setting( 'button_shape' ),
						'height' => (int) $this->get_setting( 'button_height' )
					];
				}

				return [
					'layout' => 'vertical',
					'label'  => $this->get_setting( 'button_label' ),
					'shape'  => $this->get_setting( 'button_shape' ),
					'height' => (int) $this->get_setting( 'button_height' ),
					'color'  => $this->get_setting( "{$source}_button_color" )
				];
			}, $sources ) ),
		];
		/*if ( 'vault' == apply_filters( 'wc_ppcp_get_paypal_flow', 'checkout', 'checkout' ) ) {
			$data['scriptData']['intent'] = 'tokenize';
			$data['scriptData']['vault']  = 'true';
		}*/

		return array_merge( parent::get_payment_method_data(), $data );
	}

	public function get_payment_method_icons() {
		return [
			'id'  => 'paypal',
			'src' => $this->assets_api->assets_url( '../../assets/img/paypal_logo.svg' ),
			'alt' => 'PayPal'
		];
	}
}