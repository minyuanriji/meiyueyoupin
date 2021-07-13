<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'merch/core/inc/page_merch.php';
class Selecticon_VcShopPage extends MerchWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		include $this->template();
	}
}

?>
