<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Level_VcShopPage extends PluginWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		global $_S;
		$set = $_S['commission'];
		$leveltype = $set['leveltype'];
		$default = array('id' => 'default','level'=>0, 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'commission1' => $set['commission1'], 'commission2' => $set['commission2'], 'commission3' => $set['commission3'], 'mix' => $set['mix'], 'is_team' => $set['is_team'], 'monthly_credit' => $set['monthly_credit']);
		$others = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_commission_level') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY commission1 asc'));
		$list = array_merge(array($default), $others);
		include $this->template();
	}

	public function add()
	{
		$this->post();
	}

	public function edit()
	{
		$this->post();
	}

	protected function post()
	{
		global $_W;
		global $_GPC;
		global $_S;
		$set = $_S['commission'];
		$leveltype = $set['leveltype'];
		$id = trim($_GPC['id']);
		$level_array = array();
		$i = 1;

		while ($i < 101) {
			$level_array[$i] = $i;
			++$i;
		}

		if ($id == 'default') {
			$level = array('id' => 'default','level'=>0, 'levelname' => empty($set['levelname']) ? '默认等级' : $set['levelname'], 'commission1' => $set['commission1'], 'commission2' => $set['commission2'], 'commission3' => $set['commission3'], 'mix' => $set['mix'], 'is_team' => $set['is_team'], 'monthly_credit' => $set['monthly_credit']);
		}
		else {
			$level = pdo_fetch('SELECT * FROM ' . tablename('vcshop_commission_level') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':id' => intval($id), ':uniacid' => $_W['uniacid']));

			if (!empty($level['goodsids'])) {
				$goodsids = iunserializer($level['goodsids']);

				if (!empty($goodsids)) {
					$goods = pdo_fetchall('SELECT id,uniacid,title,thumb FROM ' . tablename('vcshop_goods') . ' WHERE uniacid=:uniacid AND id IN (' . implode(',', $goodsids) . ')', array(':uniacid' => $_W['uniacid']));
				}
			}
		}
		// han 20191113 s

		if(!empty($level['mix'])){
			if (is_serialized($level['mix'])) {
				$level['mix'] = iunserializer($level['mix']);
			}
		}
		$levels = $this->model->getLevels(true,true);
		// han 20191113 e

		if ($_W['ispost']) {
			$data = array('uniacid' => $_W['uniacid'], 'levelname' => trim($_GPC['levelname']), 'commission1' => trim(trim($_GPC['commission1']), '%'), 'commission2' => trim(trim($_GPC['commission2']), '%'), 'commission3' => trim(trim($_GPC['commission3']), '%'), 'commissionmoney' => trim($_GPC['commissionmoney'], '%'), 'ordermoney' => $_GPC['ordermoney'], 'ordercount' => intval($_GPC['ordercount']), 'downcount' => intval($_GPC['downcount']), 'goodsids' => $_GPC['goodsids'], 'level' => $_GPC['level'], 'goodsids_text' => $_GPC['goodsids_text']);
			// han 20191113 s
			$data['is_team'] = intval($_GPC['is_team']);
			$data['monthly_credit'] = intval($_GPC['monthly_credit']);
			$data['mix'] = is_array($_GPC['mix']) && !empty($_GPC['mix']) ? iserializer($_GPC['mix']) : iserializer(array());
			// han 20191113 e

			if (!empty($data['goodsids'])) {
				$cont = count($data['goodsids']);

				if (5 < $cont) {
					show_json(0, '商品最多添加五个');
				}

				$data['goodsids'] = iserializer($data['goodsids']);
			}

			if (!empty($id)) {
				if ($id == 'default') {
					$updatecontent = '<br/>等级名称: ' . $set['levelname'] . '->' . $data['levelname'] . ('<br/>一级佣金比例: ' . $set['commission1'] . '->' . $data['commission1']) . ('<br/>二级佣金比例: ' . $set['commission2'] . '->' . $data['commission2']) . ('<br/>三级佣金比例: ' . $set['commission3'] . '->' . $data['commission3']) . ('<br/>每月送积分: ' . $set['monthly_credit'] . '->' . $data['monthly_credit']) . ('<br/>是否拥有团队分红: ' . $set['is_team'] . '->' . $data['is_team']);
					$set['levelname'] = $data['levelname'];
					$set['commission1'] = $data['commission1'];
					$set['commission2'] = $data['commission2'];
					$set['commission3'] = $data['commission3'];

					// han 20191113 s
					$set['is_team'] = $data['is_team'];
					$set['monthly_credit'] = $data['monthly_credit'];
					$set['mix'] = $data['mix'];
					// han 20191113 e
					$this->updateSet($set);
					plog('commission.level.edit', '修改分销商默认等级' . $updatecontent);
				}
				else {
					$updatecontent = '<br/>等级名称: ' . $level['levelname'] . '->' . $data['levelname'] . ('<br/>一级佣金比例: ' . $level['commission1'] . '->' . $data['commission1']) . ('<br/>二级佣金比例: ' . $level['commission2'] . '->' . $data['commission2']) . ('<br/>三级佣金比例: ' . $level['commission3'] . '->' . $data['commission3']) . ('<br/>每月送积分: ' . $level['monthly_credit'] . '->' . $data['monthly_credit']) . ('<br/>是否拥有团队分红: ' . $level['is_team'] . '->' . $data['is_team']);
					pdo_update('vcshop_commission_level', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
					plog('commission.level.edit', '修改分销商等级 ID: ' . $id . $updatecontent);
				}
			}
			else {
				pdo_insert('vcshop_commission_level', $data);
				$id = pdo_insertid();
				plog('commission.level.add', '添加分销商等级 ID: ' . $id);
			}

			show_json(1, array('url' => webUrl('commission/level')));
		}

		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$items = pdo_fetchall('SELECT id,levelname FROM ' . tablename('vcshop_commission_level') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('vcshop_commission_level', array('id' => $item['id']));
			plog('commission.level.delete', '删除分销商等级 ID: ' . $id . ' 等级名称: ' . $level['levelname']);
		}

		show_json(1);
	}
}

?>
