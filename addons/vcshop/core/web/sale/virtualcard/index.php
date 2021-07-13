<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_VcShopPage extends ComWebPage
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
		$condition = ' uniacid = :uniacid ';
		$params = array(':uniacid' => $_W['uniacid']);

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' AND virtualcardname LIKE :virtualcardname';
			$params[':virtualcardname'] = '%' . trim($_GPC['keyword']) . '%';
		}

		if (!empty($_GPC['catid'])) {
			$_GPC['catid'] = trim($_GPC['catid']);
			$condition .= ' AND catid = :catid';
			$params[':catid'] = (int) $_GPC['catid'];
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);

			if (!empty($starttime)) {
				$condition .= ' AND createtime >= :starttime';
				$params[':starttime'] = $starttime;
			}

			if (!empty($endtime)) {
				$condition .= ' AND createtime <= :endtime';
				$params[':endtime'] = $endtime;
			}
		}


		$sql = 'SELECT * FROM ' . tablename('vcshop_virtualcard') . ' ' . (' where  1 and ' . $condition . ' ORDER BY displayorder DESC,id DESC LIMIT ') . ($pindex - 1) * $psize . ',' . $psize;
		$list = pdo_fetchall($sql, $params);

		foreach ($list as &$row) {
			$deltotal = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_virtualcard_data') . ' where virtualcardid=:virtualcardid and uniacid=:uniacid and status = 2 ', array(':virtualcardid' => $row['id'], ':uniacid' => $_W['uniacid']));
			$row['total'] = $row['total'] - $deltotal;
			$row['gettotal'] = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_virtualcard_data') . ' where virtualcardid=:virtualcardid and uniacid=:uniacid and status <> 2 and userid <> 0', array(':virtualcardid' => $row['id'], ':uniacid' => $_W['uniacid']));

			$row['usetotal'] = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_virtualcard_data') . ' where used = 1 and virtualcardid=:virtualcardid and uniacid=:uniacid limit 1', array(':virtualcardid' => $row['id'], ':uniacid' => $_W['uniacid']));
			$row['agent'] = m('member')->getMember($row['agentid']);
			$url = mobileUrl('sale/virtualcard/exchange', array('id' => $row['id']), true);
			$row['qrcode'] = m('qrcode')->createQrcode($url);
		}

		unset($row);
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('vcshop_virtualcard') . (' where 1 and ' . $condition), $params);
		$pager = pagination2($total, $pindex, $psize);
		$category = pdo_fetchall('select * from ' . tablename('vcshop_virtualcard_category') . ' where uniacid=:uniacid order by id desc', array(':uniacid' => $_W['uniacid']), 'id');
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
		$id = intval($_GPC['id']);
		$type = intval($_GPC['type']);

		if ($_W['ispost']) {
			$data = array('uniacid' => $_W['uniacid'], 'virtualcardname' => trim($_GPC['virtualcardname']), 'catid' => intval($_GPC['catid']), 'timelimit' => intval($_GPC['timelimit']), 'timedays' => intval($_GPC['timedays']), 'timestart' => strtotime($_GPC['time']['start']), 'timeend' => strtotime($_GPC['time']['end']) + 86399,'displayorder' => intval($_GPC['displayorder']),'agentid' => intval($_GPC['agentid']),'sendcredit1' => intval($_GPC['sendcredit1']),'sendmonth' => intval($_GPC['sendmonth']),'sendday' => intval($_GPC['sendday']),'total' => intval($_GPC['total']),'is_alltime'=>intval($_GPC['is_alltime']));
			

			if (!empty($id)) {
				$olditem = pdo_fetch('select total,agentid from ' . tablename('vcshop_virtualcard') . ' WHERE id =:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $id));
				$old_total = $olditem['total'];
				$new_total = intval($_GPC['total']);
				if ($old_total > $new_total) {
					$delete_total = $old_total - $new_total;
					$list = pdo_fetchall('select * from ' . tablename('vcshop_virtualcard_data') . ' where virtualcardid=:virtualcardid and uniacid=:uniacid and userid = 0 order by id desc ', array(':virtualcardid' => $id, ':uniacid' => $_W['uniacid']));
					$i = 1;
					foreach ($list as $key => $value) {
						if ($i <= $delete_total) {
							pdo_delete('vcshop_virtualcard_data',array('uniacid' => $_W['uniacid'],':virtualcardid' => $id));
							$i++;
						}else{
							break;
						}
					}
				}elseif ($old_total < $new_total) {
					$insert_total = $new_total - $old_total;
					
					$createcard = com('virtualcard')->createVirtualcard($id,$insert_total);
					
				}

				// if ($olditem['agentid'] != $data['agentid']) {
				// 	pdo_update('vcshop_virtualcard_data',array('agentid' => $data['agentid']),array('uniacid'=>$_W['uniacid'],'virtualcardid' => $id,'used' => 0));
				// }
				
				pdo_update('vcshop_virtualcard_data',array('sendcredit1' => intval($_GPC['sendcredit1']),'sendmonth' => intval($_GPC['sendmonth']),'sendday' => intval($_GPC['sendday']),'is_alltime'=>intval($_GPC['is_alltime'])),array('uniacid'=>$_W['uniacid'],'virtualcardid' => $id,'used' => 0));

				pdo_update('vcshop_virtualcard', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
				plog('sale.virtualcard.edit', '编辑虚拟卡券 ID: ' . $id . ' <br/>虚拟卡券名称: ' . $data['virtualcardname']);
			}
			else {
				$data['createtime'] = time();
				pdo_insert('vcshop_virtualcard', $data);
				$id = pdo_insertid();
				$insert_total = intval($_GPC['total']);
				// for ($i=1; $i < $insert_total; $i++) { 
				// 	com('virtualcard')->createVirtualcard($id);
				// }
				$createcard = com('virtualcard')->createVirtualcard($id,$insert_total);
				plog('sale.virtualcard.add', '添加虚拟卡券 ID: ' . $id . '  <br/>虚拟卡券名称: ' . $data['virtualcardname']);
			}

			show_json(1, array('url' => webUrl('sale/virtualcard/edit', array('id' => $id, 'tab' => str_replace('#tab_', '', $_GPC['tab'])))));
		}

		$item = pdo_fetch('SELECT * FROM ' . tablename('vcshop_virtualcard') . ' WHERE id =:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $id));

		if (empty($item)) {
			$starttime = time();
			$endtime = strtotime(date('Y-m-d H:i:s', $starttime) . '+7 days');
		}
		else {
			if (!empty($item['agentid'])){
				$agent = m('member')->getMember($item['agentid']);
				// print_r($agent);die;
			}
			$starttime = $item['timestart'];
			$endtime = $item['timeend'];
			
			$left_count = 0;

			if ($item['total'] == '-1') {
				$left_count = '不限';
			}
			else {
				$gettotal = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_virtualcard_data') . ' where virtualcardid=:virtualcardid and uniacid=:uniacid and status <> 2 and userid <> 0', array(':virtualcardid' => $id, ':uniacid' => $_W['uniacid']));
				$deltotal = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_virtualcard_data') . ' where virtualcardid=:virtualcardid and uniacid=:uniacid and status = 2 ', array(':virtualcardid' => $id, ':uniacid' => $_W['uniacid']));
				// print_r($gettotal);die;
				$item['total'] = $item['total'] - $deltotal;
				$left_count = $item['total'] - $gettotal;
				$let_count = intval($left_count);
			}
		}

		$category = pdo_fetchall('select * from ' . tablename('vcshop_virtualcard_category') . ' where uniacid=:uniacid order by id desc', array(':uniacid' => $_W['uniacid']), 'id');
		$shop = $_W['shopset']['shop'];
		$levels = m('member')->getLevels();

		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,virtualcardname FROM ' . tablename('vcshop_virtualcard') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
		

		foreach ($items as $item) {
			pdo_delete('vcshop_virtualcard', array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
			pdo_delete('vcshop_virtualcard_data', array('virtualcardid' => $item['id'], 'uniacid' => $_W['uniacid']));
			plog('sale.virtualcard.delete', '删除虚拟卡券 ID: ' . $id . '  <br/>虚拟卡券名称: ' . $item['virtualcardname'] . ' ');
		}

		show_json(1, array('url' => webUrl('sale/virtualcard')));
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
		$items = pdo_fetchall('SELECT id,virtualcardname FROM ' . tablename('vcshop_virtualcard') . (' WHERE id in( ' . $id . ' ) and merchid=0 AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_update('vcshop_virtualcard', array('displayorder' => $displayorder), array('id' => $id));
			plog('sale.virtualcard.displayorder', '修改虚拟卡券排序 ID: ' . $item['id'] . ' 名称: ' . $item['virtualcardname'] . ' 排序: ' . $displayorder . ' ');
		}

		show_json(1);
	}

	public function send()
	{
		global $_W;
		global $_GPC;
		$virtualcardid = intval($_GPC['virtualcardid']);
		$virtualcard = pdo_fetch('SELECT * FROM ' . tablename('vcshop_virtualcard') . ' WHERE id=:id and uniacid=:uniacid and merchid=0', array(':id' => $virtualcardid, ':uniacid' => $_W['uniacid']));

		if ($_W['ispost']) {
			if (empty($virtualcard)) {
				show_json(0, '未找到虚拟卡券!');
			}

			$class1 = intval($_GPC['send1']);
			$plog = '';

			if ($class1 == 1) {
				$openids = $_GPC['send_openid'];
				$plog = '发放虚拟卡券 ID: ' . $virtualcardid . ' 方式: 指定 OPENID 人数: ' . count($openids);
			}
			else if ($class1 == 2) {
				$where = '';

				if (!empty($_GPC['send_level'])) {
					$where .= ' and level =' . intval($_GPC['send_level']);
				}

				$members = pdo_fetchall('SELECT openid FROM ' . tablename('vcshop_member') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\'') . $where, array(), 'openid');

				if (!empty($_GPC['send_level'])) {
					$levelname = pdo_fetchcolumn('select levelname from ' . tablename('vcshop_member_level') . ' where id=:id limit 1', array(':id' => $_GPC['send_level']));
				}
				else {
					$levelname = '全部';
				}

				$openids = array_keys($members);
				$plog = '发放虚拟卡券 ID: ' . $virtualcardid . ' 方式: 等级-' . $levelname . ' 人数: ' . count($members);
			}
			else if ($class1 == 3) {
				$where = '';

				if (!empty($_GPC['send_group'])) {
					$where .= ' and groupid =' . intval($_GPC['send_group']);
				}

				$members = pdo_fetchall('SELECT openid FROM ' . tablename('vcshop_member') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\'') . $where, array(), 'openid');

				if (!empty($_GPC['send_group'])) {
					$groupname = pdo_fetchcolumn('select groupname from ' . tablename('vcshop_member_group') . ' where id=:id limit 1', array(':id' => $_GPC['send_group']));
				}
				else {
					$groupname = '全部分组';
				}

				$openids = array_keys($members);
				$plog = '发放虚拟卡券 ID: ' . $virtualcardid . '  方式: 分组-' . $groupname . ' 人数: ' . count($members);
			}
			else if ($class1 == 4) {
				$where = '';
				$members = pdo_fetchall('SELECT openid FROM ' . tablename('vcshop_member') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\'') . $where, array(), 'openid');
				$openids = array_keys($members);
				$plog = '发放虚拟卡券 ID: ' . $virtualcardid . '  方式: 全部会员 人数: ' . count($members);
			}
			else if ($class1 == 5) {
				$where = '';
				if (!empty($_GPC['send_agentlevel']) || $_GPC['send_partnerlevels'] === '0') {
					$where .= ' and agentlevel =' . intval($_GPC['send_agentlevel']);
				}

				$members = pdo_fetchall('SELECT openid FROM ' . tablename('vcshop_member') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' and isagent=1 and status=1 ') . $where, array(), 'openid');

				if ($_GPC['send_agentlevel'] != '') {
					$levelname = pdo_fetchcolumn('select levelname from ' . tablename('vcshop_commission_level') . ' where id=:id limit 1', array(':id' => $_GPC['send_agentlevel']));
				}
				else {
					$levelname = '全部';
				}

				$openids = array_keys($members);
				$plog = '发放虚拟卡券 ID: ' . $virtualcardid . '  方式: 分销商-' . $levelname . ' 人数: ' . count($members);
			}
			else if ($class1 == 6) {
				$where = '';
				if (!empty($_GPC['send_partnerlevels']) || $_GPC['send_partnerlevels'] === '0') {
					$where .= ' and partnerlevel =' . intval($_GPC['send_partnerlevels']);
				}

				$members = pdo_fetchall('SELECT openid FROM ' . tablename('vcshop_member') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' and ispartner=1 and partnerstatus=1 ') . $where, array(), 'openid');

				if ($_GPC['send_partnerlevels'] != '') {
					$levelname = pdo_fetchcolumn('select levelname from ' . tablename('vcshop_globonus_level') . ' where id=:id limit 1', array(':id' => $_GPC['send_partnerlevels']));
				}
				else {
					$levelname = '全部';
				}

				$openids = array_keys($members);
				$plog = '发放虚拟卡券 ID: ' . $virtualcardid . '  方式: 股东-' . $levelname . ' 人数: ' . count($members);
			}
			else {
				if ($class1 == 7) {
					$where = '';
					if (!empty($_GPC['send_aagentlevels']) || $_GPC['send_partnerlevels'] === '0') {
						$where .= ' and aagentlevel =' . intval($_GPC['send_aagentlevels']);
					}

					$members = pdo_fetchall('SELECT openid FROM ' . tablename('vcshop_member') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' and isaagent=1 and aagentstatus=1 ') . $where, array(), 'openid');

					if ($_GPC['send_aagentlevels'] != '') {
						$levelname = pdo_fetchcolumn('select levelname from ' . tablename('vcshop_abonus_level') . ' where id=:id limit 1', array(':id' => $_GPC['send_aagentlevels']));
					}
					else {
						$levelname = '全部';
					}

					$openids = array_keys($members);
					$plog = '发放虚拟卡券 ID: ' . $virtualcardid . '  方式: 区域代理-' . $levelname . ' 人数: ' . count($members);
				}
			}

			$mopenids = array();

			foreach ($openids as $openid) {
				$mopenids[] = '\'' . str_replace('\'', '\'\'', $openid) . '\'';
			}

			if (empty($mopenids)) {
				show_json(0, '未找到发送的会员!');
			}

			$members = pdo_fetchall('select id,openid,nickname from ' . tablename('vcshop_member') . ' where openid in (' . implode(',', $mopenids) . (') and uniacid=' . $_W['uniacid']));

			if (empty($members)) {
				show_json(0, '未找到发送的会员!');
			}

			if ($virtualcard['total'] != -1) {
				$last = com('virtualcard')->get_last_count($virtualcardid);

				if ($last <= 0) {
					show_json(0, '虚拟卡券数量不足,无法发放!');
				}

				$need = count($members) - $last;

				if (0 < $need) {
					show_json(0, '虚拟卡券数量不足,请补充 ' . $need . ' 张虚拟卡券才能发放!');
				}
			}

			$upgrade = array('resptitle' => trim($_GPC['send_title']), 'respthumb' => trim($_GPC['send_thumb']), 'respdesc' => trim($_GPC['send_desc']), 'respurl' => trim($_GPC['send_url']));
			pdo_update('vcshop_virtualcard', $upgrade, array('id' => $virtualcard['id']));
			$send_total = intval($_GPC['send_total']);
			$send_total <= 0 && ($send_total = 1);
			$account = m('common')->getAccount();
			$time = time();

			foreach ($members as $m) {
				$i = 1;

				while ($i <= $send_total) {
					$log = array('uniacid' => $_W['uniacid'], 'merchid' => $virtualcard['merchid'], 'openid' => $m['openid'], 'logno' => m('common')->createNO('virtualcard_log', 'logno', 'CC'), 'virtualcardid' => $virtualcardid, 'status' => 1, 'paystatus' => -1, 'creditstatus' => -1, 'createtime' => $time, 'getfrom' => 0);
					pdo_insert('vcshop_virtualcard_log', $log);
					$logid = pdo_insertid();
					$data = array('uniacid' => $_W['uniacid'], 'merchid' => $virtualcard['merchid'], 'openid' => $m['openid'], 'virtualcardid' => $virtualcardid, 'gettype' => 0, 'gettime' => $time, 'senduid' => $_W['uid']);
					pdo_insert('vcshop_virtualcard_data', $data);
					++$i;
				}

				com('virtualcard')->sendMessage($virtualcard, $send_total, $m, $account);
			}

			plog('sale.virtualcard.send', $plog);
			show_json(1, array('message' => '虚拟卡券发放成功!', 'url' => webUrl('sale/virtualcard')));
		}

		$list = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_member_level') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY level asc'));
		$list2 = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_member_group') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY id asc'));
		$virtualcards = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_virtualcard') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY id asc'));
		$hascommission = false;
		$plugin_com = p('commission');

		if ($plugin_com) {
			$plugin_com_set = $plugin_com->getSet();
			$hascommission = !empty($plugin_com_set['level']);
		}

		$hasglobonus = false;
		$plugin_globonus = p('globonus');

		if ($plugin_globonus) {
			$plugin_globonus_set = $plugin_globonus->getSet();
			$hasglobonus = !empty($plugin_globonus_set['open']);
		}

		$hasabonus = false;
		$plugin_abonus = p('abonus');

		if ($plugin_abonus) {
			$plugin_abonus_set = $plugin_abonus->getSet();
			$hasabonus = !empty($plugin_abonus_set['open']);
		}

		if ($hascommission) {
			$list3 = $plugin_com->getLevels();
		}

		if ($hasglobonus) {
			$list4 = $plugin_globonus->getLevels();
		}

		if ($hasabonus) {
			$list5 = $plugin_abonus->getLevels();
		}

		load()->func('tpl');
		include $this->template();
	}

}

?>
