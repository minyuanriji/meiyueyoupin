<?php
error_reporting(0);
require '../../../../../framework/bootstrap.inc.php';
require '../../../../../addons/vcshop/defines.php';
require '../../../../../addons/vcshop/core/inc/functions.php';
global $_W;
global $_GPC;
ignore_user_abort();
set_time_limit(0);
$sets = pdo_fetchall('select uniacid from ' . tablename('vcshop_sysset'));

foreach ($sets as $set) {
	$_W['uniacid'] = $set['uniacid'];

	if (empty($_W['uniacid'])) {
		continue;
	}

	$trade = m('common')->getSysset('trade', $_W['uniacid']);
	$goods = pdo_fetchall('select id,statustimestart,statustimeend from ' . tablename('vcshop_goods') . ' where uniacid = ' . $_W['uniacid'] . ' and isstatustime > 0 and deleted = 0 ');

	foreach ($goods as $key => $value) {
		if ($value['statustimestart'] < time() && time() < $value['statustimeend']) {
			$value['status'] = 1;
		}
		else {
			$value['status'] = 0;
		}

		pdo_update('vcshop_goods', array('status' => $value['status']), array('id' => $value['id']));
	}
}

?>
