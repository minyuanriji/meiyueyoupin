<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Transfer_VcShopPage extends MobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$set = $_W['shopset']['trade'];
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		if ($set['transfer']==1) {
			$this->message('系统未开启转账!', '', 'error');
		}
		$member = m('member')->getMember($_W['openid'], true);
		$credit = m('member')->getCredit($_W['openid'], 'credit2');

		include $this->template();
	}

	public function assigns() {
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$member = m('member')->getMember($_W['openid'], true);
		if ($_W['isajax']) {
			$assigns_id = trim($_GPC['assigns_id']);
			$assigns = pdo_fetch('select id,nickname from ' . tablename('vcshop_member') . ' where uniacid=:uniacid and mobile = :assigns_id ', array(':uniacid' => $uniacid, ':assigns_id' => $assigns_id));
	
			if ($assigns) {
				if ($assigns['id'] == $member['id']) {
					show_json(0);
				}
				show_json(1,$assigns);
			}
			else {
				show_json(-1);
			}
		}
	}

	public function submit()
	{
		global $_W;
		global $_GPC;
		$set = $_W['shopset']['trade'];
		$uniacid = $_W['uniacid'];
		
		if ($set['transfer']==1) {
			$this->message('系统未开启转账!', '', 'error');
		}
		if ($_W['isajax']) {
			$member = m('member')->getMember($_W['openid'], true);
			$member['credit2'] = m('member')->getCredit($_W['openid'], 'credit2');
			$money = floatval($_GPC['money']);
			if (($money <= 0) || ($member['credit2'] < $money)) {
				show_json(0, '转让金额不正确');
			}
			if(((float)$set['transfermoney'] > 0 && $money < (float)$set['transfermoney'])){
				show_json(0, '请输入大于'.(float)$set['transfermoney'].'的金额');
			}
			// if(($money % 100) > 0){
//				show_json(0, '请输入100的整数倍金额');
			// }
			$assigns_mobile = trim($_GPC['assigns']);
			$assigns = pdo_fetch('select * from ' . tablename('vcshop_member') . ' where uniacid=:uniacid and mobile = :assigns_id ', array(':uniacid' => $uniacid, ':assigns_id' => $assigns_mobile));
			if ($assigns) {
				//  所有操作放在发送通知前，以免微信通信失败导致 转账失败，一个扣一个未加
				$assigns_id = $assigns['id'];
				$mc_assigns = m('member')->getMember($assigns['openid']);
				m('member')->setCredit($member['openid'], 'credit2', 0 - $money, array(0, '向' . $assigns['nickname'] . '转赠' . $set['moneytext'] . ':' . $money));		//转账人
				m('member')->setCredit($assigns['openid'], 'credit2', $money, array(0, '会员'.$set['moneytext'].'转赠所得：' . $money)); 		//被转账人

				//插入转账记录 S
				$member_data = array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'tosell_id' => $member['id'], 'assigns_id' => $assigns_id, 'createtime' => time(), 'status' => 1, 'money' => $money, 'tosell_current_credit' => $member['credit2'] - $money, 'assigns_current_credit' => $assigns['credit2'] + $money);
				pdo_insert('vcshop_member_transfer_log', $member_data);
				//插入转账记录 E

				//转账人通知 S
				$messages = array(
					'keyword1' => array('value' => '转赠通知', 'color' => '#73a68d'),
					'keyword2' => array('value' => '你向' . $assigns['nickname'] . '转赠'. $set['moneytext'] . ':' . $money, 'color' => '#73a68d')
				);
				m('message')->sendCustomNotice($member['openid'], $messages);
				//转账人通知 E

				//被转账人通知 S
				$messages = array(
					'keyword1' => array('value' => '转赠通知', 'color' => '#73a68d'),
					'keyword2' => array('value' => $member['nickname'] . '向你转赠' . $set['moneytext'] . ':' . $money, 'color' => '#73a68d')
				);
				m('message')->sendCustomNotice($assigns['openid'], $messages);
				//被转账人通知 E
				show_json(1);				
			}else{
				show_json(0, '受让人不存在！');
			}
		}
		show_json(0,'异常提交');
	}
}

?>
