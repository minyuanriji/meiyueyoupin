<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}
class Test_VcShopPage extends PluginWebPage
{
    public function main()
    {
        global $_W;
        $orderid = 17557;
        $openid = 'oHQr7wq4nGND8r3CeEe4-CfH0sOg';
        $afterbuy = true;
        if (empty($orderid)) {
            return false;
        }
        if (empty($openid)) {
            return false;
        }
        $set = p('commission')->getSet();
        if (empty($set['level'])) {
            return false;
        }
        $m = m('member')->getMember($openid);
        if (empty($m)) {
            return NULL;
        }
        if ($m['status'] == 0 || $m['agentblack'] == 1) {
            return NULL;
        }
        $leveltype = intval($set['leveltype']);
        if (!empty($m['agentnotupgrade'])) {
            return NULL;
        }
        if ($leveltype != 11) {
            return NULL;
        }
        $oldlevel = p('commission')->getLevel($m['openid']);
        if (empty($oldlevel['id'])) {
            $oldlevel = array(
                'levelname' => empty($set['levelname']) ? '普通等级' : $set['levelname'],
                'commission1' => $set['commission1'],
                'commission2' => $set['commission2'],
                'commission3' => $set['commission3'],
                'level' => 0
            );
        }
        // han 20191121 修改为可控制付款后和完成后
        if($afterbuy){
            $buy_status = 1;
        }else{
            $buy_status = 3;
        }
        $orders = pdo_fetchall('select og.goodsid from ' . tablename('vcshop_order') . ' o ' . ' left join  ' . tablename('vcshop_order_goods') . ' og on og.orderid=o.id ' . ' where o.openid=:openid and o.status>=:status and o.uniacid=:uniacid and og.orderid=:orderid', array(
            ':uniacid' => $_W['uniacid'],
            ':openid' => $openid,
            ':orderid' => $orderid,
            ':status' => $buy_status
        ));
        if ($leveltype == 11) {
            $newlevel = pdo_fetchall('select * from ' . tablename('vcshop_commission_level') . ' where uniacid=:uniacid  ', array(
                ':uniacid' => $_W['uniacid']
            ), 'level');
            foreach ($newlevel as $key => $value) {
                $newarray[$key] = iunserializer($value['goodsids']);
            }
            print_r($newlevel);
            $orders    = array_column($orders, 'goodsid', 'goodsid');
            $leveldata = array();
            foreach ($newarray as $ke => $val) {
                foreach ($val as $k => $v) {
                    foreach ($orders as $a => $b) {
                        if ($b == $v) {
                            array_push($leveldata, $ke);
                        }
                    }
                }
            }
            if (empty($leveldata)) {
                return NULL;
            }
            $biglevel = max($leveldata);
            if (empty($newlevel)) {
                return NULL;
            }
            $newlevel = $newlevel[$biglevel];
            if (!empty($oldlevel['level'])) {
                if ($oldlevel['level'] == $newlevel['level']) {
                    return NULL;
                }
                if ($newlevel['level'] < $oldlevel['level']) {
                    return NULL;
                }
            }
        }
        print_r($newlevel);
        // pdo_update('vcshop_member', array(
        //     'agentlevel' => $newlevel['id']
        // ), array(
        //     'id' => $m['id']
        // ));
        // $this->sendMessage($m['openid'], array(
        //     'nickname' => $m['nickname'],
        //     'oldlevel' => $oldlevel,
        //     'newlevel' => $newlevel
        // ), TM_COMMISSION_UPGRADE);
        // if($newlevel['id'] == $set['quota_level']){
        //     // han 20191121 添加推荐398会员奖励
        //     // $this->calculatebonus($orderid,true);
        // }else{
        //     $this->agentReward($m['id'],$newlevel,$orderid);
        // }
    }

    public function bonus()
    {
        global $_W;
        // if(empty($id) || empty($level)) return false;
        $set        = $this->model->getSet();
        echo "<pre>";
        $debug = array();
        $id = 24395;
        $orderid = 31972;
        $member = m('member')->getMember($id);
        $level = $this->model->getLevel($member['agentlevel']);
        if(empty($member) || empty($member['agentlevel']) || empty($member['isagent']) || empty($member['status'])){
            $debug[] = '1-'.$id;
            // pdo_insert('a',array('info'=>'1-'.$id));
            return false;
        }
        // 检查是否第一次触发
        // $lock = pdo_fetchcolumn('SELECT ifnull(count(*),0) FROM ' . tablename('vcshop_lock') . ' WHERE uniacid=:uniacid AND mid=:mid AND type=1 AND levelweight>=:levelweight',array(':uniacid' => $_W['uniacid'], ':mid' => $member['id'],':levelweight'=>$level['level']));
        // if($lock > 0){
        //     // pdo_insert('a',array('info'=>'2-'.$id));
        //     return false;
        // }else{
        //     pdo_insert('vcshop_lock',array('uniacid' => $_W['uniacid'], 'mid' => $member['id'], 'uid' => $member['uid'], 'status' => 1, 'type' => 1,'levelweight'=>$level['level']));
        // }
        $level_record = array(
            'uniacid' => $_W['uniacid'], 
            'mid' => $member['id'], 
            'uid' => $member['uid'], 
            'openid' => $member['openid'],
            'agentlevel'=>$level['id'], 
            'createtime' => time(), 
            'quota_status' => !empty($level['is_team']) ? 2 : 1, 
            'notice_status' => 1, 
            'remark'=>'升级为'.$level['levelname'],
            'orderid'=>$orderid
        );
        // pdo_insert('vcshop_commission_level_record',$level_record);
        // $record_id = pdo_insertid();
        print_r($level_record);echo "<br>";
        if(empty($level['mix'])){
            return false;
        }
        $level['mix_'] = (is_serialized($level['mix'])) ? iunserializer($level['mix']) : $level['mix'];
        // 发放自己的名额 s
        foreach ($level['mix_']['rec'] as $key => $value) {
            if(empty($value['thisquota'])){
                continue;
            }
            // $this->setQuota($member['openid'],$key,$value['thisquota'],'升级为'.$level['levelname'].'赠送名额');
            print_r($value['thisquota'],'升级为'.$level['levelname'].'赠送名额');echo "<br>";
        }
        if($level['is_team'] == 0){
            return false;
        }
        // 开发到此处，还没测试，还需考虑是否需要记录是否发放完名额
        // 暂时考虑为不记录是否发放完名额
        // 发放自己的名额 e
        // 发放奖励或者通知上线 s
        // return true;
        $has_rec = pdo_fetchcolumn('SELECT ifnull(count(*),0) FROM ' . tablename('vcshop_rewardlog') . ' WHERE uniacid=:uniacid AND mid=:mid AND type in(1,2) AND levelweight>=:levelweight',array(':uniacid'=>$_W['uniacid'],':mid' =>$member['id'],':levelweight'=>$level['level']));
        if($has_rec > 0) return false;
        if(empty($member['agentid'])){
            return false;   
        } 
        $parents = pdo_fetchall('SELECT r.level as relation_level,m.id,m.openid,m.nickname,m.agentlevel,m.isagent,m.status,l.level as levelweight,l.mix,l.is_team FROM ' . tablename('vcshop_commission_relation') . ' r' . ' LEFT JOIN ' . tablename('vcshop_member') . ' m ON m.id=r.pid' . ' LEFT JOIN ' . tablename('vcshop_commission_level') . ' l ON l.id=m.agentlevel' . ' WHERE r.id=:id ORDER BY relation_level ASC',array(':id'=>$member['id']));
        print_r($parents);
        if(empty($parents)){
            return false;
        }
        if($parents[0]['levelweight'] >= $level['level']){
            // 直推有奖
            $agent = $parents[0];
            $agent_quota = pdo_fetch('SELECT * FROM ' . tablename('vcshop_member_quota') . ' WHERE uniacid=:uniacid AND mid=:mid AND agentlevel=:agentlevel',array(':uniacid'=>$_W['uniacid'], ':mid'=>$agent['id'], ':agentlevel' => $level['id']));
            print_r($agent_quota);die;
            // if($agent_quota && $agent_quota['num'] > 0){
            //     // $this->setQuota($agent['openid'],$level['id'],-1,'团队会员-['.$member['id'].']'.$member['nickname'].'升级为['.$level['levelname'].'],扣除库存');
            //     print_r($agent['openid']);echo "<br>";
            //     print_r('团队会员-['.$member['id'].']'.$member['nickname'].'升级为['.$level['levelname'].'],扣除库存');echo "<br>";

            //     $agent['mix_'] = (is_serialized($agent['mix'])) ? iunserializer($agent['mix']) : $agent['mix'];
            //     $team_bonus = $agent['mix_']['rec'][$level['id']]['team_bonus'];
            //     $team_quota = $agent['mix_']['rec'][$level['id']]['user_quota'];
            //     $desc = '团队会员-['.$member['id'].']'.$member['nickname'].'升级为['.$level['levelname'].'],获奖励';
            //     if($team_quota>0 && isset($set['quota_level'])){
            //         $this->setQuota($agent['openid'],$set['quota_level'],$team_quota,'团队会员-['.$member['id'].']'.$member['nickname'].'升级为['.$level['levelname'].'],奖励库存');
            //     }
            //     if($team_bonus>0){
            //         m('member')->setCredit($agent['openid'], 'credit2', $team_bonus, $desc . $team_bonus);
            //         if(empty($orderid)){
            //             $this->insertRewardlog($_W['uniacid'],$member['id'],$agent['id'],$team_bonus,$desc,1,$level['id'],$level['level'],$agent['relation_level'],1);//1为type
            //         }else{

            //             $this->insertRewardlog($_W['uniacid'],$member['id'],$agent['id'],$team_bonus,$desc,2,$level['id'],$level['level'],$agent['relation_level'],1,$orderid);//1为type
            //         }
            //         return true;
            //     }
            // }
        }
        
    }

    public function second()
    {
        global $_W;
        $orderid = 32465;
        $update = true;
        $set        = $this->model->getSet();
        if(empty($set['quota_second_level'])){
            return null;
        }
        $quota_level = pdo_fetch('SELECT * FROM ' . tablename('vcshop_commission_level') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid' => $_W['uniacid'], ':id' => $set['quota_second_level']));
        if(empty($quota_level)){
            return null;
        }
        $quota_level['goodsids'] = (is_serialized($quota_level['goodsids'])) ? iunserializer($quota_level['goodsids']) : $quota_level['goodsids'];
        if(empty($quota_level['goodsids'])){
            return null;
        }
        // han 20191120 add openid
        $order      = pdo_fetch('select openid,agentid,price,goodsprice,deductcredit2,discountprice,isdiscountprice,dispatchprice,changeprice,ispackage,packageid,couponprice,buyagainprice,deductprice,deductenough,merchdeductenough,grprice from ' . tablename('vcshop_order') . ' where id=:id limit 1', array(
              ':id' => $orderid
        ));
        $member = m('member')->getMember($order['openid']);

        $level = pdo_fetch('SELECT * FROM ' . tablename('vcshop_commission_level') . ' WHERE uniacid=:uniacid AND id=:id',array(':uniacid' => $_W['uniacid'], ':id' => $member['agentlevel']));
        // print_r($level);
        if(!$level){
            return null;
        }
        $goodsids = pdo_fetchall('select goodsid from ' . tablename('vcshop_order_goods') . ' where uniacid=:uniacid and orderid=:orderid',array(':uniacid'=>$_W['uniacid'],':orderid'=>$orderid));
        if(empty($goodsids)){
            return null;
        }
        $hasgoods = false;
        foreach ($goodsids as $key => $value) {
            if(in_array($value['goodsid'], $quota_level['goodsids'])){
                $hasgoods = true;
                break;
            }
        }
        if(!$hasgoods){
            return null;
        }
        // // 检查是否第一次触发
        // $lock = pdo_fetchcolumn('SELECT ifnull(count(*),0) FROM ' . tablename('vcshop_lock') . ' WHERE uniacid=:uniacid AND mid=:mid AND type=2 AND orderid>=:orderid',array(':uniacid' => $_W['uniacid'], ':mid' => $member['id'],':orderid'=>$orderid));
        // if($lock > 0){
        //     return false;
        // }else{
        //     pdo_insert('vcshop_lock',array('uniacid' => $_W['uniacid'], 'mid' => $member['id'], 'uid' => $member['uid'], 'status' => 1, 'type' => 2,'orderid'=>$orderid));
        // }

        $parent_use = array();
        $parents = pdo_fetchall('SELECT r.level as relation_level,m.id,m.openid,m.nickname,m.agentlevel,m.isagent,m.status,l.level as levelweight,l.mix,l.is_team FROM ' . tablename('vcshop_commission_relation') . ' r' . ' LEFT JOIN ' . tablename('vcshop_member') . ' m ON m.id=r.pid' . ' LEFT JOIN ' . tablename('vcshop_commission_level') . ' l ON l.id=m.agentlevel' . ' WHERE r.id=:id AND l.is_team=1 ORDER BY relation_level ASC',array(':id'=>$member['id']));
        print_r('SELECT r.level as relation_level,m.id,m.openid,m.nickname,m.agentlevel,m.isagent,m.status,l.level as levelweight,l.mix,l.is_team FROM ' . tablename('vcshop_commission_relation') . ' r' . ' LEFT JOIN ' . tablename('vcshop_member') . ' m ON m.id=r.pid' . ' LEFT JOIN ' . tablename('vcshop_commission_level') . ' l ON l.id=m.agentlevel' . ' WHERE r.id=:id AND l.is_team=1 ORDER BY relation_level ASC');
        // print_r($member['id']);
        // if($level['is_team'] == 1){
        //     $my[] = array(
        //         'relation_level' => 0,
        //         'id' => $member['id'],
        //         'openid' => $member['openid'],
        //         'nickname' => $member['nickname'],
        //         'agentlevel' => $member['agentlevel'],
        //         'isagent' => $member['isagent'],
        //         'status' => $member['status'],
        //         'levelweight' => $level['level'],
        //         'mix' => $level['mix'],
        //         'is_team' => $level['is_team']
        //     );
        //     $parents = array_merge($my,$parents);
        // }
        // print_r($parents);
        
        // $checkorder = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_rewardlog') . ' where uniacid=:uniacid and level=0 and levelweight=0 and orderid=:orderid and mid=:mid',array(':uniacid'=>$_W['uniacid'],':mid'=>$member['id'],':orderid'=>$orderid));
        // if($checkorder > 0){
        //     return '已经奖励了';
        // }
        // han 20200422 防止重复奖励 e
        if(!empty($parents)){
            $last_weight = 0;
            // 未做：统计每个代理扣了多少库存然后搬出去，每次扣库存不是只扣1，而是扣商品数量，break那里要判断是否扣完改商品数量
            foreach ($parents as $key => $value) {

                if($value['levelweight'] <= $last_weight){
                    continue;
                }
                $agent_quota = pdo_fetch('SELECT * FROM ' . tablename('vcshop_member_quota') . ' WHERE uniacid=:uniacid AND mid=:mid AND agentlevel=:agentlevel',array(':uniacid'=>$_W['uniacid'], ':mid'=>$value['id'], ':agentlevel' => $set['quota_second_level']));
                if($agent_quota && $agent_quota['num'] > 0){
                    if($update){
                        // $this->setQuota($value['openid'],$set['quota_second_level'],-1,'团队会员-['.$member['id'].']'.$member['nickname'].'购买产品,扣除库存');
                        print_r('团队会员-['.$member['id'].']'.$member['nickname'].'购买产品,扣除库存');
                        $parent_use[$value['id']] = array('level'=>$set['quota_second_level'],'num'=>1);
                    }
                }else{
                    continue;
                }
                if($update){
                    $value['mix_'] = (is_serialized($value['mix'])) ? iunserializer($value['mix']) : $value['mix'];
                    $team_bonus =(float) $value['mix_']['rec'][$set['quota_second_level']]['team_bonus'];
                    $desc = '团队会员-['.$member['id'].']'.$member['nickname'].'购买产品,获奖励';
                    if($team_bonus>0){
                        $rewardlog = array(
                            'uniacid'   => $_W['uniacid'],
                            'mid'    => $member['id'],
                            'agentid' => $value['id'],
                            'money' => $team_bonus,
                            'desc'    => $desc,
                            'type' => 2,
                            'level' => 0,
                            'levelweight' => 0,
                            'floot' => $value['relation_level'],
                            'status'    => 0,
                            'orderid'   => $orderid,
                            'create_time'   => time()
                        );
                        // pdo_insert('vcshop_rewardlog',$rewardlog);
                        print_r($rewardlog);
                        // $this->insertRewardlog($_W['uniacid'],$member['id'],$value['id'],$team_bonus,$desc,2,0,0,$value['relation_level'],$status=0,$orderid);//第一个1为type
                    }
                }
                break;
            }
        }
        // if($level['is_team'] == 1){
        //     // 自购修改订单上级
        //     $res = pdo_update('vcshop_order', array(
        //         'agentid' => $member['id']
        //     ), array(
        //         'id' => $orderid
        //     ));
        //     $this->calculate($orderid, true, $res ? $member['id'] : NULL);
        // }
    }

    public function line()
    {
        global $_W;
        echo "<pre>";
        // $memberid = 26700;
        // $member = m('member')->getMember($memberid);
        // $parents = pdo_fetchall('SELECT r.level as relation_level,m.id,m.openid,m.nickname,m.agentlevel,m.isagent,m.status,l.level as levelweight,l.mix,l.is_team FROM ' . tablename('vcshop_commission_relation') . ' r' . ' LEFT JOIN ' . tablename('vcshop_member') . ' m ON m.id=r.pid' . ' LEFT JOIN ' . tablename('vcshop_commission_level') . ' l ON l.id=m.agentlevel' . ' WHERE r.id=:id ORDER BY relation_level ASC',array(':id'=>$member['id']));

        // print_r($parents);

        $cardsn = 'VC6476972529826845';
        $cardkey = 'BHATrWXJ';
        $virtualdata = pdo_fetch('select * from '. tablename('vcshop_virtualcard_data') .' where cardsn = :cardsn and cardkey = :cardkey and uniacid = :uniacid limit 1',array(':uniacid' => $_W['uniacid'],':cardkey' => $cardkey,':cardsn' => $cardsn));
        print_r($virtualdata);
    }

    public function info()
    {
        global $_W;
        $openid = 'oHQr7wg7lZ3FlQa-oxCR819ju5Ak';
        $member = $this->model->getInfo($openid, array('total', 'ok', 'apply', 'check', 'lock', 'pay', 'wait', 'fail'));
        print_r($member['commission_ok']);

    }
}

?>
