<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/payment/wechat/notify.php : v a4b6a17a6d8a : 2015/09/14 08:41:00 : yanghf $
 */
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
$input = file_get_contents('php://input');
$isxml = true;

if (!empty($input) && empty($_GET['out_trade_no'])) {
    load()->library('sdk-module');
    $appEncryptor = new \W7\Sdk\Module\Support\AppEncryptor($_W['setting']['server_setting']['app_id'], $_W['setting']['server_setting']['token'], $_W['setting']['server_setting']['encodingaeskey']);
    $data = $appEncryptor->decrypt($input);
    if (empty($data)) {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => ''
        );
        echo array2xml($result);
        exit;
    }
    if ($data['trade_state'] != 'SUCCESS') {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => empty($data['return_msg']) ? $data['err_code_des'] : $data['return_msg']
        );
        echo array2xml($result);
        exit;
    }
    $get = $data;
} else {
    $isxml = false;
    $get = $_GET;
}
load()->web('common');
load()->classs('coupon');
$get['attach'] = json_decode($get['attach'], true);
$_W['uniacid'] = $_W['weid'] = intval($get['attach']['uniacid']);
$_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
WeUtility::logging('pay', var_export($get, true));
$log = table('core_paylog')
    ->where(array('uniontid' => $get['out_trade_no']))
    ->get();

if (!empty($log) && $log['status'] == '0' && (($get['amount']['payer_total'] / 100) == $log['card_fee'])) {
    $log['tag'] = iunserializer($log['tag']);
    $log['tag']['transaction_id'] = $get['transaction_id'];
    $log['uid'] = $log['tag']['uid'];
    $record = array();
    $record['status'] = '1';
    $record['tag'] = iserializer($log['tag']);
    $coupon_info = array();
    if (!empty($get['coupon_count'])) {
        $coupon_info['settlement_total_fee'] = empty($get['settlement_total_fee']) ? '' : $get['settlement_total_fee'];
        foreach ($get as $key => $value) {
            if ('coupon_' == substr($key, 0, 7)) {
                $coupon_info[$key] = $value;
            }
        }
    }
    $record['coupon'] = empty($coupon_info) ? '' : iserializer($coupon_info);
    table('core_paylog')
        ->where(array('plid' => $log['plid']))
        ->fill($record)
        ->save();
    $mix_pay_credit_log = table('core_paylog')
        ->where(array(
            'module' => $log['module'],
            'tid' => $log['tid'],
            'uniacid' => $log['uniacid'],
            'type' => 'credit'
        ))
        ->get();
    if (!empty($mix_pay_credit_log)) {
        table('core_paylog')
            ->where(array('plid' => $mix_pay_credit_log['plid']))
            ->fill(array('status' => 1))
            ->save();
        $log['fee'] = $mix_pay_credit_log['fee'] + $log['fee'];
        $log['card_fee'] = $mix_pay_credit_log['fee'] + $log['card_fee'];
        $setting = uni_setting($_W['uniacid'], array('creditbehaviors'));
        $credtis = mc_credit_fetch($log['uid']);
        mc_credit_update($log['uid'], $setting['creditbehaviors']['currency'], -$mix_pay_credit_log['fee'], array($log['uid'], '??????' . $setting['creditbehaviors']['currency'] . ':' . $fee));
    }
    if ($log['type'] == 'wxapp') {
        $site = WeUtility::createModuleWxapp($log['module']);
    } else {
        $site = WeUtility::createModuleSite($log['module']);
    }
    if (!is_error($site)) {
        $method = 'payResult';
        if (method_exists($site, $method)) {
            $ret = array();
            $ret['weid'] = $log['weid'];
            $ret['uniacid'] = $log['uniacid'];
            $ret['acid'] = $log['acid'];
            $ret['result'] = 'success';
            $ret['type'] = $log['type'];
            $ret['from'] = 'notify';
            $ret['tid'] = $log['tid'];
            $ret['uniontid'] = $log['uniontid'];
            $ret['transaction_id'] = $log['transaction_id'];
            $ret['trade_type'] = $get['trade_type'];
            $ret['follow'] = $get['is_subscribe'] == 'Y' ? 1 : 0;
            $ret['user'] = empty($log['openid']) ? $get['openid'] : $log['openid'];
            $ret['fee'] = $log['fee'];
            $ret['tag'] = $log['tag'];
            $ret['is_usecard'] = $log['is_usecard'];
            $ret['card_type'] = $log['card_type'];
            $ret['card_fee'] = $log['card_fee'];
            $ret['card_id'] = $log['card_id'];
            $ret['coupon'] = $coupon_info;
            if (!empty($get['time_end'])) {
                $ret['paytime'] = strtotime($get['time_end']);
            }
            $site->$method($ret);
            if ($isxml) {
                $result = array(
                    'return_code' => 'SUCCESS',
                    'return_msg' => 'OK'
                );
                echo array2xml($result);
                exit;
            } else {
                exit('success');
            }
        }
    }
}
if ($isxml) {
    $result = array(
        'return_code' => 'SUCCESS',
        'return_msg' => 'OK'
    );
    echo array2xml($result);
    exit;
} else {
    exit('fail');
}
