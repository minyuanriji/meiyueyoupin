<?php
function order_sort($a, $b)
{
	if ($a['createtime'] == $b['createtime']) {
		return 0;
	}

	return $a['createtime'] < $b['createtime'] ? 1 : -1;
}

if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'commission/core/page_login_mobile.php';
class Newchild_VcShopPage extends CommissionMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$member = m('member')->getMember($_W['openid']);
		// 20200321 lin
		$newchilds = 0;
		// 第一步 查询会员的分销等级的权重
		$clevel = pdo_fetchcolumn('select level from '.tablename('vcshop_commission_level').' where id = :agentlevel and uniacid = :uniacid',array(':agentlevel'=>$member['agentlevel'],':uniacid'=>$_W['uniacid']));
		// 第二步 查询会员下面的仔
		$childmembers = pdo_fetchall('select r.id,m.openid,r.level,m.agentlevel from '.tablename('vcshop_commission_relation'). ' as r left join '.tablename('vcshop_member').' as m on m.id = r.id  where pid = :pid and m.uniacid = :uniacid order by level ASC',array(':pid'=>$member['id'],':uniacid'=>$_W['uniacid']));
		$childarr = []; // 线下会员
		if (!empty($childmembers)) {
			foreach ($childmembers as $child => &$childs) {
				// 把仔传进数组 以便查询他有没有买升级产品
				$childarr[$child] = "'".$childs['openid']."'";
			}
			// 统计仔仔们有没有购买升级产品
			$newchild = pdo_fetchall('select id,openid from '.tablename('vcshop_order').' where openid in ('.implode(',',$childarr).') and upgradeorder = 1 and status = 3 group by openid' );
			$newchilds = ($newchild)?count($newchild):0;
		}
		// 20200513 lin
		$data = m('common')->getPluginset('commission');
		$showteam = json_decode($data['showteam'],true);
		$showchild = json_decode($data['showchild'],true);
		include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		global $_S;
		$openid = $_W['openid'];
		$member = m('member')->getMember($openid);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$list = [];

		// 20200321 lin
		$newchilds = 0;
		// 第一步 查询会员的分销等级的权重
		$clevel = pdo_fetchcolumn('select level from '.tablename('vcshop_commission_level').' where id = :agentlevel and uniacid = :uniacid',array(':agentlevel'=>$member['agentlevel'],':uniacid'=>$_W['uniacid']));
		// 第二步 查询会员下面的仔
		$childmembers = pdo_fetchall('select r.id,m.openid,r.level,m.agentlevel from '.tablename('vcshop_commission_relation'). ' as r left join '.tablename('vcshop_member').' as m on m.id = r.id  where pid = :pid and m.uniacid = :uniacid order by level ASC',array(':pid'=>$member['id'],':uniacid'=>$_W['uniacid']));
		$childarr = []; // 线下会员
		if (!empty($childmembers)) {
			foreach ($childmembers as $child => &$childs) {
				// 把仔传进数组 以便查询他有没有买升级产品
				$childarr[$child] = "'".$childs['openid']."'";
			}
			// 统计仔仔们有没有购买升级产品
			$newchild = pdo_fetchcolumn('select count(id) from '.tablename('vcshop_order').' where openid in ('.implode(',',$childarr).') and upgradeorder = 1 group by openid' );
			$list = pdo_fetchall('select id,openid,ordersn,price,createtime,status from '.tablename('vcshop_order').' where openid in ('.implode(',',$childarr).') and upgradeorder = 1 and status = 3 order by createtime desc limit ' . ($pindex - 1) * $psize . ',' . $psize );
			$total = pdo_fetchcolumn('select count(id) from '.tablename('vcshop_order').' where openid in ('.implode(',',$childarr).') and upgradeorder = 1 and status = 3');
			$newchilds = ($newchild)?$newchild:0;

			foreach ($list as &$row) {
				$goods = pdo_fetchall('SELECT og.id,og.goodsid,g.thumb,og.price,og.total,g.title,og.optionname,' . 'og.commission1,og.commission2,og.commission3,og.commissions,' . 'og.status1,og.status2,og.status3,' . 'og.content1,og.content2,og.content3 from ' . tablename('vcshop_order_goods') . ' og' . ' left join ' . tablename('vcshop_goods') . ' g on g.id=og.goodsid  ' . ' where og.orderid=:orderid and og.nocommission=0 and og.uniacid = :uniacid order by og.createtime  desc ', array(':uniacid' => $_W['uniacid'], ':orderid' => $row['id']));
				$goods = set_medias($goods, 'thumb');
				$row['order_goods'] = set_medias($goods, 'thumb');
				$row['buyer'] = m('member')->getMember($row['openid']);
				// 20200513 lin
				$agentlevelname = p('commission')->getLevel($row['openid']);
				if (empty($agentlevelname)) {
					$set = p('commission')->getSet();
					$row['buyer']['agentlevelname'] = empty($set['levelname']) ? '普通等级' : $set['levelname'];
				}else{
					$row['buyer']['agentlevelname'] = $agentlevelname['levelname'];
				}
				$memberlevelname = m('member')->getLevel($row['openid']);
				if (empty($memberlevelname)) {
					$row['buyer']['memberlevelname'] = empty($_S['shop']['levelname']) ? '普通会员' : $_S['shop']['levelname'];
				}else{
					$row['buyer']['memberlevelname'] = $memberlevelname['levelname'];
				}
				$row['buyer']['agenttime'] = date('Y-m-d H:i', $row['buyer']['agenttime']);
				if ($row['status'] == 0) {
					$row['statusstr'] = '待付款';
				}
				else if ($row['status'] == 1) {
					$row['statusstr'] = '已付款';
				}
				else if ($row['status'] == 2) {
					$row['statusstr'] = '待收货';
				}
				else {
					if ($row['status'] == 3) {
						$row['statusstr'] = '已完成';
					}
				}
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
			}
		}
		show_json(1, array('list' => $list, 'pagesize' => $psize, 'total' => $total));
	}
}

?>
