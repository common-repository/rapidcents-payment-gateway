import { registerPaymentMethod } from '@woocommerce/blocks-registry';

const wcRapidCentsPaymentMethod = {
    name: 'custom_payment_method',
    label: 'Custom Payment Gateway',
    canMakePayment: () => true,  // logic to determine if the gateway is available
    content: <div>Enter custom payment details here</div>,
    edit: <div>Editing Custom Payment Gateway</div>,
    paymentMethodId: 'rapidcents', 
    savePaymentInformation: async () => {
        // Handle saving payment info if necessary
        return {};
    },
    validatePaymentMethod: async () => {
        // Validate before proceeding to order
        return true;
    }
};

registerPaymentMethod(wcRapidCentsPaymentMethod);