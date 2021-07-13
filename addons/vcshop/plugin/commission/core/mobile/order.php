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
class Order_VcShopPage extends CommissionMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$member = $this->model->getInfo($_W['openid'], array('total', 'ordercount0'));
		include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		$member = $this->model->getInfo($openid, array('ordercount0'));
		$agentLevel = $this->model->getLevel($openid);
		$level = intval($this->set['level']);
		$status = trim($_GPC['status']);
		$condition = ' and o.status>=0';

		if ($status != '') {
			$condition = ' and o.status=' . intval($status);
		}

		$orders = array();
		$level1 = $member['level1'];
		$level2 = $member['level2'];
		$level3 = $member['level3'];
		$ordercount = $member['ordercount0'];

		if (1 <= $level) {
			$level1_memberids = pdo_fetchall('select id from ' . tablename('vcshop_member') . ' where uniacid=:uniacid and agentid=:agentid', array(':uniacid' => $_W['uniacid'], ':agentid' => $member['id']), 'id');
			$level1_orders = pdo_fetchall('select commission1,o.id,o.createtime,o.price,og.commissions from ' . tablename('vcshop_order_goods') . ' og ' . ' left join  ' . tablename('vcshop_order') . ' o on og.orderid=o.id ' . (' where o.uniacid=:uniacid and o.agentid=:agentid ' . $condition . ' and og.status1>=0 and og.nocommission=0'), array(':uniacid' => $_W['uniacid'], ':agentid' => $member['id']));

			foreach ($level1_orders as $o) {
				if (empty($o['id'])) {
					continue;
				}

				$commissions = iunserializer($o['commissions']);
				$commission = iunserializer($o['commission1']);

				if (empty($commissions)) {
					$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : (isset($commission['default']) ? $commission['default'] : 0);
				}
				else {
					$commission_ok = isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
				}

				$hasorder = false;

				foreach ($orders as &$or) {
					if ($or['id'] == $o['id'] && $or['level'] == 1) {
						$or['commission'] += $commission_ok;
						$hasorder = true;
						break;
					}
				}

				unset($or);

				if (!$hasorder) {
					$orders[] = array('id' => $o['id'], 'commission' => $commission_ok, 'createtime' => $o['createtime'], 'level' => 1,'levels' =>array(1));
				}
			}
			//team_bonus 20190618 s
			$level1_orders_team_bonus = pdo_fetchall('SELECT o.id,o.createtime,t.money FROM ' . tablename('vcshop_rewardlog') . ' t' . ' LEFT JOIN ' . tablename('vcshop_order') . ' o ON o.id=t.orderid ' . ' WHERE t.uniacid=:uniacid AND t.agentid=:agentid  AND type in(2,3) ' . $condition, array(':uniacid' => $_W['uniacid'], ':agentid' => $member['id']));
			foreach ($level1_orders_team_bonus as $o) {
				if (empty($o['id'])) {
					continue;
				}

				$commission_team_bonus = floatval($o['money']);
				$commission_ok = $commission_team_bonus;
				$hasorder = false;
				foreach ($orders as &$or) {
					if (($or['id'] == $o['id'])) {
						$or['commission'] += $commission_ok;
						$or['levels'][] = 4;
						$hasorder = true;
						break;
					}
				}

				unset($or);

				if (!$hasorder) {
					$orders[] = array('id' => $o['id'], 'commission' => $commission_ok, 'createtime' => $o['createtime'], 'level' => 4,'levels' =>array(4));
				}
			}
			//team_bonus 20190618 e
		}

		if (2 <= $level) {
			if (0 < $level1) {
				$level2_orders = pdo_fetchall('select commission2 ,o.id,o.createtime,o.price,og.commissions   from ' . tablename('vcshop_order_goods') . ' og ' . ' left join  ' . tablename('vcshop_order') . ' o on og.orderid=o.id ' . ' where o.uniacid=:uniacid and o.agentid in( ' . implode(',', array_keys($member['level1_agentids'])) . (')  ' . $condition . '  and og.status2>=0 and og.nocommission=0 '), array(':uniacid' => $_W['uniacid']));

				foreach ($level2_orders as $o) {
					if (empty($o['id'])) {
						continue;
					}

					$commissions = iunserializer($o['commissions']);
					$commission = iunserializer($o['commission2']);

					if (empty($commissions)) {
						$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
					}
					else {
						$commission_ok = isset($commissions['level2']) ? floatval($commissions['level2']) : 0;
					}

					$hasorder = false;

					foreach ($orders as &$or) {
						if ($or['id'] == $o['id'] && $or['level'] == 2) {
							$or['commission'] += $commission_ok;
							$hasorder = true;
							break;
						}
					}

					unset($or);

					if (!$hasorder) {
						$orders[] = array('id' => $o['id'], 'commission' => $commission_ok, 'createtime' => $o['createtime'], 'level' => 2,'levels' =>array(2));
					}
				}
			}
		}

		if (3 <= $level) {
			if (0 < $level2) {
				$level3_orders = pdo_fetchall('select commission3 ,o.id,o.createtime,o.price,og.commissions  from ' . tablename('vcshop_order_goods') . ' og ' . ' left join  ' . tablename('vcshop_order') . ' o on og.orderid=o.id ' . ' where o.uniacid=:uniacid and o.agentid in( ' . implode(',', array_keys($member['level2_agentids'])) . (')  ' . $condition . ' and og.status3>=0 and og.nocommission=0'), array(':uniacid' => $_W['uniacid']));

				foreach ($level3_orders as $o) {
					if (empty($o['id'])) {
						continue;
					}

					$commissions = iunserializer($o['commissions']);
					$commission = iunserializer($o['commission3']);

					if (empty($commissions)) {
						$commission_ok = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
					}
					else {
						$commission_ok = isset($commissions['level3']) ? floatval($commissions['level3']) : 0;
					}

					$hasorder = false;

					foreach ($orders as &$or) {
						if ($or['id'] == $o['id'] && $or['level'] == 3) {
							$or['commission'] += $commission_ok;
							$hasorder = true;
							break;
						}
					}

					unset($or);

					if (!$hasorder) {
						$orders[] = array('id' => $o['id'], 'commission' => $commission_ok, 'createtime' => $o['createtime'], 'level' => 3,'levels' =>array(3));
					}
				}
			}
		}

		if ($orders) {
			usort($orders, 'order_sort');
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$orders1 = array_slice($orders, ($pindex - 1) * $psize, $psize);
		$orderids = array();

		foreach ($orders1 as $o) {
			$orderids[$o['id']] = $o;
		}

		$list = array();

		if (!empty($orderids)) {
			$list = pdo_fetchall('select id,ordersn,openid,createtime,status from ' . tablename('vcshop_order') . ('  where uniacid =' . $_W['uniacid'] . ' and id in ( ') . implode(',', array_keys($orderids)) . ') order by id desc');

			foreach ($list as &$row) {
				// han 20190618 s
				foreach ($orderids as $o) {
					if ($o['id'] == $row['id']) {
						$row['levels'] = $o['levels'];
					}
				}
				// han e
				$row['commission'] = number_format((double) $orderids[$row['id']]['commission'], 2);
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);

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

				// if ($orderids[$row['id']]['level'] == 1) {
				// 	$row['level'] = $this->set['texts']['c1'];
				// }
				// else if ($orderids[$row['id']]['level'] == 2) {
				// 	$row['level'] = $this->set['texts']['c2'];
				// }
				// else {
				// 	if ($orderids[$row['id']]['level'] == 3) {
				// 		$row['level'] = $this->set['texts']['c3'];
				// 	}
				// }
				if (in_array(1, $orderids[$row['id']]['levels'])) {
					$row['level'] = $this->set['texts']['c1'];
				}
				else if (in_array(2, $orderids[$row['id']]['levels'])) {
					$row['level'] = $this->set['texts']['c2'];
				}
				else {
					if (in_array(3, $orderids[$row['id']]['levels'])) {
						$row['level'] = $this->set['texts']['c3'];
					}
					if (in_array(4, $orderids[$row['id']]['levels'])) {
						$row['level'] = '团队';
					}
				}

				if (!empty($this->set['openorderdetail'])) {
					$goods = pdo_fetchall('SELECT og.id,og.goodsid,g.thumb,og.price,og.total,g.title,og.optionname,' . 'og.commission1,og.commission2,og.commission3,og.commissions,' . 'og.status1,og.status2,og.status3,' . 'og.content1,og.content2,og.content3 from ' . tablename('vcshop_order_goods') . ' og' . ' left join ' . tablename('vcshop_goods') . ' g on g.id=og.goodsid  ' . ' where og.orderid=:orderid and og.nocommission=0 and og.uniacid = :uniacid order by og.createtime  desc ', array(':uniacid' => $_W['uniacid'], ':orderid' => $row['id']));
					$goods = set_medias($goods, 'thumb');

					foreach ($goods as &$g) {
						$commissions = iunserializer($g['commissions']);

						// if ($orderids[$row['id']]['level'] == 1) {
						if (in_array(1, $orderids[$row['id']]['levels'])) {
							$commission = iunserializer($g['commission1']);

							if (empty($commissions)) {
								$g['commission'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
							}
							else {
								$g['commission'] = isset($commissions['level1']) ? floatval($commissions['level1']) : 0;
							}
						}
						// else if ($orderids[$row['id']]['level'] == 2) {
						else if (in_array(2, $orderids[$row['id']]['levels'])) {
							$commission = iunserializer($g['commission2']);

							if (empty($commissions)) {
								$g['commission'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
							}
							else {
								$g['commission'] = isset($commissions['level2']) ? floatval($commissions['level2']) : 0;
							}
						}
						else {
							// if ($orderids[$row['id']]['level'] == 3) {
							if (in_array(3, $orderids[$row['id']]['levels'])) {
								$commission = iunserializer($g['commission3']);

								if (empty($commissions)) {
									$g['commission'] = isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default'];
								}
								else {
									$g['commission'] = isset($commissions['level3']) ? floatval($commissions['level3']) : 0;
								}
							}
							if(in_array(4, $orderids[$row['id']]['levels'])){
								$reward_logs = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND orderid=:orderid AND type in(2,3) ORDER BY id ASC,floot ASC',array(':uniacid' => $_W['uniacid'], ':orderid' => $row['id']));
								if(!empty($reward_logs) && is_array($reward_logs)){
									foreach ($reward_logs as $team) {
										$t_datas = unserialize($team['datas']);
										foreach ($t_datas as $tk => $tv) {
											if($tk == $g['id']){
												$g['commission'] += $team['money'];
											}
										}
									}
								}
							}
						}
					}

					unset($g);
					$row['order_goods'] = set_medias($goods, 'thumb');
				}

				if (!empty($this->set['openorderbuyer'])) {
					$row['buyer'] = m('member')->getMember($row['openid']);
				}
			}

			unset($row);
		}

		show_json(1, array('list' => $list, 'pagesize' => $psize, 'total' => $ordercount));
	}
}

?>
