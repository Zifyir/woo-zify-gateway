<?php
if(!defined('ABSPATH'))exit;

if( class_exists('WC_Payment_Gateway') && !class_exists('zify_woo') ){
	class zify_woo extends WC_Payment_Gateway{
	    
        private $baseurl = 'https://zify.ir';
        private $zifyToken;
        private $success_massage;
        private $failed_massage;
        
		public function __construct(){
		    
			$this->id = 'zify_woo';
			$this->method_title = __('پرداخت از طریق درگاه زیفای', 'woocommerce');
			$this->method_description = __('تنظیمات درگاه پرداخت زیفای برای افزونه فروشگاه ساز ووکامرس', 'woocommerce');
			$this->icon = apply_filters('woo_zify_logo', WOO_GZFDU.'/assets/images/logo.png');
			$this->has_fields = false;
			$this->init_form_fields();
			$this->init_settings();
			
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];

			$this->zifyToken = $this->settings['zifyToken'];

			$this->success_massage = $this->settings['success_massage'];
			$this->failed_massage = $this->settings['failed_massage'];

			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>='))
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			else
				add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));

			add_action('woocommerce_receipt_' . $this->id . '', array($this, 'create_order'));
			add_action('woocommerce_api_' . strtolower(get_class($this)) . '', array($this, 'callback_after_pay'));

		}

		public function admin_options(){
			parent::admin_options();
		}

		public function init_form_fields(){
			$this->form_fields = apply_filters('zify_woo_Config', array(
					'base_confing' => array(
						'title' => __('تنظیمات پایه ای', 'woocommerce'),
						'type' => 'title',
						'description' => '',
					),
					'enabled' => array(
						'title' => __('فعالسازی/غیرفعالسازی', 'woocommerce'),
						'type' => 'checkbox',
						'label' => __('فعالسازی درگاه زیفای', 'woocommerce'),
						'description' => __('برای فعالسازی درگاه پرداخت زیفای باید چک باکس را تیک بزنید', 'woocommerce'),
						'default' => 'yes',
						'desc_tip' => true,
					),
					'title' => array(
						'title' => __('عنوان درگاه', 'woocommerce'),
						'type' => 'text',
						'description' => __('عنوان درگاه که در طی خرید به مشتری نمایش داده میشود', 'woocommerce'),
						'default' => __('پرداخت از طریق زیفای', 'woocommerce'),
						'desc_tip' => true,
					),
					'description' => array(
						'title' => __('توضیحات درگاه', 'woocommerce'),
						'type' => 'text',
						'desc_tip' => true,
						'description' => __('توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'woocommerce'),
						'default' => __('پرداخت به وسیله کلیه کارت های عضو شتاب از طریق درگاه زیفای', 'woocommerce')
					),
					'account_confing' => array(
						'title' => __('تنظیمات حساب زیفای', 'woocommerce'),
						'type' => 'title',
						'description' => '',
					),
					'zifyToken' => array(
						'title' => __('توکن', 'woocommerce'),
						'type' => 'text',
						'description' => __('توکن درگاه زیفای', 'woocommerce'),
						'default' => '',
						'desc_tip' => true
					),
					'payment_confing' => array(
						'title' => __('تنظیمات عملیات پرداخت', 'woocommerce'),
						'type' => 'title',
						'description' => '',
					),
					'success_massage' => array(
						'title' => __('پیام پرداخت موفق', 'woocommerce'),
						'type' => 'textarea',
						'description' => __('متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (توکن) زیفای استفاده نمایید .', 'woocommerce'),
						'default' => __('با تشکر از شما . سفارش شما با موفقیت پرداخت شد .', 'woocommerce'),
					),
					'failed_massage' => array(
						'title' => __('پیام پرداخت ناموفق', 'woocommerce'),
						'type' => 'textarea',
						'description' => __('متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید .', 'woocommerce'),
						'default' => __('پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'woocommerce'),
					)
				)
			);
		}

		public function process_payment($order_id){
			$order = new WC_Order($order_id);
			return array(
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url(true)
			);
		}

		function isJson($string) {
			json_decode($string);
			return (json_last_error() == JSON_ERROR_NONE);
		}
		
		public function create_order($order_id){
			
			$zifypayCode = get_post_meta($order_id, '_zify_payCode', true);
			if( $zifypayCode ){
				wp_redirect( sprintf('%s/order/accept/%s', $this->baseurl, $zifypayCode )) ;
				exit;
			}
			global $woocommerce;
			$woocommerce->session->order_id_zify = $order_id;
			
			$order = new WC_Order($order_id);
			
			$currency = $order->get_currency();
			$currency = apply_filters('zify_woo_Currency', $currency, $order_id);
			
			$form = '<form action="" method="POST" class="zify-checkout-form" id="zify-checkout-form">
				 <input type="submit" name="zify_submit" class="button alt" id="zify-payment-button" value="' . esc_html(__('پرداخت', 'woocommerce')) . '"/>
				 <a class="button cancel" href="' . esc_url(wc_get_checkout_url()) . '">' . esc_html(__('بازگشت', 'woocommerce')) . '</a>
			 	</form><br/>';
			$form = apply_filters('zify_woo_Form', $form, $order_id, $woocommerce);

			do_action('zify_woo_Gateway_Before_Form', $order_id, $woocommerce);
			echo $form;
			do_action('zify_woo_Gateway_After_Form', $order_id, $woocommerce);

			$CallbackUrl = add_query_arg('wc_order', $order_id, WC()->api_request_url('zify_woo'));

			$products = array();
			$order_items = $order->get_items();
			foreach ((array)$order_items as $product) {
				$products[] = $product['name'].'('.$product['qty'].')';
			}
			$products = implode(' - ', $products);

			$Description = 'خرید به شماره سفارش : ' . $order->get_order_number() . ' | خریدار : ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . ' | محصولات : ' . $products;
			
			$billing_address = $order->get_address('billing');
			
			// Shipping
			$shipping_total  = $this->zify_check_currency( $order->get_shipping_total(), $currency );
			$shipping_tax    = $this->zify_check_currency( $order->get_shipping_tax(), $currency );
			
			// Discount
			$total_discount  = $this->zify_check_currency( $order->get_total_discount(), $currency );
			$discount_tax    = $this->zify_check_currency( $order->get_discount_tax(), $currency );
			
			// Tax
			$cart_tax        = $this->zify_check_currency( $order->get_cart_tax(), $currency );
			$total_tax       = $this->zify_check_currency( $order->get_total_tax(), $currency );
			
			// Total
			$amount          = $this->zify_check_currency( $order->get_total(), $currency );
			
			$payer = array(
				'first_name' => $billing_address['first_name'],
				'last_name'  => $billing_address['last_name'],
				'state'      => $billing_address['state'],
				'city'       => $billing_address['city'],
				'address_1'  => $billing_address['address_1'],
				'address_2'  => $billing_address['address_2']
			);

			$phone_number = $this->zify_check_mobile_payer($billing_address['phone']);

			if($phone_number){
				$payer['phone'] = $phone_number;
			}elseif($phone_number == false && isset($billing_address['email'])){
				$payer['email'] = $billing_address['email'];
			}else{
				$payer['phone'] = null;
				$payer['email'] = null;
			}
			$order_products = array();
			
			foreach ((array)$order_items as $product) {
    			$product_id = $product['product_id'];
    			// Check if the product has variations
    			if (wc_get_product($product_id)->is_type('variable')) {
        			$variation_ids = $product['variation_id'];

        			// If multiple variations exist for the product, loop through them
        			if (is_array($variation_ids)) {
            			foreach ($variation_ids as $variation_id) {
                			$variation = wc_get_product($variation_id);
                			if ($variation && $variation->get_parent_id() === $product_id) {
                    			$code = $variation_id;
                    			break; // Stop the loop after finding the selected variation
                			}
            			}
        			} else {
            			// Only one variation exists for the product
            			$code = $variation_ids;
        			}
    			} else {
        			// No variations, use the product ID
        			$code = $product_id;
    			}
    			$price = ($product['subtotal'] / $product['quantity']);
    			$order_products[] = array(
        			'code'          => $code,
        			'title'         => $product['name'],
        			'amount'        => $this->zify_check_currency($price, $currency),
        			'sellQuantity'  => $product['quantity'],
        			'description'   => $product['description'],
        			'unlimited'     => true
    			);
			}
			
			$data = array(
				'payer'          => $payer,
				'products'       => $order_products,
				"shipping_total" => $shipping_total,
				"shipping_tax"   => $shipping_tax,
				"off_total"      => $total_discount,
				"off_tax"        => $discount_tax,
				"cart_tax"       => $cart_tax,
				"tax_total"      => $total_tax,
				"total"          => $amount,
				'returnUrl'      => $CallbackUrl,
				'clientRefId'    => $order_id,
				"description"    => $Description
			);
			
			$args = array(
				'body' => json_encode($data),
				'timeout' => '45',
				'redirection' => '5',
				'httpsversion' => '1.0',
				'blocking' => true,
			   'headers' => array(
				  'Authorization' => 'Bearer '.$this->zifyToken,
				  'Content-Type'  => 'application/json',
				  'Accept' => 'application/json'
				  ),
				'cookies' => array()
			);
			
			$response = wp_remote_post($this->baseurl.'/api/order/v2/create', $args);
			
			$body = json_decode(wp_remote_retrieve_body($response), JSON_UNESCAPED_UNICODE);
			
			if( is_wp_error($response) ){
				$Message = $response->get_error_message();
			}else{	
				$code = wp_remote_retrieve_response_code( $response );
				if( $code === 200){
					if (isset($body['data']) and $body['data'] != '') {
						$order_code = $body['data']['order'];
						update_post_meta($order_id, '_zify_orderCode', $order_code );
						$Note = 'ساخت موفق پرداخت، کد پرداخت: '.$order_code;
						$order->add_order_note($Note, 1, false);
						wp_redirect(sprintf('%s/order/accept/%s', $this->baseurl, $order_code));
						exit;
					} else {
						$Message = ' تراکنش ناموفق بود : ';
						$Fault = $Message;
					}
				}else{
					$Message = $body['message'];
					$Fault = $Message;
				}
			}

			if(!empty($Message) && $Message){
				$Note = sprintf(__('خطا در هنگام ارسال به بانک : %s', 'woocommerce'), $Message);
				$Fault = sprintf(__('خطا در هنگام ارسال به بانک : %s', 'woocommerce'), $Fault);
				$Note = apply_filters('woo_zify_Send_to_Gateway_Failed_Note', $Note, $order_id, $Fault);
				wc_add_notice($Fault, 'error');
				$order->add_order_note($Note, 0, false);
			}
		}

		public function callback_after_pay(){
			global $woocommerce;
			
			if( isset( $_REQUEST['wc_order'] ) ){
				$order_id = sanitize_text_field( $_REQUEST['wc_order'] );
			}else{
				$order_id = $woocommerce->session->order_id_zify;
				unset( $woocommerce->session->order_id_zify );
			}

			// Get refid
			if( isset( $_REQUEST['refid'] ) ){
				$refid = sanitize_text_field( $_REQUEST['refid'] );
			}else{
				$refid = null;
			}
			
			$order_id = apply_filters('zify_woo_return_order_id', $order_id);
			
			
			if( isset( $order_id ) ){
				// Get Order id
				$order = new WC_Order($order_id);
				// Get Currency Order
				$currency = $order->get_currency();
				// Add Filter For Another Developer
				$currency = apply_filters('zify_woo_Currency', $currency, $order_id);
				
				if( $order->get_status() != 'completed' ){
					// Get Amount
					$Amount = intval($order->get_total());
					/* add filter for other developer */
					$Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
					/* check currency and set amount */
					$Amount = $this->zify_check_currency( $Amount, $currency );

					// Add Filter for ANother Developer
					
					$Transaction_ID = $refid;
					$order_code = get_post_meta( $order_id, '_zify_orderCode', true );
					//Set Data 
					$data = array(
						'order' => $order_code
					);
					$args = array(
						'body' => json_encode($data),
						'timeout' => '45',
						'redirection' => '5',
						'httpsversion' => '1.0',
						'blocking' => true,
						'headers' => array(
						'Authorization' => 'Bearer ' . $this->zifyToken,
						'Content-Type'  => 'application/json',
						'Accept' => 'application/json'
						),
					 'cookies' => array()
					);
					
				//response
				$response = wp_remote_post($this->baseurl.'/api/order/v2/verify', $args);
				
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				
				if( is_wp_error($response) ){
					$Status = 'failed';
					$Fault = $response->get_error_message();
					$Message = 'خطا در ارتباط به زیفای : شرح خطا '.$response->get_error_message();
				}else{
					$code = wp_remote_retrieve_response_code( $response );
					
					if( $code === 200 && $body['data']['amount'] == $Amount){
						$Status = 'success';
						$card_number = '-';
						if( isset($card_number)){
							$card_number = $body['data']['card_number'];
						}
						$Message = 'پرداخت با موفقیت انجام شد. <br>شماره کارت: <b dir="ltr">'.$card_number.'</b>';
						$Transaction_ID = $body['data']['refid'];
					}else{
						$Status = 'failed';
						$Message = $body['message'];
						$Fault = 'پرداخت ناموفق بود.';
					}
				}
				
					if( isset( $Transaction_ID ) && $Transaction_ID != 0){
						update_post_meta($order_id, '_transaction_id', $Transaction_ID );
						if( $Status == 'success' ){
							$Note = sprintf( __('%s <br> شماره سفارش: %s <br>', 'woocommerce'), $Message, $Transaction_ID) ;
							$Note = apply_filters('zify_woo_Return_from_Gateway_Success_Note', $Note, $order_id, $Transaction_ID );
							$Notice = wpautop(wptexturize($this->success_massage));
							$Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);
							
							do_action('zify_woo_Return_from_Gateway_Success', $order_id, $Transaction_ID, $response);
							wc_add_notice($Message, 'error');
							$order->add_order_note( $Message, 0, false);
							$woocommerce->cart->empty_cart();
							$order->payment_complete($Transaction_ID);
							wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
							exit;
						}else{
							$tr_id = ($Transaction_ID && $Transaction_ID != 0) ? ('<br/>کد پیگیری: ' . $Transaction_ID) : '';
							$Note = sprintf(__('خطا در هنگام تایید پرداخت: %s', 'woocommerce'), $Message, $tr_id);
							$Note = apply_filters('zify_woo_Return_from_Gateway_Failed_Note', $Note, $order_id, $Transaction_ID, $Fault);
							$Notice = wpautop(wptexturize($Note));
							$Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);
							$Notice = str_replace("{fault}", $Message, $Notice);
							
							do_action('zify_woo_Return_from_Gateway_Failed', $order_id, $Transaction_ID, $Fault);
							wc_add_notice($Fault, 'error');
							$order->add_order_note( $Notice, 0, false);
							wp_redirect(wc_get_checkout_url());
							exit;
						}
					}else{
						$Notice = $Message;
						wc_add_notice($Fault, 'error');
						$order->add_order_note( $Notice, 0, false);
						update_post_meta($order_id, '_transaction_id', 1 );
						wp_redirect(wc_get_checkout_url());
						exit;
					}
				}else{
					$Transaction_ID = get_post_meta($order_id, '_transaction_id', true);
					$Notice = wpautop(wptexturize($this->success_massage));
					$Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);
					
					do_action('zify_woo_Return_from_Gateway_ReSuccess', $order_id, $Transaction_ID);
					wc_add_notice($Fault, 'error');
					$order->add_order_note( $Notice, 0, false);
					wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
					exit;
				}
			}else{
				$Fault = __('شماره سفارش وجود ندارد .', 'woocommerce');
				$Notice = wpautop(wptexturize($this->failed_massage));
				$Notice = str_replace("{fault}", $Fault, $Notice);
				wc_add_notice($Fault, 'error');
				$order->add_order_note( $Notice, 0, false);
				wp_redirect(wc_get_checkout_url());
				exit;
			}
		}

		public function zify_check_currency( $Amount, $currency ){
			if( strtolower( $currency ) == strtolower('IRT') || strtolower( $currency ) == strtolower('TOMAN') || strtolower( $currency ) == strtolower('Iran TOMAN') || strtolower( $currency ) == strtolower('Iranian TOMAN') || strtolower( $currency ) == strtolower('Iran-TOMAN') || strtolower( $currency ) == strtolower('Iranian-TOMAN') || strtolower( $currency ) == strtolower('Iran_TOMAN') || strtolower( $currency ) == strtolower('Iranian_TOMAN') || strtolower( $currency ) == strtolower('تومان') || strtolower( $currency ) == strtolower('تومان ایران') ){
				$Amount = $Amount * 1;
			}elseif(strtolower($currency) == strtolower('IRHT')){
				$Amount = $Amount * 1000;
			}elseif( strtolower( $currency ) == strtolower('IRHR') ){
				$Amount = $Amount * 100;					
			}elseif( strtolower( $currency ) == strtolower('IRR') ){
				$Amount = $Amount / 10;
			}
			return  $Amount;                      
		}

		function zify_check_mobile_payer($input){
			$phone_number = $input;
			if (substr($phone_number, 0, 3) === '+98') {
				if (substr($phone_number, 0, 4) === '+989') {
					$phone_number = '0' . substr($phone_number, 3);
				} else {
					$phone_number = null;
				}
			} else if (substr($phone_number, 0, 2) === '98') {
				if (substr($phone_number, 0, 3) === '989') {
					$phone_number = '0' . substr($phone_number, 2);
				} else {
					$phone_number = null;
				}
			} else if (substr($phone_number, 0, 4) === '0098') {
				if (substr($phone_number, 0, 5) === '00989'){
					$phone_number = '0' . substr($phone_number, 4);
				} else {
					$phone_number = null;
				}
			} else {
				if (substr($phone_number, 0, 1) === '0' && substr($phone_number, 1, 1) !== '9'){
					$phone_number = null;
				} else if (substr($phone_number, 0, 1) !== '0' && substr($phone_number, 0, 1) === '9') {
					$phone_number = '0' . $phone_number;
				} else if (substr($phone_number, 0, 1) !== '0' && substr($phone_number, 0, 1) !== '9') {
					$phone_number = null;
				} else if (substr($phone_number, 0, 1) === '0' && substr($phone_number, 1, 1) === '9') {
					$phone_number = $input;
				}
			}
			return $phone_number;
		}

		public function status_message($code){
			switch ($code){
				case 200 :
					return 'عملیات با موفقیت انجام شد';
					break ;
				case 400 :
					return 'مشکلی در ارسال درخواست وجود دارد';
					break ;
				case 500 :
					return 'مشکلی در سرور رخ داده است';
					break;
				case 503 :
					return 'سرور در حال حاضر قادر به پاسخگویی نمی‌باشد';
					break;
				case 401 :
					return 'عدم دسترسی';
					break;
				case 403 :
					return 'دسترسی غیر مجاز';
					break;
				case 404 :
					return 'آیتم درخواستی مورد نظر موجود نمی‌باشد';
					break;
			}
		}

	}
}
