<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Virtualcard_VcShopComModel extends ComModel
{
	protected function getUrl($do, $query = NULL)
	{
		$url = mobileUrl($do, $query, true);

		if (strexists($url, '/addons/vcshop/')) {
			$url = str_replace('/addons/vcshop/', '/', $url);
		}

		if (strexists($url, '/core/mobile/order/')) {
			$url = str_replace('/core/mobile/order/', '/', $url);
		}

		return $url;
	}

	public function get_last_count($virtualcardid = 0)
	{
		global $_W;
		$virtualcard = pdo_fetch('SELECT id,total FROM ' . tablename('vcshop_virtualcard') . ' WHERE id=:id and uniacid=:uniacid ', array(':id' => $virtualcardid, ':uniacid' => $_W['uniacid']));

		if (empty($virtualcard)) {
			return 0;
		}

		if ($virtualcard['total'] == -1) {
			return -1;
		}

		$gettotal = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_virtualcard_data') . ' where virtualcardid=:virtualcardid and uniacid=:uniacid and status in (0,1) and userid <> 0', array(':virtualcardid' => $virtualcardid, ':uniacid' => $_W['uniacid']));
		
		$deltotal = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_virtualcard_data') . ' where virtualcardid=:virtualcardid and uniacid=:uniacid and status = 2 ', array(':virtualcardid' => $virtualcardid, ':uniacid' => $_W['uniacid']));

		$virtualcard['total'] = $virtualcard['total'] - $deltotal;

		return $virtualcard['total'] - $gettotal;
	}

	public function sendMessage($virtualcard, $send_total, $member, $account = NULL)
	{
		global $_W;
		$articles = array();
		$title = str_replace('[nickname]', $member['nickname'], $virtualcard['resptitle']);
		$desc = str_replace('[nickname]', $member['nickname'], $virtualcard['respdesc']);
		$title = str_replace('[total]', $send_total, $title);
		$desc = str_replace('[total]', $send_total, $desc);
		$siteroot = $_W['siteroot'];

		if (strexists($siteroot, '/addons/vcshop/')) {
			$replace_str = array('/addons/vcshop/');
			$siteroot = str_replace($replace_str, '/', trim($siteroot));
			$_W['siteroot'] = $siteroot;
		}

		$url = empty($virtualcard['respurl']) ? mobileUrl('sale/virtualcard/my', NULL, true) : $virtualcard['respurl'];
		$picurl = tomedia($virtualcard['respthumb']);

		if (!strexists($picurl, 'http')) {
			$picurl = $this->spec_tomedia($virtualcard['respthumb']);
		}

		if (!empty($virtualcard['resptitle'])) {
			$articles[] = array('title' => urlencode($title), 'description' => urlencode($desc), 'url' => $url, 'picurl' => $picurl);
		}

		if (!empty($articles)) {
			$resp = m('message')->sendNews($member['openid'], $articles, $account);

			if (is_error($resp)) {
				$templateid = $virtualcard['templateid'];
				$msg = array(
					'first'    => array('value' => '亲爱的' . $member['nickname'] . '恭喜您获得虚拟卡券', 'color' => '#ff0000'),
					'keyword1' => array('title' => '业务类型', 'value' => '虚拟卡券通知', 'color' => '#000000'),
					'keyword2' => array('title' => '处理进度', 'value' => '获得' . $virtualcard['virtualcardname'], 'color' => '#000000'),
					'keyword3' => array('title' => '处理内容', 'value' => $desc, 'color' => '#4b9528'),
					'remark'   => array('value' => '点击查看详情', 'color' => '#000000')
				);

				if (!empty($templateid)) {
					m('message')->sendTplNotice($member['openid'], $templateid, $msg, $url);
				}
			}
		}
	}

	public function perms()
	{
		return array(
			'virtualcard' => array(
				'text'     => $this->getName(),
				'isplugin' => true,
				'child'    => array(
					'virtualcard'   => array('text' => '虚拟卡券', 'view' => '查看', 'add' => '添加虚拟卡券-log', 'edit' => '编辑虚拟卡券-log', 'delete' => '删除虚拟卡券-log', 'send' => '发放虚拟卡券-log'),
					'category' => array('text' => '分类', 'view' => '查看', 'add' => '添加分类-log', 'edit' => '编辑分类-log', 'delete' => '删除分类-log'),
					'log'      => array('text' => '虚拟卡券记录', 'view' => '查看', 'export' => '导出-log'),
					'center'   => array('text' => '领券中心设置', 'view' => '查看设置', 'save' => '保存设置-log'),
					'set'      => array('text' => '基础设置', 'view' => '查看设置', 'save' => '保存设置-log')
				)
			)
		);
	}

	public function getVirtualcard($virtualcardid = 0)
	{
		global $_W;
		return pdo_fetch('select * from ' . tablename('vcshop_virtualcard') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $virtualcardid, ':uniacid' => $_W['uniacid']));
	}

	public function createVirtualcard($id='',$total = 0)
	{
		global $_W;
		if (empty($id) || $total == 0) {
			return false;
		}

		$virtualcard = $this->getVirtualcard($id);
		for ($i=0; $i < $total; $i++) { 
			$data = array('uniacid' => $_W['uniacid'], 'virtualcardid' => $id, 'createtime' => time(),'agentid' => $virtualcard['agentid'],'cardsn' => $this->createNO('virtualcard_data', 'cardsn', ''), 'cardkey' => $this->build('nozero', 6),'sendcredit1' => intval($virtualcard['sendcredit1']),'sendmonth' => intval($virtualcard['sendmonth']),'sendday' => intval($virtualcard['sendday']), 'senduid' => $_W['uid'],'is_alltime' => intval($virtualcard['is_alltime']));
			pdo_insert('vcshop_virtualcard_data', $data);
		}
		return true;
		// $virtualcardlog = array('uniacid' => $_W['uniacid'], 'openid' => $member['openid'], 'logno' => m('common')->createNO('coupon_log', 'logno', 'CC'), 'couponid' => $couponid, 'status' => 1, 'paystatus' => -1, 'creditstatus' => -1, 'createtime' => time(), 'getfrom' => 3);
		// 	pdo_insert('vcshop_virtualcard_log', $virtualcardlog);
	}

	/**
	 * 能用的随机数生成
	 * @param string $type 类型 alpha/alnum/numeric/nozero/unique/md5/encrypt/sha1
	 * @param int    $len  长度
	 * @return string
	 */
	public static function build($type = 'alnum', $len = 8)
	{
	    switch ($type) {
	        case 'alpha':
	        case 'alnum':
	        case 'numeric':
	        case 'nozero':
	            switch ($type) {
	                case 'alpha':
	                    $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	                    break;
	                case 'alnum':
	                    $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	                    break;
	                case 'numeric':
	                    $pool = '0123456789';
	                    break;
	                case 'nozero':
	                    $pool = '123456789';
	                    break;
	            }
	            return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
	        case 'unique':
	        case 'md5':
	            return md5(uniqid(mt_rand()));
	        case 'encrypt':
	        case 'sha1':
	            return sha1(uniqid(mt_rand(), true));
	    }
	}


	public function createNO($table, $field, $prefix)
	{
		$billno = $this->build('nozero', 7);

		while (1) {
			$count = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_' . $table) . (' where ' . $field . '=:billno limit 1'), array(':billno' => $billno));

			if ($count <= 0) {
				break;
			}

			$billno = $this->build('nozero', 7);
		}

		return $prefix . $billno;
	}
}

?>
