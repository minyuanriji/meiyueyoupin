<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Usesendtask_VcShopPage extends ComWebPage
{
	public function __construct($_com = 'coupon')
	{
		parent::__construct($_com);
	}

	public function main()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$data = m('common')->getPluginset('coupon');
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and cs.uniacid=:uniacid';
		$params = array(':uniacid' => $uniacid);
		$usesendtasks = pdo_fetchall('SELECT cs.*, c.couponname as couponname , c.thumb as thumb,uc.couponname as usecouponname, uc.thumb as usethumb  FROM ' . tablename('vcshop_coupon_usesendtasks') . '  cs left  join  ' . tablename('vcshop_coupon') . '  c on cs.couponid =c.id
                    left  join  ' . tablename('vcshop_coupon') . '  uc on cs.usecouponid =uc.id
                    WHERE 1 ' . $condition . '   LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('vcshop_coupon_usesendtasks') . ' cs WHERE 1 ' . $condition . ' ', $params);
		$pager = pagination($total, $pindex, $psize);
		include $this->template();
	}

	public function opentask()
	{
		$data = m('common')->getPluginset('coupon');
		$data['isopenusesendtask'] = 1;
		m('common')->updatePluginset(array('coupon' => $data));
		header('location: ' . webUrl('sale.coupon.usesendtask'));
	}

	public function closetask()
	{
		$data = m('common')->getPluginset('coupon');
		$data['isopenusesendtask'] = 0;
		m('common')->updatePluginset(array('coupon' => $data));
		header('location: ' . webUrl('sale.coupon.usesendtask'));
	}

	public function add()
	{
		$this->post();
	}

	public function edit()
	{
		$this->post();
	}

	protected function post()
	{
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$id = intval($_GPC['id']);

		if (!empty($id)) {
			$item = pdo_fetch('SELECT *  FROM ' . tablename('vcshop_coupon_usesendtasks') . ' WHERE uniacid = ' . $uniacid . ' and id =' . $id);

			if (!empty($item['usecouponid'])) {
				$usecoupon = pdo_fetch('SELECT id,couponname as title , thumb  FROM ' . tablename('vcshop_coupon') . ' WHERE uniacid = ' . $uniacid . ' and id =' . $item['usecouponid']);
			}

			if (!empty($item['couponid'])) {
				$coupon = pdo_fetch('SELECT id,couponname as title , thumb  FROM ' . tablename('vcshop_coupon') . ' WHERE uniacid = ' . $uniacid . ' and id =' . $item['couponid']);
			}
		}

		if ($_W['ispost']) {
			if (intval($_GPC['sendnum']) < 1) {
				show_json(0, '发送数量不能小于1');
			}

			if (intval($_GPC['num']) < 0) {
				show_json(0, '剩余数量不能小于0');
			}

			$data = array('uniacid' => $uniacid, 'usecouponid' => intval($_GPC['usecouponid']), 'couponid' => intval($_GPC['couponid']), 'starttime' => strtotime($_GPC['sendtime']['start']), 'endtime' => strtotime($_GPC['sendtime']['end']) + 86399, 'sendnum' => intval($_GPC['sendnum']), 'num' => intval($_GPC['num']), 'status' => intval($_GPC['status']));

			if (!empty($id)) {
				pdo_update('vcshop_coupon_usesendtasks', $data, array('id' => $id));
				plog('coupon.usesendtask.edit', '修改优惠券发送任务 ID: ' . $id);
			}
			else {
				pdo_insert('vcshop_coupon_usesendtasks', $data);
				$id = pdo_insertid();
				plog('coupon.usesendtask.add', '添加优惠券发送任务 ID: ' . $id);
			}

			show_json(1, array('url' => webUrl('sale.coupon.usesendtask')));
		}

		if (empty($item['starttime'])) {
			$item['starttime'] = time();
		}

		if (empty($item['endtime'])) {
			$item['endtime'] = time() + 60 * 60 * 24 * 7;
		}

		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$item = pdo_fetch('SELECT * FROM ' . tablename('vcshop_coupon_usesendtasks') . ' WHERE id  = :id  and uniacid=:uniacid ', array(':id' => $id, ':uniacid' => $_W['uniacid']));

		if (!empty($item)) {
			pdo_delete('vcshop_coupon_usesendtasks', array('id' => $id, 'uniacid' => $_W['uniacid']));
		}

		show_json(1);
	}
}

?>
