<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Index_VcShopPage extends SystemPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$domain = trim(preg_replace('/http(s)?:\\/\\//', '', rtrim($_W['siteroot'], '/')));
		$ip = gethostbyname($_SERVER['HTTP_HOST']);
		$setting = setting_load('site');
		$id = ((isset($setting['site']['key']) ? $setting['site']['key'] : '0'));
		$auth = get_auth();
		load()->func('communication');
		if(!$id) $id = rand(56865,99999);
		if ($_W['ispost']) {
			show_json(0);
			if (empty($_GPC['domain'])) {
				show_json(0, '域名不能为空!');
			}
			if (empty($_GPC['code'])) {
				show_json(0, '请填写授权码!');
			}
			if (empty($_GPC['id'])) {
				show_json(0,'您还没未注册站点!');
			}
			$resp = ihttp_request('http://admin.zheyitianshi.com/gateway.php/a=rr', array('ip' => $ip, 'id' => $id, 'code' => $_GPC['code'], 'domain' => $domain));
			$result = @json_decode($resp['content'], true);
			
			if ($result['status'] == '1') {
				$set = pdo_fetch('select id, sets from ' . tablename('vcshop_sysset') . ' order by id asc limit 1');
				$sets = iunserializer($set['sets']);

				if (!is_array($sets)) {
					$sets = array();
				}

				$sets['auth'] = array('ip' => $ip, 'id' => $id, 'code' => $_GPC['code'], 'domain' => $domain);

				if (empty($set)) {
					pdo_insert('vcshop_sysset', array('sets' => iserializer($sets), 'uniacid' => $_W['uniacid']));
				}
				 else {
					pdo_update('vcshop_sysset', array('sets' => iserializer($sets)), array('id' => $set['id']));
				}

				$result['result']['url'] = webUrl('system/auth');
				show_json(1,$result['message']);
				exit(json_encode($result));
			}else{
				if(!isset($result['status'])) show_json(0,'验证授权失败，无法连接授权服务器！');
				show_json(0,$result['message']);
			}
			exit(json_encode($result));
		}
		
		$result = array('status' => 1);

		if (!empty($ip) && !empty($id) && !empty($auth['code'])) {
			// load()->func('communication');
			// $resp = ihttp_request(VCSHOP_AUTH_URL, array('ip' => $ip, 'id' => $id, 'code' => $auth['code'], 'domain' => $domain));
			// $result = @json_decode($resp['content'], true);
		}
		include $this->template();
	}
}
?>