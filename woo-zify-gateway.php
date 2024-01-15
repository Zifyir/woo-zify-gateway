<?php
/*
Plugin Name: Gateway for Zify on WooCommerce
Version: 0.0.2
Description:  افزونه درگاه پرداخت زیفای برای ووکامرس
Plugin URI: https://www.zify.ir/
Author: Hadi Hosseini
Author URI: https://hosseini-dev.ir
License: GPLv3 or later
*/
if(!defined('ABSPATH')) exit;

define('GZFDIR', plugin_dir_path( __FILE__ ));
define('GZFDU', plugin_dir_url( __FILE__ ));

function loadZifyWooGateway(){

	/* Add Zify Gateway Method */
	add_filter('woocommerce_payment_gateways', 'zifyActiveGateway');
	function zifyActiveGateway($methods){
		$methods[] = 'zifyWoo';
		return $methods;
	}
	/* Add Iranian Currencies Woocommerce */
	add_filter('woocommerce_currencies', 'irCurrencyForZify');
	function irCurrencyForZify($currencies){
		$currencies['IRR'] = __('ریال', 'woocommerce');
		$currencies['IRT'] = __('تومان', 'woocommerce');
		$currencies['IRHR'] = __('هزار ریال', 'woocommerce');
		$currencies['IRHT'] = __('هزار تومان', 'woocommerce');
		return $currencies;
	}
	/* Add Iranian Currencies Symbols Woocommerce */
	add_filter('woocommerce_currency_symbol', 'irCurrencySymbolForZify', 10, 2);
	function irCurrencySymbolForZify($currency_symbol, $currency){
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
	require_once( GZFDIR . 'class-wc-gateway-zify.php' );
}
add_action('plugins_loaded', 'loadZifyWooGateway', 0);
