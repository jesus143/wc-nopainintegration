<?php

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	require_once ( '../../../wp-load.php' );

	// if ( $_POST['SimulatePaid'] == 1 AND $_POST['MerchantID'] !='2000132' ) {
	// 	exit('1|OK');
	// }

	ksort( $_POST );	

	$order_id = ($_POST['MerchantID'] !='2000132')
		? $_POST['MerchantTradeNo']
		: substr($_POST['MerchantTradeNo'], 13 );

	$MerchantTradeNo = $_POST['MerchantTradeNo'];
	$TradeNo = $_POST['TradeNo'];

	if( $MerchantTradeNo ) {
		update_post_meta( $order_id, '_MerchantTradeNo', $MerchantTradeNo );
	}

	if( $TradeNo ) {
		update_post_meta( $order_id, '_TradeNo', $TradeNo );
	}
	
	$order = new WC_Order( $order_id );

	$class = 'WC_'.$order->payment_method;

	if ( ! class_exists( $class ) ) {
		$file = str_replace( '_', '-', $class ).'.php';
		require_once( __DIR__.'/gateway/'.strtolower( $file ) );
	}

	$payment = new $class;

	if ( $order->payment_method != 'allpay_aio_credit_fixed' ) {
		// if ( $payment->check_value( $_POST ) ) {
		if ( $_POST['RtnCode'] == 1 ) {
			$status = str_replace('wc-', '', $payment->settings['paid_order_status']);
			$order->update_status( $status, __( '已收到付款', 'allpay' ) );
			if ( is_plugin_active( 'wishlist-member/wpm.php' ) ) {
				wc_product_payments::get_instance()->wishlist_add_member( $order_id );
			}
		}
	} else {
		if ( $_POST['RtnCode'] == 1 ) {
			if ( $_POST['TotalSuccessTimes'] == $_POST['ExecTimes'] ) {
				$status = str_replace('wc-', '', $payment->settings['paid_order_status']);
				$order->update_status( $status, __( '已收到付款', 'allpay' ) );	
				if ( is_plugin_active( 'wishlist-member/wpm.php' ) ) {				
					wc_product_payments::get_instance()->wishlist_add_member( $order_id );				
				}
			} else {
				$order->update_status( 'processing', __( '第'.$_POST['TotalSuccessTimes'].'期已收到付款', 'allpay' ) );					
			}
			$einvoice = paynow_einvoice();
			$einvoice->set_payment_result( $_POST );
			$einvoice->create_invoice( $order_id );
		}
	}
	exit('1|OK');
}