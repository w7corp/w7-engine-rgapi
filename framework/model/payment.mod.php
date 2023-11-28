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
    if (!empty($data['timeStamp'])) {
        $data['timeStamp'] = (string)$data['timeStamp'];
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
