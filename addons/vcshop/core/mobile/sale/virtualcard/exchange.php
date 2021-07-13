<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
class Exchange_VcShopPage extends MobileLoginPage
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
        $openid = $_W["openid"];
        $cardsn = '';
        $cardkey = '';
        // if (isset($_GPC['id'])) {
        //     $virtualdata = pdo_fetch('select * from '. tablename('vcshop_virtualcard_data') .' where id = :id and uniacid = :uniacid limit 1',array(':uniacid' => $_W['uniacid'],':id' => $_GPC['id']));
        //     $cardsn = $virtualdata['cardsn'];
        //     $cardkey = $virtualdata['cardkey'];
        // }
        include $this->template();
    }

    public function submit()
    {
        global $_W;
        global $_GPC;
        $openid = $_W["openid"];
        $member = m('member')->getMember($openid);

        $cardsn = trim($_GPC['cardsn']);
        $cardkey = trim($_GPC['cardkey']);

        $virtualdata = pdo_fetch('select * from '. tablename('vcshop_virtualcard_data') .' where cardsn = :cardsn and cardkey = :cardkey and uniacid = :uniacid limit 1',array(':uniacid' => $_W['uniacid'],':cardkey' => $cardkey,':cardsn' => $cardsn));

        if (empty($virtualdata)) {
            show_json(0, '输入兑换信息有误！');
        }

        if ($virtualdata['used'] == 1) {
            show_json(0, '此卡已兑换！');
        }

        $virtualcard = com('virtualcard')->getVirtualcard($virtualdata['virtualcardid']);
        // print_r($virtualdata);

        if (empty($virtualcard) || $virtualdata['status'] == 0 || $virtualdata['status'] == 2) {
            show_json(0, '此卡已失效！');
        }

        $nowtime = time();
        $time = time();
        $starttime = $virtualcard['timestart'];
        $endtime = $virtualcard['timeend'];
        if ($nowtime < $starttime) {
            show_json(0, '此卡暂时无法兑换！');
        }
        if ($nowtime > $endtime) {
            show_json(0, '此卡已过期！');
        }

        $agent = $virtualdata['agentid'];
        $credit1 = $virtualdata['sendcredit1'];

        // 成为上下级关系
        if (!empty($agent)) {
            $parent = m('member')->getMember($agent);
            $parent_is_agent = !empty($parent) && $parent['isagent'] == 1 && $parent['status'] == 1;
            if ($parent_is_agent && empty($member['agentid']) && empty($member['agentlevel'])) {
                if ($member['id'] != $parent['id']) {
                    if (empty($member['fixagentid'])) {
                        $authorid = empty($parent['isauthor']) ? $parent['authorid'] : $parent['id'];
                        $author   = p('author');
                        if ($author) {
                            $author->upgradeLevelByAgent($parent['id']);
                            pdo_update('vcshop_member', array(
                                'agentid' => $parent['id'],
                                'childtime' => $time,
                                'authorid' => $authorid
                            ), array(
                                'uniacid' => $_W['uniacid'],
                                'id' => $member['id']
                            ));
                        } else {
                            pdo_update('vcshop_member', array(
                                'agentid' => $parent['id'],
                                'childtime' => $time
                            ), array(
                                'uniacid' => $_W['uniacid'],
                                'id' => $member['id']
                            ));
                        }
                        if (p('dividend')) {
                            p('commission')->saveRelation($member['id'], $parent['id'], 1);
                            p('dividend')->update_headsid($member['id'], $parent['id']);
                        }
                        if ($author) {
                            $author_set = $author->getSet();
                            if (!empty($author_set['become']) && ($author_set['become'] == '2' || $author_set['become'] == '5')) {
                                $can_author       = false;
                                $getAgentsDownNum = p('commission')->getAgentsDownNum($parent['openid']);
                                if ($author_set['become'] == '2') {
                                    if ($author_set['become_down1'] <= $getAgentsDownNum['level1']) {
                                        $can_author = true;
                                    } else if ($author_set['become_down2'] <= $getAgentsDownNum['level2']) {
                                        $can_author = true;
                                    } else {
                                        if ($author_set['become_down3'] <= $getAgentsDownNum['level3']) {
                                            $can_author = true;
                                        }
                                    }
                                } else if ($author_set['become'] == '5') {
                                    if ($author_set['become_downcount'] <= $getAgentsDownNum['total']) {
                                        $can_author = true;
                                    }
                                } else {
                                    if ($author_set['become'] == '6') {
                                        $temp_parent = $parent['id'];
                                        do {
                                            $res         = $author->becomeType6($temp_parent);
                                            $temp_parent = $res['agentid'];
                                        } while ($res['agentid'] != 0);
                                    }
                                }
                                if ($can_author) {
                                    $become_check = intval($author_set['become_check']);
                                    if (empty($member['authorblack'])) {
                                        pdo_update('vcshop_member', array(
                                            'authorstatus' => $become_check,
                                            'isauthor' => 1,
                                            'authortime' => $time
                                        ), array(
                                            'uniacid' => $_W['uniacid'],
                                            'id' => $parent['id']
                                        ));
                                        if ($become_check == 1) {
                                            p('commission')->sendMessage($parent['openid'], array(
                                                'nickname' => $parent['nickname'],
                                                'authortime' => $time
                                            ), TM_AUTHOR_BECOME);
                                        }
                                    }
                                }
                            }
                        }
                        p('commission')->sendMessage($parent['openid'], array(
                            'nickname' => $member['nickname'],
                            'childtime' => $time,
                            'openid' => $member['openid']
                        ), TM_COMMISSION_AGENT_NEW);
                        p('commission')->upgradeLevelByAgent($parent['id']);
                        if (p('globonus')) {
                            p('globonus')->upgradeLevelByAgent($parent['id']);
                        }
                        if (p('abonus')) {
                            p('abonus')->upgradeLevelByAgent($parent['id']);
                        }
                        if (p('author')) {
                            p('author')->upgradeLevelByAgent($parent['id']);
                        }
                    }
                }
            }
        }

        $oldmoney = m('member')->getCredit($member['openid'], 'credit1');
        $newcredit = $credit1 + $oldmoney;
        m('member')->setCredit($openid, 'credit1', $credit1, '虚拟卡券兑换送积分 ' . $credit1);
        if ($virtualdata['is_alltime'] == 1) {
            $operator = 5;
        }else{
            $operator = 6;
        }
        $data = array(
            'uid' => $member['uid'],
            'openid' => $member['openid'],
            'uniacid' => $member['uniacid'],
            'credittype' => 'credit1',
            'num' => $credit1,
            'operator' => $operator,
            'createtime' => time(),
            'remark' => '兑换卡'.$virtualdata['id'].'每月赠送积分 OPENID: ' . $member['openid'] . ' 剩余: '.$newcredit,
            'module' => 'vcshop',
            'presentcredit' => $newcredit
        );
        pdo_insert('vcshop_member_credit_send_record',$data);
        
        pdo_insert('vcshop_virtualcard_send_log',array('uniacid'=>$_W['uniacid'],'cardid'=>$virtualdata['virtualcardid'],'dataid'=>$virtualdata['id'],'userid'=>$member['id'],'sendtime'=>time(),'status'=>'1'));

        pdo_update('vcshop_virtualcard_data',array('openid' => $openid, 'used' => 1, 'usetime' => time(), 'gettime' => time(), 'userid' => $member['id'],'sendstatus' => 1),array('id'=>$virtualdata['id']));

        // if ($virtualdata['is_alltime'] == 0) {
            $sendcount = $virtualdata['sendmonth'];
            $totalcount = pdo_fetchcolumn('select ifnull(count(*),0) from ' . tablename('vcshop_virtualcard_send_log') . ' where uniacid = :uniacid and cardid = :cardid and dataid = :dataid and userid = :userid' ,array(':uniacid' => $_W['uniacid'],':cardid' => $virtualdata['virtualcardid'],'dataid' => $virtualdata['id'], 'userid' => $member['id']));
            if ($totalcount >= $sendcount) {
                pdo_update('vcshop_virtualcard_data',array('sendstatus'=>2),array('id'=>$virtualdata['id']));
            }
        // }
        m('notice')->sendMemberPointChange($_W['openid'],$credit1,0,3);
        show_json(1, '兑换成功！');
    }

    public function send()
    {   
        global $_W;
        global $_GPC;
        print_r(111);
        m('notice')->sendMemberPointChange($_W['openid'],1,0,3);
    }
}

?>