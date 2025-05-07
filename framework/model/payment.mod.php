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
    $account = pdo_get('account', ['uniacid' => $_W['uniacid']]);
    $uniacid = $account['uniacid'];
    $pay_setting = payment_setting();
    $wechat = $pay_setting['wechat'];
    if (empty($wechat['pay_switch'])) {
        return error(-1, '未开启微信支付！');
    }

    if (!empty($wechat['platform_public_key'])) {
        load()->library('wechatpayv3');
        $wechatpayConfig = [
            'mchid' => $wechat['mchid'],
            'serial' => $wechat['ertificate_serial_number'],
            'privateKey' => WeChatPay\Crypto\Rsa::from($wechat['apiclient_key'], WeChatPay\Crypto\Rsa::KEY_TYPE_PRIVATE),
            'certs' => [
                $wechat['platform_public_key_id'] => WeChatPay\Crypto\Rsa::from($wechat['platform_public_key'], WeChatPay\Crypto\Rsa::KEY_TYPE_PUBLIC),
            ],
        ];
        if (!empty($wechat['ertificate_serial_number_expired']) && !empty($wechat['wechat_platform_certificate_expired'])) {
            $wechatpayConfig['certs'][$wechat['ertificate_serial_number_expired']] = $wechat['wechat_platform_certificate_expired'][0];
        }
        $instance = WeChatPay\Builder::factory($wechatpayConfig);
        $wechatpayPost = ['json' => [
            'mchid'        => $wechat['mchid'],
            'out_trade_no' => $params['uniontid'],
            'appid'        => $account['app_id'],
            'description'  => cutstr($params['title'], 26),
            'notify_url'   => $_W['siteroot'] . 'payment/wechat/notify.php/' . $_W['uniacid'],
            'amount'       => ['total'    => $params['fee'] * 100,'currency' => 'CNY'],
            'payer' => ['openid' => empty($params['user']) ? $_W['fans']['from_user'] : $params['user']],
            'attach' => (string) $_W['uniacid']
        ]];
        try {
            $resp = $instance->chain('v3/pay/transactions/jsapi')->post($wechatpayPost);
        } catch (RequestException $e) {
            return error(-1, $e->getMessage());
        }
    } else {
        load()->library('wechatpay-v3');
        $merchantId = $wechat['mchid'];
        $merchantSerialNumber = $wechat['ertificate_serial_number'];
        $wechatpayCertificate = $wechat['wechat_platform_certificate'];
        $merchantPrivateKey = $wechat['apiclient_key'];
        $wechatpayMiddleware = WechatPay\GuzzleMiddleware\WechatPayMiddleware::builder()
            ->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey)
            ->withWechatPay($wechatpayCertificate)
            ->build();
        $stack = GuzzleHttp\HandlerStack::create();
        $stack->push($wechatpayMiddleware, 'wechatpay');
        $client = new GuzzleHttp\Client(['handler' => $stack]);
        try {
            $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi', [
                'json' => [
                    'appid' => $account['app_id'],
                    'mchid' => $wechat['mchid'],
                    'description' => cutstr($params['title'], 26),
                    'out_trade_no' => $params['uniontid'],
                    'notify_url' => $_W['siteroot'] . 'payment/wechat/notify.php/' . $uniacid,
                    'amount' => ['total' => $params['fee'] * 100, 'currency' => 'CNY'],
                    'payer' => ['openid' => empty($params['user']) ? $_W['fans']['from_user'] : $params['user']],
                    'attach' => (string) $uniacid,
                ],
                'headers' => [ 'Accept' => 'application/json' ],
            ]);
        } catch (RequestException $e) {
            return error(-1, $e->getMessage());
        }
    }
    if ($resp->getStatusCode() < 200 || $resp->getStatusCode() > 299) {
        return error(-1, "支付失败： code={$resp->getStatusCode()}, body=[{$resp->getBody()}]");
    }
    $resp = json_decode($resp->getBody(), true);
    $prepayid = $resp['prepay_id'];
    $wOpt['appId'] = $account['app_id'];
    $wOpt['timeStamp'] = strval(TIMESTAMP);
    $wOpt['nonceStr'] = random(32);
    $wOpt['package'] = 'prepay_id=' . $prepayid;
    $wOpt['signType'] = 'RSA';
    $rsa = $wOpt['appId'] . "\n" . $wOpt['timeStamp'] . "\n" . $wOpt['nonceStr'] . "\n" . $wOpt['package'] . "\n";
    openssl_sign($rsa, $raw_sign, $wechat['apiclient_key'], 'sha256WithRSAEncryption');
    $wOpt['paySign'] = base64_encode($raw_sign);
    return $wOpt;
}

function wechat_build_native($params) {
    if (empty($params['uniontid']) || empty($params['fee']) || empty($params['description'])) {
        return error(-1, '参数错误！');
    }
    load()->classs('pay');
    $wechat = Pay::create();
    return $wechat->buildNative($params);
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

function payment_setting() {
    global $_W;
    $setting = uni_setting_load('payment', $_W['uniacid']);
    $pay_setting = is_array($setting['payment']) ? $setting['payment'] : [];
    if (empty($pay_setting['alipay'])) {
        $pay_setting['alipay'] = array(
            'refund_switch' => STATUS_OFF,
            'pay_switch' => STATUS_OFF,
        );
    }
    if (empty($pay_setting['alipay']['pay_switch'])) {
        $pay_setting['alipay']['pay_switch'] = STATUS_OFF;
    }
    if (empty($pay_setting['alipay']['refund_switch'])) {
        $pay_setting['alipay']['refund_switch'] = STATUS_OFF;
    }
    if (empty($pay_setting['wechat'])) {
        $pay_setting['wechat'] = array(
            'refund_switch' => STATUS_OFF,
            'pay_switch' => STATUS_OFF,
        );
    }
    if (empty($pay_setting['wechat']['pay_switch'])) {
        $pay_setting['wechat']['pay_switch'] = STATUS_OFF;
    }
    if (empty($pay_setting['wechat']['refund_switch'])) {
        $pay_setting['wechat']['refund_switch'] = STATUS_OFF;
    }
    //废弃微信借用支付
    $has_config_keys = array('pay_switch', 'refund_switch', 'has_config');
    foreach ($pay_setting as &$value) {
        if (empty($value) || !is_array($value)) {
            continue;
        }
        $value['has_config'] = STATUS_OFF;
        foreach ($value as $key => $val) {
            if (!in_array($key, $has_config_keys) && !empty($val)) {
                $value['has_config'] = STATUS_ON;
            }
        }
    }
    unset($value);
    return $pay_setting;
}
