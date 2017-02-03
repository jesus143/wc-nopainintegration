<?php defined('PAYNOW_EINVOICE') or die;?>

		<div class="wrap">

			<h2>PayNow電子發票設定</h2>

			<form method="post" action="admin.php?page=wc-paynow-options">

			<?php wp_nonce_field('paynow-update-options'); ?>

			<table class="form-table">

				<tbody>

				<tr valign="top">

					<td>

						<label>商家帳號</label> 						

					</td>

					<td>

						<input size="40" type="text" name="wc_paynow_einvoice[mem_cid]" value="<?php echo paynow_option('mem_cid');?>">

						<div class="help-div">開立發票之商家統編</div>

					</td>

				</tr>

				<tr valign="top">

					<td>

						<label>商家密碼</label> 						

					</td>

					<td>

						<input size="40" type="text" name="wc_paynow_einvoice[mem_pwd]" value="<?php echo paynow_option('mem_pwd');?>">

						<div class="help-div">商家登入密碼</div>

					</td>

				</tr>				

				<tr valign="top">

					<td>

						<label for="incsub_wiki-slug">交易網址</label> 						

					</td>

					<td>

						<input size="100" type="text" name="wc_paynow_einvoice[process_url]" value="<?php echo paynow_option('process_url');?>">

						<div class="help-div">正式環境請改 http://invoice.paynow.com.tw/PayNowEInvoice.asmx</div>

					</td>

				</tr>		

				<tr valign="top">

					<td>

						<label>開發票時機(當訂單狀態變更為)</label> 						

					</td>

					<td>
						<select class="select " name="wc_paynow_einvoice[paynow_order_status]">
							<?php foreach($order_statuses as $k => $v):?>
							<?php $selected = (paynow_option( 'paynow_order_status' ) == $k) ? 'selected' : '';?>
							<option value="<?php echo $k?>" <?php echo $selected?>><?php echo $v?></option>
							<?php endforeach?>
						</select>

						<!-- <div class="help-div">商家登入密碼</div> -->

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