<?php
/**
 * 支付参数配置
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('payment');
load()->model('account');
load()->func('communication');

$dos = array('save_setting', 'display', 'test_alipay', 'test_wechat', 'get_setting', 'switch', 'change_status');
$do = in_array($do, $dos) ? $do : 'display';
$module_name = safe_gpc_string($_GPC['module_name'] ?? $_GPC['m']);
$module = $_W['current_module'] = module_fetch($module_name);
if (empty($module)) {
    itoast('抱歉，你操作的模块不能被访问！');
}
$account_type = empty($_GPC['account_type']) ? ACCOUNT_TYPE_OFFCIAL_NORMAL : intval($_GPC['account_type']);
$account = pdo_get('account', ['type' => $account_type]);
$_W['uniacid'] = $account['uniacid'];

if ('get_setting' == $do) {
    $pay_setting = payment_setting();
    iajax(0, $pay_setting, '');
}

if ('test_alipay' == $do) {
    $alipay = safe_gpc_array($_GPC['param']);
    $pay_data = array(
        'uniacid' => $_W['uniacid'],
        'acid' => $_W['acid'],
        'uniontid' => date('Ymd', time()) . time(),
        'module' => 'system',
        'fee' => '0.01',
        'status' => 0,
        'card_fee' => 0.01,
    );
    $params = array();
    $params['tid'] = md5(uniqid());
    $params['user'] = '测试用户';
    $params['fee'] = '0.01';
    $params['title'] = '测试支付接口';
    $params['uniontid'] = $pay_data['uniontid'];
    $result = alipay_build($params, $alipay);
    if (is_error($result) || empty($result['url'])) {
        iajax(1, '支付参数异常！');
    } else {
        iajax(0, $result['url']);
    }
}

if ('test_wechat' == $do) {
    $wechat = safe_gpc_array($_GPC['param']);
    $param = [
        'pay_way' => 'web',
        'title' => '测试商品标题',
        'uniontid' => md5(uniqid()),
        'fee' => '0.01',
        'goodsid' => '1',
        'user' => 'ohaAI0SNeumOyyg01jm7cNd1btEs'
//        'user' => 'o93_16EShLIq_c-TLLRHR2T5Me-s'
    ];
    $wechat_result = wechat_build($param);
    if (is_error($wechat_result)) {
        iajax(1, $wechat_result['message']);
    } else {
        iajax(0, $wechat_result);
    }
}

if ('save_setting' == $do) {
    $type = safe_gpc_string($_GPC['type']);
    $param = safe_gpc_array($_GPC['param']);
    $setting = uni_setting_load('payment', $_W['uniacid']);
    $pay_setting = empty($setting['payment']) ? ['wechat' => [], 'alipay' => []] : $setting['payment'];
    if ('wechat' == $type) {
        $param['account'] = $_W['uniacid'];
        $param['mchid'] = safe_gpc_string($_GPC['mchid']);
        $param['apikey'] = safe_gpc_string($_GPC['apikey']);
        $param['ertificate_serial_number'] = safe_gpc_string($_GPC['ertificate_serial_number']);
        if (!empty($_FILES['apiclient_cert'])) {
            $param['apiclient_cert'] = file_get_contents($_FILES['apiclient_cert']['tmp_name']);
            if (strexists($param['apiclient_cert'], '<?php') || '-----BEGIN CERTIFICATE-----' != substr($param['apiclient_cert'], 0, 27) || '-----END CERTIFICATE-----' != substr($param['apiclient_cert'], -26, 25)) {
                iajax(-1, 'apiclient_cert.pem证书内容不合法！');
            }
        }
        if (!empty($_FILES['apiclient_key'])) {
            $param['apiclient_key'] = file_get_contents($_FILES['apiclient_key']['tmp_name']);
            if (strexists($param['apiclient_key'], '<?php') || '-----BEGIN PRIVATE KEY-----' != substr($param['apiclient_key'], 0, 27) || '-----END PRIVATE KEY-----' != substr($param['apiclient_key'], -26, 25)) {
                iajax(-1, 'apiclient_key.pem证书内容不合法！');
            }
        }
        load()->library('wechatpay-v3');
        $merchantId = $param['mchid'];
        $merchantSerialNumber = $param['ertificate_serial_number'];
        if (!empty($param['apiclient_key'])) {
            $merchantPrivateKey = $param['apiclient_key'];
        }
        if (empty($merchantPrivateKey) && !empty($pay_setting['wechat']['apiclient_key'])) {
            $merchantPrivateKey = $pay_setting['wechat']['apiclient_key'];
        }
        if (empty($merchantPrivateKey)) {
            iajax(-1, 'apiclient_key.pem证书必须传！');
        }
        $wechatpayMiddleware = WechatPay\GuzzleMiddleware\WechatPayMiddleware::builder()
            ->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey)
            ->withValidator(new WechatPay\GuzzleMiddleware\NoopValidator)
            ->build();
        $stack = GuzzleHttp\HandlerStack::create();
        $stack->push($wechatpayMiddleware, 'wechatpay');
        $client = new GuzzleHttp\Client(['handler' => $stack]);
        try {
            $canonical_url = '/v3/certificates';
            $http_method = 'GET';
            $timestamp = time();
            $nonce = random(32);
            $message = $http_method . "\n" . $canonical_url . "\n" . $timestamp . "\n" . $nonce . "\n\n";
            openssl_sign($message, $raw_sign, $merchantPrivateKey, 'sha256WithRSAEncryption');
            $sign = base64_encode($raw_sign);
            $schema = "WECHATPAY2-SHA256-RSA2048";
            $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"', $merchantId, $nonce, $timestamp, $merchantSerialNumber, $sign);
            $resp = $client->request('GET', 'https://api.mch.weixin.qq.com/v3/certificates', [
                'headers' => [
                    'Authorization' => $schema . ' ' . $token,
                    'Accept' => 'application/json',
                    'User-Agent' => '用户代理(https://zh.wikipedia.org/wiki/User_agent)',
                ]
            ]);
            if ($resp->getStatusCode() < 200 || $resp->getStatusCode() > 299) {
                iajax(-1, "download failed, code={$resp->getStatusCode()}, body=[{$resp->getBody()}]");
            }
            $list = json_decode($resp->getBody(), true);
            $plain_certs = [];
            $decrypter = new WechatPay\GuzzleMiddleware\Util\AesUtil($param['apikey']);
            foreach ($list['data'] as $item) {
                $encCert = $item['encrypt_certificate'];
                $plain = $decrypter->decryptToString($encCert['associated_data'], $encCert['nonce'], $encCert['ciphertext']);
                if (empty($plain)) {
                    iajax(-1, "微信平台证书解密失败!");
                }
                $plain_certs[] = $plain;
            }
            $param['wechat_platform_certificate'] = $plain_certs;
        } catch (GuzzleHttp\Exception\RequestException $e) {
            iajax(-1, $e->getMessage());
        }
    }
    if ('alipay' == $type) {
        $param['account'] = safe_gpc_string($_GPC['account']);
        $param['partner'] = safe_gpc_string($_GPC['partner']);
        $param['secret'] = safe_gpc_string($_GPC['secret']);
        $param['app_id'] = safe_gpc_string($_GPC['app_id']);
        if (!empty($_FILES['private_key'])) {
            $param['private_key'] = file_get_contents($_FILES['private_key']['tmp_name']);
            if (strexists($param['private_key'], '<?php') || '-----BEGIN RSA PRIVATE KEY-' != substr($param['private_key'], 0, 27) || 'ND RSA PRIVATE KEY-----' != substr($param['private_key'], -24, 23)) {
                iajax(-1, 'rsa_private_key.pem证书内容不合法，请重新上传！');
            }
        }
    }
    $pay_setting[$type] = array_merge($pay_setting[$type], $param);
    uni_setting_save('payment', $pay_setting);
    if ($_W['isajax']) {
        iajax(0, '设置成功！', referer());
    }
    itoast('设置成功！', referer(), 'success');
}

if ('change_status' == $do) {
    $pay_type = in_array($_GPC['pay_type'], ['alipay', 'wechat']) ? safe_gpc_string($_GPC['pay_type']) : '';
    if (empty($pay_type)) {
        iajax(-1, '参数错误！');
    }
    $switch_type = safe_gpc_string($_GPC['switch_type']);
    $switch_type = in_array($_GPC['switch_type'], ['pay_switch', 'refund_switch']) ? safe_gpc_string($_GPC['switch_type']) : '';
    $setting = uni_setting_load('payment', $_W['uniacid']);
    $pay_setting = !empty($setting['payment']) ? $setting['payment'] : [];
    $pay_setting[$pay_type][$switch_type] = !$pay_setting[$pay_type][$switch_type] ? STATUS_ON : STATUS_OFF;
    uni_setting_save('payment', $pay_setting);
    iajax(0, '设置成功！', referer());
}

if ('display' == $do) {
    $pay_setting = payment_setting();
}
template('profile/payment');
