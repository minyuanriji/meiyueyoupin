<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Points_VcShopPage extends PluginMobilePage
{
	public function main()
	{
		include $this->template();
	}
}

?>
