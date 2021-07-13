<?php
	define('IN_MOBILE', true);
	require '../../framework/bootstrap.inc.php';
	require_once 'AppConfig.php';
	require_once 'AppUtil.php';
	load()->web('common');
	load()->classs('coupon');
	load()->model('payment');
	$params = array();
	$buff = "";
	foreach($_POST as $key=>$val) {//动态遍历获取所有收到的参数,此步非常关键,因为收银宝以后可能会加字段,动态获取可以兼容由于收银宝加字段而引起的签名异常
		$params[$key] = $val;
		if($val != "" && !is_array($val)){
				$buff .= $key . "=" . $val . "&";
			}
	}
	if(count($params)<1){//如果参数为空,则不进行处理
		// echo "error";
		exit();
	}
	$get = $params;
	$_W['uniacid'] = $_W['weid'] = intval($get['trxreserved']);
	$setting = uni_setting($_W['uniacid'], array('payment'));
	if(!is_array($setting['payment'])) {
		exit('没有设定支付参数.');
	}
	$tl_weixin = $setting['payment']['tonglian_weixin'];
	if(AppUtil::ValidSign($params, $tl_weixin['key'])){//验签成功
		if($get['trxstatus'] == '0000'){
			//此处进行业务逻辑处理
			// echo "success";
			$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `tid`=:tid';
			$params = array();
			$params[':tid'] = $get['outtrxid'];
			$log = pdo_fetch($sql, $params);
			if(!empty($log) && $log['status'] == '0' && (($get['trxamt'] / 100) == $log['card_fee'])) {
				$record = array();
				$record['status'] = '1';
				$record['tag'] = iserializer($get);
				pdo_update('core_paylog', $record, array('plid' => $log['plid']));
				if ($log['is_usecard'] == 1 && !empty($log['encrypt_code'])) {
					$coupon_info = pdo_get('coupon', array('id' => $log['card_id']), array('id'));
					$coupon_record = pdo_get('coupon_record', array('code' => $log['encrypt_code'], 'status' => '1'));
					load()->model('activity');
				 	$status = activity_coupon_use($coupon_info['id'], $coupon_record['id'], $log['module']);
				}	
				$site = WeUtility::createModuleSite($log['module']);
				if(!is_error($site)) {
					$method = 'payResult';
					if (method_exists($site, $method)) {
						$ret = array();
						$ret['weid'] = $log['weid'];
						$ret['uniacid'] = $log['uniacid'];
						$ret['acid'] = $log['acid'];
						$ret['result'] = 'success';
						$ret['type'] = $log['type'];
						$ret['from'] = 'notify';
						$ret['tid'] = $log['tid'];
						$ret['uniontid'] = $log['uniontid'];
						$ret['user'] = empty($get['acct']) ? $log['openid'] : $get['acct'];
						$ret['fee'] = $log['fee'];
						$ret['tag'] = $log['tag'];
						$ret['is_usecard'] = $log['is_usecard'];
						$ret['card_type'] = $log['card_type'];
						$ret['card_fee'] = $log['card_fee'];
						$ret['card_id'] = $log['card_id'];
						if(!empty($get['time_end'])) {
							$ret['paytime'] = strtotime($get['time_end']);
						}
						$site->$method($ret);					
					}
				}
			}
		}
	}

?>  
