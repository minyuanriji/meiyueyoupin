<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'merch/core/inc/page_merch.php';
class Index_VcShopPage extends MerchWebPage
{
	public function main()
	{
		global $_W;
		include $this->template();
	}
}

?>
