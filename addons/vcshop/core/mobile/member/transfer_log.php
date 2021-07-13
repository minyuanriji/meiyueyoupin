<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
class Transfer_log_VcShopPage extends MobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$_GPC['type'] = intval($_GPC['type']);
		include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$type = intval($_GPC['type']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
        $member = m('member')->getMember($_W['openid']);
        $condition = ' AND openid=:openid AND uniacid=:uniacid OR assigns_id=:mid';
        $params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'],':mid'=>$member['id']);
		$list = pdo_fetchall('select * from ' . tablename('vcshop_member_transfer_log') . ' where 1 ' . $condition . ' order by createtime desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('vcshop_member_transfer_log') . ' where 1 ' . $condition, $params);

		foreach ($list as &$row) {
			$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
			$sql = 'SELECT nickname,realname,mobile FROM '.tablename('vcshop_member').' WHERE id=:id AND uniacid=:uniacid';
            if( $row['tosell_id'] == $member['id'] ){
	            $row['to_member'] = pdo_fetch($sql,array(':id'=>$row['assigns_id'],':uniacid'=>$_W['uniacid']));
            	$row['type'] = 1; //转出
			}elseif( $row['assigns_id'] == $member['id']  ){
                $row['type'] = 2; //转入
                $row['to_member'] = pdo_fetch($sql,array(':id'=>$row['tosell_id'],':uniacid'=>$_W['uniacid']));
			}
		}

		unset($row);
		show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $psize));
	}
}

?>
