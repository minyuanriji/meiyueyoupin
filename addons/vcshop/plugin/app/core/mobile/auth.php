<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require_once VCSHOP_PLUGIN . 'app/core/page_mobile.php';
class Auth_VcShopPage extends AppMobilePage
{
	public function __construct()
	{
		global $_W;
		$this->authkey = $_W['setting']['site']['token'] . '_' . $_W['uniacid'];
	}

	public function main()
	{
		global $_W;
		global $_GPC;
		$token = trim($_GPC['token']);
		$callback = trim($_GPC['callback']);
		$callback = urldecode($callback);
		if (!empty($token) && !empty($callback)) {
			$token = authcode(base64_decode($token), 'DECODE', $this->authkey);
			$params = explode('|', $token);

			if (!empty($params[0])) {
				$member = m('member')->getMember($params[0]);

				if (!empty($member)) {
					if (strexists($callback, '&c=entry&m=vcshop&do=mobile')) {
						m('account')->setLogin($member);
					}
				}
			}
		}

		header('location: ' . $callback);
	}

	public function token()
	{
		global $_GPC;
		$token = trim($_GPC['token']);

		if (!empty($token)) {
			$token = authcode(base64_decode($token), 'DECODE', '*736bg%21@');

			if (!empty($token)) {
				return app_json(array('token' => $token));
			}

			return app_error(AppError::$UserTokenFail);
		}

		return app_json();
	}
}

?>
