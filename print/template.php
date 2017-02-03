<?php defined('BARCODE_PRINT') OR die; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<title>列印超商繳費帳單</title>
<link rel="shortcut icon" href="" />
<!-- CSS -->
<link href="/wp-content/plugins/wc-gw-allpay-aio/print/css/global.css" rel="stylesheet" type="text/css" />
<link href="/wp-content/plugins/wc-gw-allpay-aio/print/css/responsive.css" rel="stylesheet" type="text/css" />
<!--[if lt IE 9]<link href="css/ie_layout.css" rel="stylesheet" type="text/css" /> <![endif]-->
<link href="/wp-content/plugins/wc-gw-allpay-aio/print/css/seller_pay.css" rel="stylesheet" type="text/css" />
<style type="text/css">
.nav-section-inner h3 {
	font-weight: bold;
}
a {
	color: #227BBB;
	text-decoration: none;
}
</style>
</head>
<body>
<div class="wrapper"> 
  <!-- header -->
  <div class="gblHeader pag_Header"  >
    <div id="nav" class="gblnav pa_gblnav">
      <div class="container">
        <div class="Logo sell_logo"> <a class="navbar-brand" href="http://www.allpay.com.tw/WebHome" target="_blank"></a> <span style="margin-top: 28px;font-size: 1.2em;float: left;">第三方支付平台</span> </div>
        <p class="left and" style="display:none;">and</p>
        <div class="store_logo" style="display:none;"><img src="/Content/themes/WebStyle201401/images/header_logo.png" width="120" height="71" alt="AllPay歐付寶第三方支付平台" /></div>
        <div class="lap utility">
          <div class="help line_bo"> <a href="http://www.allpay.com.tw/ServiceReply/Create" target="_blank">線上回報</a> </div>
        </div>
      </div>
    </div>
  </div>
  <!-- End:header -->
  <link rel="Stylesheet" href='/wp-content/plugins/wc-gw-allpay-aio/print/css/Print.css' media="print" />

  <div class="container">
    <div class="pay_content">
      <div class="order">
        <h2>訂單成立</h2>
      </div>
      <div class="info">
        <p>請於時間內使用您選擇的付款方式進行付款！</p>
        <p>本次交易明細如下 :</p>
      </div>
      <div class="pay_box print_1">
        <h3 class="store1">便利商店繳費電子帳單</h3>
        <table border="0" cellpadding="0" cellspacing="0" class="boxB">
          <tr>
            <td height="55" colspan="4" align="center" bgcolor="#F9F9F9" ><h3><font>第一聯　客戶收執聯</font></h3></td>
          </tr>
          <tr>
            <td width="22%" height="45" align="center">訂單編號</td>
            <td width="22%" align="center">訂單金額</td>
            <td width="22%" align="center">繳費期限</td>
            <td width="34%" align="center">代收店鋪收訖章</td>
          </tr>
          <tr>
            <td height="45" align="center"><?php echo $order_number; ?></td>
            <td align="center"><?php echo $order_total; ?><br/></td>
            <td align="center" class="r"><?php echo $expire_date; ?></td>
            <td rowspan="2" align="center">&nbsp;</td>
          </tr>
          <tr>
            <td height="45" align="center">實際繳費金額</td>
            <td colspan="2" align="center"><?php echo $order_total; ?></td>
          </tr>
        </table>
        <p class="ss_info">此聯請客戶保存</p>
        <img src='http://payment-stage.allpay.com.tw/Content/themes/WebStyle201401/images/seller_pay/cutline.gif' width="auto" alt=""/>
        <table border="0" cellpadding="0" cellspacing="0" class="boxB">
          <tr>
            <td height="55" colspan="4" align="center" bgcolor="#F9F9F9" ><h3>第二聯　店鋪收執聯</h3></td>
          </tr>
          <tr>
            <td width="22%" height="45" align="center">訂單編號</td>
            <td width="22%" align="center">訂單金額</td>
            <td width="22%" align="center">繳費期限</td>
            <td width="34%" align="center">代收店鋪收訖章</td>
          </tr>
          <tr>
            <td height="45" align="center"><?php echo $order_number; ?></td>
            <td align="center"><?php echo $order_total; ?><br/></td>
            <td align="center"  class="r"><?php echo $expire_date; ?></td>
            <td rowspan="2" align="center">&nbsp;</td>
          </tr>
          <tr>
            <td height="45" align="center">實際繳費金額</td>
            <td colspan="2" align="center"><?php echo $order_total; ?></td>
          </tr>
        </table>
        <p class="ss_info">此聯請店鋪保存</p>
        <h2>繳費條碼</h2>
        <p class="cord">
          <img src="https://pay-stage.allpay.com.tw/bank/tcbank/cnt/GenerateBarcode?barcode=<?php echo $barcode1;?>" alt="條碼1" /><br />
          <img src="https://pay-stage.allpay.com.tw/bank/tcbank/cnt/GenerateBarcode?barcode=<?php echo $barcode2;?>" alt="條碼2" /><br />
          <img src="https://pay-stage.allpay.com.tw/bank/tcbank/cnt/GenerateBarcode?barcode=<?php echo $barcode3;?>" alt="條碼3" /></p>
        <div class="message margin_t30">
          <h4>注意事項：</h4>
          <ol>
            <li>本繳費單請以雷射印表機列印。</li>
            <li>條碼的入帳時間為3-5個工作日，若您超過入帳時間未收到通知，請與歐付寶聯繫。</li>
            <li>超商條碼的繳費期限為7天，請務必於期限內進行繳款。<br />
              例：08/01的20:15分購買商品，繳費期限為7天，表示8/08 的20:15分前您必須前往繳費。</li>
          </ol>
        </div>
      </div>
      <div class="print"><a href="javascript:void(window.print())" class="btn blue_button">列印本頁</a></div>
    </div>
  </div>
  
  <!-- footer -->
  <div class="footer sell_footer">
    <div class="container">
      <div class="copyright">
        <p class="left">COPYRIGHT 2013 © AllPay <span class="blue">歐付寶 </span>Financial Information Service Co., Ltd</p>
        <ul>
          <li><a href="http://www.allpay.com.tw/About/ProvisionOnMember_20131226_2" target="_blank">會員服務約定條款</a></li>
          <li><a href="http://www.allpay.com.tw/CreditCard/Privacy_20131226" target="_blank">隱私權政策</a></li>
        </ul>
      </div>
      <div class="ser">
        <p>客服電話：（02）2655 - 0115   時間：09:00 - 21:00</p>
      </div>
    </div>
  </div>
  <!-- End: footer --> </div>
<!-- End: wrapper -->
</body>
</html>
