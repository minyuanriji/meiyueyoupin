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

	public function ajaxgettotalprice()
	{
		global $_W;
		$merchid = $_W['merchid'];
		$totals = $this->model->getMerchOrderTotalPrice($merchid);
		show_json(1, $totals);
	}

	public function ajaxgettotalcredit()
	{
		global $_W;
		$merchid = $_W['merchid'];
		$totals = $this->model->getMerchCreditTotalPrice($merchid);
		show_json(1, $totals);
	}
}

?>
