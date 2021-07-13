<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'commission/core/page_login_mobile.php';
class Quota_VcShopPage extends CommissionMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$_GPC['type'] = intval($_GPC['type']);
		// zhou 20191122 获取用户配额表
		$member = m('member')->getMember($_W['openid']);
		// zhou 20191121 获取当前能充值的等级  游客没有
		$check_quota = pdo_fetchcolumn('select is_team from ' . tablename('vcshop_commission_level') . ' where id=:id and uniacid=:uniacid' , array(':id'=>$member['agentlevel'],':uniacid'=>$_W['uniacid'])); 
		$quota = pdo_fetchall('select q.*,c.levelname from ' . tablename('vcshop_member_quota') . ' as q LEFT JOIN ' . tablename('vcshop_commission_level') .' as  c on c.id = q.agentlevel ' . ' where q.uniacid=:uniacid and q.openid=:openid ', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
		// zhou 20191122 
		include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$level = intval($_GPC['level']);

		$pindex = max(1, intval($_GPC['page']));
		$uidinfo = M('member')->getInfo($_W['openid']);
		$uid = $uidinfo['uid'];
		$psize = 10;

		// 等级名
		$level_list = pdo_fetchall('select id,levelname from ' . tablename('vcshop_commission_level') . ' where uniacid=:uniacid ',array(':uniacid'=>$_W['uniacid']));
		$levelname = array();
		foreach ($level_list as $key => $value) {
			$levelname[$value['id']] = $value['levelname'];
		}
		$condition = ' and openid=:openid and uniacid=:uniacid'; //  and type=:type
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']); // , ':type' => intval($_GPC['type'])
		// 根据条件
		if(!empty($level)){
			$condition .= " and agentlevel= " . $level;
		}


		$list = pdo_fetchall('select * from ' . tablename('vcshop_member_quota_record') . (' where 1 ' . $condition . ' order by createtime desc LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_member_quota_record') . (' where 1 ' . $condition), $params);

		foreach ($list as &$row) {
			$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
			$row['typestr'] = $levelname[$row['agentlevel']];
		}

		// unset($row);

/*		if (is_array($r)) {
			$list = array_merge($r, $list);
		}
*/
		show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $psize,'levelname'=>$levelname,'level'=>$level,'old_level'=>$_GPC['level'],'condition'=>$condition));
	}
}

?>
