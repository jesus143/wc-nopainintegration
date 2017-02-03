<?php

if ( ! defined( 'PAYNOW_EINVOICE' ) ) {
	define( 'PAYNOW_EINVOICE', 1 );
}

class wc_paynow_einvoice
{
	private static $_instance = null;

	public $payment_result = array();

	public static function get_instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	private function __construct() 
	{	
		register_activation_hook( WC_NPI, array( $this, 'plugin_activated' ) );
		register_deactivation_hook( WC_NPI, array( $this, 'plugin_deactivated' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_woocommerce_submenu' ), 999 );		
		}
		add_action ( 'plugins_loaded', array( $this, 'init_paynow_einvoice' ) );	

		// add_action( 'woocommerce_order_status_completed', array( $this, 'create_invoice') );
	}	

	public function set_payment_result($result = array())
	{
		$this->payment_result = $result;
	}

	public function plugin_activated()
	{
		if ( get_option( 'wc_paynow_einvoice' ) == '' ) {
			$wc_paynow_einvoice = include( dirname(__FILE__) . '/default/paynow-default-options.php' );
			update_option( 'wc_paynow_einvoice', $wc_paynow_einvoice );
		}	
	}

	public function plugin_deactivated()
	{
		delete_option( 'wc_paynow_einvoice' );
	}

	public function add_woocommerce_submenu()
	{
	    add_submenu_page( 
    		'woocommerce', 
    		'PayNow電子發票設定', 
    		'PayNow電子發票設定',
    		'manage_options', 
    		'wc-paynow-options', 
    		array( $this, 'admin_options_page' )
    	);
	}

	public function admin_options_page()
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			echo '<p>' . __( '沒有權限操作', 'wc-paynow-einvoice' ) . '</p>';
			return;
		}

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'paynow-update-options' ) ) {
			update_option( 'wc_paynow_einvoice', $_POST['wc_paynow_einvoice'] );
		}
		$order_statuses = wc_get_order_statuses();
		require_once( 'admin/paynow-options-page.php' );		
	}

	public function init_paynow_einvoice()
	{
		$paynow_order_status = paynow_option( 'paynow_order_status' );
		$paynow_order_status = str_replace( 'wc-', '', $paynow_order_status );
		add_action( 'woocommerce_order_status_'.$paynow_order_status, array( $this, 'create_invoice' ), 10, 1);
	}


	/**
	 * Cancel Paynow invoice
	 * @param type object $order WC_order
	 * @return boolean
	 * @throws Exception
	 */
	public function cancel_invoice( $order_id ) {

		$order = new WC_Order( $order_id );

		$url = paynow_option( 'process_url' ).'?wsdl';
		$client = new SoapClient( $url );

		$invoice_number = get_post_meta( $order_id, '_invoice_number', true );

		if( empty( $invoice_number ) ) {
			$order->add_order_note( '找不到發票號碼' );
			return;
		}

		if( $invoice_number == 'cancelled' ) {
			return;
		}

		$mem_cid = paynow_option( 'mem_cid' );
		
		$data = array(
			'mem_cid' => $mem_cid,
			'InvoiceNo' => $invoice_number
		);

		$is_invoice_cancelled = false;

		try {
			$cancel_invoice = $client->CancelInvoice_I( $data );
			$cancel_invoice_result = $cancel_invoice->CancelInvoice_IResult;
			if( $cancel_invoice_result == 'S' ) {
				$is_invoice_cancelled = true;
			}
		} catch( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		if ( $is_invoice_cancelled ) {
			$order->add_order_note( '發票作廢 : ' . $invoice_number );
			update_post_meta( $order_id, '_invoice_number', 'cancelled' );
		} else {
			$order->add_order_note( '發票作廢失敗 : ' . $cancel_invoice_result );
		}
	}

	// 發票開立
	public function create_invoice($order_id = '')
	{
		// ini_set('display_errors', 1);
		// error_reporting(-1);

		$order = new WC_Order( $order_id );

		$order_items = $order->get_items();

		$get_invoice_number = false;

		// $user_id = $order->get_user_id();
		// $billing_first_name = get_user_meta( $user_id, 'billing_first_name', true );
		// $billing_email = get_user_meta( $user_id, 'billing_email', true );
		// $billing_phone = get_user_meta( $user_id, 'billing_phone', true );
		// $billing_postcode = get_user_meta( $user_id, 'billing_postcode', true );
		// $billing_address = get_user_meta( $user_id, 'billing_address_1', true );
		// $billing_company = get_user_meta( $user_id, 'billing_company', true );
		// $biling_uniform_numbers = get_user_meta( $user_id, 'biling_uniform_numbers', true );
		$billing_first_name = $order->billing_first_name;
		$billing_email = $order->billing_email;
		$billing_phone = $order->billing_phone;
		$billing_postcode = $order->billing_postcode;
		$billing_address = $order->billing_address_1;
		$billing_company = $order->billing_company;
		$billing_uniform_numbers = $order->billing_uniform_numbers;

		$url = paynow_option( 'process_url' ).'?wsdl';
		
		$mem_cid = paynow_option( 'mem_cid' );

		$mem_pwd = paynow_option( 'mem_pwd' );

		$Year = date('Y');

		$period = 0;

		switch ( date('m') ) {
			case '01': 
				$period = 0; 
				break;
			case '02': 
				$period = 0; 
				break;
			case '03': 
				$period = 1; 
				break;
			case '04': 
				$period = 1; 
				break;
			case '05': 
				$period = 2; 
				break;
			case '06': 
				$period = 2; 
				break;
			case '07': 
				$period = 3; 
				break;
			case '08': 
				$period = 3; 
				break;
			case '09': 
				$period = 4; 
				break;
			case '10': 
				$period = 4; 
				break;
			case '11': 
				$period = 5; 
				break;
			case '12': 
				$period = 5; 
				break;
		}

		$client = new SoapClient( $url );

		$data = array(
			'mem_cid' => $mem_cid, 
			'Year' => $Year,
			'period' => $period,
		);
		
		try {
			$get_invoice_number = $client->GetInvoiceNumber($data);
			$invoice_number = $get_invoice_number->GetInvoiceNumberResult;
			$get_invoice_number = true;
		} catch(Exception $e) {
			list(, $invoice_number) = explode('>', $e->getMessage());
			$invoice_number = trim($invoice_number);
		}

		$product_title = array();

		if ( true === $get_invoice_number ) {
			$total = 0;
			foreach ($order_items as $item) {
				$product_title[] = $item['name'];
				if ( $order->payment_method == 'allpay_aio_credit_fixed' ) {
					$product_name = $item['name'].'(第'.$this->payment_result['TotalSuccessTimes'].'期)';
					$price = $this->payment_result['TradeAmt'];
				} else {
					$product_name = $item['name'];
					$price = $item['line_total'];
				}				
				$data = array(
					'kMapingCode' => $invoice_number,
					// 'InvNo' => $invoice_num,
					'Description' => $product_name,
					'Amount' => $price * $item['qty'],
					'Quantity' => $item['qty'],
					'UnitPrice' => $price,
					'mem_cid' => $mem_cid,
					'orderno' => $order_id,
					'Remark'=>''
				);

				$UploadInvoice_Body = $client->UploadInvoice_Body($data);
			
				$result = $UploadInvoice_Body->UploadInvoice_BodyResult;	

				if ($result->ReturnStatus ) {
					++$total;
				}
			}

			if (count($order_items) == $total) {
				$encrypt_pwd = encryption_3des( $mem_pwd );
				
				if ( $order->payment_method == 'allpay_aio_credit_fixed' ) {
					$total = $this->payment_result['TradeAmt'];
				} else {
					$total = $order->get_total();
				}

				$data = array(
					'mem_cid' => $mem_cid,
					'mem_pw' => $encrypt_pwd,
					'InvNo' => $invoice_number, 
					'kMapingCode' => $invoice_number, 
					// 'BuyerId' => $company_code,
					// 'BuyerName' => $billing_first_name,
					'BuyerAdd' => $billing_postcode.$billing_address,
					'BuyerPhoneNo' => $billing_phone,
					'BuyerEmail' => $billing_email,		
					'TotalAmount' => $total,
					'OrderNo' => $order_id,
					'OrderInfo' => join(',', $product_title),
					'Send' => 0,
					'CarrierType' => '',
					'CarrierId1' => '',
					'CarrierId2' => '',
					'NPOBAN' => '',
				);

				if ($billing_company && $billing_uniform_numbers) {
					$data['BuyerId'] = $billing_uniform_numbers;
					$data['BuyerName'] = $billing_company;
				} else {
					$data['BuyerName'] = $billing_first_name;
				}
				
				$result = $client->UploadInvoice($data);
			
				if ( $result->UploadInvoiceResult->ReturnStatus ) {
					$order->add_order_note( '發票號碼: '. $invoice_number );
					update_post_meta( $order_id, '_invoice_number', $invoice_number );
				} else {
					$order->add_order_note( $result->UploadInvoiceResult->ErrorMsg );
				}
			}
		}
	}
}

function paynow_einvoice() 
{
	return wc_paynow_einvoice::get_instance();
}

function paynow_option( $key ) 
{
	$options = get_option( 'wc_paynow_einvoice' );
	return ( isset( $options[$key] ) ) ? $options[$key] : null;
}

function encryption_3des( $string ) 
{
	$public_key = '12345678'; // public key => IV
	
	$full_length = '1234567890' . $string . '123456'; // private key => Key
	
	$key = $full_length; // private key
	$iv = $public_key; // public key
	$to_be_encrypted = $string;
	
	$cipher = mcrypt_module_open( MCRYPT_3DES, '', MCRYPT_MODE_ECB, '' );
	mcrypt_generic_init( $cipher, $key, $iv );
	$encrypted = mcrypt_generic( $cipher,$to_be_encrypted );
	mcrypt_generic_deinit( $cipher );
	
	return base64_encode( $encrypted );
}

paynow_einvoice();