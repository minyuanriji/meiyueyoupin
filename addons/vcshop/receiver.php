<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require_once IA_ROOT . '/addons/vcshop/version.php';
require_once IA_ROOT . '/addons/vcshop/defines.php';
require_once VCSHOP_INC . 'functions.php';
require_once VCSHOP_INC . 'receiver.php';
class VcshopModuleReceiver extends Receiver
{
	public function receive()
	{
		parent::receive();
	}
}

?>
