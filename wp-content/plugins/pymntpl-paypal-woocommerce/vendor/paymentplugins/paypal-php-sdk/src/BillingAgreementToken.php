<?php


namespace PaymentPlugins\PayPalSDK;

use PaymentPlugins\PayPalSDK\V1\Address;

/**
 * Class BillingAgreementToken
 * @package PaymentPlugins\PayPalSDK
 *
 * @property string $description
 * @property string $token_id
 * @property string $token_status
 * @property string $experience_id
 * @property boolean $skip_shipping_address
 * @property boolean $immutable_shipping_address
 * @property string $external_selected_funding_instrument_type
 * @property array $accepted_legal_country_codes
 * @property Address $shipping_address
 * @property object $redirect_urls
 * @property array $plan_unit_list
 * @property PayerInfo $payer_info
 * @property Payee $owner
 * @property Collection $links
 */
class BillingAgreementToken extends AbstractObject {

}