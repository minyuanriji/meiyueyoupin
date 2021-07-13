<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
class List_VcShopPage extends MobileLoginPage
{
    protected function merchData()
    {
        $merch_plugin = p("merch");
        $merch_data = m("common")->getPluginset("merch");
        if ($merch_plugin && $merch_data["is_openmerch"]) {
            $is_openmerch = 1;
        } else {
            $is_openmerch = 0;
        }
        return array("is_openmerch" => $is_openmerch, "merch_plugin" => $merch_plugin, "merch_data" => $merch_data);
    }
	public function main()
	{
		global $_W;
		global $_GPC;
		include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		// $openid = 'oHQr7wrtYMrL4VD4lWf1QHI4W-QQ';
		$status = trim($_GPC['status']);
		$member = m('member')->getMember($openid);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$status =intval($status);

		$condition = ' and d.used = 0';
		$orderby = '';

		if ($status != 2) {
			$condition = ' and d.used = ' . $status;
			if ($status == 0) {
				$condition .= ' and c.timestart <= unix_timestamp()  and c.timeend >= unix_timestamp() ';
			}
		}else{
			$condition .= ' and c.timeend < unix_timestamp() ';
		}

		if ($status == 1 ) {
			$orderby = ' order by d.usetime desc ';
		}else{
			$orderby = ' order by c.createtime desc ';
		}

		$condition .= ' and d.status = 1 and d.agentid = :mid and d.uniacid = :uniacid ';
		$params = array();
		$params[':uniacid'] = $_W['uniacid'];
		$params[':mid'] = $member['id'];

		$list = pdo_fetchall('select d.*,c.virtualcardname as title from ' . tablename('vcshop_virtualcard_data') . ' as d left join ' .tablename('vcshop_virtualcard'). ' as c on c.id = d.virtualcardid '.( 'where 1 ' . $condition . $orderby .' LIMIT ') . ($pindex - 1) * $psize . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(d.id) from ' . tablename('vcshop_virtualcard_data') . ' as d left join ' .tablename('vcshop_virtualcard'). ' as c on c.id = d.virtualcardid ' . (' where 1 ' . $condition), $params);

		foreach ($list as $key => &$value) {
			if ($value['used'] == 1) {
				$child = m('member')->getMember($value['openid']);
				$value['user']['nickname'] = $child['nickname'];
				$value['user']['avatar'] = tomedia($child['avatar']);
				$value['usetime'] = date('Y-m-d H:i:s',$value['usetime']);
				$value['statusstr'] = '已激活';
			}else{
				$value['statusstr'] = '未激活';
			}
			if ($status == 2) {
				$value['statusstr'] = '已过期';
			}


			$value['curstatus'] = $status;
		}



		

		show_json(1, array('list' => $list, 'pagesize' => $psize, 'total' => $total));
	}
}

?>
