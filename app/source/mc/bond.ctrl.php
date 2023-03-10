<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->func('tpl');
load()->model('user');

$dos = array('display', 'credits', 'address', 'card', 'mycard', 'record',
            'mobile', 'email', 'card_qrcode',
            'addressadd', 'settings', 'password', 'aboutus', 'binding_account', 'pay_password');
$do = in_array($do, $dos) ? $do : 'display';
$profile = mc_fetch($_W['member']['uid']);

if ($do == 'pay_password') {
    $user_info = mc_fetch($_W['member']['uid']);
    $pay_password = $user_info['pay_password'];
    if ($_W['isajax'] && $_W['ispost']) {
        $password = safe_check_password($_GPC['pay_password']);
        $repeat_password = safe_check_password($_GPC['repeat_pay_password']);
        if (!empty($_GPC['pay_password_open']) && $_GPC['pay_password_open'] == 'on') {
            if (is_error($password)) {
                message($password['message'], '', 'error');
            }
            if (empty($password) || empty($repeat_password)) {
                message('请输入支付密码', '', 'error');
            }
            if ($password != $repeat_password) {
                message('两次输入的密码不一致', '', 'error');
            }
            if (strlen($password) < 6) {
                message('密码最小长度为6位', '', 'error');
            }
            $password = md5($password . $user_info['salt']);
            mc_update($_W['member']['uid'], array('pay_password' => $password));
            message('设置成功', url('mc/bond/pay_password'));
        } else {
            mc_update($_W['member']['uid'], array('pay_password' => ''));
            message('已关闭支付密码', url('mc/bond/pay_password'));
        }
    }
}

/*积分记录*/
if ($do == 'credits') {
    $pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
    $psize = 15;
    /*默认显示时间*/
    $period = intval($_GPC['period']);
    if ($period == '1') {
        $starttime = date('Ym01', strtotime(0));
        $endtime = date('Ymd His', time());
    } elseif ($period == '0') {
        $starttime = date('Ym01', strtotime(1 * $period . "month"));
        $endtime = date('Ymd', strtotime("$starttime + 1 month - 1 day"));
    } else {
        $starttime = date('Ym01', strtotime(1 * $period . "month"));
        $endtime = date('Ymd', strtotime("$starttime + 1 month - 1 day"));
    }
    $where = array(
        'uid' => $_W['member']['uid'],
        'createtime >=' => strtotime($starttime),
        'createtime <' => strtotime($endtime)
    );
    /*获取用户名字和头像*/
    $user = table('mc_members')
        ->select(array('realname', 'avatar'))
        ->where(array('uid' => $_W['member']['uid']))
        ->get();
    if ($_GPC['credittype']) {
        /*获取充值订单记录*/
        if ($_GPC['type'] == 'order') {
            $orders = table('mc_credits_recharge')
                ->where($where)
                ->limit(($pindex - 1) * $psize, $psize)
                ->getall();
            foreach ($orders as &$value) {
                $value['createtime'] = date('Y-m-d', $value['createtime']);
                $value['fee'] = number_format($value['fee'], 2);
                if ($value['status'] == 1) {
                    $orderspay += $value['fee'];
                }
                unset($value);
            }
            /*订单数据分页*/
            $total = table('mc_credits_recharge')->where($where)->getcolumn('COUNT(*)');
            $orderpager = pagination($total, $pindex, $psize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
            template('mc/bond');
            exit();
        }
        $where['credittype'] = safe_gpc_string($_GPC['credittype']);
    }

    /*获取总支出收入情况*/
    $nums = table('mc_credits_record')->select('num')->where($where)->getall();
    $pay = $income = 0;
    foreach ($nums as $value) {
        if ($value['num'] > 0) {
            $income += $value['num'];
        } else {
            $pay += abs($value['num']);
        }
    }
    if ($_GPC['credittype'] == 'credit2') {
        $pay = number_format($pay, 2);
        $income = number_format($income, 2);
    }
    /*获取交易记录*/
    $data = table('mc_credits_record')
        ->where($where)
        ->orderby(array('createtime' => 'DESC'))
        ->limit(($pindex - 1) * $psize, $psize)
        ->getall();
    foreach ($data as $key => $value) {
        $data[$key]['credittype'] = $creditnames[$data[$key]['credittype']]['title'];
        $data[$key]['createtime'] = date('Y-m-d H:i', $data[$key]['createtime']);
        $data[$key]['num'] = number_format($value['num'], 2);
        if ($data[$key]['num'] < 0) {
            $data[$key]['color'] = "#000";
        } else {
            $data[$key]['color'] = "#04be02";
            $data[$key]['num'] = "+" . $data[$key]['num'];
        }
        $data[$key]['remark'] = str_replace('credit1', '积分', $data[$key]['remark']);
        $data[$key]['remark'] = str_replace('credit2', '余额', $data[$key]['remark']);
        $data[$key]['remark'] = empty($data[$key]['remark']) ? '未记录' : $data[$key]['remark'];
    }
    /*数据分页*/
    $total = table('mc_credits_record')->where($where)->getcolumn('COUNT(*)');
    $pager = pagination($total, $pindex, $psize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
    $pagenums = ceil($total / $psize);
    if ($_W['isajax'] && $_W['ispost']) {
        if (!empty($data)) {
            exit(json_encode($data));
        } else {
            exit(json_encode(array('state' => 'error')));
        }
    }
    $type = safe_gpc_string($_GPC['type']);
    if ($type == 'recorddetail') {
        $id = intval($_GPC['id']);
        $credittype = safe_gpc_string($_GPC['credittype']);
        $data = table('mc_credits_record')
            ->searchWithUsers()
            ->select(array('r.*', 'u.username'))
            ->where(array(
                'r.id' => $id,
                'r.uniacid' => $_W['uniacid'],
                'r.credittype' => $credittype
            ))
            ->orderby(array('r.id' => 'DESC'))
            ->limit(($pindex - 1) * $psize, $psize)
            ->get();

        if ($data['credittype'] == 'credit2') {
            $data['credittype'] = '余额';
        } elseif ($data['credittype'] == 'credit1') {
            $data['credittype'] = '积分';
        }
        $data['remark'] = str_replace('credit1', '积分', $data['remark']);
        $data['remark'] = str_replace('credit2', '余额', $data['remark']);
        $data['remark'] = empty($data['remark']) ? '暂无记录' : $data['remark'];
    }
}

if ($do == 'record') {
    $setting = table('mc_card')
        ->where(array('uniacid' => $_W['uniacid']))
        ->select(array('nums_text', 'times_text'))
        ->get();
    $card = table('mc_card_members')
        ->where(array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid']
        ))
        ->get();
    $type = safe_gpc_string($_GPC['type']);
    $where = array(
        'uniacid' => $_W['uniacid'],
        'uid' => $_W['member']['uid'],
        'type' => $type,
    );
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $total = table('mc_card_record')
        ->where($where)
        ->getcolumn('COUNT(*)');

    $data = table('mc_card_record')
        ->where($where)
        ->orderby(array('id' => 'DESC'))
        ->limit(($pindex - 1) * $psize, $psize)
        ->getall();
    $pager = pagination($total, $pindex, $psize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
}

if ($do == 'mobile') {
    $reregister = false;
    if ($_W['member']['email'] == md5($_W['openid']) . '@we7.cc') {
        $reregister = true;
        message('请先完善账号信息', url('mc/bond/binding_account', array('type' => '1')), 'error');
    }
    $op = !empty($_GPC['op']) ? safe_gpc_string($_GPC['op']) : 'index';
    $mobile_exist = empty($profile['mobile']) ? 0 : 1;
    if ($_W['ispost'] && $_W['isajax']) {
        $code = safe_gpc_string($_GPC['code']);
        $mobile = safe_gpc_string($_GPC['mobile']);
        $password = safe_gpc_string($_GPC['password']);
        $repassword = safe_gpc_string($_GPC['repassword']);
        load()->model('utility');
        if (!preg_match(REGULAR_MOBILE, $mobile)) {
            message(error(-1, '格式错误'), '', 'ajax');
        }
        if (!code_verify($_W['uniacid'], $mobile, $code)) {
            table('uni_verifycode')->where(array('receiver' => $username))->delete();
            message(error(-1, '验证码错误'), '', 'ajax');
        } else {
        }
        if (empty($mobile)) {
            message(error(-1, '请填写手机号'), '', 'ajax');
        }
        if (!empty($reregister)) {
            if (empty($password) || empty($repassword)) {
                message(error(-1, '请填写密码'), '', 'ajax');
            }
            if ($password !== $repassword) {
                message(error(-1, '密码不一致'), '', 'ajax');
            }
        }
        $is_exist = table('mc_members')
            ->select('uid')
            ->where(array(
                'uniacid' => $_W['uniacid'],
                'mobile' => $mobile,
                'uid' => $_W['member']['uid']
            ))
            ->get();
        if (!empty($is_exist)) {
            message(error(-1, '手机号已被绑定'), '', 'ajax');
        } else {
            $password = md5($password . $profile['salt'] . $_W['config']['setting']['authkey']);
            if (!empty($reregister)) {
                mc_update($_W['member']['uid'], array('mobile' => $mobile, 'email' => '', 'password' => $password));
            } else {
                mc_update($_W['member']['uid'], array('mobile' => $mobile));
            }
            message(error(0, '绑定成功'), url('mc/bond/mobile'), 'ajax');
        }
    }
}

if ($do == 'password') {
    $reregister = false;
    if ($_W['member']['email'] == md5($_W['openid']) . '@we7.cc') {
        $reregister = true;
        message('请先完善账号信息', url('mc/bond/binding_account', array('type' => '1')), 'error');
    }
    if ($_W['isajax'] && $_W['ispost']) {
        if (empty($reregister) && !empty($profile['password'])) {
            $oldpassword = safe_gpc_string($_GPC['oldpassword']);
            $oldpassword = md5($oldpassword . $profile['salt'] . $_W['config']['setting']['authkey']);
            $correct = table('mc_members')
                ->where(array(
                    'uid' => $_W['member']['uid'],
                    'password' => $oldpassword
                ))
                ->select('uid')
                ->get();
            if (empty($correct)) {
                message('旧密码不正确', '', 'error');
            }
        }
        $password = safe_gpc_string($_GPC['password']);
        if (empty($password) || strlen($password) < 6) {
            message('密码不能少于6位', '', 'error');
        }
        $repassword = safe_gpc_string($_GPC['repassword']);
        if ($password != $repassword) {
            message('两次输入密码不一致', '', 'error');
        }
        $password = md5($password . $profile['salt'] . $_W['config']['setting']['authkey']);
        mc_update($_W['member']['uid'], array('password' => $password));
        message('设置密码成功', url('mc/bond/settings'), 'success');
    }
}

if ($do == 'email') {
    $reregister = false;
    if ($_W['member']['email'] == md5($_W['openid']) . '@we7.cc') {
        $reregister = true;
        message('请先完善账号信息', url('mc/bond/binding_account', array('type' => '1')), 'error');
    }
    if ($_W['isajax'] && $_W['ispost']) {
        $data = array();
        if (empty($_GPC['email'])) {
            message('请输入您的邮箱', '', 'error');
        }
        $data['email'] = safe_gpc_string($_GPC['email']);
        $emailexists = table('mc_members')
            ->where(array(
                'email' => $data['email'],
                'uniacid' => $_W['uniacid'],
                'uid <>' => $_W['member']['uid']
            ))
            ->select('uid')
            ->get();
        if (!empty($emailexists['uid'])) {
            message('抱歉，该E-Mail地址已经被注册，请更换。', '', 'error');
        }
        mc_update($profile['uid'], $data);
        message('邮箱绑定成功', url('mc/home'), 'success');
    }
}
if ($do == 'settings') {
    $reregister = false;
    if ($_W['member']['email'] == md5($_W['openid']) . '@we7.cc') {
        $reregister = true;
    }
    $profile_hide = mc_card_settings_hide();
    $item = 'email';
    $ltype = empty($setting['passport']['type']) ? 'hybird' : $setting['passport']['type'];
}
if ($do == 'binding_account') {
    $type = intval($_GPC['type']);
    $reregister = false;
    if ($_W['member']['email'] == md5($_W['openid']) . '@we7.cc') {
        $reregister = true;
    }
    $item = 'email';
    if ($_W['isajax'] && $_W['ispost']) {
        $username = safe_gpc_string($_GPC['username']);
        $password = safe_gpc_string($_GPC['password']);
        $data = array();
        if (empty($_GPC['username'])) {
            message('请输入您的账号', '', 'error');
        }
        if (empty($_GPC['password'])) {
            message('请输入您的密码', '', 'error');
        }
        //根据后台设置的参数进行判断
        if ($item == 'email') {
            if (preg_match(REGULAR_EMAIL, $username)) {
                $data['email'] = $username;
            } else {
                message('邮箱格式不正确', referer(), 'error');
            }
        } elseif ($item == 'mobile') {
            if (preg_match(REGULAR_MOBILE, $username)) {
                $data['mobile'] = $username;
            } else {
                message('手机号格式不正确', referer(), 'error');
            }
        } else {
            if (preg_match(REGULAR_MOBILE, $username)) {
                $data['mobile'] = $username;
            } elseif (preg_match(REGULAR_EMAIL, $username)) {
                $data['email'] = $username;
            } else {
                message('用户名格式错误', referer(), 'error');
            }
        }
        if ($type == '1') {
            if (!empty($data['email'])) {
                $userexists = table('mc_members')
                    ->where(array(
                        'email' => $data['email'],
                        'uniacid' => $_W['uniacid'],
                        'uid <>' => $_W['member']['uid']
                    ))
                    ->select('uid')
                    ->get();
            } elseif (!empty($data['mobile'])) {
                $userexists = table('mc_members')
                    ->where(array(
                        'mobile' => $data['mobile'],
                        'uniacid' => $_W['uniacid'],
                        'uid <>' => $_W['member']['uid']
                    ))
                    ->select('uid')
                    ->get();
                $data['email'] = '';
            }

            if (!empty($userexists['uid'])) {
                message('抱歉，该账号已经被注册，请更换。', '', 'error');
            }
            $hash = md5($password . $profile['salt'] . $_W['config']['setting']['authkey']);
            $data['password'] = $hash;
            mc_update($profile['uid'], $data);
            message('账号绑定成功', url('mc/home'), 'success');
        } else {
            if (!empty($data['mobile'])) {
                $member = table('mc_members')
                    ->select(array('uid', 'salt', 'password'))
                    ->where(array(
                        'uniacid' => $_W['uniacid'],
                        'mobile' => $data['mobile']
                    ))
                    ->get();
            } else {
                $member = table('mc_members')
                    ->select(array('uid', 'salt', 'password'))
                    ->where(array(
                        'uniacid' => $_W['uniacid'],
                        'email' => $data['email']
                    ))
                    ->get();
            }
            if (!empty($reregister)) {
                if (empty($member)) {
                    message('绑定已有账号失败', '', 'error');
                }
                $hash = md5($_GPC['password'] . $member['salt'] . $_W['config']['setting']['authkey']);
                if ($member['password'] != $hash) {
                    message('绑定已有账号失败', '', 'error');
                }
                table('mc_mapping_fans')
                    ->where(array(
                        'uniacid' => $_W['uniacid'],
                        'openid' => $_W['openid'],
                    ))
                    ->fill(array('uid' => $member['uid']))
                    ->save();

                //删除之前的帐号信息，转称资料，积分数据
                $member_old = mc_fetch($_W['member']['uid']);
                $member_new = mc_fetch($member['uid']);
                if (!empty($member_old) && !empty($member_new)) {
                    $ignore = array('email', 'password', 'uid', 'uniacid', 'salt', 'credit1', 'credit2', 'credit3', 'credit4', 'credit5');
                    $profile_update = array();
                    foreach ($member_old as $key => $value) {
                        if (!in_array($key, $ignore)) {
                            if (empty($member_new[$key]) && !empty($member_old[$key])) {
                                $profile_update[$key] = $member_old[$key];
                            }
                        }
                    }
                    $profile_update['credit1'] = $member_old['credit1'] + $member_new['credit1'];
                    $profile_update['credit2'] = $member_old['credit2'] + $member_new['credit2'];
                    $profile_update['credit3'] = $member_old['credit3'] + $member_new['credit3'];
                    $profile_update['credit4'] = $member_old['credit4'] + $member_new['credit4'];
                    $profile_update['credit5'] = $member_old['credit5'] + $member_new['credit5'];
                    table('mc_members')
                        ->where(array(
                            'uid' => $member['uid'],
                            'uniacid' => $_W['uniacid']
                        ))
                        ->fill($profile_update)
                        ->save();
                    cache_build_memberinfo($member['uid']);
                    table('mc_members')
                        ->where(array(
                            'uid' => $_W['member']['uid'],
                            'uniacid' => $_W['uniacid']
                        ))
                        ->delete();
                    //转换各种券的信息
                    pdo_update('coupon_record', array('uid' => $member['uid']), array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
                    pdo_update('activity_exchange_trades', array('uid' => $member['uid']), array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
                    pdo_update('activity_exchange_trades_shipping', array('uid' => $member['uid']), array('uid' => $_W['member']['uid'], 'uniacid' => $_W['uniacid']));
                    //积分变更日志信息
                    table('mc_credits_record')
                        ->where(array(
                            'uid' => $_W['member']['uid'],
                            'uniacid' => $_W['uniacid']
                        ))
                        ->fill(array('uid' => $member['uid']))
                        ->save();
                    table('mc_card_members')
                        ->where(array(
                            'uid' => $_W['member']['uid'],
                            'uniacid' => $_W['uniacid']
                        ))
                        ->fill(array('uid' => $member['uid']))
                        ->save();
                }
                message('绑定已有账号成功', url('mc/home'), 'success');
            }
        }
    }
}

template('mc/bond');
