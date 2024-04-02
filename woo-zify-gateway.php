<?php
/*
Plugin Name: Zify Gateway
Version: 1.0.0
Description:  افزونه درگاه پرداخت زیفای برای ووکامرس
Plugin URI: https://www.zify.ir/
Author: Hadi Hosseini
Author URI: https://hosseini-dev.ir
License: GPLv3 or later
*/
if (!defined('ABSPATH'))
	exit;

define('GZFDIR', plugin_dir_path(__FILE__));
define('GZFDU', plugin_dir_url(__FILE__));

function loadZifyWooGateway()
{

	/* Add Zify Gateway Method */
	add_filter('woocommerce_payment_gateways', 'zifyActiveGateway');
	function zifyActiveGateway($methods)
	{
		$methods[] = 'zifyWoo';
		return $methods;
	}
	/* Add Iranian Currencies Woocommerce */
	add_filter('woocommerce_currencies', 'irCurrencyForZify');
	function irCurrencyForZify($currencies)
	{
		$currencies['IRR'] = __('ریال', 'woocommerce');
		$currencies['IRT'] = __('تومان', 'woocommerce');
		$currencies['IRHR'] = __('هزار ریال', 'woocommerce');
		$currencies['IRHT'] = __('هزار تومان', 'woocommerce');
		return $currencies;
	}
	/* Add Iranian Currencies Symbols Woocommerce */
	add_filter('woocommerce_currency_symbol', 'irCurrencySymbolForZify', 10, 2);
	function irCurrencySymbolForZify($currency_symbol, $currency)
	{
		switch ($currency) {
			case 'IRR':
				$currency_symbol = 'ریال';
				break;
			case 'IRT':
				$currency_symbol = 'تومان';
				break;
			case 'IRHR':
				$currency_symbol = 'هزار ریال';
				break;
			case 'IRHT':
				$currency_symbol = 'هزار تومان';
				break;
		}
		return $currency_symbol;
	}
	require_once (GZFDIR . 'class-wc-gateway-zify.php');
}
add_action('plugins_loaded', 'loadZifyWooGateway', 0);


/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
 */
function declare_zify_cart_checkout_blocks_compatibility()
{
	// Check if the required class exists
	if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		// Declare compatibility for 'cart_checkout_blocks'
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
	}
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_zify_cart_checkout_blocks_compatibility');


// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'zify_register_order_approval_payment_method_type');

/**
 * Custom function to register a payment method type

 */
function zify_register_order_approval_payment_method_type()
{
	// Check if the required class exists
	if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		return;
	}

	// Include the custom Blocks Checkout class
	require_once plugin_dir_path(__FILE__) . 'class-block.php';

	// Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
			$payment_method_registry->register(new Zify_Gateway_Blocks);
		}
	);
}