<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/payment/wechat/notify.php : v a4b6a17a6d8a : 2015/09/14 08:41:00 : yanghf $
 */

require '../../framework/bootstrap.inc.php';
load()->web('common');
$input = file_get_contents('php://input');

if (!empty($input)) {
    load()->library('sdk-module');
    $appEncryptor = new \W7\Sdk\Module\Support\AppEncryptor($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['token'], $_W['setting']['server_setting']['encodingaeskey']);
    $wechat_data = $appEncryptor->decrypt($input);
    if (empty($wechat_data)) {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => ''
        );
        echo array2xml($result);
        exit;
    }
    if ($wechat_data['refund_status'] != 'SUCCESS') {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => $wechat_data['return_msg']
        );
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
$pay_log = table('core_paylog')
    ->where(array('uniontid' => $wechat_data['out_trade_no']))
    ->get();
$refund_log = table('core_refundlog')
    ->where(array('refund_uniontid' => $wechat_data['out_refund_no']))
    ->get();

if (!empty($refund_log) && $refund_log['status'] == '0' && (($wechat_data['amount']['payer_refund'] / 100) == $pay_log['card_fee'])) {
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
