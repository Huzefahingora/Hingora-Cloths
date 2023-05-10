<?php


namespace PaymentPlugins\PayPalSDK;

use PaymentPlugins\PayPalSDK\V1\Address;

/**
 * Class BillingAgreement
 * @package PaymentPlugins\PayPalSDK
 * @property string $id
 * @property string $state
 * @property string name
 * @property string $description
 * @property string $start_date
 * @property AgreementDetails $agreement_details
 * @property Payer $payer
 * @property Address $shipping_address
 *
 */
class BillingAgreement extends AbstractObject {

}