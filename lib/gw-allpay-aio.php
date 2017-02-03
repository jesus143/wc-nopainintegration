<?php

/*
	特店編號(MerchantID) 2000132
	登入廠商後台帳號/密碼 StageTest/test1234
	廠商後台測試環境 http://vendor-stage.allpay.com.tw
	all in one 介接的 HashKey 5294y06JbISpM5x9
	all in one 介接的 HashIV v77hoKGq4kWxNNIS
	信用卡測試卡號 4311-9522-2222-2222
	信用卡測試安全碼 222
	信用卡測試有效年月 請設定大於測試時間。假如您的測試時間在 2013 年 11 月 26 號,該筆交易的
   	信用卡有效年月請設定 2013 年 11 月以後,因為系統會判斷有效年月是否已過期,已過期則會回應刷卡失敗。
*/

class GW_Allpay_Aio extends WC_Payment_Gateway 
{
	public function __construct() 
	{
		$this->MerchantID = aio_option( 'MerchantID' );
		$this->HashKey = aio_option( 'HashKey' );
		$this->HashIV = aio_option( 'HashIV' );
		$this->notify_url = trailingslashit( home_url() );
		$this->has_fields = false;

		if ( $this->MerchantID == '2000132') {
			$this->gateway = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V2';
		} else {
			$this->gateway = 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V2';
		}				
	}	

	public function create_check_code( $allpay_args ) 
	{
		ksort( $allpay_args );
		if (isset($allpay_args['CheckMacValue'])) {
			unset($allpay_args['CheckMacValue']);
		}
		$hash_raw_data = 'HashKey='.$this->HashKey.'&'.urldecode(http_build_query($allpay_args)).'&HashIV='.$this->HashIV;

		$urlencode_data = strtolower(urlencode($hash_raw_data));

		$urlencode_data = str_replace('%2d', '-', $urlencode_data);
		$urlencode_data = str_replace('%5f', '_', $urlencode_data);
		$urlencode_data = str_replace('%2e', '.', $urlencode_data);
		$urlencode_data = str_replace('%21', '!', $urlencode_data);
		$urlencode_data = str_replace('%2a', '*', $urlencode_data);
		$urlencode_data = str_replace('%28', '(', $urlencode_data);
		$urlencode_data = str_replace('%29', ')', $urlencode_data);

		return strtoupper(md5($urlencode_data));		
	}

	public function check_value( $post ) 
	{
		$MyCheckMacValue = $this->create_check_code( $post );

		if ( (int)$post['RtnCode'] == 1 AND $MyCheckMacValue == $post['CheckMacValue'] ) {
			return true;
		}
		return false;
	}		

	// 後台設置
	public function init_form_fields() 
	{  
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('啟用/關閉', 'woocommerce'),
				'type' => 'checkbox',
				'label' => __('啟用 ', 'woocommerce') . $this->method_title,
				'default' => 'no'
			),
			'title' => array(
			    'title' => __('標題', 'woocommerce'),
			    'type' => 'text',
			    'description' => __('客戶在結帳時所看到的標題', 'woocommerce'),
			    'default' => $this->method_title
			),
			'description' => array(
			    'title' => __('客戶訊息', 'woocommerce'),
			    'type' => 'textarea',
			    'description' => __('', 'woocommerce'),
			    'default' => $this->method_title
			), 
            'paid_order_status' => array(
                'title' => __('付款成功訂單狀態', 'woocommerce'),
                'type' => 'select',
                'options' => wc_get_order_statuses(),
                // 'description' => "URL of success page"
            )			
    	);

    	if ($this->id == 'allpay_aio_atm') {
    		$this->form_fields['atm_expire_date'] = array(
			    'title' => __('繳費期限', 'woocommerce'),
			    'type' => 'text',
			    'description' => __('繳費期限(天)', 'woocommerce'),
			    'default' => 1
    		);
    	}

    	if ($this->id == 'allpay_aio_barcode') {
    		$this->form_fields['barcode_expire_date'] = array(
			    'title' => __('繳費期限', 'woocommerce'),
			    'type' => 'text',
			    'description' => __('繳費期限(天)', 'woocommerce'),
			    'default' => 1
    		);
    	}    	

    	if ($this->id == 'allpay_aio_cvs') {
    		$this->form_fields['cvs_expire_date'] = array(
			    'title' => __('繳費期限', 'woocommerce'),
			    'type' => 'text',
			    'description' => __('繳費期限(天)', 'woocommerce'),
			    'default' => 1
    		);
    	}    	

    	if ($this->id == 'allpay_aio_ibon') {
    		$this->form_fields['ibon_expire_date'] = array(
			    'title' => __('繳費期限', 'woocommerce'),
			    'type' => 'text',
			    'description' => __('繳費期限(天)', 'woocommerce'),
			    'default' => 1
    		);
    	}
		if( $this->id == 'allpay_aio_credit' ) {
			$this->form_fields['refund_note'] = array(
			    'title' => __('後台退款指示訊息', 'woocommerce'),
			    'type' => 'textarea',
			    'description' => '給商店管理員的指示訊息，此訊息會顯示在訂單頁裡的退款區下方，預設訊息:<code>一鍵退操作規範&更新 請先查閱http://bit.ly/1VElbWW</code>',
				'default' => '一鍵退操作規範&更新 請先查閱http://bit.ly/1VElbWW'
    		);
		}
	}
	
	/**
	 * Remove WishList Members member level
	 * @param type object $order WooCommerce WC_Order
	 */
	public function remove_wlm_member_level( $order_id ) {

		$order = new WC_Order( $order_id );
		
		if( !function_exists( 'wlmapi_update_member' ) ) {
			return;
		}

		$items = $order->get_items();
		$user_id = $order->user_id;

		// Remove the member level from the given product within the order
		$level_ids = array();
		$level_names = array();
		$member_levels = wlmapi_get_member_levels( $user_id );
		foreach ( $items as $item ) {

		    $product_id = $item['product_id'];
			$level_id = get_post_meta( $product_id, 'wishlist_level', true );
			$level_data = wlmapi_get_level( $level_id );

			if( isset( $level_data['success'] ) && $level_data['success'] == '1' && isset( $member_levels[$level_id] ) ) {
				$level = $level_data['level'];
				$level_ids[] = $level_id;
				$level_names[] = $level['name'];
			}
		}

		if( $level_ids ) {

			$wlm_args = array(
				 'RemoveLevels' => $level_ids
			);

			$member = wlmapi_update_member( $user_id, $wlm_args );
			$canceled_level = implode( ',' , $level_names );
			$order->add_order_note( '取消會員資格 : ' . $canceled_level );
		}
	}

	/**
	 * Get Allpay installment args
	 * @return string
	 */
	static function allpay_credit_installment_args() {
		$credit_installment = array(
			'default' => '無分期',
			'3' => '3期',
			'6' => '6期',
			'12' => '12期',
			'18' => '18期',
			'24' => '24期'
		);
		return apply_filters( 'allpay_credit_installment_args', $credit_installment );
	}

	// 接收回傳參數驗證
	public function thankyou_page( $order_id ) 
	{  
	    global $woocommerce;

	    $order = new WC_Order( $order_id );

	    if ( $description = $this->get_description() )
	        echo wpautop( wptexturize( $description ) );

		if ( $this->check_value( $_POST ) ) {
			$result_msg = '交易成功，交易單號：' . $_REQUEST['TradeNo'] . '，處理日期：' . $_REQUEST['TradeDate'];   //交易成功
			// if ($this->id != 'allpay_aio_credit_fixed') { // 定期定額由period.php處理
			// 	$status = str_replace('wc-', '', $this->settings['paid_order_status']);
		 //   		$order->update_status( $status, __( '已收到付款', 'allpay' ) );	
		 //   	}
	   		$woocommerce->cart->empty_cart();	
		} else {
			if ( $_POST )
				$result_msg = '交易授權失敗。';
		}
		echo $result_msg;
	}

	public function generate_allpay_form( $order_id ) 
	{
		// ini_set('display_errors', 1);
		// error_reporting(-1);
		
	    global $woocommerce;

	    $order = new WC_Order( $order_id );

	    $allpay_args = $this->get_allpay_args( $order );

	    // print_r($allpay_args);
	    $allpay_gateway = $this->gateway;

	    $input_array = array();    

	    foreach ($allpay_args as $key => $value) {
	        $allpay_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
	    }

	    // $woocommerce->add_inline_js( 'jQuery("#submit_allpay_payment_form").click();' ); 
	  
	    return '<form id="allpay" name="allpay" action=" ' . $allpay_gateway . ' " method="post" target="_top">' . implode('', $allpay_args_array) . '
			<input type="submit" class="button-alt" id="submit_allpay_payment_form" value="' . __('付款', 'allpay') . '" />
			</form>' . "<script>document.forms['allpay'].submit();</script>";
	}		

	public function receipt_page( $order_id ) 
	{
	    echo '<p>' . __( '感謝您的訂購，接下來將導向到付款頁面，請稍後.', 'allpay' ) . '</p>';
	    echo $this->generate_allpay_form( $order_id );
	}

	public function process_payment( $order_id ) 
	{
	    global $woocommerce;

	    $order = new WC_Order( $order_id );

	    // Empty awaiting payment session
	    unset( $_SESSION['order_awaiting_payment'] );

	    return array(
	        'result' => 'success',
	        'redirect' => add_query_arg( 
	        		'order', 
	        		$order->id, 
	        		add_query_arg( 
	        			'key', 
	        			$order->order_key, 
	        			get_permalink( woocommerce_get_page_id( 'pay' ) ) 
	        		) 
	        	)
	    );
	}

	public function payment_fields() 
	{
		if ( $this->description )
		    echo wpautop( wptexturize( $this->description ) );
	}	

}