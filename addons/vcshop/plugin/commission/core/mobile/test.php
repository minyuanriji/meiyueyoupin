<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'commission/core/page_login_mobile.php';
class Test_VcShopPage extends CommissionMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$_W['openid'] = 'oHQr7wtu2WAuvNyZxBDrLscSRgaQ';
		$member = $this->model->getInfo($_W['openid']);
		$total = 0;

		// 20200321 lin
		$teamchild = 0;
		// 第一步 查询会员的分销等级的权重
		$clevel = pdo_fetchcolumn('select level from '.tablename('vcshop_commission_level').' where id = :agentlevel and uniacid = :uniacid',array(':agentlevel'=>$member['agentlevel'],':uniacid'=>$_W['uniacid']));
		// 第二步 查询会员下面的仔
		$childmembers = pdo_fetchall('select r.id,m.openid,r.level,m.agentlevel from '.tablename('vcshop_commission_relation'). ' as r left join '.tablename('vcshop_member').' as m on m.id = r.id  where pid = :pid and m.uniacid = :uniacid order by level ASC',array(':pid'=>$member['id'],':uniacid'=>$_W['uniacid']));
		
		$maxmember = []; // 分销等级比此会员大或者相等的会员 大哥组
		$teammember = []; // 团队数组
		if (!empty($childmembers)) {
			foreach ($childmembers as $child => &$childs) {
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
			$total = count($teammember);
		}

		// 20200513 lin
		$data = m('common')->getPluginset('commission');
		$showteam = json_decode($data['showteam'],true);
		$showchild = json_decode($data['showchild'],true);
		// $total = count($teammember);
		print_r($total);
		// include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		global $_S;

		$openid = $_W['openid'];
		$member = $this->model->getInfo($openid);

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$list = [];
		$total = 0;

		// 20200321 lin
		$teamchild = 0;
		// 第一步 查询会员的分销等级的权重
		$clevel = pdo_fetchcolumn('select level from '.tablename('vcshop_commission_level').' where id = :agentlevel and uniacid = :uniacid',array(':agentlevel'=>$member['agentlevel'],':uniacid'=>$_W['uniacid']));
		// 第二步 查询会员下面的仔
		$childmembers = pdo_fetchall('select r.id,m.openid,r.level,m.agentlevel from '.tablename('vcshop_commission_relation'). ' as r left join '.tablename('vcshop_member').' as m on m.id = r.id  where pid = :pid and m.uniacid = :uniacid order by level ASC',array(':pid'=>$member['id'],':uniacid'=>$_W['uniacid']));
		$maxmember = []; // 分销等级比此会员大或者相等的会员 大哥组
		$teammember = []; // 团队数组
		if (!empty($childmembers)) {
			foreach ($childmembers as $child => &$childs) {
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

			$total = count($teammember);
			if ($total > 0) {
				$teamarr = array_slice($teammember, ($pindex - 1) * $psize, $psize);
				$list = pdo_fetchall('select * from ' . tablename('vcshop_member') . ' where uniacid = ' . $_W['uniacid'] . ' and id in ('.implode(',', $teamarr).')');
				// 20200513 lin
				foreach ($list as &$row) {
					// 20200513 lin
					$agentlevelname = p('commission')->getLevel($row['openid']);
					if (empty($agentlevelname)) {
						$set = p('commission')->getSet();
						$row['agentlevelname'] = empty($set['levelname']) ? '普通等级' : $set['levelname'];
					}else{
						$row['agentlevelname'] = $agentlevelname['levelname'];
					}
					$memberlevelname = m('member')->getLevel($row['openid']);
					if (empty($memberlevelname)) {
						$row['memberlevelname'] = empty($_S['shop']['levelname']) ? '普通会员' : $_S['shop']['levelname'];
					}else{
						$row['memberlevelname'] = $memberlevelname['levelname'];
					}
					$row['agenttime'] = date('Y-m-d H:i', $row['agenttime']);
				}
			}

		}
		
		show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $psize));
	}
}

?>
