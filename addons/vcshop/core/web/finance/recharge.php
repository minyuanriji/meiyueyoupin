<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Recharge_VcShopPage extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$type = trim($_GPC['type']);
		// zhou 20191121 增加字段识别 s 
		$lever = trim($_GPC['lever']);
		// zhou 20191121 增加字段识别 e 

		if (!cv('finance.recharge.' . $type)) {
			$this->message('你没有相应的权限查看');
		}

		$id = intval($_GPC['id']);
		$profile = m('member')->getMember($id, true);
		// zhou 20191121 获取当前能充值的等级  游客没有
		$check_quota = pdo_fetchcolumn('select is_team from ' . tablename('vcshop_commission_level') . ' where id=:id and uniacid=:uniacid' , array(':id'=>$profile['agentlevel'],':uniacid'=>$_W['uniacid'])); 
		$quota = pdo_fetchall('select q.*,c.levelname from ' . tablename('vcshop_member_quota') . ' as q LEFT JOIN ' . tablename('vcshop_commission_level') .' as  c on c.id = q.agentlevel ' . ' where q.uniacid=:uniacid and q.openid=:openid ', array(':uniacid' => $_W['uniacid'], ':openid' => $profile['openid']));
		// LEFT JOIN ims_vcshop_commission_level AS b ON b.level <= a.level
		$check_level = pdo_fetchall('select b.id,b.level,b.levelname from ' . tablename('vcshop_commission_level') . ' as a LEFT JOIN ' . tablename('vcshop_commission_level') . ' as  b on b.level <= a.level ' . ' where a.uniacid=:uniacid and a.id=:id',array(':uniacid'=>$_W['uniacid'],':id'=>$profile['agentlevel']));
		// $check_level = array_merge(array( array( "id" => "","level"=>"", "levelname" => (empty($_W["shopset"]["commission"]["levelname"]) ? "默认等级" : $_W["shopset"]["commission"]["levelname"]) ) ), $check_level);
		// zhou 20191121 e
		if ($_W['ispost']) {
			// zhou 20191121 增加充值项 
			// $typestr = $type == 'credit1' ? '积分' : '余额';
			if($type == 'credit1'){
				$typestr = '积分';
			}elseif($type == 'credit2'){
				$typestr = '积分';
			}else{
				$typestr = '名额';
			}
			// zhou 20191121
			$num = floatval($_GPC['num']);
			$remark = trim($_GPC['remark']);

			if ($num <= 0) {
				show_json(0, array('message' => '请填写大于0的数字!'));
			}

			$changetype = intval($_GPC['changetype']);

			if ($changetype == 2) {
				$num -= $profile[$type];
			}
			else {
				if ($changetype == 1) {
					$num = 0 - $num;
				}
			}

			// zhou 20191121 这里直接执行修改 
			$log_num = $num; // log 用
			if($type != "quota"){
				m('member')->setCredit($profile['openid'], $type, $num, array($_W['uid'], '后台会员充值' . $typestr . ' ' . $remark));
			}else{
				// level num 
				if(empty($check_quota)){
					show_json(0,array('message'=>"该会员等级无配额"));	
				}
				if(empty($_GPC['level'])){
					show_json(0,array('message'=>"等级，为空"));	
				}
				$level = trim($_GPC['level']);
				$upda = array(
					'uniacid' => $_W['uniacid'],
					'mid' => $profile['id'],
					'agentlevel' =>$level,
				); 
				// old_num new_num 
				// num 重新拿
				$num = floatval($_GPC['num']);
				$old_num = pdo_get('vcshop_member_quota',$upda);

				$old_num = intval($old_num['num']);
				if ($changetype == 0) {
					// 0 加
					$num += $old_num;
				}
				else 
				{
					// 1 减
					if ($changetype == 1) {
						$num = ($old_num - $num);
						// $num =1 ;
					}
				}
				if($num < 0){
					show_json(0,array('message'=>"操作失败，不能为负"));
				}
				// han 20201119 修复原有不能添加新名额等级bug
				$data = array('num'=>$num);
				if(!$old_num){
					$data['uniacid'] = $_W['uniacid'];
					$data['uid'] = $_W['uniacid'];
					$data['mid'] = $profile['id'];
					$data['openid'] = $profile['openid'];
					$data['agentlevel'] = $level;
					$data['createtime'] = time;
					$update = pdo_insert('vcshop_member_quota',$data);
				}else{
					$update = pdo_update('vcshop_member_quota',$data,$upda);
				}
				if(!$update){
					show_json(0,array('message'=>"操作失败，请检查"));
				}
				// 等级名
				$levelname = pdo_fetchcolumn('select levelname from ' . tablename('vcshop_commission_level') . ' where id=:id and uniacid=:uniacid' , array(':id'=>$level,':uniacid'=>$_W['uniacid'])); 
				$upda['remark'] = "后台会员充值" . "[ " . $levelname . " ]";
				$upda['createtime'] = time();
				$upda['openid'] = $profile['openid'];
				$upda['num'] = $log_num;  
				$upda['presentnum'] = $num;
				$upda['operator'] = $_W['uid'];
				$up_log = pdo_insert('vcshop_member_quota_record',$upda);
				m('notice')->sendMemberQuotaChange($profile['openid'], $log_num, $changetype,$level); // 用户id 记录数量 类型   更改的等级
			}
			// zhou 20191121 这里直接执行 e 
			$changetype = 0;
			$changenum = 0;

			if (0 <= $num) {
				$changetype = 0;
				$changenum = $num;
			}
			else {
				$changetype = 1;
				$changenum = 0 - $num;
			}

			if ($type == 'credit1') {
				m('notice')->sendMemberPointChange($profile['openid'], $changenum, $changetype);
			}

			if ($type == 'credit2') {
				$set = m('common')->getSysset('shop');
				$logno = m('common')->createNO('member_log', 'logno', 'RC');
				$data = array('openid' => $profile['openid'], 'logno' => $logno, 'uniacid' => $_W['uniacid'], 'type' => '0', 'createtime' => TIMESTAMP, 'status' => '1', 'title' => $set['name'] . '会员充值', 'money' => $num, 'remark' => $remark, 'rechargetype' => 'system');
				pdo_insert('vcshop_member_log', $data);
				$logid = pdo_insertid();
				m('notice')->sendMemberLogMessage($logid, 0, true);
			}

			plog('finance.recharge.' . $type, '充值' . $typestr . ': ' . $_GPC['num'] . ' <br/>会员信息: ID: ' . $profile['id'] . ' /  ' . $profile['openid'] . '/' . $profile['nickname'] . '/' . $profile['realname'] . '/' . $profile['mobile']);
			show_json(1, array('url' => referer()));
		}

		include $this->template();
	}
	
}

?>
