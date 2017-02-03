<?php

// ini_set( 'display_errors', 1 );
// error_reporting( -1 );

class wc_product_payments
{
	private static $_instance = null;

	public static function get_instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	private function __construct() 
	{
		add_action( 'plugins_loaded', array( $this, 'init_payments_and_thankyou_page' ) );
	}

	public function init_payments_and_thankyou_page()
	{	
        if (is_admin()) {
		 	add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ), 10, 2 );
		}
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'payment_gateway_disable' ) );			
		add_action( 'woocommerce_thankyou', array( $this, 'thankyou_page' ) );
		add_filter( 'woocommerce_allpay_aio_credit_fixed_args', array( $this, 'allpay_credit_fixed_args' ) );		
		add_action( 'woocommerce_checkout_before_order_review', array( $this, 'checkout_before_order_review' ) );
		add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'checkout_top' ) );
		add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
		add_action( 'woocommerce_checkout_shipping', array( $this, 'checkout_sidebar' ) );

		add_filter('woocommerce_create_account_default_checked' , function ($checked){   
		    return true;
		});
	//	if ( is_plugin_active( 'wishlist-member/wpm.php' ) ) {
	//	 	add_action( 'woocommerce_checkout_order_processed', array( $this, 'wishlist_add_member' ) );
	//	}
	}

	public function add_meta_boxes()
	{
		global $post;

		add_meta_box(
			'payments', 
			'付款方式', 
			array( $this, 'payments_form' ), 
			'product', 
			'side', 
			'high'
		);

		if( $post->post_type == 'product' ) {

			$payment_gateways = new WC_Payment_Gateways();
			$gateways = $payment_gateways->get_available_payment_gateways();

			foreach( $gateways as $gateway ) {
				if( 'allpay_aio_credit' == $gateway->id ) {
					add_meta_box(
						'credit', 
						'信用卡分期方式', 
						array( $this, 'credit_installments' ), 
						'product', 
						'side', 
						'high'
					);
				}
			}
		}

		add_meta_box(
			'credit-fixed-options',
			'定期定額選項',
			array( $this, 'credit_fixed_form' ),
			'product',
			'side',
			'high'
		);

		add_meta_box(
			'thankyou-page',
			'感謝頁',
			array( $this, 'thankyou_page_form' ),
			'product',
			'side',
			'high'
		);		

		add_meta_box(
			'checkout-iframe',
			'結帳頁IFRAME',
			array( $this, 'checkout_iframe_form' ),
			'product',
			'advanced',
			'high'
		);			

		if ( is_plugin_active( 'wishlist-member/wpm.php' ) ) {
			add_meta_box(
				'wishlist-member',
				'WishList自動註冊',
				array( $this, 'wishlist_member_form' ),
				'product',
				'advanced',
				'high'
			);
		}
	}

	public function checkout_iframe_form()
	{
		global $post;
		$checkout_iframe = get_post_meta($post->ID, 'checkout_iframe', true);
		if ($checkout_iframe) extract($checkout_iframe);
		?>
		<p class="form-field">
			<label>頂部IFRAME網址</label>
			<input type="text" class="short" name="checkout_iframe[top_iframe]" value="<?php echo isset($top_iframe)?$top_iframe:''?>">
		</p>
		<p class="form-field">			
			<label>頂部高度</label>
			<input style="width:100px;" type="text" name="checkout_iframe[top_iframe_height]" value="<?php echo isset($top_iframe_height)?$top_iframe_height:''?>"> px
		</p>
		<p class="form-field">		
			<label>側邊IFRAME網址</label>
			<input type="text" class="short" name="checkout_iframe[sidebar_iframe]" value="<?php echo isset($sidebar_iframe)?$sidebar_iframe:''?>">		
		</p>
		<p class="form-field">			
			<label>側邊高度</label>
			<input style="width:100px;" type="text" name="checkout_iframe[left_iframe_height]" value="<?php echo isset($left_iframe_height)?$left_iframe_height:''?>"> px
		</p>		
<!-- 		<p class="form-field">
			<label>顯示購物車</label>
			<input type="checkbox" name="checkout_iframe[cart]" <?php echo ($cart=='on') ? 'checked' : ''?>>
		</p> -->
		<?php
	}

	public function credit_fixed_form()
	{
		global $post;
		$allpay = get_post_meta($post->ID, 'allpay', true);
		if ($allpay) extract($allpay);
		$selected = isset($period_type) ? 'selected' : '';
		?>
		<label>每</label>
		<input type="text" name="allpay[frequency]" value="<?php echo isset($frequency)?$frequency:1?>" style="width:45px;">
		<select name="allpay[period_type]" id="type">
			<option value="M" <?php echo $selected?>>月</option>
			<option value="D" <?php echo $selected?>>日</option>
			<option value="Y" <?php echo $selected?>>年</option>
		</select>
		<label>扣</label>
		<input type="text" name="allpay[period_amount]" value="<?php echo isset($period_amount)?$period_amount:''?>" style="width:60px;">元
		<br>
		<label>扣</label>
		<input type="number" id="exec" min="1" max="" name="allpay[exec_times]" value="<?php echo isset($exec_times)?$exec_times:''?>" style="width:60px;">次	
		<script>
		jQuery(function() {
			var max = 9;
			jQuery('#exec').attr('max', max);
			jQuery('#type').change(function() {
				var type = jQuery(this).val();
				jQuery('#exec').val(1);
				switch (type) {
					case 'D':
						max = 999;
						break;
					case 'M':
						max = 99;
						break;
					case 'Y':
						max = 9;
						break;
				}
				jQuery('#exec').attr('max', max);
			});
		});
		</script>
		<?php
	}

	public function thankyou_page_form()
	{
        global $post, $woo;		

		$thankyou_page = get_post_meta($post->ID, 'thankyou_page', true);

		$args = array(
			'sort_order' => 'asc',
			'sort_column' => 'post_title',
			'hierarchical' => 1,
			'exclude' => '',
			'include' => '',
			'meta_key' => '',
			'meta_value' => '',
			'authors' => '',
			'child_of' => 0,
			'parent' => -1,
			'exclude_tree' => '',
			'number' => '',
			'offset' => 0,
			'post_type' => 'page',
			'post_status' => 'publish'
		); 
		$pages = get_pages($args);
		?>
		<select name="thankyou_page">
			<option value="">預設</option>
			<?php foreach($pages as $page):?>
			<?php $selected = ($thankyou_page == $page->ID) ? 'selected' : ''?> 
			<option value="<?php echo $page->ID?>" <?php echo $selected?>><?php echo $page->post_title?></option>
			<?php endforeach?>
		</select>		
		<?php
	}

	public function payments_form()
	{
        global $post, $woo;

        $productIds = get_option('woocommerce_product_apply');
        if (is_array($productIds)) {
            foreach ($productIds as $key => $product) {
                if (!get_post($product) || !count(get_post_meta($product, 'payments', true))) {
                    unset($productIds[$key]);
                }
            }
        }
        update_option('woocommerce_product_apply', $productIds);

        $postPayments = count(get_post_meta($post->ID, 'payments', true)) ? get_post_meta($post->ID, 'payments', true) : array();

        $woo = new WC_Payment_Gateways();
        $payments = $woo->payment_gateways;
        ?>
        	<p>不勾選無法付款</p>
        <?php 
        foreach ($payments as $pay) {
            if ($pay->enabled == 'no') continue;
            $checked = '';
            if (is_array($postPayments) && in_array($pay->id, $postPayments)) {
                $checked = ' checked="yes" ';
            }
            ?>  
            <input type="checkbox" <?php echo $checked; ?> value="<?php echo $pay->id; ?>" name="pays[]" id="payment_<?php echo $pay->id; ?>" />
            <label for="payment_<?php echo $pay->id; ?>"><?php echo $pay->title; ?></label>  
            <br />  
            <?php
        }
	}

	public function credit_installments() {

		global $post;
		$post_id = $post->ID;

		$selected_installments = get_post_meta( $post_id, 'credit_installments', TRUE );
		$credit_installments = GW_Allpay_Aio::allpay_credit_installment_args();
		
		foreach( $credit_installments as $key => $label ) {
			$checked = '';
			if( is_array( $selected_installments ) && in_array( $key, $selected_installments ) ) {
				$checked = ' checked=checked';
			}
			?>
           <input type="checkbox" <?php echo $checked; ?> value="<?php echo $key; ?>" name="credit_installments[]" id="credit_installments_<?php echo $key; ?>" />
            <label for="credit_installments_<?php echo $key; ?>"><?php echo $label; ?></label>  
            <br />  
		<?php }
	}

	public function save_meta_box($post_id, $post)
	{
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (isset($post->post_type) && $post->post_type == 'revision') {
            return $post_id;
        }

        if (isset($_POST['post_type']) && $_POST['post_type'] == 'product' && isset($_POST['pays'])  ) {
            $productIds = get_option('woocommerce_product_apply');
            if (is_array($productIds) && !in_array($post_id, $productIds)) {
                $productIds[] = $post_id;
                update_option('woocommerce_product_apply', $productIds);
            }

            $payments = array();
            if ($_POST['pays']) {
                foreach ($_POST['pays'] as $pay) {
                    $payments[] = $pay;
                }
            }
            update_post_meta($post_id, 'payments', $payments);
        } elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'product'  ) {
            update_post_meta($post_id, 'payments', array());
        }

		if (isset($_POST['post_type']) && $_POST['post_type'] == 'product' && isset($_POST['credit_installments']) ) {
			$credit_installments = $_POST['credit_installments'];
			$installments = array();
			foreach( $credit_installments as $installment ) {
				$installments[] = $installment;
			}
            update_post_meta($post_id, 'credit_installments', $installments);
        } elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'product' ) {
            update_post_meta($post_id, 'credit_installments', '');
        }  
		
        if (isset($_POST['post_type']) && $_POST['post_type'] == 'product' && isset($_POST['thankyou_page']) ) {
            update_post_meta($post_id, 'thankyou_page', $_POST['thankyou_page']);
        } elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'product' ) {
            update_post_meta($post_id, 'thankyou_page', '');
        }        

        if (isset($_POST['post_type']) && $_POST['post_type'] == 'product' && isset($_POST['allpay']) ) {
        	update_post_meta($post_id, 'allpay', $_POST['allpay']);
        } elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'product' ) {        
			update_post_meta($post_id, 'allpay', '');
        }

        if (isset($_POST['post_type']) && $_POST['post_type'] == 'product' && isset($_POST['checkout_iframe']) ) {
        	update_post_meta($post_id, 'checkout_iframe', $_POST['checkout_iframe']);
        } elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'product' ) {        
			update_post_meta($post_id, 'checkout_iframe', '');
        }        

        if (isset($_POST['post_type']) && $_POST['post_type'] == 'product' && isset($_POST['wishlist_level']) ) {
        	update_post_meta($post_id, 'wishlist_level', $_POST['wishlist_level']);
        } elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'product' ) {        
			update_post_meta($post_id, 'wishlist_level', '');
        }            
	}

	public function price_format($price)
	{
		$price = wc_price($price);
		$price = str_replace(array('<span class="amount">', '<span>'), '', $price);
		return $price;
	}

	public function payment_gateway_disable($available_gateways)
	{
        // ini_set('display_errors', 1);
        // error_reporting(-1);
        global $woocommerce;
        $arrayKeys = array_keys($available_gateways);
        if (count($woocommerce->cart)) {
            $items = $woocommerce->cart->cart_contents;
            $itemsPays = '';
            if (is_array($items)) {
                foreach ($items as $item) {
                    $itemsPays = get_post_meta($item['product_id'], 'payments', true);
                    if (is_array($itemsPays) && count($itemsPays)) {
                        foreach ($arrayKeys as $key) {
                            if (array_key_exists($key, $available_gateways) && !in_array($available_gateways[$key]->id, $itemsPays)) {
                                unset($available_gateways[$key]);
                            }
                            if ($key == 'allpay_aio_credit_fixed') {
                            	$allpay = get_post_meta($item['product_id'], 'allpay', true);
                            	switch ($allpay['period_type']) {
                            		case 'Y':
                            			$period = '每'.$allpay['frequency'].'年 '.$this->price_format($allpay['period_amount']).' × '.$allpay['exec_times'].' 週期';
                            			break;
                            		case 'M':
                            			$period = '每'.$allpay['frequency'].'月 '.$this->price_format($allpay['period_amount']).' × '.$allpay['exec_times'].' 週期';
                            			break;
                            		case 'D':
                            			$period = '每'.$allpay['frequency'].'日 '.$this->price_format($allpay['period_amount']).' × '.$allpay['exec_times'].' 週期';
                            			break;
                            	}
                                if (isset($available_gateways[$key])) {
                            	   $available_gateways[$key]->title = $available_gateways[$key]->settings['title'].' ('.$period.')';
                                }
                            }
                        }
                    } else {
                    	unset($available_gateways); 
                    }
                }            
            } 
        }
        return $available_gateways;		
	}

	public function thankyou_page($order_id)
	{	
		global $woocommerce;

		$order = new WC_Order( $order_id );
		$items = $order->get_items();

		foreach ( $items as $item ) {
		    $product_id = $item['product_id'];
		}				

		if ($_POST['RtnCode'] == 1) {
			$thankyou_page = get_post_meta( $product_id, 'thankyou_page', true);

			if ( $thankyou_page ) {			
				$redirect = home_url().'/?p='.$thankyou_page;

				if ( $order->status != 'failed' ) {
					wp_redirect( $redirect ); 
					exit;
				}		
			}
		} else {
			if ($custom_fail_page = aio_option('custom_fail_page')) {
				$redirect = home_url().'/?p='.$custom_fail_page;
				wp_redirect( $redirect );
				exit;
			}
		}
	}

	public function get_product_id()
	{
		global $woocommerce; 
		$items = $woocommerce->cart->get_cart();
		foreach ($items as $item) {
			$product_id = $item['product_id'];
			if ($product_id) break;
		}
		return $product_id;		
	}

	public function get_meta($key)
	{
		$meta = get_post_meta($this->get_product_id(), 'checkout_iframe', true);
		return (array_key_exists($key, $meta)) ? $meta[$key] : null;
	}

	public function checkout_top()
	{
		$checkout_top_iframe = $this->get_meta('top_iframe');
		$checkout_top_iframe_height = $this->get_meta('top_iframe_height');
		?>
		<?php if($checkout_top_iframe):?>
		<iframe style="width:100%; height:<?php echo $checkout_top_iframe_height?>px;" frameborder="0" id="frame_top" scrolling="no" src="<?php echo $checkout_top_iframe?>"></iframe>
		<?php endif?>
		<?php
	}

	public function checkout_sidebar()
	{		
		$checkout_sidebar_iframe = $this->get_meta('sidebar_iframe');
		$checkout_left_iframe_height = $this->get_meta('left_iframe_height');		
		?>
		<?php if($checkout_sidebar_iframe):?>
		<iframe style="width:100%;height:<?php echo $checkout_left_iframe_height?>px;" frameborder="0" id="frame_sidebar" scrolling="no" src="<?php echo $checkout_sidebar_iframe?>"></iframe>
		<?php endif?>
		<?php
	}

	public function checkout_before_order_review()
	{
		$checkout_cart = $this->get_meta('cart');
		if ($checkout_cart == 'on') {
			echo do_shortcode('[woocommerce_cart]');
		}
	}

	// 定期定額參數
	public function allpay_credit_fixed_args( $allpay_args )
	{
		global $woocommerce;
        if ( count( $woocommerce->cart ) ) {
            $items = $woocommerce->cart->cart_contents;
            if ( is_array( $items ) ) {
                foreach ( $items as $item ) {
                    $allpay = get_post_meta( $item['product_id'], 'allpay', true );
                   	if ( count( $allpay ) < 4 ) {
                   		?>
                   		<p>定期定額參數錯誤!!請聯絡網站客服人員!!</p>
                   		<p><a href="javascript:history.go(-1)">返回</a></p>
                   		<?php
                   		die;
                   	}
                }
            }
        }		

        extract($allpay);

		$allpay_args['ExecTimes'] = $exec_times;
		$allpay_args['Frequency'] = $frequency;
		$allpay_args['PeriodAmount'] = $period_amount;
		$allpay_args['TotalAmount'] = $period_amount;
		$allpay_args['PeriodType'] = $period_type;
		$aio = new GW_Allpay_Aio();
		$allpay_args['CheckMacValue'] = $aio->create_check_code( $allpay_args );
	
		return $allpay_args;
	}	

	public function wishlist_member_form()
	{
		if ( is_plugin_active( 'wishlist-member/wpm.php' ) ) {
        	global $post;
			$wishlist_level = get_post_meta($post->ID, 'wishlist_level', true);			
			$levels = wlmapi_get_levels();
			?>
			<label>Level</label>
			<select name="wishlist_level">
				<option value="">請選擇</option>
				<?php foreach($levels['levels']['level'] as $level):?>
				<?php $selected = ($wishlist_level == $level['id']) ? 'selected' : ''?> 
				<option value="<?php echo $level['id']?>" <?php echo $selected?>><?php echo $level['name']?></option>
				<?php endforeach?>
			</select>		
			<?php			
		} else {
			echo 'Please install WishList Member plugin.';
		}
	}
	
	public function wishlist_add_member( $order_id )
	{
		if( !function_exists( 'wlmapi_add_member_to_level' ) ) {
			return;
		}

		$order = new WC_Order( $order_id );

		$items = $order->get_items();

		foreach ( $items as $item ) {
		    $product_id = $item['product_id'];
		}				

		$wishlist_level = get_post_meta( $product_id, 'wishlist_level', true );

		if( $wishlist_level ) {
			$args = array(
				'Users' => array($order->user_id)
			);

			$members = wlmapi_add_member_to_level($wishlist_level, $args);
		}

	}
}

wc_product_payments::get_instance();