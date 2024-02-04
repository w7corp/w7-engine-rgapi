<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/payment/wechat/notify.php : v a4b6a17a6d8a : 2015/09/14 08:41:00 : yanghf $
 */

require '../../framework/bootstrap.inc.php';
load()->library('wechatpay-v3');
$input = file_get_contents('php://input');
WeUtility::logging('refund-input', var_export($input, true));
if (!empty($input)) {
    $wechat_data = json_decode($input, true);
    if (empty($wechat_data)) {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => ''
        );
        echo array2xml($result);
        exit;
    }
    if (!empty($wechat_data) && 'REFUND.SUCCESS' != $wechat_data['event_type']) {
        echo json_encode(['code' => 'FAIL', 'message' => '失败']);
        exit;
    }
} else {
    $result = array(
        'return_code' => 'FAIL',
        'return_msg' => ''
    );
    echo array2xml($result);
    exit;
}
WeUtility::logging('refund', var_export($wechat_data, true));
$_W['uniacid'] = substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/') + 1);
$setting = uni_setting($_W['uniacid'], array('payment'));
$key = $setting['payment']['wechat']['apikey'];
$decrypter = new WechatPay\GuzzleMiddleware\Util\AesUtil($key);
$plain = $decrypter->decryptToString(
    $wechat_data['resource']['associated_data'],
    $wechat_data['resource']['nonce'],
    $wechat_data['resource']['ciphertext']
);
$wechat_data = json_decode($plain, true);
if (empty($wechat_data)) {
    WeUtility::logging('refund-fail', var_export($wechat_data, true));
    echo json_encode(['code' => 'FAIL', 'message' => '解密失败']);
    exit;
}
WeUtility::logging('refund-resource', var_export($wechat_data, true));
$pay_log = table('core_paylog')
    ->where(array('uniontid' => $wechat_data['out_trade_no']))
    ->get();
$refund_log = table('core_refundlog')
    ->where(array('refund_uniontid' => $wechat_data['out_refund_no']))
    ->get();

if (!empty($refund_log) && $refund_log['status'] == '0' && (($wechat_data['amount']['payer_refund'] / 100) == $pay_log['fee'])) {
    table('core_refundlog')
        ->where(array('id' => $refund_log['id']))
        ->fill(array('status' => 1))
        ->save();
    $site = WeUtility::createModuleSite($pay_log['module']);
    if (!is_error($site)) {
        $method = 'refundResult';
        if (method_exists($site, $method)) {
            $ret = array();
            $ret['uniacid'] = $pay_log['uniacid'];
            $ret['result'] = 'success';
            $ret['type'] = $pay_log['type'];
            $ret['from'] = 'refund';
            $ret['tid'] = $pay_log['tid'];
            $ret['uniontid'] = $pay_log['uniontid'];
            $ret['refund_uniontid'] = $refund_log['refund_uniontid'];
            $ret['user'] = $pay_log['openid'];
            $ret['fee'] = $wechat_data['amount']['payer_refund'];
            if (!empty($wechat_data['success_time'])) {
                $ret['refund_time'] = strtotime($wechat_data['success_time']);
            }
            $site->$method($ret);
            exit('success');
        }
    }
}
