import {useState, useEffect, useRef, useCallback} from '@wordpress/element';
import {convertPayPalAddressToCart, extractFullName} from "@ppcp/utils";
import {isEmpty} from 'lodash';
import {getSetting} from '@woocommerce/settings';
import {
    versionCompare,
    DEFAULT_BILLING_ADDRESS,
    DEFAULT_SHIPPING_ADDRESS
} from "../../../utils";

const version = getSetting('ppcpGeneralData').blocksVersion

export const useProcessPayment = (
    {
        onSubmit,
        billingData,
        shippingData,
        onPaymentProcessing,
        responseTypes,
        activePaymentMethod,
        paymentMethodId
    }) => {
    const [paymentData, setPaymentData] = useState(null);
    const currentPaymentData = useRef(null);
    const currentBillingData = useRef(null);
    const currentShippingData = useRef(null);

    useEffect(() => {
        currentPaymentData.current = paymentData;
        currentBillingData.current = billingData;
        currentShippingData.current = shippingData;
    });

    useEffect(() => {
        if (!isEmpty(paymentData)) {
            onSubmit();
        }
    }, [paymentData, onSubmit]);

    const convertBillingData = useCallback((order) => {
        const {needsShipping} = currentShippingData.current;
        let address = {};
        if (!isEmpty(order?.payer?.address?.address_line_1)) {
            address = convertPayPalAddressToCart(order.payer.address);
        } else if (needsShipping && !isEmpty(order?.purchase_units?.[0]?.shipping)) {
            const shipping = order.purchase_units[0].shipping;
            address = convertPayPalAddressToCart(shipping.address);
        }
        if (order?.payer?.name) {
            address = {...address, ...extractName(order.payer.name)};
        }
        if (order?.payer?.email_address) {
            address = {...address, email: order.payer.email_address};
        }
        if (order?.payer?.phone?.phone_number?.national_number) {
            address = {...address, phone: order.payer.phone.phone_number.national_number};
        }
        return address;
    }, []);

    const extractName = useCallback((name) => {
        let first_name, last_name;
        if (Array.isArray(name)) {
            [first_name, last_name] = name;
        } else {
            ({given_name: first_name, surname: last_name} = name);
        }
        return {first_name, last_name};
    }, []);

    const convertShippingAddress = useCallback(order => {
        let address = {};
        if (order?.purchase_units?.[0]?.shipping) {
            const shipping = order.purchase_units[0].shipping;
            address = convertPayPalAddressToCart(shipping.address);
            if (shipping?.name?.full_name) {
                const name = extractFullName(shipping.name.full_name);
                address = {...address, ...extractName(name)};
            }
        }
        return address;
    }, []);

    useEffect(() => {
        if (activePaymentMethod === paymentMethodId) {
            const unsubscribe = onPaymentProcessing(() => {
                const billingData = currentBillingData.current;
                const shippingData = currentShippingData.current;
                const {shippingAddress, needsShipping} = shippingData;
                const paymentData = currentPaymentData.current;
                const response = {
                    meta: {
                        paymentMethodData: {
                            ppcp_paypal_order_id: paymentData.orderId,
                            ppcp_billing_token: paymentData.billingToken
                        },
                        ...(versionCompare(version, '9.5.0', '<') &&
                            {
                                billingData: {
                                    ...DEFAULT_BILLING_ADDRESS,
                                    ...billingData,
                                    ...convertBillingData(paymentData.order)
                                }
                            }),
                        billingAddress: {
                            ...DEFAULT_BILLING_ADDRESS,
                            ...billingData,
                            ...convertBillingData(paymentData.order)
                        }
                    }
                }
                if (needsShipping) {
                    if (versionCompare(version, '9.5.0', '<')) {
                        response.meta.shippingData = {
                            address: {
                                ...shippingAddress, ...convertShippingAddress(paymentData.order)
                            }
                        }
                    } else {
                        response.meta.shippingAddress = {
                            ...DEFAULT_SHIPPING_ADDRESS,
                            ...shippingAddress,
                            ...convertShippingAddress(paymentData.order)
                        }
                    }
                }
                return {type: responseTypes.SUCCESS, ...response};
            });

            return () => unsubscribe();
        }
    }, [onPaymentProcessing, activePaymentMethod]);

    return {paymentData, setPaymentData};
}