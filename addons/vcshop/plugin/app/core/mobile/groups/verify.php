<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require_once VCSHOP_PLUGIN . 'app/core/page_mobile.php';
class Verify_VcShopPage extends AppMobilePage
{
	public function qrcode()
	{
		global $_W;
		global $_GPC;
		$orderid = intval($_GPC['id']);
		$verifycode = $_GPC['verifycode'];
		$query = array('id' => $orderid, 'verifycode' => $verifycode);
		$url = mobileUrl('groups/verify/detail', $query, true);
		return app_json(array('url' => m('qrcode')->createQrcode($url)));
	}
}

?>
