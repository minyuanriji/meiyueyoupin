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

	protected function virtualcardData($st, $index)
	{
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		// 20201113 lin
		if ($_GPC["export"] == 1) {
            $pindex = max(1, intval($index));
            $psize = 200;
        }

        if ($st == "main") {
            $st = "";
        } else {
            $st = "." . $st;
        }

		$condition = ' d.uniacid = :uniacid and d.status in (0,1) ';
		$params = array(':uniacid' => $_W['uniacid']);
		$virtualcardid = intval($_GPC['id']);
		$name = '';
		if (!empty($virtualcardid)) {
			$virtualcard = pdo_fetch('select * from ' . tablename('vcshop_virtualcard') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $virtualcardid, ':uniacid' => $_W['uniacid']));
			$name = $virtualcard['virtualcardname'];
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

		$sql = 'SELECT d.*, c.virtualcardname,m.nickname,m.avatar,m.realname,m.mobile FROM ' . tablename('vcshop_virtualcard_data') . ' d ' . ' left join ' . tablename('vcshop_virtualcard') . ' c on d.virtualcardid = c.id ' . ' left join ' . tablename('vcshop_member') . ' m on m.openid = d.openid and m.uniacid = d.uniacid ' . (' where  1 and ' . $condition . ' ORDER BY gettime DESC,agentid DESC');

		// if (empty($_GPC['export'])) {
			$sql .= ' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
		// }

		$list = pdo_fetchall($sql, $params);

		foreach ($list as &$row) {
			$row['agent'] = m('member')->getMember($row['agentid']);
			$row['agentnickname'] = $row['agent']['nickname'];
			if (!empty($row['openid'])) {
				$row['member'] = m('member')->getMember($row['openid']);
			}
			$url = mobileUrl('sale/virtualcard/exchange', array('id' => $row['id']), true);
			// $row['qrcode'] = m('qrcode')->createQrcode($url);
			$row['qrcode'] = $url;
		}

		unset($row);
		set_time_limit(0); //20201113 lin
		@ini_set("memory_limit", "256M");
		if ($_GPC['export'] == 1) {
			ca('sale.virtualcard.log.export');

			foreach ($list as &$row) {
				if (!empty($row['gettime'])) {
					$row['gettime'] = date('Y-m-d H:i', $row['gettime']);
				}else{
					$row['gettime'] = '---';
				}

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
				array('title' => '归属会员', 'field' => 'agentnickname', 'width' => 12),
				array('title' => '会员信息', 'field' => 'nickname', 'width' => 12),
				array('title' => '姓名', 'field' => 'realname', 'width' => 12),
				array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
				array('title' => '获取时间', 'field' => 'gettime', 'width' => 12),
				array('title' => '使用时间', 'field' => 'usetime', 'width' => 12),
				array('title' => '兑换卡号', 'field' => 'cardsn', 'width' => 12),
				array('title' => '兑换码', 'field' => 'cardkey', 'width' => 12),
			);
			// m('excel')->export($list, array('title' => '虚拟卡券数据-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
			// plog('sale.virtualcard.log.export', '导出虚拟卡券发放记录');
			// 20201113 lin
			if (!empty($list)) {
			    $exflag = false;
			} else {
			    $exflag = true;
			}
			m("excel")->exportCSV($list, array("title" => "卡券数据-" . $name . "-" , "columns" => $columns), VCSHOP_DATA . "virtualcard/", $index, $exflag);
			plog('sale.virtualcard.log.export', '导出虚拟卡券发放记录');
			unset($list);
			if (!$exflag) {
			    $pindex++;
			    $this->virtualcardData($st, $pindex);
			}
			exit;
		}

		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('vcshop_virtualcard_data') . ' d ' . ' left join ' . tablename('vcshop_virtualcard') . ' c on d.virtualcardid = c.id ' . ' left join ' . tablename('vcshop_member') . ' m on m.openid = d.openid and m.uniacid = d.uniacid ' . ('where 1 and ' . $condition), $params);
		$pager = pagination2($total, $pindex, $psize);
		include $this->template();
	}

	// 20201113 lin
	public function main()
    {
        global $_W;
        global $_GPC;
        $list = $this->virtualcardData("main", 1);
    }

	public function togglestatus()
	{
		global $_W;
		global $_GPC;

		$id = intval($_GPC['id']);
		$status = intval($_GPC['status']);

		$virtualcard_item = pdo_fetch('select * from ' . tablename('vcshop_virtualcard_data') . ' where uniacid = :uniacid and id = :id',array(':uniacid'=>$_W['uniacid'],':id'=>$id));

		if (empty($virtualcard_item)) {
			show_json(0, '操作失败！');
		}

		if ($virtualcard_item['status'] == $status) {
			show_json(0, '请勿重复操作！');
		}

		pdo_update('vcshop_virtualcard_data', array('status' => $status), array('id' => $id));
		plog('sale.virtualcard.log', '修改虚拟卡券记录 ID: ' . $id . '  <br/>虚拟卡券序号: ' . $virtualcard_item['cardsn'] . ' ');
		show_json(1, '操作成功！');
	}

	public function batchange()
	{
		global $_W;
		global $_GPC;

		if ($_W['ispost']) {
			$ids = $_GPC['ids'];
			$agentid = $_GPC['agentid'];

			if (empty($ids) || !is_array($ids)) {
				show_json(0, '请选择要操作的卡券');
			}

			if (empty($agentid)) {
				show_json(0, '请选择卡券归属人');
			}

			$ids = array_filter($ids);
			$idsstr = implode(',', $ids);
			$loginfo = '批量修改';

			$agentInfo = m('member')->getMember($agentid);
			if (empty($agentInfo) || empty($agentInfo['status']) || empty($agentInfo['isagent'])) {
				show_json(0, '此卡券归属人有误');
			}

			$changeids = array();
			$datas = pdo_fetchall('select id,agentid,used from ' . tablename('vcshop_virtualcard_data') . (' WHERE id in( ' . $idsstr . ' ) AND uniacid=') . $_W['uniacid']);

			foreach ($datas as $key => $value) {
				if ($value['used'] == 1) {
					continue;
				}
				pdo_update('vcshop_virtualcard_data', array('agentid' => $agentid), array('id' => $value['id']));
				$changeids[] = $value['id'];
			}

			if (!empty($changeids)) {
				$loginfo .= ' 卡券id：' . implode(',', $changeids);
				plog('sale.virtualcard.edit', $loginfo);
			}

			show_json(1);

		}
	}

	public function changeagent()
	{
		global $_W;
		global $_GPC;

		if ($_W['ispost']) {
			$dataid = $_GPC['dataid'];
			$agentid = $_GPC['agentid'];

			if (empty($dataid)) {
				show_json(0, '请选择要操作的卡券');
			}

			if (empty($agentid)) {
				show_json(0, '请选择卡券归属人');
			}

			$loginfo = '修改卡券';

			$agentInfo = m('member')->getMember($agentid);
			if (empty($agentInfo) || empty($agentInfo['status']) || empty($agentInfo['isagent'])) {
				show_json(0, '此卡券归属人有误');
			}

			$changeids = array();
			$datas = pdo_fetch('select id,agentid,used from ' . tablename('vcshop_virtualcard_data') . ' WHERE id = ' . $dataid .' AND uniacid=' . $_W['uniacid']);

			if (empty($datas)) {
				show_json(0, '查无此券');
			}

			if ($datas['used'] == 1) {
				show_json(0, '此券也被领取！');
			}
			

			pdo_update('vcshop_virtualcard_data', array('agentid' => $agentid), array('id' => $datas['id']));
			
			$loginfo .= ' 卡券id：' . $dataid;
			plog('sale.virtualcard.edit', $loginfo);
			

			show_json(1);

		}
	}
}

?>
