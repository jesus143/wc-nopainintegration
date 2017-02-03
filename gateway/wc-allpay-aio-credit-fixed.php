<?php

class WC_Allpay_Aio_Credit_Fixed extends GW_Allpay_Aio 
{
	public function __construct() 
	{
		parent::__construct();			
		$this->id = 'allpay_aio_credit_fixed';
		$this->icon = apply_filters( 'wc_allpay_aio_credit_icon', '' );
		$this->method_title = __('信用卡定期定額', 'woocommerce');

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
			'ChoosePayment' => 'Credit',
			'ClientBackURL' => $this->get_return_url( $order ),	
			// 'CreditInstallment' => '0', // 刷卡分期期數			
			'ExecTimes' => 1, // 執行次數
			'Frequency' => 1, // 執行頻率	
			'ItemName' => $buyer_name . ' 訂單[ '.$order_id.' ]',
			'MerchantID' => $this->MerchantID, // 商店編號
			'MerchantTradeDate' => date('Y/m/d H:i:s'),				
			'MerchantTradeNo' => $trade_no,  // 商店交易編號
			'NeedExtraPaidInfo' => 'Y',
			'OrderResultURL' => $this->get_return_url( $order ), // Client 端回傳付款結果網址
			'PaymentType' => 'aio',
			'PeriodAmount' => 0, // 每次授權金額
			'PeriodReturnURL' => allpay_aio()->period_url(), // 定期定額的執行結果回應URL
			'PeriodType' => 'M', // 週期種類
			'ReturnURL' => allpay_aio()->notify_url(),		
			'TotalAmount' => round( $order->get_total() ),
			'TradeDesc' => $buyer_name
		);		
	
		$allpay_args['CheckMacValue'] = $this->create_check_code( $allpay_args );

	    $allpay_args = apply_filters( 'woocommerce_allpay_aio_credit_fixed_args', $allpay_args );
	    // echo '<pre>';
	    // print_r($allpay_args); die;	
	    return $allpay_args;
	}

}