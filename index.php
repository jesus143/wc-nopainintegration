<?php

/*
Plugin Name: 無痛金流整合
Description: 無痛金流整合 for woocommerce
Version: 1.0
Author: Alan <support@supershortcut.com>
Text Domain: wc-npi
*/

$server_url = 'http://demo4.iamrockylin.com/index.php';
$product_id = 'WP-ALLPAY-1';

define( 'SL_APP_API_URL', $server_url );
define( 'SL_PRODUCT_ID', $product_id );
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
define( 'SL_INSTANCE', str_replace( $protocol, '', get_bloginfo( 'wpurl' ) ) );

register_activation_hook( __FILE__, 'npi_on_activation' );

register_deactivation_hook( __FILE__, 'npi_on_deactivated' );

add_action( 'admin_notices', 'npi_admin_notices', 0 );

add_action( 'load-plugins.php', function() {
	if ( ! wc_npi_licence_check() ) {
		add_filter( 'gettext', 'b2e_gettext', 99, 3 );
	}
});    

if ( wp_plugin_active( 'woocommerce/woocommerce.php' ) && wc_npi_licence_check() ) {
	define( 'WC_NPI', __FILE__ );

	require_once( plugin_dir_path( __FILE__ ).'wc-gw-allpay-aio.php' );

	require_once( plugin_dir_path( __FILE__ ).'wc-paynow-einvoice.php' );

	require_once( plugin_dir_path( __FILE__ ).'wc-product-payments.php' );

	add_filter( 'wc_order_statuses', function( $order_statuses ) {
	    $order_statuses['wc-credit-fixed'] = _x( '定期定額已付款', 'Order status', 'woocommerce' );
	    return $order_statuses;
	});		
} 

function nsp_check($license_key)
{
	$args = array(
        'woo_sl_action' => 'activate',
        'licence_key' => $license_key,
        'product_unique_id' => SL_PRODUCT_ID,
        'domain' => SL_INSTANCE
    );
	$request_uri = SL_APP_API_URL . '?' . http_build_query( $args );

	$data = wp_remote_get( $request_uri );
	 
	if (is_wp_error( $data ) || $data['response']['code'] != 200) {
	    //there was a problem establishing a connection to the API server
        return false;
    }
	 
	$data_body = json_decode($data['body']);
	$data_body = array_pop( $data_body );
	// print_r($data_body);
	// if (isset($data_body->status)) {
    if ($data_body->status == 'success' && $data_body->status_code == 's200') {
        //the license is active and the software is active
        //doing further actions like saving the license and allow the plugin to run
		return true;
    }
    if ($data_body->status == 'error' && $data_body->status_code == 'e113') {
    	return true;
    }
    else {
       //  //there was a problem activating the license
        return false;
    }
}

function wc_npi_licence_check($key = '')
{
	// $key = !empty( $key ) ? $key :  get_option( 'wc_npi_license_key' );
	// // var_dump ( nsp_check( 'Allpay--d4521258-6ece752d-dc5b3d65' ) );
	// return nsp_check( $key );
	return true;
}

function npi_on_activation()
{
    if ( ! current_user_can( 'activate_plugins' ) )
        return;
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "activate-plugin_{$plugin}" );
}

function npi_on_deactivated()
{
	delete_option( 'wc_npi_license_key' );
}

function npi_admin_notices()
{
	global $pagenow;
	if ( $pagenow === 'plugins.php' && !empty( $_GET['npi_license_key'] ) ) {	
		// echo $_GET['npi_license_key'];
		$check = wc_npi_licence_check( esc_attr( $_GET['npi_license_key'] ) );
		if ( ! $check ) {
			?>
			<div class="error">
				<p>請輸入正確<strong>產品序號<strong></p>
			</div>
			<?php
			deactivate_plugins( plugin_basename( __FILE__ ) );
		} else {
			?>
		    <div class="updated">
		        <p><strong>啟用</strong>外掛。</p>
		    </div>
			<?php
			update_option( 'wc_npi_license_key', $_GET['npi_license_key'] );
		}
	}
}


function b2e_gettext( $translated_text, $untranslated_text, $domain )
{
    $old = array(
        "Plugin <strong>activated</strong>.",
        "Selected plugins <strong>activated</strong>." 
    );

    $new = '<form name="license" action="">';
    $new .= '請輸入無痛金流整合產品序號 <input style="width:250px" type="text" name="npi_license_key"><input type="submit" value="確認啟用">';
    $new .= '</form>';

    if ( in_array( $untranslated_text, $old, true ) ) {
            $translated_text = $new;
            remove_filter( current_filter(), __FUNCTION__, 99 );
    }
	// deactivate_plugins( plugin_basename( __FILE__ ) );
    return $translated_text;
}

function wp_plugin_active( $plugin ) 
{
    return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
}