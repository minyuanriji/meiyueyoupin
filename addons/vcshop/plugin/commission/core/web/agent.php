<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Agent_VcShopPage extends PluginWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$agentlevels = $this->model->getLevels(true, true);
		$level = $this->set['level'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$params = array();
		$condition = '';
		$searchfield = strtolower(trim($_GPC['searchfield']));
		$keyword = trim($_GPC['keyword']);
		if (!empty($searchfield) && !empty($keyword)) {
			if ($searchfield == 'member') {
				$condition .= ' and ( dm.realname like :keyword or dm.nickname like :keyword or dm.mobile like :keyword)';
				$params[':keyword'] = '%' . $keyword . '%';
			}
			else {
				if ($searchfield == 'parent') {
					if ($keyword == '总店') {
						$condition .= ' and dm.agentid=0';
					}
					else {
						$condition .= ' and ( p.mobile like :keyword or p.nickname like :keyword or p.realname like :keyword)';
						$params[':keyword'] = '%' . $keyword . '%';
					}
				}
			}
		}

		if ($_GPC['followed'] != '') {
			if ($_GPC['followed'] == 2) {
				$condition .= ' and f.follow=0 and f.unfollowtime<>0';
			}
			else {
				$condition .= ' and f.follow=' . intval($_GPC['followed']) . ' and f.unfollowtime=0 ';
			}
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND dm.agenttime >= :starttime AND dm.agenttime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		if ($_GPC['agentlevel'] != '') {
			$condition .= ' and dm.agentlevel=' . intval($_GPC['agentlevel']);
		}

		if ($_GPC['isagentblack'] != '') {
			$condition .= ' and dm.agentblack=' . intval($_GPC['isagentblack']);
		}

		if ($_GPC['status'] != '') {
			$condition .= ' and dm.status=' . intval($_GPC['status']);
		}

		if ($_GPC['agentblack'] != '') {
			$condition .= ' and dm.agentblack=' . intval($_GPC['agentblack']);
		}

		$sql = 'select dm.*,dm.nickname,dm.avatar,l.levelname,p.agentlevel as parentagentlevel, p.mobile as parentmobile,p.id as pid,p.nickname as parentname,p.avatar as parentavatar,f.follow as followed,p.realname as parentrealname, f.unfollowtime from ' . tablename('vcshop_member') . ' dm ' . ' left join ' . tablename('vcshop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('vcshop_commission_level') . ' l on l.id = dm.agentlevel' . ' left join ' . tablename('mc_mapping_fans') . ('f on f.openid=dm.openid and f.uniacid=' . $_W['uniacid']) . ' where dm.uniacid = ' . $_W['uniacid'] . (' and dm.isagent =1  ' . $condition . ' ORDER BY dm.status ASC ,dm.createtime desc');

		if (empty($_GPC['export'])) {
			$sql .= ' limit ' . ($pindex - 1) * $psize . ',' . $psize;
		}

		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('select count(dm.id) from' . tablename('vcshop_member') . ' dm  ' . ' left join ' . tablename('vcshop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('mc_mapping_fans') . 'f on f.openid=dm.openid' . ' where dm.uniacid =' . $_W['uniacid'] . (' and dm.isagent =1 ' . $condition), $params);

		foreach ($list as &$row) {
			$info = $this->model->getInfo($row['openid'], array('total', 'pay'));
			$row['levelcount'] = $info['agentcount'];

			if (1 <= $level) {
				$row['level1'] = $info['level1'];
			}

			if (2 <= $level) {
				$row['level2'] = $info['level2'];
			}

			if (3 <= $level) {
				$row['level3'] = $info['level3'];
			}

			$row['credit1'] = m('member')->getCredit($row['openid'], 'credit1');
			$row['credit2'] = m('member')->getCredit($row['openid'], 'credit2');
			$row['commission_total'] = $info['commission_total'];
			$row['commission_pay'] = $info['commission_pay'];
			$row['followed'] = m('user')->followed($row['openid']);
			if (p('diyform') && !empty($row['diymemberfields']) && !empty($row['diymemberdata'])) {
				$diyformdata_array = p('diyform')->getDatas(iunserializer($row['diymemberfields']), iunserializer($row['diymemberdata']));
				$diyformdata = '';

				foreach ($diyformdata_array as $da) {
					$diyformdata .= $da['name'] . ': ' . $da['value'] . '
';
				}

				$row['member_diyformdata'] = $diyformdata;
			}

			if (p('diyform') && !empty($row['diycommissionfields']) && !empty($row['diycommissiondata'])) {
				$diyformdata_array = p('diyform')->getDatas(iunserializer($row['diycommissionfields']), iunserializer($row['diycommissiondata']));
				$diyformdata = '';

				foreach ($diyformdata_array as $da) {
					$diyformdata .= $da['name'] . ': ' . $da['value'] . '
';
				}

				$row['agent_diyformdata'] = $diyformdata;
			}

			if (!empty($agentlevels) || $_GPC['export'] == '1') {
				foreach ($agentlevels as $levels) {
					if ($row['agentlevel'] == $levels['id']) {
						$row['levelname'] = $levels['levelname'];
					}

					if ($row['parentagentlevel'] == $levels['id']) {
						$row['parentlevelname'] = $levels['levelname'];
					}
				}
			}

			// 20200321 lin
			$row['newchild'] = 0;
			$row['teamchild'] = 0;
			// 第一步 查询会员的分销等级的权重
			$clevel = pdo_fetchcolumn('select level from '.tablename('vcshop_commission_level').' where id = :agentlevel and uniacid = :uniacid',array(':agentlevel'=>$info['agentlevel'],':uniacid'=>$_W['uniacid']));
			// 第二步 查询会员下面的仔
			$childmembers = pdo_fetchall('select r.id,m.openid,r.level,m.agentlevel from '.tablename('vcshop_commission_relation'). ' as r left join '.tablename('vcshop_member').' as m on m.id = r.id  where pid = :pid and m.uniacid = :uniacid order by level ASC',array(':pid'=>$row['id'],':uniacid'=>$_W['uniacid']));
			$childarr = []; // 线下会员
			$maxmember = []; // 分销等级比此会员大或者相等的会员 大哥组
			$teammember = []; // 团队数组
			if (!empty($childmembers)) {
				foreach ($childmembers as $child => &$childs) {
					// 把仔传进数组 以便查询他有没有买升级产品
					$childarr[$child] = "'".$childs['openid']."'";
					// 仔的分销等级权重
					$c_clevel = pdo_fetchcolumn('select level from '.tablename('vcshop_commission_level').' where id = :agentlevel and uniacid = :uniacid',array(':agentlevel'=>$childs['agentlevel'],':uniacid'=>$_W['uniacid']));
					// 如果仔的分销等级权重 小于 爸爸的分销等级权重
					if ($clevel > $c_clevel) {
						// 如果有大哥组 就要查询一下这个仔是不是在大哥下面
						if(!empty($maxmember)){
							$cutmembers = pdo_fetchall('select * from '.tablename('vcshop_commission_relation'). ' where id = :id and pid in ('.implode(',', $maxmember).')',array(':id'=>$childs['id']));
							// 如果他不是这个大哥组的其中一个手下 就进去团队数组
							if (empty($cutmembers)) {
								array_push($teammember, $childs['id']);
							}else{ // 如果他是这个大哥组的其中一个手下 则跳过
								continue;
							}
						}else{ //如果暂时没有大哥组 则进入团队数组
							array_push($teammember, $childs['id']);
						}
					}else{ // 如果仔的分销等级权重大于爸爸的分销等级权重 则进入大哥组
						array_push($maxmember, $childs['id']);
					}
				}
				// 统计仔仔们有没有购买升级产品
				$newchild = pdo_fetchall('select id,openid from '.tablename('vcshop_order').' where openid in ('.implode(',',$childarr).') and upgradeorder = 1 and status = 3 group by openid' );
				$row['newchild'] = ($newchild)?count($newchild):0;
				$row['teamchild'] = count($teammember);
			}
		}

		unset($row);

		if ($_GPC['export'] == '1') {
			ca('commission.agent.export');
			plog('commission.agent.export', '导出分销商数据');

			foreach ($list as &$row) {
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
				$row['agentime'] = empty($row['agenttime']) ? '' : date('Y-m-d H:i', $row['agentime']);
				$row['groupname'] = empty($row['groupname']) ? '无分组' : $row['groupname'];
				$row['levelname'] = empty($row['levelname']) ? '普通等级' : $row['levelname'];
				$row['parentname'] = empty($row['pid']) ? '总店' : '[' . $row['agentid'] . ']' . $row['parentname'];
				$row['statusstr'] = empty($row['status']) ? '未通过' : '通过';
				$row['followstr'] = empty($row['followed']) ? '' : '已关注';
				$row['realname'] = str_replace('=', '', $row['realname']);
				$row['nickname'] = str_replace('=', '', $row['nickname']);
			}

			unset($row);
			$columns = array(
				array('title' => 'ID', 'field' => 'id', 'width' => 12),
				array('title' => '昵称', 'field' => 'nickname', 'width' => 12),
				array('title' => '姓名', 'field' => 'realname', 'width' => 12),
				array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
				array('title' => '微信号', 'field' => 'weixin', 'width' => 12),
				array('title' => 'openid', 'field' => 'openid', 'width' => 24),
				array('title' => '推荐人', 'field' => 'parentname', 'width' => 12),
				array('title' => '分销商等级', 'field' => 'levelname', 'width' => 12),
				array('title' => '点击数', 'field' => 'clickcount', 'width' => 12),
				array('title' => '下线分销商总数', 'field' => 'levelcount', 'width' => 12),
				array('title' => '一级下线分销商数', 'field' => 'level1', 'width' => 12),
				array('title' => '二级下线分销商数', 'field' => 'level2', 'width' => 12),
				array('title' => '三级下线分销商数', 'field' => 'level3', 'width' => 12),
				array('title' => '累计佣金', 'field' => 'commission_total', 'width' => 12),
				array('title' => '打款佣金', 'field' => 'commission_pay', 'width' => 12),
				array('title' => '注册时间', 'field' => 'createtime', 'width' => 12),
				array('title' => '成为分销商时间', 'field' => 'createtime', 'width' => 12),
				array('title' => '审核状态', 'field' => 'statusstr', 'width' => 12),
				array('title' => '是否关注', 'field' => 'followstr', 'width' => 12)
			);

			if (p('diyform')) {
				$columns[] = array('title' => '分销商会员自定义信息', 'field' => 'member_diyformdata', 'width' => 36);
				$columns[] = array('title' => '分销商申请自定义信息', 'field' => 'agent_diyformdata', 'width' => 36);
			}

			m('excel')->export($list, array('title' => '分销商数据-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
		}

		$pager = pagination2($total, $pindex, $psize);
		load()->func('tpl');
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

		$members = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_member') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($members as $member) {
			pdo_update('vcshop_member', array('isagent' => 0, 'status' => 0), array('id' => $member['id']));
			plog('commission.agent.delete', '取消分销商资格 <br/>分销商信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
		}

		show_json(1, array('url' => referer()));
	}

	public function user()
	{
		global $_W;
		global $_GPC;
		$agentlevels = $this->model->getLevels(true, true);
		$level_map = array(1 => '一级', 2 => '二级', 3 => '三级');
		$level_name = $level_map[$_GPC['level']];
		$level = intval($_GPC['level']);
		$agentid = intval($_GPC['id']);
		$member = $this->model->getInfo($agentid);
		$parentInfo = m('member')->getMember($member['agentid']);
		$total = $member['agentcount'];
		$level1 = $member['level1'];
		$level2 = $member['level2'];
		$level3 = $member['level3'];
		$downcount = 0;
		$downs1 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid=:agentid and uniacid=:uniacid and isagent=0', array(':agentid' => $member['id'], ':uniacid' => $_W['uniacid']), 'id');
		$downcount += count($downs1);

		if (!empty($downs1)) {
			$downs2 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid in( ' . implode(',', array_keys($downs1)) . ') and uniacid=:uniacid and isagent=0 ', array(':uniacid' => $_W['uniacid']), 'id');
			$downcount += count($downs2);

			if (!empty($downs2)) {
				$downs3 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid in( ' . implode(',', array_keys($downs2)) . ') and uniacid=:uniacidand isagent=0 ', array(':uniacid' => $_W['uniacid']), 'id');
				$downcount += count($downs3);
			}
		}

		$child_num = pdo_fetchcolumn('select count(1) from ' . tablename('vcshop_commission_relation') . ' where pid = :pid', array(':pid' => $agentid));
		$level11 = $downcount;
		$newlevel = pdo_fetch('select * from ' . tablename('vcshop_commission_level') . (' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0  order by downcount desc limit 1'), array(':uniacid' => $_W['uniacid']));
		$condition = '';
		$params = array();
		$hasagent = false;
		if (empty($level) && !empty($this->set['level'])) {
			$condition = ' and ( dm.agentid=' . $member['id'];
			if (0 < $level1 && 1 < $this->set['level']) {
				$condition .= ' or  dm.agentid in( ' . implode(',', array_keys($member['level1_agentids'])) . ')';
			}

			if (0 < $level2 && 2 < $this->set['level']) {
				$condition .= ' or  dm.agentid in( ' . implode(',', array_keys($member['level2_agentids'])) . ')';
			}

			$condition .= ' )';
			$hasagent = true;
		}
		else {
			if ($level == 1 && 0 < $this->set['level']) {
				if (0 < $level1 || !empty($level11)) {
					$condition = ' and dm.agentid=' . $member['id'];
					$hasagent = true;
				}
			}
			else {
				if ($level == 2 && 1 < $this->set['level']) {
					if (0 < $level2) {
						$condition = ' and dm.agentid in( ' . implode(',', array_keys($member['level1_agentids'])) . ')';
						$hasagent = true;
					}
				}
				else {
					if ($level == 3 && 2 < $this->set['level']) {
						if (0 < $level3) {
							$condition = ' and dm.agentid in( ' . implode(',', array_keys($member['level2_agentids'])) . ')';
							$hasagent = true;
						}
					}
				}
			}
		}

		if (!empty($_GPC['mid'])) {
			$condition .= ' and dm.id=:mid';
			$params[':mid'] = intval($_GPC['mid']);
		}

		$searchfield = strtolower(trim($_GPC['searchfield']));
		$keyword = trim($_GPC['keyword']);
		if (!empty($keyword)) {
			
				$condition .= ' and ( dm.realname like :keyword or dm.nickname like :keyword or dm.mobile like :keyword)';
				$params[':keyword'] = '%' . $keyword . '%';
			
		}

		if ($_GPC['isagent'] != '') {
			$condition .= ' and dm.isagent=' . intval($_GPC['isagent']);
		}

		if ($_GPC['agentlevel'] != '') {
			$condition .= ' and dm.agentlevel=' . intval($_GPC['agentlevel']);
		}

		if ($_GPC['status'] != '') {
			$condition .= ' and dm.status=' . intval($_GPC['status']);
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND dm.agenttime >= :starttime AND dm.agenttime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		if ($_GPC['followed'] != '') {
			if ($_GPC['followed'] == 2) {
				$condition .= ' and f.follow=0 and dm.uid<>0';
			}
			else {
				$condition .= ' and f.follow=' . intval($_GPC['followed']);
			}
		}

		if ($_GPC['isagentblack'] != '') {
			$condition .= ' and dm.agentblack=' . intval($_GPC['isagentblack']);
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$list = array();

		if ($hasagent) {
			$total = pdo_fetchcolumn('select count(dm.id) from' . tablename('vcshop_member') . ' dm ' . ' left join ' . tablename('vcshop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('mc_mapping_fans') . 'f on f.openid=dm.openid' . ' where dm.uniacid =' . $_W['uniacid'] . ('  ' . $condition), $params);
			$sql = 'select dm.*,p.nickname as parentname,p.avatar as parentavatar,l.levelname as levelname  from ' . tablename('vcshop_member') . ' dm ' . ' left join ' . tablename('vcshop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('vcshop_commission_level') . ' l on l.id = dm.agentlevel' . ' left join ' . tablename('mc_mapping_fans') . ('f on f.openid=dm.openid  and f.uniacid=' . $_W['uniacid']) . ' where dm.uniacid = ' . $_W['uniacid'] . ('  ' . $condition . '   ORDER BY dm.agenttime desc');

			if (empty($_GPC['export'])) {
				$sql .= ' limit ' . ($pindex - 1) * $psize . ',' . $psize;
			}

			$list = pdo_fetchall($sql, $params);
			$pager = pagination($total, $pindex, $psize);

			foreach ($list as &$row) {
				$info = $this->model->getInfo($row['openid'], array('total', 'pay'));
				$row['levelcount'] = $info['agentcount'];

				if (1 <= $this->set['level']) {
					$row['level1'] = $info['level1'];
				}

				if (2 <= $this->set['level']) {
					$row['level2'] = $info['level2'];
				}

				if (3 <= $this->set['level']) {
					$row['level3'] = $info['level3'];
				}

				$row['credit1'] = m('member')->getCredit($row['openid'], 'credit1');
				$row['credit2'] = m('member')->getCredit($row['openid'], 'credit2');
				$row['commission_total'] = $info['commission_total'];
				$row['commission_pay'] = $info['commission_pay'];
				$row['followed'] = m('user')->followed($row['openid']);

				if ($row['agentid'] == $member['id']) {
					$row['level'] = 1;
				}
				else if (in_array($row['agentid'], array_keys($member['level1_agentids']))) {
					$row['level'] = 2;
				}
				else {
					if (in_array($row['agentid'], array_keys($member['level2_agentids']))) {
						$row['level'] = 3;
					}
				}
			}
		}

		unset($row);

		if ($_GPC['export'] == 1) {
			foreach ($list as &$row) {
				$row['realname'] = str_replace('=', '', $row['realname']);
				$row['nickname'] = str_replace('=', '', $row['nickname']);
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
				$row['agentime'] = empty($row['agenttime']) ? '' : date('Y-m-d H:i', $row['agentime']);
				$row['groupname'] = empty($row['groupname']) ? '无分组' : $row['groupname'];
				$row['levelname'] = empty($row['levelname']) ? '普通等级' : $row['levelname'];
				$row['parentname'] = empty($row['parentname']) ? '总店' : '[' . $row['agentid'] . ']' . $row['parentname'];
				$row['statusstr'] = empty($row['status']) ? '' : '通过';
				$row['followstr'] = empty($row['followed']) ? '' : '已关注';
				if (p('diyform') && !empty($row['diymemberfields']) && !empty($row['diymemberdata'])) {
					$diyformdata_array = p('diyform')->getDatas(iunserializer($row['diymemberfields']), iunserializer($row['diymemberdata']));
					$diyformdata = '';

					foreach ($diyformdata_array as $da) {
						$diyformdata .= $da['name'] . ': ' . $da['value'] . '
';
					}

					$row['member_diyformdata'] = $diyformdata;
				}

				if (p('diyform') && !empty($row['diycommissionfields']) && !empty($row['diycommissiondata'])) {
					$diyformdata_array = p('diyform')->getDatas(iunserializer($row['diycommissionfields']), iunserializer($row['diycommissiondata']));
					$diyformdata = '';

					foreach ($diyformdata_array as $da) {
						$diyformdata .= $da['name'] . ': ' . $da['value'] . '
';
					}

					$row['agent_diyformdata'] = $diyformdata;
				}
			}

			unset($row);
			$columns = array(
				array('title' => 'ID', 'field' => 'id', 'width' => 12),
				array('title' => '昵称', 'field' => 'nickname', 'width' => 12),
				array('title' => '姓名', 'field' => 'realname', 'width' => 12),
				array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
				array('title' => '微信号', 'field' => 'weixin', 'width' => 12),
				array('title' => 'openid', 'field' => 'openid', 'width' => 24),
				array('title' => '推荐人', 'field' => 'parentname', 'width' => 12),
				array('title' => '分销商等级', 'field' => 'levelname', 'width' => 12),
				array('title' => '点击数', 'field' => 'clickcount', 'width' => 12),
				array('title' => '下线分销商总数', 'field' => 'levelcount', 'width' => 12),
				array('title' => '一级下线分销商数', 'field' => 'level1', 'width' => 12),
				array('title' => '二级下线分销商数', 'field' => 'level2', 'width' => 12),
				array('title' => '三级下线分销商数', 'field' => 'level3', 'width' => 12),
				array('title' => '累计佣金', 'field' => 'commission_total', 'width' => 12),
				array('title' => '打款佣金', 'field' => 'commission_pay', 'width' => 12),
				array('title' => '注册时间', 'field' => 'createtime', 'width' => 12),
				array('title' => '成为分销商时间', 'field' => 'createtime', 'width' => 12),
				array('title' => '审核状态', 'field' => 'createtime', 'width' => 12),
				array('title' => '是否关注', 'field' => 'followstr', 'width' => 12)
			);

			if (p('diyform')) {
				$columns[] = array('title' => '分销商会员自定义信息', 'field' => 'member_diyformdata', 'width' => 36);
				$columns[] = array('title' => '分销商申请自定义信息', 'field' => 'agent_diyformdata', 'width' => 36);
			}

			m('excel')->export($list, array('title' => '推广下线-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
		}

		load()->func('tpl');
		include $this->template('commission/agent_user');
	}

	public function query()
	{
		global $_W;
		global $_GPC;
		$kwd = trim($_GPC['keyword']);
		$wechatid = intval($_GPC['wechatid']);

		if (empty($wechatid)) {
			$wechatid = $_W['uniacid'];
		}

		$params = array();
		$params[':uniacid'] = $wechatid;
		$condition = ' and uniacid=:uniacid and isagent=1 and status=1';

		if (!empty($kwd)) {
			$condition .= ' AND ( `nickname` LIKE :keyword or `realname` LIKE :keyword or `mobile` LIKE :keyword )';
			$params[':keyword'] = '%' . $kwd . '%';
		}

		if (!empty($_GPC['selfid'])) {
			$condition .= ' and id<>' . intval($_GPC['selfid']);
		}

		$ds = pdo_fetchall('SELECT id,avatar,nickname,openid,realname,mobile FROM ' . tablename('vcshop_member') . (' WHERE 1 ' . $condition . ' order by createtime desc'), $params);

		foreach ($ds as $key => $val) {
			$ds[$key]['nickname'] = str_replace('\'', '', $ds[$key]['nickname']);
		}

		include $this->template('commission/query');
	}

	public function check()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$status = intval($_GPC['status']);
		$members = pdo_fetchall('SELECT id,openid,agentid,nickname,realname,mobile,status FROM ' . tablename('vcshop_member') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);
		$time = time();

		foreach ($members as $member) {
			if ($member['status'] === $status) {
				continue;
			}

			if ($status == 1) {
				pdo_update('vcshop_member', array('status' => 1, 'agenttime' => $time), array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
				plog('commission.agent.check', '审核分销商 <br/>分销商信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
				$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'agenttime' => $time), TM_COMMISSION_BECOME);

				if (!empty($member['agentid'])) {
					$this->model->upgradeLevelByAgent($member['agentid']);

					if (p('globonus')) {
						p('globonus')->upgradeLevelByAgent($member['agentid']);
					}

					if (p('author')) {
						p('author')->upgradeLevelByAgent($member['agentid']);
					}
				}
			}
			else {
				pdo_update('vcshop_member', array('status' => 0, 'agenttime' => 0), array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
				plog('commission.agent.check', '取消审核 <br/>分销商信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
			}
		}

		show_json(1, array('url' => referer()));
	}

	public function agentblack()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$agentblack = intval($_GPC['agentblack']);
		$members = pdo_fetchall('SELECT id,openid,nickname,realname,mobile,agentblack FROM ' . tablename('vcshop_member') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($members as $member) {
			if ($member['agentblack'] === $agentblack) {
				continue;
			}

			if ($agentblack == 1) {
				pdo_update('vcshop_member', array('agentblack' => 1), array('id' => $member['id']));
				plog('commission.agent.agentblack', '设置黑名单 <br/>分销商信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
			}
			else {
				pdo_update('vcshop_member', array('agentblack' => 0), array('id' => $member['id']));
				plog('commission.agent.agentblack', '取消黑名单 <br/>分销商信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
			}
		}

		show_json(1, array('url' => referer()));
	}

	// 20200513 lin
	public function columnAgentlist($openid,$type){
		global $_W;
		$member = m('member')->getMember($openid);
		if(!$member){
		    return false;
		}
		$mid = $member['id'];
		// 第一步 查询会员的分销等级的权重
		$clevel = pdo_fetchcolumn('select level from '.tablename('vcshop_commission_level').' where id = :agentlevel and uniacid = :uniacid',array(':agentlevel'=>$member['agentlevel'],':uniacid'=>$_W['uniacid']));
		// 第二步 查询会员下面的仔
		$childmembers = pdo_fetchall('select r.id,m.openid,r.level,m.agentlevel from '.tablename('vcshop_commission_relation'). ' as r left join '.tablename('vcshop_member').' as m on m.id = r.id  where pid = :pid and m.uniacid = :uniacid order by level ASC',array(':pid'=>$mid,':uniacid'=>$_W['uniacid']));
		$childarr = []; // 线下会员
		$maxmember = []; // 分销等级比此会员大或者相等的会员 大哥组
		$teammember = []; // 团队数组
		if (!empty($childmembers)) {
			foreach ($childmembers as $child => &$childs) {
				if ($type == 0) {
					// 把仔传进数组 以便查询他有没有买升级产品
					$childarr[$child] = "'".$childs['openid']."'";
				}
				
				if ($type == 1) {
					// 仔的分销等级权重
					$c_clevel = pdo_fetchcolumn('select level from '.tablename('vcshop_commission_level').' where id = :agentlevel and uniacid = :uniacid',array(':agentlevel'=>$childs['agentlevel'],':uniacid'=>$_W['uniacid']));
					// 如果仔的分销等级权重 小于 爸爸的分销等级权重
					if ($clevel > $c_clevel) {
						// 如果有大哥组 就要查询一下这个仔是不是在大哥下面
						if(!empty($maxmember)){
							$cutmembers = pdo_fetchall('select * from '.tablename('vcshop_commission_relation'). ' where id = :id and pid in ('.implode(',', $maxmember).')',array(':id'=>$childs['id']));
							// 如果他不是这个大哥组的其中一个手下 就进去团队数组
							if (!empty($cutmembers)) {
								array_push($teammember, $childs['id']);
							}else{ // 如果他是这个大哥组的其中一个手下 则跳过
								continue;
							}
						}else{ //如果暂时没有大哥组 则进入团队数组
							array_push($teammember, $childs['id']);
						}
					}else{ // 如果仔的分销等级权重大于爸爸的分销等级权重 则进入大哥组
						array_push($maxmember, $childs['id']);
					}
				}
			}

			if ($type == 0) {
				// 统计仔仔们有没有购买升级产品
				$newchild = pdo_fetchall('select o.id,o.openid,m.id as mid from '.tablename('vcshop_order').' as o left join '.tablename('vcshop_member').' as m on m.openid = o.openid where o.openid in ('.implode(',',$childarr).') and o.upgradeorder = 1 and o.status = 3 group by o.openid' );
				return $newchild;
			}else{
				return $teammember;
			}
			
		}
	}

	public function childlist()
	{
		global $_W;
		global $_GPC;
		$agentlevels = $this->model->getLevels(true, true);
		$level_map = array(1 => '一级', 2 => '二级', 3 => '三级');
		$level_name = $level_map[$_GPC['level']];
		$level = intval($_GPC['level']);
		$agentid = intval($_GPC['id']);
		$member = $this->model->getInfo($agentid);
		$memberinfo = m('member')->getMember($agentid);
		$openid = $memberinfo['openid'];
		$parentInfo = m('member')->getMember($member['agentid']);
		$total = $member['agentcount'];
		$level1 = $member['level1'];
		$level2 = $member['level2'];
		$level3 = $member['level3'];
		$downcount = 0;
		$downs1 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid=:agentid and uniacid=:uniacid and isagent=0', array(':agentid' => $member['id'], ':uniacid' => $_W['uniacid']), 'id');
		$downcount += count($downs1);

		if (!empty($downs1)) {
			$downs2 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid in( ' . implode(',', array_keys($downs1)) . ') and uniacid=:uniacid and isagent=0 ', array(':uniacid' => $_W['uniacid']), 'id');
			$downcount += count($downs2);

			if (!empty($downs2)) {
				$downs3 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid in( ' . implode(',', array_keys($downs2)) . ') and uniacid=:uniacidand isagent=0 ', array(':uniacid' => $_W['uniacid']), 'id');
				$downcount += count($downs3);
			}
		}

		$child_num = pdo_fetchcolumn('select count(1) from ' . tablename('vcshop_commission_relation') . ' where pid = :pid', array(':pid' => $agentid));
		$level11 = $downcount;
		$newlevel = pdo_fetch('select * from ' . tablename('vcshop_commission_level') . (' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0  order by downcount desc limit 1'), array(':uniacid' => $_W['uniacid']));
		$condition = '';
		$params = array();
		$hasagent = false;
		

		if (!empty($_GPC['mid'])) {
			$condition .= ' and dm.id=:mid';
			$params[':mid'] = intval($_GPC['mid']);
		}

		$searchfield = strtolower(trim($_GPC['searchfield']));
		$keyword = trim($_GPC['keyword']);
		if (!empty($keyword)) {
			
				$condition .= ' and ( dm.realname like :keyword or dm.nickname like :keyword or dm.mobile like :keyword)';
				$params[':keyword'] = '%' . $keyword . '%';
			
		}

		if ($_GPC['isagent'] != '') {
			$condition .= ' and dm.isagent=' . intval($_GPC['isagent']);
		}

		if ($_GPC['agentlevel'] != '') {
			$condition .= ' and dm.agentlevel=' . intval($_GPC['agentlevel']);
		}

		if ($_GPC['status'] != '') {
			$condition .= ' and dm.status=' . intval($_GPC['status']);
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND dm.agenttime >= :starttime AND dm.agenttime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		if ($_GPC['followed'] != '') {
			if ($_GPC['followed'] == 2) {
				$condition .= ' and f.follow=0 and dm.uid<>0';
			}
			else {
				$condition .= ' and f.follow=' . intval($_GPC['followed']);
			}
		}

		if ($_GPC['isagentblack'] != '') {
			$condition .= ' and dm.agentblack=' . intval($_GPC['isagentblack']);
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$list = array();

		$childlist = $this->columnAgentlist($openid,0);
		if (!empty($childlist)) {
			$mids = array();
			foreach ($childlist as $key => $value) {
				array_push($mids, $value['mid']);
			}

			$condition .= ' and dm.id in ('.implode(',', $mids).')';
			$total = pdo_fetchcolumn('select count(dm.id) from' . tablename('vcshop_member') . ' dm ' . ' left join ' . tablename('vcshop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('mc_mapping_fans') . 'f on f.openid=dm.openid' . ' where dm.uniacid =' . $_W['uniacid'] . ('  ' . $condition), $params);
			$sql = 'select dm.*,p.nickname as parentname,p.avatar as parentavatar,l.levelname as levelname  from ' . tablename('vcshop_member') . ' dm ' . ' left join ' . tablename('vcshop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('vcshop_commission_level') . ' l on l.id = dm.agentlevel' . ' left join ' . tablename('mc_mapping_fans') . ('f on f.openid=dm.openid  and f.uniacid=' . $_W['uniacid']) . ' where dm.uniacid = ' . $_W['uniacid'] . ('  ' . $condition . '   ORDER BY dm.agenttime desc');
			if (empty($_GPC['export'])) {
				$sql .= ' limit ' . ($pindex - 1) * $psize . ',' . $psize;
			}

			$list = pdo_fetchall($sql, $params);
			$pager = pagination($total, $pindex, $psize);
			foreach ($list as &$row) {
				$info = $this->model->getInfo($row['openid'], array('total', 'pay'));
				$row['levelcount'] = $info['agentcount'];

				if (1 <= $this->set['level']) {
					$row['level1'] = $info['level1'];
				}

				if (2 <= $this->set['level']) {
					$row['level2'] = $info['level2'];
				}

				if (3 <= $this->set['level']) {
					$row['level3'] = $info['level3'];
				}

				$row['credit1'] = m('member')->getCredit($row['openid'], 'credit1');
				$row['credit2'] = m('member')->getCredit($row['openid'], 'credit2');
				$row['commission_total'] = $info['commission_total'];
				$row['commission_pay'] = $info['commission_pay'];
				$row['followed'] = m('user')->followed($row['openid']);
				// if (!empty($member['level1_agentids'])) {
				// 	$level1_agentids =  array_keys($member['level1_agentids']);
				// }
				// if (!empty($member['level2_agentids'])) {
				// 	$level2_agentids =  array_keys($member['level2_agentids']);
				// }
				// print_r($row['agentid']);
				// if ($row['agentid'] == $member['id']) {
				// 	$row['level'] = 1;
				// }
				// else if (in_array($row['agentid'],$level1_agentids)) {
				// 	$row['level'] = 2;
				// }
				// else {
				// 	if (in_array($row['agentid'], $level2_agentids)) {
				// 		$row['level'] = 3;
				// 	}
				// }
			}
			unset($row);
		}
		

		if ($_GPC['export'] == 1) {
			foreach ($list as &$row) {
				$row['realname'] = str_replace('=', '', $row['realname']);
				$row['nickname'] = str_replace('=', '', $row['nickname']);
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
				$row['agentime'] = empty($row['agenttime']) ? '' : date('Y-m-d H:i', $row['agentime']);
				$row['groupname'] = empty($row['groupname']) ? '无分组' : $row['groupname'];
				$row['levelname'] = empty($row['levelname']) ? '普通等级' : $row['levelname'];
				$row['parentname'] = empty($row['parentname']) ? '总店' : '[' . $row['agentid'] . ']' . $row['parentname'];
				$row['statusstr'] = empty($row['status']) ? '' : '通过';
				$row['followstr'] = empty($row['followed']) ? '' : '已关注';
				if (p('diyform') && !empty($row['diymemberfields']) && !empty($row['diymemberdata'])) {
					$diyformdata_array = p('diyform')->getDatas(iunserializer($row['diymemberfields']), iunserializer($row['diymemberdata']));
					$diyformdata = '';

					foreach ($diyformdata_array as $da) {
						$diyformdata .= $da['name'] . ': ' . $da['value'] . '';
					}

					$row['member_diyformdata'] = $diyformdata;
				}

				if (p('diyform') && !empty($row['diycommissionfields']) && !empty($row['diycommissiondata'])) {
					$diyformdata_array = p('diyform')->getDatas(iunserializer($row['diycommissionfields']), iunserializer($row['diycommissiondata']));
					$diyformdata = '';

					foreach ($diyformdata_array as $da) {
						$diyformdata .= $da['name'] . ': ' . $da['value'] . '';
					}

					$row['agent_diyformdata'] = $diyformdata;
				}
			}

			unset($row);
			$columns = array(
				array('title' => 'ID', 'field' => 'id', 'width' => 12),
				array('title' => '昵称', 'field' => 'nickname', 'width' => 12),
				array('title' => '姓名', 'field' => 'realname', 'width' => 12),
				array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
				array('title' => '微信号', 'field' => 'weixin', 'width' => 12),
				array('title' => 'openid', 'field' => 'openid', 'width' => 24),
				array('title' => '推荐人', 'field' => 'parentname', 'width' => 12),
				array('title' => '分销商等级', 'field' => 'levelname', 'width' => 12),
				array('title' => '点击数', 'field' => 'clickcount', 'width' => 12),
				array('title' => '下线分销商总数', 'field' => 'levelcount', 'width' => 12),
				array('title' => '一级下线分销商数', 'field' => 'level1', 'width' => 12),
				array('title' => '二级下线分销商数', 'field' => 'level2', 'width' => 12),
				array('title' => '三级下线分销商数', 'field' => 'level3', 'width' => 12),
				array('title' => '累计佣金', 'field' => 'commission_total', 'width' => 12),
				array('title' => '打款佣金', 'field' => 'commission_pay', 'width' => 12),
				array('title' => '注册时间', 'field' => 'createtime', 'width' => 12),
				array('title' => '成为分销商时间', 'field' => 'createtime', 'width' => 12),
				array('title' => '审核状态', 'field' => 'createtime', 'width' => 12),
				array('title' => '是否关注', 'field' => 'followstr', 'width' => 12)
			);

			if (p('diyform')) {
				$columns[] = array('title' => '分销商会员自定义信息', 'field' => 'member_diyformdata', 'width' => 36);
				$columns[] = array('title' => '分销商申请自定义信息', 'field' => 'agent_diyformdata', 'width' => 36);
			}

			m('excel')->export($list, array('title' => '推广下线-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
		}

		load()->func('tpl');
		include $this->template('commission/childlist');
	}

	public function teamlist()
	{
		global $_W;
		global $_GPC;
		$agentlevels = $this->model->getLevels(true, true);
		$level_map = array(1 => '一级', 2 => '二级', 3 => '三级');
		$level_name = $level_map[$_GPC['level']];
		$level = intval($_GPC['level']);
		$agentid = intval($_GPC['id']);
		$member = $this->model->getInfo($agentid);
		$memberinfo = m('member')->getMember($agentid);
		$openid = $memberinfo['openid'];
		$parentInfo = m('member')->getMember($member['agentid']);
		$total = $member['agentcount'];
		$level1 = $member['level1'];
		$level2 = $member['level2'];
		$level3 = $member['level3'];
		$downcount = 0;
		$downs1 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid=:agentid and uniacid=:uniacid and isagent=0', array(':agentid' => $member['id'], ':uniacid' => $_W['uniacid']), 'id');
		$downcount += count($downs1);

		if (!empty($downs1)) {
			$downs2 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid in( ' . implode(',', array_keys($downs1)) . ') and uniacid=:uniacid and isagent=0 ', array(':uniacid' => $_W['uniacid']), 'id');
			$downcount += count($downs2);

			if (!empty($downs2)) {
				$downs3 = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where agentid in( ' . implode(',', array_keys($downs2)) . ') and uniacid=:uniacidand isagent=0 ', array(':uniacid' => $_W['uniacid']), 'id');
				$downcount += count($downs3);
			}
		}

		$child_num = pdo_fetchcolumn('select count(1) from ' . tablename('vcshop_commission_relation') . ' where pid = :pid', array(':pid' => $agentid));
		$level11 = $downcount;
		$newlevel = pdo_fetch('select * from ' . tablename('vcshop_commission_level') . (' where uniacid=:uniacid  and ' . $downcount . ' >= downcount and downcount>0  order by downcount desc limit 1'), array(':uniacid' => $_W['uniacid']));
		$condition = '';
		$params = array();
		$hasagent = false;
		

		if (!empty($_GPC['mid'])) {
			$condition .= ' and dm.id=:mid';
			$params[':mid'] = intval($_GPC['mid']);
		}

		$searchfield = strtolower(trim($_GPC['searchfield']));
		$keyword = trim($_GPC['keyword']);
		if (!empty($keyword)) {
			
				$condition .= ' and ( dm.realname like :keyword or dm.nickname like :keyword or dm.mobile like :keyword)';
				$params[':keyword'] = '%' . $keyword . '%';
			
		}

		if ($_GPC['isagent'] != '') {
			$condition .= ' and dm.isagent=' . intval($_GPC['isagent']);
		}

		if ($_GPC['agentlevel'] != '') {
			$condition .= ' and dm.agentlevel=' . intval($_GPC['agentlevel']);
		}

		if ($_GPC['status'] != '') {
			$condition .= ' and dm.status=' . intval($_GPC['status']);
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND dm.agenttime >= :starttime AND dm.agenttime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		if ($_GPC['followed'] != '') {
			if ($_GPC['followed'] == 2) {
				$condition .= ' and f.follow=0 and dm.uid<>0';
			}
			else {
				$condition .= ' and f.follow=' . intval($_GPC['followed']);
			}
		}

		if ($_GPC['isagentblack'] != '') {
			$condition .= ' and dm.agentblack=' . intval($_GPC['isagentblack']);
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$list = array();

		$mids = $this->columnAgentlist($openid,1);
		if (!empty($mids)) {
			$condition .= ' and dm.id in ('.implode(',', $mids).')';
			$total = pdo_fetchcolumn('select count(dm.id) from' . tablename('vcshop_member') . ' dm ' . ' left join ' . tablename('vcshop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('mc_mapping_fans') . 'f on f.openid=dm.openid' . ' where dm.uniacid =' . $_W['uniacid'] . ('  ' . $condition), $params);
			$sql = 'select dm.*,p.nickname as parentname,p.avatar as parentavatar,l.levelname as levelname  from ' . tablename('vcshop_member') . ' dm ' . ' left join ' . tablename('vcshop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('vcshop_commission_level') . ' l on l.id = dm.agentlevel' . ' left join ' . tablename('mc_mapping_fans') . ('f on f.openid=dm.openid  and f.uniacid=' . $_W['uniacid']) . ' where dm.uniacid = ' . $_W['uniacid'] . ('  ' . $condition . '   ORDER BY dm.agenttime desc');
			if (empty($_GPC['export'])) {
				$sql .= ' limit ' . ($pindex - 1) * $psize . ',' . $psize;
			}

			$list = pdo_fetchall($sql, $params);
			$pager = pagination($total, $pindex, $psize);
			foreach ($list as &$row) {
				$info = $this->model->getInfo($row['openid'], array('total', 'pay'));
				$row['levelcount'] = $info['agentcount'];

				if (1 <= $this->set['level']) {
					$row['level1'] = $info['level1'];
				}

				if (2 <= $this->set['level']) {
					$row['level2'] = $info['level2'];
				}

				if (3 <= $this->set['level']) {
					$row['level3'] = $info['level3'];
				}

				$row['credit1'] = m('member')->getCredit($row['openid'], 'credit1');
				$row['credit2'] = m('member')->getCredit($row['openid'], 'credit2');
				$row['commission_total'] = $info['commission_total'];
				$row['commission_pay'] = $info['commission_pay'];
				$row['followed'] = m('user')->followed($row['openid']);

				if ($row['agentid'] == $member['id']) {
					$row['level'] = 1;
				}
				else if (in_array($row['agentid'], array_keys($member['level1_agentids']))) {
					$row['level'] = 2;
				}
				else {
					if (in_array($row['agentid'], array_keys($member['level2_agentids']))) {
						$row['level'] = 3;
					}
				}
			}
			unset($row);
		}
		

		if ($_GPC['export'] == 1) {
			foreach ($list as &$row) {
				$row['realname'] = str_replace('=', '', $row['realname']);
				$row['nickname'] = str_replace('=', '', $row['nickname']);
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
				$row['agentime'] = empty($row['agenttime']) ? '' : date('Y-m-d H:i', $row['agentime']);
				$row['groupname'] = empty($row['groupname']) ? '无分组' : $row['groupname'];
				$row['levelname'] = empty($row['levelname']) ? '普通等级' : $row['levelname'];
				$row['parentname'] = empty($row['parentname']) ? '总店' : '[' . $row['agentid'] . ']' . $row['parentname'];
				$row['statusstr'] = empty($row['status']) ? '' : '通过';
				$row['followstr'] = empty($row['followed']) ? '' : '已关注';
				if (p('diyform') && !empty($row['diymemberfields']) && !empty($row['diymemberdata'])) {
					$diyformdata_array = p('diyform')->getDatas(iunserializer($row['diymemberfields']), iunserializer($row['diymemberdata']));
					$diyformdata = '';

					foreach ($diyformdata_array as $da) {
						$diyformdata .= $da['name'] . ': ' . $da['value'] . '';
					}

					$row['member_diyformdata'] = $diyformdata;
				}

				if (p('diyform') && !empty($row['diycommissionfields']) && !empty($row['diycommissiondata'])) {
					$diyformdata_array = p('diyform')->getDatas(iunserializer($row['diycommissionfields']), iunserializer($row['diycommissiondata']));
					$diyformdata = '';

					foreach ($diyformdata_array as $da) {
						$diyformdata .= $da['name'] . ': ' . $da['value'] . '';
					}

					$row['agent_diyformdata'] = $diyformdata;
				}
			}

			unset($row);
			$columns = array(
				array('title' => 'ID', 'field' => 'id', 'width' => 12),
				array('title' => '昵称', 'field' => 'nickname', 'width' => 12),
				array('title' => '姓名', 'field' => 'realname', 'width' => 12),
				array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
				array('title' => '微信号', 'field' => 'weixin', 'width' => 12),
				array('title' => 'openid', 'field' => 'openid', 'width' => 24),
				array('title' => '推荐人', 'field' => 'parentname', 'width' => 12),
				array('title' => '分销商等级', 'field' => 'levelname', 'width' => 12),
				array('title' => '点击数', 'field' => 'clickcount', 'width' => 12),
				array('title' => '下线分销商总数', 'field' => 'levelcount', 'width' => 12),
				array('title' => '一级下线分销商数', 'field' => 'level1', 'width' => 12),
				array('title' => '二级下线分销商数', 'field' => 'level2', 'width' => 12),
				array('title' => '三级下线分销商数', 'field' => 'level3', 'width' => 12),
				array('title' => '累计佣金', 'field' => 'commission_total', 'width' => 12),
				array('title' => '打款佣金', 'field' => 'commission_pay', 'width' => 12),
				array('title' => '注册时间', 'field' => 'createtime', 'width' => 12),
				array('title' => '成为分销商时间', 'field' => 'createtime', 'width' => 12),
				array('title' => '审核状态', 'field' => 'createtime', 'width' => 12),
				array('title' => '是否关注', 'field' => 'followstr', 'width' => 12)
			);

			if (p('diyform')) {
				$columns[] = array('title' => '分销商会员自定义信息', 'field' => 'member_diyformdata', 'width' => 36);
				$columns[] = array('title' => '分销商申请自定义信息', 'field' => 'agent_diyformdata', 'width' => 36);
			}

			m('excel')->export($list, array('title' => '推广下线-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
		}

		load()->func('tpl');
		include $this->template('commission/teamlist');
	}
}

?>
