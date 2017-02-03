<?php

if (  $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	// file_put_contents( dirname(__FILE__) . '/' . date('ymdhis') . '.json', json_encode( $_POST ) );
	// return;
	require_once ( '../../../wp-load.php' );

	if ( strstr( $_POST['PaymentType'], 'ATM' ) ) {
		if ( $_POST['RtnCode'] == '2' ) {
			$payment_info = "\n銀行代碼: " . $_POST['BankCode'];
			$payment_info .= "\n虛擬帳號: " . $_POST['vAccount'];
			$payment_info .= "\n繳費期限: " . $_POST['ExpireDate'];
		} else {
			$payment_info = '取號失敗:'.$_POST['RtnCode'].'('.$_POST['RtnMsg'].')';		
		}
	}

	if ( strstr( $_POST['PaymentType'], 'CVS' ) ) {
		if ( $_POST['RtnCode'] == '10100073' ) {
			$payment_info = "\n繳費代碼 : " . $_POST['PaymentNo'];
			$payment_info .= "\n繳費期限: " . $_POST['ExpireDate'];
		} else {
			$payment_info = '取號失敗:'.$_POST['RtnCode'].'('.$_POST['RtnMsg'].')';	
		}
	}

	if ( strstr( $_POST['PaymentType'], 'BARCODE' ) ) {
		if ( $_POST['RtnCode'] == '10100073' ) {
			$data = array(
				'Barcode1' => $_POST['Barcode1'],
				'Barcode2' => $_POST['Barcode2'],
				'Barcode3' => $_POST['Barcode3'],
				'TradeAmt' => $_POST['TradeAmt'],
				'ExpireDate' => $_POST['ExpireDate'],
				'MerchantTradeNo' => $_POST['MerchantTradeNo']
			);
			$p = base64_encode( serialize( $data ) );
			$payment_info = '<a href="' . plugins_url( 'print.php', __FILE__ ) . '?p=' . $p .'" target="_blank">列印繳費單</a>';
		} else {
			$payment_info = '取號失敗:'.$_POST['RtnCode'].'('.$_POST['RtnMsg'].')';
		}
	}	

	$order_id = (aio_option( 'MerchantID' ) !='2000132')
		? $_POST['MerchantTradeNo']
		: substr($_POST['MerchantTradeNo'], 13 );	
	
	$data = array(
	    'comment_post_ID' => $order_id,
	    'comment_author' => 'WooCommerce',
	    'comment_author_email' => 'woocommerce@' . $host,
	    'comment_author_url' => '',
	    'comment_content' => $payment_info,
	    'comment_type' => 'order_note',
	    'comment_parent' => 0,
	    'user_id' => 0,
	    'comment_author_IP' => '',
	    'comment_agent' => 'WooCommerce',
	    'comment_date' => current_time('mysql'),
	    'comment_approved' => 1,
	);

	$comment_id = wp_insert_comment( $data );	
	add_comment_meta( $comment_id, 'is_customer_note', 1 );
}