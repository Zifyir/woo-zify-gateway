<?php
/*
Plugin Name: Gateway for Zify on WooCommerce
Version: 0.0.1
Description:  افزونه درگاه پرداخت زیفای برای ووکامرس
Plugin URI: https://www.zify.ir/
Author: Mahdi Sarani
Author URI: https://mahdisarani.ir
*/
if(!defined('ABSPATH')) exit;

define('WOO_GZFDIR', plugin_dir_path( __FILE__ ));
define('WOO_GZFDU', plugin_dir_url( __FILE__ ));

function load_zify_woo_gateway(){

	/* Add Zify Gateway Method */
	add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_zify_Gateway');
	function Woocommerce_Add_zify_Gateway($methods){
		$methods[] = 'WC_zify';
		return $methods;
	}
	/* Add Iranian Currencies Woocommerce */
	add_filter('woocommerce_currencies', 'add_IR_currency_For_Zify');
	function add_IR_currency_For_Zify($currencies){
		$currencies['IRR'] = __('ریال', 'woocommerce');
		$currencies['IRT'] = __('تومان', 'woocommerce');
		$currencies['IRHR'] = __('هزار ریال', 'woocommerce');
		$currencies['IRHT'] = __('هزار تومان', 'woocommerce');
		return $currencies;
	}
	/* Add Iranian Currencies Symbols Woocommerce */
	add_filter('woocommerce_currency_symbol', 'add_IR_currency_symbol_For_Zify', 10, 2);
	function add_IR_currency_symbol_For_Zify($currency_symbol, $currency){
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
	require_once( WOO_GZFDIR . 'class-wc-gateway-zify.php' );
}
add_action('plugins_loaded', 'load_zify_woo_gateway', 0);
