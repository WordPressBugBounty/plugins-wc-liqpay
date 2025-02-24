const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement } = wp.element;

const LiqpayPaymentBlock = {
    name: 'liqpay',
    label: window.liqpayLocalize.title,
    ariaLabel: window.liqpayLocalize.title,
    content: createElement('p', {}, window.liqpayLocalize.description),
    edit: createElement('div', {}, window.liqpayLocalize.not_edit),
    canMakePayment: () => true,
    placeOrderButtonLabel: window.liqpayLocalize.description,
    onClick: async (event, { emitResponse }) => {
        try {
            emitResponse.success();
        } catch (error) {
            emitResponse.fail();
        }
    },
};

registerPaymentMethod(LiqpayPaymentBlock);