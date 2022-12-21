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
$params = @json_decode(base64_decode($_GPC['params']), true);
if (empty($params) || !array_key_exists($params['module'], $moduels)) {
    message('访问错误.');
}

$setting = uni_setting($_W['uniacid'], 'payment');
if (empty($setting['payment'])) {
    message('支付方式错误,请联系商家', '', 'error');
}
foreach ($setting['payment'] as &$value) {
    $value['switch'] = $params['module'] == 'recharge' ? $value['recharge_switch'] : $value['pay_switch'];
}
unset($value);
$dos = array();
if (!empty($setting['payment']['credit']['switch'])) {
    $dos[] = 'credit';
}
if (!empty($setting['payment']['alipay']['switch'])) {
    $dos[] = 'alipay';
}
if (!empty($setting['payment']['wechat']['switch'])) {
    $dos[] = 'wechat';
}
if (!empty($setting['payment']['delivery']['switch'])) {
    $dos[] = 'delivery';
}
if (!empty($setting['payment']['unionpay']['switch'])) {
    $dos[] = 'unionpay';
}
if (!empty($setting['payment']['baifubao']['switch'])) {
    $dos[] = 'baifubao';
}
if (!empty($setting['payment']['mix']['switch'])) {
    $dos[] = 'mix';
}
$type = in_array($do, $dos) ? $do : '';

if (empty($type)) {
    message('支付方式错误,请联系商家', '', 'error');
}

if (!empty($type)) {
    //混合支付会有两条paylog记录,所以使用getall
    $log = table(core_paylog)
        ->where(array(
            'uniacid' => $_W['uniacid'],
            'module' => $params['module'],
            'tid' => $params['tid']
        ))
        ->orderby(array('plid' => 'ASC'))
        ->limit(1)
        ->getall();
    $log = empty($log) ? $log : $log[0];

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

    if ($type == 'credit') {
        ignore_user_abort(true);
        $setting = uni_setting($_W['uniacid'], array('creditbehaviors'));
        $credtis = mc_credit_fetch($_W['member']['uid']);
        $log = table('core_paylog')
            ->where(array('plid' => $ps['tid']))
            ->get();
        if ($log['module'] == 'recharge') {
            message('不能使用余额支付', referer(), 'error');
        }
        if (!is_numeric($log['openid'])) {
            $uid = mc_openid2uid($log['openid']);
            if (empty($uid)) {
                $fans_info = mc_init_fans_info($log['openid']);
                $uid = $fans_info['uid'];
            }
            $log['openid'] = $uid;
        }
        //如果是return返回的话，处理相应付款操作
        if (empty($_GPC['notify'])) {
            if (!empty($log) && $log['status'] == '0') {
                if ($credtis[$setting['creditbehaviors']['currency']] < $ps['fee']) {
                    message("余额不足以支付, 需要 {$ps['fee']}, 当前 {$credtis[$setting['creditbehaviors']['currency']]}");
                }
                $fee = floatval($ps['fee']);
                $result = mc_credit_update($_W['member']['uid'], $setting['creditbehaviors']['currency'], -$fee, array($_W['member']['uid'], '消费' . $setting['creditbehaviors']['currency'] . ':' . $fee));
                if (is_error($result)) {
                    message($result['message'], '', 'error');
                }
                table('core_paylog')->where(array('plid' => $log['plid']))->fill(array('status' => '1'))->save();
                if (!empty($_W['openid'])) {
                    if (is_error($is_grant_credit)) {
                        $grant_credit_nums = 0;
                    } else {
                        $grant_credit_nums = $is_grant_credit['message'];
                    }
                    if (!empty($params['grant_credit1_num'])) {
                        $grant_credit_nums += intval($params['grant_credit1_num']);
                    }
                    mc_notice_credit2($_W['openid'], $_W['member']['uid'], $fee, $grant_credit_nums, '线上消费');
                }
                $site = WeUtility::createModuleSite($log['module']);
                if (!is_error($site)) {
                    $site->weid = $_W['weid'];
                    $site->uniacid = $_W['uniacid'];
                    $site->inMobile = true;
                    $method = 'payResult';
                    if (method_exists($site, $method)) {
                        $ret = array();
                        $ret['result'] = 'success';
                        $ret['type'] = $log['type'];
                        $ret['from'] = 'return';
                        $ret['tid'] = $log['tid'];
                        $ret['user'] = $log['openid'];
                        $ret['fee'] = $log['fee'];
                        $ret['weid'] = $log['weid'];
                        $ret['uniacid'] = $log['uniacid'];
                        $ret['acid'] = $log['acid'];
                        //支付成功后新增是否使用优惠券信息【需要模块去处理】
                        $ret['is_usecard'] = $log['is_usecard'];
                        $ret['card_type'] = $log['card_type']; //区分是系统优惠券还是微信卡券
                        $ret['card_fee'] = $log['card_fee'];
                        $ret['card_id'] = $log['card_id'];
                        $notify_url = murl('mc/cash/credit', array('notify' => 'yes', 'params' => safe_gpc_string($_GPC['params']), 'code' => safe_gpc_string($_GPC['code']), 'coupon_id' => intval($_GPC['coupon_id'])), true, true);
                        ihttp_request($notify_url);
                        $site->$method($ret);
                    }
                }
            }
        } else {
            $site = WeUtility::createModuleSite($log['module']);
            if (!is_error($site)) {
                $site->weid = $_W['weid'];
                $site->uniacid = $_W['uniacid'];
                $site->inMobile = true;
                $method = 'payResult';
                if (method_exists($site, $method)) {
                    $ret = array();
                    $ret['result'] = 'success';
                    $ret['type'] = $log['type'];
                    $ret['from'] = 'notify';
                    $ret['tid'] = $log['tid'];
                    $ret['user'] = $log['openid'];
                    $ret['fee'] = $log['fee'];
                    $ret['weid'] = $log['weid'];
                    $ret['uniacid'] = $log['uniacid'];
                    $ret['acid'] = $log['acid'];
                    //支付成功后新增是否使用优惠券信息【需要模块去处理】
                    $ret['is_usecard'] = $log['is_usecard'];
                    $ret['card_type'] = $log['card_type']; //区分是系统优惠券还是微信卡券
                    $ret['card_fee'] = $log['card_fee'];
                    $ret['card_id'] = $log['card_id'];
                    $site->$method($ret);
                }
            }
        }
    }

    if ($type == 'delivery') {
        $log = table('core_paylog')
            ->where(array('plid' => $ps['tid']))
            ->get();
        if (!empty($log) && $log['status'] == '0') {
            $site = WeUtility::createModuleSite($log['module']);

            if (!is_error($site)) {
                $site->weid = $_W['weid'];
                $site->uniacid = $_W['uniacid'];
                $site->inMobile = true;
                $method = 'payResult';
                if (method_exists($site, $method)) {
                    $ret = array();
                    $ret['result'] = 'failed';
                    $ret['type'] = $log['type'];
                    $ret['from'] = 'return';
                    $ret['tid'] = $log['tid'];
                    $ret['user'] = $log['openid'];
                    $ret['fee'] = $log['fee']; //原价
                    $ret['weid'] = $log['weid'];
                    $ret['uniacid'] = $log['uniacid'];
                    //支付成功后新增是否使用优惠券信息【需要模块去处理】
                    $ret['is_usecard'] = $log['is_usecard'];
                    $ret['card_type'] = $log['card_type']; //区分是系统优惠券还是微信卡券
                    $ret['card_fee'] = $log['card_fee'];
                    $ret['card_id'] = $log['card_id'];
                    exit($site->$method($ret));
                }
            }
        }
    }
    if ($type == 'unionpay') {
        $sl = base64_encode(json_encode($ps));
        $auth = sha1($sl . $_W['uniacid'] . $_W['config']['setting']['authkey']);
        header("location: ../payment/unionpay/pay.php?i={$_W['uniacid']}&auth={$auth}&ps={$sl}");
        exit();
    }
    if ($type == 'baifubao') {
        $sl = base64_encode(json_encode($ps));
        $auth = sha1($sl . $_W['uniacid'] . $_W['config']['setting']['authkey']);
        header("location: ../payment/baifubao/pay.php?i={$_W['uniacid']}&auth={$auth}&ps={$sl}");
        exit();
    }
}
