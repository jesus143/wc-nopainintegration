<?php

class WC_Allpay_Aio_Webatm extends GW_Allpay_Aio 
{
	public function __construct() 
	{
		parent::__construct();			
		$this->id = 'allpay_aio_webatm';
		$this->icon = apply_filters( 'wc_allpay_aio_webatm_icon', '' );		
		$this->method_title = __('WebATM', 'woocommerce');

		$this->init_form_fields();
		$this->init_settings();			

		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];	

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page') );  //需與id名稱大小寫相同
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page') );			
	}

	public function get_allpay_args( $order ) 
	{
		global $woocommerce;

		$order_id = $order->id;

		$buyer_name = $order->billing_last_name . $order->billing_first_name;

		$total_fee = $order->order_total;

		$trade_no = ($this->MerchantID == '2000132') ? uniqid().$order_id : $order_id;
		// $trade_no = $this->order_number_prefix . $order_id;		

		$allpay_args =array(
			'ChoosePayment' => 'WebATM',
			'ClientBackURL' => $this->get_return_url( $order ),		
			'ItemName' => $buyer_name . ' 訂單[ '.$order_id.' ]',
			'MerchantID' => $this->MerchantID, // 商店編號
			'MerchantTradeDate' => date('Y/m/d H:i:s'),				
			'MerchantTradeNo' => $trade_no,  // 商店交易編號
			'OrderResultURL' => $this->get_return_url( $order ), // Client 端回傳付款結果網址
			'PaymentType' => 'aio',
			'ReturnURL' => allpay_aio()->notify_url(),		
			'TotalAmount' => round( $order->get_total() ),
			'TradeDesc' => $buyer_name
		);		
	
		$allpay_args['CheckMacValue'] = $this->create_check_code( $allpay_args );

	    $allpay_args = apply_filters('woocommerce_allpay_aio_webatm_args', $allpay_args);
	    	
	    return $allpay_args;
	}

}