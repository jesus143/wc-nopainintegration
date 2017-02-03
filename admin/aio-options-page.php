<?php defined('ALLPAY_AIO') or die;?>
		<div class="wrap">
			<h2>歐付寶AIO設定</h2>
			<form method="post" action="admin.php?page=allpay-aio-setting">
			<?php wp_nonce_field('allpay-aio-update-options'); ?>
			<table class="form-table">
				<tbody>
				<tr valign="top">
					<td>
						<label for="incsub_wiki-slug">商店代號</label> 						
					</td>
					<td>
						<input size="40" type="text" name="wc_gw_allpay_aio[MerchantID]" value="<?php echo aio_option('MerchantID');?>">
						<div class="help-div">特店編號(MerchantID)</div>
					</td>
				</tr>
				<tr valign="top">
					<td>
						<label for="incsub_wiki-slug">HashKey</label> 						
					</td>
					<td>
						<input size="40" type="text" name="wc_gw_allpay_aio[HashKey]" value="<?php echo aio_option('HashKey');?>">
						<div class="help-div">All in one 介接的 HashKey</div>
					</td>
				</tr>			
				<tr valign="top">
					<td>
						<label for="incsub_wiki-slug">HashIV</label> 						
					</td>
					<td>
						<input size="40" type="text" name="wc_gw_allpay_aio[HashIV]" value="<?php echo aio_option('HashIV');?>">
						<div class="help-div">All in one 介接的 HashIV</div>
					</td>
				</tr>		
				<tr valign="top">
					<td>
						<label for="incsub_wiki-slug">自訂交易失敗頁面</label> 						
					</td>
					<td>
						<!-- <input size="40" type="text" name="wc_gw_allpay_aio[custom_fail_page]" value="<?php echo aio_option('custom_fail_page');?>"> -->
						<select name="wc_gw_allpay_aio[custom_fail_page]">
							<option value="">預設</option>
							<?php foreach($pages as $page):?>
							<?php $selected = (aio_option('custom_fail_page') == $page->ID) ? 'selected' : ''?> 
							<option value="<?php echo $page->ID?>" <?php echo $selected?>><?php echo $page->post_title?></option>
							<?php endforeach?>
						</select>							
						<div class="help-div">請選擇頁面</div>
					</td>
				</tr>			
				<tr valign="top">
					<td>
						<label for="incsub_wiki-slug">自訂返回商店頁面</label> 						
					</td>
					<td>
						<!-- <input size="40" type="text" name="wc_gw_allpay_aio[custom_fail_page]" value="<?php echo aio_option('custom_fail_page');?>"> -->
						<select name="wc_gw_allpay_aio[custom_back_page]">
							<option value="">預設</option>
							<?php foreach($pages as $page):?>
							<?php $selected = (aio_option('custom_back_page') == $page->ID) ? 'selected' : ''?> 
							<option value="<?php echo $page->ID?>" <?php echo $selected?>><?php echo $page->post_title?></option>
							<?php endforeach?>
						</select>							
						<div class="help-div">請選擇頁面</div>
					</td>
				</tr>	

				</tbody>
			</table>
			<p class="submit">
			<input type="submit" name="submit_settings" value="儲存">
			</p>
		</form>		
		<div class="clear"></div>
		</div>