<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Inviter_VcShopPage extends MobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$member = m('member')->getMember($_W['openid'], true);
		if($member && !empty($member['agentid'])){
			header('location:' . mobileUrl('member/index'));
			exit();
		}

		include $this->template();
	}
	public function assigns() {
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$member = m('member')->getMember($_W['openid'], true);
		if ($_W['isajax']) {
			if(!empty($member['agentid'])){
				show_json(0,'您已经有邀请人！');
			}
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
	public function yqm(){
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		if($_GPC['yqm']){
			$member = m('member')->getMember($_GPC['yqm'], true);
			if ($member) {
				show_json(1,$member['mobile']);
			}
			else {
				show_json(-1);
			}
		}else{
			echo '';
		}
	}
	
	public function submit(){
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$member = m('member')->getMember($_W['openid'], true);
		if ($_W['isajax']) {
			$assigns_id = trim($_GPC['assigns']);
			$assigns = pdo_fetch('select * from ' . tablename('vcshop_member') . ' where uniacid=:uniacid and mobile = :assigns_id ', array(':uniacid' => $uniacid, ':assigns_id' => $assigns_id));
		
			if ($assigns) {
				if ($assigns['id'] == $member['id']) {
					show_json(0,'邀请码不能填写自己的码');
				}
				if(!empty($assigns['isagent']) && !empty($assigns['status'])){
					$clickcount = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_commission_clickcount') . ' where uniacid=:uniacid and openid=:openid and from_openid=:from_openid limit 1', array(
					    ':uniacid' => $_W['uniacid'],
					    ':openid' => $member['openid'],
					    ':from_openid' => $assigns['openid']
					));
					if ($clickcount <= 0) {
					    $click = array(
					        'uniacid' => $_W['uniacid'],
					        'openid' => $member['openid'],
					        'from_openid' => $assigns['openid'],
					        'clicktime' => time()
					    );
					    pdo_insert('vcshop_commission_clickcount', $click);
					    pdo_update('vcshop_member', array(
					        'clickcount' => $assigns['clickcount'] + 1
					    ), array(
					        'uniacid' => $_W['uniacid'],
					        'id' => $assigns['id']
					    ));
					}
					pdo_update('vcshop_member',array('agentid'=>$assigns['id'],'childtime' => time()),array('id'=>$member['id'],'uniacid'=>$_W['uniacid']));
					p('commission')->delRelation($member['id']);
					$mem = p('commission')->saveRelation($member['id'], $assigns['id']);
					if(is_array($mem)){
						p('commission')->delRelation($member['id']);
						show_json(0,'绑定邀请人失败!');
					}
					show_json(1,'绑定成功');
				}else{
					show_json(-1,'该填写正确的推荐人手机号');
				}
			}
			else {
				show_json(-1,'该推荐人不存在');
			}
		}
	}
}

?>
