<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require_once VCSHOP_PLUGIN . 'app/core/page_mobile.php';
class Fullback_VcShopPage extends AppMobilePage
{
	public function get_list()
	{
		global $_W;
		global $_GPC;
		$isfullback = intval($_GPC['type']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and fl.openid=:openid and fl.uniacid=:uniacid and fl.isfullback=:isfullback';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':isfullback' => $isfullback);
		$list = array();
		$list = pdo_fetchall('select fl.*,g.thumb,g.title from ' . tablename('vcshop_fullback_log') . ' as fl
            left join ' . tablename('vcshop_goods') . ' as g on g.id = fl.goodsid
            left join ' . tablename('vcshop_order_goods') . (' as og on og.orderid = fl.orderid and og.goodsid = fl.goodsid 
            where 1 ' . $condition . ' group by fl.id order by fl.createtime desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(1) from ' . tablename('vcshop_fullback_log') . (' as fl
            where 1 ' . $condition . ' order by fl.createtime desc '), $params);

		foreach ($list as &$row) {
			$row['createtime'] = date('Y/m/d H:i:s', $row['createtime']);
			$row['price'] = price_format($row['price'], 2);
			$row['priceevery'] = price_format($row['priceevery'], 2);

			if (0 < $row['optionid']) {
				$optionname = pdo_get('vcshop_order_goods', array('optionid' => $row['optionid']), array('optionname'));
				$row['optionname'] = $optionname['optionname'];
			}

			if ($row['fullbackday'] < $row['day']) {
				$row['surplusday'] = $row['day'] - $row['fullbackday'];
				$row['surplusprice'] = $row['priceevery'] * $row['fullbackday'];
			}
			else {
				$row['surplusday'] = 0;
				$row['surplusprice'] = $row['price'];
			}

			$row = set_medias($row, array('thumb'));
		}

		unset($row);
		return app_json(array('list' => $list, 'total' => $total, 'pagesize' => $psize));
	}

	public function get_all()
	{
		global $_W;
		global $_GPC;
		$_GPC['orderid'] = intval($_GPC['orderid']);
		$orderid = $_GPC['orderid'];
		$condition = ' where uniacid=:uniacid and openid =:openid ';
		$params = array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid']);

		if ($orderid) {
			$params['orderid'] = $orderid;
			$condition .= ' and orderid=:orderid ';
		}

		$info = pdo_fetchall('SELECT * FROM' . tablename('vcshop_fullback_log') . $condition, $params);
		$alldata = array();
		$allday = array();
		$fullbackday = array();
		$createtime = array();
		if (is_array($info) && !empty($info)) {
			foreach ($info as &$value) {
				$alldata['allprice'] += $value['price'];
				$alldata['hasprice'] += $value['priceevery'] * $value['fullbackday'];
				array_push($createtime, $value['createtime']);
				array_push($allday, $value['day']);
				array_push($fullbackday, $value['fullbackday']);
			}

			unset($value);
		}

		$alldata['day'] = max($allday);
		$alldata['fullbackday'] = max($fullbackday);
		$alldata['createtime'] = min($createtime);
		$alldata['createtime'] = date('Y/m/d h:i:s', $alldata['createtime']);
		return app_json(array('info' => $alldata));
	}
}

?>
