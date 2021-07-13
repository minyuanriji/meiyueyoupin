<?php
echo "<pre>";
error_reporting(0);
require '../../../../../framework/bootstrap.inc.php';
require '../../../../../addons/vcshop/defines.php';
require '../../../../../addons/vcshop/core/inc/functions.php';
ignore_user_abort();
set_time_limit(0);
$sets = pdo_fetchall('select uniacid from ' . tablename('vcshop_sysset'));
if (!empty($sets)) {
	$thismonth = mktime(0,0,0,date('m'),1);//本月
	$lastmonth = mktime(0,0,0,date('m')-1,1);//上月
	$nextmonth = mktime(0,0,0,date('m')+1,1);//下月

	// mktime(hour,minute,second,month,day,year,is_dst);
	// $nowdate = '2020-11-10 16:33:00';
	// $lastdate = '2020-11-10 16:30:00';
	// $nextdate = '2020-11-10 15:38:00';
	// $thismonth = strtotime($nowdate);//本月
	// $lastmonth = strtotime($lastdate);//上月
	// $nextmonth = strtotime($nextdate);//下月

	foreach ($sets as $set) {
		$_W['uniacid'] = $uniacid = $set['uniacid'];

		$p = p('commission');
		$commission = $p->getSet($uniacid);
		$levels = $p->getLevels(true);
		$default = array(
		    'id' => '0',
		    'level' => '0',
		    'levelname' => empty($commission['levelname']) ? '默认等级' : $commission['levelname'],
		    'commission1' => $commission['commission1'],
		    'commission2' => $commission['commission2'],
		    'commission3' => $commission['commission3'],
		    'mix' => $commission['mix'],
		    'is_team' => $commission['is_team'],
		    'monthly_credit' => $commission['monthly_credit'],
		    'withdraw' => (double) $commission['withdraw_default'],
		    'repurchase' => (double) $commission['repurchase_default']
		);
		$levels  = array_merge(array(
		    $default
		), $levels);
		$level_ids = array();
		foreach ($levels as $level) {
			if(!empty($level['monthly_credit']) && $level['monthly_credit'] > 0){
				$level_ids[$level['id']] = $level['id'];
			}
		}
		if(empty($level_ids)){
			continue;
		}

		$datas = pdo_fetchall('select * from ' . tablename('vcshop_virtualcard_data') . ' where uniacid = :uniacid and used = 1 and status = 1 and sendstatus in (1,2) group by userid',array(':uniacid'=>$uniacid));
		
		foreach ($datas as $k => $u) {
			$credittype = 'credit1';
			$cardmember = m('member')->getMember($u['openid']);
			// 如果不是每月送积分的会员 在这里扣积分 如果是就在隔壁commission扣积分
			if (!in_array($cardmember['agentlevel'], $level_ids)) {
				// 清除上月赠送的没用完的积分
				$count = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_member_credit_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and credittype=:credittype and num<0 and operator=0 and `remark` LIKE :remark',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$thismonth,'credittype'=>'credit1',':remark'=>'%decutmonthlycredit%'));
				if($count == 0){
					$last_send = pdo_fetchcolumn('select ifnull(sum(num),0) from ' . tablename('vcshop_member_credit_send_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and createtime<=:createtime2 and operator <> 5',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$lastmonth,':createtime2'=>$thismonth));
					if($last_send > 0){
						$last_used = pdo_fetchcolumn('select ifnull(sum(num),0) from ' . tablename('vcshop_member_credit_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and createtime<=:createtime2 and credittype=:credittype and num<0 and operator=0 and `remark` NOT LIKE :remark',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$lastmonth,':createtime2'=>$thismonth,'credittype'=>'credit1',':remark'=>'%decutmonthlycredit%'));
						$last_used = abs($last_used);
						if($last_used < $last_send){
							$decut = $last_send - $last_used;
							if($decut > 0){
								m('member')->setCredit($u['openid'], $credittype, -$decut, '扣除上月剩余的赠送积分 ' . $decut.' decutmonthlycredit');
							}
						}
					}
				}
			}

			// 送积分
			$send_data = pdo_fetchall('select * from ' . tablename('vcshop_virtualcard_data') . ' where uniacid = :uniacid and userid = :userid and used = 1 and status = 1 and sendstatus in (1,2)',array(':uniacid'=>$uniacid,'userid' => $u['userid']));
			foreach ($send_data as $send => $d) {
				$lastsend_log = pdo_fetch('select * from ' . tablename('vcshop_virtualcard_send_log') . ' where uniacid = :uniacid and dataid = :dataid and userid = :userid and sendtime>:sendtime order by id desc limit 1',array(':uniacid'=>$uniacid,':userid' => $d['userid'],':dataid' => $d['id'],':sendtime'=>$thismonth));
				if (!empty($lastsend_log)) {
					continue;
				}
				if ($d['sendstatus'] == 2) {
					pdo_update('vcshop_virtualcard_data',array('sendstatus' => 3),array('id'=>$d['id']));
					continue;
				}
				
				$member = m('member')->getMember($d['userid']);
				$credit1 = $d['sendcredit1'];
				
				
				$oldmoney = m('member')->getCredit($member['openid'], 'credit1');
				$newcredit = $credit1 + $oldmoney;
				m('member')->setCredit($member['openid'], 'credit1', $credit1, '虚拟卡券兑换送积分 ' . $credit1);
				if ($d['is_alltime'] == 1) {
				    $operator = 5;
				}else{
				    $operator = 6;
				}
				$data = array(
				    'uid' => $member['uid'],
				    'openid' => $member['openid'],
				    'uniacid' => $member['uniacid'],
				    'credittype' => 'credit1',
				    'num' => $credit1,
				    'operator' => $operator,
				    'createtime' => time(),
				    'remark' => '兑换卡' . $d['id'] . '每月赠送积分 OPENID: ' . $member['openid'] . ' 剩余: '.$newcredit,
				    'module' => 'vcshop',
				    'presentcredit' => $newcredit
				);

				pdo_insert('vcshop_member_credit_send_record',$data);
				
				
				pdo_insert('vcshop_virtualcard_send_log',array('uniacid'=>$_W['uniacid'],'cardid'=>$d['virtualcardid'],'dataid'=>$d['id'],'userid'=>$member['id'],'sendtime'=>time(),'status'=>1));

				// if ($d['is_alltime'] == 0) {
					$sendcount = $d['sendmonth'];
					$totalcount = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_virtualcard_send_log') . ' where uniacid = :uniacid and cardid = :cardid and dataid = :dataid and userid = :userid' ,array(':uniacid' => $_W['uniacid'],':cardid' => $d['virtualcardid'],':dataid' => $d['id'], 'userid' => $member['id']));
					if ($totalcount >= $sendcount) {
						pdo_update('vcshop_virtualcard_data',array('sendstatus' => 2),array('id'=>$d['id']));
					}
				// }
			}
		}
		
	}
}

?>
