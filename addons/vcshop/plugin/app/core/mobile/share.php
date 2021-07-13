<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require_once VCSHOP_PLUGIN . 'app/core/page_mobile.php';
class Share_VcShopPage extends AppMobilePage
{
	public function main()
	{
		global $_GPC;
		echo '以下是分享内容: ';
		print_r($_GET);
	}
}

?>
