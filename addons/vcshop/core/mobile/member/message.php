<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Message_VcShopPage extends MobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$_GPC['type'] = empty(intval($_GPC['type'])) ? 1 : intval($_GPC['type']);
		include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$uidinfo = M('member')->getMember($_W['openid']);
		$type = intval($_GPC['type']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and mid=:mid and uniacid=:uniacid and type=:type';
		$params = array(':uniacid' => $_W['uniacid'], ':mid' => $uidinfo['id'], ':type' => intval($_GPC['type']));
		$list = pdo_fetchall('select * from ' . tablename('vcshop_message_notice_log') . (' where 1 ' . $condition . ' order by create_time desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_message_notice_log') . (' where 1 ' . $condition), $params);

		foreach ($list as &$row) {
			$row['create_time'] = date('Y-m-d H:i', $row['create_time']);
		}

		unset($row);
		show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $psize));
	}
}

?>
