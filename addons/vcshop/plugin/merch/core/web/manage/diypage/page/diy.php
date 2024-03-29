<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require VCSHOP_PLUGIN . 'merch/core/inc/page_merch.php';
class Diy_VcShopPage extends MerchWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$pagetype = 'diy';

		if (!empty($_GPC['keyword'])) {
			$keyword = '%' . trim($_GPC['keyword']) . '%';
			$condition = ' and name like \'' . $keyword . '\' ';
		}

		$diypage_plugin = p('diypage');
		$result = $diypage_plugin->getPageList('diy', $condition, intval($_GPC['page']));
		extract($result);

		if (!empty($list)) {
			foreach ($list as $key => &$value) {
				$url = mobileUrl('diypage', array('id' => $value['id'], 'merchid' => $_W['merchid']), true);
				$value['qrcode'] = m('qrcode')->createQrcode($url);
			}
		}

		include $this->template('diypage/page/list');
	}

	public function edit()
	{
		$this->post('edit');
	}

	public function add()
	{
		$this->post('add');
	}

	protected function post($do)
	{
		global $_W;
		global $_GPC;
		$diypage_plugin = p('diypage');
		$result = $diypage_plugin->verify($do, 'diy');
		extract($result);
		if ($template && $do == 'add') {
			$template['data'] = base64_decode($template['data']);
			$template['data'] = json_decode($template['data'], true);
			$page = $template;
		}

		$allpagetype = $diypage_plugin->getPageType();
		$typename = $allpagetype[$type]['name'];
		$diymenu = pdo_fetchall('select id, name from ' . tablename('vcshop_diypage_menu') . ' where merch=:merch and uniacid=:uniacid  order by id desc', array(':merch' => intval($_W['merchid']), ':uniacid' => $_W['uniacid']));
		$diyadvs = pdo_fetchall('select id, `name` from ' . tablename('vcshop_diypage_plu') . ' where merch=:merch and `type`=1 and status=1 and uniacid=:uniacid  order by id desc', array(':merch' => intval($_W['merchid']), ':uniacid' => $_W['uniacid']));
		$category = pdo_fetchall('SELECT id, name FROM ' . tablename('vcshop_diypage_template_category') . ' WHERE merch=:merch and uniacid=:uniacid order by id desc ', array(':merch' => intval($_W['merchid']), ':uniacid' => $_W['uniacid']));
		$levels = array();
		$levels['member'] = m('member')->getLevels(false);
		array_unshift($levels['member'], array('id' => 'default', 'levelname' => '默认等级'));

		if (p('commission')) {
			$levels['commission'] = p('commission')->getLevels(true, true);
		}

		if ($_W['ispost']) {
			$data = $_GPC['data'];
			$diypage_plugin->savePage($id, $data);
		}

		$hasplugins = json_encode(array());
		include $this->template('diypage/page/post');
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$diypage_plugin = p('diypage');
		$diypage_plugin->delPage($id);
	}

	public function savetemp()
	{
		global $_W;
		global $_GPC;
		$temp = array('type' => intval($_GPC['type']), 'cate' => intval($_GPC['cate']), 'name' => trim($_GPC['name']), 'preview' => trim($_GPC['preview']), 'data' => $_GPC['data'], 'merch' => intval($_W['merchid']));
		$diypage_plugin = p('diypage');
		$diypage_plugin->saveTemp($temp);
	}
}

?>
