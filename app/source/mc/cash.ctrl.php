<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('activity');
load()->model('module');
load()->model('payment');
load()->func('communication');

if ($do == 'check_password') {
    $password = safe_gpc_string($_GPC['password']);
    $user_info = mc_fetch($_W['member']['uid']);
    $password = md5($password . $user_info['salt']);
    if ($password == $user_info['pay_password']) {
        message(0, '', 'ajax');
    } else {
        message(1, '', 'ajax');
    }
}

$moduels = uni_modules();
$moduels = empty($moduels) ? array() : array_column($moduels, 'name');
$params = @json_decode(base64_decode($_GPC['params']), true);
if (empty($params) || !in_array($params['module'], $moduels)) {
    message('访问错误.');
}

$dos = array('wechat', 'alipay');
$type = in_array($do, $dos) ? $do : '';

if (empty($type)) {
    message('支付方式错误,请联系商家', '', 'error');
}

if (!empty($type)) {
    $log = table('core_paylog')
        ->where(array(
            'uniacid' => $_W['uniacid'],
            'module' => $params['module'],
            'tid' => $params['tid']
        ))
        ->get();

    if (!empty($log) && ($type != 'credit' && !empty($_GPC['notify'])) && $log['status'] != '0') {
        message('这个订单已经支付成功, 不需要重复支付.');
    }

    $update_card_log = array(
        'is_usecard' => '0',
        'card_type' => '0',
        'card_id' => '0',
        'card_fee' => $log['fee'],
        'type' => $type,
    );
    table('core_paylog')
        ->where(array('plid' => $log['plid']))
        ->fill($update_card_log)
        ->save();
    $log['is_usecard'] = '0';
    $log['card_type'] = '0';
    $log['card_id'] = '0';
    $log['card_fee'] = $log['fee'];

    $moduleid = table('modules')->where(array('name' => $params['module']))->getcolumn('mid');
    $moduleid = empty($moduleid) ? '000000' : sprintf("%06d", $moduleid);

    $record = array();
    $record['type'] = $type;
    if (empty($log['uniontid'])) {
        $record['uniontid'] = $log['uniontid'] = date('YmdHis') . $moduleid . random(8, 1);
    }

    if ($type != 'delivery') {
        if ($_GPC['mix_pay']) {
            $has_mix_credit_log = table('core_paylog')
                ->where(array(
                    'uniacid' => $_W['uniacid'],
                    'module' => $params['module'],
                    'tid' => $params['tid'],
                    'type' => 'credit'
                ))
                ->get();
            if ($_GPC['mix_pay'] == 'true' && empty($has_mix_credit_log)) {
                $setting = uni_setting($_W['uniacid'], array('creditbehaviors'));
                $credtis = mc_credit_fetch($_W['member']['uid']);
                if ($credtis[$setting['creditbehaviors']['currency']] > 0 && in_array('mix', $dos) && $credtis[$setting['creditbehaviors']['currency']] < $log['card_fee']) {
                    $mix_credit_log = $log;
                    unset($mix_credit_log['plid']);
                    $mix_credit_log['uniontid'] = date('YmdHis') . $moduleid . random(8, 1);
                    $mix_credit_log['type'] = 'credit';
                    $mix_credit_log['fee'] = $credtis[$setting['creditbehaviors']['currency']];
                    $mix_credit_log['card_fee'] = $credtis[$setting['creditbehaviors']['currency']];
                    table('core_paylog')->fill($mix_credit_log)->save();
                    $mixed_fee = $log['fee'] - $credtis[$setting['creditbehaviors']['currency']];
                }
            } elseif ($_GPC['mix_pay'] == 'false' && $has_mix_credit_log) {
                table('core_paylog')
                    ->where(array('plid' => $has_mix_credit_log['plid']))
                    ->delete();
                $mixed_fee = $log['fee'] + $has_mix_credit_log['fee'];
            }
            if (!empty($mixed_fee)) {
                $record['card_fee'] = $record['fee'] = $log['card_fee'] = $log['fee'] = $mixed_fee;
                $record['uniontid'] = $log['uniontid'] = date('YmdHis') . $moduleid . random(8, 1);
            }
        }
    }
    if (empty($log)) {
        message('系统支付错误, 请稍后重试.');
    } else {
        table('core_paylog')
            ->where(array('plid' => $log['plid']))
            ->fill($record)
            ->save();
        if (!empty($log['uniontid']) && $record['card_fee']) {
            $log['card_fee'] = $record['card_fee'];
            $log['card_id'] = $record['card_id'];
            $log['card_type'] = $record['card_type'];
            $log['is_usecard'] = $record['is_usecard'];
        }
    }
    $ps = array(
        'tid' => $log['plid'],
        'uniontid' => $log['uniontid'],
        'user' => $_W['openid'],
        'fee' => $log['card_fee'],
        'title' => $params['title'],
    );
    if ($type == 'alipay') {
        if (!empty($log['plid'])) {
            table('core_paylog')
                ->where(array('plid' => $log['plid']))
                ->fill(array('openid' => $_W['member']['uid']))
                ->save();
        }
        $ret = alipay_build($ps, $setting['payment']['alipay']);
        if ($ret['url']) {
            echo '<script type="text/javascript" src="../payment/alipay/ap.js"></script><script type="text/javascript">_AP.pay("' . $ret['url'] . '")</script>';
            exit();
        }
    }

    if ($type == 'wechat') {
        if (!empty($log['plid'])) {
            $tag = iunserializer($log['tag']);
            $tag['acid'] = $_W['acid'];
            $tag['uid'] = $_W['member']['uid'];
            table('core_paylog')
                ->where(array('plid' => $log['plid']))
                ->fill(array(
                    'openid' => $_W['openid'],
                    'tag' => iserializer($tag)
                ))
                ->save();
        }
        $ps['title'] = urlencode($params['title']);
        $ps['goods_tag'] = empty($params['goods_tag']) ? '' : $params['goods_tag'];
        $sl = base64_encode(json_encode($ps));
        $auth = sha1($sl . $_W['uniacid'] . $_W['config']['setting']['authkey']);
        $oauth_url = uni_account_oauth_host();
        if (!empty($oauth_url)) {
            $callback = $oauth_url . "payment/wechat/pay.php?i={$_W['uniacid']}&auth={$auth}&ps={$sl}";
        }
        //如果有借用支付，则需要通过网页授权附带用户Openid跳转至支付，否则直接跳转
        $proxy_pay_account = payment_proxy_pay_account();
        if (!is_error($proxy_pay_account)) {
            $forward = $proxy_pay_account->getOauthCodeUrl(urlencode($callback), 'we7sid-' . $_W['session_id']);
            header('Location: ' . $forward);
            exit;
        }
        header("Location: $callback");
        exit();
    }
}
