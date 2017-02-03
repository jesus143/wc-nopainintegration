<?php 
$_GET['p'] OR die; 
define( 'BARCODE_PRINT', 1 );
$p = $_GET['p'];
$p = unserialize( base64_decode( $p ) ); 
$order_number = $p['MerchantTradeNo'];
$order_total = $p['TradeAmt'];
$expire_date = date( 'Y年m月d日', strtotime( $p['ExpireDate'] ) );
$barcode1 = $p['Barcode1'];
$barcode2 = $p['Barcode2'];
$barcode3 = $p['Barcode3'];
include( dirname(__FILE__) . '/print/template.php' );
?>


