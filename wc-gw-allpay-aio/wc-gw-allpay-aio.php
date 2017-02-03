<?php

/*
Plugin Name: 歐付寶全功能金流
Description: 歐付寶全功能金流 for woocommerce
Version: 1.2
Author: Alan <alanchang15@gmail.com>
Text Domain: wc-gw-allpay-aio
*/

// ini_set('display_errors', 1);
// error_reporting(-1);

if ( ! defined( 'ALLPAY_AIO' ) ) {
	define( 'ALLPAY_AIO', 1 );
}

class wc_gw_allpay_aio 
{
	private static $_instance = NULL;
	public $methods = array();

	public static function get_instance() 
	{
		if ( self::$_instance == NULL ) {
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
			// add_action( 'admin_notices', array( $this, 'error_notice' ));				
		}
		add_action ( 'plugins_loaded', array( $this, 'init_allpay_aio' ) );	
	}

	public function plugin_activated() 
	{
		if ( get_option( 'wc_gw_allpay_aio') == '' ) {
			$wc_gw_allpay_aio = include( dirname(__FILE__) . '/default-setting.php' );
			update_option( 'wc_gw_allpay_aio', $wc_gw_allpay_aio );
		}
	}

	public function notify_url() 
	{
		return plugins_url( 'notify.php', __FILE__ );
	}

	public function paymentinfo_url() 
	{
		return plugins_url( 'paymentinfo.php', __FILE__ );
	}	

	public function period_url()
	{
		return plugins_url( 'period.php', __FILE__ );
	}

	public function plugin_deactivated() 
	{
		delete_option( 'wc_gw_allpay_aio' );
	}

	public function init_allpay_aio() 
	{
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

		if ( ! class_exists( 'GW_Allpay_Aio') ) {
			require_once( dirname(__FILE__) . '/lib/gw-allpay-aio.php' );
		}

		$gateway_path = plugin_dir_path( __FILE__ ) . 'gateway/';
		$classes = glob( $gateway_path . 'wc-allpay-aio-*.php' );

		if ( ! $classes ) return;

		foreach ( $classes as $class ) {
			$class_name =  basename( str_replace( '-', '_', $class), '.php');
			if ( ! class_exists( $class_name ) ) {
				require_once( $class );
				$this->methods[] = $class_name;
			}			
		}
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_allpay_aio_gateway' ) );	
	}

	public function add_allpay_aio_gateway( $methods ) 
	{
	    $methods = array_merge( $methods, $this->methods );
	    return $methods;
	}		

	/* admin page *************************************************************/

	public function add_woocommerce_submenu() 
	{
	    add_submenu_page( 
    		'woocommerce', 
    		'歐付寶AIO設定', 
    		'歐付寶AIO設定',
    		'manage_options', 
    		'allpay-aio-setting', 
    		array( $this, 'admin_options_page')
    	); 
	}

	public function admin_options_page() 
	{
		if ( ! current_user_can('manage_options' ) ) {
			echo '<p>' . __('沒有權限操作', 'woocommerce') . '</p>';
			return;
		}

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'allpay-aio-update-options' ) ) {
			$license_key = esc_attr( $_POST['wc_gw_allpay_aio']['LicenseKey'] );
			update_option( 'wc_gw_allpay_aio', $_POST['wc_gw_allpay_aio'] );
		}
		require_once( 'admin/options-page.php' );
	}
}

function allpay_aio() 
{
	return wc_gw_allpay_aio::get_instance();
}

function aio_option( $key ) 
{
	$options = get_option( 'wc_gw_allpay_aio' );
	return ( isset( $options[$key] ) ) ? $options[$key] : null;
}

allpay_aio();