<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_VcShopPage extends ComWebPage
{
	public function __construct($_com = 'coupon')
	{
		parent::__construct($_com);
	}

	public function main()
	{
		global $_W;
		global $_GPC;
		
		include $this->template();
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
		
		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		// if (empty($id)) {
		// 	$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		// }

		// $items = pdo_fetchall('SELECT id,couponname FROM ' . tablename('vcshop_coupon') . (' WHERE id in( ' . $id . ' ) and merchid=0 AND uniacid=') . $_W['uniacid']);
		// $key = 'vcshop:com:coupon:' . $id;
		// $rule = pdo_fetch('select * from ' . tablename('rule') . ' where uniacid=:uniacid and module=:module and name=:name  limit 1', array(':uniacid' => $_W['uniacid'], ':module' => 'vcshop', ':name' => $key));

		// if (!empty($rule)) {
		// 	pdo_delete('rule_keyword', array('rid' => $rule['id']));
		// 	pdo_delete('rule', array('id' => $rule['id']));
		// }

		// foreach ($items as $item) {
		// 	pdo_delete('vcshop_coupon', array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
		// 	pdo_delete('vcshop_coupon_data', array('couponid' => $item['id'], 'uniacid' => $_W['uniacid']));
		// 	plog('sale.coupon.delete', '删除优惠券 ID: ' . $id . '  <br/>优惠券名称: ' . $item['couponname'] . ' ');
		// }

		// show_json(1, array('url' => webUrl('sale/coupon')));
	}

	public function displayorder()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$displayorder = intval($_GPC['value']);
		$items = pdo_fetchall('SELECT id,couponname FROM ' . tablename('vcshop_coupon') . (' WHERE id in( ' . $id . ' ) and merchid=0 AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('vcshop_coupon', array('displayorder' => $displayorder), array('id' => $id));
			plog('sale.coupon.displayorder', '修改优惠券排序 ID: ' . $item['id'] . ' 名称: ' . $item['couponname'] . ' 排序: ' . $displayorder . ' ');
		}

		show_json(1);
	}


}

?>
