<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'commission/core/page_login_mobile.php';
class Apply_VcShopPage extends CommissionMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		$level = $this->set['level'];
		$member = $this->model->getInfo($openid, array());
		$become_reg = $this->set['become_reg'];

		if (empty($become_reg)) {
			if (empty($member['realname'])) {
				$returnurl = urlencode(mobileUrl('commission/apply'));
				$this->message('需要您完善资料才能继续操作!', mobileUrl('member/info', array('returnurl' => $returnurl)), 'info');
			}
		}

		$time = time();
		$day_times = intval($this->set['settledays']) * 3600 * 24;
		$agentLevel = $this->model->getLevel($openid);
		$commission_ok = 0;
		$orderids = array();
		$paystatus = 1;
		if (1 <= $level) {
			// o.status>=3
			$level1_orders = pdo_fetchall('select distinct o.id from ' . tablename('vcshop_order') . ' o ' . ' left join  ' . tablename('vcshop_order_goods') . ' og on og.orderid=o.id ' . (' where o.agentid=:agentid and o.status>=:status  and og.status1=0 and og.nocommission=0 and (' . $time . ' - o.finishtime > ' . $day_times . ') and o.uniacid=:uniacid  group by o.id'), array(':uniacid' => $_W['uniacid'], ':agentid' => $member['id'],':status'=>$paystatus));
			//team && express
			$level1_reward_orders = pdo_fetchall('select distinct o.id from ' . tablename('vcshop_order') . ' o ' . ' left join  ' . tablename('vcshop_rewardlog') . ' t on t.orderid=o.id ' . ' where t.agentid=:agentid and o.status>=:status  and t.status=0 and t.is_delete=0 and (' . $time . ' - o.finishtime > ' . $day_times . ') and o.uniacid=:uniacid  group by o.id', array(':uniacid' => $_W['uniacid'], ':agentid' => $member['id'],':status'=>$paystatus));
			$level1_orders = array_merge($level1_orders,$level1_reward_orders);
			foreach ($level1_orders as $o) {
				if (empty($o['id'])) {
					continue;
				}

				$hasorder = false;

				foreach ($orderids as $or) {
					if ($or['orderid'] == $o['id']) {
						$hasorder = true;
						break;
					}
				}

				if ($hasorder) {
					continue;
				}
				// han 20190619 add ,'isteam_bonus'=>false,'isexpress'=>false

				$orderids[] = array('orderid' => $o['id'], 'level' => 1,'isone'=>false,'isteam_bonus'=>false);
			}
		}

		if (2 <= $level) {
			if (0 < $member['level1']) {
				// o.status>=3
				$level2_orders = pdo_fetchall('select distinct o.id from ' . tablename('vcshop_order') . ' o ' . ' left join  ' . tablename('vcshop_order_goods') . ' og on og.orderid=o.id ' . ' where o.agentid in( ' . implode(',', array_keys($member['level1_agentids'])) . (')  and o.status>=:status  and og.status2=0 and og.nocommission=0 and (' . $time . ' - o.finishtime > ' . $day_times . ') and o.uniacid=:uniacid  group by o.id'), array(':uniacid' => $_W['uniacid'],':status'=>$paystatus));

				foreach ($level2_orders as $o) {
					if (empty($o['id'])) {
						continue;
					}

					$hasorder = false;

					foreach ($orderids as $or) {
						// han 20190725 
						if ($or['orderid'] == $o['id'] && $or['level'] == 2) {
							$hasorder = true;
							break;
						}
					}

					if ($hasorder) {
						continue;
					}

					$orderids[] = array('orderid' => $o['id'], 'level' => 2);
				}
			}
		}

		if (3 <= $level) {
			if (0 < $member['level2']) {
				// o.status>=3
				$level3_orders = pdo_fetchall('select distinct o.id from ' . tablename('vcshop_order') . ' o ' . ' left join  ' . tablename('vcshop_order_goods') . ' og on og.orderid=o.id ' . ' where o.agentid in( ' . implode(',', array_keys($member['level2_agentids'])) . (')  and o.status>=:status  and  og.status3=0 and og.nocommission=0 and (' . $time . ' - o.finishtime > ' . $day_times . ')   and o.uniacid=:uniacid  group by o.id'), array(':uniacid' => $_W['uniacid'],':status'=>$paystatus));

				foreach ($level3_orders as $o) {
					if (empty($o['id'])) {
						continue;
					}

					$hasorder = false;

					foreach ($orderids as $or) {
						// han 20190725 

						if ($or['orderid'] == $o['id'] && $or['level'] == 3) {
							$hasorder = true;
							break;
						}
					}

					if ($hasorder) {
						continue;
					}

					$orderids[] = array('orderid' => $o['id'], 'level' => 3);
				}
			}
		}

		foreach ($orderids as &$o) {
			$goods = pdo_fetchall('SELECT ' . 'og.commission1,og.commission2,og.commission3,og.commissions,' . 'og.status1,og.status2,og.status3,' . 'og.content1,og.content2,og.content3 from ' . tablename('vcshop_order_goods') . ' og' . ' left join ' . tablename('vcshop_goods') . ' g on g.id=og.goodsid  ' . ' where og.orderid=:orderid and og.nocommission=0 and og.uniacid = :uniacid order by og.createtime  desc ', array(':uniacid' => $_W['uniacid'], ':orderid' => $o['orderid']));
			//han 20191125 查询订单
			$order_info = pdo_fetch('select * from ' . tablename('vcshop_order') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $o['orderid'], ':uniacid' => $_W['uniacid']));
			foreach ($goods as $g) {
				$commissions = iunserializer($g['commissions']);
				// han 20190619 add  && ($order_info['agentid'] == $member['id'])
				if ($o['level'] == 1 && $g['status1'] == 0 && ($order_info['agentid'] == $member['id'])) {
					$o['isone'] = true;// han 20190619
					$commission1 = iunserializer($g['commission1']);

					if (empty($commissions)) {
						$commission_ok += isset($commission1['level' . $agentLevel['id']]) ? $commission1['level' . $agentLevel['id']] : $commission1['default'];
					}
					else {
						$commission_ok += isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
					}
				}

				if ($o['level'] == 2 && $g['status2'] == 0) {
					$commission2 = iunserializer($g['commission2']);

					if (empty($commissions)) {
						$commission_ok += isset($commission2['level' . $agentLevel['id']]) ? $commission2['level' . $agentLevel['id']] : $commission2['default'];
					}
					else {
						$commission_ok += isset($commissions['level2']) ? floatval($commissions['level2']) : 0;
					}
				}

				if ($o['level'] == 3 && $g['status3'] == 0) {
					$commission3 = iunserializer($g['commission3']);

					if (empty($commissions)) {
						$commission_ok += isset($commission3['level' . $agentLevel['id']]) ? $commission3['level' . $agentLevel['id']] : $commission3['default'];
					}
					else {
						$commission_ok += isset($commissions['level3']) ? floatval($commissions['level3']) : 0;
					}
				}
			}
			// han 20190619 s
			//统计team express
			$reward_log = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND orderid=:orderid AND is_delete=0 AND status=0',array(':uniacid' => $_W['uniacid'], ':orderid' => $o['orderid']));
			foreach ($reward_log as $tk => $t) {
				if (($o['level'] == 1) && ($t['status'] == 0)  && ($t['agentid'] == $member['id'])) {
					if($t['type'] == 2){
						$o['isteam_bonus'] = true;// han 20190619
					}
					$commission_team_bonus = floatval($t['money']);
					$commission_ok += $commission_team_bonus;
				}
			}
			// han 20190619 e
		}
		unset($o);

		$withdraw = floatval($this->set['withdraw']);

		if ($withdraw <= 0) {
			$withdraw = 1;
		}

		$cansettle = $withdraw <= $commission_ok;
		$member['commission_ok'] = number_format($commission_ok, 2);
		$set_array = array();
		$set_array['charge'] = $this->set['withdrawcharge'];
		$set_array['begin'] = floatval($this->set['withdrawbegin']);
		$set_array['end'] = floatval($this->set['withdrawend']);
		$realmoney = $commission_ok;
		$deductionmoney = 0;

		if (!empty($set_array['charge'])) {
			$money_array = m('member')->getCalculateMoney($commission_ok, $set_array);

			if ($money_array['flag']) {
				$realmoney = $money_array['realmoney'];
				$deductionmoney = $money_array['deductionmoney'];
			}
		}

		$last_data = $this->model->getLastApply($member['id']);
		$canusewechat = !strexists($openid, 'wap_user_') && !strexists($openid, 'sns_qq_') && !strexists($openid, 'sns_wx_') && !strexists($openid, 'sns_wa_');
		$type_array = array();

		if ($this->set['cashcredit'] == 1) {
			$type_array[0]['title'] = $this->set['texts']['withdraw'] . '到' . $_W['shopset']['trade']['moneytext'];
		}

		if ($this->set['cashweixin'] == 1 && $canusewechat) {
			$type_array[1]['title'] = $this->set['texts']['withdraw'] . '到微信钱包';
		}

		if ($this->set['cashother'] == 1) {
			if ($this->set['cashalipay'] == 1) {
				$type_array[2]['title'] = $this->set['texts']['withdraw'] . '到支付宝';

				if (!empty($last_data)) {
					if ($last_data['type'] != 2) {
						$type_last = $this->model->getLastApply($member['id'], 2);

						if (!empty($type_last)) {
							$last_data['realname'] = $type_last['realname'];
							$last_data['alipay'] = $type_last['alipay'];
						}
					}
				}
			}

			if ($this->set['cashcard'] == 1) {
				$type_array[3]['title'] = $this->set['texts']['withdraw'] . '到银行卡';

				if (!empty($last_data)) {
					if ($last_data['type'] != 3) {
						$type_last = $this->model->getLastApply($member['id'], 3);

						if (!empty($type_last)) {
							$last_data['realname'] = $type_last['realname'];
							$last_data['bankname'] = $type_last['bankname'];
							$last_data['bankcard'] = $type_last['bankcard'];
						}
					}
				}

				$condition = ' and uniacid=:uniacid and status=1';
				$params = array(':uniacid' => $_W['uniacid']);
				$banklist = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_commission_bank') . (' WHERE 1 ' . $condition . '  ORDER BY displayorder DESC'), $params);
			}
		}

		if (!empty($last_data)) {
			if (array_key_exists($last_data['type'], $type_array)) {
				$type_array[$last_data['type']]['checked'] = 1;
			}
		}

		if ($_W['ispost']) {
			if ($commission_ok <= 0 || empty($orderids)) {
				show_json(0, '参数错误,请刷新页面后重新提交!');
			}

			$type = intval($_GPC['type']);

			if (!array_key_exists($type, $type_array)) {
				show_json(0, '未选择提现方式，请您选择提现方式后重试!');
			}

			$apply = array();

			if ($type == 2) {
				$realname = trim($_GPC['realname']);
				$alipay = trim($_GPC['alipay']);
				$alipay1 = trim($_GPC['alipay1']);

				if (empty($realname)) {
					show_json(0, '请填写姓名!');
				}

				if (empty($alipay)) {
					show_json(0, '请填写支付宝帐号!');
				}

				if (empty($alipay1)) {
					show_json(0, '请填写确认帐号!');
				}

				if ($alipay != $alipay1) {
					show_json(0, '支付宝帐号与确认帐号不一致!');
				}

				$apply['realname'] = $realname;
				$apply['alipay'] = $alipay;
			}
			else {
				if ($type == 3) {
					$realname = trim($_GPC['realname']);
					$bankname = trim($_GPC['bankname']);
					$bankcard = trim($_GPC['bankcard']);
					$bankcard1 = trim($_GPC['bankcard1']);

					if (empty($realname)) {
						show_json(0, '请填写姓名!');
					}

					if (empty($bankname)) {
						show_json(0, '请选择银行!');
					}

					if (empty($bankcard)) {
						show_json(0, '请填写银行卡号!');
					}

					if (empty($bankcard1)) {
						show_json(0, '请填写确认卡号!');
					}

					if ($bankcard != $bankcard1) {
						show_json(0, '银行卡号与确认卡号不一致!');
					}

					$apply['realname'] = $realname;
					$apply['bankname'] = $bankname;
					$apply['bankcard'] = $bankcard;
				}
			}

			foreach ($orderids as $o) {
				// pdo_update('vcshop_order_goods', array('status' . $o['level'] => 1, 'applytime' . $o['level'] => $time), array('orderid' => $o['orderid'], 'uniacid' => $_W['uniacid']));
				// han 20190619 添加判断level==1的对应情况
				if($o['level'] == 1){
					if($o['isone']){
						pdo_update('vcshop_order_goods', array('status' . $o['level'] => 1, 'applytime' . $o['level'] => $time), array('orderid' => $o['orderid'], 'uniacid' => $_W['uniacid']));
					}
					if($o['isteam_bonus']){
						pdo_update('vcshop_rewardlog', array('status' => 1, 'applytime' => $time), array('orderid' => $o['orderid'], 'uniacid' => $_W['uniacid'], 'agentid'=>$member['id'],'type'=>2,'status'=>0));
					}
				}else{
					pdo_update('vcshop_order_goods', array('status' . $o['level'] => 1, 'applytime' . $o['level'] => $time), array('orderid' => $o['orderid'], 'uniacid' => $_W['uniacid']));
				}
			}

			$applyno = m('common')->createNO('commission_apply', 'applyno', 'CA');
			$apply['uniacid'] = $_W['uniacid'];
			$apply['applyno'] = $applyno;
			$apply['orderids'] = iserializer($orderids);
			$apply['mid'] = $member['id'];
			$apply['commission'] = $commission_ok;
			$apply['type'] = $type;
			$apply['status'] = 1;
			$apply['applytime'] = $time;
			$apply['realmoney'] = $realmoney;
			$apply['deductionmoney'] = $deductionmoney;
			$apply['charge'] = $set_array['charge'];
			$apply['beginmoney'] = $set_array['begin'];
			$apply['endmoney'] = $set_array['end'];
			pdo_insert('vcshop_commission_apply', $apply);
			$apply_type = array('余额', '微信钱包', '支付宝', '银行卡');
			$mcommission = $commission_ok;

			if (!empty($deductionmoney)) {
				$mcommission .= ',实际到账金额:' . $realmoney . ',提现手续费金额:' . $deductionmoney;
			}

			$this->model->sendMessage($openid, array('commission' => $mcommission, 'type' => $apply_type[$apply['type']]), TM_COMMISSION_APPLY);
			$this->model->sendMessage($openid, array('commission' => $mcommission, 'type' => $apply_type[$apply['type']]), TM_COMMISSION_APPLYMONEY);
			show_json(1, '已提交,请等待审核!');
		}

		include $this->template();
	}
}

?>
