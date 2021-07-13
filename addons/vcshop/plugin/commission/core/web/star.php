<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Star_VcShopPage extends PluginWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		global $_S;
		$set = $_S['commission'];
		$list = pdo_fetchall('SELECT * FROM ' . tablename('vcshop_commission_star') . (' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY level asc'));
		foreach ($list as $key => &$value) {
			// if($value['bindlevel'] > -1){
			// 	if(empty($value['bindlevel'])){
			// 		$value['bindlevelname'] = $set['levelname'];
			// 	}else{
			// 		$value['bindlevelname'] = pdo_fetchcolumn('SELECT levelname FROM ' . tablename('vcshop_commission_level') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$value['bindlevel']));
			// 	}
			// }
			if(!empty($value['bindlevel'])){
				$value['bindlevel'] = explode(',', $value['bindlevel']);
				$value['bindlevelname'] = '';
				foreach ($value['bindlevel'] as $lk => $l) {
					$value['bindlevelname'] .= ' ';
					$value['bindlevelname'] .= pdo_fetchcolumn('SELECT levelname FROM ' . tablename('vcshop_commission_level') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$l));
				}
			}
			if($value['teamlevel'] > -1){
				if(empty($value['teamlevel'])){
					$value['teamlevelname'] = $set['levelname'];
				}else{
					$value['teamlevelname'] = pdo_fetchcolumn('SELECT levelname FROM ' . tablename('vcshop_commission_level') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$value['teamlevel']));
				}
			}
			if($value['downlevel'] > -1){
				if(empty($value['downlevel'])){
					$value['downlevelname'] = $set['levelname'];
				}else{
					$value['downlevelname'] = pdo_fetchcolumn('SELECT levelname FROM ' . tablename('vcshop_commission_level') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$value['downlevel']));
				}
			}
		}
		unset($value);
		include $this->template('commission/star/index');
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
		$id = trim($_GPC['id']);
		$level_array = array();
		$i = 1;
		while ($i < 101) {
			$level_array[$i] = $i;
			++$i;
		}
		$levels = $this->model->getLevels(true,true);
		$level = pdo_fetch('SELECT * FROM ' . tablename('vcshop_commission_star') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':id' => intval($id), ':uniacid' => $_W['uniacid']));
		if(!empty($level['bindlevel'])){
			$level['bindlevel'] = explode(',', $level['bindlevel']);
		}
		if ($_W['ispost']) {
			$data = array('uniacid' => $_W['uniacid'], 'levelname' => trim($_GPC['levelname']), 'commission1' => floatval($_GPC['commission1']), 'commissionteam' => floatval($_GPC['commissionteam']), 'commissioncharge' => floatval($_GPC['commissioncharge']), 'downcount' => $_GPC['downcount'], 'downlevel' => intval($_GPC['downlevel']), 'teamcount' => intval($_GPC['teamcount']), 'teamlevel' => $_GPC['teamlevel'], 'level' => $_GPC['level'], 'bindlevel' => implode($_GPC['bindlevel'], ','));
			$data['is_team'] = intval($_GPC['is_team']);
			$data['is_default'] = intval($_GPC['is_default']);
			if (!empty($id)) {
				$updatecontent = '<br/>等级名称: ' . $level['levelname'] . '->' . $data['levelname'] . ('<br/>一级分成: ' . $level['commission1'] . '->' . $data['commission1']) . ('<br/>团队分成: ' . $level['commissionteam'] . '->' . $data['commissionteam']) . ('<br/>是否拥有团队分红: ' . $level['is_team'] . '->' . $data['is_team']) . ('<br/>是否该绑定等级默认: ' . $level['is_default'] . '->' . $data['is_default']);
				pdo_update('vcshop_commission_star', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
				plog('commission.star.edit', '修改分销商星级 ID: ' . $id . $updatecontent);
				
			}
			else {
				pdo_insert('vcshop_commission_star', $data);
				$id = pdo_insertid();
				plog('commission.star.add', '添加分销商星级 ID: ' . $id);
			}

			show_json(1, array('url' => webUrl('commission/star')));
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

		$items = pdo_fetchall('SELECT id,levelname FROM ' . tablename('vcshop_commission_star') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

		foreach ($items as $item) {
			pdo_delete('vcshop_commission_star', array('id' => $item['id']));
			plog('commission.star.delete', '删除分销商星级 ID: ' . $id . ' 等级名称: ' . $level['levelname']);
		}

		show_json(1);
	}
}

?>
