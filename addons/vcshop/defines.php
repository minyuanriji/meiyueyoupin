<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

define('VCSHOP_DEBUG', false);
!defined('VCSHOP_PATH') && define('VCSHOP_PATH', IA_ROOT . '/addons/vcshop/');
!defined('VCSHOP_CORE') && define('VCSHOP_CORE', VCSHOP_PATH . 'core/');
!defined('VCSHOP_DATA') && define('VCSHOP_DATA', VCSHOP_PATH . 'data/');
!defined('VCSHOP_VENDOR') && define('VCSHOP_VENDOR', VCSHOP_PATH . 'vendor/');
!defined('VCSHOP_CORE_WEB') && define('VCSHOP_CORE_WEB', VCSHOP_CORE . 'web/');
!defined('VCSHOP_CORE_MOBILE') && define('VCSHOP_CORE_MOBILE', VCSHOP_CORE . 'mobile/');
!defined('VCSHOP_CORE_SYSTEM') && define('VCSHOP_CORE_SYSTEM', VCSHOP_CORE . 'system/');
!defined('VCSHOP_PLUGIN') && define('VCSHOP_PLUGIN', VCSHOP_PATH . 'plugin/');
!defined('VCSHOP_PROCESSOR') && define('VCSHOP_PROCESSOR', VCSHOP_CORE . 'processor/');
!defined('VCSHOP_INC') && define('VCSHOP_INC', VCSHOP_CORE . 'inc/');
!defined('VCSHOP_URL') && define('VCSHOP_URL', $_W['siteroot'] . 'addons/vcshop/');
!defined('VCSHOP_TASK_URL') && define('VCSHOP_TASK_URL', $_W['siteroot'] . 'addons/vcshop/core/task/');
!defined('VCSHOP_LOCAL') && define('VCSHOP_LOCAL', '../addons/vcshop/');
!defined('VCSHOP_STATIC') && define('VCSHOP_STATIC', VCSHOP_URL . 'static/');
!defined('VCSHOP_PREFIX') && define('VCSHOP_PREFIX', 'vcshop_');
define('VCSHOP_PLACEHOLDER', '../addons/vcshop/static/images/placeholder.png');

?>
