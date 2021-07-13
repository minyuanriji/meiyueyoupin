<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require_once IA_ROOT . '/addons/vcshop/version.php';
require_once IA_ROOT . '/addons/vcshop/defines.php';
require_once VCSHOP_INC . 'functions.php';
require_once VCSHOP_INC . 'processor.php';
require_once VCSHOP_INC . 'plugin_model.php';
require_once VCSHOP_INC . 'com_model.php';
class VcshopModuleProcessor extends Processor
{
	public function respond()
	{
		return parent::respond();
	}
}

?>
