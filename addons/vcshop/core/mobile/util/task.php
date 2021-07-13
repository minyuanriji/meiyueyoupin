<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Task_VcShopPage extends MobilePage
{
	public function main()
	{
		$this->runTasks();
	}
}

?>
