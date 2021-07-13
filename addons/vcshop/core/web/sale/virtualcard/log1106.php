<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Log_VcShopPage extends ComWebPage
{
	public function __construct($_com = 'virtualcard')
	{
		parent::__construct($_com);
	}

	public function main()
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' d.uniacid = :uniacid and d.merchid=0';
		$params = array(':uniacid' => $_W['uniacid']);
		$virtualcardid = intval($_GPC['virtualcardid']);

		if (!empty($virtualcardid)) {
			$virtualcard = pdo_fetch('select * from ' . tablename('vcshop_virtualcard') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $virtualcardid, ':uniacid' => $_W['uniacid']));
			$condition .= ' AND c.id=' . intval($virtualcardid);
		}

		$searchfield = strtolower(trim($_GPC['searchfield']));
		$keyword = trim($_GPC['keyword']);
		if (!empty($searchfield) && !empty($keyword)) {
			if ($searchfield == 'member') {
				$condition .= ' and ( m.realname like :keyword or m.nickname like :keyword or m.mobile like :keyword)';
			}
			else {
				if ($searchfield == 'virtualcard') {
					$condition .= ' and c.virtualcardname like :keyword';
				}
			}

			$params[':keyword'] = '%' . $keyword . '%';
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		if (empty($starttime1) || empty($endtime1)) {
			$starttime1 = strtotime('-1 month');
			$endtime1 = time();
		}

		if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND d.gettime >= :starttime AND d.gettime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		if (!empty($_GPC['time1']['start']) && !empty($_GPC['time1']['end'])) {
			$starttime1 = strtotime($_GPC['time1']['start']);
			$endtime1 = strtotime($_GPC['time1']['end']);
			$condition .= ' AND d.usetime >= :starttime1 AND d.usetime <= :endtime1 ';
			$params[':starttime1'] = $starttime1;
			$params[':endtime1'] = $endtime1;
		}

		if ($_GPC['type'] != '') {
			$condition .= ' AND c.virtualcardtype = :virtualcardtype';
			$params[':virtualcardtype'] = intval($_GPC['type']);
		}

		if ($_GPC['used'] != '') {
			$condition .= ' AND d.used =' . intval($_GPC['used']);
		}

		if ($_GPC['gettype'] != '') {
			$condition .= ' AND d.gettype = :gettype';
			$params[':gettype'] = intval($_GPC['gettype']);
		}

		$sql = 'SELECT d.*, c.virtualcardname,m.nickname,m.avatar,m.realname,m.mobile FROM ' . tablename('vcshop_virtualcard_data') . ' d ' . ' left join ' . tablename('vcshop_virtualcard') . ' c on d.virtualcardid = c.id ' . ' left join ' . tablename('vcshop_member') . ' m on m.openid = d.openid and m.uniacid = d.uniacid ' . (' where  1 and ' . $condition . ' ORDER BY gettime DESC');

		if (empty($_GPC['export'])) {
			$sql .= ' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
		}

		$list = pdo_fetchall($sql, $params);

		foreach ($list as &$row) {
			$row['agent'] = m('member')->getMember($row['agentid']);
			if (!empty($row['openid'])) {
				$row['member'] = m('member')->getMember($row['openid']);
			}
		}

		unset($row);

		if ($_GPC['export'] == 1) {
			ca('virtualcard.log.export');

			foreach ($list as &$row) {
				$row['gettime'] = date('Y-m-d H:i', $row['gettime']);

				if (!empty($row['usetime'])) {
					$row['usetime'] = date('Y-m-d H:i', $row['usetime']);
				}
				else {
					$row['usetime'] = '---';
				}
			}

			$columns = array(
				array('title' => 'ID', 'field' => 'id', 'width' => 12),
				array('title' => '虚拟卡券', 'field' => 'virtualcardname', 'width' => 24),
				array('title' => '类型', 'field' => 'virtualcardstr', 'width' => 12),
				array('title' => '会员信息', 'field' => 'nickname', 'width' => 12),
				array('title' => '姓名', 'field' => 'realname', 'width' => 12),
				array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
				array('title' => 'openid', 'field' => 'openid', 'width' => 24),
				array('title' => '获取方式', 'field' => 'gettypestr', 'width' => 12),
				array('title' => '获取时间', 'field' => 'gettime', 'width' => 12),
				array('title' => '使用时间', 'field' => 'usetime', 'width' => 12),
				array('title' => '使用单号', 'field' => 'ordersn', 'width' => 12)
			);
			m('excel')->export($list, array('title' => '虚拟卡券数据-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
			plog('sale.virtualcard.log.export', '导出虚拟卡券发放记录');
		}

		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('vcshop_virtualcard_data') . ' d ' . ' left join ' . tablename('vcshop_virtualcard') . ' c on d.virtualcardid = c.id ' . ' left join ' . tablename('vcshop_member') . ' m on m.openid = d.openid and m.uniacid = d.uniacid ' . ('where 1 and ' . $condition), $params);
		$pager = pagination2($total, $pindex, $psize);
		include $this->template();
	}
}

?>
