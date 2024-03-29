<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'merch/core/inc/page_merch.php';
class Log_VcShopPage extends MerchWebPage
{
	public function main()
	{
		global $_W;

		if (mcv('creditshop.log.exchange')) {
			header('location: ' . webUrl('creditshop/log/exchange'));
		}
		else if (mcv('creditshop.log.draw')) {
			header('location: ' . webUrl('creditshop/log/draw'));
		}
		else if (mcv('creditshop.log.order')) {
			header('location: ' . webUrl('creditshop/log/order'));
		}
		else if (mcv('creditshop.log.convey')) {
			header('location: ' . webUrl('creditshop/log/convey'));
		}
		else if (mcv('creditshop.log.finish')) {
			header('location: ' . webUrl('creditshop/log/finish'));
		}
		else if (mcv('creditshop.log.verifying')) {
			header('location: ' . webUrl('creditshop/log/verifying'));
		}
		else if (mcv('creditshop.log.verifyover')) {
			header('location: ' . webUrl('creditshop/log/verifyover'));
		}
		else if (mcv('creditshop.log.verify')) {
			header('location: ' . webUrl('creditshop/log/verify'));
		}
		else {
			header('location: ' . webUrl('creditshop'));
		}

		exit();
	}

	public function exchange()
	{
		$this->getData(0);
	}

	public function draw()
	{
		$this->getData(1);
	}

	public function order()
	{
		$this->getData(2);
	}

	public function convey()
	{
		$this->getData(3);
	}

	public function finish()
	{
		$this->getData(4);
	}

	public function verifying()
	{
		$this->getData(5);
	}

	public function verifyover()
	{
		$this->getData(6);
	}

	public function verify()
	{
		$this->getData(7);
	}

	protected function getData($type)
	{
		global $_W;
		global $_GPC;
		$type = intval($type);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and log.uniacid=:uniacid and g.merchid = ' . $_W['merchid'] . ' ';
		if (empty($type) || $type == 1) {
			$condition .= ' and log.status>0 and g.type=' . $type;
		}
		else if ($type == 2) {
			$condition .= ' and log.status=2 and log.time_send=0 and g.isverify = 0 ';
		}
		else if ($type == 3) {
			$condition .= ' and log.status=3 and log.time_send<>0 and log.time_finish = 0 and g.isverify = 0 ';
		}
		else if ($type == 4) {
			$condition .= ' and log.time_finish>0 and log.time_send>0 and log.status > 0 and g.isverify = 0 ';
		}
		else if ($type == 5) {
			$condition .= ' and g.isverify > 0 and log.status=2 ';
		}
		else if ($type == 6) {
			$condition .= ' and g.isverify > 0 and log.status=3 ';
		}
		else {
			if ($type == 7) {
				$condition .= ' and g.isverify > 0 and log.status > 0 ';
			}
		}

		$params = array(':uniacid' => $_W['uniacid']);
		$set = m('common')->getPluginset('creditshop');
		$searchfield = strtolower(trim($_GPC['searchfield']));
		$keyword = trim($_GPC['keyword']);
		if (!empty($searchfield) && !empty($keyword)) {
			if ($searchfield == 'member') {
				$condition .= ' and ( m.realname like :keyword or m.nickname like :keyword  or m.mobile like :keyword) ';
			}
			else if ($searchfield == 'address') {
				$condition .= ' and ( log.realname like :keyword  or log.mobile like :keyword  or a.realname like :keyword or a.mobile like :keyword  ) ';
			}
			else if ($searchfield == 'logno') {
				$condition .= ' and log.logno like :keyword';
			}
			else if ($searchfield == 'eno') {
				$condition .= ' and log.eno like :keyword';
			}
			else if ($searchfield == 'goods') {
				$condition .= ' and g.title like :keyword';
			}
			else if ($searchfield == 'store') {
				$condition .= ' and  s.storename like :keyword';
			}
			else {
				if ($searchfield == 'express') {
					$condition .= ' and  log.expresssn like :keyword';
				}
			}

			$params[':keyword'] = '%' . $keyword . '%';
		}

		if ($_GPC['status'] != '') {
			$condition .= ' and log.status=' . intval($_GPC['status']);
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND log.createtime >= :starttime AND log.createtime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		$sql = 'select log.*, m.nickname,m.avatar,m.realname as mrealname,m.mobile as mmobile, g.title,g.thumb,g.thumb,g.credit,g.money,g.type as goodstype,g.isverify,
			g.goodstype as iscoupon,s.storename,s.address as storeaddress,g.dispatch,g.goodstype,g.type,g.merchid as gmerchid
			from ' . tablename('vcshop_creditshop_log') . ' log ' . ' left join ' . tablename('vcshop_member') . ' m on m.openid = log.openid and m.uniacid=log.uniacid' . ' left join ' . tablename('vcshop_member_address') . ' a on a.id = log.addressid' . ' left join ' . tablename('vcshop_store') . ' s on s.id = log.storeid' . ' left join ' . tablename('vcshop_creditshop_goods') . ' g on g.id = log.goodsid' . (' where 1 ' . $condition . ' ORDER BY log.createtime desc ');

		if (empty($_GPC['export'])) {
			$sql .= ' limit ' . ($pindex - 1) * $psize . ',' . $psize;
		}

		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('select count(log.id) from' . tablename('vcshop_creditshop_log') . ' log ' . ' left join ' . tablename('vcshop_member') . ' m on m.openid = log.openid and m.uniacid=log.uniacid' . ' left join ' . tablename('vcshop_store') . ' s on s.id = log.storeid' . ' left join ' . tablename('vcshop_member_address') . ' a on a.id = log.addressid' . ' left join ' . tablename('vcshop_creditshop_goods') . ' g on g.id = log.goodsid' . (' where 1 ' . $condition), $params);

		foreach ($list as &$row) {
			$row['address'] = array();

			if (!empty($row['addressid'])) {
				$row['address'] = pdo_fetch('select realname,mobile,address,province,city,area from ' . tablename('vcshop_member_address') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $row['addressid'], ':uniacid' => $_W['uniacid']));
			}
			else {
				if (0 < intval($row['gmerchid'])) {
					$stores = pdo_fetch('select * from ' . tablename('vcshop_merch_store') . ' where id=:id and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':id' => $row['storeid'], ':uniacid' => $_W['uniacid'], ':merchid' => $row['gmerchid']));
				}
				else {
					$stores = pdo_fetch('select * from ' . tablename('vcshop_store') . ' where id=:id and uniacid=:uniacid and status=1 and type in(2,3)', array(':id' => $row['storeid'], ':uniacid' => $_W['uniacid']));
				}

				$row['address'] = array('carrier_realname' => $row['realname'], 'carrier_mobile' => $row['mobile'], 'carrier_storename' => $stores['storename'], 'carrier_address' => $row['storeaddress']);
			}

			$row['address']['logid'] = $row['id'];
			$row['address']['isverify'] = $row['isverify'];
			$row['address']['storeid'] = $row['storeid'];
			$row['address']['addressid'] = $row['addressid'];

			if ($row['optionid']) {
				$option = pdo_fetch('select title from ' . tablename('vcshop_creditshop_option') . ' where id = ' . $row['optionid'] . ' and uniacid = ' . $_W['uniacid'] . ' ');
				$row['optiontitle'] = $option['title'];
			}
			else {
				$row['optiontitle'] = '';
			}

			$canexchange = true;
			$verifynum = pdo_fetchcolumn('select count(1) from ' . tablename('vcshop_creditshop_verify') . ' where logid = ' . $row['id'] . ' and uniacid = ' . $_W['uniacid'] . ' ');

			if ($row['status'] == 2) {
				if (empty($row['paystatus']) && $row['creditpay'] != 1) {
					$canexchange = false;
				}

				if (empty($row['dispatchstatus']) && 0 < $row['dispatch']) {
					$canexchange = false;
				}

				if ($row['isverify'] == 1) {
					if (empty($row['storeid'])) {
						$canexchange = false;
					}
				}
			}
			else {
				if ($row['status'] == 3 && 1 < $row['verifynum']) {
					if ($row['verifynum'] <= $verifynum) {
						$canexchange = false;
					}
				}
				else {
					$canexchange = false;
				}
			}

			$row['canexchange'] = $canexchange;
		}

		unset($row);

		if ($_GPC['export'] == 1) {
			mca('creditshop.log.export');

			if (empty($type)) {
				plog('creditshop.log.export', '导出兑换订单');
			}
			else {
				plog('creditshop.log.export', '导出抽奖订单');
			}

			foreach ($list as &$row) {
				$row['typestr'] = empty($row['type']) ? '兑换' : '抽奖';
				$row['verifystr'] = empty($row['isverify']) ? '快递' : '线下';
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
				$row['user1'] = empty($row['realname']) ? $row['mrealname'] : $row['realname'];
				$row['user2'] = empty($row['mobile']) ? $row['mmobile'] : $row['mobile'];

				if (!empty($row['addressid'])) {
					$row['addressinfo_province'] = $row['address']['province'];
					$row['addressinfo_city'] = $row['address']['city'];
					$row['addressinfo_area'] = $row['address']['area'];
					$row['addressinfo_address'] = $row['address']['address'];
					$row['addressinfo_realname'] = $row['address']['realname'];
					$row['addressinfo_mobile'] = $row['address']['mobile'];
				}
				else {
					$row['storeinfo_storename'] = $row['address']['carrier_storename'];
					$row['storeinfo_address'] = $row['address']['carrier_address'];
				}

				if (empty($type)) {
					if ($row['status'] == 2) {
						$row['statusstr'] = '已兑换';
					}
					else {
						if ($row['status'] == 3) {
							$row['statusstr'] = '已兑奖';
						}
					}
				}
				else if ($row['status'] == 1) {
					$row['statusstr'] = '未中奖';
				}
				else if ($row['status'] == 2) {
					$row['statusstr'] = '已中奖';
				}
				else {
					if ($row['status'] == 3) {
						$row['statusstr'] = '已兑奖';
					}
				}

				if ($row['paytype'] == -1) {
					$row['paystr'] = '无需支付';
				}
				else if ($row['paytype'] == 0) {
					if ($row['paystatus'] == 0) {
						$row['paystr'] = '余额未支付';
					}
					else {
						$row['paystr'] = '余额已支付';
					}
				}
				else if ($row['paytype'] == 1) {
					if ($row['paystatus'] == 0) {
						$row['paystr'] = '微信未支付';
					}
					else {
						$row['paystr'] = '微信已支付';
					}
				}
				else {
					if ($row['paytype'] == 2) {
						if ($row['paystatus'] == 0) {
							$row['paystr'] = '支付宝未支付';
						}
						else {
							$row['paystr'] = '支付宝已支付';
						}
					}
				}

				if ($row['dispatchstatus'] == -1) {
					$row['dispatchstr'] = '无需支付';
				}
				else if ($row['dispatchstatus'] == 0) {
					$row['dispatchstr'] = '未支付';
				}
				else {
					if ($row['dispatchstatus'] == 1) {
						$row['dispatchstr'] = '已支付';
					}
				}
			}

			unset($row);
			$columns = array(
				array('title' => 'ID', 'field' => 'id', 'width' => 12),
				array('title' => '活动编号', 'field' => 'logno', 'width' => 24),
				array('title' => '商品名称', 'field' => 'title', 'width' => 12),
				array('title' => '商品数量', 'field' => 'goods_num', 'width' => 12),
				array('title' => '商品规格', 'field' => 'optiontitle', 'width' => 20),
				array('title' => '活动类型', 'field' => 'typestr', 'width' => 12),
				array('title' => '兑换方式', 'field' => 'verifystr', 'width' => 12),
				array('title' => '联系人', 'field' => 'user1', 'width' => 12),
				array('title' => '联系电话', 'field' => 'user2', 'width' => 12),
				array('title' => '邮寄地址', 'field' => 'addressinfo_province', 'width' => 12),
				array('title' => '', 'field' => 'addressinfo_city', 'width' => 12),
				array('title' => '', 'field' => 'addressinfo_area', 'width' => 12),
				array('title' => '', 'field' => 'addressinfo_address', 'width' => 24),
				array('title' => '', 'field' => 'addressinfo_realname', 'width' => 12),
				array('title' => '', 'field' => 'addressinfo_mobile', 'width' => 12),
				array('title' => '兑换门店', 'field' => 'storeinfo_storename', 'width' => 12),
				array('title' => '', 'field' => 'storeinfo_address', 'width' => 24),
				array('title' => '参与状态', 'field' => 'statusstr', 'width' => 12),
				array('title' => '支付状态', 'field' => 'paystr', 'width' => 12),
				array('title' => '快递状态', 'field' => 'dispatchstr', 'width' => 12),
				array('title' => '参与时间', 'field' => 'createtime', 'width' => 12)
			);
			m('excel')->export($list, array('title' => (empty($type) ? '兑换' : '抽奖') . '订单数据-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
		}

		$pager = pagination($total, $pindex, $psize);
		include $this->template('creditshop/log/index');
	}

	public function detail()
	{
		global $_W;
		global $_GPC;
		$merchid = intval($_W['merchid']);
		$id = intval($_GPC['id']);
		$log = pdo_fetch('select * from ' . tablename('vcshop_creditshop_log') . ' where id=:id and uniacid=:uniacid and merchid = :merchid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid']));

		if (empty($log)) {
			$this->message('兑换记录不存在!', referer(), 'error');
		}

		$member = m('member')->getMember($log['openid']);
		$goods = p('creditshop')->getGoods($log['goodsid'], $member, $log['optionid']);
		$goods['credit'] = $log['credit'];
		$goods['money'] = $log['money'];

		if (empty($goods['id'])) {
			$this->message('商品记录不存在!', referer(), 'error');
		}

		$set = m('common')->getPluginset('creditshop');
		$canexchange = true;

		if ($log['status'] == 2) {
			if (empty($log['paystatus'])) {
				$canexchange = false;
			}

			if (empty($log['dispatchstatus'])) {
				$canexchange = false;
			}

			if ($goods['isverify'] == 1 && empty($log['storeid'])) {
				$canexchange = false;
			}
		}
		else {
			$canexchange = false;
		}

		$log['canexchange'] = $canexchange;

		if ($goods['isverify']) {
			if (!empty($log['storeid'])) {
				if (0 < $merchid) {
					$stores = pdo_fetch('select * from ' . tablename('vcshop_merch_store') . ' where id=:id and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':id' => $log['storeid'], ':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
				}
				else {
					$stores = pdo_fetch('select * from ' . tablename('vcshop_store') . ' where id=:id and uniacid=:uniacid and status=1 and type in(2,3)', array(':id' => $log['storeid'], ':uniacid' => $_W['uniacid']));
				}
			}
		}
		else {
			$address = iunserializer($log['address']);

			if (!is_array($address)) {
				$address = pdo_fetch('select realname,mobile,address,province,city,area from ' . tablename('vcshop_member_address') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $log['addressid'], ':uniacid' => $_W['uniacid']));
			}
		}

		include $this->template();
	}

	public function doexchange()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$verifynum = intval($_GPC['verifynum']);
		$log = pdo_fetch('select * from ' . tablename('vcshop_creditshop_log') . ' where id=:id and uniacid=:uniacid and merchid = :merchid limit 1', array(':id' => $id, ':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid']));
		$verifytotal = pdo_fetchcolumn('select count(1) from ' . tablename('vcshop_creditshop_verify') . ' where logid=:logid and uniacid=:uniacid', array(':logid' => $id, ':uniacid' => $_W['uniacid']));

		if (empty($log)) {
			show_json(0, '兑换记录不存在!');
		}

		if (empty($log['status'])) {
			show_json(0, '无效兑换记录!');
		}

		$log['verifynum'] = $log['verifynum'] - $verifytotal ? $log['verifynum'] - $verifytotal : 0;

		if ($log['verifynum'] - $verifytotal < $verifynum) {
			show_json(0, '此记录剩余核销 ' . $log['verifynum'] . ' 次');
		}

		$member = m('member')->getMember($log['openid']);
		$goods = p('creditshop')->getGoods($log['goodsid'], $member);

		if (empty($goods['id'])) {
			show_json(0, '商品记录不存在!');
		}

		if (!empty($goods['type'])) {
			if ($log['status'] <= 1) {
				show_json(0, '未中奖，不能兑换!');
			}
		}

		$store = false;
		$address = false;

		if (!empty($goods['isverify'])) {
			if (!empty($log['storeid'])) {
				$store = pdo_fetch('select id,storename,address  from ' . tablename('vcshop_store') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $log['storeid'], ':uniacid' => $_W['uniacid']));
			}
		}
		else {
			$address = iunserializer($log['address']);

			if (!is_array($address)) {
				$address = pdo_fetch('select realname,mobile,address,province,city,area from ' . tablename('vcshop_member_address') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $log['addressid'], ':uniacid' => $_W['uniacid']));
			}
		}

		if (0 < $goods['money'] && empty($log['paystatus'])) {
			show_json(0, '未支付，无法进行兑换!');
		}

		if (0 < $goods['dispatch'] && empty($log['dispatchstatus'])) {
			show_json(0, '未支付运费，无法进行兑换!');
		}

		if ($_W['ispost']) {
			if ($goods['goodstype'] == 0) {
				if (0 < $log['verifynum'] && $verifynum <= 0 && 0 < $goods['isverify']) {
					show_json(0, '最少兑换1次!');
				}

				if ($log['verifynum'] - $verifytotal <= 0 && 0 < $goods['isverify']) {
					show_json(0, '此记录已兑换过了!');
				}

				pdo_update('vcshop_creditshop_log', array('status' => 2, 'usetime' => time(), 'time_send' => time(), 'expresscom' => $_GPC['expresscom'], 'expresssn' => $_GPC['expresssn'], 'express' => $_GPC['express']), array('id' => $id));

				if ($goods['verifytype'] == 0) {
					pdo_update('vcshop_creditshop_log', array('status' => 3, 'usetime' => time(), 'verifyopenid' => 'system', 'time_finish' => time()), array('id' => $id));
					$data = array('uniacid' => $_W['uniacid'], 'openid' => $log['openid'], 'logid' => $id, 'verifycode' => $log['eno'], 'storeid' => $log['storeid'], 'verifier' => 'system', 'isverify' => 1, 'verifytime' => time());
					pdo_insert('vcshop_creditshop_verify', $data);
				}
				else {
					if ($goods['verifytype'] == 1) {
						if ($log['status'] != 3) {
							pdo_update('vcshop_creditshop_log', array('status' => 3, 'usetime' => time(), 'verifyopenid' => 'system', 'time_finish' => time()), array('id' => $id));
						}

						$i = 1;

						while ($i <= $verifynum) {
							$data = array('uniacid' => $_W['uniacid'], 'openid' => $log['openid'], 'logid' => $id, 'verifycode' => $log['eno'], 'storeid' => $log['storeid'], 'verifier' => 'system', 'isverify' => 1, 'verifytime' => time());
							pdo_insert('vcshop_creditshop_verify', $data);
							++$i;
						}
					}
				}
			}

			p('creditshop')->sendMessage($id);
			plog('creditshop.log.doexchange', '积分商城兑换 兑换记录ID: ' . $id);
			show_json(1);
		}

		include $this->template('creditshop/log/exchange');
	}
}

?>
