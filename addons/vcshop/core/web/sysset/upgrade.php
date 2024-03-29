<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

global $_W;
global $_GPC;

if (!$_W['isfounder']) {
	$this->message('无权访问!');
}

$entry = IA_ROOT . '/addons/vcshop/plugin/poster/model.php';
$op = empty($_GPC['op']) ? 'display' : $_GPC['op'];
load()->func('communication');
load()->func('file');

if ($op == 'display') {
	$auth = $this->getAuthSet();
	$versionfile = IA_ROOT . '/addons/vcshop/version.php';
	$updatedate = date('Y-m-d H:i', filemtime($versionfile));
	$version = VCSHOP_VERSION;
}
else if ($op == 'check') {
	set_time_limit(0);
	$auth = $this->getAuthSet();
	$version = defined('VCSHOP_VERSION') ? VCSHOP_VERSION : '1.0';
	$resp = ihttp_post(VCSHOP_AUTH_URL, array('type' => 'check', 'ip' => $auth['ip'], 'id' => $auth['id'], 'code' => $auth['code'], 'domain' => $auth['domain'], 'version' => $version, 'manual' => 1));
	$templatefiles = '';
	$ret = @json_decode($resp['content'], true);

	if (is_array($ret)) {
		$templatefiles = '';

		if ($ret['result'] == 1) {
			$files = array();

			if (!empty($ret['files'])) {
				foreach ($ret['files'] as $file) {
					$entry = IA_ROOT . '/addons/vcshop/' . $file['path'];
					if (!is_file($entry) || md5_file($entry) != $file['hash']) {
						$files[] = array('path' => $file['path'], 'download' => 0);
						if (strexists($entry, 'template/mobile') && strexists($entry, '.html')) {
							$templatefiles .= '/' . $file['path'] . '
';
						}
					}
				}
			}

			cache_write('cloud:modules:upgrade', array('files' => $files, 'version' => $ret['version'], 'upgrade' => $ret['upgrade']));
			$log = base64_decode($ret['log']);

			if (!empty($templatefiles)) {
				$log = '<br/><b>模板变化:</b><br/>' . $templatefiles . '
' . $log;
			}

			exit(json_encode(array('result' => 1, 'version' => $ret['version'], 'filecount' => count($files), 'upgrade' => !empty($ret['upgrade']), 'log' => str_replace('
', '<br/>', $log))));
		}
	}

	exit(json_encode(array('result' => 0, 'message' => $resp['content'] . '. ')));
}
else if ($op == 'download') {
	$upgrade = cache_load('cloud:modules:upgrade');
	$files = $upgrade['files'];
	$version = $upgrade['version'];
	$auth = $this->getAuthSet();
	$path = '';

	foreach ($files as $f) {
		if (empty($f['download'])) {
			$path = $f['path'];
			break;
		}
	}

	if (!empty($path)) {
		$resp = ihttp_post(VCSHOP_AUTH_URL, array('type' => 'download', 'ip' => $auth['ip'], 'id' => $auth['id'], 'code' => $auth['code'], 'domain' => $auth['domain'], 'path' => $path));
		$ret = @json_decode($resp['content'], true);

		if (is_array($ret)) {
			$path = $ret['path'];
			$dirpath = dirname($path);

			if (!is_dir(IA_ROOT . '/addons/vcshop/' . $dirpath)) {
				mkdirs(IA_ROOT . '/addons/vcshop/' . $dirpath, '0777');
			}

			$content = base64_decode($ret['content']);
			file_put_contents(IA_ROOT . '/addons/vcshop/' . $path, $content);

			if (isset($ret['path1'])) {
				$path1 = $ret['path1'];
				$dirpath1 = dirname($path1);

				if (!is_dir(IA_ROOT . '/addons/vcshop/' . $dirpath1)) {
					mkdirs(IA_ROOT . '/addons/vcshop/' . $dirpath1, '0777');
				}

				$content1 = base64_decode($ret['content1']);
				file_put_contents(IA_ROOT . '/addons/vcshop/' . $path1, $content1);
			}

			$success = 0;

			foreach ($files as &$f) {
				if ($f['path'] == $path) {
					$f['download'] = 1;
					break;
				}

				if ($f['download']) {
					++$success;
				}
			}

			unset($f);
			cache_write('cloud:modules:upgrade', array('files' => $files, 'version' => $version, 'upgrade' => $upgrade['upgrade']));
			exit(json_encode(array('result' => 1, 'total' => count($files), 'success' => $success)));
		}
	}
	else {
		if (!empty($upgrade['upgrade'])) {
			$updatefile = IA_ROOT . '/addons/vcshop/upgrade.php';
			file_put_contents($updatefile, base64_decode($upgrade['upgrade']));
			require $updatefile;
			@unlink($updatefile);
		}

		load()->func('file');
		@rmdirs(IA_ROOT . '/addons/vcshop/tmp');
		file_put_contents(IA_ROOT . '/addons/vcshop/version.php', '<?php if(!defined(\'IN_IA\')) {exit(\'Access Denied\');}if(!defined(\'VCSHOP_VERSION\')) {define(\'VCSHOP_VERSION\', \'' . $upgrade['version'] . '\');}');
		cache_delete('cloud:modules:upgrade');
		$time = time();
		global $my_scenfiles;
		my_scandir(IA_ROOT . '/addons/vcshop');

		foreach ($my_scenfiles as $file) {
			if (!strexists($file, '/vcshop/data/') && !strexists($file, 'version.php')) {
				@touch($file, $time);
			}
		}

		exit(json_encode(array('result' => 2)));
	}
}
else {
	if ($op == 'checkversion') {
		file_put_contents(IA_ROOT . '/addons/vcshop/version.php', '<?php if(!defined(\'IN_IA\')) {exit(\'Access Denied\');}if(!defined(\'VCSHOP_VERSION\')) {define(\'VCSHOP_VERSION\', \'1.0\');}');
		header('location: ' . webUrl('upgrade'));
		exit();
	}
}

include $this->template('web/sysset/upgrade');

?>
