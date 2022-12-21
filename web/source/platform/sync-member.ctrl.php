<?php
/**
 * 会员同步
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('mc');

$dos = array('display', 'sync', 'del', 'base_information', 'member_credits', 'credit_statistics', 'address', 'export');
$do = in_array($do, $dos) ? $do : 'display';

$module_name = safe_gpc_string($_GPC['module_name']);
$module = $_W['current_module'] = module_fetch($module_name);
define('IN_MODULE', true);
$creditnames = array(
    'credit1' =>
        array (
            'title' => '积分',
            'enabled' => 1,
        ),
    'credit2' =>
        array (
            'title' => '余额',
            'enabled' => 1,
        ),
);

if ('display' == $do) {
    $search_mod = !empty($_GPC['search_mod']) && 1 == intval($_GPC['search_mod']) ? '1' : '2';
    $pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
    $psize = 25;

    $condition = '1';
    $params = array();
    $username = empty($_GPC['username']) ? '' : safe_gpc_string($_GPC['username']);
    if (!empty($username)) {
        if (1 == $search_mod) {
            $condition .= ' AND ((`uid` = :openid) OR (`realname` = :realname) OR (`nickname` = :nickname) OR (`mobile` = :mobile))';
            $params[':realname'] = $params[':nickname'] = $params[':mobile'] = $username;
            if (!is_numeric($username)) {
                $uid = table('mc_mapping_fans')
                    ->where(array('openid' => $username))
                    ->getcolumn('uid');
                $params[':openid'] = empty($uid) ? '' : $uid;
            } else {
                $params[':openid'] = $username;
            }
        } else {
            $condition .= ' AND ((`uid` = :openid) OR (`realname` LIKE :realname) OR (`nickname` LIKE :nickname) OR (`mobile` LIKE :mobile))';
            $params[':realname'] = $params[':nickname'] = $params[':mobile'] = '%' . $username . '%';
            if (!is_numeric($username)) {
                $uid = table('mc_mapping_fans')
                    ->where(array('openid' => $username))
                    ->getcolumn('uid');
                $params[':openid'] = empty($uid) ? '' : $uid;
            } else {
                $params[':openid'] = $username;
            }
        }
    }
    if (!empty($_GPC['datelimit'])) {
        $starttime = strtotime($_GPC['datelimit']['start']);
        if (!empty($starttime)) {
            $endtime = strtotime($_GPC['datelimit']['end']) + 86399;
            $condition .= ' AND createtime > :start AND createtime < :end';
            $params[':start'] = $starttime;
            $params[':end'] = $endtime;
        }
    }
    if (!empty($_GPC['groupid']) && intval($_GPC['groupid']) > 0) {
        $condition .= ' AND `groupid` = :groupid';
        $params[':groupid'] = intval($_GPC['groupid']);
    }
    if (checksubmit('export_submit', true)) {
        $keys = 'realname,nickname,avatar,qq,mobile,vip,gender,birthyear,constellation,zodiac,telephone,idcard,studentid,grade,address,zipcode,nationality,resideprovince,graduateschool,company,education,occupation,position,revenue,affectivestatus,lookingfor,bloodtype,height,weight,alipay,msn,email,taobao,site,bio,interest,credit1,credit2';
        $sql = 'SELECT ' . $keys . ' FROM ' . tablename('mc_members') . ' WHERE uniacid = :uniacid ' . $condition;
        $members = pdo_fetchall($sql, $params);
        if (empty($members)) {
            itoast('暂无会员数据可以导出！', referer(), 'error');
        }
        $available_fields = array (
            'realname' => '真实姓名',
            'nickname' => '昵称',
            'avatar' => '头像',
            'qq' => 'QQ号',
            'mobile' => '手机号码',
            'vip' => 'VIP级别',
            'gender' => '性别',
            'birthyear' => '出生生日',
            'constellation' => '星座',
            'zodiac' => '生肖',
            'telephone' => '固定电话',
            'idcard' => '证件号码',
            'studentid' => '学号',
            'grade' => '班级',
            'address' => '邮寄地址',
            'zipcode' => '邮编',
            'nationality' => '国籍',
            'resideprovince' => '居住地址',
            'graduateschool' => '毕业学校',
            'company' => '公司',
            'education' => '学历',
            'occupation' => '职业',
            'position' => '职位',
            'revenue' => '年收入',
            'affectivestatus' => '情感状态',
            'lookingfor' => ' 交友目的',
            'bloodtype' => '血型',
            'height' => '身高',
            'weight' => '体重',
            'alipay' => '支付宝帐号',
            'msn' => 'MSN',
            'email' => '电子邮箱',
            'taobao' => '阿里旺旺',
            'site' => '主页',
            'bio' => '自我介绍',
            'interest' => '兴趣爱好',
            'credit1' => '积分',
            'credit2' => '余额',
        );
        $html = mc_member_export_parse($members, $available_fields);
        header('Content-type:text/csv');
        header('Content-Disposition:attachment; filename=会员数据.csv');
        echo $html;
        exit();
    }
    $sql = 'SELECT uid, uniacid, groupid, realname, nickname, email, mobile, credit1, credit2, credit6, createtime  FROM ' . tablename('mc_members') . ' WHERE ' . $condition . ' ORDER BY `uid` DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
    $member_list = pdo_fetchall($sql, $params);
    if (!empty($member_list)) {
        foreach ($member_list as &$li) {
            if (empty($li['email']) || (!empty($li['email']) && 'we7.cc' == substr($li['email'], -6) && 39 == strlen($li['email']))) {
                $li['email_effective'] = 0;
            } else {
                $li['email_effective'] = 1;
            }
        }
    }
    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('mc_members') . ' WHERE ' . $condition, $params);
    $pager = pagination($total, $pindex, $psize);
    $stat['total'] = table('mc_members')->getcolumn('COUNT(*)');
    $stat['today'] = table('mc_members')
        ->where(array(
            'createtime >=' => strtotime('today'),
            'createtime <=' => strtotime('today') + 86399
        ))
        ->getcolumn('COUNT(*)');
    $stat['yesterday'] = table('mc_members')
        ->where(array(
            'createtime >=' => strtotime('today') - 86399,
            'createtime <=' => strtotime('today')
        ))
        ->getcolumn('COUNT(*)');
}

if ('sync' == $do) {
    if (empty($_W['setting']['server_setting']['app_id']) || empty($_W['setting']['server_setting']['app_secret'])) {
        iajax(-1, '请先配置3.0多平台接入信息', url('system/base-info'));
    }
    load()->classs('weixin.account');
    $account_api = new WeixinAccount();
    $wechat_fans_list = $account_api->fansAll();
    if (!is_error($wechat_fans_list)) {
        $wechat_fans_count = count($wechat_fans_list['fans']);
        $total_page = ceil($wechat_fans_count / 500);
        for ($i = 0; $i < $total_page; ++$i) {
            $wechat_fans = array_slice($wechat_fans_list['fans'], $i * 500, 500);
            $system_fans = table('mc_members')
                ->where(array('openid' => $wechat_fans))
                ->getall('openid');
            $add_fans_sql = '';
            $params = array(':uniacid' => $_W['uniacid'], ':createtime' => TIMESTAMP);

            foreach ($wechat_fans as $key => $openid) {
                if (empty($system_fans) || empty($system_fans[$openid])) {
                    $params_key_openid = ':' . $key . 'openid';
                    $params_key_slat = ':' . $key . 'salt';
                    $params_key_password = ':' . $key . 'password';
                    $params_key_email = ':' . $key . 'email';
                    $params_key_nickname = ':' . $key . 'nickname';
                    $add_fans_sql .= '(:uniacid, :createtime, ' . $params_key_openid . ', ' . $params_key_slat . ', ' . $params_key_password . ', ' . $params_key_email . ', ' . $params_key_nickname . '),';
                    $params[$params_key_openid] = $openid;
                    $params[$params_key_slat] = random(8);
                    $params[$params_key_password] = md5($openid . $params[$params_key_slat] . $_W['config']['setting']['authkey']);
                    $params[$params_key_email] = md5($openid) . '@we7.cc';
                    $params[$params_key_nickname] = $openid;
                }
            }
            if (!empty($add_fans_sql)) {
                $add_fans_sql = rtrim($add_fans_sql, ',');
                $add_fans_sql = 'INSERT INTO ' . tablename('mc_members') . ' (`uniacid`, `createtime`, `openid`, `salt`, `password`, `email`, `nickname`) VALUES ' . $add_fans_sql;
                $result = pdo_query($add_fans_sql, $params);
            }
        }
        $return['total'] = $wechat_fans_list['total'];
        $return['count'] = !empty($wechat_fans_list['fans']) ? $wechat_fans_count : 0;
        $return['next'] = $wechat_fans_list['next'];
        iajax(0, $return, referer());
    } else {
        iajax(1, $wechat_fans_list['message']);
    }
}

if ('del' == $do) {
    if (is_array($_GPC['uid'])) {
        $delete_uids = array();
        foreach ($_GPC['uid'] as $uid) {
            $uid = intval($uid);
            if (!empty($uid)) {
                $delete_uids[] = $uid;
            }
        }
    } else {
        $delete_uids = intval($_GPC['uid']);
    }
    if (!empty($delete_uids)) {
        $tables = array('mc_members', 'mc_cash_record', 'mc_credits_recharge', 'mc_credits_record', 'mc_member_address');
        foreach ($tables as $key => $value) {
            table($value)->where(array('uniacid' => $_W['uniacid'], 'uid' => $delete_uids))->delete();
        }
        table('mc_mapping_fans')
            ->where(array(
                'uid' => $delete_uids,
                'uniacid' => $_W['uniacid']
            ))
            ->fill(array('uid' => 0))
            ->save();
        itoast('删除成功！', referer(), 'success');
    }
    itoast('请选择要删除的项目！', referer(), 'error');
}

if ('base_information' == $do) {
    $uid = intval($_GPC['uid']);
    $profile = mc_fetch_one($uid, $_W['uniacid']);
    $profile = mc_parse_profile($profile);
    $all_fields = mc_fields();
    $custom_fields = array();
    $base_fields = cache_load(cache_system_key('userbasefields'));
    $base_fields = array_keys($base_fields);
    foreach ($all_fields as $field => $title) {
        if (!in_array($field, $base_fields)) {
            $custom_fields[] = $field;
        }
    }
    $addresses = table('mc_member_address')
        ->where(array(
            'uid' => $uid,
            'uniacid' => $_W['uniacid']
        ))
        ->getall();
    if ($_W['ispost'] && $_W['isajax']) {
        if (!empty($_GPC['type'])) {
            $type = safe_gpc_string($_GPC['type']);
        } else {
            iajax(-1, '参数错误！', '');
        }
        switch ($type) {
            case 'avatar':
                $data = array('avatar' => safe_gpc_url($_GPC['imgsrc']));
                break;
            case 'groupid':
            case 'gender':
            case 'education':
            case 'constellation':
            case 'zodiac':
            case 'bloodtype':
            case 'nickname':
            case 'realname':
            case 'address':
            case 'qq':
            case 'mobile':
            case 'email':
            case 'telephone':
            case 'msn':
            case 'taobao':
            case 'alipay':
            case 'graduateschool':
            case 'grade':
            case 'studentid':
            case 'revenue':
            case 'position':
            case 'occupation':
            case 'company':
            case 'nationality':
            case 'height':
            case 'weight':
            case 'idcard':
            case 'zipcode':
            case 'site':
            case 'affectivestatus':
            case 'lookingfor':
            case 'bio':
            case 'interest':
                $data = array($type => safe_gpc_string($_GPC['request_data']));
                break;
            case 'births':
                $data = array(
                    'birthyear' => safe_gpc_string($_GPC['birthyear']),
                    'birthmonth' => safe_gpc_string($_GPC['birthmonth']),
                    'birthday' => safe_gpc_string($_GPC['birthday']),
                );
                break;
            case 'resides':
                $data = array(
                    'resideprovince' => safe_gpc_string($_GPC['resideprovince']),
                    'residecity' => safe_gpc_string($_GPC['residecity']),
                    'residedist' => safe_gpc_string($_GPC['residedist']),
                );
                break;
            case 'password':
                $password = safe_check_password($_GPC['password']);
                if (is_error($password)) {
                    iajax(-1, $password['mesage']);
                }
                $user = table('mc_members')
                    ->select(array('uid', 'salt'))
                    ->where(array(
                        'uniacid' => $_W['uniacid'],
                        'uid' => $uid
                    ))
                    ->get();
                $data = array();
                if (!empty($user) && $user['uid'] == $uid) {
                    if (empty($user['salt'])) {
                        $user['salt'] = $salt = random(8);
                        table('mc_members')
                            ->where(array(
                                'uid' => $uid,
                                'uniacid' => $_W['uniacid']
                            ))
                            ->fill(array('salt' => $salt))
                            ->save();
                    }
                    $password = md5($password . $user['salt'] . $_W['config']['setting']['authkey']);
                    $data = array('password' => $password);
                }
                break;
            default:
                //其它信息
                $data = array($type => safe_gpc_string($_GPC['request_data']));
                break;
        }
        $result = mc_update($uid, $data);
        if ($result) {
            iajax(0, '修改成功！', '');
        } else {
            iajax(1, '修改失败！', '');
        }
    }
}

if ('member_credits' == $do) {
    $uid = intval($_GPC['uid']);
    $credits = mc_credit_fetch($uid, array('credit1', 'credit2'));
    //积分或余额记录
    $type = !empty($_GPC['type']) ? safe_gpc_string($_GPC['type']) : 'credit1';
    $pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
    $psize = 50;
    $mc_credits_record = table('mc_credits_record');
    $mc_credits_record->searchWithUniacid($_W['uniacid']);
    $mc_credits_record->searchWithPage($pindex, $psize);
    $records = $mc_credits_record->getCreditsRecordListByUidAndCredittype($uid, $type);
    $total = $mc_credits_record->getLastQueryTotal();

    $pager = pagination($total, $pindex, $psize);
}

if ('credit_statistics' == $do) {
    $uid = intval($_GPC['uid']);
    $credits = array(
        'credit1' => $creditnames['credit1']['title'],
        'credit2' => $creditnames['credit2']['title'],
    );
    if (!empty($_GPC['datelimit'])) {
        $starttime = strtotime($_GPC['datelimit']['start']);
        $endtime = strtotime($_GPC['datelimit']['end']) + 86399;
        $time_where = array(
            'createtime >' => $starttime,
            'createtime <' => $endtime,
        );
    }
    if (!empty($credits)) {
        $data = array();
        foreach ($credits as $key => $li) {
            $mc_credits_record_add = table('mc_credits_record')
                ->where(array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $uid,
                    'credittype' => $key,
                    'num >' => 0,
                ));

            $mc_credits_record_del = table('mc_credits_record')
                ->where(array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $uid,
                    'credittype' => $key,
                    'num <' => 0
                ));
            if (!empty($time_where)) {
                $mc_credits_record_add->where($time_where);
                $mc_credits_record_del->where($time_where);
            }
            $data[$key]['add'] = round($mc_credits_record_add->getcolumn('SUM(num)'), 2);
            $data[$key]['del'] = abs(round($mc_credits_record_del->getcolumn('SUM(num)'), 2));
            $data[$key]['end'] = $data[$key]['add'] - $data[$key]['del'];
        }
    }
}

if ('address' == $do) {
    $uid = intval($_GPC['uid']);
    if ($_W['ispost'] && $_W['isajax']) {
        $op = safe_gpc_string($_GPC['op']);
        if ('addaddress' === $op || 'editaddress' === $op) {
            $post = array(
                'uniacid' => $_W['uniacid'],
                'province' => safe_gpc_string($_GPC['province']),
                'city' => safe_gpc_string($_GPC['city']),
                'district' => safe_gpc_string($_GPC['district']),
                'address' => safe_gpc_string($_GPC['detail']),
                'uid' => intval($_GPC['uid']),
                'username' => safe_gpc_string($_GPC['name']),
                'mobile' => safe_gpc_string($_GPC['phone']),
                'zipcode' => safe_gpc_string($_GPC['code']),
            );
            if ('addaddress' === $op) {
                $exist_address = table('mc_member_address')
                    ->where(array(
                        'uniacid' => $post['uniacid'],
                        'uid' => $uid
                    ))
                    ->getcolumn('COUNT(*)');
                if (!$exist_address) {
                    $post['isdefault'] = 1;
                }
                if (table('mc_member_address')->fill($post)->save()) {
                    $post['id'] = pdo_insertid();
                    iajax(0, $post, '');
                } else {
                    iajax(1, '收货地址添加失败', '');
                }
            } else {
                $post['id'] = intval($_GPC['id']);
                $result = table('mc_member_address')
                    ->where(array(
                        'id' => intval($_GPC['id']),
                        'uniacid' => $_W['uniacid']
                    ))
                    ->fill($post)
                    ->save();
                if ($result) {
                    iajax(0, $post, '');
                } else {
                    iajax(1, '收货地址修改失败', '');
                }
            }
        }
        if ('deladdress' === $op) {
            $id = intval($_GPC['id']);
            if (table('mc_member_address')
                ->where(array(
                    'id' => $id,
                    'uniacid' => $_W['uniacid']
                ))
                ->delete()) {
                iajax(0, '删除成功', '');
            } else {
                iajax(1, '删除失败', '');
            }
        }
        if ('isdefault' === $op) {
            $id = intval($_GPC['id']);
            $uid = intval($_GPC['uid']);
            table('mc_member_address')
                ->where(array(
                    'uid' => $uid,
                    'uniacid' => $_W['uniacid']
                ))
                ->fill(array('isdefault' => 0))
                ->save();
            table('mc_member_address')
                ->where(array(
                    'id' => $id,
                    'uniacid' => $_W['uniacid']
                ))
                ->fill(array('isdefault' => 1))
                ->save();
            iajax(0, '设置成功', '');
        }
    }
}

if ('export' == $do) {
    $uid = intval($_GPC['uid']);
    $type = safe_gpc_string($_GPC['type']) ? safe_gpc_string($_GPC['type']) : 'credit1';
    if (empty($uid)) {
        iajax('-1', '参数错误，请刷新后重试！');
    }
    //导出数据
    $available_fields = [
        'credittype' => '账户类型',
        'username' => '操作员',
        'num' => '积分增减',
        'module' => '模块',
        'createtime' => '操作时间',
        'remark' => '备注',
    ];
    $params = [
        ':uniacid' => $_W['uniacid'],
        ':uid' => $uid,
    ];
    $condition = '';
    if (!empty($type)) {
        $condition .= 'AND r.credittype = :credittype ';
        $params[':credittype'] = $type;
    }
    $sql = 'SELECT r.credittype, u.username, r.num, r.module, r.createtime, r.remark FROM ' . tablename('mc_credits_record') . ' r LEFT JOIN ' . tablename('users') . ' u ON r.operator = u.uid  WHERE r.uniacid = :uniacid AND r.uid = :uid ' . $condition . 'ORDER BY r.id desc';
    $credittype_data = pdo_fetchall($sql, $params);
    $html = mc_member_export_parse($credittype_data, $available_fields, 'creditinfo');
    header('Content-type:text/csv');
    header('Content-Disposition:attachment; filename=会员账户数据.csv');
    echo $html;
    exit();
}

template('platform/sync-member');
