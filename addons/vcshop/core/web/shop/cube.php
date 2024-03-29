<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Cube_VcShopPage extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;

		if ($_W['ispost']) {
			$imgs = $_GPC['cube_img'];
			$urls = $_GPC['cube_url'];
			$cubes = array();

			if (is_array($imgs)) {
				foreach ($imgs as $key => $img) {
					$cubes[] = array('img' => save_media($img), 'url' => trim($urls[$key]));
				}
			}

			$shop = $_W['shopset']['shop'];
			$shop['cubes'] = $cubes;
			m('common')->updateSysset(array('shop' => $shop));
			plog('shop.cube.edit', '修改基本设置');
			show_json(1);
		}

		$cubes = isset($_W['shopset']['shop']['cubes']) ? $_W['shopset']['shop']['cubes'] : array();
		include $this->template();
	}
}

?>
