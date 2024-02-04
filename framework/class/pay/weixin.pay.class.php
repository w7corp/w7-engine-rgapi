<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$.
 */
defined('IN_IA') or exit('Access Denied');
class WeiXinPay extends pay {
    public function buildNative($params) {
        global $_W;
        load()->model('payment');
        load()->library('wechatpay-v3');
        $account = pdo_get('account', ['uniacid' => $_W['uniacid']]);
        $uniacid = $account['uniacid'];
        $pay_setting = payment_setting();
        $wechat = $pay_setting['wechat'];
        if (empty($wechat['pay_switch'])) {
            return error(-1, '未开启微信支付！');
        }
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
            $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/pay/transactions/native', [
                'json' => [
                    'appid' => $account['app_id'],
                    'mchid' => $wechat['mchid'],
                    'description' => cutstr($params['description'], 26),
                    'out_trade_no' => $params['uniontid'],
                    'notify_url' => $_W['siteroot'] . 'payment/wechat/notify.php/' . $uniacid,
                    'amount' => ['total' => $params['fee'] * 100, 'currency' => 'CNY'],
                    'attach' => (string) $uniacid,
                ],
                'headers' => [ 'Accept' => 'application/json' ],
            ]);
            if ($resp->getStatusCode() < 200 || $resp->getStatusCode() > 299) {
                return error(-1, "支付失败： code={$resp->getStatusCode()}, body=[{$resp->getBody()}]");
            }
            $resp = json_decode($resp->getBody(), true);
            return $resp;
        } catch (RequestException $e) {
            return error(-1, $e->getMessage());
        }
    }
    /*
     * 申请退款V3
     * $params 退款参数
     * */
    public function refundV3($params) {
        global $_W;
        load()->library('wechatpay-v3');
        $setting = uni_setting_load('payment', $_W['uniacid']);
        $pay_setting = empty($setting['payment']['wechat']) ? [] : $setting['payment']['wechat'];

        $merchantId = $pay_setting['mchid'];
        $merchantSerialNumber = $pay_setting['ertificate_serial_number'];
        $wechatpayCertificate = $pay_setting['wechat_platform_certificate'];
        $merchantPrivateKey = $pay_setting['apiclient_key'];
        $wechatpayMiddleware = WechatPay\GuzzleMiddleware\WechatPayMiddleware::builder()
            ->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey)
            ->withWechatPay($wechatpayCertificate)
            ->build();
        $stack = GuzzleHttp\HandlerStack::create();
        $stack->push($wechatpayMiddleware, 'wechatpay');
        $client = new \GuzzleHttp\Client(['handler' => $stack]);

        try {
            $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds', [
                'json' => $params,
                'headers' => [ 'Accept' => 'application/json' ]
            ]);
            if ($resp->getStatusCode() < 200 || $resp->getStatusCode() > 299) {
                return error(-1, "退款失败： code={$resp->getStatusCode()}, body=[{$resp->getBody()}]");
            }
            $result = json_decode($resp->getBody(), true);
            if (!in_array($result['status'], ['SUCCESS', 'PROCESSING'])) {
                return error(-1, "退款失败： code={$resp->getStatusCode()}, body=[{$resp->getBody()}]");
            }
            return $result;
        } catch (\Exception $e) {
            return error(-1, $e->getMessage());
        }
    }
}
