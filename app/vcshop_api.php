<?php
define('IN_SYS', true);
require __DIR__ . '/../framework/bootstrap.inc.php';
load()->web('common');
$uniacid = intval($_GPC['i']);
$_W['attachurl'] = $_W['attachurl_local'] = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/';

if (!(empty($_W['setting']['remote'][$_W['uniacid']]['type']))) {
	$_W['setting']['remote'] = $_W['setting']['remote'][$_W['uniacid']];
}


if (!(empty($_W['setting']['remote']['type']))) {
	if ($_W['setting']['remote']['type'] == ATTACH_FTP) {
		$_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['ftp']['url'] . '/';
	}
	 else if ($_W['setting']['remote']['type'] == ATTACH_OSS) {
		$_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['alioss']['url'] . '/';
	}
	 else if ($_W['setting']['remote']['type'] == ATTACH_QINIU) {
		$_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['qiniu']['url'] . '/';
	}
	 else if ($_W['setting']['remote']['type'] == ATTACH_COS) {
		$_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['cos']['url'] . '/';
	}

}


if (!(empty($_GPC['formwe7']))) {
	$bind = pdo_fetch('SELECT * FROM ' . tablename('vcshop_wxapp_bind') . ' WHERE wxapp=:wxapp LIMIT 1', array(':wxapp' => $uniacid));

	if (!(empty($bind)) && !(empty($bind['uniacid']))) {
		$uniacid = $_GPC['i'] = $bind['uniacid'];
	}

}


if (empty($uniacid)) {
	exit('Access Denied.');
}


$site = WeUtility::createModuleSite('vcshop');
$_GPC['c'] = 'site';
$_GPC['a'] = 'entry';
$_GPC['m'] = 'vcshop';
$_GPC['do'] = 'mobile';
$_W['uniacid'] = (int) $_GPC['i'];
$_W['acid'] = (int) $_GPC['i'];

if (!(isset($_GPC['r']))) {
	$_GPC['r'] = 'app';
}
 else {
	$_GPC['r'] = 'app.' . $_GPC['r'];
}

if (!(is_error($site))) {
	$method = 'doMobileMobile';
	$site->uniacid = $uniacid;
	$site->inMobile = true;

	if (method_exists($site, $method)) {
		$site->$method();
		exit();
	}

}


?>