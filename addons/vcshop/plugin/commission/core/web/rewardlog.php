<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class rewardlog_VcShopPage extends WebPage
{
	public function main() 
	{
		global $_W;
		global $_GPC;

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$params = array();
        $params[':uniacid'] = $_W['uniacid'];
		$condition = '';
		$keyword = trim($_GPC['keyword']);

        $mid = $_GPC['mid'];

        if( $mid != '' ){
            $mid = intval($mid);
            
            $condition .= " AND l.mid=:mid ";
			$params[':mid'] = $mid;

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
			$condition .= ' AND l.create_time >= :starttime AND l.create_time <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		$sql = 'SELECT l.*,m.nickname,m.id AS member_id,m.mobile,m.avatar,m.realname,m.weixin FROM '.tablename('vcshop_rewardlog').' l LEFT JOIN '.tablename('vcshop_member').' m ON l.agentid=m.id WHERE l.uniacid=:uniacid '.$condition.' ORDER BY l.id DESC';

		if (empty($_GPC['export']))
		{
			$sql .= ' limit ' . (($pindex - 1) * $psize) . ',' . $psize;
		}
		$list = pdo_fetchall($sql, $params);
		//echo $sql;
		$total = pdo_fetchcolumn('SELECT COUNT(l.id) FROM '.tablename('vcshop_rewardlog').' l LEFT JOIN '.tablename('vcshop_member').' m ON l.agentid=m.id WHERE l.uniacid=:uniacid '.$condition, $params);

		$pager = pagination($total, $pindex, $psize);
		load()->func('tpl');

		include $this->template();
	}
	/*
	public function cancel()
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) {
			$id = intval($_GPC['id']);
			$reward_log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid' => $_W['uniacid'], ':id' => $id));
			$team_log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_team_log') . ' WHERE uniacid=:uniacid AND reward_id=:id',array(':uniacid' => $_W['uniacid'], ':id' => $id));
			if(empty($reward_log) || empty($team_log)){
				show_json(0,'记录异常!');
			}
			if($team_log['status'] > 0){
				show_json(0,'该记录已发放奖励，不能作废!');
			}elseif($team_log['status'] == -1){
				show_json(0,'该记录已作废!');
			}
			$time = time();
			$rdata = array('status'=>-1,'cancel_time'=>$time);
			$tdata = array('status'=>-1,'invalidtime'=>$time);
			$res = pdo_update('vcshop_team_log',$tdata,array('id'=>$team_log['id']));
			if($res){
				pdo_update('vcshop_rewardlog',$rdata,array('id'=>$id));
				plog('commission.rewardlog.cancel', '作废奖励 ID: ' . $reward_log['id']);
				show_json(1);
			}else{
				show_json(0,'操作失败！');
			}
		}
	}
	public function pay()
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) {
			$id = intval($_GPC['id']);
			$reward_log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid' => $_W['uniacid'], ':id' => $id));
			$team_log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_team_log') . ' WHERE uniacid=:uniacid AND reward_id=:id',array(':uniacid' => $_W['uniacid'], ':id' => $id));
			if(empty($reward_log) || empty($team_log)){
				show_json(0,'记录异常!');
			}
			if($team_log['status'] > 0){
				show_json(0,'该记录已发放奖励!');
			}elseif($team_log['status'] == -1){
				show_json(0,'该记录已作废!');
			}
			$time = time();
			$tdata = array('status'=>3,'applytime'=>$time,'checktime'=>$time,'paytime'=>$time);
			$rdata = array('status'=>1,'pay_time'=>$time);
			if($team_log['money'] > 0){
	        	m('member')->setCredit($team_log['openid'], 'credit2', $team_log['money'], array(0,'团队分红:credit2:' . $team_log['money']));
				$res = pdo_update('vcshop_team_log',$tdata,array('id'=>$team_log['id']));
				pdo_update('vcshop_rewardlog',$rdata,array('id'=>$id));
				plog('commission.rewardlog.pay', '发放奖励 ID: ' . $reward_log['id']);
				show_json(1);
			}else{
				show_json(0,'金额：0，异常');
			}
		}
	}
	public function cancel2()
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) {
			$id = intval($_GPC['id']);
			$reward_log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid' => $_W['uniacid'], ':id' => $id));
			
			if(empty($reward_log)){
				show_json(0,'记录异常!');
			}
			if($reward_log['status'] > 0){
				show_json(0,'该记录已发放奖励，不能作废!');
			}elseif($reward_log['status'] == -1){
				show_json(0,'该记录已作废!');
			}
			$time = time();
			$rdata = array('status'=>-1,'cancel_time'=>$time);
			$res = pdo_update('vcshop_rewardlog',$rdata,array('id'=>$id));
			if($res){
				plog('commission.rewardlog.cancel2', '作废奖励 ID: ' . $reward_log['id']);
				show_json(1);
			}else{
				show_json(0,'操作失败！');
			}
		}
	}
	public function pay2()
	{
		global $_W;
		global $_GPC;
		if ($_W['ispost']) {
			$id = intval($_GPC['id']);
			$reward_log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid' => $_W['uniacid'], ':id' => $id));
			if(empty($reward_log)){
				show_json(0,'记录异常!');
			}
			if($reward_log['status'] > 0){
				show_json(0,'该记录已发放奖励!');
			}elseif($reward_log['status'] == -1){
				show_json(0,'该记录已作废!');
			}
			$time = time();
			$rdata = array('status'=>1,'pay_time'=>$time);
			if($reward_log['money'] > 0){
				$agent = m('member')->getMember($reward_log['agentid']);
				if(!$agent){
					show_json(0,'用户异常');
				}
	        	m('member')->setCredit($agent['openid'], 'credit2', $reward_log['money'], array(0,'推荐奖励:credit2:' . $reward_log['money']));
				$res = pdo_update('vcshop_rewardlog',$rdata,array('id'=>$id));
				plog('commission.rewardlog.pay2', '发放奖励 ID: ' . $reward_log['id']);
				show_json(1);
			}else{
				show_json(0,'金额：0，异常');
			}
		}
	}
	*/

}

?>
