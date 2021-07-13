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
		
		$user = pdo_fetchall('select * from ' . tablename('vcshop_virtualcard_data') . ' where uniacid = :uniacid and used = 1 and status = 1 and sendstatus in (1,2) group by userid',array(':uniacid'=>$uniacid));

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
					$member = m('member')->getMember($u['userid']);
					$lasttime = strtotime(m('cache')->getString('vrcreditnotice'.$u['id'], 'global'));
					$interval = 60 * 60 * 12;//间隔12小时,配合下面的工作时间，其实是1天1检查
					$current = time();
					if($lasttime + $interval > $current){
						continue;
					}
					m('cache')->set('expriecreditnotice'.$member['id'], date('Y-m-d H:i:s', $current), 'global');
					$checksend = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_noticelock') . ' where uniacid=:uniacid and mid=:id and createtime>:createtime',array(':uniacid'=>$uniacid,':id'=>$member['id'],':createtime'=>$todaytime));
					if($checksend == 0){
						$credittype = 'credit1';
						// 查询本月赠送的没用完的积分
						$last_send = pdo_fetchcolumn('select ifnull(sum(num),0) from ' . tablename('vcshop_member_credit_send_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and createtime<=:createtime2 and operator <> 5',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$thismonth,':createtime2'=>$nextmonth));
						if($last_send > 0){
							$last_used = pdo_fetchcolumn('select ifnull(sum(num),0) from ' . tablename('vcshop_member_credit_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and createtime<=:createtime2 and credittype=:credittype and num<0 and operator=0 and `remark` NOT LIKE :remark',array(':uniacid'=>$uniacid,':openid'=>$member['openid'],':createtime'=>$thismonth,':createtime2'=>$nextmonth,'credittype'=>'credit1',':remark'=>'%decutmonthlycredit%'));
							$last_used = abs($last_used);
							if($last_used < $last_send){
								$decut = $last_send - $last_used;
								if($decut > 0){
									$p->sendMessage($member['openid'], array(), TM_COMMISSION_EXPRIECREDIT);//通知
									$noticelog = array(
										'uniacid' => $uniacid,
										'mid' => $u['id'],
										'uid' => $u['uid'],
										'type' => 1,
										'createtime' => time()
									);
									pdo_insert('vcshop_noticelock',$noticelog);
									$i++;
									// if($i >= 4){
									// 	// 每次只发4条
									// 	break;
									// }
									sleep(5);
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
