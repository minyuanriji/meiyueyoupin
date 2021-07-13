<?php
echo '  ';

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Postertool_VcShopPage extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		include $this->template();
	}

	public function clear()
	{
		global $_W;
		global $_GPC;
		load()->func('file');
		@rmdirs(IA_ROOT . '/addons/vcshop/data/poster/' . $_W['uniacid']);
		@rmdirs(IA_ROOT . '/addons/vcshop/data/qrcode/' . $_W['uniacid']);
		$acid = pdo_fetchcolumn('SELECT acid FROM ' . tablename('account_wechats') . ' WHERE `uniacid`=:uniacid LIMIT 1', array(':uniacid' => $_W['uniacid']));
		pdo_update('vcshop_poster_qr', array('mediaid' => ''), array('acid' => $acid));
		plog('poster.clear', '清除海报缓存');
		@rmdirs(IA_ROOT . '/addons/vcshop/data/goodscode/' . $_W['uniacid']);
		@rmdirs(IA_ROOT . '/addons/vcshop/data/poster_wxapp/commission/' . $_W['uniacid']);
		@rmdirs(IA_ROOT . '/addons/vcshop/data/poster_wxapp/goods/' . $_W['uniacid']);
		@rmdirs(IA_ROOT . '/addons/vcshop/data/postera/' . $_W['uniacid']);
		@rmdirs(IA_ROOT . ' /addons/vcshop/data/task/poster/' . $_W['uniacid']);
		@rmdirs(IA_ROOT . ' /addons/vcshop/data/upload/exchange/' . $_W['uniacid']);
		show_json(1);
	}
}

?>
