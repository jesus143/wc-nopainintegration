<?php

class WC_Allpay_Aio_Credit extends GW_Allpay_Aio 
{
	public function __construct() 
	{
		parent::__construct();			
		$this->id = 'allpay_aio_credit';
		$this->icon = apply_filters( 'wc_allpay_aio_credit_icon', '' );
		$this->method_title =  __('信用卡/分期付款', 'woocommerce');

		$this->init_form_fields();
		$this->init_settings();	

		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];
		$this->refund_note = $this->settings['refund_note'];
		
		$this->supports = array(
			'products',
			'refunds'
		);
		
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page') );  //需與id名稱大小寫相同
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page') );
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'display_refund_note' ) );
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'auto_fill_refund_amount' ) );
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
			'CreditInstallment' => '0', // 刷卡分期期數				
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

		$credit_installment = get_post_meta( $order_id, '_CreditInstallment', true );

		if( $credit_installment != 'default' ) {
			$allpay_args['CreditInstallment'] = $credit_installment;
		}

		$allpay_args['CheckMacValue'] = $this->create_check_code( $allpay_args );

	    $allpay_args = apply_filters( 'woocommerce_allpay_aio_credit_args', $allpay_args );
	    	
	    return $allpay_args;
	}

	/**
	 * Display checkout message and installment payment field,
	 * only display allowed method set in the product
	 */
	public function payment_fields() {

		global $woocommerce;
		parent::payment_fields();
		
		$allpay_credit_installment_args = self::allpay_credit_installment_args();
		$allowed_installments = array();
		$items = $woocommerce->cart->cart_contents;
		
		// find out all the allowed method from given product
		foreach( $items as $item ) {
			$product_id = $item['product_id'];
			$installments_per_product = get_post_meta( $product_id, 'credit_installments', true );
			if( empty( $installments_per_product ) ) {
				continue;
			}

			foreach( $installments_per_product as $installment ) {
				if( !in_array( $installment, $allowed_installments ) ) {
					$allowed_installments[] = $installment;
				}
			}
		}
		
		// Display methods
		if( $allowed_installments ) {
			echo '<label for="CreditInstallment">選擇分期方式</label> ';
			echo '<select name="CreditInstallment" id="CreditInstallment">';
			foreach( $allpay_credit_installment_args as $method => $label ) {
				if( !in_array( $method, $allowed_installments ) ) {
					continue;
				}
				echo '<option value="' . $method . '">' . $label . '</label>';
			}
			echo '</select>';
		}
	}

	public function process_payment( $order_id ) {
		if( isset( $_POST['CreditInstallment'] ) && !empty( $_POST['CreditInstallment'] ) ) {
			$credit_installment = $_POST['CreditInstallment'];
		} else {
			$credit_installment = 'default';
		}
		update_post_meta( $order_id, '_CreditInstallment', $credit_installment );
		return parent::process_payment( $order_id );
	}

	/**
	 * Allpay refund
	 *
	 * @param string $order_id
	 * @return mixed
	 */
	function allpay_credit_refund( $order_id, $amount = null ) {

		$allpay_credit_action = 'https://payment.allpay.com.tw/CreditDetail/DoAction'; // Live url only
		
		$MerchantID = $this->MerchantID;
		$MerchantTradeNo = get_post_meta( $order_id, '_MerchantTradeNo', true );
		
		if( empty( $MerchantTradeNo ) ) {
			return false;
		}
		
		$TradeNo = get_post_meta( $order_id, '_TradeNo', true );
		$Action = 'R'; // R = Refund

		$refund_args = array(
			'MerchantID'      => $MerchantID,
			'MerchantTradeNo' => $MerchantTradeNo,
			'TradeNo'         => $TradeNo,
			'Action'          => $Action,
			'TotalAmount'     => $amount
		);

		$refund_args['CheckMacValue'] = $this->create_check_code( $refund_args );

		$http_query = http_build_query( $refund_args );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $allpay_credit_action );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); // in case the curl responded FALSE
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $http_query );
		$output = curl_exec( $ch );
		curl_close( $ch );

		if( empty( $output ) ) {
			return false;
		}

		$return_args = explode( '&', $output );
		$args = array();

		foreach( $return_args as $arg ) {
			$arg_explode = explode( '=',  $arg );
			$key = $arg_explode[0];
			$value = $arg_explode[1];
			$args[$key] = $value;
		}

		return $args;
	}

	/**
	 * Display refund note message under order item metabox
	 * @param object $order
	 */
	function display_refund_note( $order ) {

		if( $order->payment_method != 'allpay_aio_credit' || $order->status == 'refunded' ) {
			return;
		}
		echo '<div style="margin:1em; color: red">';
		echo wpautop( wptexturize( $this->refund_note ) );
		echo '</div>';
	}

	/**
	 * Auto fill refund amount when clicked refund button in order.
	 * @param type $order
	 */
	function auto_fill_refund_amount( $order ) {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('.refund-items').click(function() {
					var symbol;
					if( typeof woocommerce_admin_meta_boxes !== 'undefined' ) {
						symbol = woocommerce_admin_meta_boxes.currency_format_symbol;
					}
					var refund_amount = <?php echo $order->get_total() - $order->get_total_refunded() ?>;
					$('.wc-order-refund-amount .amount').text( symbol + refund_amount );
					$('#refund_amount').val(refund_amount)
				})
			})
		</script>
	<?php }

	/**
	 * Process refund
	 * 
	 * @param  string $order_id Order id
	 * @param  string $amount Get from input amount
	 * @param  string $reason Refund reason
	 * @return bool
	 */
	function process_refund( $order_id, $amount = null, $reason = '' ) {

		$order = new WC_Order( $order_id );
		$user_id = $order->user_id;

		$order_total = $order->get_total();

		$installment = get_post_meta( $order_id, '_CreditInstallment', TRUE );

		// acording to Allpay refund api, installment refund must refund all amount
		if( $installment != 'default' && (int)$installment > 0 && $amount != $order_total ) {
			throw new Exception( '分期付款退款金額必需輸入全額' );
		}

		// only proceed refund when not in test mode
		if( $this->MerchantID != '2000132' ) {

			$refund_result = $this->allpay_credit_refund( $order_id, $amount );

			// if allpay refund api no response
			if( $refund_result == false ) {
				return false;
			}

			if( isset( $refund_result['RtnCode'] ) ) {
				$RtnCode = $refund_result['RtnCode'];
			}

			if( isset( $refund_result['RtnMsg'] ) ) {
				$RtnMsg = $refund_result['RtnMsg'];
			}

			if( $RtnCode !== '1' ) {
				throw new Exception( '退款失敗 : ' . $RtnMsg );
			}
		}

		/**
		 *  Remove WishList Member level
		 */
		$this->remove_wlm_member_level( $order_id );

		/**
		 *  Cancel paynow invoice
		 */
		$einvoice = paynow_einvoice();
		$einvoice->cancel_invoice( $order_id );			

		return true;
	}
}