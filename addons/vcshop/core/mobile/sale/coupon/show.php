<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Show_VcShopPage extends MobileLoginPage
{
	public function main()
	{
		include $this->template('sale/coupon/my/showcoupons');
	}

	public function main2()
	{
		include $this->template('sale/coupon/my/showcoupons2');
	}
}

?>
