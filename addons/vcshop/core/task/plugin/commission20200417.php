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
		$user = pdo_fetchall('select id,openid,uniacid,agentlevel,uid from ' . tablename('vcshop_member') . ' where uniacid=:uniacid and status=1 and isagent=1 and  agentlevel in('.$level_ids_str.')',array(':uniacid'=>$uniacid));//获取本月之前的分销商
		foreach ($user as $k => $u) {
			$credittype = 'credit1';
			// 清除上月赠送的没用完的积分
			$count = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_member_credit_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and credittype=:credittype and num<0 and operator=0 and `remark` LIKE :remark',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$thismonth,'credittype'=>'credit1',':remark'=>'%decutmonthlycredit%'));
			if($count == 0){
				$last_send = pdo_fetchcolumn('select ifnull(sum(num),0) from ' . tablename('vcshop_member_credit_send_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime and createtime<=:createtime2',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$lastmonth,':createtime2'=>$thismonth));
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
			// 以下为赠送积分
			// 本月已发放过或者已经发放够3年36次
			$count = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_member_credit_send_record') . ' where uniacid=:uniacid and openid=:openid and createtime>:createtime',array(':uniacid'=>$uniacid,':openid'=>$u['openid'],':createtime'=>$thismonth));
			if($count >= 1){
				continue;
			}
			$count = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_member_credit_send_record') . ' where uniacid=:uniacid and openid=:openid',array(':uniacid'=>$uniacid,':openid'=>$u['openid']));
			if($count >= 36){
				continue;
			}
			// 去掉 and paytime<:paytime  ,':paytime'=>$thismonth
			$orders = pdo_fetchall('select id,status from ' . tablename('vcshop_order') . ' where status in(1,2,3) and openid=:openid and uniacid=:uniacid',array(':uniacid'=>$uniacid,':openid'=>$u['openid']));
			if(empty($orders)){
				// 没买过产品
				continue;
			}else{
				$checkcredit = true;
				foreach ($orders as  $order) {
					$count = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_order_goods') . ' where uniacid=:uniacid and orderid=:orderid and cretidgoods=1',array(':uniacid'=>$uniacid,':orderid'=>$order['id']));
					if($count >= 1){
						$checkcredit = false;
						break;
					}
				}
				if($checkcredit){
					// 没买过产品
					continue;
				}
			}
			$money = (int) $level_ids[$u['agentlevel']]['monthly_credit'];
			$oldmoney = m('member')->getCredit($u['openid'], $credittype);
			$newcredit = $money + $oldmoney;
			m('member')->setCredit($u['openid'], $credittype, $money, '每月赠送积分 ' . $money);
			$data = array(
				'uid' => $u['uid'],
				'openid' => $u['openid'],
				'uniacid' => $u['uniacid'],
				'credittype' => $credittype,
				'num' => $money,
				'operator' => 0,
				'createtime' => time(),
				'remark' => '每月赠送积分 OPENID: ' . $u['openid'] . ' 剩余: '.$newcredit,
				'module' => 'vcshop',
				'presentcredit' => $newcredit
			);
			pdo_insert('vcshop_member_credit_send_record',$data);
		}

		// 24小时后检查未发放奖励的升级

		$diff_time = time() - (24*60*60);
		$com_level_record = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_commission_level_record') . ' WHERE notice_status=3 AND createtime<:time and type=0',array(':time'=>$diff_time));//升级
		if(!empty($com_level_record)){
			foreach ($com_level_record as $key => $value) {
				$check_thislog = pdo_fetch('select * from ' . tablename('vcshop_commission_level_record') . ' where id=:id',array(':id'=>$value['id']));
				if($check_thislog['notice_status'] > 3){
					break;
				}
				$member = m('member')->getMember($value['mid']);
				if(!$member){
					pdo_update('vcshop_commission_level_record',array('notice_status'=>4),array('id'=>$value['id'],'notice_status'=>3));
					continue;
				}
				if(!empty($value['agentlevel'])){
					$level = pdo_fetch('SELECT id,level,levelname FROM ' . tablename('vcshop_commission_level') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid'=>$_W['uniacid'], ':id'=>$value['agentlevel']));
				}else{
					$level = array('id'=>$default['id'],'level'=>$default['level'],'levelname'=>$default['levelname']);
				}
				$has_rec = pdo_fetchcolumn('SELECT ifnull(count(*),0) FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND mid=:mid AND type=:type AND levelweight=:levelweight',array(':uniacid'=>$_W['uniacid'],':type'=>1,':mid' =>$member['id'],':levelweight'=>$level['level']));
				if($has_rec > 0){
					continue;
				} 
				$parents = pdo_fetchall('SELECT r.level as relation_level,m.id,m.openid,m.nickname,m.agentlevel,m.isagent,m.status,l.level as levelweight,l.mix,l.is_team FROM ' . tablename('vcshop_commission_relation') . ' r' . ' LEFT JOIN ' . tablename('vcshop_member') . ' m ON m.id=r.pid' . ' LEFT JOIN ' . tablename('vcshop_commission_level') . ' l ON l.id=m.agentlevel' . ' WHERE r.id=:id and m.status=1 and m.isagent=1 ORDER BY relation_level ASC',array(':id'=>$member['id']));
				if(empty($parents) || empty($member['agentid'])){
					pdo_update('vcshop_commission_level_record',array('notice_status'=>4),array('id'=>$value['id'],'notice_status'=>3));
				    continue;
				}
				// han 20191121 这里还没做，直接跳出
				// 应该添加奖励状态记录，防止名额重复扣
                $last_weight = 0;
                $last_turn = 0;
                $isallno = true;
                $isfirst = true;
				foreach ($parents as $ak => &$agent) {
					$agent['levelweight'] = (empty($agent['levelweight'])) ? 0 : $agent['levelweight'];
					$check_thislog = pdo_fetch('select * from ' . tablename('vcshop_commission_level_record') . ' where id=:id',array(':id'=>$value['id']));
					if($check_thislog['notice_status'] > 3){
		                $isfirst = false;
						break;
					}
					if( ($agent['levelweight'] < $level['level']) || (!empty($last_turn) && $agent['levelweight'] <= $last_weight) ){
                        continue;
                    }
                    $last_turn++;
                    $last_weight = $agent['levelweight'];
    			    $agent_quota = pdo_fetch('SELECT * FROM ' . tablename('vcshop_member_quota') . ' WHERE uniacid=:uniacid AND mid=:mid AND agentlevel=:agentlevel',array(':uniacid'=>$_W['uniacid'], ':mid'=>$agent['id'], ':agentlevel' => $level['id']));
    			    if($agent_quota && $agent_quota['num'] > 0){
    			        $res = $p->setQuota($agent['openid'],$level['id'],-1,'团队会员-['.$member['id'].']'.$member['nickname'].'升级为['.$level['levelname'].'],扣除库存');
    			        if($res){
    			        	$isallno = false;
    			        	pdo_update('vcshop_commission_level_record',array('notice_status'=>5),array('id'=>$value['id']));
	    			        $agent['mix_'] = (is_serialized($agent['mix'])) ? iunserializer($agent['mix']) : $agent['mix'];
	    			        $team_bonus = $agent['mix_']['rec'][$level['id']]['team_bonus'];
	                        $team_quota = $agent['mix_']['rec'][$level['id']]['user_quota'];
	    			        $desc = '团队会员-['.$member['id'].']'.$member['nickname'].'升级为['.$level['levelname'].'],获奖励';
	    			        if($team_quota>0){
	    			            $p->setQuota($agent['openid'],$commission['quota_level'],$team_quota,'团队会员-['.$member['id'].']'.$member['nickname'].'升级为['.$level['levelname'].'],奖励库存');
	    			        }
	    			        if($team_bonus>0){
	    			            m('member')->setCredit($agent['openid'], 'credit2', $team_bonus, $desc . $team_bonus);
	    			            // $p->insertRewardlog($_W['uniacid'],$member['id'],$agent['id'],$team_bonus,$desc,1,$level['id'],$level['level'],$agent['relation_level'],1);//1为type
	    			            if(empty($value['orderid'])){
	    			                $p->insertRewardlog($_W['uniacid'],$member['id'],$agent['id'],$team_bonus,$desc,1,$level['id'],$level['level'],$agent['relation_level'],1);//1为type
	    			            }else{
	    			                $p->insertRewardlog($_W['uniacid'],$member['id'],$agent['id'],$team_bonus,$desc,2,$level['id'],$level['level'],$agent['relation_level'],1,$value['orderid']);//1为type
	    			            }
	    			        }
	    			        break;
    			        }
    			    }
    			    
				}
				unset($agent);
				if($isallno){
					// 全部上级都扣不了，不发奖励
		        	pdo_update('vcshop_commission_level_record',array('notice_status'=>4),array('id'=>$value['id'],'notice_status'=>3));
				}
				if($isfirst){
					$agents = pdo_fetch('select m.id,m.openid,m.agentlevel,l.level as levelweight from ' . tablename('vcshop_member') . ' m' . ' left join ' . tablename('vcshop_commission_level') . ' l ON l.id=m.agentlevel' . ' WHERE m.id=:id',array(':id'=>$member['agentid']));
					if($level['level'] > $agents['levelweight']){
						// 比直推上级高级，断关系
						$isbreak = false;
						foreach ($parents as $ak => $agent) {
							if($agent['levelweight'] >= $level['level']){
								$p->delRelation($member['id']);
								$mem = $p->saveRelation($member['id'], $agent['id']);
								$isbreak = true;
								pdo_update('vcshop_member',array('agentid'=>$agent['id']),array('id'=>$member['id']));
								break;
							}
						}
						if(!$isbreak){
							// 已经没有上级了
							$p->delRelation($member['id']);
							pdo_update('vcshop_member',array('agentid'=>0),array('id'=>$member['id']));
						}
					}
				}
			}
		}
		$com_level_record = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_commission_level_record') . ' WHERE notice_status=3 AND createtime<:time and type=1',array(':time'=>$diff_time));//补货
		if(!empty($com_level_record)){
			foreach ($com_level_record as $key => $value) {
				$check_thislog = pdo_fetch('select * from ' . tablename('vcshop_commission_level_record') . ' where id=:id',array(':id'=>$value['id']));
				if($check_thislog['notice_status'] > 3){
					break;
				}
				$member = m('member')->getMember($value['mid']);
				if(!$member){
					pdo_update('vcshop_commission_level_record',array('notice_status'=>4),array('id'=>$value['id'],'notice_status'=>3));
					continue;
				}
				if(!empty($value['agentlevel'])){
					$level = pdo_fetch('SELECT id,level,levelname FROM ' . tablename('vcshop_commission_level') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid'=>$_W['uniacid'], ':id'=>$value['agentlevel']));
				}else{
					$level = array('id'=>$default['id'],'level'=>$default['level'],'levelname'=>$default['levelname']);
				}

				// $has_rec = pdo_fetchcolumn('SELECT ifnull(count(*),0) FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND mid=:mid AND type=:type AND levelweight=:levelweight',array(':uniacid'=>$_W['uniacid'],':type'=>1,':mid' =>$member['id'],':levelweight'=>$level['level']));
				// if($has_rec > 0){
				// 	continue;
				// } 
				$parents = pdo_fetchall('SELECT r.level as relation_level,m.id,m.openid,m.nickname,m.agentlevel,m.isagent,m.status,l.level as levelweight,l.mix,l.is_team FROM ' . tablename('vcshop_commission_relation') . ' r' . ' LEFT JOIN ' . tablename('vcshop_member') . ' m ON m.id=r.pid' . ' LEFT JOIN ' . tablename('vcshop_commission_level') . ' l ON l.id=m.agentlevel' . ' WHERE r.id=:id and m.status=1 and m.isagent=1 ORDER BY relation_level ASC',array(':id'=>$member['id']));
				if(empty($parents) || empty($member['agentid'])){
					pdo_update('vcshop_commission_level_record',array('notice_status'=>4),array('id'=>$value['id'],'notice_status'=>3));
				    continue;
				}
				// han 20191121 这里还没做，直接跳出
				// 应该添加奖励状态记录，防止名额重复扣
                $last_weight = 0;
                $last_turn = 0;
                $isallno = true;
				foreach ($parents as $ak => &$agent) {
					$agent['levelweight'] = (empty($agent['levelweight'])) ? 0 : $agent['levelweight'];
					$check_thislog = pdo_fetch('select * from ' . tablename('vcshop_commission_level_record') . ' where id=:id',array(':id'=>$value['id']));
					if($check_thislog['notice_status'] > 3){
						break;
					}
					if( ($agent['levelweight'] < $level['level']) || (!empty($last_turn) && $agent['levelweight'] <= $last_weight) ){
                        continue;
                    }
                    $last_turn++;
                    $last_weight = $agent['levelweight'];
    			    $agent_quota = pdo_fetch('SELECT * FROM ' . tablename('vcshop_member_quota') . ' WHERE uniacid=:uniacid AND mid=:mid AND agentlevel=:agentlevel',array(':uniacid'=>$_W['uniacid'], ':mid'=>$agent['id'], ':agentlevel' => $level['id']));
    			    if($agent_quota && $agent_quota['num'] > 0){
    			        $res = $p->setQuota($agent['openid'],$level['id'],-1,'团队会员-['.$member['id'].']'.$member['nickname'].'补充['.$level['levelname'].']等级名额,扣除库存');
    			        if($res){
    			        	$isallno = false;
    			        	pdo_update('vcshop_commission_level_record',array('notice_status'=>5),array('id'=>$value['id']));
	    			        $agent['mix_'] = (is_serialized($agent['mix'])) ? iunserializer($agent['mix']) : $agent['mix'];
	    			        $team_bonus = $agent['mix_']['rec'][$level['id']]['team_bonus'];
	                        $team_quota = $agent['mix_']['rec'][$level['id']]['user_quota'];
	    			        $desc = '团队会员-['.$member['id'].']'.$member['nickname'].'补充['.$level['levelname'].']等级名额,获奖励';
	    			        if($team_quota>0){
	    			            $p->setQuota($agent['openid'],$commission['quota_level'],$team_quota,'团队会员-['.$member['id'].']'.$member['nickname'].'补充['.$level['levelname'].']等级名额,奖励库存');
	    			        }
	    			        if($team_bonus>0){
	    			            m('member')->setCredit($agent['openid'], 'credit2', $team_bonus, $desc . $team_bonus);
	    			            
	    			            if(empty($value['orderid'])){
	    			                $p->insertRewardlog($_W['uniacid'],$member['id'],$agent['id'],$team_bonus,$desc,1,$level['id'],$level['level'],$agent['relation_level'],1);//1为type
	    			            }else{
	    			                $p->insertRewardlog($_W['uniacid'],$member['id'],$agent['id'],$team_bonus,$desc,2,$level['id'],$level['level'],$agent['relation_level'],1,$value['orderid']);//1为type
	    			            }
	    			        }
	    			        break;
    			        }
    			    }
    			    
				}
				unset($agent);
				if($isallno){
					// 全部上级都扣不了，不发奖励
		        	pdo_update('vcshop_commission_level_record',array('notice_status'=>4),array('id'=>$value['id'],'notice_status'=>3));
				}
				
			}
		}
		$sql = 'update ' . tablename('vcshop_message_notice_log') . (' set is_read = 1,end_time = '.time().' where create_time < ' . $diff_time . ' and is_read=0;');
		pdo_query($sql);//更新全部超过24小时的信息
		
		// 检查是否通知未使用完积分
		$lasttime = strtotime(m('cache')->getString('expriecreditnotice', 'global'));
		// $interval = 2;
		$interval = 60 * 60 * 12;//间隔12小时,配合下面的工作时间，其实是1天1检查
		$current = time();
		$tm = $commission['tm'];
		if ($lasttime + $interval <= $current) {
			m('cache')->set('expriecreditnotice', date('Y-m-d H:i:s', $current), 'global');
			if(!empty($tm['commission_expriecredit_notice']) && !empty($tm['commission_expriecredittitle']) && !empty($tm['commission_expriecredit'])){
				$t = date('t'); // 本月一共有几天，无前导0
				$today = date('j');//今天是第几天，无前导0
				$diff_day = $t - $today + 1;
				$h = intval(date('H'));
				$todaytime = mktime(0,0,0,date('m'),$today);//今天0点

				if(in_array($diff_day, $tm['commission_expriecredit_notice']) && ((9 <= $h && $h <= 12) || (14 <= $h && $h <= 18))){
					// 工作时间发送
					foreach ($user as $k => $u) {
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
									}
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
