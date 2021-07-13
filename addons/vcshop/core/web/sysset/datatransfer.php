<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Datatransfer_VcShopPage extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$item = pdo_fetch('select dt.*,w.name from ' . tablename('vcshop_datatransfer') . ' dt left join ' . tablename('account_wechats') . ' w on w.uniacid = dt.touniacid where dt.fromuniacid =:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
		$senduniacid = $_GPC['acid'];
		$isopen = $_GPC['isopen'];

		if ($_W['ispost']) {
			if (!empty($isopen)) {
				pdo_delete('vcshop_datatransfer', array('fromuniacid' => $_W['uniacid']));
				show_json(1, array('url' => referer()));
			}

			$data = array('fromuniacid' => $_W['uniacid'], 'touniacid' => $senduniacid, 'status' => 1);
			pdo_insert('vcshop_datatransfer', $data);
			$tables = array('vcshop_category', 'vcshop_carrier', 'vcshop_adv', 'vcshop_feedback', 'vcshop_form', 'vcshop_form_category', 'vcshop_gift', 'vcshop_goods', 'vcshop_goods_comment', 'vcshop_goods_group', 'vcshop_goods_label', 'vcshop_goods_labelstyle', 'vcshop_goods_option', 'vcshop_goods_param', 'vcshop_goods_spec', 'vcshop_goods_spec_item', 'vcshop_member_address', 'vcshop_member_printer', 'vcshop_member_printer_template', 'vcshop_member_group', 'vcshop_member_level', 'vcshop_member_log', 'mc_credits_record', 'vcshop_commission_apply', 'vcshop_commission_bank', 'vcshop_commission_level', 'vcshop_commission_log', 'vcshop_commission_rank', 'vcshop_commission_repurchase', 'vcshop_commission_shop', 'vcshop_order', 'vcshop_order_comment', 'vcshop_order_goods', 'vcshop_order_peerpay', 'vcshop_order_peerpay_payinfo', 'vcshop_order_refund');

			foreach ($tables as $table) {
				pdo_update($table, array('uniacid' => $senduniacid), array('uniacid' => $_W['uniacid']));
			}

			show_json(1, array('url' => referer()));
		}

		include $this->template();
	}
}

?>
