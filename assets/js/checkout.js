const zify_settings = window.wc.wcSettings.getSetting( 'zify_gateway_data', {} );
const zify_label = window.wp.htmlEntities.decodeEntities( zify_settings.title ) || window.wp.i18n.__( 'پرداخت از طریق زیفای', 'woocommerce' );
const zify_Content = () => {
    return window.wp.htmlEntities.decodeEntities( zify_settings.description || '' );
};
const Zify_Block_Gateway = {
    name: 'zifyWoo',
    label: zify_label,
    content: Object( window.wp.element.createElement )( zify_Content, null ),
    edit: Object( window.wp.element.createElement )( zify_Content, null ),
    canMakePayment: () => true,
    ariaLabel: zify_label,
    supports: {
        features: zify_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Zify_Block_Gateway );