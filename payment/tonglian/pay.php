<?php
	define('IN_MOBILE', true);
	header("Content-type:text/html;charset=utf-8");
	require_once 'AppConfig.php';
	require_once 'AppUtil.php';
	require_once 'test.php';
	require '../../framework/bootstrap.inc.php';
	require '../../app/common/bootstrap.app.inc.php';
	load()->app('common');
	load()->app('template');
	$sl = $_GPC['ps'];
	$payopenid = $_GPC['payopenid'];
	$params = @json_decode(base64_decode($sl), true);
	if($_GPC['done'] == '1') {
		$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `plid`=:plid';
		$pars = array();
		$pars[':plid'] = $params['tid'];
		$log = pdo_fetch($sql, $pars);
		if(!empty($log) && (($log['status']) ==0)) {
			if (!empty($log['tag'])) {
				$tag = iunserializer($log['tag']);
				$log['uid'] = $tag['uid'];
			}
            $site = WeUtility::createModuleSite($log['module']);
            if(!is_error($site)) {
				$method = 'payResult';
				if (method_exists($site, $method)) {
					$ret = array();
					$ret['weid'] = $log['uniacid'];
					$ret['uniacid'] = $log['uniacid'];
					$ret['result'] = 'success';
					$ret['type'] = $log['type'];
					$ret['from'] = 'return';
					$ret['tid'] = $log['tid'];
					$ret['uniontid'] = $log['uniontid'];
					$ret['user'] = $log['openid'];
					$ret['fee'] = $log['fee'];
					$ret['tag'] = $tag;
					$ret['is_usecard'] = $log['is_usecard'];
					$ret['card_type'] = $log['card_type'];
					$ret['card_fee'] = $log['card_fee'];
					$ret['card_id'] = $log['card_id'];

                    $refurl = "{$_W['siteroot']}";
                    $refurl =  preg_replace('/\/payment\/tonglian\//','/app/',$refurl);
                    $refurl=$refurl . 'index.php?i='.$_W['uniacid'];
                    if (!empty($log['module'])) {
                        $refurl.="&c=entry&do=index&m=".$log['module'];
                    }
                    // message('支付成功！', $refurl, 'success');
                    exit('<script>top.window.location.href=\'' . $refurl . '\'</script>');
					// exit($site->$method($ret));
				}
			}
		}
	}
	$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `plid`=:plid';
	$log = pdo_fetch($sql, array(':plid' => $params['tid']));
	if(!empty($log) && $log['status'] != '0') {
        $refurl = "{$_W['siteroot']}";
        $refurl =  preg_replace('/\/payment\/tonglian\//','/app/',$refurl);
        $refurl=$refurl . 'index.php?i='.$_W['uniacid'];
        if (!empty($log['module'])) {
            $refurl.="&c=entry&do=index&m=".$log['module'];
        }
        exit('<script>top.window.location.href=\'' . $refurl . '\'</script>');
		// exit('这个订单已经支付成功, 不需要重复支付.');
	}
	$auth = sha1($sl . $log['uniacid'] . $_W['config']['setting']['authkey']);
	if($auth != $_GPC['auth']) {
		exit('参数传输错误.');
	}

	load()->model('payment');
	$_W['uniacid'] = $log['uniacid'];
	$_W['openid'] = $log['openid'];
	$setting = uni_setting($_W['uniacid'], array('payment'));
	if(!is_array($setting['payment'])) {
		exit('没有设定支付参数.');
	}
	$tl_weixin = $setting['payment']['tonglian_weixin'];
	$paramss = array(
		'tid' => $log['tid'],
		'fee' => $log['card_fee'],
		'user' => $log['openid'],
		'title' => urldecode($params['title']),
		'uniontid' => $log['uniontid'],
		'mchid' => $tl_weixin['mchid'],
		'appid' => $tl_weixin['appid'],
		'key' => $tl_weixin['key'],
        'notify_url' => $_W['siteroot']."notify.php",
        'remark' => $_W['uniacid'],
	);
	$wOpt = pay($paramss);
	
?>
<script type="text/javascript">
    document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
        WeixinJSBridge.invoke('getBrandWCPayRequest', {
            'appId' : '<?php echo $wOpt['appId'];?>',
            'timeStamp': '<?php echo $wOpt['timeStamp'];?>',
            'nonceStr' : '<?php echo $wOpt['nonceStr'];?>',
            'package' : '<?php echo $wOpt['package'];?>',
            'signType' : '<?php echo $wOpt['signType'];?>',
            'paySign' : '<?php echo $wOpt['paySign'];?>'
        }, function(res) {
            if(res.err_msg == 'get_brand_wcpay_request:ok') {
                location.search += '&done=1';
            } else {
             alert('启动微信支付失败, 请检查你的支付参数. 详细错误为: ' + res.err_msg);
                history.go(-1);
            }
        });
    }, false);
</script>