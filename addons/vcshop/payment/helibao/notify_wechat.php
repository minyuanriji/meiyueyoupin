<?php
ini_set('display_errors',1);            //错误信息  
ini_set('display_startup_errors',1);    //php启动错误信息  
error_reporting(-1); 
// ini_set('display_errors', 'On');
// error_reporting(30719 ^ 8);
define('IN_MOBILE', true);
require '../../../../payment/helibao/AppUtil.php';
$get = array();
foreach($_POST as $key=>$val) {//动态遍历获取所有收到的参数,此步非常关键,因为收银宝以后可能会加字段,动态获取可以兼容由于收银宝加字段而引起的签名异常
	$get[$key] = $val;
}
if(count($get)<1){//如果参数为空,则不进行处理
	echo "fail";
	exit();
}
require dirname(__FILE__) . '/../../../../framework/bootstrap.inc.php';
require IA_ROOT . '/addons/vcshop/defines.php';
require IA_ROOT . '/addons/vcshop/core/inc/functions.php';
require IA_ROOT . '/addons/vcshop/core/inc/plugin_model.php';
require IA_ROOT . '/addons/vcshop/core/inc/com_model.php';
new VcShopWechatPay($get);
exit('fail');
class VcShopWechatPay 
{
	public $get;
	public $type;
	public $total_fee;
	public $set;
	public $setting;
	public $sec;
	public $sign;
	public $isapp = true;
	public $is_jie = false;
	public function __construct($get) 
	{
		global $_W;
		// pdo_insert('a',array('info'=>json_encode($get)));
		$this->get = $get;
		$strs = explode(':', $this->get['rt8_desc']);
		$this->type = intval($strs[1]);
		$this->total_fee = $this->get['rt5_orderAmount'];
		$GLOBALS['_W']['uniacid'] = intval($strs[0]);
		$_W['uniacid'] = intval($strs[0]);
		$this->init();
	}
	public function success() 
	{
		echo 'SUCCESS';
		exit();
	}
	public function fail() 
	{
		echo 'FAIL';
		exit();
	}
	public function init() 
	{
		if ($this->type == '0') 
		{
			$this->order();
		}
		else if ($this->type == '1') 
		{
			$this->recharge();
		}
		else if ($this->type == '2') 
		{
			$this->creditShop();
		}
		else if ($this->type == '3') 
		{
			$this->creditShopFreight();
		}
		else if ($this->type == '5') 
		{
			$this->groups();
		}
		else if ($this->type == '20') 
		{
			$this->recharequan();
		}
		else if ($this->type == '21'){
			
			$this->rechargevip();
		}
		$this->success();
	}
	public function order() 
	{
		global $_W;
		if (!($this->publicMethod())) 
		{
			exit('order');
		}
		$tid = $this->get['rt2_orderId'];
		$isborrow = 0;
		$borrowopenid = '';
		if (strpos($tid, '_borrow') !== false) 
		{
			$tid = str_replace('_borrow', '', $tid);
			$isborrow = 1;
			$borrowopenid = $this->get['acct'];
		}
		if (strpos($tid, '_B') !== false) 
		{
			$tid = str_replace('_B', '', $tid);
			$isborrow = 1;
			$borrowopenid = $this->get['acct'];
		}
		if (strexists($tid, 'GJ')) 
		{
			$tids = explode('GJ', $tid);
			$tid = $tids[0];
		}
		$ispeerpay = 0;
		$tid2 = 0;
		if (22 < strlen($tid)) 
		{
			$tid2 = $tid;
			$ispeerpay = 1;
		}
		$paytype = 33;
		// if (strexists($borrowopenid, '2088') || is_numeric($borrowopenid)) 
		// {
		// 	$paytype = 36;
		// }
		$tid = substr($tid, 0, 22);
		$order = pdo_fetch('SELECT * FROM ' . tablename('vcshop_order') . ' WHERE ordersn = :ordersn AND uniacid = :uniacid', array(':ordersn' => $tid, ':uniacid' => $_W['uniacid']));
		$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `module`=:module AND `tid`=:tid  limit 1';
		$params = array();
		$params[':tid'] = $tid;
		$params[':module'] = 'vcshop';
		$log = pdo_fetch($sql, $params);
		if (!(empty($log)) && (($log['status'] == '0') || $ispeerpay) && (($log['fee'] == $this->total_fee) || $ispeerpay)) 
		{
			pdo_update('vcshop_order', array('paytype' => $paytype, 'isborrow' => $isborrow, 'borrowopenid' => $borrowopenid, 'apppay' => ($this->isapp ? 1 : 0)), array('ordersn' => $log['tid'], 'uniacid' => $log['uniacid']));
			$site = WeUtility::createModuleSite($log['module']);
			if (!(empty($ispeerpay))) 
			{
				$ispeerpay = m('order')->checkpeerpay($order['id']);
				if (!(empty($ispeerpay))) 
				{
					m('order')->setOrderPayType($order['id'], $paytype);
					$openid = $this->get['acct'];
					$member = m('member')->getMember($openid);
					m('order')->peerStatus(array('pid' => $ispeerpay['id'], 'uid' => $member['id'], 'uname' => $member['nickname'], 'usay' => '支持一下，么么哒!', 'price' => $this->total_fee, 'createtime' => time(), 'openid' => $openid, 'headimg' => $member['avatar'], 'tid' => $tid2));
					unset($_SESSION['peerpaytid']);
					$peerpay_info = (double) pdo_fetchcolumn('select SUM(price) from ' . tablename('vcshop_order_peerpay_payinfo') . ' where pid=:pid limit 1', array(':pid' => $ispeerpay['id']));
					if ($peerpay_info < $ispeerpay['peerpay_realprice']) 
					{
						$this->success();
					}
				}
			}
			if (!(is_error($site))) 
			{
				$method = 'payResult';
				if (method_exists($site, $method)) 
				{
					$ret = array();
					$ret['acid'] = $log['acid'];
					$ret['uniacid'] = $log['uniacid'];
					$ret['result'] = 'success';
					$ret['type'] = $log['type'];
					$ret['from'] = 'return';
					$ret['tid'] = $log['tid'];
					$ret['user'] = $log['openid'];
					$ret['fee'] = $log['fee'];
					$ret['tag'] = $log['tag'];
					$result = $site->$method($ret);
					if ($result) 
					{
						$log['tag'] = iunserializer($log['tag']);
						$log['tag']['transaction_id'] = $this->get['transaction_id'];
						$record = array();
						$record['status'] = '1';
						$record['tag'] = iserializer($log['tag']);
						$record['type'] = 'helibao_wechat';
						pdo_update('core_paylog', $record, array('plid' => $log['plid']));
					}
				}
			}
		}
		else 
		{
			$this->fail();
		}
	}
	public function recharge() 
	{
		global $_W;
		if (!($this->publicMethod())) 
		{
			exit('recharge');
		}
		$logno = trim($this->get['rt2_orderId']);
		if (empty($logno)) 
		{
			$this->fail();
		}
		$log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_member_log') . ' WHERE `uniacid`=:uniacid and `logno`=:logno limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
		$OK = !(empty($log)) && empty($log['status']) && ($log['money'] == $this->total_fee);
		if ($OK) 
		{
			pdo_update('vcshop_member_log', array('status' => 1, 'rechargetype' => 'helibao_wechat', 'apppay' => ($this->isapp ? 1 : 0), 'isborrow' => $isborrow, 'borrowopenid' => $borrowopenid), array('id' => $log['id']));
			$shopset = m('common')->getSysset('shop');
			m('member')->setCredit($log['openid'], 'credit2', $log['money'], array(0, $shopset['name'] . '会员充值:credit2:' . $log['money']));
			m('member')->setRechargeCredit($log['openid'], $log['money']);
			com_run('sale::setRechargeActivity', $log);
			com_run('coupon::useRechargeCoupon', $log);
			m('notice')->sendMemberLogMessage($log['id']);
			
		}
	}
	
	
	
	public function rechargevip(){
		
		global $_W;
		if (!($this->publicMethod())) 
		{
			exit('rechargevip');
		}
		$logno = trim($this->get['rt2_orderId']);
		if (empty($logno)) 
		{
			$this->fail();
		}
		
		$log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_up_commission_order') . ' WHERE `uniacid`=:uniacid and `order_sn`=:order_sn limit 1', array(':uniacid' => $_W['uniacid'], ':order_sn' => $logno));
		
		$openid = $log['openid'];
		$fee = $this->total_fee;
		
		if ($fee != $log['money']) 
		{
			$this->fail();
		}
		
		if (!(empty($log)) && empty($log['status'])) 
		{
			// $shopset = m('common')->getSysset('shop');
			// $memberset = m('common')->getSysset('member');
			$record = array();
			$record['status'] = '1';
			$record['type'] = 'helibao_wechat';
			pdo_update('core_paylog', $record, array('tid' => $logno));

			//更新分销表
			$updata = array(
			    'pay_type' => 33,
			    'status'   => 1,
			    'pay_time' => time(),
			);
			pdo_update('vcshop_up_commission_order',$updata,array('uniacid' => $_W['uniacid'],'order_sn'=>$logno));
			if(empty($log['commission_id'])){
                pdo_update('vcshop_member', array('vip_num -=' => 1), array('id' => $log['mid']));
            }else{
                pdo_update('vcshop_member', array('topvip_num -=' => 1), array('id' => $log['mid']));
            }
            // pdo_update('vcshop_member', array('vip_num -=' => 1), array('id' => $log['mid']));
			p('commission')->upMemberCommission($log['id'],$_W['uniacid']);
		}
	}
	public function creditShop() 
	{
		global $_W;
		if (!($this->publicMethod())) 
		{
			exit('creditShop');
		}
		$logno = trim($this->get['rt2_orderId']);
		if (empty($logno)) 
		{
			exit();
		}
		$logno = str_replace('_borrow', '', $logno);
		if (p('creditshop')) 
		{
			p('creditshop')->payResult($logno, 'helibao_wechat', $this->total_fee, ($this->isapp ? true : false));
		}
	}
	public function creditShopFreight() 
	{
		global $_W;
		if (!($this->publicMethod())) 
		{
			exit('creditShopFreight');
		}
		$dispatchno = trim($this->get['rt2_orderId']);
		$dispatchno = str_replace('_borrow', '', $dispatchno);
		if (empty($dispatchno)) 
		{
			exit();
		}
		$log = pdo_fetch('SELECT * FROM ' . tablename('vcshop_creditshop_log') . ' WHERE `dispatchno`=:dispatchno and `uniacid`=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':dispatchno' => $dispatchno));
		if (!(empty($log)) && ($log['dispatchstatus'] < 0)) 
		{
			pdo_update('vcshop_creditshop_log', array('dispatchstatus' => 1), array('dispatchno' => $dispatchno));
		}
	}
	public function groups() 
	{
		global $_W;
		if (!($this->publicMethod())) 
		{
			exit('groups');
		}
		$orderno = trim($this->get['rt2_orderId']);
		$orderno = str_replace('_borrow', '', $orderno);
		if (empty($orderno)) 
		{
			exit();
		}
		if ($this->is_jie) 
		{
			pdo_update('vcshop_groups_order', array('isborrow' => '1', 'borrowopenid' => $this->get['openid']), array('orderno' => $orderno, 'uniacid' => $_W['uniacid']));
		}
		if (p('groups')) 
		{
			p('groups')->payResult($orderno, 'helibao_wechat', ($this->isapp ? true : false));
		}
	}
	public function publicMethod() 
	{
		global $_W;
		if (empty($_W['uniacid'])) 
		{
			return false;
		}
		list($set, $payment) = m('common')->public_build();
		$this->set = $set;
		$this->setting = uni_setting($_W['uniacid'], array('payment'));
		$this->is_jie = (strpos($this->get['rt2_orderId'], '_B') !== false) || (strpos($this->get['rt2_orderId'], '_borrow') !== false);
		if (is_array($this->setting['payment']) || ($this->set['helibao_wechat'] == 1)) 
		{
		// pdo_insert('a',array('info'=>'check1'));
	    	$signRsp = strtolower($this->get["sign"]);
			$options = $this->setting['payment']['helibao_wechat'];
			$SignArray = array(
				'rt1_customerNumber' => $this->get['rt1_customerNumber'],
				'rt2_orderId' => $this->get['rt2_orderId'],
				'rt3_systemSerial' => $this->get['rt3_systemSerial'],
				'rt4_status' => $this->get['rt4_status'],
				'rt5_orderAmount' => $this->get['rt5_orderAmount'],
				'rt6_currency' => $this->get['rt6_currency'],
				'rt7_timestamp' => $this->get['rt7_timestamp'],
				'rt8_desc' => $this->get['rt8_desc']
			);
			$sign = AppUtil::SignArray($SignArray, $options['key']);
			if ($sign==$signRsp) {
		// pdo_insert('a',array('info'=>'check2'));

				if($this->get['rt4_status'] == 'SUCCESS'){
					return true;
				}
			}
		}
		return false;
	}
}
?>