<?php

/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */

defined('IN_IA') or exit('Access Denied');

define('ALIPAY_GATEWAY', 'https://mapi.alipay.com/gateway.do');

function alipay_build($params, $alipay = array()) {
    global $_W;
    $tid = $params['uniontid'];
    $set = array();
    $set['service'] = 'alipay.wap.create.direct.pay.by.user';
    $set['partner'] = $alipay['partner'];
    $set['_input_charset'] = 'utf-8';
    $set['sign_type'] = 'MD5';
    $set['notify_url'] = $_W['siteroot'] . 'payment/alipay/notify.php';
    $set['return_url'] = $_W['siteroot'] . 'payment/alipay/return.php';
    $set['out_trade_no'] = $tid;
    $set['subject'] = $params['title'];
    $set['total_fee'] = $params['fee'];
    $set['seller_id'] = $alipay['account'];
    $set['payment_type'] = 1;
    $set['body'] = $_W['uniacid'];
    if ($params['service'] == 'create_direct_pay_by_user') {
        $set['service'] = 'create_direct_pay_by_user';
        $set['seller_id'] = $alipay['partner'];
    } else {
        $set['app_pay'] = 'Y';
    }
    $prepares = array();
    foreach ($set as $key => $value) {
        if ($key != 'sign' && $key != 'sign_type') {
            $prepares[] = "{$key}={$value}";
        }
    }
    sort($prepares);
    $string = implode('&', $prepares);
    $string .= $alipay['secret'];
    $set['sign'] = md5($string);

    $response = ihttp_request(ALIPAY_GATEWAY . '?' . http_build_query($set, '', '&'), array(), array('CURLOPT_FOLLOWLOCATION' => 0));
    if (empty($response['headers']['Location']) && empty($_W['isajax'])) {
        exit(iconv('gbk', 'utf-8', $response['content']));
        return;
    }
    return array('url' => $response['headers']['Location']);
}

function wechat_build($params) {
    global $_W;
    if (empty($params['uniontid']) || empty($params['fee']) || empty($params['user'])) {
        return error(-1, '参数错误！');
    }
    load()->library('sdk-module');
    $account_type = empty($params['account_type']) ? 1 : $params['account_type'];
    $api = new \W7\Sdk\Module\Api(getenv('APP_ID'), getenv('APP_SECRET'), $_W['setting']['server_setting']['app_id'], $account_type, V3_API_DOMAIN);
    $pay = $api->wechatPay($_W['siteroot'] . 'payment/wechat/notify.php');
    if (!empty($params['user']) && is_numeric($params['user'])) {
        $params['user'] = mc_uid2openid($params['user']);
    }
    $params['title'] = empty($params['title']) ? '测试支付' : $params['title'];
    $data = $pay->payTransactionsJsapi($params['title'], $params['uniontid'], $params['fee'] * 100, $params['user'], array('attach' => json_encode(array('uniacid' => $_W['uniacid']))))->toArray();
    if (empty($data['appId'])) {
        return error(-1, '支付失败！');
    }
    return $data;
}

function payment_proxy_pay_account() {
    global $_W;
    $setting = uni_setting($_W['uniacid'], array('payment'));
    $setting['payment']['wechat']['switch'] = intval($setting['payment']['wechat']['switch']);

    if ($setting['payment']['wechat']['switch'] == PAYMENT_WECHAT_TYPE_SERVICE) {
        $uniacid = intval($setting['payment']['wechat']['service']);
    } elseif ($setting['payment']['wechat']['switch'] == PAYMENT_WECHAT_TYPE_BORROW) {
        $uniacid = intval($setting['payment']['wechat']['borrow']);
    } else {
        $uniacid = 0;
    }
    $pay_account = uni_fetch($uniacid);
    if (empty($uniacid) || empty($pay_account)) {
        return error(1);
    }
    return WeAccount::createByUniacid($uniacid);
}
function payment_types($type = '') {
    $pay_types = array(
        'delivery' => '货到支付',
        'credit' => '余额支付',
        'mix' => '混合支付',
        'alipay' => '支付宝支付',
        'wechat' => '微信支付',
        'wechat_facilitator' => '服务商支付',
        'unionpay' => '银联支付',
        'baifubao' => '百度钱包支付',
        'line' => '汇款支付',
    );
    return !empty($pay_types[$type]) ? $pay_types[$type] : $pay_types;
}
function payment_setting() {
    global $_W;
    $setting = uni_setting_load('payment', $_W['uniacid']);
    $pay_setting = is_array($setting['payment']) ? $setting['payment'] : array();
    if (empty($pay_setting['delivery'])) {
        $pay_setting['delivery'] = array(
            'recharge_switch' => false,
            'pay_switch' => false,
        );
    }
    if (empty($pay_setting['mix'])) {
        $pay_setting['mix'] = array(
            'recharge_switch' => false,
            'pay_switch' => false,
        );
    }
    if (empty($pay_setting['credit'])) {
        $pay_setting['credit'] = array(
            'recharge_switch' => false,
            'pay_switch' => false,
        );
    }
    if (empty($pay_setting['alipay'])) {
        $pay_setting['alipay'] = array(
            'recharge_switch' => false,
            'pay_switch' => false,
            'partner' => '',
            'secret' => '',
        );
    }
    if (empty($pay_setting['wechat'])) {
        $pay_setting['wechat'] = array(
            'recharge_switch' => false,
            'pay_switch' => false,
            'switch' => false,
        );
    } else {
        if (!in_array($pay_setting['wechat']['switch'], array('1'))) {
            unset($pay_setting['wechat']['signkey']);
        }
    }
    if (empty($pay_setting['unionpay'])) {
        $pay_setting['unionpay'] = array(
            'recharge_switch' => false,
            'pay_switch' => false,
            'merid' => '',
            'signcertpwd' => '',
        );
    }
    if (empty($pay_setting['baifubao'])) {
        $pay_setting['baifubao'] = array(
            'recharge_switch' => false,
            'pay_switch' => false,
            'mchid' => '',
            'signkey' => '',
        );
    }
    if (empty($pay_setting['line'])) {
        $pay_setting['line'] = array(
            'recharge_switch' => false,
            'pay_switch' => false,
            'message' => '',
        );
    }
    
    //废弃微信借用支付
    if (empty($_W['isfounder'])) {
        $user_account_list = pdo_getall('uni_account_users', array('uid' => $_W['uid']), array(), 'uniacid');
        $param['uniacid'] = array_keys($user_account_list);
    }
    $pay_setting['unionpay']['signcertexists'] = file_exists(IA_ROOT . '/attachment/unionpay/PM_' . md5(complex_authkey() . $_W['uniacid']) . '_acp.pfx');
    $no_recharge_types = array('delivery', 'credit', 'mix', 'line');
    $has_config_keys = array('pay_switch', 'recharge_switch', 'has_config', 'recharge_set', 'signcertexists', 'support_set');
    if ($pay_setting['wechat']['switch'] == 1) {
        if ($pay_setting['wechat']['version'] == 1) {
            unset($pay_setting['wechat']['mchid'], $pay_setting['wechat']['apikey']);
        } elseif ($pay_setting['wechat']['version'] == 2) {
            unset($pay_setting['wechat']['partner'], $pay_setting['wechat']['key'], $pay_setting['wechat']['signkey']);
        }
        unset($pay_setting['wechat']['borrow'], $pay_setting['wechat']['sub_mch_id'], $pay_setting['wechat']['service']);
    } elseif ($pay_setting['wechat']['switch'] == 2) {
        unset($pay_setting['wechat']['mchid'], $pay_setting['wechat']['apikey'], $pay_setting['wechat']['partner'], $pay_setting['wechat']['key'], $pay_setting['wechat']['signkey'], $pay_setting['wechat']['sub_mch_id'], $pay_setting['wechat']['service']);
    } elseif ($pay_setting['wechat']['switch'] == 3) {
        unset($pay_setting['wechat']['mchid'], $pay_setting['wechat']['apikey'], $pay_setting['wechat']['partner'], $pay_setting['wechat']['key'], $pay_setting['wechat']['signkey'], $pay_setting['wechat']['borrow']);
    }
    foreach ($pay_setting as $type => &$value) {
        if (empty($value) || !is_array($value)) {
            continue;
        }
        if (isset($value['recharge_switch'])) {
            $value['recharge_switch'] = false == $value['recharge_switch'] ? false : true;
        }
        if (isset($value['pay_switch'])) {
            $value['pay_switch'] = false == $value['pay_switch'] ? false : true;
        }
        $value['has_config'] = true;
        $value['recharge_set'] = true;
        $value['support_set'] = true;
        if (in_array($type, $no_recharge_types)) {
            $value['recharge_set'] = false;
        }
        if (!empty($value['pay_switch']) || !empty($value['recharge_switch'])) {
            $value['support_set'] = false;
        }
        foreach ($value as $key => $val) {
            if (!in_array($key, $has_config_keys) && empty($val)) {
                $value['has_config'] = false;
                continue;
            }
        }
    }
    unset($value);
    return $pay_setting;
}
