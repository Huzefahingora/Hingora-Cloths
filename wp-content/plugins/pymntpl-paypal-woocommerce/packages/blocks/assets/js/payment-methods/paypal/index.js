import {useState, useCallback} from '@wordpress/element';
import {registerPaymentMethod, registerExpressPaymentMethod} from '@woocommerce/blocks-registry';
import {getSetting} from '@woocommerce/settings';
import {__, sprintf} from '@wordpress/i18n';
import {useBreakpointWidth, useLoadPayPalScript} from "../../hooks";
import {usePayPalOptions, usePayPalFundingSources, useProcessPayment, useValidateCheckout} from "./hooks";
import './styles.scss';
import {useProcessPaymentFailure} from "../../hooks";
import {isAddressValid, isUserAdmin} from "../../utils";

const getData = (key) => {
    const data = getSetting(key);
    return (key, defaultValue = null) => {
        if (!data.hasOwnProperty(key)) {
            data[key] = defaultValue;
        }
        return data[key];
    };
}

const data = getData('ppcp_data');
const generalData = getData('ppcpGeneralData');

const isExpressEnabled = () => {
    const sections = ['paypalSections', 'payLaterSections', 'creditCardSections', 'venmoSections'];
    for (const section of sections) {
        if (data(section, []).includes('express_checkout')) {
            return true;
        }
    }
    return false;
}

const ExpressPaymentMethod = (props) => {
    return <PayPalPaymentMethod
        context={'express_checkout'}
        isExpress={true}
        paymentMethodId='paymentplugins_ppcp_express'
        {...props}/>;
}

const PayPalPaymentMethod = (
    {
        isExpress = false,
        context,
        billing,
        shippingData,
        eventRegistration,
        emitResponse,
        onError,
        onClick,
        onClose,
        onSubmit,
        activePaymentMethod,
        paymentMethodId,
        ...props
    }) => {
    const [error, setError] = useState(false);
    const queryParams = getSetting('paypalQueryParams');
    const vault = queryParams.vault === 'true';
    const {billingData} = billing;
    const {
        onPaymentProcessing,
        onCheckoutAfterProcessingWithError,
        onCheckoutValidationBeforeProcessing
    } = eventRegistration;
    const {responseTypes, noticeContexts} = emitResponse;
    const [buttonsContainer, setButtonsContainer] = useState();

    useBreakpointWidth({width: 375, node: buttonsContainer});

    if (!isExpress) {
        onError = useCallback((error) => {
            setError(error?.message ? error.message : error);
        }, []);
    }

    const setButtonContainerRef = useCallback(el => {
        setButtonsContainer(el?.parentElement?.parentElement);
    }, []);

    const {paymentData, setPaymentData} = useProcessPayment({
        onSubmit,
        billingData,
        shippingData,
        onPaymentProcessing,
        responseTypes,
        activePaymentMethod,
        paymentMethodId
    });

    useProcessPaymentFailure({
        event: onCheckoutAfterProcessingWithError,
        responseTypes,
        messageContext: isExpress ? noticeContexts.EXPRESS_PAYMENTS : null,
        setPaymentData
    });

    useValidateCheckout({
        isExpress,
        onCheckoutValidationBeforeProcessing,
        paymentData
    })

    const paypal = useLoadPayPalScript(queryParams);

    const {getOptions} = usePayPalOptions({
        isExpress,
        paypal,
        vault,
        intent: queryParams.intent,
        buttonStyles: data('buttons'),
        billing,
        shippingData,
        eventRegistration,
        setError: onError,
        setPaymentData,
        onClick,
        onClose
    });
    const sources = usePayPalFundingSources({
        data,
        paypal,
        context,
        vault
    });
    const cancelPayment = e => {
        e.preventDefault();
        setPaymentData(null);
    }
    if (isExpress && paymentData) {
        const validAddress = isAddressValid(billingData);
        return (
            <div>
                {!validAddress &&
                    <p className='wc-ppcp-payment__notice'>{__('Please fill out all required fields and click Place Order.', 'pymntpl-paypal-woocommerce')}</p>}
                {isUserAdmin() && !validAddress &&
                    <p className='wc-ppcp-payment__notice'>{__('Admin notice: In order for customer billing address details to be auto-populated you ' +
                        'must contact PayPal Support and request this feature.', 'pymntpl-paypal-woocommerce')}</p>}
            </div>
        )
    }
    if (!isExpress && paymentData) {
        return (
            <>
                <div className={'wc-ppcp-order-review__message'}>
                    {__('Your PayPal payment method is ready to be processed. Please review your order details then click Place Order',
                        'pymntpl-paypal-woocommerce')}
                </div>
                <a href={'#'} onClick={cancelPayment} className={'wc-ppcp-cancel__payment'}>{__('Cancel', 'pymntpl-paypal-woocommerce')}</a>
            </>
        );
    }
    if (paypal && sources) {
        const Button = paypal.Buttons.driver("react", {React, ReactDOM});
        const BUTTONS = sources.map(source => {
            const options = getOptions(source);
            const button = paypal.Buttons(options);
            return button.isEligible() ? <Button key={source} {...options}/> : null;
        });
        return (
            <>
                {!isExpress && <ErrorMessage msg={error}/>}
                <div className='wc-ppcp-paypal__buttons' ref={setButtonContainerRef}>
                    {BUTTONS}
                </div>
            </>
        );
    }
    return null;
}

const PaymentMethodLabel = ({components, title, icons, id}) => {
    if (!Array.isArray(icons)) {
        icons = [icons];
    }
    const {PaymentMethodLabel: Label, PaymentMethodIcons} = components;
    return (
        <div className={`wc-ppcp-blocks-payment-method__label ${id}`}>
            <Label text={title}/>
            <PaymentMethodIcons icons={icons}/>
        </div>
    )
};

const ErrorMessage = ({msg}) => {
    if (msg) {
        return (
            <div className={'wc-ppcp-error__message'}>
                {msg}
            </div>
        )
    }
    return null;
}

if (isExpressEnabled()) {
    registerExpressPaymentMethod({
        name: 'paymentplugins_ppcp_express',
        canMakePayment: () => true,
        content: <ExpressPaymentMethod/>,
        edit: <ExpressPaymentMethod/>,
        supports: {
            features: data('features')
        }
    });
}

registerPaymentMethod({
    name: 'ppcp',
    label: <PaymentMethodLabel
        id='ppcp'
        title={data('title')}
        icons={data('icons')}/>,
    ariaLabel: 'PayPal',
    canMakePayment: () => true,
    content: <PayPalPaymentMethod context={'checkout'} paymentMethodId={'ppcp'}/>,
    edit: <PayPalPaymentMethod context={'checkout'} paymentMethodId={'ppcp'}/>,
    supports: {
        showSavedCards: false,
        showSaveOption: false,
        features: data('features')
    }
});



