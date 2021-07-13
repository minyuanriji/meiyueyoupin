<?php
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
				$level_ids[$level['id']] = $level;
			}
		}
		if(empty($level_ids)){
			continue;
		}
		$level_ids_str = implode(',', array_keys($level_ids));
		// 去掉agenttime<:agenttime $thismonth  改成 time()
		$user = pdo_fetchall('select id,openid,uniacid,agentlevel,uid,nickname from ' . tablename('vcshop_member') . ' where uniacid=:uniacid and status=1 and isagent=1 and  agentlevel in('.$level_ids_str.')',array(':uniacid'=>$uniacid));//获取本月之前的分销商
		
		$tm = $commission['tm'];
		// if(true){
		// 	foreach ($user as $k => $u) {
		// 		// if($u['id'] == '19890' || $u['id'] == '20471' || $u['id'] == '206' || $u['id'] == '7'){
		// 		if($u['id'] == '19890' || $u['id'] == '20471'){
		// 			echo $u['nickname'];
		// 			echo "<br>";
		// 			$res = $p->sendMessage($u['openid'], array(), TM_COMMISSION_EXPRIECREDIT);//通知
		// 			if($res){
		// 				echo 'succ';
		// 			echo "<br>";
		// 			}else{
		// 				echo $res;
		// 				echo 'false';
		// 			echo "<br>";
						

		// 			}
		// 			sleep(5);
		// 		}
		// 	}
		// }
		if(!empty($tm['commission_expriecredit_notice']) && !empty($tm['commission_expriecredittitle']) && !empty($tm['commission_expriecredit'])){
			$t = date('t'); // 本月一共有几天，无前导0
			$today = date('j');//今天是第几天，无前导0
			$diff_day = $t - $today + 1;
			$h = intval(date('H'));
			$todaytime = mktime(0,0,0,date('m'),$today);//今天0点

			if(in_array($diff_day, $tm['commission_expriecredit_notice']) && ((9 <= $h && $h <= 12) || (14 <= $h && $h <= 18))){
				// 工作时间发送
				$i = 0;//每次只发4条，每2分钟一次
				foreach ($user as $k => $u) {
					$lasttime = strtotime(m('cache')->getString('expriecreditnotice_'.$u['id'], 'global'));
					$interval = 60 * 60 * 12;//间隔12小时,配合下面的工作时间，其实是1天1检查
					$current = time();
					if($lasttime + $interval > $current){
						continue;
					}
					m('cache')->set('expriecreditnotice'.$u['id'], date('Y-m-d H:i:s', $current), 'global');
					$checksend = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_noticelock') . ' where uniacid=:uniacid and mid=:id and createtime>:createtime',array(':uniacid'=>$uniacid,':id'=>$u['id'],':createtime'=>$todaytime));
					if($checksend == 0){
						$credittype = 'credit1';
						// 查询本月赠送的没用完的积分
						$last_send = pdo_fetchcolumn('select ifnull(sum(num),0) from ' . tablename('vcshop_member_credit_send_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and createtime<=:createtime2',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$thismonth,':createtime2'=>$nextmonth));
						if($last_send > 0){
							$last_used = pdo_fetchcolumn('select ifnull(sum(num),0) from ' . tablename('vcshop_member_credit_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and createtime<=:createtime2 and credittype=:credittype and num<0 and operator=0 and `remark` NOT LIKE :remark',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$thismonth,':createtime2'=>$nextmonth,'credittype'=>'credit1',':remark'=>'%decutmonthlycredit%'));
							$last_used = abs($last_used);
							if($last_used < $last_send){
								$decut = $last_send - $last_used;
								if($decut > 0){
									$p->sendMessage($u['openid'], array(), TM_COMMISSION_EXPRIECREDIT);//通知
									$noticelog = array(
										'uniacid' => $uniacid,
										'mid' => $u['id'],
										'uid' => $u['uid'],
										'type' => 1,
										'createtime' => time()
									);
									pdo_insert('vcshop_noticelock',$noticelog);
									$i++;
									if($i >= 9){
										// 每次只发4条
										break;
									}
									sleep(2);
								}
							}
						}
					}
				}
					
			}
		}

		
	}
}

?>
