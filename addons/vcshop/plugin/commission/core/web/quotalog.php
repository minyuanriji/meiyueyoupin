<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class quotalog_VcShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_S;
		global $_GPC;
		$set = $_S['commission'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$params = array();
        $params[':uniacid'] = $_W['uniacid'];
		$condition = '';
		$keyword = trim($_GPC['keyword']);

        $mid = $_GPC['mid'];

        if( $mid != '' ){
            $mid = intval($mid);
            $sql = 'SELECT openid FROM '.tablename('vcshop_member').' WHERE id=:id AND uniacid=:uniacid';
            $openid = pdo_fetchcolumn($sql,array( ':id'=>$mid,':uniacid'=>$_W['uniacid']) );
            $condition .= " AND l.agent_member_openid='$openid' ";

        }

		if (!(empty($keyword)) )
		{
			$condition .= ' and ( m.realname like :keyword or m.nickname like :keyword or m.mobile like :keyword)';
			$params[':keyword'] = '%' . $keyword . '%';
		}

		if (empty($starttime) || empty($endtime)) 
		{
			$starttime = strtotime('-1 month');
			$endtime = time();
		}
		if (!(empty($_GPC['time']['start'])) && !(empty($_GPC['time']['end']))) 
		{
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND l.createtime >= :starttime AND l.createtime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		$sql = 'SELECT l.*,m.nickname,m.id AS member_id,m.mobile,m.avatar,m.realname,m.weixin FROM '.tablename('vcshop_member_quota_record').' l LEFT JOIN '.tablename('vcshop_member').' m ON l.mid=m.id WHERE l.uniacid=:uniacid '.$condition.' ORDER BY l.id DESC';

		if (empty($_GPC['export']))
		{
			$sql .= ' limit ' . (($pindex - 1) * $psize) . ',' . $psize;
		}
		$list = pdo_fetchall($sql, $params);
		foreach ($list as $key => &$value) {
			if($value['agentlevel'] == 0){
				$value['levelname'] = empty($set['levelname']) ? '默认等级' : $set['levelname'];
			}else{
				$value['levelname'] = pdo_fetchcolumn('SELECT levelname FROM ' . tablename('vcshop_commission_level') . ' WHERE id=:id',array(':id'=>$value['agentlevel']));
			}
		}
		unset($value);
		//echo $sql;
		$total = pdo_fetchcolumn('SELECT COUNT(l.id) FROM '.tablename('vcshop_member_quota_record').' l LEFT JOIN '.tablename('vcshop_member').' m ON l.mid=m.id WHERE l.uniacid=:uniacid '.$condition, $params);

		unset($row);

		$pager = pagination($total, $pindex, $psize);
		load()->func('tpl');

		include $this->template();
	}
	


}

?>
