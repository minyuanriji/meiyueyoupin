<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'merch/core/inc/page_merch.php';
class Sale_analysis_VcShopPage extends MerchWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		function sale_analysis_count($sql)
		{
			$c = pdo_fetchcolumn($sql);
			return intval($c);
		}
		$member_count = sale_analysis_count('select count(*) from ' . tablename('vcshop_member') . (' where uniacid=' . $_W['uniacid'] . ' and  openid in ( SELECT distinct openid from ') . tablename('vcshop_order') . ('   WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $_W['merchid'] . '\'  )'));
		$orderprice = sale_analysis_count('SELECT sum(price) FROM ' . tablename('vcshop_order') . (' WHERE  status>=1 and uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $_W['merchid'] . '\' '));
		$ordercount = sale_analysis_count('SELECT count(*) FROM ' . tablename('vcshop_order') . (' WHERE status>=1 and uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $_W['merchid'] . '\''));
		$viewcount = sale_analysis_count('SELECT sum(viewcount) FROM ' . tablename('vcshop_goods') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $_W['merchid'] . '\''));
		$member_buycount = sale_analysis_count('select count(*) from ' . tablename('vcshop_member') . (' where uniacid=' . $_W['uniacid'] . ' and  openid in ( SELECT distinct openid from ') . tablename('vcshop_order') . ('   WHERE uniacid = \'' . $_W['uniacid'] . '\' and merchid=\'' . $_W['merchid'] . '\' and status>=1 )'));
		include $this->template('statistics/sale_analysis');
	}
}

?>
