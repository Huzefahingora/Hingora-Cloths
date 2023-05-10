import {useEffect, useCallback, useRef} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {convertPayPalAddressToCart, extractShippingMethod} from "@ppcp/utils";
import {
    extractShippingRateParams,
    getRestPath,
    isAddressValid
} from '../../../utils';

export const usePayPalOptions = (
    {
        isExpress,
        paypal,
        vault,
        buttonStyles,
        shippingData,
        billing,
        setError,
        setPaymentData,
        onClick,
        onClose
    }) => {
    const currentShippingData = useRef(shippingData);
    const currentBilling = useRef(billing);
    const currentData = useRef({onClick, onClose, buttonState: true, actions: {}});
    useEffect(() => {
        currentShippingData.current = shippingData;
        currentBilling.current = billing;
        currentData.current = {...currentData.current, onClick, onClose};
        const {needsShipping, shippingAddress} = shippingData;
        const billingAddress = billing.billingData;
        // if address fields not valid, disable the buttons
        if (!isAddressValid(billingAddress) || (needsShipping && !isAddressValid(shippingAddress))) {
            disableButtons()
        } else {
            enableButtons();
        }
    });

    const disableButtons = useCallback(() => {
        Object.keys(currentData.current.actions).forEach(key => {
            currentData.current.actions[key].disable();
            currentData.current.buttonState = false;
        });
    }, []);

    const enableButtons = useCallback(() => {
        Object.keys(currentData.current.actions).forEach(key => {
            currentData.current.actions[key].enable();
            currentData.current.buttonState = true;
        });
    }, []);

    const getOptions = useCallback(fundingSource => {
        const {needsShipping, shippingAddress} = currentShippingData.current;
        const billingAddress = billing.billingData;
        const options = {
            fundingSource: fundingSource,
            style: getButtonStyle(fundingSource),
            onApprove,
            onError
        };
        if (isExpress) {
            options.onClick = () => currentData.current.onClick();
            options.onCancel = () => currentData.current.onClose()
        } else {
            options.onClick = () => {
                setError(null);
                // if address is not valid, show a notification that address data must be filled out first
                if (!isExpress && !currentData.current.buttonState) {
                    if (needsShipping) {
                        setError(__('Please fill out all billing and shipping fields before clicking PayPal.', 'pymntpl-paypal-woocommerce'));
                    } else {
                        setError(__('Please fill out all billing fields before clicking PayPal.', 'pymntpl-paypal-woocommerce'));
                    }
                }
            }
        }
        options.onInit = (data, actions) => {
            if (!isExpress) {
                currentData.current.buttonState = true;
                currentData.current.actions[fundingSource] = actions;
                if (!isAddressValid(billingAddress) || (needsShipping && !isAddressValid(shippingAddress))) {
                    disableButtons();
                }
            }
        }
        if (isCheckoutFlow()) {
            options.createOrder = createOrder;
            if (isExpress && needsShipping && fundingSource !== 'venmo') {
                options.onShippingChange = onShippingChange;
            }
        } else {
            options.createBillingAgreement = createBillingAgreement;
        }

        return options;
    }, [
        paypal,
        isExpress,
        onApprove,
        onError,
        createOrder,
        createBillingAgreement,
        onShippingChange,
        setError
    ]);

    const getButtonStyle = useCallback(fundingSource => {
        let styles = {};
        switch (fundingSource) {
            case paypal.FUNDING.PAYPAL:
                styles = buttonStyles.paypal;
                break;
            case paypal.FUNDING.PAYLATER:
                styles = buttonStyles.paylater;
                break;
            case paypal.FUNDING.CREDIT:
                const colors = ['black', 'white'];
                const color = colors.includes(buttonStyles.paylater.color) ? buttonStyles.paylater.color : 'darkblue';
                styles = {...buttonStyles.paylater, color};
                break;
            case paypal.FUNDING.CARD:
                styles = buttonStyles.card;
                break;
            case paypal.FUNDING.VENMO:
                styles = buttonStyles.venmo;
                break;
        }
        return styles;
    }, [paypal, buttonStyles]);

    const isCheckoutFlow = useCallback(() => !vault, [vault]);

    const onApprove = useCallback(async (data, actions) => {
        const paymentData = {
            orderId: data.orderID,
            billingToken: data.billingToken || '',
        }
        if (data.billingToken) {
            try {
                paymentData.billingTokenData = await handleBillingToken(data.billingToken);
            } catch (error) {
                setError(error);
            }
        }
        actions.order.get().then(response => {
            setPaymentData({...paymentData, order: response});
        }).catch(error => {
            setError(error);
        });
    }, [setError, handleBillingToken]);

    const onShippingChange = useCallback((data, actions) => {
        const shippingData = currentShippingData.current;
        const {setSelectedRates} = shippingData;
        const intermediateAddress = convertPayPalAddressToCart(data?.shipping_address || {}, true);
        const selectedShippingOption = data?.selected_shipping_option?.id || '';
        return apiFetch({
            method: 'POST',
            url: getRestPath('wc-ppcp/v1/cart/shipping'),
            data: {
                order_id: data.orderID,
                address: intermediateAddress,
                shipping_method: extractShippingMethod(selectedShippingOption),
                payment_method: 'ppcp'
            }
        }).then(response => {
            if (response.code) {
                return actions.reject();
            } else {
                return actions.resolve();
            }
        }).catch(error => {
            return actions.reject();
        }).finally(() => {
            if (selectedShippingOption) {
                setSelectedRates(...extractShippingRateParams(selectedShippingOption))
            }
        })
    }, []);

    const onError = useCallback(error => {
        if (error?.message?.indexOf('Window is closed') > -1) {
            return;
        }
        setError(error);
    }, [setError]);

    const createOrder = useCallback(async (data, actions) => {
        const {needsShipping} = currentShippingData.current;
        try {
            const response = await apiFetch({
                method: 'POST',
                url: getRestPath('wc-ppcp/v1/cart/order'),
                data: {payment_method: 'ppcp', address_provided: !isExpress && needsShipping}
            });
            return response;
        } catch (error) {
            console.log(error.message);
            throw error;
        }
    }, []);

    const createBillingAgreement = useCallback((data, actions) => {
        return apiFetch({
            method: 'POST',
            url: getRestPath('/wc-ppcp/v1/billing-agreement/token'),
            data: {
                context: 'checkout'
            }
        }).then(token => {
            return token;
        }).catch(error => setError(error));
    }, [setError]);

    const handleBillingToken = useCallback(async (billingToken) => {
        try {
            return apiFetch({
                method: 'GET',
                path: `/wc-ppcp/v1/billing-agreement/token/${billingToken}`
            });
        } catch (error) {
            throw error;
        }
    }, []);

    return {getOptions};
}