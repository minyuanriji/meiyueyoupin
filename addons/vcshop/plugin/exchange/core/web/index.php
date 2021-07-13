<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_VcShopPage extends PluginWebPage
{
	public function main()
	{
		global $_W;
		include $this->template();
	}
}

?>
